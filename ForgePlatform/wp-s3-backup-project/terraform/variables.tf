variable "bucket_suffix" {
  description = "Unique suffix for the S3 bucket name (e.g., mysite-backups)"
  type        = string

  validation {
    condition     = can(regex("^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$", var.bucket_suffix))
    error_message = "Bucket suffix must be lowercase alphanumeric with hyphens/dots, 3-63 chars."
  }
}

variable "aws_profile" {
  description = "AWS CLI profile name to use for authentication"
  type        = string
  default     = "default"
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
