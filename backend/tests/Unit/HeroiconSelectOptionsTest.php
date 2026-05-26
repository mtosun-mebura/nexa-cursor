<?php

namespace Tests\Unit;

use App\Support\HeroiconSelectOptions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HeroiconSelectOptionsTest extends TestCase
{
    #[Test]
    public function sorted_labels_by_id_are_alphabetical_by_label(): void
    {
        config()->set('heroicons.icons', [
            'zebra' => ['label' => 'Zebra', 'svg' => '<path />'],
            'apple' => ['label' => 'Appel', 'svg' => '<path />'],
            'mango' => ['label' => 'Mango', 'svg' => '<path />'],
            'invalid' => ['label' => 'Geen SVG'],
        ]);

        $options = HeroiconSelectOptions::sortedLabelsById();

        $this->assertSame(['apple', 'mango', 'zebra'], array_keys($options));
        $this->assertSame('Appel', $options['apple']);
    }

    #[Test]
    public function config_includes_vliegtuig_and_ziekenhuis_labels(): void
    {
        $options = HeroiconSelectOptions::sortedLabelsById();

        $this->assertArrayHasKey('vliegtuig', $options);
        $this->assertSame('Vliegtuig', $options['vliegtuig']);
        $this->assertArrayHasKey('ziekenhuis', $options);
        $this->assertSame('Ziekenhuis', $options['ziekenhuis']);
    }

    #[Test]
    public function sorted_labels_by_id_can_exclude_ids(): void
    {
        config()->set('heroicons.icons', [
            'bulb' => ['label' => 'Lamp alias', 'svg' => '<path />'],
            'light-bulb' => ['label' => 'Gloeilamp', 'svg' => '<path />'],
        ]);

        $options = HeroiconSelectOptions::sortedLabelsById(['bulb']);

        $this->assertArrayNotHasKey('bulb', $options);
        $this->assertArrayHasKey('light-bulb', $options);
    }
}
