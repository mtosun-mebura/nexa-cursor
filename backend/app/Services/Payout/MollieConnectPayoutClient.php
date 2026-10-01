<?php

namespace App\Services\Payout;

use App\Models\PayoutIdentity;

/**
 * Mollie Connect placeholder client.
 *
 * Real Connect OAuth/API wiring comes after platform Mollie partner credentials exist.
 * Until then we only accept server-supplied organization/account IDs and status syncs
 * that carry no banking secrets.
 */
class MollieConnectPayoutClient implements PayoutProviderClient
{
    public function providerKey(): string
    {
        return PayoutIdentity::PROVIDER_MOLLIE;
    }

    public function fetchAccountStatus(PayoutIdentity $identity): array
    {
        $meta = $identity->safeMeta();
        $status = (string) ($meta['remote_capability_status'] ?? $identity->capability_status);

        if (! in_array($status, [
            PayoutIdentity::STATUS_NOT_STARTED,
            PayoutIdentity::STATUS_PENDING,
            PayoutIdentity::STATUS_RESTRICTED,
            PayoutIdentity::STATUS_ENABLED,
            PayoutIdentity::STATUS_DISABLED,
            PayoutIdentity::STATUS_REJECTED,
        ], true)) {
            $status = PayoutIdentity::STATUS_PENDING;
        }

        return [
            'provider_account_id' => $identity->provider_account_id,
            'provider_organization_id' => $identity->provider_organization_id,
            'capability_status' => $status,
            'masked_destination' => $identity->masked_destination,
            'meta' => array_merge($meta, [
                'synced_via' => 'mollie_connect_stub',
                'note' => 'Authoritative Mollie Connect API sync not yet wired; status is server-controlled metadata only.',
            ]),
        ];
    }
}
