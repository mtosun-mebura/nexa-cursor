<?php

namespace App\Services\Payout;

use App\Models\PayoutIdentity;

/**
 * Transfers platform-held ride funds to a connected payout identity.
 * Stub until Mollie Connect transfers are wired.
 */
interface PlatformSettlementTransferClient
{
    /**
     * @return array{ok: bool, transfer_id?: string, error?: string, stub?: bool}
     */
    public function transfer(
        PayoutIdentity $identity,
        float $amount,
        string $currency,
        string $reference,
    ): array;
}
