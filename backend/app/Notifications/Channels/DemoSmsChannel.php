<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class DemoSmsChannel
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function send($notifiable, $notification)
    {
        if (!$notifiable->phone || !$notifiable->sms_notifications) {
            return;
        }

        $message = $notification->toSms($notifiable);

        try {
            // Demo SMS service - in production use a real SMS provider
            $this->sendDemoSms($notifiable->phone, $message);

            Log::info('Demo SMS sent successfully', [
                'to' => $notifiable->phone,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Demo SMS sending failed', [
                'to' => $notifiable->phone,
                'message' => $message,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendDemoSms($phone, $message)
    {
        // This is a demo implementation
        // In production, replace with actual SMS service
        
        // For demo purposes, we'll just log the SMS
        Log::info('DEMO SMS SENT', [
            'to' => $phone,
            'message' => $message,
            'timestamp' => now(),
            'note' => 'This is a demo SMS. In production, this would be sent via Vonage, Twilio, or another SMS provider.'
        ]);

        // Simulate API call delay
        usleep(500000); // 0.5 seconds

        return true;
    }
}
