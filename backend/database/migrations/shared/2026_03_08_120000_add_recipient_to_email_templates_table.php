<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('recipient_type', 20)->nullable()->after('company_id')->comment('user or email');
            $table->unsignedBigInteger('recipient_user_id')->nullable()->after('recipient_type');
            $table->string('recipient_email')->nullable()->after('recipient_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn(['recipient_type', 'recipient_user_id', 'recipient_email']);
        });
    }
};
