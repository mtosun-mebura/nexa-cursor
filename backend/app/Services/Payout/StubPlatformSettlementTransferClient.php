<?php

namespace App\Services\Payout;

use App\Models\PayoutIdentity;

/**
 * Dev/test transfer client. Succeeds when config platform_payout_auto_succeed=true
 * and the identity is enabled; otherwise returns a soft failure for manual retry.
 */
class StubPlatformSettlementTransferClient implements PlatformSettlementTransferClient
{
    public function transfer(
        PayoutIdentity $identity,
        float $amount,
        string $currency,
        string $reference,
    ): array {
        if ($amount <= 0) {
            return ['ok' => true, 'transfer_id' => 'xfer_zero', 'stub' => true];
        }

        if (! $identity->isEnabled()) {
            return [
                'ok' => false,
                'error' => 'Payout identity niet enabled (KYB/Connect).',
                'stub' => true,
            ];
        }

        if (! config('nexa_payout.platform_payout_auto_succeed', true)) {
            return [
                'ok' => false,
                'error' => 'Automatische payout uitgeschakeld (NEXA_PLATFORM_PAYOUT_AUTO_SUCCEED=false).',
                'stub' => true,
            ];
        }

        return [
            'ok' => true,
            'transfer_id' => 'stub_xfer_'.substr(sha1($reference.'|'.$identity->id.'|'.$amount), 0, 16),
            'stub' => true,
        ];
    }
}
