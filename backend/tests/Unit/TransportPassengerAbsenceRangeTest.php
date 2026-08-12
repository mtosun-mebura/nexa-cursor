<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Services\TransportPassengerAbsenceService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransportPassengerAbsenceRangeTest extends TestCase
{
    public function test_create_range_rejects_to_before_from(): void
    {
        Carbon::setTestNow('2026-08-12 10:00:00');

        $service = app(TransportPassengerAbsenceService::class);
        $passenger = new \App\Modules\NexaTaxi\Models\TransportPassenger([
            'company_id' => 1,
            'first_name' => 'Test',
            'last_name' => 'Leerling',
        ]);
        $passenger->id = 1;

        $actor = new \App\Models\User(['email' => 'a@b.c']);
        $actor->id = 1;

        app(\App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService::class)
            ->ensureContractPortalTables();

        try {
            $service->createRange(
                config('database.default'),
                $actor,
                $passenger,
                Carbon::parse('2026-08-20'),
                Carbon::parse('2026-08-19'),
                null
            );
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('date_to', $e->errors());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_inclusive_day_count_math(): void
    {
        $from = Carbon::parse('2026-08-12');
        $to = Carbon::parse('2026-08-14');
        $this->assertSame(3, (int) $from->diffInDays($to) + 1);
        $this->assertSame(1, (int) $from->diffInDays($from) + 1);
    }
}
