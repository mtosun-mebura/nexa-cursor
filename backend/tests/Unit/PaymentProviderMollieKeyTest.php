<?php

namespace Tests\Unit;

use App\Services\PaymentProviderService;
use Tests\TestCase;

class PaymentProviderMollieKeyTest extends TestCase
{
    public function test_valid_mollie_api_key_formats(): void
    {
        $this->assertTrue(PaymentProviderService::isValidMollieApiKeyFormat('test_1234567890abcdef'));
        $this->assertTrue(PaymentProviderService::isValidMollieApiKeyFormat('live_dHarccVRALvMa0OgWgqkPfPJ5zqGv'));
        $this->assertTrue(PaymentProviderService::isValidMollieApiKeyFormat('  test_abcdefghijklmnop  '));
    }

    public function test_invalid_mollie_api_key_formats(): void
    {
        $this->assertFalse(PaymentProviderService::isValidMollieApiKeyFormat(null));
        $this->assertFalse(PaymentProviderService::isValidMollieApiKeyFormat(''));
        $this->assertFalse(PaymentProviderService::isValidMollieApiKeyFormat('test_key'));
        $this->assertFalse(PaymentProviderService::isValidMollieApiKeyFormat('sk_test_1234567890'));
        $this->assertFalse(PaymentProviderService::isValidMollieApiKeyFormat('live_'));
    }
}
