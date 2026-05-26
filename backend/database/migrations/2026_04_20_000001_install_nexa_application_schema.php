<?php

declare(strict_types=1);

use App\Database\Pre2026Baseline;
use Illuminate\Database\Migrations\Migration;

/**
 * Voert de geconsolideerde pre-2026-baseline uit (één rij in `migrations`).
 *
 * Volgorde: zelfde als vroeger — alle stappen globaal gesorteerd op bestandsnaam (timestamp-prefix).
 *
 * - PostgreSQL/MySQL: core + shared + taxiroyaal (Nexa Taxi DDL) + skillmatching
 * - SQLite (PHPUnit): alleen core + shared
 *
 * @see App\Database\Pre2026Baseline
 */
return new class extends Migration
{
    /**
     * Eén grote transactie om honderd+ DDL-stappen heen geeft op PostgreSQL een geaborteerde transactie
     * bij de eerste fout; latere migraties rapporteren dan misleidend 25P02. Sub-migraties auto-committen.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        Pre2026Baseline::runFull();
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'De bundelmigratie install_nexa_application_schema kan niet worden teruggedraaid. Gebruik migrate:fresh op een niet-productie database.'
        );
    }
};
