# Shared AWS Infrastructure Setup

## Prerequisites

- AWS Account with billing enabled
- AWS CLI v2 installed and configured
- **Terraform >= 1.5** installed
- Node.js 20+ installed
- Git installed

## Infrastructure as Code: Terraform

All site infrastructure is managed with Terraform. Each site has its own `.tf` files under `<site>-serverless/infrastructure/`.

CloudFormation templates (`template.yaml`) are kept alongside as an alternative reference but are **not used for deployments**.

### Terraform State Backend

Each site stores its state in S3. Using the shared state bucket `techcross-terraform-state`:

### Per-Site Backend Config (in main.tf)

```hcl
backend "s3" {
  bucket       = "techcross-terraform-state"
  key          = "<site-name>/terraform.tfstate"   # e.g. "bitbybit/terraform.tfstate"
  region       = "us-east-1"
  encrypt      = true
  use_lockfile = true
  profile      = "ekewaka"
}
```

### Common Terraform Workflow

```bash
cd <site>-serverless/infrastructure

# First time setup
cp terraform.tfvars.example terraform.tfvars
# Edit terraform.tfvars with your values

terraform init          # Download providers, configure backend
terraform plan          # Preview changes
terraform apply         # Create/update resources
terraform output        # View outputs (bucket name, CF domain, etc.)

# To tear down
terraform destroy       # Remove all resources
```

## IAM Setup

### Create a Deployment User

```bash
aws iam create-user --user-name serverless-sites-deployer
aws iam create-access-key --user-name serverless-sites-deployer
# Store credentials securely — use as GitHub Actions secrets
```

### Deployment Policy (CI/CD — Site Content Only)

This policy is for the CI/CD pipeline that deploys static site content. Terraform itself needs broader permissions (run locally or from a separate admin role).

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "S3Deploy",
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:DeleteObject",
        "s3:ListBucket",
        "s3:GetObject"
      ],
      "Resource": [
        "arn:aws:s3:::bitbybit-site",
        "arn:aws:s3:::bitbybit-site/*",
        "arn:aws:s3:::nextlevel-site",
        "arn:aws:s3:::nextlevel-site/*",
        "arn:aws:s3:::altoa-site",
        "arn:aws:s3:::altoa-site/*"
      ]
    },
    {
      "Sid": "CloudFrontInvalidate",
      "Effect": "Allow",
      "Action": "cloudfront:CreateInvalidation",
      "Resource": "*"
    }
  ]
}
```

### Terraform Execution Policy

For running `terraform apply` (use locally or from a dedicated admin role):

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:*",
        "cloudfront:*",
        "route53:*",
        "acm:*",
        "lambda:*",
        "apigateway:*",
        "execute-api:*",
        "iam:*",
        "logs:*",
        "ses:*",
        "dynamodb:*"
      ],
      "Resource": "*"
    }
  ]
}
```

> **Note:** In production, scope this down to specific resource ARNs. The broad policy above is for initial setup convenience.

## SES Setup

```bash
# Verify your sending domain
aws ses verify-domain-identity --domain <your-domain> --region us-east-1

# Verify a specific email (for testing in sandbox mode)
aws ses verify-email-identity --email-address <your-email> --region us-east-1

# Request production access (to send to unverified addresses)
# Done via AWS Console: SES → Account Dashboard → Request Production Access
```

## Billing Alerts

```bash
aws cloudwatch put-metric-alarm \
  --alarm-name "MonthlyBillingAlarm" \
  --metric-name EstimatedCharges \
  --namespace AWS/Billing \
  --statistic Maximum \
  --period 21600 \
  --threshold 5.00 \
  --comparison-operator GreaterThanThreshold \
  --evaluation-periods 1 \
  --alarm-actions <your-sns-topic-arn> \
  --dimensions Name=Currency,Value=USD
```

## Naming Conventions

| Resource | Pattern | Example |
|----------|---------|---------|
| S3 Bucket | `<domain>-site` | `bitbybit-site` |
| S3 State Bucket | `wp-serverless-terraform-state` | (shared) |
| Terraform State Key | `<site>/terraform.tfstate` | `bitbybit/terraform.tfstate` |
| CloudFront | Tagged `Site=<name>` | `Site=bitbybit` |
| Lambda | `<domain>-<function>` | `bitbybit-contact-form` |
| IAM Role | `<domain>-lambda-role` | `bitbybit-lambda-role` |
| DynamoDB | `<Site>-<Entity>` | `BitByBit-Modules` |
| API Gateway | `<domain>-api` | `bitbybit-api` |

## Project Structure (Per Site)

```
<site>-serverless/
├── infrastructure/
│   ├── main.tf                    # Provider, backend
│   ├── variables.tf               # Input variables
│   ├── s3_cloudfront.tf           # S3 + CloudFront
│   ├── dns_ssl.tf                 # ACM + Route 53
│   ├── lambda_api.tf              # Lambda + API Gateway
│   ├── outputs.tf                 # Outputs
│   ├── terraform.tfvars.example   # Example variable values
│   └── template.yaml              # CloudFormation alternative (reference only)
├── lambda/
│   └── contact-form/
│       └── index.py               # Lambda handler source
├── site/                          # Astro static site
│   ├── src/
│   ├── public/
│   ├── astro.config.mjs
│   └── package.json
├── scripts/
│   ├── export-content.php         # WordPress content exporter
│   └── sync-media.bat             # Media upload to S3
└── deploy.bat                     # Quick deploy helper
```
