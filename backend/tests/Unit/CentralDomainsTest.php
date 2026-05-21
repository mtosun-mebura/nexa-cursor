<?php

namespace Tests\Unit;

use App\Support\Tenancy\CentralDomains;
use Tests\TestCase;

class CentralDomainsTest extends TestCase
{
    public function test_private_lan_ip_is_central_in_non_production(): void
    {
        $this->assertTrue(CentralDomains::isLocalDevEntryHost('192.168.2.32'));
        $this->assertTrue(CentralDomains::isCentral('192.168.2.32'));
    }

    public function test_public_ip_is_not_local_dev_entry_host(): void
    {
        $this->assertFalse(CentralDomains::isLocalDevEntryHost('8.8.8.8'));
    }

    public function test_localhost_is_local_dev_entry_host(): void
    {
        $this->assertTrue(CentralDomains::isLocalDevEntryHost('localhost'));
        $this->assertTrue(CentralDomains::isCentral('localhost'));
    }
}
