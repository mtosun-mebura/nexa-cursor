<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\CustomerLoginCode;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailTemplateService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class TaxiCustomerLoginCodeService
{
    public function issueAndSend(User $user, ?int $companyId, string $loginUrl, int $expiresMinutes = 15): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        CustomerLoginCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($expiresMinutes),
        ]);

        $template = EmailTemplate::query()
            ->where('type', 'taxi_customer_login_code')
            ->where('is_active', true)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id');
                if ($companyId) {
                    $q->orWhere('company_id', $companyId);
                }
            })
            ->orderByRaw('CASE WHEN company_id IS NULL THEN 0 ELSE 1 END DESC')
            ->first();

        $companyName = $companyId ? (Company::query()->find($companyId)?->name) : null;
        $companyName = $companyName ?: 'NEXA Taxi';

        $variables = [
            'USER_NAME' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->email ?? ''),
            'USER_EMAIL' => (string) ($user->email ?? ''),
            'COMPANY_NAME' => $companyName,
            'LOGIN_CODE' => $code,
            'LOGIN_URL' => $loginUrl,
            'CODE_EXPIRES_MINUTES' => (string) $expiresMinutes,
        ];

        $subject = $template?->subject ?? 'Uw inlogcode – {{ COMPANY_NAME }}';
        $html = $template?->html_content ?? '<p>Uw inlogcode is: <strong>{{ LOGIN_CODE }}</strong></p><p>Inloggen: <a href="{{ LOGIN_URL }}">{{ LOGIN_URL }}</a></p>';
        $text = $template?->text_content ?? "Uw inlogcode is: {{ LOGIN_CODE }}\nInloggen: {{ LOGIN_URL }}";

        $parser = app(EmailTemplateService::class);
        $subject = $parser->parseTemplateVariables($subject, $variables);
        $html = $parser->parseTemplateVariables($html, $variables);
        $text = $parser->parseTemplateVariables($text, $variables);

        Mail::send([], [], function ($message) use ($user, $subject, $html, $text) {
            $message->to($user->email, trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->email)
                ->subject($subject);
            if ($html) $message->html($html);
            if ($text) $message->text($text);
        });
    }
}

