# WP S3 Backup — S3 Client Technical Guide

## Why Not Use the AWS SDK?

The official AWS SDK for PHP is ~30MB with thousands of files. WordPress.org plugin guidelines discourage bundling large third-party libraries, and Composer autoloading can conflict with other plugins.

Instead, we implement direct S3 REST API calls with AWS Signature Version 4 (SigV4) authentication. This keeps the plugin under 50KB and fully self-contained.

---

## How AWS Signature V4 Works

Every request to S3 must be signed to prove you have valid credentials. Here's the signing process:

### Step 1: Create a Canonical Request

A canonical request is a standardized representation of your HTTP request:

```
HTTPMethod\n
CanonicalURI\n
CanonicalQueryString\n
CanonicalHeaders\n
SignedHeaders\n
HashedPayload
```

**Example** — uploading a file:
```
PUT
/backups/mysite/2026-06-15-db.sql.gz

host:my-bucket.s3.us-east-1.amazonaws.com
x-amz-content-sha256:e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855
x-amz-date:20260615T120000Z

host;x-amz-content-sha256;x-amz-date
e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855
```

### Step 2: Create a String to Sign

```
AWS4-HMAC-SHA256\n
20260615T120000Z\n
20260615/us-east-1/s3/aws4_request\n
hash(canonical_request)
```

### Step 3: Calculate the Signing Key

```
DateKey       = HMAC-SHA256("AWS4" + SecretKey, "20260615")
RegionKey     = HMAC-SHA256(DateKey, "us-east-1")
ServiceKey    = HMAC-SHA256(RegionKey, "s3")
SigningKey    = HMAC-SHA256(ServiceKey, "aws4_request")
```

### Step 4: Calculate the Signature

```
Signature = hex(HMAC-SHA256(SigningKey, StringToSign))
```

### Step 5: Add Authorization Header

```
Authorization: AWS4-HMAC-SHA256
  Credential=AKIAIOSFODNN7EXAMPLE/20260615/us-east-1/s3/aws4_request,
  SignedHeaders=host;x-amz-content-sha256;x-amz-date,
  Signature=calculated_signature
```

---

## PHP Implementation

Here's how each step translates to PHP code:

### Signing Key Generation

```php
private function get_signing_key( $date_stamp, $region, $service ) {
    $k_date    = hash_hmac( 'sha256', $date_stamp, 'AWS4' . $this->secret_key, true );
    $k_region  = hash_hmac( 'sha256', $region, $k_date, true );
    $k_service = hash_hmac( 'sha256', $service, $k_region, true );
    $k_signing = hash_hmac( 'sha256', 'aws4_request', $k_service, true );
    return $k_signing;
}
```

### Canonical Request

```php
private function get_canonical_request( $method, $uri, $query, $headers, $signed_headers, $payload_hash ) {
    $canonical_headers = '';
    foreach ( $headers as $key => $value ) {
        $canonical_headers .= strtolower( $key ) . ':' . trim( $value ) . "\n";
    }

    return implode( "\n", array(
        $method,
        $uri,
        $query,
        $canonical_headers,
        $signed_headers,
        $payload_hash,
    ) );
}
```

### Full Request Signing

```php
public function sign_request( $method, $url, $headers = array(), $body = '' ) {
    $parsed    = wp_parse_url( $url );
    $uri       = isset( $parsed['path'] ) ? $parsed['path'] : '/';
    $query     = isset( $parsed['query'] ) ? $parsed['query'] : '';
    $host      = $parsed['host'];

    $timestamp  = gmdate( 'Ymd\THis\Z' );
    $date_stamp = gmdate( 'Ymd' );

    $payload_hash = hash( 'sha256', $body );

    // Required headers
    $headers['Host']                 = $host;
    $headers['x-amz-date']          = $timestamp;
    $headers['x-amz-content-sha256'] = $payload_hash;

    // Sort headers
    ksort( $headers );

    $signed_headers = implode( ';', array_map( 'strtolower', array_keys( $headers ) ) );

    // Canonical request
    $canonical = $this->get_canonical_request(
        $method, $uri, $query, $headers, $signed_headers, $payload_hash
    );

    // String to sign
    $scope         = $date_stamp . '/' . $this->region . '/s3/aws4_request';
    $string_to_sign = "AWS4-HMAC-SHA256\n{$timestamp}\n{$scope}\n" . hash( 'sha256', $canonical );

    // Signing key and signature
    $signing_key = $this->get_signing_key( $date_stamp, $this->region, 's3' );
    $signature   = hash_hmac( 'sha256', $string_to_sign, $signing_key );

    // Authorization header
    $headers['Authorization'] = sprintf(
        'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
        $this->access_key,
        $scope,
        $signed_headers,
        $signature
    );

    return $headers;
}
```

---

## S3 Operations

### PutObject (Upload a file)

```
PUT /{key} HTTP/1.1
Host: {bucket}.s3.{region}.amazonaws.com
Content-Type: application/octet-stream
x-amz-content-sha256: {hash of body}
x-amz-date: {timestamp}
Authorization: {sigv4}

{file contents}
```

WordPress implementation:
```php
public function put_object( $key, $body, $content_type = 'application/octet-stream' ) {
    $url     = $this->get_endpoint() . '/' . ltrim( $key, '/' );
    $headers = $this->sign_request( 'PUT', $url, array(
        'Content-Type' => $content_type,
    ), $body );

    return wp_remote_request( $url, array(
        'method'  => 'PUT',
        'headers' => $headers,
        'body'    => $body,
        'timeout' => 300,
    ) );
}
```

### GetObject (Download a file)

```
GET /{key} HTTP/1.1
Host: {bucket}.s3.{region}.amazonaws.com
```

### ListObjectsV2 (List backups)

```
GET /?list-type=2&prefix={prefix} HTTP/1.1
Host: {bucket}.s3.{region}.amazonaws.com
```

### DeleteObject

```
DELETE /{key} HTTP/1.1
Host: {bucket}.s3.{region}.amazonaws.com
```

### Pre-signed URL (for downloads)

Instead of proxying the download through WordPress, we generate a pre-signed URL that lets the browser download directly from S3:

```
https://{bucket}.s3.{region}.amazonaws.com/{key}
  ?X-Amz-Algorithm=AWS4-HMAC-SHA256
  &X-Amz-Credential={access_key}/{date}/{region}/s3/aws4_request
  &X-Amz-Date={timestamp}
  &X-Amz-Expires=3600
  &X-Amz-SignedHeaders=host
  &X-Amz-Signature={signature}
```

---

## Multipart Upload (for files >100MB)

Large files are uploaded in parts to avoid timeouts and memory issues:

### Flow

1. **CreateMultipartUpload** — S3 returns an `UploadId`
2. **UploadPart** — Upload each chunk (minimum 5MB per part) with the `UploadId` and `PartNumber`
3. **CompleteMultipartUpload** — Send the list of part numbers and ETags to finalize

### Chunk Strategy

```
File Size        Chunk Size    Upload Method
< 25MB           Single PUT    One request
>= 25MB          10MB chunks   Multipart upload
```

The thresholds are set conservatively for shared hosting compatibility:
- `MULTIPART_THRESHOLD = 25MB` — triggers multipart for anything over 25MB
- `MULTIPART_CHUNK_SIZE = 10MB` — small chunks prevent timeout on slow connections
- Each chunk gets a 600-second timeout

### Error Handling

- If any part fails, retry up to 3 times
- If all retries fail, call `AbortMultipartUpload` to clean up
- Track uploaded parts so we can resume if the process is interrupted

---

## Error Handling

### HTTP Status Codes from S3

| Code | Meaning | Action |
|------|---------|--------|
| 200 | Success | Continue |
| 301 | Wrong region | Auto-detect correct region and retry |
| 403 | Access denied | Check credentials, check bucket policy |
| 404 | Bucket/key not found | Verify bucket name and key |
| 409 | Conflict | Retry after delay |
| 500 | S3 internal error | Retry with exponential backoff |
| 503 | Slow down | Retry with exponential backoff |

### Retry Strategy

```php
$max_retries = 3;
$base_delay  = 1; // seconds

for ( $attempt = 0; $attempt <= $max_retries; $attempt++ ) {
    $response = wp_remote_request( ... );

    if ( ! is_wp_error( $response ) ) {
        $code = wp_remote_retrieve_response_code( $response );
        if ( $code >= 200 && $code < 300 ) {
            return $response; // Success
        }
        if ( $code === 500 || $code === 503 ) {
            sleep( $base_delay * pow( 2, $attempt ) ); // Exponential backoff
            continue;
        }
        // Non-retryable error
        break;
    }

    sleep( $base_delay * pow( 2, $attempt ) );
}
```
