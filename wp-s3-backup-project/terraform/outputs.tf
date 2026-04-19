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
