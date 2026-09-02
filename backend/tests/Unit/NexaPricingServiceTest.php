<?php

namespace Tests\Unit;

use App\Services\NexaPricingService;
use Tests\TestCase;

class NexaPricingServiceTest extends TestCase
{
    public function test_feature_comparison_inherits_alles_van_previous_package(): void
    {
        $service = app(NexaPricingService::class);
        $rows = $service->featureComparison([
            'packages' => [
                [
                    'name' => 'Start',
                    'features' => ['Website met boekingsmodule', 'Tot 3 chauffeur-accounts'],
                ],
                [
                    'name' => 'Pro',
                    'features' => ['Alles van Start', 'Onbeperkt chauffeurs'],
                ],
            ],
        ]);

        $byLabel = [];
        foreach ($rows as $row) {
            $byLabel[$row['label']] = $row['included'];
        }

        $this->assertSame([true, true], $byLabel['Website met boekingsmodule']);
        $this->assertSame([true, true], $byLabel['Tot 3 chauffeur-accounts']);
        $this->assertSame([false, true], $byLabel['Onbeperkt chauffeurs']);
    }

    public function test_signup_url_adds_pakket_query_on_contact(): void
    {
        $service = app(NexaPricingService::class);

        $this->assertSame(
            '/contact?pakket=Pro',
            $service->signupUrl(['name' => 'Pro', 'cta_url' => '/contact'])
        );
    }

    public function test_feature_catalog_lists_unique_labels_across_packages(): void
    {
        $service = app(NexaPricingService::class);
        $labels = $service->featureCatalog([
            'packages' => [
                ['features' => ['Website met boekingsmodule', 'Tot 3 chauffeur-accounts']],
                ['features' => ['Tot 3 chauffeur-accounts', 'Onbeperkt chauffeurs']],
            ],
        ]);

        $this->assertSame(
            ['Website met boekingsmodule', 'Tot 3 chauffeur-accounts', 'Onbeperkt chauffeurs'],
            $labels
        );
    }

    public function test_display_amount_prefixes_numbers_and_keeps_free_text(): void
    {
        $service = app(NexaPricingService::class);

        $this->assertSame('€ 39,-', $service->displayAmount('39'));
        $this->assertSame('€ 39,50', $service->displayAmount('€ 39,50'));
        $this->assertSame('€ 39,-', $service->displayAmount('39,-'));
        $this->assertSame('eerste maand gratis', $service->displayAmount('eerste maand gratis'));
        $this->assertSame('', $service->packageOffer(['price' => '99']));
        $this->assertSame('39', $service->packageOffer(['offer' => ' 39 ']));
        $this->assertSame(0, $service->packageFreeMonths(['price' => '99']));
        $this->assertSame('1 maand gratis', $service->freeMonthsLabel(1));
        $this->assertSame('3 maanden gratis', $service->freeMonthsLabel(3));
        $this->assertSame('1 maand gratis', $service->packagePricePresentation([
            'price' => '49',
            'free_months' => 1,
            'period' => 'per maand',
        ])['hero']);
        $this->assertSame('daarna € 49,-', $service->packagePricePresentation([
            'price' => '49',
            'free_months' => 1,
            'period' => 'per maand',
        ])['after_label']);
        $this->assertSame(['Start', 'Pro', 'Business'], $service->packageNames());
        $this->assertSame(5, $service->trialNoticeDays());
        $this->assertSame('Start', $service->matchPackageName('start'));
        $this->assertSame(['start' => 'Start', 'pro' => 'Pro', 'business' => 'Business'], $service->packagesForSelect());
        $this->assertSame('start', $service->packageByKey('start')['key'] ?? null);
        $this->assertSame(3, $service->defaults()['packages'][0]['entitlements']['max_drivers'] ?? null);
        $this->assertSame(10, $service->defaults()['packages'][2]['entitlements']['max_contract_clients'] ?? null);
        $this->assertSame(0, $service->defaults()['packages'][1]['entitlements']['max_contract_clients'] ?? null);
        $this->assertFalse($service->packageByKey('start')['entitlements']['mollie_payments'] ?? true);
        $this->assertTrue($service->packageByKey('pro')['entitlements']['mollie_payments'] ?? false);
        $this->assertTrue($service->packageByKey('business')['entitlements']['contract_transport'] ?? false);
        $this->assertNotEmpty($service->modulesCatalog());
        $this->assertArrayNotHasKey('entitlements', $service->sectionPayload()['packages'][0]);
        $this->assertNull($service->matchPackageName('Onbekend'));
        $this->assertStringContainsString('pakket Start', $service->interestMessage('Start'));
        $overview = $service->faqPackagesOverview('info@nexasuite.nl');
        $this->assertStringContainsString('**Start**', $overview);
        $this->assertStringContainsString('**Pro**', $overview);
        $this->assertStringContainsString('Website live zetten', $overview);
        $this->assertStringContainsString('info@nexasuite.nl', $overview);
        $pro = $service->faqPackageAnswer('Pro', 'info@nexasuite.nl');
        $this->assertStringContainsString('**Pro**', $pro);
        $this->assertStringContainsString('pakket=Pro', $pro);
        $this->assertStringNotContainsString('**Start**', $pro);
        $websiteDeal = $service->websitePricePresentation([
            'price_label' => '750',
            'offer' => '499',
            'period' => 'eenmalig',
        ]);
        $this->assertTrue($websiteDeal['has_deal']);
        $this->assertSame('€ 499,-', $websiteDeal['hero']);
        $this->assertSame('€ 750,-', $websiteDeal['was_label']);
    }
}
