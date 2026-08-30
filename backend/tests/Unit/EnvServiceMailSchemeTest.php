<?php

namespace Tests\Unit;

use App\Services\EnvService;
use RuntimeException;
use Tests\TestCase;

class EnvServiceMailSchemeTest extends TestCase
{
    public function test_tls_on_port_587_maps_to_smtp_scheme(): void
    {
        $env = app(EnvService::class);

        $this->assertSame('smtp', $env->smtpSchemeForEncryption('tls', 587));
        $this->assertSame('smtp', $env->smtpSchemeForEncryption('tls', '587'));
    }

    public function test_ssl_or_port_465_maps_to_smtps_scheme(): void
    {
        $env = app(EnvService::class);

        $this->assertSame('smtps', $env->smtpSchemeForEncryption('ssl', 465));
        $this->assertSame('smtps', $env->smtpSchemeForEncryption('tls', 465));
        $this->assertSame('smtps', $env->smtpSchemeForEncryption('smtps', 587));
    }

    public function test_tls_scheme_exception_is_explained_in_dutch(): void
    {
        $env = app(EnvService::class);
        $message = $env->explainMailSendException(new RuntimeException(
            'The "tls" scheme is not supported; supported schemes for mailer "smtp" are: "smtp", "smtps".'
        ));

        $this->assertStringContainsString('smtp', $message);
        $this->assertStringContainsString('smtps', $message);
        $this->assertStringNotContainsString('scheme is not supported', $message);
    }
}
