<?php

namespace Tests\Unit;

use App\Models\NexaSuiteMarketplaceSetting;
use App\Support\NexaMarketplaceFeeCopy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NexaMarketplaceFeeCopyTest extends TestCase
{
    #[Test]
    public function faq_and_notice_use_admin_fee_percent_and_distinguish_own_site(): void
    {
        NexaSuiteMarketplaceSetting::current()->update(['fee_percent' => 12]);

        $answer = NexaMarketplaceFeeCopy::faqAnswer();
        $this->assertStringContainsString('12%', $answer);
        $this->assertStringContainsString('eigen website', $answer);
        $this->assertStringContainsString('nexasuite.nl', $answer);
        $this->assertStringContainsString('dichtstbijzijnde', $answer);
        $this->assertStringContainsString('maandabonnement', $answer);

        $this->assertStringContainsString('eigen website', NexaMarketplaceFeeCopy::pricingNoticeOwnSite());
        $this->assertStringContainsString('geen extra fee', NexaMarketplaceFeeCopy::pricingNoticeOwnSite());
        $this->assertStringContainsString('12%', NexaMarketplaceFeeCopy::pricingNoticeMarketplace());
        $this->assertStringContainsString('nexasuite.nl', NexaMarketplaceFeeCopy::pricingNoticeMarketplace());
    }

    #[Test]
    public function apply_to_home_sections_rewrites_stale_commission_copy(): void
    {
        NexaSuiteMarketplaceSetting::current()->update(['fee_percent' => 8]);

        $updated = NexaMarketplaceFeeCopy::applyToHomeSections([
            'features' => [
                'items' => [
                    [
                        'title' => 'Geen commissie per rit',
                        'description' => 'Je betaalt een vast maandbedrag.',
                    ],
                ],
            ],
            'component:landwind.faq' => [
                'subtitle' => 'Vast maandbedrag. Geen commissie per rit.',
                'items' => [
                    ['question' => 'Betaal ik commissie per rit?', 'answer' => 'Nee. Vast maandbedrag.'],
                    ['question' => 'Is NEXA white-label?', 'answer' => 'Ja.'],
                ],
            ],
            'component:stats' => [
                'subtitle' => 'Eén platform. Geen commissie per rit. Altijd boekbaar.',
                'items' => [
                    ['value' => '0', 'suffix' => '', 'label' => 'Commissie per rit'],
                ],
            ],
        ]);

        $this->assertSame(NexaMarketplaceFeeCopy::featureTitle(), $updated['features']['items'][0]['title']);
        $this->assertStringContainsString('8%', $updated['features']['items'][0]['description']);
        $this->assertStringContainsString('nexasuite.nl', $updated['component:landwind.faq']['items'][0]['answer']);
        $this->assertSame('Ja.', $updated['component:landwind.faq']['items'][1]['answer']);
        $this->assertSame(NexaMarketplaceFeeCopy::faqSubtitle(), $updated['component:landwind.faq']['subtitle']);
        $this->assertSame(NexaMarketplaceFeeCopy::ownSiteStatsLabel(), $updated['component:stats']['items'][0]['label']);
        $this->assertSame(NexaMarketplaceFeeCopy::statsSubtitle(), $updated['component:stats']['subtitle']);
    }
}
