<?php

namespace Tests\Unit;

use App\Support\DestructiveDatabaseGuard;
use RuntimeException;
use Tests\TestCase;

class DestructiveDatabaseGuardTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('NEXA_ALLOW_DESTRUCTIVE_DB');
        unset($_ENV['NEXA_ALLOW_DESTRUCTIVE_DB'], $_SERVER['NEXA_ALLOW_DESTRUCTIVE_DB']);

        parent::tearDown();
    }

    public function test_migrate_fresh_is_allowed_on_sqlite_memory_database(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        $this->assertTrue(DestructiveDatabaseGuard::allowsDestructiveOperations());
        DestructiveDatabaseGuard::abortIfBlocked('migrate:fresh');
        $this->addToAssertionCount(1);
    }

    public function test_migrate_fresh_is_blocked_on_dev_database_without_override(): void
    {
        putenv('NEXA_ALLOW_DESTRUCTIVE_DB=false');
        $_ENV['NEXA_ALLOW_DESTRUCTIVE_DB'] = 'false';

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => 'nexa',
        ]);

        $this->assertFalse(DestructiveDatabaseGuard::allowsDestructiveOperations());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Geblokkeerd: "migrate:fresh"');

        DestructiveDatabaseGuard::abortIfBlocked('migrate:fresh');
    }

    public function test_migrate_fresh_is_allowed_when_override_env_is_true(): void
    {
        putenv('NEXA_ALLOW_DESTRUCTIVE_DB=true');
        $_ENV['NEXA_ALLOW_DESTRUCTIVE_DB'] = 'true';

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => 'nexa',
        ]);

        $this->assertTrue(DestructiveDatabaseGuard::allowsDestructiveOperations());
        DestructiveDatabaseGuard::abortIfBlocked('migrate:fresh');
        $this->addToAssertionCount(1);
    }
}
