<?php

namespace Tests\Feature;

use App\Models\FrontendTheme;
use Database\Seeders\FrontendThemeSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FrontendThemeSeederPreservesPublishStateTest extends TestCase
{
    #[Test]
    public function seeder_does_not_unpublish_existing_themes(): void
    {
        $theme = FrontendTheme::query()->create([
            'slug' => 'landwind',
            'name' => 'Landwind',
            'is_active' => true,
        ]);

        (new FrontendThemeSeeder)->run();

        $this->assertTrue((bool) $theme->fresh()->is_active);
    }

    #[Test]
    public function seeder_creates_missing_themes_as_unpublished(): void
    {
        (new FrontendThemeSeeder)->run();

        $created = FrontendTheme::query()->where('slug', 'play-tailwind')->first();
        $this->assertNotNull($created);
        $this->assertFalse((bool) $created->is_active);
    }
}
