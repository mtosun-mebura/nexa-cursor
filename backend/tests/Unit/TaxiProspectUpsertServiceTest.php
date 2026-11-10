<?php

namespace Tests\Unit;

use App\Models\TaxiProspect;
use App\Models\TaxiProspectOptOut;
use App\Models\TaxiProspectSource;
use App\Services\TaxiPartnerFinder\TaxiProspectCandidate;
use App\Services\TaxiPartnerFinder\TaxiProspectUpsertService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiProspectUpsertServiceTest extends TestCase
{
    #[Test]
    public function it_creates_and_deduplicates_on_source_reference(): void
    {
        $upsert = app(TaxiProspectUpsertService::class);

        $candidate = new TaxiProspectCandidate(
            companyName: 'Taxi Dedup B.V.',
            sourceType: TaxiProspectSource::TYPE_KNV,
            externalReference: 'btn-9999',
            postalCode: '7511AA',
            city: 'Enschede',
            province: 'Overijssel',
            email: 'info@taxi-dedup.test',
            phone: '0531234567',
            knvMemberStatus: TaxiProspect::KNV_MEMBER,
            confidenceScore: 70,
        );

        $first = $upsert->upsert($candidate);
        $this->assertSame('created', $first['action']);
        $this->assertSame(1, TaxiProspect::query()->count());

        $second = $upsert->upsert(new TaxiProspectCandidate(
            companyName: 'Taxi Dedup B.V.',
            sourceType: TaxiProspectSource::TYPE_KNV,
            externalReference: 'btn-9999',
            postalCode: '7511AA',
            city: 'Enschede',
            phone: '0537654321',
            knvMemberStatus: TaxiProspect::KNV_MEMBER,
        ));
        $this->assertSame('updated', $second['action']);
        $this->assertSame(1, TaxiProspect::query()->count());
        $this->assertSame('info@taxi-dedup.test', $second['prospect']->email);
        $this->assertSame('0537654321', $second['prospect']->phone);
    }

    #[Test]
    public function it_skips_opted_out_emails(): void
    {
        TaxiProspectOptOut::query()->create([
            'email' => 'stop@taxi.test',
            'opted_out_at' => now(),
            'reason' => 'test',
        ]);

        $result = app(TaxiProspectUpsertService::class)->upsert(new TaxiProspectCandidate(
            companyName: 'Opt Out Taxi',
            sourceType: TaxiProspectSource::TYPE_KNV_CSV,
            externalReference: 'csv-1',
            email: 'stop@taxi.test',
        ));

        $this->assertSame('skipped_opt_out', $result['action']);
        $this->assertSame(0, TaxiProspect::query()->count());
    }

    #[Test]
    public function it_rejects_invalid_phone_numbers(): void
    {
        $result = app(TaxiProspectUpsertService::class)->upsert(new TaxiProspectCandidate(
            companyName: 'Phone Taxi',
            sourceType: TaxiProspectSource::TYPE_KNV,
            externalReference: 'btn-phone',
            phone: '123',
            email: 'ok@phone-taxi.test',
        ));

        $this->assertSame('created', $result['action']);
        $this->assertNull($result['prospect']->phone);
        $this->assertSame('ok@phone-taxi.test', $result['prospect']->email);
    }
}
