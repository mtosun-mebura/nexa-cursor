<?php

namespace Tests\Unit;

use App\Support\TenantSync\TenantSyncDatabaseUrl;
use PHPUnit\Framework\TestCase;

class TenantSyncDatabaseUrlTest extends TestCase
{
    public function test_strip_and_inject_password(): void
    {
        $url = 'pgsql://mtosun:MaliMert1316%21@192.168.2.41:5432/nexa';

        $stripped = TenantSyncDatabaseUrl::stripPassword($url);
        $this->assertSame('pgsql://mtosun@192.168.2.41:5432/nexa', $stripped);

        $restored = TenantSyncDatabaseUrl::injectPassword($stripped, 'MaliMert1316!');
        $this->assertSame('pgsql://mtosun:MaliMert1316%21@192.168.2.41:5432/nexa', $restored);
    }

    public function test_replace_host_port(): void
    {
        $url = 'pgsql://user:secret@192.168.2.41:5432/nexa';
        $tunneled = TenantSyncDatabaseUrl::replaceHostPort($url, '127.0.0.1', 15432);

        $this->assertSame('pgsql://user:secret@127.0.0.1:15432/nexa', $tunneled);
    }

    public function test_build_and_parse(): void
    {
        $url = TenantSyncDatabaseUrl::buildConnection('pgsql', 'nexa', '127.0.0.1', 5432, 'nexa-staging');

        $this->assertSame('pgsql://nexa@127.0.0.1:5432/nexa-staging', $url);
        $this->assertSame('nexa', TenantSyncDatabaseUrl::parseUser($url));
        $this->assertSame('nexa-staging', TenantSyncDatabaseUrl::parseDatabase($url));
    }
}
