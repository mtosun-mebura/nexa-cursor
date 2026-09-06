@extends('admin.layouts.app')

@section('title', 'NEXA Suite boekingsinstellingen')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">NEXA Suite boekingsinstellingen</h1>
            <div class="text-sm text-secondary-foreground mt-2">Fee, automatische facturatie en aanmaningen</div>
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert"><i class="ki-filled ki-check-circle me-2"></i>{{ session('success') }}</div>
    @endif

    @include('admin.nexa-suite-bookings.partials.nav')

    <form method="POST" action="{{ route('admin.nexa-suite-bookings.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">Provisie</h3></div>
                <div class="kt-card-content p-5">
                    <label class="text-sm text-muted-foreground mb-1 block">Fee over elke gereden NEXA Suite-rit (%)</label>
                    <input type="number" name="fee_percent" min="0" max="100" step="1" inputmode="numeric" class="kt-input w-40 @error('fee_percent') border-destructive @enderror" value="{{ old('fee_percent', (int) $settings->fee_percent) }}" required>
                    <p class="text-xs text-muted-foreground mt-1.5 mb-0">Bijvoorbeeld 10: over €100 ritomzet factureer je €10 excl. BTW.</p>
                    @error('fee_percent')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">Automatische facturatie</h3></div>
                <div class="kt-card-content p-5 space-y-4">
                    <label class="kt-label flex items-center gap-2">
                        <input type="hidden" name="auto_generate" value="0">
                        <input type="checkbox" name="auto_generate" value="1" class="kt-switch" @checked(old('auto_generate', $settings->auto_generate))>
                        Maandelijks automatisch facturen aanmaken
                    </label>
                    <label class="kt-label flex items-center gap-2">
                        <input type="hidden" name="auto_send" value="0">
                        <input type="checkbox" name="auto_send" value="1" class="kt-switch" @checked(old('auto_send', $settings->auto_send))>
                        Aangemaakte facturen direct naar de tenant mailen
                    </label>
                    <div class="flex flex-wrap gap-4">
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">Facturatiedag (1–28)</label>
                            <input type="number" name="billing_day" min="1" max="28" class="kt-input w-28" value="{{ old('billing_day', $settings->billing_day) }}" required>
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">Tijd</label>
                            <input type="time" name="billing_time" class="kt-input w-36" value="{{ old('billing_time', substr((string) $settings->billing_time, 0, 5)) }}" required>
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground mb-0">Op deze dag/tijd worden de voltooide ritten van de vorige maand gefactureerd — hetzelfde ritme als de SaaS-facturatie.</p>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">Betaling &amp; aanmaningen</h3></div>
                <div class="kt-card-content p-5">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">BTW %</label>
                            <input type="number" name="tax_rate_percent" min="0" max="100" step="1" inputmode="numeric" class="kt-input w-full @error('tax_rate_percent') border-destructive @enderror" value="{{ old('tax_rate_percent', (int) $settings->tax_rate_percent) }}" required>
                            @error('tax_rate_percent')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">Betaaltermijn (dagen)</label>
                            <input type="number" name="payment_terms_days" min="1" max="365" class="kt-input w-full" value="{{ old('payment_terms_days', $settings->payment_terms_days) }}" required>
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">1e aanmaning na (dagen)</label>
                            <input type="number" name="dunning_first_interval_days" min="1" max="90" class="kt-input w-full" value="{{ old('dunning_first_interval_days', $settings->dunning_first_interval_days) }}" required>
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">2e aanmaning na (dagen)</label>
                            <input type="number" name="dunning_interval_days" min="1" max="90" class="kt-input w-full" value="{{ old('dunning_interval_days', $settings->dunning_interval_days) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">Factuurkop</h3></div>
                <div class="kt-card-content p-5 space-y-4">
                    <div>
                        <label class="text-sm text-muted-foreground mb-1 block">Factuurtitel</label>
                        <input type="text" name="invoice_title" class="kt-input w-full" value="{{ old('invoice_title', $settings->invoice_title) }}">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">Prefix factuurnummer</label>
                            <input type="text" name="invoice_number_prefix" class="kt-input w-full" value="{{ old('invoice_number_prefix', $settings->invoice_number_prefix) }}" required>
                        </div>
                        <div>
                            <label class="text-sm text-muted-foreground mb-1 block">Afzender e-mail</label>
                            <input type="email" name="sender_email" class="kt-input w-full" value="{{ old('sender_email', $settings->sender_email) }}">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground mb-1 block">Afzendernaam</label>
                        <input type="text" name="sender_name" class="kt-input w-full" value="{{ old('sender_name', $settings->sender_name) }}">
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground mb-1 block">Voettekst</label>
                        <textarea name="invoice_footer" rows="3" class="kt-input w-full">{{ old('invoice_footer', $settings->invoice_footer) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
            </div>
        </div>
    </form>
</div>
@endsection
