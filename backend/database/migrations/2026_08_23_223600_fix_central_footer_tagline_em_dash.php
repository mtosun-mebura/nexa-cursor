<?php

use App\Models\WebsitePage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_TAGLINE = 'Modulair SaaS voor taxibedrijven: website, online boeking, chauffeur-app en contractvervoer — white-label per tenant.';

    private const NEW_TAGLINE = 'Modulair SaaS voor taxibedrijven: website, online boeking, chauffeur-app en contractvervoer. White-label per tenant.';

    public function up(): void
    {
        if (! Schema::hasTable('website_pages')) {
            return;
        }

        WebsitePage::query()->chunkById(100, function ($pages): void {
            foreach ($pages as $page) {
                $sections = $page->home_sections;
                if (! is_array($sections)) {
                    continue;
                }
                $footer = $sections['footer'] ?? null;
                if (! is_array($footer)) {
                    continue;
                }
                if (($footer['tagline'] ?? '') !== self::OLD_TAGLINE) {
                    continue;
                }
                $footer['tagline'] = self::NEW_TAGLINE;
                $sections['footer'] = $footer;
                $page->home_sections = $sections;
                $page->save();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('website_pages')) {
            return;
        }

        WebsitePage::query()->chunkById(100, function ($pages): void {
            foreach ($pages as $page) {
                $sections = $page->home_sections;
                if (! is_array($sections)) {
                    continue;
                }
                $footer = $sections['footer'] ?? null;
                if (! is_array($footer)) {
                    continue;
                }
                if (($footer['tagline'] ?? '') !== self::NEW_TAGLINE) {
                    continue;
                }
                $footer['tagline'] = self::OLD_TAGLINE;
                $sections['footer'] = $footer;
                $page->home_sections = $sections;
                $page->save();
            }
        });
    }
};
