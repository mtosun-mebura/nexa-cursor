<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiBookingSummaryText;
use App\Services\EnvService;
use App\Services\WhatsAppBookingMessageComposer;
use App\Services\WhatsAppBusinessService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsAppBookingMessageComposerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function customer_compose_fills_four_template_params(): void
    {
        $ride = new RideRequest([
            'customer_name' => 'Sara Jansen',
            'customer_phone' => '+31612345678',
            'pickup_address' => 'Dam 1, Amsterdam',
            'dropoff_address' => 'CS Utrecht',
            'passengers' => 2,
        ]);
        $ride->id = 99;

        $whatsapp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsapp->shouldReceive('tenantDisplayName')->andReturn('Taxi Royaal');

        $env = Mockery::mock(EnvService::class);
        $env->shouldReceive('get')
            ->with(WhatsAppBookingMessageComposer::DETAIL_FIELDS_KEY, '')
            ->andReturn(json_encode(['reference', 'pickup_address', 'dropoff_address']));

        $composer = new WhatsAppBookingMessageComposer(
            new TaxiBookingSummaryText,
            $whatsapp,
            $env
        );

        $composed = $composer->compose($ride, [], 1, 'customer');

        $this->assertSame('Sara Jansen', $composed['template_params'][0]);
        $this->assertSame('Taxi Royaal', $composed['template_params'][1]);
        $this->assertSame('rit #99', $composed['template_params'][2]);
        $this->assertSame('+31612345678', $composed['template_params'][3]);
        $this->assertSame('Dam 1, Amsterdam', $composed['template_params'][4]);
        $this->assertSame('CS Utrecht', $composed['template_params'][5]);
        $this->assertSame('2', $composed['template_params'][7]);
        $this->assertSame('Taxi Royaal', $composed['template_params'][10]);
        $this->assertCount(11, $composed['template_params']);
        $this->assertStringContainsString('Beste Sara Jansen', $composed['preview']);
        $this->assertStringContainsString('Aanbieding/voertuig:', $composed['preview']);
        $this->assertStringNotContainsString(' · ', $composed['preview']);
    }

    #[Test]
    public function status_compose_uses_universal_status_label_and_details(): void
    {
        $ride = new RideRequest([
            'customer_name' => 'Sara Jansen',
            'pickup_address' => 'Dam 1, Amsterdam',
            'dropoff_address' => 'CS Utrecht',
        ]);
        $ride->id = 55;

        $whatsapp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsapp->shouldReceive('tenantDisplayName')->andReturn('Taxi Royaal');

        $env = Mockery::mock(EnvService::class);
        $env->shouldReceive('get')
            ->with(WhatsAppBookingMessageComposer::DETAIL_FIELDS_KEY, '')
            ->andReturn(json_encode(['pickup_address', 'dropoff_address']));

        $composer = new WhatsAppBookingMessageComposer(
            new TaxiBookingSummaryText,
            $whatsapp,
            $env
        );

        $composed = $composer->composeStatus(
            $ride,
            WhatsAppBookingMessageComposer::EVENT_STARTED,
            ['driver_name' => 'Piet', 'license_plate' => 'AB-123-C'],
            1
        );

        $this->assertSame('Sara Jansen', $composed['template_params'][0]);
        $this->assertSame('Taxi Royaal', $composed['template_params'][1]);
        $this->assertSame('Rit gestart — chauffeur onderweg', $composed['template_params'][2]);
        $this->assertSame('—', $composed['template_params'][3]);
        $this->assertSame('Piet', $composed['template_params'][4]);
        $this->assertSame('Dam 1, Amsterdam', $composed['template_params'][6]);
        $this->assertSame('AB-123-C', $composed['template_params'][7]);
        $this->assertCount(8, $composed['template_params']);
        $this->assertStringContainsString('reactie op uw taxirit bij Taxi Royaal', $composed['preview']);
        $this->assertStringContainsString('Status: Rit gestart — chauffeur onderweg.', $composed['preview']);
        $this->assertStringContainsString('Chauffeur: Piet', $composed['preview']);
        $this->assertStringContainsString('Kenteken: AB-123-C', $composed['preview']);
        $this->assertStringContainsString('Ophaaladres: Dam 1, Amsterdam', $composed['preview']);
    }

    #[Test]
    public function status_meta_body_places_license_plate_under_driver(): void
    {
        $body = WhatsAppBookingMessageComposer::META_BODY_STATUS;
        $chauffeurPos = strpos($body, 'Chauffeur: {{5}}');
        $kentekenPos = strpos($body, 'Kenteken: {{8}}');
        $ophalenPos = strpos($body, 'Ophaalmoment: {{6}}');

        $this->assertNotFalse($chauffeurPos);
        $this->assertNotFalse($kentekenPos);
        $this->assertNotFalse($ophalenPos);
        $this->assertTrue($chauffeurPos < $kentekenPos);
        $this->assertTrue($kentekenPos < $ophalenPos);
    }

    #[Test]
    public function sample_preview_uses_fixed_customer_template_fields(): void
    {
        $whatsapp = Mockery::mock(WhatsAppBusinessService::class);
        $env = Mockery::mock(EnvService::class);
        $env->shouldReceive('get')->andReturn('');

        $composer = new WhatsAppBookingMessageComposer(
            new TaxiBookingSummaryText,
            $whatsapp,
            $env
        );

        $sample = $composer->sampleCustomerPreview();

        $this->assertCount(11, $sample['params']);
        $this->assertStringContainsString('Aanbieding/voertuig:', $sample['details']);
        $this->assertStringContainsString('Prijsindicatie:', $sample['details']);
        $this->assertStringContainsString("Referentie: rit #1042\nTelefoon:", $sample['details']);
        $this->assertStringNotContainsString(' · ', $sample['preview']);
    }

    #[Test]
    public function default_status_events_exclude_completed(): void
    {
        $events = WhatsAppBookingMessageComposer::defaultStatusEvents();

        $this->assertContains(WhatsAppBookingMessageComposer::EVENT_ACCEPTED, $events);
        $this->assertContains(WhatsAppBookingMessageComposer::EVENT_STARTED, $events);
        $this->assertContains(WhatsAppBookingMessageComposer::EVENT_DECLINED, $events);
        $this->assertContains(WhatsAppBookingMessageComposer::EVENT_CANCELLED, $events);
        $this->assertContains(WhatsAppBookingMessageComposer::EVENT_REDISPATCHED, $events);
        $this->assertNotContains(WhatsAppBookingMessageComposer::EVENT_COMPLETED, $events);
    }

    #[Test]
    public function pickup_proposal_sample_includes_license_plate_under_driver(): void
    {
        $whatsapp = Mockery::mock(WhatsAppBusinessService::class);
        $env = Mockery::mock(EnvService::class);
        $env->shouldReceive('get')->andReturn('');

        $composer = new WhatsAppBookingMessageComposer(
            new TaxiBookingSummaryText,
            $whatsapp,
            $env
        );

        $sample = $composer->samplePickupProposalPreview();
        $body = WhatsAppBookingMessageComposer::META_BODY_PICKUP_PROPOSAL;

        $this->assertCount(9, $sample['params']);
        $this->assertSame('AB-123-L', $sample['params'][8]);
        $this->assertStringContainsString('Chauffeur: Piet Chauffeur', $sample['preview']);
        $this->assertStringContainsString('Kenteken: AB-123-L', $sample['preview']);
        $this->assertTrue(strpos($body, 'Chauffeur: {{8}}') < strpos($body, 'Kenteken: {{9}}'));
    }
}
