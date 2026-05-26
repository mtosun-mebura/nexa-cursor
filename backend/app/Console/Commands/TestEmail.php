<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email sending functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $to = $this->argument('to');
        
        $this->info('Mail Configuration:');
        $this->info('Default: ' . config('mail.default'));
        $this->info('From: ' . config('mail.from.address'));
        $this->info('Host: ' . config('mail.mailers.smtp.host'));
        $this->info('Port: ' . config('mail.mailers.smtp.port'));
        $this->info('Username: ' . (config('mail.mailers.smtp.username') ?: 'Not set'));
        $this->info('');
        
        if (config('mail.default') === 'log') {
            $this->warn('Mail is configured to log only. Emails will not be sent externally.');
            $this->info('To send real emails, configure SMTP in .env:');
            $this->info('MAIL_MAILER=smtp');
            $this->info('MAIL_HOST=your-smtp-host');
            $this->info('MAIL_PORT=587');
            $this->info('MAIL_USERNAME=your-username');
            $this->info('MAIL_PASSWORD=your-password');
            $this->info('');
        }
        
        try {
            $this->info("Sending test email to: {$to}");
            
            Mail::raw('Dit is een test email van NEXA Skillmatching. Als je dit bericht ontvangt, werkt de mailserver correct!', function ($message) use ($to) {
                $message->to($to)
                    ->subject('Test Email - NEXA Skillmatching')
                    ->from(config('mail.from.address', 'noreply@nexa-skillmatching.nl'), config('mail.from.name', 'NEXA Skillmatching'));
            });
            
            if (config('mail.default') === 'log') {
                $this->info('Email logged successfully! Check storage/logs/laravel.log for the email content.');
            } else {
                $this->info('Email sent successfully!');
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Email sending failed: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}




