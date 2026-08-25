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

    private function setDestructiveDbFlag(?string $value): void
    {
        if ($value === null) {
            putenv('NEXA_ALLOW_DESTRUCTIVE_DB');
            unset($_ENV['NEXA_ALLOW_DESTRUCTIVE_DB'], $_SERVER['NEXA_ALLOW_DESTRUCTIVE_DB']);

            return;
        }

        putenv('NEXA_ALLOW_DESTRUCTIVE_DB='.$value);
        $_ENV['NEXA_ALLOW_DESTRUCTIVE_DB'] = $value;
        $_SERVER['NEXA_ALLOW_DESTRUCTIVE_DB'] = $value;
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
        $this->setDestructiveDbFlag('false');

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
        $this->setDestructiveDbFlag('true');

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => 'nexa',
        ]);

        $this->assertTrue(DestructiveDatabaseGuard::allowsDestructiveOperations());
        DestructiveDatabaseGuard::abortIfBlocked('migrate:fresh');
        $this->addToAssertionCount(1);
    }
}
