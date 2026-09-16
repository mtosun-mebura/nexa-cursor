<?php

namespace Tests\Unit;

use App\Support\NexaPublicCopy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NexaPublicCopyTest extends TestCase
{
    #[Test]
    public function tenant_wording_becomes_klant_on_the_public_site(): void
    {
        $this->assertSame(
            'White-label per klant',
            NexaPublicCopy::replaceTenantWording('White-label per tenant')
        );
        $this->assertSame(
            'Elke klant krijgt een eigen site',
            NexaPublicCopy::replaceTenantWording('Elke tenant krijgt een eigen site')
        );
        $this->assertSame(
            'Merkbare klantwebsites met boekingsmodule',
            NexaPublicCopy::replaceTenantWording('Merkbare tenant-sites met boekingsmodule')
        );
        $this->assertSame(
            'Meerdere klanten',
            NexaPublicCopy::replaceTenantWording('Multi-tenant')
        );
        $this->assertSame(
            'Meerdere klanten',
            NexaPublicCopy::replaceTenantWording('Multi-tenant basis')
        );
        $this->assertSame(
            'in jouw omgeving',
            NexaPublicCopy::replaceTenantWording('in jouw tenant')
        );
        $this->assertSame(
            'dezelfde klant, dezelfde chauffeurs',
            NexaPublicCopy::replaceTenantWording('dezelfde tenant, dezelfde chauffeurs')
        );
    }

    #[Test]
    public function nested_home_sections_are_rewritten(): void
    {
        $updated = NexaPublicCopy::replaceTenantWordingIn([
            'footer' => [
                'tagline' => 'White-label per tenant.',
            ],
            'items' => [
                ['label' => 'White-label per tenant'],
            ],
        ]);

        $this->assertSame('White-label per klant.', $updated['footer']['tagline']);
        $this->assertSame('White-label per klant', $updated['items'][0]['label']);
    }
}
