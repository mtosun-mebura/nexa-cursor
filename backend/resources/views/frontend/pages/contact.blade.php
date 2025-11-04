@extends('frontend.layouts.app')

@section('title', 'Contact - NEXA Skillmatching')
@section('description', 'Neem contact met ons op voor vragen of meer informatie over NEXA Skillmatching.')

@section('content')
<style>
    .border-green-500 {
        border-color: #10b981 !important;
    }
    .dark .border-green-500 {
        border-color: #10b981 !important;
    }
</style>
<!-- Hero Section -->
<section class="bg-gradient-to-br from-blue-600 via-blue-700 to-purple-800 dark:from-gray-900 dark:via-blue-900 dark:to-purple-900 section-padding relative overflow-hidden">
    <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
    <div class="container-custom relative z-10 text-center text-white">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Contact</h1>
        <p class="text-lg md:text-xl max-w-3xl mx-auto">
            Heb je vragen of wil je meer informatie? Neem gerust contact met ons op!
        </p>
    </div>
</section>

<!-- Contact Form Section -->
<section class="section-padding bg-white dark:bg-gray-900">
    <div class="container-custom">
        <div class="!max-w-7xl mx-auto" style="max-width: 80rem !important;">
            <div class="mb-12">
                <div class="max-w-2xl mx-auto">
                    @if(session('success'))
                    <div id="success-message" class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg transition-opacity duration-300">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-green-800 dark:text-green-200">{{ session('success') }}</p>
                        </div>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-red-800 dark:text-red-200">{{ session('error') }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="card p-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Stuur ons een bericht</h2>
                        
                        <form method="POST" action="{{ route('contact.submit') }}" id="contact-form">
                            @csrf
                            
                            <!-- Honeypot captcha (hidden field) -->
                            <input type="text" name="website" style="display: none;" tabindex="-1" autocomplete="off">
                            
                            <!-- Submit time for bot detection (set when page loads) -->
                            <input type="hidden" name="_submit_time" id="_submit_time" value="{{ time() }}">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- Voornaam -->
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Voornaam <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           id="first_name" 
                                           name="first_name" 
                                           value="{{ old('first_name') }}"
                                           required
                                           minlength="2"
                                           maxlength="255"
                                           pattern="[\p{L}\s\-\'\.]+"
                                           class="input w-full @error('first_name') border-red-500 @enderror">
                                    @error('first_name')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs min-h-[20px] text-gray-500 dark:text-gray-400" id="first_name-hint" style="display: block; visibility: visible;">Minimaal 2 karakters, alleen letters</p>
                                </div>
                                
                                <!-- Achternaam -->
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Achternaam <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           id="last_name" 
                                           name="last_name" 
                                           value="{{ old('last_name') }}"
                                           required
                                           minlength="2"
                                           maxlength="255"
                                           pattern="[\p{L}\s\-\'\.]+"
                                           class="input w-full @error('last_name') border-red-500 @enderror">
                                    @error('last_name')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs min-h-[20px] text-gray-500 dark:text-gray-400" id="last_name-hint" style="display: block; visibility: visible;">Minimaal 2 karakters, alleen letters</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        E-mailadres <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           value="{{ old('email') }}"
                                           required
                                           maxlength="255"
                                           class="input w-full @error('email') border-red-500 @enderror">
                                    @error('email')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs min-h-[20px] text-gray-500 dark:text-gray-400" id="email-hint" style="display: block; visibility: visible;">Voer een geldig e-mailadres in</p>
                                </div>
                                
                                <!-- Telefoon (optioneel) -->
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Telefoonnummer (optioneel)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">(10 cijfers, bijv. 0612345678)</span>
                                    </label>
                                    <input type="tel" 
                                           id="phone" 
                                           name="phone" 
                                           value="{{ old('phone') }}"
                                           pattern="[0-9]{10}"
                                           maxlength="10"
                                           placeholder="0612345678"
                                           class="input w-full @error('phone') border-red-500 @enderror">
                                    @error('phone')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs min-h-[20px] text-gray-500 dark:text-gray-400" id="phone-hint" style="display: block; visibility: visible;">Alleen cijfers, geen spaties of streepjes</p>
                                </div>
                            </div>
                            
                            <!-- Omschrijving -->
                            <div class="mb-6">
                                <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Omschrijving <span class="text-red-500">*</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">(minimaal 10, maximaal 1000 karakters)</span>
                                </label>
                                <textarea id="message" 
                                          name="message" 
                                          rows="6" 
                                          minlength="10"
                                          maxlength="1000"
                                          required
                                          class="input w-full resize-y @error('message') border-red-500 @enderror"
                                          placeholder="Vertel ons waarover je contact wilt opnemen...">{{ old('message') }}</textarea>
                                <div class="flex justify-between items-center mt-2">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        @error('message')
                                            <span class="text-red-600 dark:text-red-400">{{ $message }}</span>
                                        @else
                                            <span id="char-count">0</span> / 1000 karakters
                                        @enderror
                                    </p>
                                    <p class="text-xs min-h-[20px] text-gray-500 dark:text-gray-400" id="message-hint" style="display: block; visibility: visible;">Minimaal 10 karakters</p>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="flex items-center justify-between">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    <span class="text-red-500">*</span> Verplichte velden
                                </p>
                                <button type="submit" class="btn btn-primary" id="submit-btn">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                    <span id="submit-text">Versturen</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Contact form validation script loaded');
    
    // Auto-hide success message after 5 seconds
    const successMessage = document.getElementById('success-message');
    if (successMessage) {
        setTimeout(function() {
            successMessage.style.opacity = '0';
            setTimeout(function() {
                successMessage.style.display = 'none';
            }, 300); // Wait for fade-out animation
        }, 5000); // 5 seconds
    }
    
    // Set submit time when page loads (for bot detection)
    const submitTimeField = document.getElementById('_submit_time');
    if (submitTimeField) {
        submitTimeField.value = Math.floor(Date.now() / 1000);
    }
    
    // Real-time validation functions - direct validation on input
    function validateFirstName(value) {
        const hint = document.getElementById('first_name-hint');
        const field = document.getElementById('first_name');
        
        if (!hint || !field) return false;
        
        // Direct validation - no waiting
        if (!value || value.trim().length < 2) {
            hint.innerHTML = '<span class="text-gray-500 dark:text-gray-400">Minimaal 2 karakters vereist</span>';
            hint.classList.remove('text-green-600', 'dark:text-green-400');
            hint.classList.add('text-gray-500', 'dark:text-gray-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            field.classList.remove('border-green-500');
            return false;
        }
        
        const regex = /^[\p{L}\s\-'\.]+$/u;
        if (!regex.test(value)) {
            hint.innerHTML = '<span class="text-orange-500 dark:text-orange-400">Alleen letters, spaties, streepjes, apostrofs en punten toegestaan</span>';
            hint.classList.remove('text-green-600', 'dark:text-green-400', 'text-gray-500', 'dark:text-gray-400');
            hint.classList.add('text-orange-500', 'dark:text-orange-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            field.classList.remove('border-green-500');
            return false;
        }
        
        // Valid - show green checkmark with "Voldoet aan validatie"
        hint.innerHTML = '<span class="text-green-600 dark:text-green-400" style="color: #10b981 !important;">✓ Voldoet aan validatie</span>';
        hint.classList.remove('text-gray-500', 'dark:text-gray-400', 'text-orange-500', 'dark:text-orange-400', 'text-red-500');
        hint.classList.add('text-green-600', 'dark:text-green-400');
        hint.style.display = 'block';
        hint.style.visibility = 'visible';
        hint.style.color = '#10b981';
        field.classList.add('border-green-500');
        field.style.borderColor = '#10b981';
        console.log('First name validated successfully');
        return true;
    }
    
    function validateLastName(value) {
        const hint = document.getElementById('last_name-hint');
        const field = document.getElementById('last_name');
        
        if (!hint || !field) return false;
        
        // Direct validation - no waiting
        if (!value || value.trim().length < 2) {
            hint.innerHTML = '<span class="text-gray-500 dark:text-gray-400">Minimaal 2 karakters vereist</span>';
            hint.classList.remove('text-green-600', 'dark:text-green-400');
            hint.classList.add('text-gray-500', 'dark:text-gray-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            field.classList.remove('border-green-500');
            return false;
        }
        
        const regex = /^[\p{L}\s\-'\.]+$/u;
        if (!regex.test(value)) {
            hint.innerHTML = '<span class="text-orange-500 dark:text-orange-400">Alleen letters, spaties, streepjes, apostrofs en punten toegestaan</span>';
            hint.classList.remove('text-green-600', 'dark:text-green-400', 'text-gray-500', 'dark:text-gray-400');
            hint.classList.add('text-orange-500', 'dark:text-orange-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            field.classList.remove('border-green-500');
            return false;
        }
        
        // Valid - show green checkmark with "Voldoet aan validatie"
        hint.innerHTML = '<span class="text-green-600 dark:text-green-400" style="color: #10b981 !important;">✓ Voldoet aan validatie</span>';
        hint.classList.remove('text-gray-500', 'dark:text-gray-400', 'text-orange-500', 'dark:text-orange-400', 'text-red-500');
        hint.classList.add('text-green-600', 'dark:text-green-400');
        hint.style.display = 'block';
        hint.style.visibility = 'visible';
        hint.style.color = '#10b981';
        field.classList.add('border-green-500');
        field.style.borderColor = '#10b981';
        console.log('Last name validated successfully');
        return true;
    }
    
    function validateEmail(value) {
        const hint = document.getElementById('email-hint');
        const field = document.getElementById('email');
        
        if (!hint || !field) return false;
        
        // Direct validation - no waiting
        if (!value || value.trim().length === 0) {
            hint.innerHTML = '<span class="text-gray-500 dark:text-gray-400">Voer een e-mailadres in</span>';
            hint.classList.remove('text-green-600', 'dark:text-green-400');
            hint.classList.add('text-gray-500', 'dark:text-gray-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            field.classList.remove('border-green-500');
            return false;
        }
        
        // Strikte email validatie - moet @ bevatten en een punt na de @
        // Basis check: moet @ bevatten
        if (!value.includes('@')) {
            hint.innerHTML = '<span class="text-orange-500 dark:text-orange-400">E-mailadres moet een @ bevatten</span>';
            hint.classList.remove('text-green-600', 'dark:text-green-400', 'text-gray-500', 'dark:text-gray-400');
            hint.classList.add('text-orange-500', 'dark:text-orange-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            field.classList.remove('border-green-500');
            return false;
        }
        
        // Split op @ om te controleren
        const parts = value.split('@');
        if (parts.length !== 2) {
            hint.innerHTML = '<span class="text-orange-500 dark:text-orange-400">Ongeldig e-mailadres formaat</span>';
            hint.classList.remove('text-green-600', 'dark:text-green-400', 'text-gray-500', 'dark:text-gray-400');
            hint.classList.add('text-orange-500', 'dark:text-orange-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            field.classList.remove('border-green-500');
            return false;
        }
        
        const localPart = parts[0];
        const domainPart = parts[1];
        
        // Check local part (voor @)
        if (!localPart || localPart.length === 0 || localPart.length > 64) {
            hint.innerHTML = '<span class="text-orange-500 dark:text-orange-400">Ongeldig deel voor @</span>';
            hint.classList.remove('text-green-600', 'dark:text-green-400', 'text-gray-500', 'dark:text-gray-400');
            hint.classList.add('text-orange-500', 'dark:text-orange-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            field.classList.remove('border-green-500');
            return false;
        }
        
        // Check domain part (na @) - moet een punt bevatten
        if (!domainPart || domainPart.length === 0) {
            hint.innerHTML = '<span class="text-orange-500 dark:text-orange-400">E-mailadres moet een domein bevatten na @</span>';
            hint.classList.remove('text-green-600', 'dark:text-green-400', 'text-gray-500', 'dark:text-gray-400');
            hint.classList.add('text-orange-500', 'dark:text-orange-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            field.classList.remove('border-green-500');
            return false;
        }
        
        if (!domainPart.includes('.')) {
            hint.innerHTML = '<span class="text-orange-500 dark:text-orange-400">Domein moet een punt bevatten (bijv. .nl of .com)</span>';
            hint.classList.remove('text-green-600', 'dark:text-green-400', 'text-gray-500', 'dark:text-gray-400');
            hint.classList.add('text-orange-500', 'dark:text-orange-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            field.classList.remove('border-green-500');
            return false;
        }
        
        // Check of er een TLD is (na de laatste punt)
        const domainParts = domainPart.split('.');
        if (domainParts.length < 2 || domainParts[domainParts.length - 1].length < 2) {
            hint.innerHTML = '<span class="text-orange-500 dark:text-orange-400">Domein moet een geldige TLD hebben (bijv. .nl, .com)</span>';
            hint.classList.remove('text-green-600', 'dark:text-green-400', 'text-gray-500', 'dark:text-gray-400');
            hint.classList.add('text-orange-500', 'dark:text-orange-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            field.classList.remove('border-green-500');
            return false;
        }
        
        // Strikte email regex voor finale validatie
        const emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/;
        if (!emailRegex.test(value)) {
            hint.innerHTML = '<span class="text-orange-500 dark:text-orange-400">Voer een geldig e-mailadres in (bijv. naam@domein.nl)</span>';
            hint.classList.remove('text-green-600', 'dark:text-green-400', 'text-gray-500', 'dark:text-gray-400');
            hint.classList.add('text-orange-500', 'dark:text-orange-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            field.classList.remove('border-green-500');
            return false;
        }
        
        // Valid - show green checkmark with "Voldoet aan validatie"
        hint.innerHTML = '<span class="text-green-600 dark:text-green-400" style="color: #10b981 !important;">✓ Voldoet aan validatie</span>';
        hint.classList.remove('text-gray-500', 'dark:text-gray-400', 'text-orange-500', 'dark:text-orange-400', 'text-red-500');
        hint.classList.add('text-green-600', 'dark:text-green-400');
        hint.style.display = 'block';
        hint.style.visibility = 'visible';
        hint.style.color = '#10b981';
        field.classList.add('border-green-500');
        field.style.borderColor = '#10b981';
        console.log('Email validated successfully');
        return true;
    }
    
    function validatePhone(value) {
        const hint = document.getElementById('phone-hint');
        const field = document.getElementById('phone');
        
        if (!hint) return false;
        
        // Remove all non-numeric characters
        let cleanValue = value.replace(/[^0-9]/g, '');
        
        // Limit to 10 digits
        if (cleanValue.length > 10) {
            cleanValue = cleanValue.substring(0, 10);
        }
        
        if (field) {
            field.value = cleanValue;
        }
        
        // Direct validation - no waiting
        if (!cleanValue || cleanValue.length === 0) {
            hint.innerHTML = '<span class="text-gray-500 dark:text-gray-400">Alleen cijfers, geen spaties of streepjes</span>';
            hint.classList.remove('text-green-600', 'dark:text-green-400', 'text-orange-500', 'dark:text-orange-400');
            hint.classList.add('text-gray-500', 'dark:text-gray-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            if (field) field.classList.remove('border-green-500');
            return false;
        }
        
        if (cleanValue.length === 10) {
            // Valid - show green checkmark with "Voldoet aan validatie"
            hint.innerHTML = '<span class="text-green-600 dark:text-green-400" style="color: #10b981 !important;">✓ Voldoet aan validatie</span>';
            hint.classList.remove('text-gray-500', 'dark:text-gray-400', 'text-red-500', 'text-orange-500', 'dark:text-orange-400');
            hint.classList.add('text-green-600', 'dark:text-green-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            hint.style.color = '#10b981';
            if (field) {
                field.classList.add('border-green-500');
                field.style.borderColor = '#10b981';
            }
            console.log('Phone validated successfully');
            return true;
        } else {
            hint.innerHTML = `<span class="text-orange-500 dark:text-orange-400">${cleanValue.length}/10 cijfers</span>`;
            hint.classList.remove('text-gray-500', 'dark:text-gray-400', 'text-green-600', 'dark:text-green-400');
            hint.classList.add('text-orange-500', 'dark:text-orange-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            if (field) field.classList.remove('border-green-500');
            return false;
        }
    }
    
    function validateMessage(value) {
        const hint = document.getElementById('message-hint');
        const charCount = document.getElementById('char-count');
        const field = document.getElementById('message');
        
        const length = value.length;
        if (charCount) {
            charCount.textContent = length;
        }
        
        if (!value || length < 10) {
            hint.innerHTML = `<span class="text-orange-500 dark:text-orange-400">Minimaal 10 karakters vereist (${length}/10)</span>`;
            hint.classList.remove('text-green-600', 'dark:text-green-400', 'text-gray-500', 'dark:text-gray-400', 'text-red-500');
            hint.classList.add('text-orange-500', 'dark:text-orange-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            if (field) field.classList.remove('border-green-500');
            return false;
        }
        
        if (length > 1000) {
            hint.innerHTML = '<span class="text-red-500 dark:text-red-400">Maximaal 1000 karakters toegestaan</span>';
            hint.classList.remove('text-green-600', 'dark:text-green-400', 'text-gray-500', 'dark:text-gray-400', 'text-orange-500', 'dark:text-orange-400');
            hint.classList.add('text-red-500', 'dark:text-red-400');
            hint.style.display = 'block';
            hint.style.visibility = 'visible';
            if (field) field.classList.remove('border-green-500');
            return false;
        }
        
        hint.innerHTML = '<span class="text-green-600 dark:text-green-400" style="color: #10b981 !important;">✓ Voldoet aan validatie</span>';
        hint.classList.remove('text-gray-500', 'dark:text-gray-400', 'text-orange-500', 'dark:text-orange-400', 'text-red-500', 'dark:text-red-400');
        hint.classList.add('text-green-600', 'dark:text-green-400');
        hint.style.display = 'block';
        hint.style.visibility = 'visible';
        hint.style.color = '#10b981';
        if (field) {
            field.classList.add('border-green-500');
            field.style.borderColor = '#10b981';
        }
        console.log('Message validated successfully');
        return true;
    }
    
    // Attach real-time validation to all fields
    const firstNameField = document.getElementById('first_name');
    const lastNameField = document.getElementById('last_name');
    const emailField = document.getElementById('email');
    const phoneField = document.getElementById('phone');
    const messageField = document.getElementById('message');
    
    // Attach immediate validation on input (keypress) for all fields
    if (firstNameField) {
        firstNameField.addEventListener('input', function(e) {
            console.log('First name input:', this.value);
            validateFirstName(this.value);
        });
        firstNameField.addEventListener('keyup', function(e) {
            validateFirstName(this.value);
        });
        firstNameField.addEventListener('blur', function(e) {
            validateFirstName(this.value);
        });
        // Validate immediately on page load if there's a value
        if (firstNameField.value) {
            validateFirstName(firstNameField.value);
        }
    } else {
        console.error('First name field not found!');
    }
    
    if (lastNameField) {
        lastNameField.addEventListener('input', function() {
            validateLastName(this.value);
        });
        lastNameField.addEventListener('keyup', function() {
            validateLastName(this.value);
        });
        lastNameField.addEventListener('blur', function() {
            validateLastName(this.value);
        });
        if (lastNameField.value) {
            validateLastName(lastNameField.value);
        }
    }
    
    if (emailField) {
        emailField.addEventListener('input', function() {
            validateEmail(this.value);
        });
        emailField.addEventListener('keyup', function() {
            validateEmail(this.value);
        });
        emailField.addEventListener('blur', function() {
            validateEmail(this.value);
        });
        if (emailField.value) {
            validateEmail(emailField.value);
        }
    }
    
    if (phoneField) {
        phoneField.addEventListener('input', function(e) {
            console.log('Phone input:', this.value);
            validatePhone(this.value);
        });
        phoneField.addEventListener('keyup', function(e) {
            validatePhone(this.value);
        });
        phoneField.addEventListener('blur', function(e) {
            validatePhone(this.value);
        });
        if (phoneField.value) {
            validatePhone(phoneField.value);
        }
    } else {
        console.error('Phone field not found!');
    }
    
    if (messageField) {
        const charCountEl = document.getElementById('char-count');
        
        // Update character count function
        function updateCharCount() {
            if (charCountEl && messageField) {
                const length = messageField.value.length;
                charCountEl.textContent = length;
                
                if (length > 1000) {
                    charCountEl.classList.add('text-red-500');
                    charCountEl.classList.remove('text-orange-500', 'text-gray-500');
                } else if (length > 900) {
                    charCountEl.classList.add('text-orange-500');
                    charCountEl.classList.remove('text-gray-500', 'text-red-500');
                } else {
                    charCountEl.classList.remove('text-orange-500', 'text-red-500');
                    charCountEl.classList.add('text-gray-500');
                }
            }
        }
        
        messageField.addEventListener('input', function(e) {
            console.log('Message input event fired:', this.value.length);
            updateCharCount();
            validateMessage(this.value);
        });
        
        messageField.addEventListener('keyup', function(e) {
            updateCharCount();
            validateMessage(this.value);
        });
        
        messageField.addEventListener('blur', function(e) {
            validateMessage(this.value);
        });
        
        // Initialize character count on page load
        updateCharCount();
        
        // Validate on page load if there's a value
        if (messageField.value) {
            validateMessage(messageField.value);
        }
    } else {
        console.error('Message field not found!');
    }
    
    // Prevent form submission if honeypot is filled (client-side check)
    const form = document.getElementById('contact-form');
    const submitBtn = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const honeypot = document.querySelector('input[name="website"]');
            if (honeypot && honeypot.value) {
                e.preventDefault();
                alert('Spam gedetecteerd. Dit formulier kan niet worden verzonden.');
                return false;
            }
            
            // Disable button and show loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                if (submitText) {
                    submitText.textContent = 'Versturen...';
                }
            }
            
            // Form is valid, allow submission
            return true;
        });
    }
    
    // Debug: Log when fields are found
    console.log('Fields found:', {
        firstName: !!document.getElementById('first_name'),
        lastName: !!document.getElementById('last_name'),
        email: !!document.getElementById('email'),
        phone: !!document.getElementById('phone'),
        message: !!document.getElementById('message')
    });
});
</script>
@endpush

