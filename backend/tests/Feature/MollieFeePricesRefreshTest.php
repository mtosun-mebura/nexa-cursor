<?php

namespace Tests\Feature;

use App\Models\NexaSuiteMarketplaceSetting;
use App\Models\User;
use App\Services\Payout\MarketplaceFeeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MollieFeePricesRefreshTest extends TestCase
{
    use RefreshDatabase;

    private const MOLLIE_URL = 'https://api.mollie.com/v2/methods/all*';

    private function admin(): User
    {
        Role::findOrCreate('super-admin', 'web');
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function fakeMollie($stub): void
    {
        $this->app->forgetInstance(Factory::class);
        Http::clearResolvedInstance(Factory::class);
        Http::fake([self::MOLLIE_URL => $stub]);
    }

    private function withKey(): void
    {
        $s = NexaSuiteMarketplaceSetting::current();
        $s->setEncryptedMollieApiKey('test_abcdefghij1234567890');
        $s->save();
    }

    private function p(string $desc, string $fixed, string $variable, ?string $region = null): array
    {
        return ['description' => $desc, 'fixed' => ['currency' => 'EUR', 'value' => $fixed], 'variable' => $variable]
            + ($region !== null ? ['feeRegion' => $region] : []);
    }

    private function molliePricing(): array
    {
        return ['_embedded' => ['methods' => [
            ['id' => 'ideal', 'pricing' => [$this->p('The Netherlands', '0.29', '0')]],
            ['id' => 'creditcard', 'pricing' => [
                $this->p('Commercial & non-EEA cards', '0.25', '3.25', 'other'),
                $this->p('European Economic Area consumer cards', '0.25', '1.8', 'intra-eu'),
                $this->p('American Express', '0.25', '2.9', 'american-express'),
            ]],
            ['id' => 'bancontact', 'pricing' => [$this->p('Belgium', '0.25', '1.4')]],
            ['id' => 'applepay', 'pricing' => []],
        ]]];
    }

    #[Test]
    public function button_updates_prices_from_mollie_and_overview_follows(): void
    {
        $this->withKey();
        Http::fake([self::MOLLIE_URL => Http::response($this->molliePricing())]);

        $this->actingAs($this->admin())
            ->post(route('admin.nexa-suite-bookings.settings.refresh-prices'))
            ->assertRedirect(route('admin.nexa-suite-bookings.settings'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'iDEAL: €0,32 → €0,29')
                && str_contains($m, 'Uitbetalingskosten zijn niet via Mollie op te halen'));

        Http::assertSent(fn ($r) => $r->hasHeader('Authorization') && str_contains($r->url(), 'include=pricing'));

        $cfg = app(MarketplaceFeeCalculator::class)->config();
        $this->assertSame(0.29, $cfg['methods']['ideal']['fixed']);
        $this->assertSame(1.8, $cfg['methods']['creditcard']['percent']); // niet-EEA 3,25% genegeerd
        $this->assertSame(1.8, $cfg['methods']['applepay']['percent']);   // spiegelt kaart
        $this->assertSame(0.25, $cfg['payout_fixed']);                    // blijft handmatig

        // €15 iDEAL: 1,50 − (0,29 + 0,25) = 0,96 (was 0,93).
        $this->actingAs($this->admin())
            ->get(route('admin.nexa-suite-bookings.settings'))
            ->assertOk()
            ->assertSee('€0,96', false)
            ->assertSee('bij Mollie opgehaald op', false);
    }

    #[Test]
    public function second_run_with_equal_prices_reports_already_current(): void
    {
        $this->withKey();
        Http::fake([self::MOLLIE_URL => Http::response($this->molliePricing())]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.nexa-suite-bookings.settings.refresh-prices'));
        $this->actingAs($admin)
            ->post(route('admin.nexa-suite-bookings.settings.refresh-prices'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Prijzen zijn al actueel'));
    }

    #[Test]
    public function manual_edit_after_fetch_is_labelled_manual(): void
    {
        $this->withKey();
        Http::fake([self::MOLLIE_URL => Http::response($this->molliePricing())]);
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.nexa-suite-bookings.settings.refresh-prices'));

        $this->actingAs($admin)->put(route('admin.nexa-suite-bookings.settings.update'), [
            'fee_percent' => 10, 'billing_day' => 2, 'billing_time' => '07:00', 'tax_rate_percent' => 21,
            'payment_terms_days' => 14, 'dunning_first_interval_days' => 1, 'dunning_interval_days' => 14,
            'invoice_number_prefix' => 'NSB',
            'fee_methods' => ['ideal' => ['fixed' => '0.31', 'percent' => '0']],
        ]);

        $this->actingAs($admin)->get(route('admin.nexa-suite-bookings.settings'))
            ->assertSee('handmatig aangepast', false);
    }

    #[Test]
    public function errors_leave_settings_untouched(): void
    {
        $admin = $this->admin();
        $before = NexaSuiteMarketplaceSetting::current()->fee_minimum_settings;
        $routeName = 'admin.nexa-suite-bookings.settings.refresh-prices';

        $this->withKey();

        $this->fakeMollie(Http::response(['status' => 401], 401));
        $this->actingAs($admin)->post(route($routeName))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'weigert'));

        $this->fakeMollie(fn () => throw new ConnectionException('timeout'));
        $this->actingAs($admin)->post(route($routeName))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'niet bereikbaar'));

        $this->fakeMollie(Http::response(['_embedded' => ['methods' => [['id' => 'ideal']]]]));
        $this->actingAs($admin)->post(route($routeName))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'geen prijzen'));

        $this->assertSame($before, NexaSuiteMarketplaceSetting::current()->fresh()->fee_minimum_settings);
        $this->assertSame(0.32, app(MarketplaceFeeCalculator::class)->config()['methods']['ideal']['fixed']);
    }

    #[Test]
    public function missing_key_gives_dutch_error_without_calling_mollie(): void
    {
        Http::fake();

        $this->actingAs($this->admin())->post(route('admin.nexa-suite-bookings.settings.refresh-prices'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'geen Mollie API-sleutel'));
        Http::assertNothingSent();
        $this->assertNull(NexaSuiteMarketplaceSetting::current()->fresh()->fee_minimum_settings);
    }

    #[Test]
    public function only_super_admin_can_refresh_prices(): void
    {
        $user = User::factory()->create(['company_id' => null]);

        Http::fake();
        $this->actingAs($user)->post(route('admin.nexa-suite-bookings.settings.refresh-prices'))
            ->assertSessionMissing('success');
        Http::assertNothingSent();
    }
}
