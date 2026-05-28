<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/**
 * Push van één tenant: alle rijen op tabellen met kolom company_id + de companies-rij zelf.
 * Geen bron-primary keys overnemen: nieuwe id's op doel, met remapping van FK's tussen deze tabellen.
 * Bij unieke constraint: rij overslaan (add-only); company match op slug om bestaande tenant te hergebruiken.
 */
final class TenantCompanyDataPushService
{
    public function __construct(
        protected TenantWebsiteBundleService $websiteBundle
    ) {}

    /**
     * Introspectie op de huidige (bron-)database: welke tabellen vallen onder tenant-sync.
     *
     * @return array{
     *     company_row: string,
     *     tables_with_company_id: list<string>,
     *     prerequisite_tables: list<string>,
     *     payment_company_scoped_tables: list<string>,
     *     excluded_tables: list<string>,
     *     driver: string
     * }
     */
    public function describeSyncScope(): array
    {
        $connection = (string) config('database.default');
        $driver = Schema::connection($connection)->getConnection()->getDriverName();
        $tables = $this->discoverCompanyScopedTables($connection);
        $paymentConfigured = config('tenant_sync.payment_company_scoped_tables', []);
        $paymentTables = [];
        if (is_array($paymentConfigured)) {
            foreach ($paymentConfigured as $t) {
                if (is_string($t) && in_array($t, $tables, true)) {
                    $paymentTables[] = $t;
                }
            }
        }

        return [
            'company_row' => 'companies (één rij per tenant; op doel hergebruikt op slug of nieuw id)',
            'tables_with_company_id' => $tables,
            'prerequisite_tables' => $this->discoverPrerequisiteTables($connection, $tables),
            'payment_company_scoped_tables' => $paymentTables,
            'excluded_tables' => array_values(config('tenant_sync.excluded_tables', [])),
            'driver' => $driver,
        ];
    }

    /**
     * @return array{remote_company_id: int, inserted: int, skipped: int, tables: list<string>, messages: list<string>}
     */
    public function pushFullTenant(int $sourceCompanyId): array
    {
        return $this->websiteBundle->runWithSyncTarget(function () use ($sourceCompanyId) {
            return $this->pushFullTenantThroughTunnel($sourceCompanyId);
        });
    }

    /**
     * @return array{remote_company_id: int, inserted: int, skipped: int, tables: list<string>, messages: list<string>}
     */
    private function pushFullTenantThroughTunnel(int $sourceCompanyId): array
    {
        $sourceConn = (string) config('database.default');
        $targetConn = TenantWebsiteBundleService::SYNC_CONNECTION;

        $company = Company::query()->find($sourceCompanyId);
        if ($company === null) {
            throw new RuntimeException('Bron-bedrijf niet gevonden.');
        }

        $this->websiteBundle->registerSyncConnection();

        $messages = [];
        $inserted = 0;
        $skipped = 0;

        try {
            $tables = $this->discoverCompanyScopedTables($sourceConn);
            if ($tables === []) {
                throw new RuntimeException('Geen tabellen met company_id gevonden op de bron-database.');
            }

            $prerequisiteTables = $this->discoverPrerequisiteTables($sourceConn, $tables);
            $prerequisiteFkEdges = $this->discoverPrerequisiteForeignKeys($sourceConn, $tables, $prerequisiteTables);
            $intraPrerequisiteFkEdges = $prerequisiteTables !== []
                ? $this->discoverForeignKeysToParentId($sourceConn, $prerequisiteTables)
                : [];

            $idMaps = [];
            if ($prerequisiteTables !== []) {
                $preStats = $this->pushPrerequisiteTables(
                    $sourceConn,
                    $targetConn,
                    $prerequisiteTables,
                    $intraPrerequisiteFkEdges,
                    $idMaps
                );
                $inserted += $preStats['inserted'];
                $skipped += $preStats['skipped'];
                $messages[] = sprintf(
                    'Globale vereiste tabellen (%s): %d rijen toegevoegd, %d overgeslagen.',
                    implode(', ', $prerequisiteTables),
                    $preStats['inserted'],
                    $preStats['skipped']
                );
            }

            $fkEdges = $this->discoverForeignKeysToParentId($sourceConn, $tables);
            $allFkEdges = array_merge($fkEdges, $prerequisiteFkEdges);
            $orderedTables = $this->orderTablesForInsert($tables, $fkEdges);

            $remoteCompanyId = $this->resolveOrCreateRemoteCompany($targetConn, $company, $messages, $idMaps);
            $sameDatabase = $this->connectionsPointToSameDatabase($sourceConn, $targetConn);
            if ($sameDatabase) {
                $messages[] = 'Bron en doel zijn dezelfde database: gebruikers worden bijgewerkt (company_id), niet dubbel ingevoegd.';
            }

            $userStats = $this->summarizeTenantUsersOnSource($sourceConn, $sourceCompanyId);
            $messages[] = sprintf(
                'Gebruikers op bron voor tenant %d: %d met users.company_id, %d via rollen/chauffeurs, %d rol-koppelingen naar ontbrekende users.',
                $sourceCompanyId,
                $userStats['direct'],
                $userStats['discovered'],
                $userStats['orphan_role_user_ids']
            );
            if ($userStats['discovered'] === 0 && $userStats['orphan_role_user_ids'] > 0) {
                $messages[] = 'Let op: er zijn model_has_roles voor dit bedrijf, maar de bijbehorende users-rijen ontbreken op de bron-database.';
            }

            foreach ($orderedTables as $table) {
                if ($table === 'companies') {
                    continue;
                }
                $this->assertTableHasColumn($sourceConn, $table, 'company_id');

                if ($table === 'users') {
                    $userSync = $sameDatabase
                        ? $this->syncUsersInPlaceOnConnection($sourceConn, $sourceCompanyId, $remoteCompanyId, $idMaps)
                        : $this->syncUsersToTargetConnection(
                            $sourceConn,
                            $targetConn,
                            $sourceCompanyId,
                            $remoteCompanyId,
                            $idMaps
                        );
                    $inserted += (int) ($userSync['inserted'] ?? 0) + (int) ($userSync['updated'] ?? 0);
                    $skipped += (int) ($userSync['skipped'] ?? 0);
                    if (($userSync['message'] ?? '') !== '') {
                        $messages[] = $userSync['message'];
                    }

                    continue;
                }

                $rows = $this->collectSourceRowsForCompanyTable($sourceConn, $table, $sourceCompanyId);
                foreach ($rows as $rowObj) {
                    $row = (array) $rowObj;
                    $oldId = isset($row['id']) ? (int) $row['id'] : null;

                    $payload = $this->prepareInsertPayload($table, $row, $remoteCompanyId, $idMaps, $allFkEdges);

                    if ($payload === null) {
                        $skipped++;

                        continue;
                    }

                    $outcome = $this->insertRowOnTarget(
                        $sourceConn,
                        $targetConn,
                        $table,
                        $payload,
                        $oldId,
                        $remoteCompanyId,
                        $idMaps
                    );
                    if ($outcome === 'inserted') {
                        $inserted++;
                    } else {
                        $skipped++;
                    }
                }
            }

            $postSync = config('tenant_sync.post_sync_tables', []);
            if (is_array($postSync)) {
                foreach ($postSync as $postTable) {
                    if (! is_string($postTable) || $postTable === '') {
                        continue;
                    }
                    $postResult = $this->pushPostSyncTable(
                        $sourceConn,
                        $targetConn,
                        $postTable,
                        $sourceCompanyId,
                        $remoteCompanyId,
                        $idMaps
                    );
                    $inserted += $postResult['inserted'];
                    $skipped += $postResult['skipped'];
                }
            }

            Log::info('tenant_full_push', [
                'source_company_id' => $sourceCompanyId,
                'remote_company_id' => $remoteCompanyId,
                'inserted' => $inserted,
                'skipped' => $skipped,
                'tables' => $orderedTables,
            ]);

            return [
                'remote_company_id' => $remoteCompanyId,
                'inserted' => $inserted,
                'skipped' => $skipped,
                'tables' => $orderedTables,
                'messages' => $messages,
            ];
        } finally {
            DB::purge($targetConn);
        }
    }

    /**
     * @param  array<string, array<int, int>>  $idMaps
     * @param  list<array{child:string, child_column:string, parent:string}>  $fkEdges
     */
    private function prepareInsertPayload(
        string $table,
        array $row,
        int $remoteCompanyId,
        array &$idMaps,
        array $fkEdges
    ): ?array {
        unset($row['id']);
        $row['company_id'] = $remoteCompanyId;
        $row = $this->remapRowForeignKeys($table, $row, $fkEdges, $idMaps);
        if ($row === null) {
            return null;
        }

        return $this->stripUnsupportedColumns($table, $row);
    }

    /**
     * @param  list<array{child:string, child_column:string, parent:string}>  $fkEdges
     * @param  array<string, array<int, int>>  $idMaps
     * @return array<string, mixed>|null
     */
    private function remapRowForeignKeys(string $table, array $row, array $fkEdges, array $idMaps): ?array
    {
        foreach ($fkEdges as $edge) {
            if ($edge['child'] !== $table) {
                continue;
            }
            $col = $edge['child_column'];
            if (! array_key_exists($col, $row) || $row[$col] === null) {
                continue;
            }
            $parent = $edge['parent'];
            $oldFk = (int) $row[$col];
            if ($oldFk === 0) {
                continue;
            }
            if (! isset($idMaps[$parent][$oldFk])) {
                return null;
            }
            $row[$col] = $idMaps[$parent][$oldFk];
        }

        return $row;
    }

    private function stripUnsupportedColumns(string $table, array $row): array
    {
        $conn = TenantWebsiteBundleService::SYNC_CONNECTION;
        $cols = Schema::connection($conn)->getColumnListing($table);
        $allowed = array_flip($cols);
        $out = [];
        foreach ($row as $k => $v) {
            if (isset($allowed[$k])) {
                $out[$k] = $v;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, array<int, int>>  $idMaps
     */
    private function tryLearnIdFromUniqueHit(
        string $targetConn,
        string $table,
        array $payload,
        ?int $oldId,
        array &$idMaps
    ): void {
        if ($oldId === null || ! Schema::connection($targetConn)->hasColumn($table, 'id')) {
            return;
        }
        $q = DB::connection($targetConn)->table($table);
        if (isset($payload['company_id'])) {
            $q->where('company_id', $payload['company_id']);
        }
        if ($table === 'users' && isset($payload['email'])) {
            $q->where('email', $payload['email']);
        } elseif ($table === 'roles' && isset($payload['name'], $payload['guard_name'])) {
            $q->where('name', $payload['name'])->where('guard_name', $payload['guard_name']);
        } elseif ($table === 'email_templates') {
            if (! empty($payload['type'])) {
                $q->where('type', $payload['type']);
            } elseif (isset($payload['name'])) {
                $q->where('name', $payload['name']);
            } else {
                return;
            }
        } elseif ($table === 'general_settings' && isset($payload['key'])) {
            $q->where('key', $payload['key']);
        } elseif ($table === 'website_pages' && isset($payload['slug'])) {
            $q->where('slug', $payload['slug']);
            if (array_key_exists('frontend_theme_id', $payload)) {
                if ($payload['frontend_theme_id'] === null) {
                    $q->whereNull('frontend_theme_id');
                } else {
                    $q->where('frontend_theme_id', $payload['frontend_theme_id']);
                }
            }
        } elseif ($table === 'vacancies' && isset($payload['slug'])) {
            $q->where('slug', $payload['slug']);
        } elseif ($table === 'notifications' && isset($payload['title'])) {
            $q->where('title', $payload['title']);
        } elseif ($table === 'company_domains' && isset($payload['host'])) {
            $q->where('host', $payload['host']);
        } elseif ($table === 'company_module' && isset($payload['module_id'])) {
            $q->where('module_id', $payload['module_id']);
        } elseif ($table === 'payment_providers' && isset($payload['provider_type'])) {
            $q->where('provider_type', $payload['provider_type']);
        } elseif ($table === 'invoice_settings') {
            if (array_key_exists('location_id', $payload)) {
                if ($payload['location_id'] === null) {
                    $q->whereNull('location_id');
                } else {
                    $q->where('location_id', $payload['location_id']);
                }
            }
        } elseif ($table === 'invoices') {
            if (! empty($payload['module']) && isset($payload['module_reference_id'])) {
                $q->where('module', $payload['module'])
                    ->where('module_reference_id', $payload['module_reference_id']);
            } elseif (isset($payload['invoice_number'])) {
                $q->where('invoice_number', $payload['invoice_number']);
            } else {
                return;
            }
        } elseif ($table === 'ride_payments' && ! empty($payload['mollie_payment_id'])) {
            $q->where('mollie_payment_id', $payload['mollie_payment_id']);
        } elseif ($table === 'payments' && ! empty($payload['payment_provider_id'])) {
            $q->where('payment_provider_id', $payload['payment_provider_id']);
        } else {
            return;
        }
        $found = $q->value('id');
        if ($found !== null) {
            $idMaps[$table][$oldId] = (int) $found;
        }
    }

    private function isDuplicateKeyException(Throwable $e): bool
    {
        $msg = $e->getMessage();

        return str_contains($msg, '23505')
            || str_contains($msg, 'Duplicate entry')
            || str_contains($msg, 'UNIQUE constraint failed');
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @return array{created_at?: mixed, updated_at?: mixed}
     */
    private function timestampsPayloadFromAttributes(array $attrs): array
    {
        $out = [];
        foreach (['created_at', 'updated_at'] as $column) {
            if (array_key_exists($column, $attrs) && $attrs[$column] !== null && $attrs[$column] !== '') {
                $out[$column] = $attrs[$column];
            }
        }

        return $out;
    }

    /**
     * Vult ontbrekende created_at/updated_at op doel aan (bijv. na eerdere sync zonder timestamps).
     *
     * @param  array<string, mixed>  $payload
     */
    private function backfillTimestampsIfMissingOnTarget(string $targetConn, string $table, int $rowId, array $payload): void
    {
        if ($rowId <= 0 || ! Schema::connection($targetConn)->hasTable($table)) {
            return;
        }

        $existing = DB::connection($targetConn)->table($table)->where('id', $rowId)->first();
        if ($existing === null) {
            return;
        }

        $update = [];
        foreach (['created_at', 'updated_at'] as $column) {
            if (! Schema::connection($targetConn)->hasColumn($table, $column)) {
                continue;
            }
            if (! array_key_exists($column, $payload) || $payload[$column] === null || $payload[$column] === '') {
                continue;
            }
            $current = $existing->{$column} ?? null;
            if ($current === null || $current === '') {
                $update[$column] = $payload[$column];
            }
        }

        if ($update !== []) {
            DB::connection($targetConn)->table($table)->where('id', $rowId)->update($update);
        }
    }

    /**
     * @param  array<string, array<int, int>>  $idMaps
     */
    private function resolveOrCreateRemoteCompany(string $targetConn, Company $source, array &$messages, array $idMaps = []): int
    {
        $attrs = $source->getAttributes();
        unset($attrs['id']);
        $slug = $attrs['slug'] ?? null;
        $sourceTimestamps = $this->timestampsPayloadFromAttributes($attrs);

        if (is_string($slug) && $slug !== '') {
            $existing = DB::connection($targetConn)->table('companies')->where('slug', $slug)->value('id');
            if ($existing !== null) {
                $messages[] = 'Bedrijf met dezelfde slug bestond al op doel; bestaande company_id '.$existing.' wordt aangevuld (geen tweede company aangemaakt).';
                $this->backfillTimestampsIfMissingOnTarget($targetConn, 'companies', (int) $existing, $sourceTimestamps);

                return (int) $existing;
            }
        }

        $payload = $this->stripUnsupportedColumns('companies', $attrs);
        unset($payload['id']);
        if (isset($payload['frontend_theme_id']) && $payload['frontend_theme_id'] !== null) {
            $oldThemeId = (int) $payload['frontend_theme_id'];
            if (isset($idMaps['frontend_themes'][$oldThemeId])) {
                $payload['frontend_theme_id'] = $idMaps['frontend_themes'][$oldThemeId];
            } else {
                unset($payload['frontend_theme_id']);
            }
        }

        return (int) DB::connection($targetConn)->table('companies')->insertGetId($payload);
    }

    /**
     * @return list<string>
     */
    private function discoverCompanyScopedTables(string $connection): array
    {
        $excluded = array_flip(config('tenant_sync.excluded_tables', []));
        $driver = Schema::connection($connection)->getConnection()->getDriverName();
        $names = [];

        if ($driver === 'pgsql') {
            $schema = $this->schemaName($connection);
            $rows = DB::connection($connection)->select(
                'SELECT DISTINCT c.table_name FROM information_schema.columns c
                 INNER JOIN information_schema.tables t
                   ON t.table_schema = c.table_schema AND t.table_name = c.table_name
                 WHERE c.table_schema = ? AND c.column_name = ? AND t.table_type = ?',
                [$schema, 'company_id', 'BASE TABLE']
            );
            foreach ($rows as $r) {
                $t = (string) $r->table_name;
                if (! isset($excluded[$t])) {
                    $names[] = $t;
                }
            }
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $db = (string) config("database.connections.{$connection}.database");
            $rows = DB::connection($connection)->select(
                'SELECT DISTINCT TABLE_NAME AS table_name FROM information_schema.columns
                 WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = ?',
                [$db, 'company_id']
            );
            foreach ($rows as $r) {
                $t = (string) $r->table_name;
                if (! isset($excluded[$t])) {
                    $names[] = $t;
                }
            }
        } else {
            foreach ($this->listSqliteTables($connection) as $t) {
                if (isset($excluded[$t])) {
                    continue;
                }
                if (Schema::connection($connection)->hasColumn($t, 'company_id')) {
                    $names[] = $t;
                }
            }
        }

        sort($names);

        return array_values(array_unique($names));
    }

    /**
     * @param  list<string>  $tables
     * @return list<array{child:string, child_column:string, parent:string}>
     */
    private function discoverForeignKeysToParentId(string $connection, array $tables): array
    {
        $set = array_flip($tables);
        $edges = [];
        foreach ($this->discoverForeignKeyEdgesForChildren($connection, $tables) as $edge) {
            if (isset($set[$edge['parent']])) {
                $edges[] = $edge;
            }
        }

        return $edges;
    }

    /**
     * FK's van company-scoped tabellen naar globale prerequisite-tabellen (bijv. company_module → modules).
     *
     * @param  list<string>  $companyTables
     * @param  list<string>  $prerequisiteTables
     * @return list<array{child:string, child_column:string, parent:string}>
     */
    private function discoverPrerequisiteForeignKeys(string $connection, array $companyTables, array $prerequisiteTables): array
    {
        if ($prerequisiteTables === []) {
            return [];
        }

        $prereqSet = array_flip($prerequisiteTables);
        $edges = [];
        foreach ($this->discoverForeignKeyEdgesForChildren($connection, $companyTables) as $edge) {
            if (isset($prereqSet[$edge['parent']])) {
                $edges[] = $edge;
            }
        }

        return $edges;
    }

    /**
     * @param  list<string>  $companyTables
     * @return list<string>
     */
    private function discoverPrerequisiteTables(string $connection, array $companyTables): array
    {
        $excluded = array_flip(config('tenant_sync.excluded_tables', []));
        $companySet = array_flip($companyTables);
        $discovered = [];

        foreach ($this->discoverForeignKeyEdgesForChildren($connection, $companyTables) as $edge) {
            $parent = $edge['parent'];
            if (! isset($companySet[$parent], $excluded[$parent])
                && Schema::connection($connection)->hasTable($parent)) {
                $discovered[$parent] = true;
            }
        }

        $configured = config('tenant_sync.prerequisite_tables', []);
        if (is_array($configured)) {
            foreach ($configured as $table) {
                if (! is_string($table) || $table === '' || isset($excluded[$table])) {
                    continue;
                }
                if (Schema::connection($connection)->hasTable($table)) {
                    $discovered[$table] = true;
                }
            }
        }

        $names = array_keys($discovered);
        if ($names === []) {
            return [];
        }

        $intraEdges = $this->discoverForeignKeysToParentId($connection, $names);

        return $this->orderTablesForInsert($names, $intraEdges);
    }

    /**
     * @param  list<string>  $childTables
     * @return list<array{child:string, child_column:string, parent:string}>
     */
    private function discoverForeignKeyEdgesForChildren(string $connection, array $childTables): array
    {
        if ($childTables === []) {
            return [];
        }

        $childSet = array_flip($childTables);
        $driver = Schema::connection($connection)->getConnection()->getDriverName();
        $edges = [];

        if ($driver === 'pgsql') {
            $schema = $this->schemaName($connection);
            $rows = DB::connection($connection)->select(
                'SELECT tc.table_name AS child_table, kcu.column_name AS child_column, ccu.table_name AS parent_table
                 FROM information_schema.table_constraints tc
                 JOIN information_schema.key_column_usage kcu
                   ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
                 JOIN information_schema.constraint_column_usage ccu
                   ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema
                 WHERE tc.constraint_type = ? AND tc.table_schema = ? AND ccu.column_name = ?',
                ['FOREIGN KEY', $schema, 'id']
            );
            foreach ($rows as $r) {
                $child = (string) $r->child_table;
                if (! isset($childSet[$child])) {
                    continue;
                }
                $edges[] = [
                    'child' => $child,
                    'child_column' => (string) $r->child_column,
                    'parent' => (string) $r->parent_table,
                ];
            }
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $db = (string) config("database.connections.{$connection}.database");
            $rows = DB::connection($connection)->select(
                'SELECT TABLE_NAME AS child_table, COLUMN_NAME AS child_column, REFERENCED_TABLE_NAME AS parent_table
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_SCHEMA = ?
                   AND REFERENCED_TABLE_NAME IS NOT NULL AND REFERENCED_COLUMN_NAME = ?',
                [$db, $db, 'id']
            );
            foreach ($rows as $r) {
                $child = (string) $r->child_table;
                if (! isset($childSet[$child])) {
                    continue;
                }
                $edges[] = [
                    'child' => $child,
                    'child_column' => (string) $r->child_column,
                    'parent' => (string) $r->parent_table,
                ];
            }
        }

        return $edges;
    }

    /**
     * @param  list<string>  $prerequisiteTables
     * @param  list<array{child:string, child_column:string, parent:string}>  $intraFkEdges
     * @param  array<string, array<int, int>>  $idMaps
     * @return array{inserted: int, skipped: int}
     */
    private function pushPrerequisiteTables(
        string $sourceConn,
        string $targetConn,
        array $prerequisiteTables,
        array $intraFkEdges,
        array &$idMaps
    ): array {
        $inserted = 0;
        $skipped = 0;

        foreach ($prerequisiteTables as $table) {
            if (! Schema::connection($sourceConn)->hasTable($table)) {
                continue;
            }

            $query = DB::connection($sourceConn)->table($table);
            if (Schema::connection($sourceConn)->hasColumn($table, 'id')) {
                $query->orderBy('id');
            }
            $rows = $query->get();

            foreach ($rows as $rowObj) {
                $row = (array) $rowObj;
                $oldId = isset($row['id']) ? (int) $row['id'] : null;
                $payload = $this->remapRowForeignKeys($table, $row, $intraFkEdges, $idMaps);
                if ($payload === null) {
                    $skipped++;

                    continue;
                }
                unset($payload['id']);
                $payload = $this->stripUnsupportedColumns($table, $payload);

                $outcome = $this->insertRowOnTarget(
                    $sourceConn,
                    $targetConn,
                    $table,
                    $payload,
                    $oldId,
                    0,
                    $idMaps
                );
                if ($outcome === 'inserted') {
                    $inserted++;
                } else {
                    $skipped++;
                }
            }
        }

        return ['inserted' => $inserted, 'skipped' => $skipped];
    }

    /**
     * @param  list<string>  $tables
     * @param  list<array{child:string, child_column:string, parent:string}>  $fkEdges
     * @return list<string>
     */
    private function orderTablesForInsert(array $tables, array $fkEdges): array
    {
        $tableSet = array_flip($tables);
        $inDegree = [];
        $graph = [];
        foreach ($tables as $t) {
            $inDegree[$t] = 0;
            $graph[$t] = [];
        }
        foreach ($fkEdges as $e) {
            $parent = $e['parent'];
            $child = $e['child'];
            if (! isset($tableSet[$parent], $tableSet[$child])) {
                continue;
            }
            $graph[$parent][] = $child;
            $inDegree[$child]++;
        }

        $priority = array_flip(config('tenant_sync.priority_tables', []));
        $ready = [];
        foreach ($tables as $t) {
            if ($inDegree[$t] === 0) {
                $ready[] = $t;
            }
        }
        $this->sortReadyByPriority($ready, $priority);

        $ordered = [];
        while ($ready !== []) {
            $t = array_shift($ready);
            $ordered[] = $t;
            foreach ($graph[$t] as $child) {
                $inDegree[$child]--;
                if ($inDegree[$child] === 0) {
                    $ready[] = $child;
                    $this->sortReadyByPriority($ready, $priority);
                }
            }
        }

        foreach ($tables as $t) {
            if (! in_array($t, $ordered, true)) {
                $ordered[] = $t;
            }
        }

        return $ordered;
    }

    /**
     * @param  list<string>  $ready
     * @param  array<string, int>  $priority
     */
    private function sortReadyByPriority(array &$ready, array $priority): void
    {
        usort($ready, function (string $a, string $b) use ($priority) {
            $pa = $priority[$a] ?? 1000;
            $pb = $priority[$b] ?? 1000;
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }

            return strcmp($a, $b);
        });
    }

    private function schemaName(string $connectionName): string
    {
        $path = config("database.connections.{$connectionName}.search_path");
        if (is_string($path) && trim($path) !== '') {
            return trim(explode(',', $path)[0]);
        }

        return 'public';
    }

    /**
     * @return list<string>
     */
    private function listSqliteTables(string $connection): array
    {
        $rows = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");

        return array_values(array_filter(array_map(fn ($r) => (string) $r->name, $rows)));
    }

    private function assertTableHasColumn(string $connection, string $table, string $column): void
    {
        if (! Schema::connection($connection)->hasColumn($table, $column)) {
            throw new RuntimeException("Tabel {$table} mist kolom {$column} op bron.");
        }
    }

    /**
     * @return Collection<int, object>
     */
    private function collectSourceRowsForCompanyTable(string $sourceConn, string $table, int $sourceCompanyId): Collection
    {
        if ($table === 'users') {
            return $this->collectUsersRowsForCompany($sourceConn, $sourceCompanyId);
        }

        return DB::connection($sourceConn)->table($table)->where('company_id', $sourceCompanyId)->get();
    }

    /**
     * Gebruikers met company_id, via tenant-rollen (model_has_roles) of als chauffeur op ritten.
     *
     * @return Collection<int, object>
     */
    private function collectUsersRowsForCompany(string $sourceConn, int $sourceCompanyId): Collection
    {
        if (! Schema::connection($sourceConn)->hasTable('users')) {
            return collect();
        }

        $userIds = $this->discoverTenantUserIds($sourceConn, $sourceCompanyId);
        if ($userIds === []) {
            return collect();
        }

        return DB::connection($sourceConn)->table('users')->whereIn('id', $userIds)->orderBy('id')->get();
    }

    /**
     * @return list<int>
     */
    private function discoverTenantUserIds(string $sourceConn, int $sourceCompanyId): array
    {
        $userIds = [];

        if (! Schema::connection($sourceConn)->hasTable('users')) {
            return [];
        }

        foreach (DB::connection($sourceConn)->table('users')->where('company_id', $sourceCompanyId)->pluck('id') as $id) {
            $userIds[(int) $id] = true;
        }

        if (Schema::connection($sourceConn)->hasTable('model_has_roles')) {
            $morphKey = config('permission.column_names.model_morph_key', 'model_id');
            $types = $this->userModelTypes();

            foreach (DB::connection($sourceConn)->table('model_has_roles')
                ->where('company_id', $sourceCompanyId)
                ->whereIn('model_type', $types)
                ->pluck($morphKey) as $id) {
                if ($id !== null) {
                    $userIds[(int) $id] = true;
                }
            }

            if (Schema::connection($sourceConn)->hasTable('roles')
                && Schema::connection($sourceConn)->hasColumn('roles', 'company_id')) {
                $tenantRoleIds = DB::connection($sourceConn)->table('roles')
                    ->where('company_id', $sourceCompanyId)
                    ->pluck('id');
                if ($tenantRoleIds->isNotEmpty()) {
                    foreach (DB::connection($sourceConn)->table('model_has_roles')
                        ->whereIn('role_id', $tenantRoleIds)
                        ->whereIn('model_type', $types)
                        ->pluck($morphKey) as $id) {
                        if ($id !== null) {
                            $userIds[(int) $id] = true;
                        }
                    }
                }
            }
        }

        if (Schema::connection($sourceConn)->hasTable('ride_requests')) {
            foreach (DB::connection($sourceConn)->table('ride_requests')
                ->where('company_id', $sourceCompanyId)
                ->whereNotNull('driver_id')
                ->distinct()
                ->pluck('driver_id') as $id) {
                $userIds[(int) $id] = true;
            }
        }

        if (Schema::connection($sourceConn)->hasTable('driver_availability')) {
            foreach (DB::connection($sourceConn)->table('driver_availability')
                ->where('company_id', $sourceCompanyId)
                ->pluck('driver_id') as $id) {
                $userIds[(int) $id] = true;
            }
        }

        return array_values(array_map('intval', array_keys($userIds)));
    }

    /**
     * @return array{direct: int, discovered: int, orphan_role_user_ids: int}
     */
    private function summarizeTenantUsersOnSource(string $sourceConn, int $sourceCompanyId): array
    {
        $direct = (int) DB::connection($sourceConn)->table('users')->where('company_id', $sourceCompanyId)->count();
        $ids = $this->discoverTenantUserIds($sourceConn, $sourceCompanyId);
        $discovered = count($ids);
        $existing = $discovered > 0
            ? (int) DB::connection($sourceConn)->table('users')->whereIn('id', $ids)->count()
            : 0;
        $orphanRoleRefs = 0;
        if (Schema::connection($sourceConn)->hasTable('model_has_roles')) {
            $morphKey = config('permission.column_names.model_morph_key', 'model_id');
            $orphanRoleRefs = (int) DB::connection($sourceConn)->table('model_has_roles')
                ->where('company_id', $sourceCompanyId)
                ->whereIn('model_type', $this->userModelTypes())
                ->whereNotIn($morphKey, DB::connection($sourceConn)->table('users')->select('id'))
                ->count();
        }

        return [
            'direct' => $direct,
            'discovered' => $existing,
            'orphan_role_user_ids' => $orphanRoleRefs,
        ];
    }

    /**
     * @param  array<string, array<int, int>>  $idMaps
     * @return array{updated: int, skipped: int, message: string}
     */
    private function syncUsersInPlaceOnConnection(
        string $connection,
        int $sourceCompanyId,
        int $remoteCompanyId,
        array &$idMaps
    ): array {
        $rows = $this->collectUsersRowsForCompany($connection, $sourceCompanyId);
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $userId = (int) $row->id;
            $idMaps['users'][$userId] = $userId;
            $currentCompanyId = $row->company_id ?? null;
            if ($currentCompanyId !== null && (int) $currentCompanyId === $remoteCompanyId) {
                $skipped++;

                continue;
            }
            DB::connection($connection)->table('users')
                ->where('id', $userId)
                ->update(['company_id' => $remoteCompanyId]);
            $updated++;
        }

        $message = sprintf(
            'Gebruikers (zelfde database): %d gevonden, %d company_id bijgewerkt, %d al correct.',
            $rows->count(),
            $updated,
            $skipped
        );

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'inserted' => 0,
            'message' => $message,
        ];
    }

    /**
     * @param  array<string, array<int, int>>  $idMaps
     * @return array{inserted: int, skipped: int, updated: int, message: string}
     */
    private function syncUsersToTargetConnection(
        string $sourceConn,
        string $targetConn,
        int $sourceCompanyId,
        int $remoteCompanyId,
        array &$idMaps
    ): array {
        $rows = $this->collectUsersRowsForCompany($sourceConn, $sourceCompanyId);
        $inserted = 0;
        $skipped = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $row = (array) $row;
            $oldId = (int) $row['id'];
            $payload = $this->stripUnsupportedColumns('users', $row);
            unset($payload['id']);
            $payload['company_id'] = $remoteCompanyId;

            $existingId = $this->findExistingRowIdOnTarget($targetConn, 'users', $payload);
            if ($existingId !== null) {
                $idMaps['users'][$oldId] = $existingId;
                $existing = DB::connection($targetConn)->table('users')->where('id', $existingId)->first();
                $existingCompanyId = $existing->company_id ?? null;
                if ($existingCompanyId === null || (int) $existingCompanyId === $remoteCompanyId) {
                    $userUpdate = ['company_id' => $remoteCompanyId];
                    if (array_key_exists('updated_at', $payload) && $payload['updated_at'] !== null && $payload['updated_at'] !== '') {
                        $userUpdate['updated_at'] = $payload['updated_at'];
                    } else {
                        $userUpdate['updated_at'] = now();
                    }
                    DB::connection($targetConn)->table('users')
                        ->where('id', $existingId)
                        ->update($userUpdate);
                    $this->backfillTimestampsIfMissingOnTarget($targetConn, 'users', $existingId, $payload);
                    $updated++;
                } else {
                    $skipped++;
                }

                continue;
            }

            try {
                $newId = (int) DB::connection($targetConn)->table('users')->insertGetId($payload);
                $idMaps['users'][$oldId] = $newId;
                $inserted++;
            } catch (UniqueConstraintViolationException) {
                if ($this->reconcileExistingUserForTenant($targetConn, $payload, $oldId, $remoteCompanyId, $idMaps)) {
                    $updated++;
                } else {
                    $skipped++;
                }
            } catch (Throwable $e) {
                if ($this->isDuplicateKeyException($e)) {
                    if ($this->reconcileExistingUserForTenant($targetConn, $payload, $oldId, $remoteCompanyId, $idMaps)) {
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    throw $e;
                }
            }
        }

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'updated' => $updated,
            'message' => sprintf(
                'Gebruikers (naar doel-database): %d gevonden, %d nieuw, %d gekoppeld/bijgewerkt, %d overgeslagen.',
                $rows->count(),
                $inserted,
                $updated,
                $skipped
            ),
        ];
    }

    /**
     * Bestaat er al een rij op doel met dezelfde natuurlijke sleutel? (voorkomt dubbele inserts)
     */
    private function findExistingRowIdOnTarget(string $targetConn, string $table, array $payload): ?int
    {
        if (! Schema::connection($targetConn)->hasTable($table)) {
            return null;
        }

        if ($table === 'email_templates') {
            return $this->findExistingEmailTemplateId($targetConn, $payload);
        }

        if ($table === 'model_has_roles') {
            return $this->findExistingModelHasRolesRow($targetConn, $payload) ? -1 : null;
        }

        $configured = config("tenant_sync.existing_row_keys.{$table}");
        if (! is_array($configured) || $configured === []) {
            return null;
        }

        if (! Schema::connection($targetConn)->hasColumn($table, 'id')) {
            return null;
        }

        $q = DB::connection($targetConn)->table($table);
        foreach ($configured as $column) {
            if (! is_string($column) || $column === '' || ! Schema::connection($targetConn)->hasColumn($table, $column)) {
                continue;
            }
            if (! array_key_exists($column, $payload)) {
                return null;
            }
            if ($payload[$column] === null) {
                $q->whereNull($column);
            } else {
                $q->where($column, $payload[$column]);
            }
        }

        $found = $q->value('id');

        return $found !== null ? (int) $found : null;
    }

    private function findExistingEmailTemplateId(string $targetConn, array $payload): ?int
    {
        if (! isset($payload['company_id'])) {
            return null;
        }

        $q = DB::connection($targetConn)->table('email_templates')->where('company_id', $payload['company_id']);
        if (! empty($payload['type'])) {
            $q->where('type', $payload['type']);
        } elseif (isset($payload['name']) && $payload['name'] !== '') {
            $q->where('name', $payload['name']);
        } else {
            return null;
        }

        $found = $q->value('id');

        return $found !== null ? (int) $found : null;
    }

    private function findExistingModelHasRolesRow(string $targetConn, array $payload): bool
    {
        if (! Schema::connection($targetConn)->hasTable('model_has_roles')) {
            return false;
        }

        $roleKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $morphKey = config('permission.column_names.model_morph_key', 'model_id');
        if (! isset($payload[$roleKey], $payload[$morphKey], $payload['model_type'], $payload['company_id'])) {
            return false;
        }

        return DB::connection($targetConn)->table('model_has_roles')
            ->where('company_id', $payload['company_id'])
            ->where($roleKey, $payload[$roleKey])
            ->where($morphKey, $payload[$morphKey])
            ->where('model_type', $payload['model_type'])
            ->exists();
    }

    private function connectionsPointToSameDatabase(string $sourceConn, string $targetConn): bool
    {
        $normalize = static function (array $cfg): array {
            $host = strtolower((string) ($cfg['host'] ?? ''));
            if (in_array($host, ['db', '127.0.0.1', 'localhost', '::1'], true)) {
                $host = 'local-postgres';
            }

            return [
                'host' => $host,
                'port' => (string) ($cfg['port'] ?? '5432'),
                'database' => (string) ($cfg['database'] ?? ''),
                'username' => (string) ($cfg['username'] ?? ''),
            ];
        };

        $source = $normalize((array) config("database.connections.{$sourceConn}", []));
        $target = $normalize((array) config("database.connections.{$targetConn}", []));

        return $source === $target;
    }

    /**
     * @return list<string>
     */
    private function userModelTypes(): array
    {
        return array_values(array_unique(array_filter([
            (new User)->getMorphClass(),
            User::class,
            'App\\Models\\User',
        ])));
    }

    /**
     * @param  array<string, array<int, int>>  $idMaps
     * @return 'inserted'|'skipped'
     */
    private function insertRowOnTarget(
        string $sourceConn,
        string $targetConn,
        string $table,
        array $payload,
        ?int $oldId,
        int $remoteCompanyId,
        array &$idMaps
    ): string {
        $existingId = $this->findExistingRowIdOnTarget($targetConn, $table, $payload);
        if ($existingId !== null) {
            if ($existingId > 0 && $oldId !== null && Schema::connection($targetConn)->hasColumn($table, 'id')) {
                $idMaps[$table][$oldId] = $existingId;
                $this->backfillTimestampsIfMissingOnTarget($targetConn, $table, $existingId, $payload);
            }

            return 'skipped';
        }

        try {
            $hasId = Schema::connection($sourceConn)->hasColumn($table, 'id');
            if ($hasId) {
                unset($payload['id']);
                $newId = DB::connection($targetConn)->table($table)->insertGetId($payload);
                if ($oldId !== null) {
                    $idMaps[$table][$oldId] = (int) $newId;
                }
            } else {
                DB::connection($targetConn)->table($table)->insert($payload);
            }

            return 'inserted';
        } catch (UniqueConstraintViolationException $e) {
            return $this->handleDuplicateRowOnTarget(
                $targetConn,
                $table,
                $payload,
                $oldId,
                $remoteCompanyId,
                $idMaps,
                $e
            );
        } catch (Throwable $e) {
            if ($this->isDuplicateKeyException($e)) {
                return $this->handleDuplicateRowOnTarget(
                    $targetConn,
                    $table,
                    $payload,
                    $oldId,
                    $remoteCompanyId,
                    $idMaps,
                    $e
                );
            }

            throw $e;
        }
    }

    /**
     * @param  array<string, array<int, int>>  $idMaps
     * @return 'inserted'|'skipped'
     */
    private function handleDuplicateRowOnTarget(
        string $targetConn,
        string $table,
        array $payload,
        ?int $oldId,
        int $remoteCompanyId,
        array &$idMaps,
        Throwable $e
    ): string {
        if ($table === 'users' && isset($payload['email']) && $this->reconcileExistingUserForTenant(
            $targetConn,
            $payload,
            $oldId,
            $remoteCompanyId,
            $idMaps
        )) {
            return 'inserted';
        }

        $this->tryLearnIdFromUniqueHit($targetConn, $table, $payload, $oldId, $idMaps);

        return 'skipped';
    }

    /**
     * Bestaande gebruiker op doel koppelen aan tenant (zelfde e-mail, nog geen ander bedrijf).
     *
     * @param  array<string, array<int, int>>  $idMaps
     */
    private function reconcileExistingUserForTenant(
        string $targetConn,
        array $payload,
        ?int $oldId,
        int $remoteCompanyId,
        array &$idMaps
    ): bool {
        if ($oldId === null || ! isset($payload['email'])) {
            return false;
        }

        $existing = DB::connection($targetConn)->table('users')->where('email', $payload['email'])->first();
        if ($existing === null) {
            return false;
        }

        $existingCompanyId = $existing->company_id ?? null;
        if ($existingCompanyId !== null && (int) $existingCompanyId !== $remoteCompanyId) {
            return false;
        }

        $update = ['company_id' => $remoteCompanyId, 'updated_at' => now()];
        $allowed = array_flip(Schema::connection($targetConn)->getColumnListing('users'));
        $update = array_intersect_key($update, $allowed);
        if ($update !== []) {
            DB::connection($targetConn)->table('users')->where('id', (int) $existing->id)->update($update);
        }

        $idMaps['users'][$oldId] = (int) $existing->id;

        return true;
    }

    /**
     * @param  array<string, array<int, int>>  $idMaps
     * @return array{inserted: int, skipped: int}
     */
    private function pushPostSyncTable(
        string $sourceConn,
        string $targetConn,
        string $table,
        int $sourceCompanyId,
        int $remoteCompanyId,
        array &$idMaps
    ): array {
        if ($table !== 'role_has_permissions') {
            return ['inserted' => 0, 'skipped' => 0];
        }

        if (! Schema::connection($sourceConn)->hasTable('role_has_permissions')
            || ! Schema::connection($sourceConn)->hasTable('roles')
            || ! Schema::connection($sourceConn)->hasTable('permissions')) {
            return ['inserted' => 0, 'skipped' => 0];
        }

        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $permissionPivotKey = config('permission.column_names.permission_pivot_key') ?: 'permission_id';

        $sourceRoleIds = DB::connection($sourceConn)->table('roles')
            ->where('company_id', $sourceCompanyId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($sourceRoleIds === []) {
            return ['inserted' => 0, 'skipped' => 0];
        }

        $inserted = 0;
        $skipped = 0;
        $rows = DB::connection($sourceConn)->table('role_has_permissions')
            ->whereIn($rolePivotKey, $sourceRoleIds)
            ->get();

        foreach ($rows as $rowObj) {
            $row = (array) $rowObj;
            $oldRoleId = isset($row[$rolePivotKey]) ? (int) $row[$rolePivotKey] : 0;
            $oldPermissionId = isset($row[$permissionPivotKey]) ? (int) $row[$permissionPivotKey] : 0;
            if ($oldRoleId === 0 || $oldPermissionId === 0) {
                $skipped++;

                continue;
            }
            if (! isset($idMaps['roles'][$oldRoleId])) {
                $skipped++;

                continue;
            }
            $remotePermissionId = $this->resolveRemotePermissionId($sourceConn, $targetConn, $oldPermissionId);
            if ($remotePermissionId === null) {
                $skipped++;

                continue;
            }

            $payload = [
                $rolePivotKey => $idMaps['roles'][$oldRoleId],
                $permissionPivotKey => $remotePermissionId,
            ];

            try {
                DB::connection($targetConn)->table('role_has_permissions')->insert($payload);
                $inserted++;
            } catch (UniqueConstraintViolationException) {
                $skipped++;
            } catch (Throwable $e) {
                if ($this->isDuplicateKeyException($e)) {
                    $skipped++;
                } else {
                    throw $e;
                }
            }
        }

        return ['inserted' => $inserted, 'skipped' => $skipped];
    }

    private function resolveRemotePermissionId(string $sourceConn, string $targetConn, int $sourcePermissionId): ?int
    {
        $source = DB::connection($sourceConn)->table('permissions')->where('id', $sourcePermissionId)->first();
        if ($source === null) {
            return null;
        }

        $found = DB::connection($targetConn)->table('permissions')
            ->where('name', $source->name)
            ->where('guard_name', $source->guard_name)
            ->value('id');

        return $found !== null ? (int) $found : null;
    }
}
