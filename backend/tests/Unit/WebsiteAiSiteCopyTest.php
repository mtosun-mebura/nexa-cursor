<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Services\WebsiteAiSiteCopy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsiteAiSiteCopyTest extends TestCase
{
    #[Test]
    public function pick_animated_components_varies_across_companies_and_stays_within_the_pool(): void
    {
        $copy = new WebsiteAiSiteCopy;
        $unique = [];
        for ($i = 1; $i <= 12; $i++) {
            $company = new Company(['name' => 'Taxi Variant '.$i, 'slug' => 'taxi-variant-'.$i]);
            $company->id = $i;
            $picked = $copy->pickAnimatedComponents($company, WebsiteAiSiteCopy::HOME_COMPONENT_POOL, 'home');
            $this->assertGreaterThanOrEqual(3, count($picked));
            $this->assertLessThanOrEqual(4, count($picked));
            foreach ($picked as $id) {
                $this->assertContains($id, WebsiteAiSiteCopy::HOME_COMPONENT_POOL);
            }
            $unique[json_encode($picked)] = true;
        }

        $this->assertGreaterThan(1, count($unique));
    }

    #[Test]
    public function style_and_tone_change_the_layout_seed(): void
    {
        $copy = new WebsiteAiSiteCopy;
        $company = new Company(['name' => 'Same Taxi', 'slug' => 'same-taxi']);
        $company->id = 7;

        $this->assertNotSame(
            $copy->layoutSeed($company, 'home', 'professional|zakelijk'),
            $copy->layoutSeed($company, 'home', 'speels|enthousiast')
        );
    }
}
