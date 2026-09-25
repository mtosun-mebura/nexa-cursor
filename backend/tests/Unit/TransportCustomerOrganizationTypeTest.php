<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\TransportCustomer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransportCustomerOrganizationTypeTest extends TestCase
{
    #[Test]
    public function known_organization_type_keeps_its_label(): void
    {
        $customer = new TransportCustomer(['organization_type' => 'school']);

        $this->assertSame('school', $customer->organizationTypeKey());
        $this->assertSame('School', $customer->organizationTypeLabel());
    }

    #[Test]
    public function unknown_organization_type_falls_back_to_overig(): void
    {
        $customer = new TransportCustomer(['organization_type' => 'unknown']);

        $this->assertSame('overig', $customer->organizationTypeKey());
        $this->assertSame('Overig', $customer->organizationTypeLabel());
    }

    #[Test]
    public function billing_address_lines_skip_empty_parts(): void
    {
        $customer = new TransportCustomer([
            'billing_address' => 'Schoolstraat 1',
            'billing_postal_code' => '7511 AB',
            'billing_city' => 'Enschede',
            'billing_country' => 'Nederland',
        ]);

        $this->assertSame(
            ['Schoolstraat 1', '7511 AB Enschede', 'Nederland'],
            $customer->billingAddressLines()
        );
    }
}
