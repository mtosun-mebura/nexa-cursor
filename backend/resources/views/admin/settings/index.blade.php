@extends('admin.layouts.app')

@section('title', 'Instellingen')

@section('content')
<style>
    :root {
        --primary-color: #6c757d;
        --primary-light: #9e9e9e;
        --primary-dark: #495057;
        --secondary-color: #f5f5f5;
        --success-color: #4caf50;
        --warning-color: #ff9800;
        --danger-color: #f44336;
        --info-color: #2196f3;
        --light-bg: #fafafa;
        --dark-text: #212121;
        --medium-text: #757575;
        --border-color: #e0e0e0;
        --shadow-light: 0 2px 4px rgba(0,0,0,0.1);
        --shadow-medium: 0 4px 8px rgba(0,0,0,0.12);
        --shadow-heavy: 0 8px 16px rgba(0,0,0,0.15);
        --border-radius: 8px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .material-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-light);
        border: none;
        margin-bottom: 24px;
        transition: var(--transition);
        overflow: hidden;
    }
    
    .material-card:hover {
        box-shadow: var(--shadow-medium);
    }
    
    .material-card .card-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
        border-radius: 0;
        padding: 10px 24px;
        border: none;
        position: relative;
        overflow: hidden;
    }
    
    .material-card .card-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
        transform: translateX(-100%);
        transition: var(--transition);
    }
    
    .material-card .card-header:hover::before {
        transform: translateX(100%);
    }
    
    .material-card .card-body {
        padding: 24px;
    }
    
    .material-btn {
        border-radius: var(--border-radius);
        text-transform: uppercase;
        font-weight: 500;
        letter-spacing: 0.5px;
        padding: 10px 20px;
        border: none;
        transition: var(--transition);
        box-shadow: var(--shadow-light);
        position: relative;
        overflow: hidden;
        cursor: pointer;
        font-size: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .material-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255,255,255,0.3);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: var(--transition);
    }
    
    .material-btn:hover::before {
        width: 300px;
        height: 300px;
    }
    
    .material-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
        text-decoration: none;
    }
    
    .material-btn:active {
        transform: translateY(0);
        box-shadow: var(--shadow-light);
    }
    
    .material-btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
    }
    
    .material-btn-secondary {
        background: var(--light-bg);
        color: var(--dark-text);
        border: 1px solid var(--border-color);
    }
    
    .material-btn-secondary:hover {
        background: var(--secondary-color);
        color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
    }

    .form-control, .form-select {
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        padding: 10px 12px;
        transition: var(--transition);
        background-color: white;
        font-size: 14px;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.25);
        outline: none;
    }
    
    .form-label {
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 8px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: block;
    }

    .form-text {
        font-size: 12px;
        color: var(--medium-text);
        margin-top: 4px;
    }

    .alert {
        border-radius: var(--border-radius);
        border: none;
        padding: 16px 20px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-light);
    }
    
    .alert-success {
        background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
        color: #2e7d32;
        border-left: 4px solid var(--success-color);
    }

    .alert-danger {
        background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
        color: #c62828;
        border-left: 4px solid var(--danger-color);
    }

    .auto-dismiss {
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .auto-dismiss.fade-out {
        animation: slideUp 0.3s ease-in forwards;
    }
    
    @keyframes slideUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }

    .settings-section {
        margin-bottom: 32px;
    }

    .settings-section-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .settings-section-title i {
        color: var(--primary-color);
    }

    .test-email-section {
        display: flex;
        gap: 10px;
        align-items: flex-end;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--border-color);
    }

    .test-email-input {
        flex: 1;
        max-width: 300px;
    }

    /* Verberg pijltjes onderaan de pagina */
    .admin-content::after,
    .admin-content::before,
    .container-fluid::after,
    .container-fluid::before,
    .material-card::after,
    .material-card::before {
        display: none !important;
    }

    /* Verberg SVG pijltjes en navigatie pijltjes */
    svg[viewBox*="24"][viewBox*="24"]:last-child,
    .admin-content svg:last-child,
    .container-fluid svg:last-child,
    svg[viewBox="0 0 24 24"]:last-of-type,
    .material-card svg:last-child,
    .row svg:last-child,
    .col-12 svg:last-child {
        display: none !important;
    }

    /* Verberg alle pijltjes onderaan de pagina container */
    .container-fluid > svg,
    .admin-content > svg,
    .row > svg,
    .col-12 > svg {
        display: none !important;
    }

    /* Verberg alle pijltjes/arrow elementen onderaan */
    .container-fluid svg:last-of-type,
    .admin-content svg:last-of-type,
    svg[style*="position"][style*="bottom"],
    svg[style*="fixed"],
    .arrow,
    .arrow-right,
    [class*="arrow"],
    [id*="arrow"] {
        display: none !important;
    }
</style>

<div class="container-fluid">
    <!-- Success Alert -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show auto-dismiss" role="alert" id="success-alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Er zijn validatiefouten opgetreden:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <!-- Mail Server Instellingen -->
            <div class="material-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope me-2"></i> Mail Server Instellingen
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.settings.mail.update') }}" id="mail-settings-form">
                        @csrf
                        
                        <div class="settings-section">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="MAIL_MAILER" class="form-label">
                                        Mailer <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="MAIL_MAILER" name="MAIL_MAILER" required>
                                        <option value="log" {{ $mailSettings['MAIL_MAILER'] === 'log' ? 'selected' : '' }}>Log (alleen loggen)</option>
                                        <option value="smtp" {{ $mailSettings['MAIL_MAILER'] === 'smtp' ? 'selected' : '' }}>SMTP</option>
                                        <option value="sendmail" {{ $mailSettings['MAIL_MAILER'] === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                        <option value="mailgun" {{ $mailSettings['MAIL_MAILER'] === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                                        <option value="ses" {{ $mailSettings['MAIL_MAILER'] === 'ses' ? 'selected' : '' }}>Amazon SES</option>
                                        <option value="postmark" {{ $mailSettings['MAIL_MAILER'] === 'postmark' ? 'selected' : '' }}>Postmark</option>
                                        <option value="resend" {{ $mailSettings['MAIL_MAILER'] === 'resend' ? 'selected' : '' }}>Resend</option>
                                    </select>
                                    <small class="form-text">Selecteer de mail transport methode</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="MAIL_HOST" class="form-label">
                                        SMTP Host
                                    </label>
                                    <input type="text" class="form-control" id="MAIL_HOST" name="MAIL_HOST" 
                                           value="{{ old('MAIL_HOST', $mailSettings['MAIL_HOST']) }}" 
                                           placeholder="smtp.example.com">
                                    <small class="form-text">SMTP server hostname</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="MAIL_PORT" class="form-label">
                                        SMTP Poort
                                    </label>
                                    <input type="number" class="form-control" id="MAIL_PORT" name="MAIL_PORT" 
                                           value="{{ old('MAIL_PORT', $mailSettings['MAIL_PORT']) }}" 
                                           placeholder="587" min="1" max="65535">
                                    <small class="form-text">Meestal 587 (TLS) of 465 (SSL)</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="MAIL_ENCRYPTION" class="form-label">
                                        Encryptie
                                    </label>
                                    <select class="form-select" id="MAIL_ENCRYPTION" name="MAIL_ENCRYPTION">
                                        <option value="tls" {{ $mailSettings['MAIL_ENCRYPTION'] === 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="ssl" {{ $mailSettings['MAIL_ENCRYPTION'] === 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="null" {{ $mailSettings['MAIL_ENCRYPTION'] === 'null' || empty($mailSettings['MAIL_ENCRYPTION']) ? 'selected' : '' }}>Geen</option>
                                    </select>
                                    <small class="form-text">Encryptie type voor SMTP verbinding</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="MAIL_USERNAME" class="form-label">
                                        SMTP Gebruikersnaam
                                    </label>
                                    <input type="text" class="form-control" id="MAIL_USERNAME" name="MAIL_USERNAME" 
                                           value="{{ old('MAIL_USERNAME', $mailSettings['MAIL_USERNAME']) }}" 
                                           placeholder="your-username">
                                    <small class="form-text">SMTP authenticatie gebruikersnaam</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="MAIL_PASSWORD" class="form-label">
                                        SMTP Wachtwoord
                                    </label>
                                    <input type="password" class="form-control" id="MAIL_PASSWORD" name="MAIL_PASSWORD" 
                                           value="" placeholder="Laat leeg om niet te wijzigen">
                                    <small class="form-text">Laat leeg om het huidige wachtwoord te behouden</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="MAIL_FROM_ADDRESS" class="form-label">
                                        From Adres <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="MAIL_FROM_ADDRESS" name="MAIL_FROM_ADDRESS" 
                                           value="{{ old('MAIL_FROM_ADDRESS', $mailSettings['MAIL_FROM_ADDRESS']) }}" 
                                           placeholder="noreply@nexa-skillmatching.nl" required>
                                    <small class="form-text">E-mailadres waarvan emails worden verzonden</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="MAIL_FROM_NAME" class="form-label">
                                        From Naam <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="MAIL_FROM_NAME" name="MAIL_FROM_NAME" 
                                           value="{{ old('MAIL_FROM_NAME', $mailSettings['MAIL_FROM_NAME']) }}" 
                                           placeholder="NEXA Skillmatching" required>
                                    <small class="form-text">Naam die wordt getoond als afzender</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="material-btn material-btn-primary">
                                <i class="fas fa-save"></i> Instellingen Opslaan
                            </button>
                            
                            <div class="test-email-section">
                                <div class="test-email-input">
                                    <label for="test-email-input" class="form-label">Test Email</label>
                                    <input type="email" class="form-control" id="test-email-input" 
                                           placeholder="test@example.com">
                                </div>
                                <div>
                                    <label class="form-label" style="visibility: hidden;">&nbsp;</label>
                                    <button type="button" class="material-btn material-btn-secondary" id="test-email-btn">
                                        <i class="fas fa-paper-plane"></i> Verstuur Test
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Toekomstige instellingen secties kunnen hier worden toegevoegd -->
            <!-- 
            <div class="material-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2"></i> Andere Instellingen
                    </h5>
                </div>
                <div class="card-body">
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
            testEmailBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verzenden...';
            
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
                testEmailBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Verstuur Test';
            });
        });
    }
});
</script>
@endsection
