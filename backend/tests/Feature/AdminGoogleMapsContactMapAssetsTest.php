<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regressie: Google Maps op admin bedrijf tonen — Static Maps (deterministisch) + tile-CSS voor andere pagina's.
 */
class AdminGoogleMapsContactMapAssetsTest extends TestCase
{
    #[Test]
    public function app_css_overrides_gm_style_img_max_width_for_map_tiles(): void
    {
        $path = resource_path('css/app.css');
        $this->assertFileExists($path);
        $css = (string) file_get_contents($path);
        $this->assertStringContainsString('.gm-style img', $css);
        $this->assertStringContainsString('max-width: none', $css);
    }

    #[Test]
    public function admin_company_show_uses_static_maps_and_server_geocode(): void
    {
        $path = resource_path('views/admin/companies/show.blade.php');
        $this->assertFileExists($path);
        $blade = (string) file_get_contents($path);
        $this->assertStringContainsString('maps.googleapis.com/maps/api/staticmap', $blade);
        $this->assertStringContainsString('http_build_query($staticParams', $blade);
        $this->assertStringContainsString('$companyContactStaticMapUrl', $blade);
        $this->assertStringContainsString('nexa-company-static-map', $blade);
        $this->assertStringContainsString('maps.googleapis.com/maps/api/geocode/json', $blade);
        $this->assertStringContainsString('$resolvedLat', $blade);
        $this->assertStringNotContainsString('maps.googleapis.com/maps/api/js', $blade);
        $this->assertStringNotContainsString('__nexaAdminCompanyShowMapInit', $blade);
    }
}
