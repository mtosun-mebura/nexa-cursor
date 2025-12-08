@extends('admin.layouts.app')

@section('title', 'Instellingen')

@section('content')


<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Instellingen
            </h1>
        </div>
    </div>
    <!-- Success Alert -->
    @if(session('success'))
        <div class="kt-alert kt-alert-success auto-dismiss mb-5" role="alert" id="success-alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-kt-dismiss="alert">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <i class="ki-filled ki-information me-2"></i>
            {{ session('error') }}
            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-kt-dismiss="alert">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <i class="ki-filled ki-information me-2"></i>
            <strong>Er zijn validatiefouten opgetreden:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-kt-dismiss="alert">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5">
        <div class="w-full">
            <!-- Mail Server Instellingen -->
            <div class="kt-card">
                <div class="kt-card-header flex justify-between items-center">
                    <h3 class="kt-card-title">
                        <i class="ki-filled ki-sms me-2"></i> Mail Server Instellingen
                    </h3>
                </div>
                <div class="kt-card-content">
                    <form method="POST" action="{{ route('admin.settings.mail.update') }}" id="mail-settings-form">
                        @csrf
                        
                        <div class="flex flex-col gap-5 lg:gap-7.5">
                            <div class="grid lg:grid-cols-2 gap-5 lg:gap-7.5">
                                <div class="w-full">
                                    <div class="flex items-center py-3">
                                        <label for="MAIL_MAILER" class="kt-form-label flex items-center gap-1 max-w-56">
                                            Mailer <span class="text-destructive">*</span>
                                        </label>
                                        <select class="kt-select" id="MAIL_MAILER" name="MAIL_MAILER" required>
                                        <option value="log" {{ $mailSettings['MAIL_MAILER'] === 'log' ? 'selected' : '' }}>Log (alleen loggen)</option>
                                        <option value="smtp" {{ $mailSettings['MAIL_MAILER'] === 'smtp' ? 'selected' : '' }}>SMTP</option>
                                        <option value="sendmail" {{ $mailSettings['MAIL_MAILER'] === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                        <option value="mailgun" {{ $mailSettings['MAIL_MAILER'] === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                                        <option value="ses" {{ $mailSettings['MAIL_MAILER'] === 'ses' ? 'selected' : '' }}>Amazon SES</option>
                                        <option value="postmark" {{ $mailSettings['MAIL_MAILER'] === 'postmark' ? 'selected' : '' }}>Postmark</option>
                                        <option value="resend" {{ $mailSettings['MAIL_MAILER'] === 'resend' ? 'selected' : '' }}>Resend</option>
                                    </select>
                                    <div class="text-xs text-muted-foreground mt-1">Selecteer de mail transport methode</div>
                                </div>
                                <div class="w-full">
                                    <div class="flex items-center py-3">
                                        <label for="MAIL_HOST" class="kt-form-label flex items-center gap-1 max-w-56">
                                            SMTP Host
                                        </label>
                                        <input type="text" class="kt-input" id="MAIL_HOST" name="MAIL_HOST" 
                                               value="{{ old('MAIL_HOST', $mailSettings['MAIL_HOST']) }}" 
                                               placeholder="smtp.example.com">
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-1">SMTP server hostname</div>
                                </div>
                            </div>

                            <div class="grid lg:grid-cols-2 gap-5 lg:gap-7.5">
                                <div class="w-full">
                                    <div class="flex items-center py-3">
                                        <label for="MAIL_PORT" class="kt-form-label flex items-center gap-1 max-w-56">
                                            SMTP Poort
                                        </label>
                                        <input type="number" class="kt-input" id="MAIL_PORT" name="MAIL_PORT" 
                                               value="{{ old('MAIL_PORT', $mailSettings['MAIL_PORT']) }}" 
                                               placeholder="587" min="1" max="65535">
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-1">Meestal 587 (TLS) of 465 (SSL)</div>
                                </div>
                                <div class="w-full">
                                    <div class="flex items-center py-3">
                                        <label for="MAIL_ENCRYPTION" class="kt-form-label flex items-center gap-1 max-w-56">
                                            Encryptie
                                        </label>
                                        <select class="kt-select" id="MAIL_ENCRYPTION" name="MAIL_ENCRYPTION">
                                            <option value="tls" {{ $mailSettings['MAIL_ENCRYPTION'] === 'tls' ? 'selected' : '' }}>TLS</option>
                                            <option value="ssl" {{ $mailSettings['MAIL_ENCRYPTION'] === 'ssl' ? 'selected' : '' }}>SSL</option>
                                            <option value="null" {{ $mailSettings['MAIL_ENCRYPTION'] === 'null' || empty($mailSettings['MAIL_ENCRYPTION']) ? 'selected' : '' }}>Geen</option>
                                        </select>
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-1">Encryptie type voor SMTP verbinding</div>
                                </div>
                            </div>

                            <div class="grid lg:grid-cols-2 gap-5 lg:gap-7.5">
                                <div class="w-full">
                                    <div class="flex items-center py-3">
                                        <label for="MAIL_USERNAME" class="kt-form-label flex items-center gap-1 max-w-56">
                                            SMTP Gebruikersnaam
                                        </label>
                                        <input type="text" class="kt-input" id="MAIL_USERNAME" name="MAIL_USERNAME" 
                                               value="{{ old('MAIL_USERNAME', $mailSettings['MAIL_USERNAME']) }}" 
                                               placeholder="your-username">
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-1">SMTP authenticatie gebruikersnaam</div>
                                </div>
                                <div class="w-full">
                                    <div class="flex items-center py-3">
                                        <label for="MAIL_PASSWORD" class="kt-form-label flex items-center gap-1 max-w-56">
                                            SMTP Wachtwoord
                                        </label>
                                        <input type="password" class="kt-input" id="MAIL_PASSWORD" name="MAIL_PASSWORD" 
                                               value="" placeholder="Laat leeg om niet te wijzigen">
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-1">Laat leeg om het huidige wachtwoord te behouden</div>
                                </div>
                            </div>

                            <div class="grid lg:grid-cols-2 gap-5 lg:gap-7.5">
                                <div class="w-full">
                                    <div class="flex items-center py-3">
                                        <label for="MAIL_FROM_ADDRESS" class="kt-form-label flex items-center gap-1 max-w-56">
                                            From Adres <span class="text-destructive">*</span>
                                        </label>
                                        <input type="email" class="kt-input" id="MAIL_FROM_ADDRESS" name="MAIL_FROM_ADDRESS" 
                                               value="{{ old('MAIL_FROM_ADDRESS', $mailSettings['MAIL_FROM_ADDRESS']) }}" 
                                               placeholder="noreply@nexa-skillmatching.nl" required>
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-1">E-mailadres waarvan emails worden verzonden</div>
                                </div>
                                <div class="w-full">
                                    <div class="flex items-center py-3">
                                        <label for="MAIL_FROM_NAME" class="kt-form-label flex items-center gap-1 max-w-56">
                                            From Naam <span class="text-destructive">*</span>
                                        </label>
                                        <input type="text" class="kt-input" id="MAIL_FROM_NAME" name="MAIL_FROM_NAME" 
                                               value="{{ old('MAIL_FROM_NAME', $mailSettings['MAIL_FROM_NAME']) }}" 
                                               placeholder="NEXA Skillmatching" required>
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-1">Naam die wordt getoond als afzender</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center gap-5 mt-7.5 pt-7.5 border-t border-border">
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-check me-2"></i> Instellingen Opslaan
                            </button>
                            
                            <div class="flex items-end gap-2.5">
                                <div class="flex flex-col">
                                    <label for="test-email-input" class="kt-form-label text-sm mb-1">Test Email</label>
                                    <input type="email" class="kt-input" id="test-email-input" 
                                           placeholder="test@example.com">
                                </div>
                                <button type="button" class="kt-btn kt-btn-outline" id="test-email-btn">
                                    <i class="ki-filled ki-send me-2"></i> Verstuur Test
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Toekomstige instellingen secties kunnen hier worden toegevoegd -->
            <!-- 
            <div class="kt-card">
                <div class="kt-card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2"></i> Andere Instellingen
                    </h5>
                </div>
                <div class="kt-card-content">
                    <!-- Hier kunnen andere instellingen worden toegevoegd -->
                </div>
            </div>
            -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss success alert after 5 seconds
    const successAlert = document.getElementById('success-alert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.classList.add('fade-out');
            setTimeout(function() {
                successAlert.remove();
            }, 300);
        }, 5000);
    }

    // Test email functionality
    const testEmailBtn = document.getElementById('test-email-btn');
    const testEmailInput = document.getElementById('test-email-input');
    
    if (testEmailBtn && testEmailInput) {
        testEmailBtn.addEventListener('click', function() {
            const email = testEmailInput.value.trim();
            
            if (!email) {
                alert('Vul een e-mailadres in om te testen.');
                return;
            }
            
            if (!email.includes('@')) {
                alert('Vul een geldig e-mailadres in.');
                return;
            }
            
            // Disable button during request
            testEmailBtn.disabled = true;
            testEmailBtn.innerHTML = '<i class="ki-filled ki-arrows-circle"></i> Verzenden...';
            
            fetch('{{ route("admin.settings.mail.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    test_email: email
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ ' + data.message);
                } else {
                    alert('✗ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Er is een fout opgetreden bij het testen van de email.');
            })
            .finally(() => {
                testEmailBtn.disabled = false;
                testEmailBtn.innerHTML = '<i class="ki-filled ki-send me-2"></i> Verstuur Test';
            });
        });
    }
});
</script>
@endsection
