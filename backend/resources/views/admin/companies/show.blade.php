@extends('admin.layouts.app')

@section('title', 'Bedrijf Details - ' . $company->name)

@section('content')

<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
</style>

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            @if($company->logo_blob)
                <div class="rounded-lg shrink-0 inline-block" style="background: transparent; padding: 3px;">
                    <img class="rounded-lg w-auto object-contain bg-transparent dark:bg-transparent" style="height: 80px; display: block; padding: 8px;" src="{{ route('admin.companies.logo', $company) }}" alt="{{ $company->name }}">
                </div>
            @else
                <div class="rounded-lg border-3 border-primary h-[100px] w-[100px] lg:h-[150px] lg:w-[150px] shrink-0 flex items-center justify-center bg-primary/10 text-primary text-2xl font-semibold">
                    {{ strtoupper(substr($company->name, 0, 2)) }}
                </div>
            @endif
            <div class="flex items-center gap-1.5">
                <x-heroicon-o-building-office-2 id="company-main-icon-hero" class="w-5 h-5 lg:w-6 lg:h-6 text-primary {{ ($company->is_main || $company->mainLocation) ? '' : 'hidden' }}" />
                <div class="text-xl lg:text-2xl leading-6 font-semibold text-mono">
                    {{ $company->name }}
                </div>
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <x-heroicon-o-building-office-2 class="w-4 h-4 text-muted-foreground" />
                    @php
                        $isActive = isset($company->is_active) ? $company->is_active : true;
                    @endphp
                    <span id="company-status-hero" class="font-medium {{ $isActive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $isActive ? 'Actief' : 'Inactief' }}
                    </span>
                </div>
                @if($company->city)
                    <div class="flex gap-1.25 items-center">
                        <i class="ki-filled ki-geolocation text-muted-foreground text-sm"></i>
                        <span class="text-secondary-foreground font-medium">
                            {{ $company->city }}{{ $company->country ? ', ' . $company->country : '' }}
                        </span>
                    </div>
                @endif
                @if($company->email)
                    <div class="flex gap-1.25 items-center">
                        <x-heroicon-o-envelope class="w-4 h-4 text-muted-foreground" />
                        <a class="text-secondary-foreground font-medium hover:text-primary" href="mailto:{{ $company->email }}">
                            {{ $company->email }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <!-- End of Container -->
</div>

<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10">
        @if(auth()->user()->hasRole('super-admin'))
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.companies.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        @else
        <div></div>
        @endif
        @can('edit-companies')
        <div class="flex items-center gap-2.5">
            <div class="flex items-center gap-2">
                <form action="{{ route('admin.companies.toggle-main-location', $company) }}" method="POST" id="toggle-main-location-form-header" style="display: inline-flex; align-items: center;">
                    @csrf
                    <label class="kt-label mb-0 flex items-center">
                        <input type="checkbox" 
                               class="kt-switch kt-switch-sm" 
                               id="toggle-main-location-checkbox-header"
                               {{ $company->is_main || $company->mainLocation ? 'checked' : '' }}/>
                        <span class="ml-2">Hoofdkantoor</span>
                    </label>
                </form>
                <span class="text-orange-500 dark:text-orange-400 flex items-center">|</span>
                <form action="{{ route('admin.companies.toggle-status', $company) }}" method="POST" id="toggle-status-form-header" style="display: inline-flex; align-items: center;">
                    @csrf
                    <label class="kt-label mb-0 flex items-center" for="is_active_header">
                        <input type="checkbox" 
                               class="kt-switch kt-switch-sm" 
                               id="is_active_header"
                               {{ isset($company->is_active) && $company->is_active ? 'checked' : '' }}/>
                        <span class="ml-2">Actief</span>
                    </label>
                </form>
            </div>
            <span class="text-orange-500 dark:text-orange-400 flex items-center">|</span>
            <a href="{{ route('admin.companies.edit', $company) }}" class="kt-btn kt-btn-primary ml-auto">
                <i class="ki-filled ki-notepad-edit me-2"></i>
                Bewerken
            </a>
        </div>
        @endcan
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <!-- begin: grid -->
    <div class="flex flex-col xl:flex-row gap-5 lg:gap-7.5">
        <!-- Bedrijfsinformatie -->
        <div class="kt-card xl:w-auto xl:min-w-[400px] xl:max-w-[500px]">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Bedrijfsinformatie
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">
                            Bedrijfsnaam
                        </td>
                        <td class="min-w-48 w-full text-foreground font-normal">
                            <div class="flex items-start gap-2">
                                <x-heroicon-o-building-office-2 id="company-main-icon-table" class="w-5 h-5 font-bold text-gray-700 dark:text-white flex-shrink-0 {{ ($company->is_main || $company->mainLocation) ? '' : 'hidden' }}" />
                                <span>{{ $company->name }}</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            KVK Nummer
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $company->kvk_number ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Branche
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $company->industry ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Bedrijfstype
                        </td>
                        <td class="text-foreground font-normal">
                            @if($company->is_intermediary)
                                <span class="kt-badge kt-badge-sm kt-badge-info">Tussenpartij</span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-success">Directe werkgever</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Beschrijving
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $company->description ?? '-' }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Contact Informatie -->
        <div class="kt-card flex-1">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Contact Informatie
                </h3>
            </div>
            <div class="kt-card-content">
                <div class="flex flex-wrap items-start gap-5">
                    <div class="rounded-xl w-full md:w-80 min-h-52 flex-shrink-0" id="company_contact_map">
                    </div>
                    <div class="flex flex-col gap-2.5 flex-1 min-w-0">
                        @if($company->email)
                        <div class="flex items-center gap-2.5">
                            <span>
                                <i class="ki-filled ki-sms text-lg text-muted-foreground"></i>
                            </span>
                            <a class="link text-sm font-medium" href="mailto:{{ $company->email }}">
                                {{ $company->email }}
                            </a>
                        </div>
                        @endif
                        @if($company->phone)
                        <div class="flex items-center gap-2.5">
                            <span>
                                <i class="ki-filled ki-whatsapp text-lg text-muted-foreground"></i>
                            </span>
                            <span class="text-sm text-mono">
                                {{ $company->phone }}
                            </span>
                        </div>
                        @endif
                        @if($company->website)
                        <div class="flex items-center gap-2.5">
                            <span>
                                <i class="ki-filled ki-dribbble text-lg text-muted-foreground"></i>
                            </span>
                            <a class="link text-sm font-medium" href="{{ $company->website }}" target="_blank">
                                {{ $company->website }}
                            </a>
                        </div>
                        @endif
                        @php
                            $addressParts = [];
                            if ($company->mainLocation) {
                                if ($company->mainLocation->street && $company->mainLocation->house_number) {
                                    $addressParts[] = $company->mainLocation->street . ' ' . $company->mainLocation->house_number . ($company->mainLocation->house_number_extension ? '-' . $company->mainLocation->house_number_extension : '');
                                }
                                if ($company->mainLocation->postal_code && $company->mainLocation->city) {
                                    $addressParts[] = $company->mainLocation->postal_code . ' ' . $company->mainLocation->city;
                                }
                                if ($company->mainLocation->country) {
                                    $addressParts[] = $company->mainLocation->country;
                                }
                            } elseif ($company->street || $company->city) {
                                if ($company->street && $company->house_number) {
                                    $addressParts[] = $company->street . ' ' . $company->house_number . ($company->house_number_extension ? '-' . $company->house_number_extension : '');
                                }
                                if ($company->postal_code && $company->city) {
                                    $addressParts[] = $company->postal_code . ' ' . $company->city;
                                }
                                if ($company->country) {
                                    $addressParts[] = $company->country;
                                }
                            }
                        @endphp
                        @if(!empty($addressParts))
                        <div class="flex items-start gap-2.5">
                            <span class="mt-0.5">
                                <i class="ki-filled ki-map text-lg text-muted-foreground"></i>
                            </span>
                            <div class="flex flex-col gap-0.5">
                                @foreach($addressParts as $part)
                                <span class="text-sm text-mono">
                                    {{ $part }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end: grid -->
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <!-- Vestigingen -->
    <div class="kt-card min-w-full mt-5 lg:mt-7.5">
        <div class="kt-card-header">
            <h3 class="kt-card-title">
                Vestigingen
            </h3>
            @can('edit-companies')
            <a href="{{ route('admin.companies.locations.create', $company) }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Nieuwe Vestiging
            </a>
            @endcan
        </div>
        <div class="kt-card-content">
            @if($company->locations->count() > 0)
                <div class="kt-scrollable-x-auto">
                    <table class="kt-table table-auto kt-table-border">
                        <thead>
                            <tr>
                                <th>Naam</th>
                                <th>Adres</th>
                                <th>Contact</th>
                                <th>Status</th>
                                @can('edit-companies')
                                <th class="w-[60px] text-center">Acties</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($company->locations as $location)
                                <tr class="location-row cursor-pointer" data-href="{{ route('admin.companies.locations.show', [$company, $location]) }}" style="cursor: pointer;">
                                    <td>
                                        <div class="flex items-center gap-2">
                                            @if($location->is_main)
                                                <x-heroicon-o-building-office-2 class="w-5 h-5 font-bold text-gray-700 dark:text-white" />
                                            @endif
                                            {{ $location->name }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($location->street || $location->city)
                                            {{ $location->street }} {{ $location->house_number }}{{ $location->house_number_extension ? '-' . $location->house_number_extension : '' }}<br>
                                            {{ $location->postal_code }} {{ $location->city }}<br>
                                            {{ $location->country }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($location->phone || $location->email)
                                            <div class="flex flex-col gap-1">
                                                @if($location->phone)
                                                    <div><i class="ki-filled ki-phone me-1"></i> {{ $location->phone }}</div>
                                                @endif
                                                @if($location->email)
                                                    <div><i class="ki-filled ki-sms me-1"></i> {{ $location->email }}</div>
                                                @endif
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="location-status-cell">
                                        @can('edit-companies')
                                        @if($location->is_active)
                                            <form action="{{ route('admin.companies.locations.toggle-status', [$company, $location]) }}" method="POST" class="location-toggle-status-form-{{ $location->id }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-success location-status-button" data-location-id="{{ $location->id }}" data-company-id="{{ $company->id }}">
                                                    Actief
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.companies.locations.toggle-status', [$company, $location]) }}" method="POST" class="location-toggle-status-form-{{ $location->id }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-danger location-status-button" data-location-id="{{ $location->id }}" data-company-id="{{ $company->id }}">
                                                    Inactief
                                                </button>
                                            </form>
                                        @endif
                                        @else
                                        @if($location->is_active)
                                            <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                                        @else
                                            <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                                        @endif
                                        @endcan
                                    </td>
                                    @can('edit-companies')
                                    <td class="w-[60px] location-actions-cell">
                                        <div class="kt-menu flex justify-center" data-kt-menu="true">
                                            <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                    <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                </button>
                                                <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                    <div class="kt-menu-item">
                                                        <a class="kt-menu-link" href="{{ route('admin.companies.locations.edit', [$company, $location]) }}">
                                                            <span class="kt-menu-icon">
                                                                <i class="ki-filled ki-pencil"></i>
                                                            </span>
                                                            <span class="kt-menu-title">Bewerken</span>
                                                        </a>
                                                    </div>
                                                    <div class="kt-menu-separator"></div>
                                                    @if($location->is_active)
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.companies.locations.toggle-status', [$company, $location]) }}" method="POST" class="location-toggle-status-form-menu-{{ $location->id }}" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="kt-menu-link w-full text-left text-danger location-status-button-menu" data-location-id="{{ $location->id }}" data-company-id="{{ $company->id }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-cross-circle"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Deactiveren</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    @else
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.companies.locations.toggle-status', [$company, $location]) }}" method="POST" class="location-toggle-status-form-menu-{{ $location->id }}" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="kt-menu-link w-full text-left text-success location-status-button-menu" data-location-id="{{ $location->id }}" data-company-id="{{ $company->id }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-check-circle"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Activeren</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    @endif
                                                    @can('delete-companies')
                                                    <div class="kt-menu-separator"></div>
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.companies.locations.destroy', [$company, $location]) }}"
                                                              method="POST"
                                                              style="display: inline;"
                                                              onsubmit="return confirm('Weet je zeker dat je deze vestiging wilt verwijderen?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="kt-menu-link w-full text-left text-danger">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-trash"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Verwijderen</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-muted-foreground">
                    <i class="ki-filled ki-information-5 text-4xl mb-2"></i>
                    <p>Nog geen vestigingen toegevoegd.</p>
                </div>
            @endif
        </div>
    </div>
</div>
<!-- End of Container -->

@push('scripts')
@can('edit-companies')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Main location toggle (header)
        const mainLocationCheckboxHeader = document.getElementById('toggle-main-location-checkbox-header');
        const mainLocationFormHeader = document.getElementById('toggle-main-location-form-header');
        
        if (mainLocationCheckboxHeader && mainLocationFormHeader) {
            mainLocationCheckboxHeader.addEventListener('change', function(e) {
                e.preventDefault();
                
                const formData = new FormData(mainLocationFormHeader);
                const url = mainLocationFormHeader.action;
                const originalChecked = this.checked;
                
                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    // First check if response is ok
                    if (!response.ok) {
                        // Try to get error message from response
                        return response.text().then(text => {
                            let errorMessage = 'Network response was not ok';
                            try {
                                const jsonData = JSON.parse(text);
                                errorMessage = jsonData.message || jsonData.error || errorMessage;
                            } catch (e) {
                                // If not JSON, use status text
                                errorMessage = response.statusText || errorMessage;
                            }
                            throw new Error(errorMessage + ' (Status: ' + response.status + ')');
                        });
                    }
                    // Try to parse as JSON
                    return response.json().catch(() => {
                        throw new Error('Invalid JSON response from server');
                    });
                })
                .then(data => {
                    if (data.success) {
                        // Update checkbox state based on response
                        if (data.has_main_location !== undefined) {
                            this.checked = data.has_main_location;
                            
                            // Show/hide main location icons
                            const heroIcon = document.getElementById('company-main-icon-hero');
                            const tableIcon = document.getElementById('company-main-icon-table');
                            
                            if (data.has_main_location) {
                                // Show icons
                                if (heroIcon) {
                                    heroIcon.classList.remove('hidden');
                                }
                                if (tableIcon) {
                                    tableIcon.classList.remove('hidden');
                                }
                            } else {
                                // Hide icons
                                if (heroIcon) {
                                    heroIcon.classList.add('hidden');
                                }
                                if (tableIcon) {
                                    tableIcon.classList.add('hidden');
                                }
                            }
                        }
                    } else {
                        throw new Error(data.message || 'Wijziging mislukt');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    console.error('Error details:', {
                        message: error.message,
                        stack: error.stack
                    });
                    // Revert checkbox state on error
                    this.checked = !originalChecked;
                    // Show detailed error message
                    const errorMessage = error.message || 'Er is een fout opgetreden bij het wijzigen van het hoofdkantoor.';
                    alert('Fout: ' + errorMessage);
                });
            });
        }
        
        // Company status toggle (header)
        const isActiveHeader = document.getElementById('is_active_header');
        const isActiveFormHeader = document.getElementById('toggle-status-form-header');
        
        if (isActiveHeader && isActiveFormHeader) {
            isActiveHeader.addEventListener('change', function(e) {
                e.preventDefault();
                
                const formData = new FormData(isActiveFormHeader);
                const url = isActiveFormHeader.action;
                const originalChecked = this.checked;
                
                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    // First check if response is ok
                    if (!response.ok) {
                        // Try to get error message from response
                        return response.text().then(text => {
                            let errorMessage = 'Network response was not ok';
                            try {
                                const jsonData = JSON.parse(text);
                                errorMessage = jsonData.message || jsonData.error || errorMessage;
                            } catch (e) {
                                // If not JSON, use status text
                                errorMessage = response.statusText || errorMessage;
                            }
                            throw new Error(errorMessage + ' (Status: ' + response.status + ')');
                        });
                    }
                    // Try to parse as JSON
                    return response.json().catch(() => {
                        throw new Error('Invalid JSON response from server');
                    });
                })
                .then(data => {
                    if (data.success) {
                        // Update checkbox state based on response
                        if (data.is_active !== undefined) {
                            this.checked = data.is_active;
                            
                            // Update hero status text
                            const heroStatusElement = document.getElementById('company-status-hero');
                            if (heroStatusElement) {
                                if (data.is_active) {
                                    heroStatusElement.textContent = 'Actief';
                                    heroStatusElement.className = 'font-medium text-green-600 dark:text-green-400';
                                } else {
                                    heroStatusElement.textContent = 'Inactief';
                                    heroStatusElement.className = 'font-medium text-red-600 dark:text-red-400';
                                }
                            }
                        }
                    } else {
                        throw new Error(data.message || 'Wijziging mislukt');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    console.error('Error details:', {
                        message: error.message,
                        stack: error.stack
                    });
                    // Revert checkbox state on error
                    this.checked = !originalChecked;
                    // Show detailed error message
                    const errorMessage = error.message || 'Er is een fout opgetreden bij het wijzigen van de status.';
                    alert('Fout: ' + errorMessage);
                });
            });
        }
        
        // Location status buttons (table cell)
        const locationStatusButtons = document.querySelectorAll('.location-status-button');
        locationStatusButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const locationId = this.getAttribute('data-location-id');
                const companyId = this.getAttribute('data-company-id');
                const form = this.closest('form');
                const formData = new FormData(form);
                const url = form.action;
                const originalButton = this;
                const originalText = this.textContent;
                const originalClass = this.className;
                
                // Disable button during request
                this.disabled = true;
                
                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.is_active !== undefined) {
                        // Update button based on new status
                        if (data.is_active) {
                            originalButton.textContent = 'Actief';
                            originalButton.className = 'kt-btn kt-btn-sm kt-btn-success location-status-button';
                        } else {
                            originalButton.textContent = 'Inactief';
                            originalButton.className = 'kt-btn kt-btn-sm kt-btn-danger location-status-button';
                        }
                        originalButton.setAttribute('data-location-id', locationId);
                        originalButton.setAttribute('data-company-id', companyId);
                        
                        // Reload page to update menu items
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Er is een fout opgetreden bij het wijzigen van de status.');
                    originalButton.disabled = false;
                });
            });
        });
        
        // Location status buttons (menu dropdown)
        const locationStatusMenuButtons = document.querySelectorAll('.location-status-button-menu');
        locationStatusMenuButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const locationId = this.getAttribute('data-location-id');
                const companyId = this.getAttribute('data-company-id');
                const form = this.closest('form');
                const formData = new FormData(form);
                const url = form.action;
                
                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Reload page to update status
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Er is een fout opgetreden bij het wijzigen van de status.');
                });
            });
        });
        
        // Make location table rows clickable (except actions column and status toggle)
        document.addEventListener('click', function(e) {
            const row = e.target.closest('tr');
            if (!row || !row.classList.contains('location-row')) return;

            // Don't navigate if clicking on actions column, status column, menu, or buttons
            if (e.target.closest('.location-actions-cell') || 
                e.target.closest('.location-status-cell') || 
                e.target.closest('.kt-menu') || 
                e.target.closest('button') || 
                e.target.closest('a') ||
                e.target.closest('form')) {
                return;
            }

            // Try to get URL from data-href
            let url = row.getAttribute('data-href');
            if (url) {
                window.location.href = url;
            }
        });
        
        // Stop propagation for status button clicks
        document.querySelectorAll('.location-status-button, .location-status-button-menu').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    });
</script>
@endcan

<!-- Leaflet Map Initialization -->
<script src="{{ asset('assets/vendors/leaflet/leaflet.bundle.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mapElement = document.getElementById('company_contact_map');
    if (!mapElement) return;

    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.error('Leaflet is not loaded');
        return;
    }

    @php
        // Get address for geocoding
        $address = '';
        $lat = 52.2217; // Default to Enschede, Netherlands
        $lng = 6.8937;
        if ($company->mainLocation) {
            $address = $company->mainLocation->street . ' ' . $company->mainLocation->house_number . ($company->mainLocation->house_number_extension ? '-' . $company->mainLocation->house_number_extension : '') . ', ' . $company->mainLocation->postal_code . ' ' . $company->mainLocation->city . ($company->mainLocation->country ? ', ' . $company->mainLocation->country : '');
        } elseif ($company->street || $company->city) {
            $address = $company->street . ' ' . $company->house_number . ($company->house_number_extension ? '-' . $company->house_number_extension : '') . ', ' . $company->postal_code . ' ' . $company->city . ($company->country ? ', ' . $company->country : '');
        }
    @endphp

    // Initialize map with default location
    const leaflet = L.map('company_contact_map', {
        center: [{{ $lat }}, {{ $lng }}],
        zoom: 14,
        zoomControl: false
    });

    // Add zoom controls in bottom-left corner
    L.control.zoom({
        position: 'bottomleft'
    }).addTo(leaflet);

    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(leaflet);

    // Custom marker icon
    const leafletIcon = L.divIcon({
        html: '<i class="ki-solid ki-geolocation text-3xl text-green-500"></i>',
        bgPos: [10, 10],
        iconAnchor: [20, 37],
        popupAnchor: [0, -37],
        className: 'leaflet-marker'
    });

    // Try to geocode the address
    const address = @json($address);

    if (address) {
        // Use Nominatim (OpenStreetMap geocoding service) - free and no API key needed
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);

                    // Update map center
                    leaflet.setView([lat, lng], 16);

                    // Add marker with popup always visible
                    const marker = L.marker([lat, lng], {
                        icon: leafletIcon
                    }).addTo(leaflet);

                    // Create popup and ensure it's always visible
                    marker.bindPopup(address, {
                        closeButton: false,
                        autoPan: true,
                        autoPanPadding: [20, 20],
                        offset: [0, -40],
                        keepInView: true
                    });

                    // Open popup immediately and keep it open
                    marker.openPopup();

                    // Ensure popup stays visible
                    setTimeout(() => {
                        marker.openPopup();
                    }, 100);
                } else {
                    // Fallback to default location
                    leaflet.setView([{{ $lat }}, {{ $lng }}], 16);

                    const marker = L.marker([{{ $lat }}, {{ $lng }}], {
                        icon: leafletIcon
                    }).addTo(leaflet);

                    marker.bindPopup(address, {
                        closeButton: false,
                        autoPan: true,
                        autoPanPadding: [20, 20],
                        offset: [0, -40],
                        keepInView: true
                    });

                    marker.openPopup();

                    setTimeout(() => {
                        marker.openPopup();
                    }, 100);
                }
            })
            .catch(error => {
                console.error('Geocoding error:', error);
                // Fallback to default location
                leaflet.setView([{{ $lat }}, {{ $lng }}], 16);

                const marker = L.marker([{{ $lat }}, {{ $lng }}], {
                    icon: leafletIcon
                }).addTo(leaflet);

                marker.bindPopup(address || 'Locatie niet gevonden', {
                    closeButton: false,
                    autoPan: true,
                    autoPanPadding: [20, 20],
                    offset: [0, -40],
                    keepInView: true
                });

                marker.openPopup();

                setTimeout(() => {
                    marker.openPopup();
                }, 100);
            });
    } else {
        // No address available, show default location
        leaflet.setView([{{ $lat }}, {{ $lng }}], 14);
    }
});
</script>
@endpush

@push('styles')
<link href="{{ asset('assets/vendors/leaflet/leaflet.bundle.css') }}" rel="stylesheet" type="text/css" />
<style>
    /* Success and Danger button styles */
    .kt-btn-success {
        background-color: #10b981;
        color: white;
    }
    .kt-btn-success:hover {
        background-color: #059669;
    }
    .kt-btn-danger {
        background-color: #ef4444;
        color: white;
    }
    .kt-btn-danger:hover {
        background-color: #dc2626;
    }
    .dark .kt-btn-success {
        background-color: #059669;
    }
    .dark .kt-btn-success:hover {
        background-color: #047857;
    }
    .dark .kt-btn-danger {
        background-color: #dc2626;
    }
    .dark .kt-btn-danger:hover {
        background-color: #b91c1c;
    }
    
    /* Vertical alignment for Hoofdkantoor and Actief toggles */
    #toggle-main-location-form-header,
    #toggle-status-form-header {
        display: inline-flex !important;
        align-items: center !important;
        vertical-align: middle;
    }
    
    #toggle-main-location-form-header label,
    #toggle-status-form-header label,
    label[for="is_active_header"] {
        display: flex !important;
        align-items: center !important;
        margin-bottom: 0 !important;
        vertical-align: middle;
    }
    
    #toggle-main-location-checkbox-header,
    #is_active_header {
        vertical-align: middle;
    }
    
    /* Remove all borders between table rows in show forms */
    .kt-table-border-dashed tbody tr {
        border-bottom: none !important;
    }
    /* Uniform row height for all table rows */
    .kt-table-border-dashed tbody tr,
    .kt-table-border-dashed tbody tr td {
        height: auto;
        min-height: 48px;
    }
    .kt-table-border-dashed tbody tr td {
        padding-top: 12px;
        padding-bottom: 12px;
        vertical-align: top;
    }
    
    /* Labels (first column) should align with top of content */
    .kt-table-border-dashed tbody tr td:first-child {
        vertical-align: top;
        padding-top: 12px;
    }
    
    /* Content (second column) should align with top */
    .kt-table-border-dashed tbody tr td:last-child {
        vertical-align: top;
        padding-top: 12px;
    }
    
    /* Ensure all table cells align to top */
    .kt-table-border-dashed tbody tr td {
        vertical-align: top !important;
    }
    
    /* Location row hover styling (same as company-row on index page) */
    .location-row {
        cursor: pointer !important;
    }
    .location-row:hover {
        background-color: var(--muted) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .location-row:hover {
            background-color: color-mix(in oklab, var(--muted) 50%, transparent) !important;
        }
    }
</style>
@endpush

@endsection
