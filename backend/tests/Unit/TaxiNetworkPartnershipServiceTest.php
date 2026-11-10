<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\NexaSuiteMarketplaceSetting;
use App\Models\TaxiNetworkPartnership;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiNetworkPartnershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiNetworkPartnershipServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function invite_code_can_be_redeemed_and_accepted(): void
    {
        $owner = Company::query()->create(['name' => 'Owner A', 'is_active' => true]);
        $partner = Company::query()->create(['name' => 'Partner B', 'is_active' => true]);

        $service = app(TaxiNetworkPartnershipService::class);
        $invite = $service->ensureActiveInviteCode((int) $partner->id);

        $result = $service->redeemInviteCode((int) $owner->id, $invite->code);
        $this->assertFalse($result['auto_accepted']);
        $this->assertTrue($result['partnership']->isPending());

        $accepted = $service->acceptPartnership($result['partnership'], (int) $partner->id);
        $this->assertTrue($accepted->isAccepted());

        $settings = app(TaxiDispatchSettingsService::class);
        $this->assertSame([(int) $partner->id], $settings->networkPartnerCompanyIds((int) $owner->id));
    }

    #[Test]
    public function auto_accept_links_immediately(): void
    {
        $owner = Company::query()->create(['name' => 'Owner A', 'is_active' => true]);
        $partner = Company::query()->create(['name' => 'Partner B', 'is_active' => true]);

        $service = app(TaxiNetworkPartnershipService::class);
        $service->setAutoAccept((int) $partner->id, true);
        $invite = $service->ensureActiveInviteCode((int) $partner->id);

        $result = $service->redeemInviteCode((int) $owner->id, $invite->code);
        $this->assertTrue($result['auto_accepted']);
        $this->assertTrue($result['partnership']->isAccepted());

        $settings = app(TaxiDispatchSettingsService::class);
        $this->assertSame([(int) $partner->id], $settings->networkPartnerCompanyIds((int) $owner->id));
    }

    #[Test]
    public function cannot_redeem_own_code(): void
    {
        $company = Company::query()->create(['name' => 'Solo', 'is_active' => true]);
        $service = app(TaxiNetworkPartnershipService::class);
        $invite = $service->ensureActiveInviteCode((int) $company->id);

        $this->expectException(InvalidArgumentException::class);
        $service->redeemInviteCode((int) $company->id, $invite->code);
    }

    #[Test]
    public function revoke_removes_partner_from_dispatch_list(): void
    {
        $owner = Company::query()->create(['name' => 'Owner A', 'is_active' => true]);
        $partner = Company::query()->create(['name' => 'Partner B', 'is_active' => true]);

        $service = app(TaxiNetworkPartnershipService::class);
        $service->setAutoAccept((int) $partner->id, true);
        $invite = $service->ensureActiveInviteCode((int) $partner->id);
        $result = $service->redeemInviteCode((int) $owner->id, $invite->code);

        $service->revokePartnership($result['partnership'], (int) $owner->id);

        $settings = app(TaxiDispatchSettingsService::class);
        $this->assertSame([], $settings->networkPartnerCompanyIds((int) $owner->id));
        $this->assertSame(TaxiNetworkPartnership::STATUS_REVOKED, $result['partnership']->fresh()->status);
    }

    #[Test]
    public function marketplace_companies_cannot_partner_with_each_other_as_owner(): void
    {
        $owner = Company::query()->create([
            'name' => 'Marketplace A',
            'is_active' => true,
            'package_key' => 'marketplace',
        ]);
        $partner = Company::query()->create([
            'name' => 'Marketplace B',
            'is_active' => true,
            'package_key' => 'marketplace',
        ]);

        $service = app(TaxiNetworkPartnershipService::class);
        $service->setAutoAccept((int) $partner->id, true);
        $invite = $service->ensureActiveInviteCode((int) $partner->id);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('betaald maandabonnement');
        $service->redeemInviteCode((int) $owner->id, $invite->code);
    }

    #[Test]
    public function paid_owner_can_partner_with_marketplace_company_without_subscription(): void
    {
        $owner = Company::query()->create([
            'name' => 'Pro Taxi',
            'is_active' => true,
            'package_key' => 'pro',
        ]);
        $partner = Company::query()->create([
            'name' => 'Marketplace Partner',
            'is_active' => true,
            'package_key' => 'marketplace',
        ]);

        $service = app(TaxiNetworkPartnershipService::class);
        $service->setAutoAccept((int) $partner->id, true);
        $invite = $service->ensureActiveInviteCode((int) $partner->id);

        $result = $service->redeemInviteCode((int) $owner->id, $invite->code);
        $this->assertTrue($result['auto_accepted']);
        $this->assertTrue($result['partnership']->isAccepted());

        $settings = app(TaxiDispatchSettingsService::class);
        $this->assertSame([(int) $partner->id], $settings->networkPartnerCompanyIds((int) $owner->id));
    }

    #[Test]
    public function start_owner_can_initiate_network_partnership(): void
    {
        $owner = Company::query()->create([
            'name' => 'Start Taxi',
            'is_active' => true,
            'package_key' => 'start',
        ]);
        $partner = Company::query()->create([
            'name' => 'Partner',
            'is_active' => true,
            'package_key' => 'marketplace',
        ]);

        $service = app(TaxiNetworkPartnershipService::class);
        $this->assertTrue($service->companyCanInitiateNetworkPartnerships($owner));
        $this->assertFalse($service->companyCanInitiateNetworkPartnerships($partner));

        $service->setAutoAccept((int) $partner->id, true);
        $invite = $service->ensureActiveInviteCode((int) $partner->id);
        $result = $service->redeemInviteCode((int) $owner->id, $invite->code);
        $this->assertTrue($result['partnership']->isAccepted());
    }

    #[Test]
    public function marketplace_companies_can_partner_when_setting_enabled(): void
    {
        NexaSuiteMarketplaceSetting::current()->update(['allow_marketplace_network_owner' => true]);

        $owner = Company::query()->create([
            'name' => 'Marketplace A',
            'is_active' => true,
            'package_key' => 'marketplace',
        ]);
        $partner = Company::query()->create([
            'name' => 'Marketplace B',
            'is_active' => true,
            'package_key' => 'marketplace',
        ]);

        $service = app(TaxiNetworkPartnershipService::class);
        $this->assertTrue($service->companyCanInitiateNetworkPartnerships($owner));
        $service->setAutoAccept((int) $partner->id, true);
        $invite = $service->ensureActiveInviteCode((int) $partner->id);

        $result = $service->redeemInviteCode((int) $owner->id, $invite->code);
        $this->assertTrue($result['auto_accepted']);
        $this->assertTrue($result['partnership']->isAccepted());
    }
}
