<?php declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SendPulseService;
use Illuminate\Console\Command;

final class TestSendPulseCommand extends Command
{
    protected $signature = 'test:sendpulse';
    protected $description = 'Test SendPulse configuration and email sending';

    public function handle(SendPulseService $sendPulseService): int
    {
        $this->info('Testing SendPulse configuration...');
        
        // Test configuration
        $config = $sendPulseService->testConfiguration();
        
        $this->table(
            ['Setting', 'Status'],
            [
                ['API User ID', $config['api_user_id_set'] ? '✅ Set' : '❌ Not Set'],
                ['API Secret', $config['api_secret_set'] ? '✅ Set' : '❌ Not Set'],
                ['From Email', $config['from_email_set'] ? '✅ Set' : '❌ Not Set'],
                ['From Name', $config['from_name_set'] ? '✅ Set' : '❌ Not Set'],
                ['From Email Valid', $config['from_email_valid'] ? '✅ Valid' : '❌ Invalid'],
            ]
        );
        
        $this->info('Configuration details:');
        $this->line('From Email: ' . $config['config']['from_email']);
        $this->line('From Name: ' . $config['config']['from_name']);
        
        if (!$config['api_user_id_set'] || !$config['api_secret_set'] || !$config['from_email_set'] || !$config['from_name_set'] || !$config['from_email_valid']) {
            $this->error('Configuration is incomplete. Please check your .env file.');
            return 1;
        }
        
        $this->info('✅ Configuration looks good!');
        
        // Test authentication
        $this->info('Testing authentication...');
        $authResult = $sendPulseService->testAuthentication();
        
        if ($authResult['ok']) {
            $this->info('✅ Authentication successful!');
            $this->line('Account info: ' . json_encode($authResult['data']));
            
            // Check account limits and balance
            $this->info('Checking account limits and balance...');
            $limitsResult = $sendPulseService->checkAccountLimits();
            
            if ($limitsResult['ok']) {
                $this->info('✅ Account limits checked!');
                $this->line('Balance: ' . json_encode($limitsResult['data']['balance']));
                $this->line('Senders: ' . json_encode($limitsResult['data']['senders']));
            } else {
                $this->warn('⚠️ Could not check account limits: ' . $limitsResult['error']);
            }
        } else {
            $this->error('❌ Authentication failed: ' . $authResult['error']);
            $this->line('This might be the cause of the 422 error.');
        }
        
        // Test email sending
        if ($this->confirm('Do you want to test email sending?', false)) {
            $testRecipient = [['email' => 'test@example.com', 'name' => 'Test User']];
            $testSubject = 'Test Email - CPU UNET';
            $testHtml = '<h1>Test Email</h1><p>This is a test email from CPU UNET system.</p>';
            $testText = 'Test Email - This is a test email from CPU UNET system.';
            $testOpts = ['from_email' => 'pedro.soto@unet.edu.ve', 'from_name' => 'CPU UNET'];
            
            $this->info('Testing corrected SMTP method...');
            $result1 = $sendPulseService->sendBasic($testRecipient, $testSubject, $testHtml, $testText, $testOpts);
            
            if ($result1['ok']) {
                $this->info('✅ SMTP method successful!');
                $this->line('Response: ' . json_encode($result1['data']));
                return 0; // Success, no need to test other methods
            } else {
                $this->error('❌ SMTP method failed: ' . $result1['error']);
            }
            
            $this->info('Testing transactional emails method...');
            $result2 = $sendPulseService->sendTransactional($testRecipient, $testSubject, $testHtml, $testText, $testOpts);
            
            if ($result2['ok']) {
                $this->info('✅ Transactional method successful!');
                $this->line('Response: ' . json_encode($result2['data']));
                return 0; // Success
            } else {
                $this->error('❌ Transactional method failed: ' . $result2['error']);
            }
            
            $this->info('Testing simple method...');
            $result3 = $sendPulseService->sendSimple($testRecipient, $testSubject, $testHtml, $testText, $testOpts);
            
            if ($result3['ok']) {
                $this->info('✅ Simple method successful!');
                $this->line('Response: ' . json_encode($result3['data']));
                return 0; // Success
            } else {
                $this->error('❌ Simple method failed: ' . $result3['error']);
            }
            
            $this->error('❌ All email sending methods failed.');
            $this->line('This suggests an account limitation or API access issue.');
            $this->line('Please check your SendPulse account status and permissions.');
        }
        
        return 0;
    }
}
