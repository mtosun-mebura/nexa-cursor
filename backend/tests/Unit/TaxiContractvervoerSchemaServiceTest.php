<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Modules\NexaTaxi\Support\NexaTaxiSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiContractvervoerSchemaServiceTest extends TestCase
{
    #[Test]
    public function ensure_tables_exist_creates_contractvervoer_tables(): void
    {
        config(['database.connections.module_taxi_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection('module_taxi_test')->create('vehicles', function (Blueprint $table) {
            $table->id();
        });
        Schema::connection('module_taxi_test')->create('ride_requests', function (Blueprint $table) {
            $table->id();
        });

        app(TaxiContractvervoerSchemaService::class)->ensureTablesExist('module_taxi_test');

        foreach (array_diff(NexaTaxiSchema::CONTRACTVERVOER_TABLES, ['transport_schedule_exceptions']) as $table) {
            $this->assertTrue(
                Schema::connection('module_taxi_test')->hasTable($table),
                "Expected table {$table} to exist"
            );
        }

        $cols = Schema::connection('module_taxi_test')->getColumnListing('ride_requests');
        $this->assertContains('transport_contract_id', $cols);
        $this->assertContains('transport_occurrence_id', $cols);
        $this->assertContains('return_at', $cols);
        $this->assertContains('outbound_completed_at', $cols);
        $this->assertContains('outbound_driver_id', $cols);
        $this->assertContains('return_started_at', $cols);
    }

    #[Test]
    public function ensure_ride_request_contract_columns_adds_return_trip_columns_to_existing_table(): void
    {
        config(['database.connections.module_taxi_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection('module_taxi_test')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->dateTime('pickup_at')->nullable();
        });

        app(TaxiContractvervoerSchemaService::class)->ensureRideRequestContractColumns('module_taxi_test');

        $cols = Schema::connection('module_taxi_test')->getColumnListing('ride_requests');
        $this->assertContains('return_at', $cols);
        $this->assertContains('outbound_completed_at', $cols);
        $this->assertContains('outbound_driver_id', $cols);
        $this->assertContains('return_started_at', $cols);
    }
}
