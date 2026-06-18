<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\TransportScheduleException;
use App\Modules\NexaTaxi\Services\TransportScheduleExceptionService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransportScheduleExceptionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection('module_taxi')->create('transport_schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('transport_contract_id')->nullable()->index();
            $table->date('exception_date');
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function test_company_wide_exception_blocks_all_contracts(): void
    {
        TransportScheduleException::on('module_taxi')->create([
            'company_id' => 1,
            'transport_contract_id' => null,
            'exception_date' => '2026-12-25',
            'name' => 'Kerst',
            'active' => true,
        ]);

        $service = app(TransportScheduleExceptionService::class);
        $date = Carbon::create(2026, 12, 25, 8, 0, 0, 'Europe/Amsterdam');

        $this->assertTrue($service->isExceptionDate('module_taxi', 1, $date, 42));
    }

    public function test_contract_specific_exception_only_blocks_that_contract(): void
    {
        TransportScheduleException::on('module_taxi')->create([
            'company_id' => 1,
            'transport_contract_id' => 5,
            'exception_date' => '2026-05-14',
            'name' => 'Hemelvaart',
            'active' => true,
        ]);

        $service = app(TransportScheduleExceptionService::class);
        $date = Carbon::create(2026, 5, 14, 8, 0, 0, 'Europe/Amsterdam');

        $this->assertTrue($service->isExceptionDate('module_taxi', 1, $date, 5));
        $this->assertFalse($service->isExceptionDate('module_taxi', 1, $date, 6));
    }

    public function test_inactive_exception_is_ignored(): void
    {
        TransportScheduleException::on('module_taxi')->create([
            'company_id' => 1,
            'transport_contract_id' => null,
            'exception_date' => '2026-12-25',
            'name' => 'Kerst',
            'active' => false,
        ]);

        $service = app(TransportScheduleExceptionService::class);
        $date = Carbon::create(2026, 12, 25, 8, 0, 0, 'Europe/Amsterdam');

        $this->assertFalse($service->isExceptionDate('module_taxi', 1, $date, null));
    }
}
