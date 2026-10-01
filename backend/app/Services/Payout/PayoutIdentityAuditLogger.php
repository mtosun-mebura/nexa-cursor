<?php

namespace App\Services\Payout;

use App\Models\PayoutIdentity;
use App\Models\PayoutIdentityAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class PayoutIdentityAuditLogger
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function log(
        ?PayoutIdentity $identity,
        string $action,
        array $payload = [],
        ?User $actor = null,
        ?Request $request = null
    ): void {
        $safe = $this->stripSecrets($payload);

        PayoutIdentityAuditLog::query()->create([
            'payout_identity_id' => $identity?->id,
            'company_id' => $identity?->company_id ?? ($safe['company_id'] ?? null),
            'actor_user_id' => $actor?->id ?? auth()->id(),
            'action' => $action,
            'payload' => $safe,
            'ip' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function stripSecrets(array $payload): array
    {
        $banned = ['password', 'api_key', 'card_number', 'cvc', 'cvv', 'iban', 'account_number', 'secret', 'access_token', 'refresh_token', 'pan'];
        foreach ($banned as $key) {
            unset($payload[$key]);
        }

        return $payload;
    }
}
