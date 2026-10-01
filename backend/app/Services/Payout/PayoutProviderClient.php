<?php

namespace App\Services\Payout;

use App\Models\PayoutIdentity;

/**
 * Fetches authoritative connected-account status from a PSP.
 * Implementations must never return or persist raw card/IBAN secrets.
 */
interface PayoutProviderClient
{
    public function providerKey(): string;

    /**
     * @return array{
     *     provider_account_id?: ?string,
     *     provider_organization_id?: ?string,
     *     capability_status: string,
     *     masked_destination?: ?string,
     *     meta?: array<string, mixed>
     * }
     */
    public function fetchAccountStatus(PayoutIdentity $identity): array;
}
