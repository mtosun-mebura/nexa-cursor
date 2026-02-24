<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_configurations', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable()->after('id')->constrained('job_configuration_types')->nullOnDelete();
        });
        
        // Migrate existing type strings to type_id
        // This will match existing type strings to the new types table
        $types = DB::table('job_configuration_types')->pluck('id', 'name');
        
        foreach ($types as $typeName => $typeId) {
            DB::table('job_configurations')
                ->where('type', $typeName)
                ->update(['type_id' => $typeId]);
        }
    }

    public function down(): void
    {
        Schema::table('job_configurations', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropColumn('type_id');
        });
    }
};
