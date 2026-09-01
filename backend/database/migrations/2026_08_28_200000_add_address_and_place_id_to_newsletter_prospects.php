<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('newsletter_prospects')) {
            return;
        }

        Schema::table('newsletter_prospects', function (Blueprint $table) {
            if (! Schema::hasColumn('newsletter_prospects', 'address')) {
                $table->string('address')->nullable()->after('website');
            }
            if (! Schema::hasColumn('newsletter_prospects', 'google_place_id')) {
                $table->string('google_place_id', 128)->nullable()->unique()->after('source_url');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('newsletter_prospects')) {
            return;
        }

        Schema::table('newsletter_prospects', function (Blueprint $table) {
            if (Schema::hasColumn('newsletter_prospects', 'google_place_id')) {
                $table->dropUnique(['google_place_id']);
                $table->dropColumn('google_place_id');
            }
            if (Schema::hasColumn('newsletter_prospects', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};
