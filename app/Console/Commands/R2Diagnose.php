<?php

namespace App\Console\Commands;

use App\Support\R2ProbeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * R2 Diagnosis Command
 * 
 * Runs comprehensive diagnostics for Cloudflare R2 connectivity and configuration.
 * Provides a structured report with findings and recommendations.
 */
class R2Diagnose extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'r2:diagnose {--format=markdown : Output format (markdown, json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose R2 connectivity and configuration issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Starting R2 Diagnostics...');
        $this->newLine();

        $results = $this->runDiagnostics();
        $report = $this->generateReport($results);
        
        $this->displayReport($report);
        
        // Return appropriate exit code
        return $this->hasFailures($results) ? 1 : 0;
    }

    /**
     * Run all diagnostic tests.
     */
    private function runDiagnostics(): array
    {
        $results = [
            'config_check' => $this->checkConfiguration(),
            'flysystem_test' => $this->testFlysystem(),
            'probe_tests' => $this->runProbeTests(),
            'code_audit' => $this->auditCode(),
            'env_check' => $this->checkEnvironmentVariables(),
        ];

        return $results;
    }

    /**
     * Check R2 configuration.
     */
    private function checkConfiguration(): array
    {
        $config = config('filesystems.disks.r2');
        $sanitized = collect($config)->except(['key', 'secret'])->all();

        $issues = [];
        $warnings = [];

        // Check required fields
        $required = ['driver', 'key', 'secret', 'region', 'bucket', 'endpoint'];
        foreach ($required as $field) {
            if (empty($config[$field])) {
                $issues[] = "Missing required config: {$field}";
            }
        }

        // Check driver
        if ($config['driver'] !== 's3') {
            $issues[] = "Driver should be 's3', got: {$config['driver']}";
        }

        // Check region
        if ($config['region'] !== 'auto') {
            $warnings[] = "Region should be 'auto' for R2, got: {$config['region']}";
        }

        // Check use_path_style_endpoint
        if (!$config['use_path_style_endpoint']) {
            $issues[] = "use_path_style_endpoint should be true for R2";
        }

        // Check visibility
        if ($config['visibility'] !== 'private') {
            $warnings[] = "Visibility should be 'private' for R2, got: {$config['visibility']}";
        }

        // Check throw setting
        if (!$config['throw']) {
            $warnings[] = "throw should be true for better error handling";
        }

        return [
            'success' => empty($issues),
            'config' => $sanitized,
            'issues' => $issues,
            'warnings' => $warnings,
        ];
    }

    /**
     * Test Flysystem operations.
     */
    private function testFlysystem(): array
    {
        try {
            $disk = Storage::disk('r2');
            $testKey = 'diagnostics/' . Str::uuid() . '.txt';
            $testContent = 'Flysystem test - ' . now()->toISOString();

            // Test put
            $putResult = $disk->put($testKey, $testContent);
            if (!$putResult) {
                return [
                    'success' => false,
                    'message' => 'Failed to put test file',
                    'operations' => ['put' => false],
                ];
            }

            // Test exists
            $exists = $disk->exists($testKey);

            // Test get
            $content = $disk->get($testKey);

            // Test size
            $size = $disk->size($testKey);

            // Test url
            $url = Storage::disk('r2')->url($testKey);

            // Clean up
            $disk->delete($testKey);

            return [
                'success' => true,
                'message' => 'All Flysystem operations successful',
                'operations' => [
                    'put' => true,
                    'exists' => $exists,
                    'get' => $content === $testContent,
                    'size' => $size > 0,
                    'url' => !empty($url),
                ],
                'test_key' => $testKey,
                'test_size' => $size,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Flysystem test failed: ' . $e->getMessage(),
                'exception' => get_class($e),
                'previous' => $e->getPrevious()?->getMessage(),
            ];
        }
    }

    /**
     * Run probe tests using AWS SDK.
     */
    private function runProbeTests(): array
    {
        try {
            $probe = new R2ProbeService();
            return $probe->runAllTests();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Probe tests failed: ' . $e->getMessage(),
                'exception' => get_class($e),
            ];
        }
    }

    /**
     * Audit code for ACL/visibility issues.
     */
    private function auditCode(): array
    {
        $issues = [];
        $files = [];

        // Search for problematic patterns specific to R2
        $patterns = [
            "Storage::disk\('r2'\)->put\([^,]+,\s*[^,]+,\s*['\"]public['\"]",
            "->put\([^,]+,\s*[^,]+,\s*['\"]public['\"]",
            "'ACL'\s*=>\s*['\"]public",
        ];

        foreach ($patterns as $pattern) {
            $matches = $this->grepPattern($pattern);
            if (!empty($matches)) {
                $issues[] = "Found potential ACL/visibility issue: {$pattern}";
                $files = array_merge($files, $matches);
            }
        }

        return [
            'success' => empty($issues),
            'issues' => $issues,
            'problematic_files' => array_unique($files),
        ];
    }

    /**
     * Check environment variables.
     */
    private function checkEnvironmentVariables(): array
    {
        $required = [
            'R2_ACCESS_KEY_ID',
            'R2_SECRET_ACCESS_KEY',
            'R2_BUCKET',
            'R2_ENDPOINT',
        ];

        $optional = [
            'R2_REGION',
            'R2_PUBLIC_BASE_URL',
        ];

        $missing = [];
        $present = [];

        foreach ($required as $var) {
            if (empty(env($var))) {
                $missing[] = $var;
            } else {
                $present[] = $var;
            }
        }

        foreach ($optional as $var) {
            if (!empty(env($var))) {
                $present[] = $var;
            }
        }

        return [
            'success' => empty($missing),
            'missing_required' => $missing,
            'present' => $present,
        ];
    }

    /**
     * Search for pattern in codebase.
     */
    private function grepPattern(string $pattern): array
    {
        $files = [];
        $searchPaths = [
            app_path(),
            config_path(),
        ];

        foreach ($searchPaths as $path) {
            if (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $content = file_get_contents($file->getPathname());
                        if (preg_match("/{$pattern}/", $content)) {
                            $files[] = str_replace(base_path() . '/', '', $file->getPathname());
                        }
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Generate markdown report.
     */
    private function generateReport(array $results): string
    {
        $report = "# R2 Diagnostics Report\n\n";
        $report .= "**Generated:** " . now()->toISOString() . "\n\n";

        // Summary
        $report .= "## Summary\n\n";
        $report .= "| Test | Status |\n";
        $report .= "|------|--------|\n";
        
        foreach ($results as $test => $result) {
            $status = isset($result['success']) && $result['success'] ? '✅ PASS' : '❌ FAIL';
            $report .= "| " . ucfirst(str_replace('_', ' ', $test)) . " | {$status} |\n";
        }
        $report .= "\n";

        // Configuration Check
        $report .= "## Configuration Check\n\n";
        if ($results['config_check']['success']) {
            $report .= "✅ Configuration is valid\n\n";
        } else {
            $report .= "❌ Configuration issues found:\n";
            foreach ($results['config_check']['issues'] as $issue) {
                $report .= "- {$issue}\n";
            }
            $report .= "\n";
        }

        if (!empty($results['config_check']['warnings'])) {
            $report .= "⚠️ Warnings:\n";
            foreach ($results['config_check']['warnings'] as $warning) {
                $report .= "- {$warning}\n";
            }
            $report .= "\n";
        }

        // Environment Variables
        $report .= "## Environment Variables\n\n";
        if ($results['env_check']['success']) {
            $report .= "✅ All required environment variables are set\n\n";
        } else {
            $report .= "❌ Missing required environment variables:\n";
            foreach ($results['env_check']['missing_required'] as $var) {
                $report .= "- {$var}\n";
            }
            $report .= "\n";
        }

        // Flysystem Test
        $report .= "## Flysystem Test\n\n";
        if ($results['flysystem_test']['success']) {
            $report .= "✅ All Flysystem operations successful\n\n";
        } else {
            $report .= "❌ Flysystem test failed: {$results['flysystem_test']['message']}\n";
            if (isset($results['flysystem_test']['exception'])) {
                $report .= "- Exception: {$results['flysystem_test']['exception']}\n";
            }
            if (isset($results['flysystem_test']['previous'])) {
                $report .= "- Root cause: {$results['flysystem_test']['previous']}\n";
            }
            $report .= "\n";
        }

        // Probe Tests
        $report .= "## AWS SDK Probe Tests\n\n";
        $probeResults = $results['probe_tests'];
        
        if (isset($probeResults['head_bucket'])) {
            $bucketTest = $probeResults['head_bucket'];
            $status = $bucketTest['success'] ? '✅' : '❌';
            $report .= "### Bucket Head Test\n";
            $report .= "{$status} {$bucketTest['message']}\n";
            if (!$bucketTest['success'] && isset($bucketTest['aws_code'])) {
                $report .= "- AWS Error Code: {$bucketTest['aws_code']}\n";
                $report .= "- AWS Error Type: {$bucketTest['aws_type']}\n";
                $report .= "- HTTP Status: {$bucketTest['status_code']}\n";
            }
            $report .= "\n";
        }

        if (isset($probeResults['put_object'])) {
            $putTest = $probeResults['put_object'];
            $status = $putTest['success'] ? '✅' : '❌';
            $report .= "### Object Put Test\n";
            $report .= "{$status} {$putTest['message']}\n";
            if (!$putTest['success'] && isset($putTest['aws_code'])) {
                $report .= "- AWS Error Code: {$putTest['aws_code']}\n";
                $report .= "- AWS Error Type: {$putTest['aws_type']}\n";
                $report .= "- HTTP Status: {$putTest['status_code']}\n";
            }
            $report .= "\n";
        }

        // Code Audit
        $report .= "## Code Audit\n\n";
        if ($results['code_audit']['success']) {
            $report .= "✅ No ACL/visibility issues found in code\n\n";
        } else {
            $report .= "❌ Potential ACL/visibility issues found:\n";
            foreach ($results['code_audit']['issues'] as $issue) {
                $report .= "- {$issue}\n";
            }
            if (!empty($results['code_audit']['problematic_files'])) {
                $report .= "\n**Files with issues:**\n";
                foreach ($results['code_audit']['problematic_files'] as $file) {
                    $report .= "- {$file}\n";
                }
            }
            $report .= "\n";
        }

        // Recommendations
        $report .= "## Recommendations\n\n";
        $hasFailures = $this->hasFailures($results);
        
        if ($hasFailures) {
            $report .= "### Immediate Actions Required:\n";
            
            if (!$results['env_check']['success']) {
                $report .= "1. Set missing environment variables in your `.env` file\n";
            }
            
            if (!$results['config_check']['success']) {
                $report .= "2. Fix configuration issues in `config/filesystems.php`\n";
            }
            
            if (!$results['flysystem_test']['success']) {
                $report .= "3. Check R2 credentials and bucket permissions\n";
            }
            
            if (!$results['code_audit']['success']) {
                $report .= "4. Remove ACL/visibility parameters from R2 storage calls\n";
            }
            
            $report .= "\n### Next Steps:\n";
            $report .= "1. Run `php artisan config:clear && php artisan cache:clear`\n";
            $report .= "2. Test upload with debug endpoint: `POST /api/v1/uploads/debug?debug=1`\n";
            $report .= "3. Check Railway logs for additional error details\n";
        } else {
            $report .= "✅ All tests passed! Your R2 configuration appears to be working correctly.\n\n";
            $report .= "### Optional Optimizations:\n";
            $report .= "1. Consider setting up monitoring for R2 operations\n";
            $report .= "2. Review file cleanup policies for uploaded files\n";
        }

        return $report;
    }

    /**
     * Display the report.
     */
    private function displayReport(string $report): void
    {
        $format = $this->option('format');
        
        if ($format === 'json') {
            $this->line(json_encode(['report' => $report], JSON_PRETTY_PRINT));
        } else {
            $this->line($report);
        }
    }

    /**
     * Check if any tests failed.
     */
    private function hasFailures(array $results): bool
    {
        foreach ($results as $result) {
            if (isset($result['success']) && !$result['success']) {
                return true;
            }
        }
        return false;
    }
}
