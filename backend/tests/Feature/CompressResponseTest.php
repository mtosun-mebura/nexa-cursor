<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompressResponseTest extends TestCase
{
    #[Test]
    public function login_html_is_gzipped_when_client_accepts_it(): void
    {
        $response = $this->withHeaders([
            'Accept-Encoding' => 'gzip',
        ])->get('/admin/login');

        $response->assertOk();
        $response->assertHeader('Content-Encoding', 'gzip');

        $decoded = gzdecode($response->getContent());
        $this->assertNotFalse($decoded);
        $this->assertStringContainsString('Admin Login', $decoded);
        $this->assertStringContainsString('ktui.min.js', $decoded);
        $this->assertStringNotContainsString('apexcharts', $decoded);
    }

    #[Test]
    public function login_html_stays_plain_without_gzip_accept(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $this->assertNotSame('gzip', $response->headers->get('Content-Encoding'));
        $response->assertSee('Admin Login', false);
    }
}
