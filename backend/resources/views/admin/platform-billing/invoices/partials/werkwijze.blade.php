@php
    $dagen = static function (int $n): string {
        return $n === 1 ? '1 dag' : $n.' dagen';
    };
    $billingDay = max(1, min(28, (int) $billingSettings->billing_day));
    $billingTime = substr((string) $billingSettings->billing_time, 0, 5);
    if (! preg_match('/^\d{2}:\d{2}$/', $billingTime)) {
        $billingTime = '05:00';
    }
    $paymentTermsDays = max(1, (int) ($billingSettings->payment_terms_days ?: 14));
    $firstReminderDays = \App\Models\PlatformBillingSetting::dunningFirstIntervalDays();
    $secondIntervalDays = \App\Models\PlatformBillingSetting::dunningSecondIntervalDays();
    $examples = [
        [
            'id' => 'invoice',
            'label' => 'Factuur',
            'kind' => 'invoice',
            'mail' => null,
        ],
        [
            'id' => 'first',
            'label' => '1e aanmaning',
            'kind' => 'first',
            'mail' => $dummyFirstReminder ?? null,
        ],
        [
            'id' => 'second',
            'label' => '2e aanmaning',
            'kind' => 'second',
            'mail' => $dummySecondReminder ?? null,
        ],
    ];
@endphp

<div class="kt-card w-full min-w-0 mb-5" id="nexa-facturatie-werkwijze">
    <div class="kt-card-header nexa-werkwijze-header flex flex-wrap items-center gap-3 px-5 py-5">
        <button type="button"
                class="nexa-werkwijze-toggle flex min-w-0 flex-1 items-center border-0 bg-transparent p-0 text-start cursor-pointer"
                id="nexa-werkwijze-toggle"
                aria-expanded="true"
                aria-controls="nexa-facturatie-werkwijze-body">
            <h3 class="kt-card-title mb-0">
                Werkwijze NEXA-facturatie
            </h3>
        </button>
        <a href="{{ route('admin.platform-billing.settings.edit') }}" class="kt-btn kt-btn-sm kt-btn-outline shrink-0">
            <i class="ki-filled ki-setting-2 me-1.5"></i>
            Facturatie-instellingen
        </a>
        <button type="button"
                class="nexa-werkwijze-toggle nexa-werkwijze-chevron-btn shrink-0 inline-flex items-center justify-center border-0 bg-transparent p-0 text-muted-foreground cursor-pointer"
                id="nexa-werkwijze-chevron"
                aria-expanded="true"
                aria-controls="nexa-facturatie-werkwijze-body"
                aria-label="Werkwijze in- of uitklappen">
            <span class="nexa-werkwijze-chevron" aria-hidden="true">
                <i class="ki-filled ki-down nexa-werkwijze-icon-down text-base"></i>
                <i class="ki-filled ki-up nexa-werkwijze-icon-up text-base"></i>
            </span>
        </button>
    </div>
    <div class="kt-card-content p-5 lg:p-6" id="nexa-facturatie-werkwijze-body">
        <p class="text-sm text-muted-foreground mb-5">
            Facturatie, betalingscontrole, aanmaningen en blokkade lopen automatisch. De termijnen hieronder zijn de <strong class="font-medium text-foreground">exacte waarden</strong> zoals ingevoerd onder facturatie-instellingen; wijzigingen daar zijn meteen zichtbaar op deze pagina.
        </p>

        <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5 mb-6">
            <div class="rounded-lg border border-border bg-muted/30 p-3">
                <dt class="text-xs text-muted-foreground mb-1">Facturatiemoment</dt>
                <dd class="mb-0 text-sm font-medium text-foreground">dag {{ $billingDay }} van de maand om {{ $billingTime }}</dd>
            </div>
            <div class="rounded-lg border border-border bg-muted/30 p-3">
                <dt class="text-xs text-muted-foreground mb-1">Betaaltermijn</dt>
                <dd class="mb-0 text-sm font-medium text-foreground">{{ $dagen($paymentTermsDays) }} na factuurdatum</dd>
            </div>
            <div class="rounded-lg border border-border bg-muted/30 p-3">
                <dt class="text-xs text-muted-foreground mb-1">1e aanmaning</dt>
                <dd class="mb-0 text-sm font-medium text-foreground">{{ $dagen($firstReminderDays) }} na de vervaldatum</dd>
            </div>
            <div class="rounded-lg border border-border bg-muted/30 p-3">
                <dt class="text-xs text-muted-foreground mb-1">2e aanmaning</dt>
                <dd class="mb-0 text-sm font-medium text-foreground">{{ $dagen($secondIntervalDays) }} na de 1e aanmaning</dd>
            </div>
            <div class="rounded-lg border border-border bg-muted/30 p-3">
                <dt class="text-xs text-muted-foreground mb-1">Blokkade</dt>
                <dd class="mb-0 text-sm font-medium text-foreground">{{ $dagen($secondIntervalDays) }} na de 2e aanmaning</dd>
            </div>
        </dl>

        <ol class="m-0 flex flex-col gap-4 p-0 list-none">
            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-medium text-primary" aria-hidden="true">1</span>
                <div class="min-w-0">
                    <div class="font-medium text-foreground">Maandfactuur</div>
                    <p class="mb-0 mt-1 text-sm text-muted-foreground">
                        Elke maand op dag {{ $billingDay }} om {{ $billingTime }} maakt NEXA automatisch een factuur voor tenants met een actief abonnement (tenzij de tenant via Mollie-abonnement zelf wordt geïncasseerd). Met <strong class="font-medium text-foreground">Facturatie nu draaien</strong> kun je dat meteen forceren. De vervaldatum is de factuurdatum plus de betaaltermijn ({{ $dagen($paymentTermsDays) }}; per factuur aanpasbaar).
                    </p>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-medium text-primary" aria-hidden="true">2</span>
                <div class="min-w-0">
                    <div class="font-medium text-foreground">Betaling controleren</div>
                    <p class="mb-0 mt-1 text-sm text-muted-foreground">
                        Mollie meldt een betaling meteen via de webhook; de factuur wordt dan op betaald gezet. Daarnaast loopt elke dag om 06:00 — en meteen via <strong class="font-medium text-foreground">Betalingen controleren</strong> — een controle van alle openstaande NEXA-facturen bij Mollie. Is er alsnog betaald, dan stopt de aanmaningsreeks en wordt een automatische blokkade opgeheven.
                    </p>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-medium text-primary" aria-hidden="true">3</span>
                <div class="min-w-0">
                    <div class="font-medium text-foreground">1e aanmaning</div>
                    <p class="mb-0 mt-1 text-sm text-muted-foreground">
                        Is de factuur {{ $dagen($firstReminderDays) }} na de vervaldatum nog open, dan gaat de 1e aanmaning naar het facturatie-e-mailadres van de tenant, met de factuur-PDF als bijlage.
                    </p>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-medium text-primary" aria-hidden="true">4</span>
                <div class="min-w-0">
                    <div class="font-medium text-foreground">2e aanmaning</div>
                    <p class="mb-0 mt-1 text-sm text-muted-foreground">
                        Blijft betaling uit, dan volgt {{ $dagen($secondIntervalDays) }} na de 1e aanmaning de 2e aanmaning, opnieuw met PDF.
                    </p>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-medium text-primary" aria-hidden="true">5</span>
                <div class="min-w-0">
                    <div class="font-medium text-foreground">Blokkade</div>
                    <p class="mb-0 mt-1 text-sm text-muted-foreground">
                        Nog steeds onbetaald {{ $dagen($secondIntervalDays) }} na de 2e aanmaning? Dan wordt de tenant geblokkeerd. Per tenant kies je onder
                        <a href="{{ route('admin.platform-billing.tenants.index') }}" class="text-primary hover:underline">Tenant-abonnementen</a>
                        of alleen de boekingsmodule uit gaat, of de hele omgeving. Een handmatige blokkade blijft staan tot je die zelf opheft.
                    </p>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-medium text-primary" aria-hidden="true">6</span>
                <div class="min-w-0">
                    <div class="font-medium text-foreground">Opheffen na betaling</div>
                    <p class="mb-0 mt-1 text-sm text-muted-foreground">
                        Na betaling (via Mollie of door de factuur handmatig op betaald te zetten) verdwijnt een automatische blokkade vanzelf, tenzij er nog een andere openstaande NEXA-factuur in dezelfde fase zit.
                    </p>
                </div>
            </li>
        </ol>

        @if(! empty($dummyInvoicePreview))
            <div class="mt-8 pt-6 border-t border-border">
                <h4 class="text-sm font-medium text-foreground mb-1">Voorbeelden</h4>
                <p class="text-sm text-muted-foreground mb-4">
                    Opgebouwd met dummygegevens en de huidige facturatie-instellingen. Klik op een plaatje om te vergroten.
                </p>
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach($examples as $example)
                        <div class="nexa-werkwijze-thumb"
                             role="button"
                             tabindex="0"
                             data-nexa-preview-open="{{ $example['id'] }}"
                             aria-haspopup="dialog"
                             aria-controls="nexa-werkwijze-preview-modal"
                             aria-label="Vergroot voorbeeld {{ $example['label'] }}">
                            <span class="nexa-werkwijze-thumb__label">{{ $example['label'] }}</span>
                            <span class="nexa-werkwijze-thumb__clip">
                                <span class="nexa-werkwijze-thumb__scale" data-nexa-preview-source="{{ $example['id'] }}">
                                    @include('admin.platform-billing.invoices.partials.dummy-example', [
                                        'kind' => $example['kind'],
                                        'preview' => $dummyInvoicePreview,
                                        'mail' => $example['mail'],
                                    ])
                                </span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<div id="nexa-werkwijze-preview-modal"
     class="nexa-werkwijze-modal hidden fixed inset-0 z-[100000] flex items-center justify-center bg-zinc-950/70 p-4 backdrop-blur-md"
     role="dialog"
     aria-modal="true"
     aria-labelledby="nexa-werkwijze-preview-title"
     hidden>
    <div class="nexa-werkwijze-modal__panel flex flex-col w-full max-w-4xl max-h-[90vh] overflow-hidden rounded-2xl border border-border bg-background shadow-2xl">
        <div class="nexa-werkwijze-modal__header flex shrink-0 items-center justify-between gap-3 border-b border-border px-5 py-5">
            <h3 id="nexa-werkwijze-preview-title" class="text-base font-medium text-foreground mb-0">Voorbeeld</h3>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-nexa-preview-close aria-label="Sluiten">
                <i class="ki-filled ki-cross text-lg"></i>
            </button>
        </div>
        <div class="nexa-werkwijze-modal__body min-h-0 flex-1 overflow-y-auto p-5 lg:p-6" data-nexa-preview-target></div>
    </div>
</div>
