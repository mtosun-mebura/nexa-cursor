<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiBookingSummaryText;

class WhatsAppBookingMessageComposer
{
    /** Aanbevolen Meta body (klant) — vaste labels + 1 regel per variabele (Meta verbiedt \\n in params). */
    public const META_BODY_CUSTOMER = <<<'TXT'
Beste {{1}},

Uw boeking is succesvol vastgelegd bij {{2}}.

De boekingsgegevens zijn als volgt:
Referentie: {{3}}
Telefoon: {{4}}
Ophalen: {{5}}
Afzetten: {{6}}
Datum/tijd: {{7}}
Passagiers: {{8}}
Aanbieding/voertuig: {{9}}
Prijsindicatie: {{10}}

Met vriendelijke groet,
{{11}}

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
     * Universeel status-sjabloon (klant) — één Meta-template voor acceptatie, afwijzing, start, afronding, enz.
     * {{1}} klant, {{2}} bedrijf, {{3}} status, {{4}} opmerking, {{5}} chauffeur, {{6}} ophaalmoment, {{7}} ophaaladres.
     */
    public const META_BODY_STATUS = <<<'TXT'
Beste {{1}},

Hierbij de reactie op uw taxirit bij {{2}}.

Status: {{3}}.
Opmerking: {{4}}.

Chauffeur: {{5}}
Ophaalmoment: {{6}}
Ophaaladres: {{7}}

Met vriendelijke groet.
TXT;

    /**
     * Ophaalvoorstel (klant) — Meta-template met Quick Reply-knoppen Accepteren / Weigeren.
     * In Meta hoeft geen payload of webhook op de knop: de knoptekst komt via de app-webhook binnen.
     * Elke body-parameter komt maximaal 1× voor (Meta-eis), in leesvolgorde:
     * {{1}} klant, {{2}} bedrijf, {{3}} telefoon tenant, {{4}} huidig ophaalmoment,
     * {{5}} voorgesteld moment, {{6}} ophaaladres, {{7}} afleveradres, {{8}} chauffeur.
     */
    public const META_BODY_PICKUP_PROPOSAL = <<<'TXT'
Beste {{1}},

Uw chauffeur stelt een nieuw ophaalmoment voor, omdat het eerdere tijdstip is verstreken.

Taxi / vervoerder: {{2}}
Telefoon: {{3}}

Huidig ophaalmoment: {{4}}
Voorgesteld ophaalmoment: {{5}}
Ophaaladres: {{6}}
Afleveradres: {{7}}
Chauffeur: {{8}}

Kies Accepteren of Weigeren.
Bij Weigeren kunt u daarna een korte opmerking sturen voor de chauffeur.

Voor vragen kunt u ons bereiken via het telefoonnummer hierboven.

Met vriendelijke groet,

Wij houden u graag op de hoogte via WhatsApp.
TXT;

    public const PICKUP_PROPOSAL_TEMPLATE_KEY = 'WHATSAPP_PICKUP_PROPOSAL_TEMPLATE';

    public const PICKUP_PROPOSAL_TEMPLATE_LANG_KEY = 'WHATSAPP_PICKUP_PROPOSAL_TEMPLATE_LANG';

    /**
     * SMS-tekst bij chauffeur-acceptatie / -afwijzing (geen Meta; plain SMS).
     * {{3}} = Geaccepteerd | Geweigerd, {{4}} = opmerking (of —).
     */
    public const META_BODY_CUSTOMER_SMS = <<<'TXT'
Beste {{1}},

Hierbij de reactie op uw taxirit bij {{2}}.

Status: {{3}}.
Opmerking: {{4}}.

Chauffeur: {{5}}
Ophaalmoment: {{6}}
Ophaaladres: {{7}}

Met vriendelijke groet.
TXT;

    public const DECISION_ACCEPTED = 'Geaccepteerd';

    public const DECISION_DECLINED = 'Geweigerd';

    public const DETAIL_FIELDS_KEY = 'WHATSAPP_BOOKING_DETAIL_FIELDS';

    public const STATUS_TEMPLATE_KEY = 'WHATSAPP_RIDE_STATUS_TEMPLATE';

    public const STATUS_TEMPLATE_LANG_KEY = 'WHATSAPP_RIDE_STATUS_TEMPLATE_LANG';

    public const STATUS_EVENTS_KEY = 'WHATSAPP_RIDE_STATUS_EVENTS';

    public const EVENT_ACCEPTED = 'accepted';

    public const EVENT_DECLINED = 'declined';

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
            self::EVENT_DECLINED => 'Geweigerd',
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
            self::EVENT_DECLINED,
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
            'offer' => 'Aanbieding/voertuig',
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
            $params = $this->customerTemplateParams($ride, $context, $companyName, $customerName);
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
     * Klant-template: labels staan vast in Meta (met regeleinden); params zijn enkelvoudige waarden.
     *
     * @param  array{stopovers?: list<string>, return_at?: string|null, section_config?: array<string, mixed>}  $context
     * @return list<string>
     */
    private function customerTemplateParams(
        RideRequest $ride,
        array $context,
        string $companyName,
        string $customerName
    ): array {
        $selected = is_array($ride->selected_offer_payload) ? $ride->selected_offer_payload : [];
        $phone = trim((string) ($ride->customer_phone ?? ''));
        $pickup = trim((string) ($ride->pickup_address ?? ''));
        $dropoff = trim((string) ($ride->dropoff_address ?? ''));
        $pickupAt = $this->summaryText->formatDateTimeNl($ride->pickup_at);
        $offer = trim((string) ($selected['title'] ?? ''));
        $price = (isset($selected['price']) && is_numeric($selected['price']))
            ? '€ '.number_format((float) $selected['price'], 2, ',', '.')
            : '';

        return [
            $customerName !== '' ? $customerName : 'klant',
            $companyName,
            $ride->id ? 'rit #'.$ride->id : '—',
            $phone !== '' ? $phone : '—',
            $pickup !== '' ? $pickup : '—',
            $dropoff !== '' ? $dropoff : '—',
            $pickupAt !== '' ? $pickupAt : '—',
            (string) ($ride->passengers ?? 1),
            $offer !== '' ? $offer : '—',
            $price !== '' ? $price : '—',
            $companyName,
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
     *     remark?: string|null,
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

        $remark = $this->resolveStatusRemark($context);
        $driverName = trim((string) ($context['driver_name'] ?? ''));
        if ($driverName === '') {
            $driverName = '—';
        }

        $pickupAt = $ride->pickup_at
            ? $ride->pickup_at->timezone(config('app.timezone', 'Europe/Amsterdam'))->format('d-m-Y H:i')
            : '—';
        $pickupAddress = trim((string) ($ride->pickup_address ?: '—'));
        if ($pickupAddress === '') {
            $pickupAddress = '—';
        }

        $params = [
            $customerName,
            $companyName,
            $statusLabel,
            mb_substr($remark, 0, 1024),
            $driverName,
            $pickupAt,
            mb_substr($pickupAddress, 0, 1024),
        ];
        $preview = $this->renderPreview(self::META_BODY_STATUS, $params);
        $detailsBlock = trim(implode("\n", array_filter([
            $remark !== '—' ? 'Opmerking: '.$remark : null,
            $driverName !== '—' ? 'Chauffeur: '.$driverName : null,
            $pickupAt !== '—' ? 'Ophaalmoment: '.$pickupAt : null,
            $pickupAddress !== '—' ? 'Ophaaladres: '.$pickupAddress : null,
        ])));

        return [
            'template_params' => $params,
            'fallback_body' => $preview,
            'details' => $detailsBlock !== '' ? $detailsBlock : 'Referentie: rit #'.(string) ($ride->id ?: '—'),
            'preview' => $preview,
            'status_label' => $statusLabel,
            'event' => $event,
        ];
    }

    /**
     * @param  array{remark?: string|null, extra_lines?: list<string>}  $context
     */
    protected function resolveStatusRemark(array $context): string
    {
        $remark = trim((string) ($context['remark'] ?? ''));
        if ($remark !== '') {
            return $remark;
        }

        $parts = [];
        foreach ($context['extra_lines'] ?? [] as $line) {
            $line = is_string($line) ? trim($line) : '';
            if ($line === '') {
                continue;
            }
            if (str_starts_with($line, 'Opmerking:')) {
                $line = trim(mb_substr($line, strlen('Opmerking:')));
            }
            if ($line !== '') {
                $parts[] = $line;
            }
        }

        if ($parts === []) {
            return '—';
        }

        return implode(' ', $parts);
    }

    /**
     * SMS-body bij accept/afwijs (zelfde structuur als META_BODY_CUSTOMER_SMS).
     *
     * @param  array{driver_name?: string|null, remark?: string|null}  $context
     * @return array{template_params: list<string>, body: string, status_label: string}
     */
    public function composeCustomerSms(
        RideRequest $ride,
        string $decisionLabel,
        array $context,
        ?int $companyId
    ): array {
        $companyName = $this->whatsapp->tenantDisplayName($companyId);
        if ($companyName === '') {
            $companyName = 'Nexa';
        }

        $customerName = trim((string) ($ride->customer_name ?: 'klant'));
        if ($customerName === '') {
            $customerName = 'klant';
        }

        $status = trim($decisionLabel) !== '' ? trim($decisionLabel) : self::DECISION_ACCEPTED;
        $remark = trim((string) ($context['remark'] ?? ''));
        if ($remark === '') {
            $remark = '—';
        }

        $driverName = trim((string) ($context['driver_name'] ?? ''));
        if ($driverName === '') {
            $driverName = '—';
        }

        $pickupAt = $ride->pickup_at
            ? $ride->pickup_at->timezone(config('app.timezone', 'Europe/Amsterdam'))->format('d-m-Y H:i')
            : '—';
        $pickupAddress = trim((string) ($ride->pickup_address ?: '—'));
        if ($pickupAddress === '') {
            $pickupAddress = '—';
        }

        $params = [
            $customerName,
            $companyName,
            $status,
            mb_substr($remark, 0, 1024),
            $driverName,
            $pickupAt,
            mb_substr($pickupAddress, 0, 1024),
        ];

        return [
            'template_params' => $params,
            'body' => $this->renderPreview(self::META_BODY_CUSTOMER_SMS, $params),
            'status_label' => $status,
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
        $statusLabel = self::statusEventLabels()[$event] ?? 'Statusupdate';
        $params = [
            'Jan de Vries',
            'Taxi Voorbeeld',
            $statusLabel,
            '—',
            'Piet Chauffeur',
            '16-07-2026 14:30',
            'Dam 1, Amsterdam',
        ];

        return [
            'preview' => $this->renderPreview(self::META_BODY_STATUS, $params),
            'details' => "Opmerking: —\nChauffeur: Piet Chauffeur\nOphaalmoment: 16-07-2026 14:30\nOphaaladres: Dam 1, Amsterdam",
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
        $params = [
            'Jan de Vries',
            'Taxi Voorbeeld',
            'rit #1042',
            '+31 6 12345678',
            'Stationsplein 1, Amsterdam',
            'Schiphol Airport',
            '16-08-2026 14:30',
            '2',
            'Comfort',
            '€ 45,00',
            'Taxi Voorbeeld',
        ];
        $details = implode("\n", [
            'Referentie: '.$params[2],
            'Telefoon: '.$params[3],
            'Ophalen: '.$params[4],
            'Afzetten: '.$params[5],
            'Datum/tijd: '.$params[6],
            'Passagiers: '.$params[7],
            'Aanbieding/voertuig: '.$params[8],
            'Prijsindicatie: '.$params[9],
        ]);

        return [
            'preview' => $this->renderPreview(self::META_BODY_CUSTOMER, $params),
            'details' => $details,
            'params' => $params,
        ];
    }

    public function pickupProposalTemplateName(): string
    {
        $name = trim((string) $this->env->get(self::PICKUP_PROPOSAL_TEMPLATE_KEY, ''));
        if ($name === '') {
            $name = trim((string) GeneralSetting::get(self::PICKUP_PROPOSAL_TEMPLATE_KEY, ''));
        }

        return $name !== '' ? $name : 'rit_ophaal_voorstel';
    }

    public function pickupProposalTemplateLanguage(): string
    {
        $lang = trim((string) $this->env->get(self::PICKUP_PROPOSAL_TEMPLATE_LANG_KEY, 'nl'));
        if ($lang === '') {
            $lang = trim((string) GeneralSetting::get(self::PICKUP_PROPOSAL_TEMPLATE_LANG_KEY, 'nl')) ?: 'nl';
        }

        return $lang !== '' ? $lang : 'nl';
    }

    /**
     * @return list<string>
     */
    public function pickupProposalBodyParameters(RideRequest $ride, ?\App\Models\User $driver = null): array
    {
        $companyName = 'Taxi';
        $companyPhone = '—';
        try {
            if ($ride->company_id) {
                $company = \App\Models\Company::query()->find($ride->company_id);
                if ($company && trim((string) $company->name) !== '') {
                    $companyName = trim((string) $company->name);
                }
                $rawPhone = trim((string) ($company->phone ?? ''));
                if ($rawPhone !== '') {
                    $normalized = \App\Support\DutchPhoneNumber::normalizeOptionalNlToInternational($rawPhone);
                    $companyPhone = ($normalized !== null && $normalized !== '') ? $normalized : $rawPhone;
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        $driverName = 'uw chauffeur';
        if ($driver) {
            $driverName = trim(($driver->first_name ?? '').' '.($driver->last_name ?? ''));
            if ($driverName === '') {
                $driverName = 'uw chauffeur';
            }
        }

        $format = function ($value) {
            if (! $value) {
                return '—';
            }
            $wall = \App\Modules\NexaTaxi\Support\ContractTransportTimezone::asAmsterdamWall($value)
                ?? \Illuminate\Support\Carbon::parse($value);

            return $wall->timezone(\App\Modules\NexaTaxi\Support\ContractTransportTimezone::TIMEZONE)
                ->format('d-m-Y H:i');
        };

        return [
            (string) ($ride->customer_name ?: 'klant'),
            $companyName,
            $companyPhone,
            $format($ride->pickup_at),
            $format($ride->pickup_proposal_at),
            (string) ($ride->pickup_address ?: '—'),
            (string) ($ride->dropoff_address ?: '—'),
            $driverName,
        ];
    }

    /**
     * @return array{preview: string, params: list<string>}
     */
    public function samplePickupProposalPreview(): array
    {
        $params = [
            'Jan de Vries',
            'Taxi Voorbeeld',
            '+31531234567',
            '13-08-2026 08:25',
            '13-08-2026 09:15',
            'Deurningerstraat 153, Enschede',
            'KFC Spaansland, Enschede',
            'Piet Chauffeur',
        ];

        return [
            'preview' => $this->renderPreview(self::META_BODY_PICKUP_PROPOSAL, $params),
            'params' => $params,
        ];
    }

    public function pickupProposalPreviewForRide(RideRequest $ride, ?\App\Models\User $driver = null): string
    {
        return $this->renderPreview(
            self::META_BODY_PICKUP_PROPOSAL,
            $this->pickupProposalBodyParameters($ride, $driver)
        );
    }
}
