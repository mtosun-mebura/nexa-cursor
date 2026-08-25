<?php

namespace App\Support;

use RuntimeException;

/**
 * Blokkeert destructieve database-commando's op de lokale ontwikkeldatabase.
 *
 * Artisan op de host (Mac) en in Docker delen vaak dezelfde PostgreSQL op 127.0.0.1:5432.
 * Eén migrate:fresh vanaf de host wist daardoor alle data die de draaiende app gebruikt.
 */
final class DestructiveDatabaseGuard
{
    /** @var list<string> */
    public const BLOCKED_COMMANDS = [
        'migrate:fresh',
        'migrate:refresh',
        'migrate:reset',
        'db:wipe',
        'nexa:reset-all',
    ];

    public static function allowsDestructiveOperations(): bool
    {
        if (self::envFlagAllowsDestructive()) {
            return true;
        }

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($database === ':memory:') {
            return true;
        }

        return (bool) preg_match('/(_test|_testing|testing)$/i', $database);
    }

    public static function abortIfBlocked(?string $commandName): void
    {
        if ($commandName === null || $commandName === '') {
            return;
        }

        if (! in_array($commandName, self::BLOCKED_COMMANDS, true)) {
            return;
        }

        if (self::allowsDestructiveOperations()) {
            return;
        }

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        throw new RuntimeException(sprintf(
            'Geblokkeerd: "%s" op database "%s" (%s). Dit wist alle data in je lokale ontwikkelomgeving. '
            .'Gebruik "php artisan migrate" voor schema-updates, of "php artisan nexa:ensure-bootstrap" om super-admin en basisdata te herstellen zonder data te wissen. '
            .'Alleen voor een bewuste schone lei: zet NEXA_ALLOW_DESTRUCTIVE_DB=true in .env.',
            $commandName,
            $database,
            $connection
        ));
    }

    public static function assertAllowedForService(string $action): void
    {
        if (self::allowsDestructiveOperations()) {
            return;
        }

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        throw new RuntimeException(sprintf(
            'Geblokkeerd: %s op database "%s". Zet NEXA_ALLOW_DESTRUCTIVE_DB=true in .env als dit bewust moet.',
            $action,
            $database
        ));
    }

    /**
     * Laravel env() is immutable na boot. Tests en runtime-overrides zitten in $_ENV/$_SERVER/getenv.
     */
    private static function envFlagAllowsDestructive(): bool
    {
        $raw = $_ENV['NEXA_ALLOW_DESTRUCTIVE_DB']
            ?? $_SERVER['NEXA_ALLOW_DESTRUCTIVE_DB']
            ?? getenv('NEXA_ALLOW_DESTRUCTIVE_DB');

        if ($raw === false || $raw === null || $raw === '') {
            return false;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOL);
    }

    public static function allowsDatabaseRestore(): bool
    {
        if (app()->environment('local')) {
            return true;
        }

        return self::allowsDestructiveOperations();
    }

    public static function assertAllowedForDatabaseRestore(): void
    {
        if (self::allowsDatabaseRestore()) {
            return;
        }

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        throw new RuntimeException(sprintf(
            'Database-herstel is geblokkeerd op "%s". Zet NEXA_ALLOW_DESTRUCTIVE_DB=true in .env als dit bewust moet.',
            $database
        ));
    }
}
