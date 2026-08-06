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
        $this->assertStringContainsString('Referentie: rit #99', $composed['template_params'][2]);
        $this->assertStringContainsString('Ophalen: Dam 1, Amsterdam', $composed['template_params'][2]);
        $this->assertStringNotContainsString('Telefoon:', $composed['template_params'][2]);
        $this->assertSame('Taxi Royaal', $composed['template_params'][3]);
        $this->assertStringContainsString('Beste Sara Jansen', $composed['preview']);
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
            ['driver_name' => 'Piet'],
            1
        );

        $this->assertSame('Sara Jansen', $composed['template_params'][0]);
        $this->assertSame('Taxi Royaal', $composed['template_params'][1]);
        $this->assertSame('Rit gestart — chauffeur onderweg', $composed['template_params'][2]);
        $this->assertStringContainsString('Chauffeur: Piet', $composed['template_params'][3]);
        $this->assertStringContainsString('Ophalen: Dam 1, Amsterdam', $composed['template_params'][3]);
        $this->assertStringContainsString('statusupdate over uw taxirit bij Taxi Royaal', $composed['preview']);
        $this->assertStringContainsString('Huidige status: Rit gestart — chauffeur onderweg', $composed['preview']);
    }

    #[Test]
    public function sample_preview_respects_selected_fields(): void
    {
        $whatsapp = Mockery::mock(WhatsAppBusinessService::class);
        $env = Mockery::mock(EnvService::class);
        $env->shouldReceive('get')->andReturn('');

        $composer = new WhatsAppBookingMessageComposer(
            new TaxiBookingSummaryText,
            $whatsapp,
            $env
        );

        $sample = $composer->sampleCustomerPreview(['pickup_address', 'price']);

        $this->assertStringContainsString('Ophalen:', $sample['details']);
        $this->assertStringContainsString('Prijsindicatie:', $sample['details']);
        $this->assertStringNotContainsString('Telefoon:', $sample['details']);
        $this->assertCount(4, $sample['params']);
    }
}
