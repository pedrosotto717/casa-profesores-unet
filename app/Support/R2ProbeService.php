<?php

namespace App\Support;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Str;

/**
 * R2 Probe Service using native AWS S3 client for connectivity tests.
 * 
 * This service provides low-overhead probes to test R2 connectivity
 * and basic operations without using Laravel's Storage facade.
 */
final class R2ProbeService
{
    private S3Client $client;
    private array $config;

    public function __construct()
    {
        $this->config = config('filesystems.disks.r2');
        
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $this->config['region'],
            'endpoint' => $this->config['endpoint'],
            'use_path_style_endpoint' => $this->config['use_path_style_endpoint'],
            'credentials' => new \Aws\Credentials\Credentials(
                $this->config['key'],
                $this->config['secret']
            ),
        ]);
    }

    /**
     * Test bucket connectivity by performing a headBucket operation.
     * 
     * @return array Result with success status and message
     */
    public function headBucket(): array
    {
        try {
            $this->client->headBucket([
                'Bucket' => $this->config['bucket']
            ]);

            return [
                'success' => true,
                'message' => 'Bucket head operation successful',
                'bucket' => $this->config['bucket'],
                'endpoint' => $this->config['endpoint'],
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'message' => 'Bucket head operation failed: ' . $e->getMessage(),
                'aws_code' => $e->getAwsErrorCode(),
                'aws_type' => $e->getAwsErrorType(),
                'status_code' => $e->getStatusCode(),
                'bucket' => $this->config['bucket'],
                'endpoint' => $this->config['endpoint'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Unexpected error during bucket head: ' . $e->getMessage(),
                'bucket' => $this->config['bucket'],
                'endpoint' => $this->config['endpoint'],
            ];
        }
    }

    /**
     * Test object upload by putting a small test file.
     * 
     * @return array Result with success status and message
     */
    public function putObject(): array
    {
        $testKey = 'healthcheck/' . Str::uuid() . '.txt';
        $testContent = 'R2 connectivity test - ' . now()->toISOString();

        try {
            $result = $this->client->putObject([
                'Bucket' => $this->config['bucket'],
                'Key' => $testKey,
                'Body' => $testContent,
                'ContentType' => 'text/plain',
            ]);

            // Clean up the test file
            try {
                $this->client->deleteObject([
                    'Bucket' => $this->config['bucket'],
                    'Key' => $testKey,
                ]);
            } catch (\Exception $e) {
                // Log cleanup failure but don't fail the test
            }

            return [
                'success' => true,
                'message' => 'Object put operation successful',
                'key' => $testKey,
                'etag' => $result['ETag'] ?? null,
                'bucket' => $this->config['bucket'],
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'message' => 'Object put operation failed: ' . $e->getMessage(),
                'aws_code' => $e->getAwsErrorCode(),
                'aws_type' => $e->getAwsErrorType(),
                'status_code' => $e->getStatusCode(),
                'key' => $testKey,
                'bucket' => $this->config['bucket'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Unexpected error during object put: ' . $e->getMessage(),
                'key' => $testKey,
                'bucket' => $this->config['bucket'],
            ];
        }
    }

    /**
     * Get sanitized configuration (without secrets).
     * 
     * @return array Configuration without sensitive data
     */
    public function getSanitizedConfig(): array
    {
        return collect($this->config)
            ->except(['key', 'secret'])
            ->all();
    }

    /**
     * Run all probe tests and return comprehensive results.
     * 
     * @return array Complete test results
     */
    public function runAllTests(): array
    {
        return [
            'config' => $this->getSanitizedConfig(),
            'head_bucket' => $this->headBucket(),
            'put_object' => $this->putObject(),
            'timestamp' => now()->toISOString(),
        ];
    }
}
