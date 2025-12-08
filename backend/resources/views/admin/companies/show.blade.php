@extends('admin.layouts.app')

@section('title', 'Bedrijf Details - ' . $company->name)

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono flex items-center gap-2">
                Bedrijf Details
                <span class="text-orange-500">|</span>
                <span class="text-blue-500">{{ $company->name }}</span>
            </h1>
        </div>
        <div class="flex items-center justify-between gap-5">
            <a href="{{ route('admin.companies.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
            @can('edit-companies')
            <a href="{{ route('admin.companies.edit', $company) }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-notepad-edit me-2"></i>
                Bewerken
            </a>
            @endcan
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <!-- General Info -->
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Algemene Informatie
                </h3>
                <div class="flex items-center gap-2">
                    @can('edit-companies')
                    <form action="{{ route('admin.companies.toggle-main-location', $company) }}" method="POST" id="toggle-main-location-form">
                        @csrf
                        <label class="kt-label">
                            <input type="checkbox" 
                                   class="kt-switch kt-switch-sm" 
                                   id="toggle-main-location-checkbox"
                                   {{ $company->is_main || $company->mainLocation ? 'checked' : '' }}/>
                            Hoofdkantoor
                        </label>
                    </form>
                    @else
                    <label class="kt-label">
                        <input type="checkbox" 
                               class="kt-switch kt-switch-sm" 
                               {{ $company->mainLocation ? 'checked' : '' }}
                               disabled/>
                        Hoofdkantoor
                    </label>
                    @endcan
                    <span class="text-muted-foreground">|</span>
                    @can('edit-companies')
                    <form action="{{ route('admin.companies.toggle-status', $company) }}" method="POST" id="toggle-status-form">
                        @csrf
                        <label class="kt-label">
                            <input type="checkbox" 
                                   class="kt-switch kt-switch-sm" 
                                   id="toggle-status-checkbox"
                                   {{ $company->is_active ? 'checked' : '' }}/>
                            Actief
                        </label>
                    </form>
                    @else
                    <label class="kt-label">
                        <input type="checkbox" class="kt-switch kt-switch-sm" {{ $company->is_active ? 'checked' : '' }} disabled/>
                        Actief
                    </label>
                    @endcan
                </div>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr style="padding-top: 18px;">
                        <td class="min-w-56 text-secondary-foreground font-normal" style="padding-top: 18px; vertical-align: top;">
                            Bedrijfsnaam
                        </td>
                        <td class="min-w-48 w-full text-foreground font-normal">
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center gap-2">
                                    {{ $company->name }}
                                    @if($company->is_main || $company->mainLocation)
                                        <span class="kt-badge kt-badge-success">Hoofdkantoor</span>
                                    @endif
                                </div>
                                @if($company->logo_blob)
                                    <img alt="Company Logo" class="max-h-[100px] w-auto object-contain self-start" style="height: auto; max-width: 100%;" src="{{ route('admin.companies.logo', $company) }}"/>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            KVK Nummer
                        </td>
                        <td class="text-foreground font-normal">
                            @if($company->kvk_number)
                                {{ $company->kvk_number }}
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-destructive">
                                    Niet opgegeven
                                </span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Branche
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $company->industry ?? 'Niet opgegeven' }}
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
                            Website
                        </td>
                        <td class="text-foreground font-normal">
                            @if($company->website)
                                <a class="text-foreground text-sm font-normal hover:text-primary" href="{{ $company->website }}" target="_blank">
                                    {{ $company->website }}
                                </a>
                            @else
                                <span class="text-secondary-foreground text-sm">Niet opgegeven</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Beschrijving
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $company->description ?? 'Geen beschrijving' }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Contact Informatie -->
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Contact Informatie
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">
                            E-mail
                        </td>
                        <td class="min-w-60 w-full">
                            <span class="text-foreground text-sm font-normal">
                                {{ $company->email }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Telefoon
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $company->phone ?? 'Niet opgegeven' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Adres
                        </td>
                        <td class="text-foreground font-normal">
                            @if($company->street || $company->city)
                                {{ $company->street }} {{ $company->house_number }}{{ $company->house_number_extension ? '-' . $company->house_number_extension : '' }}<br>
                                {{ $company->postal_code }} {{ $company->city }}<br>
                                {{ $company->country ?? 'Nederland' }}
                            @else
                                <span class="text-secondary-foreground text-sm">Niet opgegeven</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Vestigingen -->
        <div class="kt-card min-w-full">
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
                                    <th class="w-[60px] text-center">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($company->locations as $location)
                                    <tr class="location-row cursor-pointer" data-href="{{ route('admin.companies.locations.show', [$company, $location]) }}" style="cursor: pointer;">
                                        <td>
                                            <div class="flex items-center gap-2">
                                                {{ $location->name }}
                                                @if($location->is_main)
                                                    <span class="kt-badge kt-badge-success">Hoofdkantoor</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($location->street || $location->city)
                                                {{ $location->street }} {{ $location->house_number }}{{ $location->house_number_extension ? '-' . $location->house_number_extension : '' }}<br>
                                                {{ $location->postal_code }} {{ $location->city }}<br>
                                                {{ $location->country }}
                                            @else
                                                <span class="text-muted-foreground">Geen adres</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-1">
                                                @if($location->phone)
                                                    <div><i class="ki-filled ki-phone me-1"></i> {{ $location->phone }}</div>
                                                @endif
                                                @if($location->email)
                                                    <div><i class="ki-filled ki-sms me-1"></i> {{ $location->email }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="location-status-cell">
                                            @can('edit-companies')
                                            <form action="{{ route('admin.companies.locations.toggle-status', [$company, $location]) }}" method="POST" class="location-toggle-status-form-{{ $location->id }}">
                                                @csrf
                                                <input type="checkbox" 
                                                       class="kt-switch kt-switch-sm location-status-checkbox" 
                                                       id="toggle-status-checkbox-{{ $location->id }}"
                                                       data-location-id="{{ $location->id }}"
                                                       data-company-id="{{ $company->id }}"
                                                       {{ $location->is_active ? 'checked' : '' }}/>
                                            </form>
                                            @else
                                            <input type="checkbox" 
                                                   class="kt-switch kt-switch-sm" 
                                                   {{ $location->is_active ? 'checked' : '' }} 
                                                   disabled/>
                                            @endcan
                                        </td>
                                        <td class="w-[60px] location-actions-cell">
                                            <div class="kt-menu flex justify-center" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                        @can('edit-companies')
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.companies.locations.edit', [$company, $location]) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-pencil"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bewerken</span>
                                                            </a>
                                                        </div>
                                                        @endcan
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
</div>

@push('scripts')
@can('edit-companies')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Main location toggle
        const mainLocationCheckbox = document.getElementById('toggle-main-location-checkbox');
        const mainLocationForm = document.getElementById('toggle-main-location-form');
        
        if (mainLocationCheckbox && mainLocationForm) {
            mainLocationCheckbox.addEventListener('change', function(e) {
                e.preventDefault();
                
                const formData = new FormData(mainLocationForm);
                const url = mainLocationForm.action;
                const originalChecked = this.checked;
                
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
                        return response.json().then(data => {
                            throw new Error(data.message || 'Network response was not ok');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.message) {
                        console.log(data.message);
                        // Update checkbox state based on response
                        if (data.has_main_location !== undefined) {
                            this.checked = data.has_main_location;
                        }
                        // Reload page to update the UI
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert checkbox state on error
                    this.checked = !originalChecked;
                    alert(error.message || 'Er is een fout opgetreden bij het wijzigen van het hoofdkantoor.');
                });
            });
        }
        
        // Company status toggle
        const checkbox = document.getElementById('toggle-status-checkbox');
        const form = document.getElementById('toggle-status-form');
        
        if (checkbox && form) {
            checkbox.addEventListener('change', function(e) {
                e.preventDefault();
                
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
                    if (data.message) {
                        console.log(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    checkbox.checked = !checkbox.checked;
                    alert('Er is een fout opgetreden bij het wijzigen van de status.');
                });
            });
        }

        // Location status toggles
        const locationCheckboxes = document.querySelectorAll('.location-status-checkbox');
        locationCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const locationId = this.getAttribute('data-location-id');
                const companyId = this.getAttribute('data-company-id');
                const form = document.querySelector('.location-toggle-status-form-' + locationId);
                const formData = new FormData(form);
                const url = form.action;
                const originalChecked = this.checked;
                
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
                    if (data.message) {
                        console.log(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert checkbox state on error
                    this.checked = !originalChecked;
                    alert('Er is een fout opgetreden bij het wijzigen van de status.');
                });
            });
        });
        
        // Make location table rows clickable (except actions column and status toggle)
        document.addEventListener('click', function(e) {
            const row = e.target.closest('tr');
            if (!row || !row.classList.contains('location-row')) return;

            // Don't navigate if clicking on actions column, status column, menu, or toggle switch
            if (e.target.closest('.location-actions-cell') || 
                e.target.closest('.location-status-cell') || 
                e.target.closest('.kt-menu') || 
                e.target.closest('button') || 
                e.target.closest('a') ||
                e.target.closest('.kt-switch') ||
                e.target.closest('form')) {
                return;
            }

            // Try to get URL from data-href
            let url = row.getAttribute('data-href');
            if (url) {
                window.location.href = url;
            }
        });
        
        // Stop propagation for status toggle clicks
        document.querySelectorAll('.location-status-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    });
</script>
@endcan
@endpush

@push('styles')
<style>
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
        vertical-align: middle;
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
