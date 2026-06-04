<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Support\NexaTaxiSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NexaTaxiSchemaTest extends TestCase
{
    #[Test]
    public function core_tables_exist_returns_false_when_vehicles_missing(): void
    {
        config(['database.connections.module_taxi_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection('module_taxi_test')->create('ride_requests', function (Blueprint $table) {
            $table->id();
        });
        Schema::connection('module_taxi_test')->create('default_rates', function (Blueprint $table) {
            $table->id();
        });

        $this->assertFalse(NexaTaxiSchema::coreTablesExist('module_taxi_test'));
    }

    #[Test]
    public function core_tables_exist_returns_true_when_all_present(): void
    {
        config(['database.connections.module_taxi_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        foreach (NexaTaxiSchema::CORE_TABLES as $table) {
            Schema::connection('module_taxi_test')->create($table, function (Blueprint $table) {
                $table->id();
            });
        }

        $this->assertTrue(NexaTaxiSchema::coreTablesExist('module_taxi_test'));
    }
}
