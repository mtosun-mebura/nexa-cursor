<?php

namespace App\Services;

use App\Models\User;
use App\Support\NexaBranding;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Signed e-mail verification links for public registration (reuses /verify-email).
 */
class EmailVerificationLinkService
{
    public function send(User $user): void
    {
        $verificationUrl = $this->signedUrl($user);

        try {
            $html = view('emails.verification', [
                'user' => $user,
                'verificationUrl' => $verificationUrl,
                'suiteBrand' => 'Nexa Suite',
                'nexaLogoHtml' => NexaBranding::EMAIL_LOGO_PLACEHOLDER,
            ])->render();

            $fromAddress = config('mail.from.address', 'noreply@nexasuite.nl');
            $fromName = config('mail.from.name', 'Nexa Suite');

            Mail::html($html, function ($message) use ($user, $fromAddress, $fromName) {
                $message->to($user->email, trim(($user->first_name ?? '').' '.($user->last_name ?? '')))
                    ->subject('Verifieer je e-mailadres')
                    ->from($fromAddress, $fromName);
            });
        } catch (\Throwable $e) {
            Log::warning('Public registration verification mail failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function signedUrl(User $user): string
    {
        URL::forceRootUrl(rtrim((string) config('app.url'), '/'));

        return URL::temporarySignedRoute(
            'verify-email',
            now()->addDays(7),
            ['user' => $user->id, 'hash' => sha1((string) $user->email)]
        );
    }
}
