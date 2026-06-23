<?php

namespace Tests\Unit;

use App\DTO\AiChat\AiChatRequestContext;
use App\Enums\AiChat\AiChatChannel;
use App\Services\AiChat\AiChatMapsRouteService;
use App\Services\AiChat\AiChatQuoteAnswerFormatter;
use App\Services\AiChat\AiChatQuoteConversationService;
use App\Services\AiChat\AiChatRouteQuoteParser;
use App\Services\NexaTaxiBookingPricingService;
use App\Services\WebsiteBuilderService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AiChatQuoteConversationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_starts_quote_flow_and_asks_for_missing_pickup(): void
    {
        $service = $this->makeService();
        $context = new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
            sessionId: 'quote-test-1',
        );

        $reply = $service->handle($context, 'Wat kost een rit naar Schiphol?');

        $this->assertStringContainsString('Schiphol', $reply->reply);
        $this->assertStringContainsString('Vanaf welk adres', $reply->reply);
        $this->assertTrue($service->hasActiveSession($context));
        $this->assertSame('address', $reply->input['type'] ?? null);
        $this->assertSame('pickup', $reply->input['step'] ?? null);
    }

    public function test_travel_intent_starts_quote_flow_with_destination(): void
    {
        $service = $this->makeService();
        $context = new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
            sessionId: 'quote-test-travel',
        );

        $reply = $service->handle($context, 'Ik wil naar Schiphol');

        $this->assertStringContainsString('Schiphol', $reply->reply);
        $this->assertStringContainsString('Vanaf welk adres', $reply->reply);
        $this->assertTrue($service->hasActiveSession($context));
        $this->assertSame('address', $reply->input['type'] ?? null);
        $this->assertSame('pickup', $reply->input['step'] ?? null);
    }

    public function test_full_route_question_asks_to_confirm_pickup_before_passengers(): void
    {
        $service = $this->makeService();
        $context = new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
            sessionId: 'quote-test-2',
        );

        $first = $service->handle($context, 'Wat kost een rit van Enschede naar Düsseldorf Airport?');

        $this->assertStringContainsString('Bevestig je ophaaladres', $first->reply);
        $this->assertSame('address', $first->input['type'] ?? null);
        $this->assertSame('pickup', $first->input['step'] ?? null);
        $this->assertSame('Enschede', $first->input['prefill'] ?? null);

        $second = $service->handle($context, 'Enscheded, Netherlands');
        $this->assertSame('address', $second->input['type'] ?? null);
        $this->assertSame('dropoff', $second->input['step'] ?? null);
    }

    public function test_booking_request_starts_booking_flow(): void
    {
        $service = $this->makeService();
        $context = new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
            sessionId: 'quote-test-booking',
        );

        $reply = $service->handle($context, 'Boek een rit naar Schiphol');

        $this->assertStringContainsString('boeken', mb_strtolower($reply->reply));
        $this->assertStringContainsString('Schiphol', $reply->reply);
        $this->assertSame('address', $reply->input['type'] ?? null);
        $this->assertSame('pickup', $reply->input['step'] ?? null);
        $this->assertNull($reply->input['prefill'] ?? null);
    }

    public function test_booking_flow_requires_email_address(): void
    {
        $formatter = new AiChatQuoteAnswerFormatter();
        $session = ['contact_email_required' => true];

        $this->assertSame('Wat is je e-mailadres?', $formatter->questionForStep('email', $session));
        $this->assertTrue($formatter->inputSpecForStep('email', $session)['required'] ?? false);
        $this->assertStringNotContainsString('optioneel', $formatter->questionForStep('email', $session));
    }

    public function test_booking_flow_asks_for_contact_after_remarks(): void
    {
        $service = $this->makeService();
        $context = new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
            sessionId: 'quote-test-contact',
        );

        $service->handle($context, 'Boek een rit naar Schiphol');
        $service->handle($context, 'Stationsplein 1, Enschede');
        $service->handle($context, 'Luchthaven Schiphol');
        $service->handle($context, '2');
        $service->handle($context, '0', null, ['baggage' => [], 'special_baggage' => []]);
        $service->handle($context, now()->addDay()->format('Y-m-d H:i'));
        $reply = $service->handle($context, 'geen');

        $this->assertStringContainsString('voornaam', mb_strtolower($reply->reply));
        $this->assertSame('text', $reply->input['type'] ?? null);
        $this->assertSame('first_name', $reply->input['step'] ?? null);
    }

    public function test_quote_flow_does_not_ask_for_contact(): void
    {
        $service = $this->makeService();
        $context = new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
            sessionId: 'quote-test-no-contact',
        );

        $service->handle($context, 'Wat kost een rit naar Schiphol?');
        $service->handle($context, 'Stationsplein 1, Enschede');
        $service->handle($context, 'Luchthaven Schiphol');
        $service->handle($context, '2');
        $service->handle($context, '0', null, ['baggage' => [], 'special_baggage' => []]);
        $service->handle($context, now()->addDay()->format('Y-m-d H:i'));
        $reply = $service->handle($context, 'geen');

        $this->assertNotSame('first_name', $reply->input['step'] ?? null);
    }

    private function makeService(): AiChatQuoteConversationService
    {
        $pricing = $this->createMock(NexaTaxiBookingPricingService::class);
        $pricing->method('getDefaultSectionConfig')->willReturn([]);
        $pricing->method('mergeSectionConfig')->willReturn([]);

        $websiteBuilder = $this->createMock(WebsiteBuilderService::class);
        $websiteBuilder->method('resolveBookingModuleSection')->willReturn([
            'config' => [],
            'tenant_company_id' => 1,
            'page' => null,
        ]);

        return new AiChatQuoteConversationService(
            new AiChatRouteQuoteParser(),
            new AiChatMapsRouteService(),
            new AiChatQuoteAnswerFormatter(),
            $pricing,
            $websiteBuilder,
        );
    }
}
