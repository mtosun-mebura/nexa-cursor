<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Database\UniqueConstraintViolationException;
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
     *     excluded_tables: list<string>,
     *     driver: string
     * }
     */
    public function describeSyncScope(): array
    {
        $connection = (string) config('database.default');
        $driver = Schema::connection($connection)->getConnection()->getDriverName();

        return [
            'company_row' => 'companies (één rij per tenant; op doel hergebruikt op slug of nieuw id)',
            'tables_with_company_id' => $this->discoverCompanyScopedTables($connection),
            'excluded_tables' => array_values(config('tenant_sync.excluded_tables', [])),
            'driver' => $driver,
        ];
    }

    /**
     * @return array{remote_company_id: int, inserted: int, skipped: int, tables: list<string>, messages: list<string>}
     */
    public function pushFullTenant(int $sourceCompanyId): array
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

            $fkEdges = $this->discoverForeignKeysToParentId($sourceConn, $tables);
            $orderedTables = $this->orderTablesForInsert($tables, $fkEdges);

            $idMaps = [];
            $remoteCompanyId = $this->resolveOrCreateRemoteCompany($targetConn, $company, $messages);

            foreach ($orderedTables as $table) {
                if ($table === 'companies') {
                    continue;
                }
                $this->assertTableHasColumn($sourceConn, $table, 'company_id');

                $rows = DB::connection($sourceConn)->table($table)->where('company_id', $sourceCompanyId)->get();
                foreach ($rows as $rowObj) {
                    $row = (array) $rowObj;
                    $oldId = isset($row['id']) ? (int) $row['id'] : null;

                    $payload = $this->prepareInsertPayload($table, $row, $remoteCompanyId, $idMaps, $fkEdges);

                    if ($payload === null) {
                        $skipped++;

                        continue;
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
                        $inserted++;
                    } catch (UniqueConstraintViolationException) {
                        $skipped++;
                        $this->tryLearnIdFromUniqueHit($targetConn, $table, $payload, $oldId, $idMaps);
                    } catch (Throwable $e) {
                        if ($this->isDuplicateKeyException($e)) {
                            $skipped++;
                            $this->tryLearnIdFromUniqueHit($targetConn, $table, $payload, $oldId, $idMaps);
                        } else {
                            throw $e;
                        }
                    }
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

        return $this->stripUnsupportedColumns($table, $row);
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
        } elseif ($table === 'company_domains' && isset($payload['host'])) {
            $q->where('host', $payload['host']);
        } elseif ($table === 'company_module' && isset($payload['module_id'])) {
            $q->where('module_id', $payload['module_id']);
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

    private function resolveOrCreateRemoteCompany(string $targetConn, Company $source, array &$messages): int
    {
        $attrs = $source->getAttributes();
        unset($attrs['id'], $attrs['created_at'], $attrs['updated_at']);
        $slug = $attrs['slug'] ?? null;

        if (is_string($slug) && $slug !== '') {
            $existing = DB::connection($targetConn)->table('companies')->where('slug', $slug)->value('id');
            if ($existing !== null) {
                $messages[] = 'Bedrijf met dezelfde slug bestond al op doel; bestaande company_id '.$existing.' wordt aangevuld (geen tweede company aangemaakt).';

                return (int) $existing;
            }
        }

        $payload = $this->stripUnsupportedColumns('companies', $attrs);
        unset($payload['id']);

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
                $parent = (string) $r->parent_table;
                if (isset($set[$child], $set[$parent])) {
                    $edges[] = [
                        'child' => $child,
                        'child_column' => (string) $r->child_column,
                        'parent' => $parent,
                    ];
                }
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
                $parent = (string) $r->parent_table;
                if (isset($set[$child], $set[$parent])) {
                    $edges[] = [
                        'child' => $child,
                        'child_column' => (string) $r->child_column,
                        'parent' => $parent,
                    ];
                }
            }
        }

        return $edges;
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
}
