# WP S3 Backup — Terraform Infrastructure Guide

## Overview

This Terraform configuration creates the AWS infrastructure needed by the WP S3 Backup plugin:
- S3 bucket with encryption, versioning, and lifecycle rules
- IAM user with least-privilege access to the bucket only
- Access key for the IAM user (to enter in the plugin settings)

---

## Prerequisites

- [Terraform](https://developer.hashicorp.com/terraform/install) installed (v1.5+)
- AWS CLI configured with credentials that can create S3 buckets and IAM users
- An AWS account

---

## Quick Start

```bash
cd terraform/

# Initialize Terraform
terraform init

# Preview what will be created
terraform plan -var="bucket_suffix=mysite-backups"

# Create the infrastructure
terraform apply -var="bucket_suffix=mysite-backups"

# Get the credentials for the plugin
terraform output -json
```

---

## What Gets Created

### S3 Bucket

| Setting | Value | Why |
|---------|-------|-----|
| Name | `wp-s3-backup-{suffix}` | Globally unique |
| Encryption | AES-256 (SSE-S3) | Data encrypted at rest |
| Versioning | Enabled | Protects against accidental overwrites |
| Public Access | All blocked | No public exposure |
| Lifecycle: Transition | 30 days → Glacier Instant Retrieval | Cost savings for old backups |
| Lifecycle: Expiration | 90 days → Delete | Automatic cleanup |

### IAM User

| Setting | Value | Why |
|---------|-------|-----|
| Name | `wp-s3-backup-{suffix}-user` | Dedicated service account |
| Policy | Scoped to bucket only | Least privilege |
| Permissions | Put, Get, Delete, List, Multipart | Only what the plugin needs |

---

## Cost Estimate

For a typical WordPress site with 500MB backups:

| Component | Monthly Cost |
|-----------|-------------|
| S3 Standard (30 days × 500MB daily) | ~$0.35 |
| S3 Glacier Instant (days 31-90) | ~$0.12 |
| PUT/GET requests | ~$0.01 |
| **Total** | **~$0.48/month** |

With weekly backups instead of daily: ~$0.10/month.

---

## Terraform Code Explanation

### Variables (`variables.tf`)

```hcl
variable "bucket_suffix" {
  description = "Unique suffix for the S3 bucket name (e.g., mysite-backups)"
  type        = string

  validation {
    condition     = can(regex("^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$", var.bucket_suffix))
    error_message = "Bucket suffix must be lowercase alphanumeric with hyphens/dots, 3-63 chars."
  }
}

variable "aws_region" {
  description = "AWS region for the S3 bucket"
  type        = string
  default     = "us-east-1"
}

variable "glacier_transition_days" {
  description = "Days before transitioning backups to Glacier Instant Retrieval"
  type        = number
  default     = 30
}

variable "expiration_days" {
  description = "Days before permanently deleting backups"
  type        = number
  default     = 90
}

variable "tags" {
  description = "Tags to apply to all resources"
  type        = map(string)
  default = {
    Project   = "wp-s3-backup"
    ManagedBy = "terraform"
  }
}
```

### Main Configuration (`main.tf`)

```hcl
terraform {
  required_version = ">= 1.5"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = var.aws_region
}

# ─── S3 Bucket ───────────────────────────────────────────

resource "aws_s3_bucket" "backup" {
  bucket = "wp-s3-backup-${var.bucket_suffix}"
  tags   = var.tags
}

# Enable versioning to protect against accidental overwrites
resource "aws_s3_bucket_versioning" "backup" {
  bucket = aws_s3_bucket.backup.id

  versioning_configuration {
    status = "Enabled"
  }
}

# Encrypt all objects at rest with AES-256
resource "aws_s3_bucket_server_side_encryption_configuration" "backup" {
  bucket = aws_s3_bucket.backup.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
    bucket_key_enabled = true
  }
}

# Block ALL public access
resource "aws_s3_bucket_public_access_block" "backup" {
  bucket = aws_s3_bucket.backup.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

# Lifecycle rules for cost optimization
resource "aws_s3_bucket_lifecycle_configuration" "backup" {
  bucket = aws_s3_bucket.backup.id

  rule {
    id     = "backup-lifecycle"
    status = "Enabled"

    filter {
      prefix = "backups/"
    }

    # Move to Glacier Instant Retrieval after N days
    transition {
      days          = var.glacier_transition_days
      storage_class = "GLACIER_IR"
    }

    # Delete after N days
    expiration {
      days = var.expiration_days
    }

    # Clean up incomplete multipart uploads after 7 days
    abort_incomplete_multipart_upload {
      days_after_initiation = 7
    }

    # Clean up old versions after 30 days
    noncurrent_version_expiration {
      noncurrent_days = 30
    }
  }
}

# ─── IAM User ────────────────────────────────────────────

resource "aws_iam_user" "backup" {
  name = "wp-s3-backup-${var.bucket_suffix}-user"
  tags = var.tags
}

resource "aws_iam_access_key" "backup" {
  user = aws_iam_user.backup.name
}

# Least-privilege policy: only access this specific bucket
resource "aws_iam_user_policy" "backup" {
  name = "wp-s3-backup-${var.bucket_suffix}-policy"
  user = aws_iam_user.backup.name

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid    = "AllowBucketListing"
        Effect = "Allow"
        Action = [
          "s3:ListBucket",
        ]
        Resource = aws_s3_bucket.backup.arn
      },
      {
        Sid    = "AllowObjectOperations"
        Effect = "Allow"
        Action = [
          "s3:PutObject",
          "s3:GetObject",
          "s3:DeleteObject",
          "s3:AbortMultipartUpload",
          "s3:ListMultipartUploadParts",
        ]
        Resource = "${aws_s3_bucket.backup.arn}/*"
      },
    ]
  })
}
```

### Outputs (`outputs.tf`)

```hcl
output "bucket_name" {
  description = "S3 bucket name — enter this in the plugin settings"
  value       = aws_s3_bucket.backup.id
}

output "bucket_region" {
  description = "S3 bucket region — enter this in the plugin settings"
  value       = var.aws_region
}

output "iam_access_key_id" {
  description = "IAM Access Key ID — enter this in the plugin settings"
  value       = aws_iam_access_key.backup.id
}

output "iam_secret_access_key" {
  description = "IAM Secret Access Key — enter this in the plugin settings"
  value       = aws_iam_access_key.backup.secret
  sensitive   = true
}
```

---

## Usage

### First Time Setup

```bash
cd terraform/

# Initialize
terraform init

# Create infrastructure
terraform apply -var="bucket_suffix=ekewaka-backups"

# View the access key ID
terraform output iam_access_key_id

# View the secret key (sensitive)
terraform output -raw iam_secret_access_key
```

### Enter Credentials in Plugin

1. Go to **All-in-One WP Migration → S3 Backup** (or wherever the settings page is)
2. Enter the values from Terraform output:
   - **Access Key ID:** from `iam_access_key_id`
   - **Secret Access Key:** from `iam_secret_access_key`
   - **Bucket Name:** from `bucket_name`
   - **Region:** from `bucket_region`
3. Click **Test Connection**
4. Click **Save Settings**

### Customizing Lifecycle Rules

To keep backups longer:

```bash
terraform apply \
  -var="bucket_suffix=ekewaka-backups" \
  -var="glacier_transition_days=60" \
  -var="expiration_days=180"
```

### Multiple Sites

Run Terraform once per site with a different suffix:

```bash
# Site 1
terraform apply -var="bucket_suffix=ekewaka-backups"

# Site 2 (use a separate state file or workspace)
terraform workspace new altoa
terraform apply -var="bucket_suffix=altoa-backups"
```

Or use a single bucket with different path prefixes per site (the plugin handles this via the "S3 Path Prefix" setting).

### Destroying Infrastructure

```bash
# WARNING: This deletes the bucket and ALL backups in it
terraform destroy -var="bucket_suffix=ekewaka-backups"
```

---

## State Management

For production use, store Terraform state remotely:

```hcl
terraform {
  backend "s3" {
    bucket = "my-terraform-state"
    key    = "wp-s3-backup/terraform.tfstate"
    region = "us-east-1"
  }
}
```

For local/personal use, the default local state file (`terraform.tfstate`) is fine — just don't commit it to git.

---

## Security Notes

- The IAM user can ONLY access the specific backup bucket — nothing else in your AWS account
- The bucket blocks all public access — backups are only accessible with valid credentials
- Server-side encryption ensures data is encrypted at rest
- Versioning protects against accidental deletion or overwrite
- The Terraform state file contains the IAM secret key — treat it as sensitive
