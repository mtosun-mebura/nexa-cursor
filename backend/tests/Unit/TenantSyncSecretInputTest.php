<?php

namespace Tests\Unit;

use App\Support\TenantSync\TenantSyncSecretInput;
use PHPUnit\Framework\TestCase;

class TenantSyncSecretInputTest extends TestCase
{
    public function test_decodes_html_entities(): void
    {
        $this->assertSame('Memmo@Mdb!', TenantSyncSecretInput::normalize('Memmo&#64;Mdb!'));
        $this->assertSame('a&b', TenantSyncSecretInput::normalize('a&amp;b'));
    }

    public function test_decodes_url_encoding_when_present(): void
    {
        $this->assertSame('Memmo@Mdb!', TenantSyncSecretInput::normalize('Memmo%40Mdb%21'));
    }

    public function test_plain_password_unchanged(): void
    {
        $this->assertSame('plain-Pass_123', TenantSyncSecretInput::normalize('plain-Pass_123'));
    }

    public function test_to_url_encoded_form(): void
    {
        $this->assertSame('Memmo%40Mdb%21', TenantSyncSecretInput::toUrlEncodedForm('Memmo@Mdb!'));
        $this->assertSame('Memmo%40Mdb%21', TenantSyncSecretInput::toUrlEncodedForm('Memmo%40Mdb%21'));
        $this->assertSame('simple', TenantSyncSecretInput::toUrlEncodedForm('simple'));
    }
}
