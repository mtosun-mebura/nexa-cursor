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
            // Rename body to html_content
            $table->renameColumn('body', 'html_content');
            
            // Add new columns
            $table->string('type', 50)->nullable()->after('subject');
            $table->text('text_content')->nullable()->after('html_content');
            $table->text('description')->nullable()->after('text_content');
            $table->boolean('is_active')->default(true)->after('description');
            
            // Drop language column as it's not needed
            $table->dropColumn('language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            // Revert changes
            $table->renameColumn('html_content', 'body');
            $table->string('language', 20)->default('Nederlands')->after('body');
            $table->dropColumn(['type', 'text_content', 'description', 'is_active']);
        });
    }
};
