<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'ride_alert_tone')) {
            return;
        }

        // Oude standaarden (classic / alert) → Bel (chime).
        DB::table('users')
            ->where(function ($q) {
                $q->whereIn('ride_alert_tone', ['alert', 'classic'])
                    ->orWhereNull('ride_alert_tone')
                    ->orWhere('ride_alert_tone', '');
            })
            ->update(['ride_alert_tone' => 'chime']);
    }

    public function down(): void
    {
        // Niet terugzetten: gekozen Bel mag niet stilzwijgend alert worden.
    }
};
