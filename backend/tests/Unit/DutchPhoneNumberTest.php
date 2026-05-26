<?php

namespace Tests\Unit;

use App\Support\DutchPhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DutchPhoneNumberTest extends TestCase
{
    #[DataProvider('validCasesProvider')]
    public function test_normalizes_valid_nl_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, DutchPhoneNumber::normalizeOptionalNlToInternational($input));
    }

    public static function validCasesProvider(): array
    {
        return [
            'empty' => ['', ''],
            'national mobile' => ['0612345678', '+31612345678'],
            'national with spaces' => ['06 12 34 56 78', '+31612345678'],
            'international plus' => ['+31612345678', '+31612345678'],
            'international spaced' => ['+31 6 12 34 56 78', '+31612345678'],
            'digits 31' => ['31612345678', '+31612345678'],
            '0031 prefix' => ['0031612345678', '+31612345678'],
        ];
    }

    public function test_invalid_non_empty_returns_null(): void
    {
        $this->assertNull(DutchPhoneNumber::normalizeOptionalNlToInternational('abc'));
        $this->assertNull(DutchPhoneNumber::normalizeOptionalNlToInternational('06123'));
        $this->assertNull(DutchPhoneNumber::normalizeOptionalNlToInternational('+441234567890'));
    }
}
