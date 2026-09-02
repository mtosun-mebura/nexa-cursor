@extends('admin.companies.wizard.layout')

@section('title', 'Stap 7 — Mailserver')

@section('wizard_content')
<form method="post" action="{{ route('admin.companies.wizard.submit-step', [$company, 7]) }}">
    @csrf
    <x-error-card :errors="$errors" />

    <div class="kt-card min-w-full mb-6">
        <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
            <h3 class="kt-card-title mb-0">Mailserver van deze tenant</h3>
        </div>
        <div class="kt-card-content p-5 lg:p-6">
            <p class="text-sm text-secondary-foreground mb-4">
                Optioneel. Als je geen eigen mailserver invult, gebruikt deze tenant de NEXA Suite-mailserver.
                Je moet deze stap wel doorlopen om verder te gaan.
            </p>
            @if(!empty($mailUsingPlatformFallback))
                <div class="kt-alert kt-alert-primary mb-4" role="status">
                    <i class="ki-filled ki-information-2 me-2"></i>
                    Deze tenant heeft nog geen eigen mailserver. Uitgaande mail valt terug op NEXA Suite.
                </div>
            @endif
        </div>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Mailer</td>
                    <td class="min-w-48 w-full">
                        <select class="kt-select @error('MAIL_MAILER') border-destructive @enderror" id="MAIL_MAILER" name="MAIL_MAILER">
                            <option value="" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === '' ? 'selected' : '' }}>NEXA Suite-mailserver (standaard)</option>
                            <option value="log" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === 'log' ? 'selected' : '' }}>Log (alleen loggen)</option>
                            <option value="smtp" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === 'smtp' ? 'selected' : '' }}>SMTP</option>
                            <option value="sendmail" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                            <option value="mailgun" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                            <option value="ses" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === 'ses' ? 'selected' : '' }}>Amazon SES</option>
                            <option value="postmark" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === 'postmark' ? 'selected' : '' }}>Postmark</option>
                            <option value="resend" {{ old('MAIL_MAILER', $mailSettings['MAIL_MAILER']) === 'resend' ? 'selected' : '' }}>Resend</option>
                        </select>
                        <div class="text-xs text-muted-foreground mt-1">Kies alleen een eigen mailer als deze tenant niet de platformmail mag gebruiken.</div>
                        @error('MAIL_MAILER')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">SMTP Host</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input @error('MAIL_HOST') border-destructive @enderror" name="MAIL_HOST" value="{{ old('MAIL_HOST', $mailSettings['MAIL_HOST']) }}" placeholder="smtp.example.com">
                        @error('MAIL_HOST')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">SMTP Poort</td>
                    <td class="min-w-48 w-full">
                        <input type="number" class="kt-input @error('MAIL_PORT') border-destructive @enderror" name="MAIL_PORT" value="{{ old('MAIL_PORT', $mailSettings['MAIL_PORT']) }}" placeholder="587" min="1" max="65535">
                        @error('MAIL_PORT')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Encryptie</td>
                    <td class="min-w-48 w-full">
                        <select class="kt-select" name="MAIL_ENCRYPTION">
                            <option value="tls" {{ old('MAIL_ENCRYPTION', $mailSettings['MAIL_ENCRYPTION']) === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ old('MAIL_ENCRYPTION', $mailSettings['MAIL_ENCRYPTION']) === 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="null" {{ old('MAIL_ENCRYPTION', $mailSettings['MAIL_ENCRYPTION']) === 'null' ? 'selected' : '' }}>Geen</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">SMTP Gebruikersnaam</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input" name="MAIL_USERNAME" value="{{ old('MAIL_USERNAME', $mailSettings['MAIL_USERNAME']) }}" autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">SMTP Wachtwoord</td>
                    <td class="min-w-48 w-full">
                        <input type="password" class="kt-input" name="MAIL_PASSWORD" value="" autocomplete="new-password" placeholder="Laat leeg om niet te wijzigen">
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">From adres</td>
                    <td class="min-w-48 w-full">
                        <input type="email" class="kt-input @error('MAIL_FROM_ADDRESS') border-destructive @enderror" name="MAIL_FROM_ADDRESS" value="{{ old('MAIL_FROM_ADDRESS', $mailSettings['MAIL_FROM_ADDRESS']) }}">
                        @error('MAIL_FROM_ADDRESS')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">From naam</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input @error('MAIL_FROM_NAME') border-destructive @enderror" name="MAIL_FROM_NAME" value="{{ old('MAIL_FROM_NAME', $mailSettings['MAIL_FROM_NAME']) }}">
                        @error('MAIL_FROM_NAME')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <x-wizard.footer-actions :current-step="$currentStep" :company="$company">
        <button type="submit" name="skip_config" value="1" class="kt-btn kt-btn-outline">
            Overslaan
        </button>
        <button type="submit" class="kt-btn kt-btn-primary">
            Volgende
            <i class="ki-filled ki-arrow-right ms-2"></i>
        </button>
    </x-wizard.footer-actions>
</form>
@endsection
