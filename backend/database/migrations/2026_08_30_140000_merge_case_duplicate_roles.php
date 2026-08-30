<?php

use App\Support\DuplicateRoleMerger;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DuplicateRoleMerger::mergeCaseDuplicates();
    }

    public function down(): void
    {
        // Samenvoegen van dubbele rollen is niet omkeerbaar.
    }
};
