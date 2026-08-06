<?php

namespace App\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiBookingSummaryText;
use App\Models\GeneralSetting;

class WhatsAppBookingMessageComposer
{
    /** Aanbevolen Meta body (klant) — vaste zinnen; variabelen worden vanuit Nexa gevuld. */
    public const META_BODY_CUSTOMER = <<<'TXT'
Beste {{1}},

Uw boeking is succesvol vastgelegd bij {{2}}.

De boekingsgegevens zijn als volgt:
{{3}}

Met vriendelijke groet,
{{4}}

Wij houden u graag op de hoogte via WhatsApp.
TXT;

    /** Aanbevolen Meta body (dispatch). */
    public const META_BODY_DISPATCH = <<<'TXT'
Nieuwe boeking voor {{1}}.

Klant: {{2}}

De boekingsgegevens zijn als volgt:
{{3}}

Afzender: {{4}}. Controleer de rit in het dispatch-overzicht.
TXT;

    /**
     * Universeel status-sjabloon (klant) — één Meta-template voor acceptatie, start, afronding, annulering, enz.
     * {{3}} = statuslabel (wisselbaar), {{4}} = details.
     */
    public const META_BODY_STATUS = <<<'TXT'
Beste {{1}},

Hierbij een statusupdate over uw taxirit bij {{2}}.

Huidige status: {{3}}.

De bijbehorende gegevens zijn:
{{4}}

Heeft u vragen over deze rit? Reageer gerust op dit WhatsApp-bericht. Wij helpen u graag verder.

Met vriendelijke groet en tot ziens.
TXT;

    public const DETAIL_FIELDS_KEY = 'WHATSAPP_BOOKING_DETAIL_FIELDS';

    public const STATUS_TEMPLATE_KEY = 'WHATSAPP_RIDE_STATUS_TEMPLATE';

    public const STATUS_TEMPLATE_LANG_KEY = 'WHATSAPP_RIDE_STATUS_TEMPLATE_LANG';

    public const STATUS_EVENTS_KEY = 'WHATSAPP_RIDE_STATUS_EVENTS';

    public const EVENT_ACCEPTED = 'accepted';

    public const EVENT_STARTED = 'started';

    public const EVENT_COMPLETED = 'completed';

    public const EVENT_CANCELLED = 'cancelled';

    public const EVENT_REDISPATCHED = 'redispatched';

    /**
     * @return array<string, string> event => NL statuslabel voor {{3}}
     */
    public static function statusEventLabels(): array
    {
        return [
            self::EVENT_ACCEPTED => 'Chauffeur toegewezen',
            self::EVENT_STARTED => 'Rit gestart — chauffeur onderweg',
            self::EVENT_COMPLETED => 'Rit afgerond',
            self::EVENT_CANCELLED => 'Rit geannuleerd',
            self::EVENT_REDISPATCHED => 'Opnieuw op zoek naar een chauffeur',
        ];
    }

    /**
     * @return list<string>
     */
    public static function defaultStatusEvents(): array
    {
        return [
            self::EVENT_ACCEPTED,
            self::EVENT_STARTED,
            self::EVENT_COMPLETED,
            self::EVENT_CANCELLED,
            self::EVENT_REDISPATCHED,
        ];
    }

    /**
     * @return array<string, string> key => label
     */
    public static function availableDetailFields(): array
    {
        return [
            'reference' => 'Referentie (rit #)',
            'customer_name' => 'Naam',
            'customer_phone' => 'Telefoon',
            'customer_email' => 'E-mail',
            'pickup_address' => 'Ophalen',
            'dropoff_address' => 'Afzetten',
            'pickup_at' => 'Datum/tijd',
            'passengers' => 'Passagiers',
            'baggage' => 'Bagage',
            'stopovers' => 'Tussenstops',
            'return_trip' => 'Retour',
            'offer' => 'Aanbieding',
            'price' => 'Prijsindicatie',
            'remarks' => 'Opmerking',
        ];
    }

    /**
     * @return list<string>
     */
    public static function defaultDetailFields(): array
    {
        return [
            'reference',
            'pickup_address',
            'dropoff_address',
            'pickup_at',
            'passengers',
            'customer_phone',
            'offer',
            'price',
            'remarks',
        ];
    }

    public function __construct(
        protected TaxiBookingSummaryText $summaryText,
        protected WhatsAppBusinessService $whatsapp,
        protected EnvService $env
    ) {}

    /**
     * @param  array{stopovers?: list<string>, return_at?: string|null, section_config?: array<string, mixed>}  $context
     * @param  'dispatch'|'customer'  $purpose
     * @return array{
     *     template_params: list<string>,
     *     fallback_body: string,
     *     details: string,
     *     preview: string
     * }
     */
    public function compose(RideRequest $ride, array $context, ?int $companyId, string $purpose = 'customer'): array
    {
        $companyName = $this->whatsapp->tenantDisplayName($companyId);
        if ($companyName === '') {
            $companyName = 'Nexa';
        }

        $customerName = trim((string) ($ride->customer_name ?: 'klant'));
        if ($customerName === '') {
            $customerName = 'klant';
        }

        $details = $this->summaryText->buildSelected(
            $ride,
            $this->selectedDetailFields(),
            $context
        );
        if ($details === '') {
            $details = $this->summaryText->build($ride, $context);
        }

        if ($purpose === 'dispatch') {
            $params = [
                $companyName,
                $customerName,
                mb_substr($details, 0, 1024),
                $companyName,
            ];
            $preview = $this->renderPreview(self::META_BODY_DISPATCH, $params);
            $fallback = $preview;
        } else {
            $params = [
                $customerName,
                $companyName,
                mb_substr($details, 0, 1024),
                $companyName,
            ];
            $preview = $this->renderPreview(self::META_BODY_CUSTOMER, $params);
            $fallback = $preview;
        }

        return [
            'template_params' => $params,
            'fallback_body' => $fallback,
            'details' => $details,
            'preview' => $preview,
        ];
    }

    /**
     * Universele statusupdate voor de klant (één Meta-template, wisselbaar statuslabel).
     *
     * @param  array{
     *     stopovers?: list<string>,
     *     return_at?: string|null,
     *     section_config?: array<string, mixed>,
     *     driver_name?: string|null,
     *     driver_phone?: string|null,
     *     extra_lines?: list<string>
     * }  $context
     * @return array{
     *     template_params: list<string>,
     *     fallback_body: string,
     *     details: string,
     *     preview: string,
     *     status_label: string,
     *     event: string
     * }
     */
    public function composeStatus(RideRequest $ride, string $event, array $context, ?int $companyId): array
    {
        $labels = self::statusEventLabels();
        $statusLabel = $labels[$event] ?? trim($event);
        if ($statusLabel === '') {
            $statusLabel = 'Statusupdate';
        }

        $companyName = $this->whatsapp->tenantDisplayName($companyId);
        if ($companyName === '') {
            $companyName = 'Nexa';
        }

        $customerName = trim((string) ($ride->customer_name ?: 'klant'));
        if ($customerName === '') {
            $customerName = 'klant';
        }

        $detailLines = [];
        $driverName = trim((string) ($context['driver_name'] ?? ''));
        if ($driverName !== '') {
            $detailLines[] = 'Chauffeur: '.$driverName;
        }
        $driverPhone = trim((string) ($context['driver_phone'] ?? ''));
        if ($driverPhone !== '') {
            $detailLines[] = 'Chauffeur telefoon: '.$driverPhone;
        }

        $details = $this->summaryText->buildSelected(
            $ride,
            $this->selectedDetailFields(),
            $context
        );
        if ($details === '') {
            $details = $this->summaryText->build($ride, $context);
        }
        if ($details !== '') {
            $detailLines[] = $details;
        }
        foreach ($context['extra_lines'] ?? [] as $line) {
            $line = is_string($line) ? trim($line) : '';
            if ($line !== '') {
                $detailLines[] = $line;
            }
        }

        $detailsBlock = implode("\n", $detailLines);
        if ($detailsBlock === '') {
            $detailsBlock = 'Referentie: rit #'.(string) ($ride->id ?: '—');
        }

        $params = [
            $customerName,
            $companyName,
            $statusLabel,
            mb_substr($detailsBlock, 0, 1024),
        ];
        $preview = $this->renderPreview(self::META_BODY_STATUS, $params);

        return [
            'template_params' => $params,
            'fallback_body' => $preview,
            'details' => $detailsBlock,
            'preview' => $preview,
            'status_label' => $statusLabel,
            'event' => $event,
        ];
    }

    /**
     * @return list<string>
     */
    public function selectedStatusEvents(): array
    {
        $raw = trim((string) $this->env->get(self::STATUS_EVENTS_KEY, ''));
        if ($raw === '') {
            $raw = trim((string) GeneralSetting::get(self::STATUS_EVENTS_KEY, ''));
        }

        $available = array_keys(self::statusEventLabels());
        if ($raw === '') {
            return self::defaultStatusEvents();
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            $decoded = preg_split('/\s*,\s*/', $raw) ?: [];
        }

        $events = [];
        foreach ($decoded as $key) {
            $key = is_string($key) ? trim($key) : '';
            if ($key !== '' && in_array($key, $available, true)) {
                $events[] = $key;
            }
        }

        return $events !== [] ? array_values(array_unique($events)) : self::defaultStatusEvents();
    }

    public function statusEventEnabled(string $event): bool
    {
        return in_array($event, $this->selectedStatusEvents(), true);
    }

    public function statusTemplateName(): string
    {
        $name = trim((string) $this->env->get(self::STATUS_TEMPLATE_KEY, ''));
        if ($name === '') {
            $name = trim((string) GeneralSetting::get(self::STATUS_TEMPLATE_KEY, ''));
        }

        return $name;
    }

    public function statusTemplateLang(): string
    {
        $lang = trim((string) $this->env->get(self::STATUS_TEMPLATE_LANG_KEY, 'nl'));
        if ($lang === '') {
            $lang = trim((string) GeneralSetting::get(self::STATUS_TEMPLATE_LANG_KEY, 'nl')) ?: 'nl';
        }

        return $lang !== '' ? $lang : 'nl';
    }

    /**
     * @param  list<string>|null  $detailFields
     * @return array{preview: string, details: string, params: list<string>}
     */
    public function sampleStatusPreview(string $event = self::EVENT_ACCEPTED, ?array $detailFields = null): array
    {
        $sample = $this->sampleCustomerPreview($detailFields);
        $statusLabel = self::statusEventLabels()[$event] ?? 'Statusupdate';
        $details = "Chauffeur: Piet Chauffeur\n".$sample['details'];
        $params = ['Jan de Vries', 'Taxi Voorbeeld', $statusLabel, mb_substr($details, 0, 1024)];

        return [
            'preview' => $this->renderPreview(self::META_BODY_STATUS, $params),
            'details' => $details,
            'params' => $params,
        ];
    }

    /**
     * @return list<string>
     */
    public function selectedDetailFields(): array
    {
        $raw = trim((string) $this->env->get(self::DETAIL_FIELDS_KEY, ''));
        if ($raw === '') {
            $raw = trim((string) GeneralSetting::get(self::DETAIL_FIELDS_KEY, ''));
        }

        $available = array_keys(self::availableDetailFields());
        if ($raw === '') {
            return self::defaultDetailFields();
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            $decoded = preg_split('/\s*,\s*/', $raw) ?: [];
        }

        $fields = [];
        foreach ($decoded as $key) {
            $key = is_string($key) ? trim($key) : '';
            if ($key !== '' && in_array($key, $available, true)) {
                $fields[] = $key;
            }
        }

        return $fields !== [] ? array_values(array_unique($fields)) : self::defaultDetailFields();
    }

    /**
     * @param  list<string>  $params
     */
    public function renderPreview(string $metaBody, array $params): string
    {
        $out = $metaBody;
        foreach ($params as $i => $value) {
            $out = str_replace('{{'.($i + 1).'}}', (string) $value, $out);
        }

        return $out;
    }

    /**
     * Sample preview for admin settings (geen echte rit).
     *
     * @param  list<string>|null  $detailFields
     * @return array{preview: string, details: string, params: list<string>}
     */
    public function sampleCustomerPreview(?array $detailFields = null): array
    {
        $fields = $detailFields ?? $this->selectedDetailFields();
        $labels = self::availableDetailFields();
        $sampleValues = [
            'reference' => 'rit #1042',
            'customer_name' => 'Jan de Vries',
            'customer_phone' => '+31 6 12345678',
            'customer_email' => 'jan@example.nl',
            'pickup_address' => 'Stationsplein 1, Amsterdam',
            'dropoff_address' => 'Schiphol Airport',
            'pickup_at' => '16-08-2026 14:30',
            'passengers' => '2',
            'baggage' => 'Koffer x 1',
            'stopovers' => 'Geen',
            'return_trip' => 'Nee',
            'offer' => 'Comfort',
            'price' => '€ 45,00',
            'remarks' => 'Bordje bij aankomst',
        ];

        $lines = [];
        foreach ($fields as $key) {
            if (! isset($labels[$key])) {
                continue;
            }
            $lines[] = $labels[$key].': '.($sampleValues[$key] ?? '—');
        }
        $details = implode("\n", $lines);
        $params = ['Jan de Vries', 'Taxi Voorbeeld', mb_substr($details, 0, 1024), 'Taxi Voorbeeld'];

        return [
            'preview' => $this->renderPreview(self::META_BODY_CUSTOMER, $params),
            'details' => $details,
            'params' => $params,
        ];
    }
}
