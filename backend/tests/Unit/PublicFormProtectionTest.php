<?php

namespace Tests\Unit;

use App\Services\PublicFormProtection;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicFormProtectionTest extends TestCase
{
    #[Test]
    public function test_detects_filled_honeypot(): void
    {
        $protection = new PublicFormProtection;
        $filled = Request::create('/info-request', 'POST', ['company_website' => 'https://spam.test']);
        $empty = Request::create('/info-request', 'POST', ['company_website' => '']);

        $this->assertTrue($protection->honeypotFilled($filled));
        $this->assertFalse($protection->honeypotFilled($empty));
    }

    #[Test]
    public function test_detects_malicious_markup(): void
    {
        $protection = new PublicFormProtection;

        $this->assertTrue($protection->containsMaliciousMarkup('<script>alert(1)</script>'));
        $this->assertTrue($protection->containsMaliciousMarkup('javascript:alert(1)'));
        $this->assertFalse($protection->containsMaliciousMarkup('Ik wil NEXA Suite voor mijn taxibedrijf.'));
    }

    #[Test]
    public function test_strips_tags_and_control_characters(): void
    {
        $protection = new PublicFormProtection;

        $this->assertSame('Hallo wereld', $protection->sanitizePlainText("  Hallo <b>wereld</b>\n "));
        $this->assertSame("Regel 1\nRegel 2", $protection->sanitizePlainText("Regel 1\nRegel 2", true));
    }

    #[Test]
    public function test_detects_header_injection(): void
    {
        $protection = new PublicFormProtection;

        $this->assertTrue($protection->containsHeaderInjection("jan@example.com\nBcc:evil@example.com"));
        $this->assertFalse($protection->containsHeaderInjection('jan@example.com'));
    }
}
