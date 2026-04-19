<?php
/**
 * S3 REST API client with AWS Signature V4 authentication.
 *
 * Implements direct S3 API calls without the AWS SDK.
 * Uses WordPress wp_remote_request() for all HTTP communication.
 * Supports single-part and multipart uploads.
 *
 * @package WP_S3_Backup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_S3_Client {

	/** @var string AWS Access Key ID */
	private $access_key;

	/** @var string AWS Secret Access Key */
	private $secret_key;

	/** @var string AWS region (e.g., us-east-1) */
	private $region;

	/** @var string S3 bucket name */
	private $bucket;

	/** @var int Maximum retries for failed requests */
	const MAX_RETRIES = 3;

	/** @var int Multipart upload threshold in bytes (100MB) */
	const MULTIPART_THRESHOLD = 104857600;

	/** @var int Multipart chunk size in bytes (25MB) */
	const MULTIPART_CHUNK_SIZE = 26214400;

	/**
	 * @param string $access_key AWS Access Key ID.
	 * @param string $secret_key AWS Secret Access Key.
	 * @param string $region     AWS region.
	 * @param string $bucket     S3 bucket name.
	 */
	public function __construct( $access_key, $secret_key, $region, $bucket ) {
		$this->access_key = $access_key;
		$this->secret_key = $secret_key;
		$this->region     = $region;
		$this->bucket     = $bucket;
	}

	/**
	 * Get the S3 endpoint URL for this bucket.
	 *
	 * @return string Endpoint URL.
	 */
	private function get_endpoint() {
		return sprintf( 'https://%s.s3.%s.amazonaws.com', $this->bucket, $this->region );
	}

	/**
	 * Get the host header value.
	 *
	 * @return string Host value.
	 */
	private function get_host() {
		return sprintf( '%s.s3.%s.amazonaws.com', $this->bucket, $this->region );
	}

	// ─── AWS Signature V4 ────────────────────────────────

	/**
	 * Generate the signing key for AWS Signature V4.
	 *
	 * The signing key is derived through a chain of HMAC-SHA256 operations:
	 * DateKey    = HMAC("AWS4" + secret, date)
	 * RegionKey  = HMAC(DateKey, region)
	 * ServiceKey = HMAC(RegionKey, "s3")
	 * SigningKey = HMAC(ServiceKey, "aws4_request")
	 *
	 * @param string $date_stamp Date in Ymd format.
	 * @return string Raw signing key.
	 */
	private function get_signing_key( $date_stamp ) {
		$k_date    = hash_hmac( 'sha256', $date_stamp, 'AWS4' . $this->secret_key, true );
		$k_region  = hash_hmac( 'sha256', $this->region, $k_date, true );
		$k_service = hash_hmac( 'sha256', 's3', $k_region, true );
		return hash_hmac( 'sha256', 'aws4_request', $k_service, true );
	}

	/**
	 * Sign an HTTP request with AWS Signature V4.
	 *
	 * @param string $method  HTTP method (GET, PUT, DELETE, POST).
	 * @param string $uri     Request URI path.
	 * @param string $query   Query string (without leading ?).
	 * @param array  $headers Request headers (will be modified with auth headers).
	 * @param string $payload Request body (or 'UNSIGNED-PAYLOAD' for streaming).
	 * @return array Signed headers array.
	 */
	private function sign_request( $method, $uri, $query = '', $headers = array(), $payload = '' ) {
		$timestamp  = gmdate( 'Ymd\THis\Z' );
		$date_stamp = gmdate( 'Ymd' );

		$payload_hash = ( 'UNSIGNED-PAYLOAD' === $payload )
			? 'UNSIGNED-PAYLOAD'
			: hash( 'sha256', $payload );

		$headers['Host']                 = $this->get_host();
		$headers['x-amz-date']           = $timestamp;
		$headers['x-amz-content-sha256'] = $payload_hash;

		// Sort headers by lowercase key name
		$sorted = array();
		foreach ( $headers as $k => $v ) {
			$sorted[ strtolower( $k ) ] = trim( $v );
		}
		ksort( $sorted );

		// Build canonical headers string
		$canonical_headers = '';
		foreach ( $sorted as $k => $v ) {
			$canonical_headers .= $k . ':' . $v . "\n";
		}

		$signed_headers = implode( ';', array_keys( $sorted ) );

		// Canonical request
		$canonical_request = implode( "\n", array(
			$method,
			$uri,
			$query,
			$canonical_headers,
			$signed_headers,
			$payload_hash,
		) );

		// String to sign
		$scope          = $date_stamp . '/' . $this->region . '/s3/aws4_request';
		$string_to_sign = "AWS4-HMAC-SHA256\n{$timestamp}\n{$scope}\n" . hash( 'sha256', $canonical_request );

		// Signature
		$signing_key = $this->get_signing_key( $date_stamp );
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

	// ─── S3 Operations ───────────────────────────────────

	/**
	 * Test the connection to S3 by listing objects with max-keys=1.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function test_connection() {
		$uri     = '/';
		$query   = 'list-type=2&max-keys=1';
		$headers = $this->sign_request( 'GET', $uri, $query );

		$url      = $this->get_endpoint() . $uri . '?' . $query;
		$response = wp_remote_get( $url, array(
			'headers' => $headers,
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			$body = wp_remote_retrieve_body( $response );
			return new WP_Error(
				'wps3b_s3_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: Error body */
					__( 'S3 returned HTTP %1$d: %2$s', 'wp-s3-backup' ),
					$code,
					wp_strip_all_tags( $body )
				)
			);
		}

		return true;
	}

	/**
	 * Upload a file to S3.
	 *
	 * Automatically uses multipart upload for files larger than MULTIPART_THRESHOLD.
	 *
	 * @param string $local_path  Path to the local file.
	 * @param string $s3_key      S3 object key (path in bucket).
	 * @param string $content_type MIME type.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function upload_file( $local_path, $s3_key, $content_type = 'application/octet-stream' ) {
		$file_size = filesize( $local_path );

		if ( $file_size > self::MULTIPART_THRESHOLD ) {
			return $this->multipart_upload( $local_path, $s3_key, $content_type );
		}

		return $this->single_upload( $local_path, $s3_key, $content_type );
	}

	/**
	 * Upload a small file in a single PUT request.
	 *
	 * @param string $local_path  Local file path.
	 * @param string $s3_key      S3 object key.
	 * @param string $content_type MIME type.
	 * @return true|WP_Error
	 */
	private function single_upload( $local_path, $s3_key, $content_type ) {
		$body = file_get_contents( $local_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $body ) {
			return new WP_Error( 'wps3b_read_error', __( 'Could not read file for upload.', 'wp-s3-backup' ) );
		}

		$uri     = '/' . ltrim( $s3_key, '/' );
		$headers = $this->sign_request( 'PUT', $uri, '', array(
			'Content-Type'   => $content_type,
			'Content-Length' => strlen( $body ),
		), $body );

		$url = $this->get_endpoint() . $uri;

		return $this->request_with_retry( $url, array(
			'method'  => 'PUT',
			'headers' => $headers,
			'body'    => $body,
			'timeout' => 300,
		) );
	}

	/**
	 * Upload a large file using S3 multipart upload.
	 *
	 * 1. CreateMultipartUpload — get an UploadId
	 * 2. UploadPart — upload each chunk
	 * 3. CompleteMultipartUpload — finalize
	 *
	 * @param string $local_path  Local file path.
	 * @param string $s3_key      S3 object key.
	 * @param string $content_type MIME type.
	 * @return true|WP_Error
	 */
	private function multipart_upload( $local_path, $s3_key, $content_type ) {
		$uri = '/' . ltrim( $s3_key, '/' );

		// Step 1: Initiate multipart upload
		$upload_id = $this->create_multipart_upload( $uri, $content_type );
		if ( is_wp_error( $upload_id ) ) {
			return $upload_id;
		}

		// Step 2: Upload parts
		$file_handle = fopen( $local_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $file_handle ) {
			$this->abort_multipart_upload( $uri, $upload_id );
			return new WP_Error( 'wps3b_read_error', __( 'Could not open file for multipart upload.', 'wp-s3-backup' ) );
		}

		$parts       = array();
		$part_number = 1;

		while ( ! feof( $file_handle ) ) {
			$chunk = fread( $file_handle, self::MULTIPART_CHUNK_SIZE ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			if ( empty( $chunk ) ) {
				break;
			}

			$etag = $this->upload_part( $uri, $upload_id, $part_number, $chunk );
			if ( is_wp_error( $etag ) ) {
				fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				$this->abort_multipart_upload( $uri, $upload_id );
				return $etag;
			}

			$parts[] = array(
				'PartNumber' => $part_number,
				'ETag'       => $etag,
			);

			$part_number++;

			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		}

		fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		// Step 3: Complete multipart upload
		return $this->complete_multipart_upload( $uri, $upload_id, $parts );
	}

	/**
	 * Initiate a multipart upload.
	 *
	 * @param string $uri          S3 object URI.
	 * @param string $content_type MIME type.
	 * @return string|WP_Error Upload ID or error.
	 */
	private function create_multipart_upload( $uri, $content_type ) {
		$query   = 'uploads=';
		$headers = $this->sign_request( 'POST', $uri, $query, array(
			'Content-Type' => $content_type,
		) );

		$url      = $this->get_endpoint() . $uri . '?' . $query;
		$response = wp_remote_post( $url, array(
			'headers' => $headers,
			'timeout' => 60,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( preg_match( '/<UploadId>(.+?)<\/UploadId>/', $body, $matches ) ) {
			return $matches[1];
		}

		return new WP_Error( 'wps3b_multipart_init', __( 'Could not initiate multipart upload.', 'wp-s3-backup' ) );
	}

	/**
	 * Upload a single part of a multipart upload.
	 *
	 * @param string $uri         S3 object URI.
	 * @param string $upload_id   Multipart upload ID.
	 * @param int    $part_number Part number (1-based).
	 * @param string $body        Part data.
	 * @return string|WP_Error ETag of the uploaded part, or error.
	 */
	private function upload_part( $uri, $upload_id, $part_number, $body ) {
		$query   = sprintf( 'partNumber=%d&uploadId=%s', $part_number, rawurlencode( $upload_id ) );
		$headers = $this->sign_request( 'PUT', $uri, $query, array(
			'Content-Length' => strlen( $body ),
		), $body );

		$url      = $this->get_endpoint() . $uri . '?' . $query;
		$response = wp_remote_request( $url, array(
			'method'  => 'PUT',
			'headers' => $headers,
			'body'    => $body,
			'timeout' => 300,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error( 'wps3b_part_upload', sprintf(
				/* translators: 1: Part number, 2: HTTP status code */
				__( 'Failed to upload part %1$d (HTTP %2$d).', 'wp-s3-backup' ),
				$part_number,
				$code
			) );
		}

		$etag = wp_remote_retrieve_header( $response, 'etag' );
		return trim( $etag, '"' );
	}

	/**
	 * Complete a multipart upload.
	 *
	 * @param string $uri       S3 object URI.
	 * @param string $upload_id Multipart upload ID.
	 * @param array  $parts     Array of ['PartNumber' => int, 'ETag' => string].
	 * @return true|WP_Error
	 */
	private function complete_multipart_upload( $uri, $upload_id, $parts ) {
		$xml = '<CompleteMultipartUpload>';
		foreach ( $parts as $part ) {
			$xml .= sprintf(
				'<Part><PartNumber>%d</PartNumber><ETag>"%s"</ETag></Part>',
				$part['PartNumber'],
				$part['ETag']
			);
		}
		$xml .= '</CompleteMultipartUpload>';

		$query   = 'uploadId=' . rawurlencode( $upload_id );
		$headers = $this->sign_request( 'POST', $uri, $query, array(
			'Content-Type'   => 'application/xml',
			'Content-Length' => strlen( $xml ),
		), $xml );

		$url = $this->get_endpoint() . $uri . '?' . $query;

		return $this->request_with_retry( $url, array(
			'method'  => 'POST',
			'headers' => $headers,
			'body'    => $xml,
			'timeout' => 60,
		) );
	}

	/**
	 * Abort a multipart upload (cleanup on failure).
	 *
	 * @param string $uri       S3 object URI.
	 * @param string $upload_id Multipart upload ID.
	 */
	private function abort_multipart_upload( $uri, $upload_id ) {
		$query   = 'uploadId=' . rawurlencode( $upload_id );
		$headers = $this->sign_request( 'DELETE', $uri, $query );

		$url = $this->get_endpoint() . $uri . '?' . $query;
		wp_remote_request( $url, array(
			'method'  => 'DELETE',
			'headers' => $headers,
			'timeout' => 30,
		) );
	}

	/**
	 * List objects in the bucket with a given prefix.
	 *
	 * @param string $prefix S3 key prefix to filter by.
	 * @return array|WP_Error Array of objects or error.
	 */
	public function list_objects( $prefix = '' ) {
		$uri   = '/';
		$query = 'list-type=2';
		if ( ! empty( $prefix ) ) {
			$query .= '&prefix=' . rawurlencode( $prefix );
		}

		$headers = $this->sign_request( 'GET', $uri, $query );
		$url     = $this->get_endpoint() . $uri . '?' . $query;

		$response = wp_remote_get( $url, array(
			'headers' => $headers,
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error( 'wps3b_list_error', sprintf(
				/* translators: HTTP status code */
				__( 'Failed to list objects (HTTP %d).', 'wp-s3-backup' ),
				$code
			) );
		}

		$body    = wp_remote_retrieve_body( $response );
		$objects = array();

		if ( preg_match_all( '/<Contents>(.+?)<\/Contents>/s', $body, $matches ) ) {
			foreach ( $matches[1] as $content ) {
				$key  = '';
				$size = 0;
				$date = '';

				if ( preg_match( '/<Key>(.+?)<\/Key>/', $content, $m ) ) {
					$key = $m[1];
				}
				if ( preg_match( '/<Size>(\d+)<\/Size>/', $content, $m ) ) {
					$size = intval( $m[1] );
				}
				if ( preg_match( '/<LastModified>(.+?)<\/LastModified>/', $content, $m ) ) {
					$date = $m[1];
				}

				$objects[] = array(
					'key'           => $key,
					'size'          => $size,
					'last_modified' => $date,
				);
			}
		}

		return $objects;
	}

	/**
	 * Delete an object from S3.
	 *
	 * @param string $s3_key S3 object key.
	 * @return true|WP_Error
	 */
	public function delete_object( $s3_key ) {
		$uri     = '/' . ltrim( $s3_key, '/' );
		$headers = $this->sign_request( 'DELETE', $uri );
		$url     = $this->get_endpoint() . $uri;

		return $this->request_with_retry( $url, array(
			'method'  => 'DELETE',
			'headers' => $headers,
			'timeout' => 30,
		) );
	}

	/**
	 * Generate a pre-signed URL for downloading an object.
	 *
	 * The URL is valid for the specified number of seconds.
	 * The browser can download directly from S3 without proxying through WordPress.
	 *
	 * @param string $s3_key  S3 object key.
	 * @param int    $expires Seconds until the URL expires (default 3600 = 1 hour).
	 * @return string Pre-signed URL.
	 */
	public function get_presigned_url( $s3_key, $expires = 3600 ) {
		$timestamp  = gmdate( 'Ymd\THis\Z' );
		$date_stamp = gmdate( 'Ymd' );
		$scope      = $date_stamp . '/' . $this->region . '/s3/aws4_request';
		$credential = $this->access_key . '/' . $scope;

		$uri = '/' . ltrim( $s3_key, '/' );

		$query_params = array(
			'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
			'X-Amz-Credential'    => $credential,
			'X-Amz-Date'          => $timestamp,
			'X-Amz-Expires'       => $expires,
			'X-Amz-SignedHeaders'  => 'host',
		);
		ksort( $query_params );
		$query_string = http_build_query( $query_params, '', '&', PHP_QUERY_RFC3986 );

		// Canonical request for pre-signed URL
		$canonical_request = implode( "\n", array(
			'GET',
			$uri,
			$query_string,
			'host:' . $this->get_host() . "\n",
			'host',
			'UNSIGNED-PAYLOAD',
		) );

		$string_to_sign = "AWS4-HMAC-SHA256\n{$timestamp}\n{$scope}\n" . hash( 'sha256', $canonical_request );
		$signing_key    = $this->get_signing_key( $date_stamp );
		$signature      = hash_hmac( 'sha256', $string_to_sign, $signing_key );

		return $this->get_endpoint() . $uri . '?' . $query_string . '&X-Amz-Signature=' . $signature;
	}

	// ─── Helpers ─────────────────────────────────────────

	/**
	 * Make an HTTP request with retry logic and exponential backoff.
	 *
	 * @param string $url  Request URL.
	 * @param array  $args wp_remote_request arguments.
	 * @return true|WP_Error
	 */
	private function request_with_retry( $url, $args ) {
		$last_error = null;

		for ( $attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++ ) {
			$response = wp_remote_request( $url, $args );

			if ( is_wp_error( $response ) ) {
				$last_error = $response;
				sleep( pow( 2, $attempt ) );
				continue;
			}

			$code = wp_remote_retrieve_response_code( $response );

			if ( $code >= 200 && $code < 300 ) {
				return true;
			}

			if ( 500 === $code || 503 === $code ) {
				$last_error = new WP_Error( 'wps3b_s3_error', sprintf(
					/* translators: HTTP status code */
					__( 'S3 returned HTTP %d. Retrying...', 'wp-s3-backup' ),
					$code
				) );
				sleep( pow( 2, $attempt ) );
				continue;
			}

			// Non-retryable error
			$body = wp_remote_retrieve_body( $response );
			return new WP_Error( 'wps3b_s3_error', sprintf(
				/* translators: 1: HTTP status code, 2: Error body */
				__( 'S3 error (HTTP %1$d): %2$s', 'wp-s3-backup' ),
				$code,
				wp_strip_all_tags( $body )
			) );
		}

		return $last_error ? $last_error : new WP_Error( 'wps3b_s3_error', __( 'S3 request failed after retries.', 'wp-s3-backup' ) );
	}
}
