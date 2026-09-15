<?php

namespace Tests\Unit;

use App\Support\SameOriginPath;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SameOriginPathTest extends TestCase
{
    #[Test]
    public function it_strips_localhost_host_from_absolute_urls(): void
    {
        $this->assertSame(
            '/api/taxi/v1/driver/login',
            SameOriginPath::fromUrl('http://localhost:8085/api/taxi/v1/driver/login')
        );
        $this->assertSame(
            '/taxi/chauffeur?v=1',
            SameOriginPath::fromUrl('http://192.168.2.70:8085/taxi/chauffeur?v=1')
        );
        $this->assertSame('/taxi/chauffeur', SameOriginPath::fromUrl('/taxi/chauffeur'));
    }

    #[Test]
    public function named_route_returns_path_not_app_url_host(): void
    {
        Route::get('/taxi/chauffeur', fn () => 'ok')->name('taxi.chauffeur.index');

        $this->assertSame(
            '/taxi/chauffeur',
            SameOriginPath::namedRoute('taxi.chauffeur.index', '/taxi/chauffeur')
        );
        $this->assertSame(
            '/taxi/missing',
            SameOriginPath::namedRoute('taxi.does-not-exist', '/taxi/missing')
        );
    }
}
