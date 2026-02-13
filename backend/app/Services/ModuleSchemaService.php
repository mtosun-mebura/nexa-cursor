<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Beheert per-module PostgreSQL-schema's: aanmaken, basis-tabellen, superadmin-seed.
 * Alleen actief wanneer DB_CONNECTION=pgsql.
 */
class ModuleSchemaService
{
    public const SUPERADMIN_EMAIL = 'm.tosun@mebura.nl';
    public const SUPERADMIN_PASSWORD = '!';

    /**
     * Schema-naam voor een module (bijv. skillmatching -> module_skillmatching).
     */
    public function getSchemaName(string $moduleName): string
    {
        return 'module_' . strtolower(preg_replace('/[^a-z0-9_]/i', '_', $moduleName));
    }

    /**
     * Of de huidige database PostgreSQL is.
     */
    public function supportsModuleSchemas(): bool
    {
        return config('database.default') === 'pgsql';
    }

    /**
     * Maak een schema aan voor de module.
     */
    public function createSchema(string $moduleName): void
    {
        if (!$this->supportsModuleSchemas()) {
            return;
        }
        $schema = $this->getSchemaName($moduleName);
        DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$schema}\"");
    }

    /**
     * Voer een callback uit met search_path op het gegeven schema.
     */
    public function runInSchema(string $schemaName, callable $callback): void
    {
        DB::statement("SET search_path TO \"{$schemaName}\"");
        try {
            $callback();
        } finally {
            DB::statement('SET search_path TO public');
        }
    }

    /**
     * Maak basis-tabellen aan in het schema (users, sessions, password_reset_tokens, roles, permissions, cache, jobs).
     */
    public function runBaseTablesInSchema(string $schemaName): void
    {
        $this->runInSchema($schemaName, function () {
            $this->createBaseTables();
        });
    }

    /**
     * Seed superadmin in het schema.
     */
    public function seedSuperadminInSchema(string $schemaName): void
    {
        $this->runInSchema($schemaName, function () use ($schemaName) {
            $this->createSuperadminUserAndRole();
        });
    }

    /**
     * Volledige setup: schema aanmaken, basis-tabellen, superadmin. Aanroepen bij module install.
     */
    public function setupModuleSchema(string $moduleName): void
    {
        if (!$this->supportsModuleSchemas()) {
            return;
        }
        $this->createSchema($moduleName);
        $schema = $this->getSchemaName($moduleName);
        $this->runBaseTablesInSchema($schema);
        $this->seedSuperadminInSchema($schema);
    }

    /**
     * Schema verwijderen (bij uninstall).
     */
    public function dropSchema(string $moduleName): void
    {
        if (!$this->supportsModuleSchemas()) {
            return;
        }
        $schema = $this->getSchemaName($moduleName);
        DB::statement("DROP SCHEMA IF EXISTS \"{$schema}\" CASCADE");
    }

    /**
     * Basis-tabellen aanmaken in de huidige search_path.
     */
    protected function createBaseTables(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->date('date_of_birth')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function ($table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function ($table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('permissions', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_roles', function ($table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function ($table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function ($table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['permission_id', 'role_id']);
        });

        if (!Schema::hasTable('cache')) {
            Schema::create('cache', function ($table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (!Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function ($table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function ($table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }
    }

    /**
     * Superadmin-gebruiker en super-admin rol aanmaken in de huidige search_path.
     */
    protected function createSuperadminUserAndRole(): void
    {
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'super-admin',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => self::SUPERADMIN_EMAIL,
            'email_verified_at' => now(),
            'password' => Hash::make(self::SUPERADMIN_PASSWORD),
            'company_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => 'App\Models\User',
            'model_id' => $userId,
        ]);
    }
}
