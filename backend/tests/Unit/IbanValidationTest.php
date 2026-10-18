<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IbanValidationTest extends TestCase
{
    #[Test]
    public function a_real_iban_passes_country_length_and_checksum(): void
    {
        $this->assertTrue(is_valid_iban('NL91 ABNA 0417 1643 00'));
        $this->assertTrue(is_valid_iban('DE89370400440532013000'));
        $this->assertTrue(is_valid_iban('GB82WEST12345698765432'));
    }

    #[Test]
    public function a_wrong_length_or_checksum_is_rejected(): void
    {
        $this->assertFalse(is_valid_iban('NL91ABNA041716430'));
        $this->assertFalse(is_valid_iban('NL91ABNA04171643001'));
        $this->assertFalse(is_valid_iban('NL00ABNA0417164300'));
        $this->assertFalse(is_valid_iban('XX91ABNA0417164300'));
    }
}
