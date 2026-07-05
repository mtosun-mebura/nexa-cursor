<?php

namespace App\Services\AiChat;

use App\DTO\AiChat\AiChatIntentResult;
use App\DTO\AiChat\AiChatMessageResult;
use App\DTO\AiChat\AiChatRequestContext;
use App\Enums\AiChat\AiChatIntent;
use App\Enums\AiChat\AiChatResponseMode;
use App\Models\User;
use App\Services\NexaTaxiBookingPricingService;
use App\Services\WebsiteBuilderService;
use Illuminate\Support\Facades\Cache;

final class AiChatQuoteConversationService
{
    private const SESSION_TTL_MINUTES = 45;

    public function __construct(
        private readonly AiChatRouteQuoteParser $parser,
        private readonly AiChatMapsRouteService $mapsRouteService,
        private readonly AiChatQuoteAnswerFormatter $formatter,
        private readonly NexaTaxiBookingPricingService $pricing,
        private readonly WebsiteBuilderService $websiteBuilder,
    ) {}

    public function hasActiveSession(AiChatRequestContext $context): bool
    {
        return $this->getSession($context) !== null;
    }

    public function handle(
        AiChatRequestContext $context,
        string $message,
        ?array $quoteAddress = null,
        ?array $quoteBaggage = null,
    ): AiChatMessageResult {
        if ($this->parser->isCancellation($message)) {
            $this->clearSession($context);

            return new AiChatMessageResult(
                'Geen probleem, ik stop met het samenstellen van je prijsopgave. Waar kan ik je verder mee helpen?',
            );
        }

        $session = $this->getSession($context);

        if ($session !== null && $this->ensureBaggageCatalog($session, $context)) {
            $this->saveSession($context, $session);
        }

        if ($session === null) {
            $session = $this->bootstrapSession($message, $context);
            $this->prefillContactFromUser($session, $context);
            $this->saveSession($context, $session);

            $intro = $this->formatter->introForSession($session);
            $step = $this->nextMissingStep($session, $context);
            $question = $this->formatter->questionForStep($step ?? 'pickup', $session);

            return $this->respond(trim($intro."\n\n".$question), $step, $session, $context);
        }

        $step = $this->nextMissingStep($session, $context);
        if ($step === null) {
            return $this->finalizeQuote($context, $session);
        }

        if (! $this->applyAnswer($session, $step, $message, $quoteAddress, $quoteBaggage)) {
            return $this->respond(
                $this->formatter->invalidAnswerForStep($step, $session)."\n\n".$this->formatter->questionForStep($step, $session),
                $step,
                $session,
                $context,
            );
        }

        $this->saveSession($context, $session);

        $nextStep = $this->nextMissingStep($session, $context);
        if ($nextStep !== null) {
            return $this->respond($this->formatter->questionForStep($nextStep, $session), $nextStep, $session, $context);
        }

        return $this->finalizeQuote($context, $session);
    }

    private function respond(string $text, ?string $step, array &$session, AiChatRequestContext $context): AiChatMessageResult
    {
        if ($step === 'baggage' && $this->ensureBaggageCatalog($session, $context)) {
            $this->saveSession($context, $session);
        }

        return new AiChatMessageResult($text, $this->formatter->inputSpecForStep($step, $session));
    }

    /**
     * @return array<string, mixed>
     */
    private function bootstrapSession(string $message, AiChatRequestContext $context): array
    {
        $route = $this->parser->parseRouteFromQuestion($message);
        $defaults = $this->pricing->getDefaultSectionConfig();
        $bookingConfig = $this->websiteBuilder->resolveBookingModuleSection(
            'component:taxi.boekingsmodule',
            $context->module,
        )['config'] ?? $defaults;

        return [
            'flow' => $this->parser->resolveFlow($message, $context->isPublicChannel()),
            'suggested_pickup' => $route['pickup_address'],
            'suggested_dropoff' => $route['dropoff_address'],
            'pickup_address' => null,
            'dropoff_address' => null,
            'passengers' => null,
            'baggage' => [],
            'special_baggage' => [],
            'baggage_answered' => false,
            'pickup_at' => null,
            'remarks' => null,
            'remarks_answered' => false,
            'first_name' => null,
            'last_name' => null,
            'phone' => null,
            'email' => null,
            'email_answered' => false,
            'contact_email_required' => true,
            'baggage_items' => $this->resolveCatalogItems($bookingConfig['baggage_items'] ?? null, $defaults['baggage_items'] ?? []),
            'special_items' => $this->resolveCatalogItems($bookingConfig['special_items'] ?? null, $defaults['special_items'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function prefillContactFromUser(array &$session, AiChatRequestContext $context): void
    {
        $user = $context->user;
        if (! $user instanceof User) {
            return;
        }

        $firstName = trim((string) ($user->first_name ?? ''));
        if ($firstName !== '') {
            $session['first_name'] = $firstName;
        }

        $lastName = trim((string) ($user->last_name ?? ''));
        if ($lastName !== '') {
            $session['last_name'] = $lastName;
        }

        $phone = trim((string) ($user->phone ?? ''));
        if ($phone !== '') {
            $session['phone'] = $phone;
        }

        $email = trim((string) ($user->email ?? ''));
        if ($email !== '') {
            $session['email'] = $email;
        }
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function ensureBaggageCatalog(array &$session, AiChatRequestContext $context): bool
    {
        $defaults = $this->pricing->getDefaultSectionConfig();
        $bookingConfig = $this->websiteBuilder->resolveBookingModuleSection(
            'component:taxi.boekingsmodule',
            $context->module,
        )['config'] ?? $defaults;

        $changed = false;

        if (! is_array($session['baggage_items'] ?? null) || $session['baggage_items'] === []) {
            $session['baggage_items'] = $this->resolveCatalogItems(
                $bookingConfig['baggage_items'] ?? null,
                $defaults['baggage_items'] ?? [],
            );
            $changed = true;
        }

        if (! is_array($session['special_items'] ?? null) || $session['special_items'] === []) {
            $session['special_items'] = $this->resolveCatalogItems(
                $bookingConfig['special_items'] ?? null,
                $defaults['special_items'] ?? [],
            );
            $changed = true;
        }

        return $changed;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fallback
     * @return array<int, array<string, mixed>>
     */
    private function resolveCatalogItems(mixed $items, array $fallback): array
    {
        if (! is_array($items) || $items === []) {
            return $fallback;
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function nextMissingStep(array $session, AiChatRequestContext $context): ?string
    {
        foreach ($this->stepsForSession($session, $context) as $step) {
            if ($this->stepIsMissing($session, $step)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $session
     * @return list<string>
     */
    private function stepsForSession(array $session, AiChatRequestContext $context): array
    {
        $steps = ['pickup', 'dropoff', 'passengers', 'baggage', 'pickup_at', 'remarks'];

        if (($session['flow'] ?? 'quote') === 'booking' && $this->needsContactSteps($context)) {
            $steps = array_merge($steps, ['first_name', 'last_name', 'phone', 'email']);
        }

        return $steps;
    }

    private function needsContactSteps(AiChatRequestContext $context): bool
    {
        return $context->user === null;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function stepIsMissing(array $session, string $step): bool
    {
        return match ($step) {
            'pickup' => trim((string) ($session['pickup_address'] ?? '')) === '',
            'dropoff' => trim((string) ($session['dropoff_address'] ?? '')) === '',
            'passengers' => ! is_int($session['passengers'] ?? null),
            'baggage' => ($session['baggage_answered'] ?? false) !== true,
            'pickup_at' => trim((string) ($session['pickup_at'] ?? '')) === '',
            'remarks' => ($session['remarks_answered'] ?? false) !== true,
            'first_name' => trim((string) ($session['first_name'] ?? '')) === '',
            'last_name' => trim((string) ($session['last_name'] ?? '')) === '',
            'phone' => trim((string) ($session['phone'] ?? '')) === '',
            'email' => ($session['email_answered'] ?? false) !== true,
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $session
     */
    /**
     * @param  array{label?: string, place_id?: string, lat?: float, lng?: float}|null  $quoteAddress
     */
    private function applyAnswer(
        array &$session,
        string $step,
        string $message,
        ?array $quoteAddress = null,
        ?array $quoteBaggage = null,
    ): bool {
        if ($step === 'remarks') {
            return $this->assignRemarks($session, $message);
        }

        if ($step === 'baggage') {
            return $this->assignBaggage($session, $quoteBaggage, $message);
        }

        if ($step === 'first_name') {
            return $this->assignContactName($session, 'first_name', $message);
        }

        if ($step === 'last_name') {
            return $this->assignContactName($session, 'last_name', $message);
        }

        if ($step === 'phone') {
            return $this->assignPhone($session, $message);
        }

        if ($step === 'email') {
            return $this->assignEmail($session, $message);
        }

        $value = trim($message);
        if ($value === '') {
            return false;
        }

        return match ($step) {
            'pickup' => $this->assignAddress($session, 'pickup_address', 'pickup', $value, $quoteAddress),
            'dropoff' => $this->assignAddress($session, 'dropoff_address', 'dropoff', $value, $quoteAddress),
            'passengers' => $this->assignInt($session, 'passengers', $this->parser->parsePassengers($message)),
            'pickup_at' => $this->assignString($session, 'pickup_at', $this->parser->parsePickupDatetime($message) ?? ''),
            default => false,
        };
    }

    /**
     * @param  array{baggage?: array<string, int>, special_baggage?: array<string, int>}|null  $quoteBaggage
     */
    private function assignBaggage(array &$session, ?array $quoteBaggage, string $message): bool
    {
        if ($quoteBaggage !== null) {
            $session['baggage'] = $quoteBaggage['baggage'] ?? [];
            $session['special_baggage'] = $quoteBaggage['special_baggage'] ?? [];
            $session['baggage_answered'] = true;

            return true;
        }

        $parsed = $this->parser->parseBaggagePieces($message);
        if ($parsed === null) {
            return false;
        }

        $session['baggage'] = $parsed > 0 ? ['small' => $parsed] : [];
        $session['special_baggage'] = [];
        $session['baggage_answered'] = true;

        return true;
    }

    private function assignRemarks(array &$session, string $message): bool
    {
        $value = trim($message);
        if ($this->parser->isOptionalSkipAnswer($message)) {
            $value = '';
        }

        $session['remarks'] = $value;
        $session['remarks_answered'] = true;

        return true;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function assignContactName(array &$session, string $key, string $message): bool
    {
        $value = trim($message);
        if (! $this->parser->isValidContactName($value)) {
            return false;
        }

        $session[$key] = $value;

        return true;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function assignPhone(array &$session, string $message): bool
    {
        $value = trim($message);
        if (! $this->parser->isValidPhone($value)) {
            return false;
        }

        $session['phone'] = $value;

        return true;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function assignEmail(array &$session, string $message): bool
    {
        $value = trim($message);
        $required = ($session['contact_email_required'] ?? false) === true;

        if ($value === '' && ($this->parser->isOptionalSkipAnswer($message) || ! $required)) {
            $session['email'] = '';
            $session['email_answered'] = true;

            return true;
        }

        if (! $this->parser->isValidEmail($value)) {
            return false;
        }

        $session['email'] = $value;
        $session['email_answered'] = true;

        return true;
    }

    /**
     * @param  array<string, mixed>  $session
     * @param  array{label?: string, place_id?: string, lat?: float, lng?: float}|null  $quoteAddress
     */
    private function assignAddress(
        array &$session,
        string $addressKey,
        string $prefix,
        string $value,
        ?array $quoteAddress = null,
    ): bool {
        if (! $this->assignString($session, $addressKey, $value)) {
            return false;
        }

        $session[$prefix.'_place_id'] = null;
        $session[$prefix.'_lat'] = null;
        $session[$prefix.'_lng'] = null;

        if (! is_array($quoteAddress)) {
            return true;
        }

        $placeId = trim((string) ($quoteAddress['place_id'] ?? ''));
        if ($placeId !== '') {
            $session[$prefix.'_place_id'] = $placeId;
        }

        $lat = $quoteAddress['lat'] ?? null;
        $lng = $quoteAddress['lng'] ?? null;
        if (is_numeric($lat) && is_numeric($lng)) {
            $session[$prefix.'_lat'] = (float) $lat;
            $session[$prefix.'_lng'] = (float) $lng;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function assignString(array &$session, string $key, ?string $value): bool
    {
        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }

        $session[$key] = $value;

        return true;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function assignInt(array &$session, string $key, ?int $value): bool
    {
        if ($value === null) {
            return false;
        }

        $session[$key] = $value;

        return true;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function finalizeQuote(AiChatRequestContext $context, array $session): AiChatMessageResult
    {
        $pickup = trim((string) ($session['pickup_address'] ?? ''));
        $dropoff = trim((string) ($session['dropoff_address'] ?? ''));

        $route = $this->mapsRouteService->resolveRoute(
            $pickup,
            $dropoff,
            $this->addressMetaFromSession($session, 'pickup'),
            $this->addressMetaFromSession($session, 'dropoff'),
        );
        if ($route === null) {
            $session['pickup_address'] = null;
            $session['dropoff_address'] = null;
            $session['pickup_place_id'] = null;
            $session['pickup_lat'] = null;
            $session['pickup_lng'] = null;
            $session['dropoff_place_id'] = null;
            $session['dropoff_lat'] = null;
            $session['dropoff_lng'] = null;
            $this->saveSession($context, $session);

            return $this->respond(
                'Ik kon de route niet berekenen voor deze adressen. Kies je ophaal- en bestemmingsadres opnieuw uit de Google-suggesties (bijv. «Amsterdam Airport Schiphol»).',
                'pickup',
                $session,
                $context,
            );
        }

        $this->ensureBaggageCatalog($session, $context);

        $resolved = $this->websiteBuilder->resolveBookingModuleSection('component:taxi.boekingsmodule', $context->module);
        $quoteInput = [
            'distance_meters' => $route['distance_meters'],
            'duration_seconds' => $route['duration_seconds'],
            'passengers' => (int) ($session['passengers'] ?? 1),
            'pickup_at' => (string) ($session['pickup_at'] ?? ''),
            'baggage' => is_array($session['baggage'] ?? null) ? $session['baggage'] : [],
            'special_baggage' => is_array($session['special_baggage'] ?? null) ? $session['special_baggage'] : [],
        ];

        $quoteData = $this->pricing->buildQuotes(
            $resolved['config'],
            $quoteInput,
            $resolved['tenant_company_id'],
        );

        $cheapestOffer = $this->cheapestOffer($quoteData);
        $bookingUrl = $this->buildBookingUrl($session, $cheapestOffer, $route);

        $this->clearSession($context);

        return new AiChatMessageResult($this->formatter->formatQuote($session, $quoteData, $bookingUrl));
    }

    /**
     * @param  array<string, mixed>  $quoteData
     * @return array<string, mixed>|null
     */
    private function cheapestOffer(array $quoteData): ?array
    {
        $offers = is_array($quoteData['offers'] ?? null) ? $quoteData['offers'] : [];
        if ($offers === []) {
            return null;
        }

        usort($offers, fn (array $a, array $b) => ((float) ($a['price'] ?? 0)) <=> ((float) ($b['price'] ?? 0)));

        return $offers[0];
    }

    /**
     * @param  array<string, mixed>  $session
     * @param  array<string, mixed>|null  $offer
     * @param  array{distance_meters?: int, duration_seconds?: int, polyline?: ?string}|null  $route
     */
    private function buildBookingUrl(array $session, ?array $offer, ?array $route = null): string
    {
        $baggage = is_array($session['baggage'] ?? null) ? $session['baggage'] : [];
        $specialBaggage = is_array($session['special_baggage'] ?? null) ? $session['special_baggage'] : [];
        $routePolyline = trim((string) ($route['polyline'] ?? ''));

        $params = array_filter([
            'book_pickup' => $session['pickup_address'] ?? null,
            'book_dropoff' => $session['dropoff_address'] ?? null,
            'book_passengers' => $session['passengers'] ?? null,
            'book_pickup_at' => $session['pickup_at'] ?? null,
            'book_pickup_lat' => $session['pickup_lat'] ?? null,
            'book_pickup_lng' => $session['pickup_lng'] ?? null,
            'book_dropoff_lat' => $session['dropoff_lat'] ?? null,
            'book_dropoff_lng' => $session['dropoff_lng'] ?? null,
            'book_distance_meters' => isset($route['distance_meters']) ? (int) $route['distance_meters'] : null,
            'book_duration_seconds' => isset($route['duration_seconds']) ? (int) $route['duration_seconds'] : null,
            'book_route_polyline' => $routePolyline !== '' && strlen($routePolyline) <= 3500 ? $routePolyline : null,
            'book_baggage' => $baggage !== [] ? json_encode($baggage, JSON_UNESCAPED_UNICODE) : null,
            'book_special_baggage' => $specialBaggage !== [] ? json_encode($specialBaggage, JSON_UNESCAPED_UNICODE) : null,
            'book_remarks' => trim((string) ($session['remarks'] ?? '')) !== '' ? $session['remarks'] : null,
            'book_first_name' => trim((string) ($session['first_name'] ?? '')) !== '' ? $session['first_name'] : null,
            'book_last_name' => trim((string) ($session['last_name'] ?? '')) !== '' ? $session['last_name'] : null,
            'book_phone' => trim((string) ($session['phone'] ?? '')) !== '' ? $session['phone'] : null,
            'book_email' => trim((string) ($session['email'] ?? '')) !== '' ? $session['email'] : null,
            'book_offer' => $offer['id'] ?? null,
            'book_step' => 'confirm',
        ], fn ($value) => $value !== null && $value !== '');

        $query = http_build_query($params);

        return url('/').($query !== '' ? '?'.$query : '').'#boek-rit';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getSession(AiChatRequestContext $context): ?array
    {
        $sessionId = trim((string) ($context->sessionId ?? ''));
        if ($sessionId === '') {
            return null;
        }

        $payload = Cache::get($this->cacheKey($context, $sessionId));

        return is_array($payload) ? $payload : null;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function saveSession(AiChatRequestContext $context, array $session): void
    {
        $sessionId = trim((string) ($context->sessionId ?? ''));
        if ($sessionId === '') {
            return;
        }

        Cache::put(
            $this->cacheKey($context, $sessionId),
            $session,
            now()->addMinutes(self::SESSION_TTL_MINUTES),
        );
    }

    private function clearSession(AiChatRequestContext $context): void
    {
        $sessionId = trim((string) ($context->sessionId ?? ''));
        if ($sessionId === '') {
            return;
        }

        Cache::forget($this->cacheKey($context, $sessionId));
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array{lat?: float, lng?: float, place_id?: string}|null
     */
    private function addressMetaFromSession(array $session, string $prefix): ?array
    {
        $placeId = trim((string) ($session[$prefix.'_place_id'] ?? ''));
        $lat = $session[$prefix.'_lat'] ?? null;
        $lng = $session[$prefix.'_lng'] ?? null;

        if ($placeId === '' && ! is_numeric($lat) && ! is_numeric($lng)) {
            return null;
        }

        $meta = [];
        if ($placeId !== '') {
            $meta['place_id'] = $placeId;
        }
        if (is_numeric($lat) && is_numeric($lng)) {
            $meta['lat'] = (float) $lat;
            $meta['lng'] = (float) $lng;
        }

        return $meta !== [] ? $meta : null;
    }

    private function cacheKey(AiChatRequestContext $context, string $sessionId): string
    {
        return 'ai_chat_quote:'.$context->companyId.':'.$sessionId;
    }

    public function intentResultForAudit(): AiChatIntentResult
    {
        return new AiChatIntentResult(
            intent: AiChatIntent::RitOfferte,
            isAdmin: false,
            allowLiveData: false,
            allowPublicRates: false,
            responseMode: AiChatResponseMode::List,
        );
    }
}
