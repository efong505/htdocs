# Forge Platform Architecture

## Goal
Build a serverless AWS platform for the Forge ecosystem.

## Frontend
Static site hosted on S3 and served through CloudFront.

## Backend
API Gateway routes requests to Lambda functions.

## Database
DynamoDB stores products, leads, customers, license records, email events, and system data.

## Infrastructure
Terraform manages all AWS resources.

## Environments
- dev
- staging
- prod

## Deployment
GitHub Actions deploys frontend, Lambda code, and Terraform infrastructure.

## Similar Prior Project
This should be modeled after the ChristianConservativesToday.com platform build, using the same concepts:
- Terraform modules
- S3 + CloudFront
- Lambda
- DynamoDB
- API Gateway
- CI/CD
- environment separation