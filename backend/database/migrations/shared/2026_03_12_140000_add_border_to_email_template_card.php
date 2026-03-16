<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add border to the card table in email template HTML (informatieaanvraag and others)
     * so the card is clearly visible in dark-themed mail clients.
     */
    public function up(): void
    {
        $old = 'width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
        $new = 'width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';

        DB::table('email_templates')
            ->whereNotNull('html_content')
            ->where('html_content', 'like', '%' . $old . '%')
            ->where('html_content', 'not like', '%border: 1px solid #e5e7eb%')
            ->update([
                'html_content' => DB::raw("REPLACE(html_content, '" . str_replace("'", "''", $old) . "', '" . str_replace("'", "''", $new) . "')"),
            ]);
    }

    public function down(): void
    {
        $new = 'width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
        $old = 'width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';

        DB::table('email_templates')
            ->whereNotNull('html_content')
            ->where('html_content', 'like', '%' . $new . '%')
            ->update([
                'html_content' => DB::raw("REPLACE(html_content, '" . str_replace("'", "''", $new) . "', '" . str_replace("'", "''", $old) . "')"),
            ]);
    }
};
