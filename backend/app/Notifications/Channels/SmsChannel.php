<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Vonage\Client;
use Vonage\SMS\Message\SMS;

class SmsChannel
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(
            new \Vonage\Client\Credentials\Basic(
                config('services.vonage.api_key'),
                config('services.vonage.api_secret')
            )
        );
    }

    public function send($notifiable, Notification $notification)
    {
        if (!$notifiable->phone || !$notifiable->sms_notifications) {
            return;
        }

        $message = $notification->toSms($notifiable);

        try {
            $response = $this->client->sms()->send(
                new SMS(
                    $notifiable->phone,
                    config('services.vonage.from'),
                    $message
                )
            );

            \Log::info('SMS sent successfully', [
                'to' => $notifiable->phone,
                'message' => $message,
                'response' => $response
            ]);

        } catch (\Exception $e) {
            \Log::error('SMS sending failed', [
                'to' => $notifiable->phone,
                'message' => $message,
                'error' => $e->getMessage()
            ]);
        }
    }
}

