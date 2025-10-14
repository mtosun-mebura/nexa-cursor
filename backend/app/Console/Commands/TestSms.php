<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Vonage\Client;
use Vonage\SMS\Message\SMS;
use App\Notifications\Channels\DemoSmsChannel;

class TestSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sms {phone} {message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SMS sending functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $message = $this->argument('message');

        // Check if Vonage is configured
        if (!config('services.vonage.api_key') || !config('services.vonage.api_secret')) {
            $this->warn('Vonage SMS service is not configured. Using demo SMS service instead.');
            $this->info('Sending demo SMS to: ' . $phone);
            $this->info('Message: ' . $message);
            
            // Use demo SMS service
            $demoChannel = new DemoSmsChannel();
            $demoChannel->send((object)['phone' => $phone, 'sms_notifications' => true], new class($message) {
                private $message;
                public function __construct($message) { $this->message = $message; }
                public function toSms($notifiable) { return $this->message; }
            });
            
            $this->info('Demo SMS sent successfully! (Check logs for details)');
            $this->info('');
            $this->info('To use real SMS, configure Vonage:');
            $this->info('VONAGE_API_KEY=your_api_key_here');
            $this->info('VONAGE_API_SECRET=your_api_secret_here');
            $this->info('VONAGE_FROM_NUMBER=your_phone_number_here');
            $this->info('');
            $this->info('Get free credits at: https://developer.vonage.com/');
            return 0;
        }

        try {
            $client = new Client(
                new \Vonage\Client\Credentials\Basic(
                    config('services.vonage.api_key'),
                    config('services.vonage.api_secret')
                )
            );

            $this->info("Sending SMS to: {$phone}");
            $this->info("Message: {$message}");

            $response = $client->sms()->send(
                new SMS(
                    $phone,
                    config('services.vonage.from'),
                    $message
                )
            );

            $this->info('SMS sent successfully!');
            $this->info('Response: ' . json_encode($response));

        } catch (\Exception $e) {
            $this->error('SMS sending failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
