<?php

return [
    'disk' => 'local',
    'storage_directory' => 'database-backups',
    'default_retention_days' => 30,
    'default_frequency' => 'daily',
    'default_time' => '03:00',
    'display_timezone' => env('DATABASE_BACKUP_TIMEZONE', 'Europe/Amsterdam'),
    'pg_dump_binary' => env('PG_DUMP_BINARY', 'pg_dump'),
    'pg_restore_binary' => env('PG_RESTORE_BINARY', 'pg_restore'),
    'psql_binary' => env('PSQL_BINARY', 'psql'),
];
