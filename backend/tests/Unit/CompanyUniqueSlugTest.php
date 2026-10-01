<?php

namespace Tests\Unit;

use App\Models\Company;
use Tests\TestCase;

class CompanyUniqueSlugTest extends TestCase
{
    public function test_duplicate_company_names_get_unique_slugs(): void
    {
        $first = Company::query()->create([
            'name' => 'Taxi Uniek BV',
            'email' => 'uniek1@example.com',
            'phone' => '0612345678',
            'kvk_number' => '11111111',
            'industry' => 'Taxi',
            'street' => 'Kerkstraat',
            'house_number' => '1',
            'postal_code' => '1234AB',
            'city' => 'Amsterdam',
            'package_key' => 'start',
            'is_active' => true,
        ]);

        $second = Company::query()->create([
            'name' => 'Taxi Uniek BV',
            'email' => 'uniek2@example.com',
            'phone' => '0612345679',
            'kvk_number' => '22222222',
            'industry' => 'Taxi',
            'street' => 'Kerkstraat',
            'house_number' => '2',
            'postal_code' => '1234AB',
            'city' => 'Amsterdam',
            'package_key' => 'start',
            'is_active' => true,
        ]);

        $this->assertSame('taxi-uniek-bv', $first->slug);
        $this->assertSame('taxi-uniek-bv-2', $second->slug);
        $this->assertNotSame($first->slug, $second->slug);
    }

    public function test_empty_slug_source_falls_back_to_company(): void
    {
        $this->assertSame('company', Company::uniqueSlugFromName('!!!'));
    }
}
