@extends('admin.layouts.app')

@section('title', 'Gebruiker Details - ' . $user->first_name . ' ' . $user->last_name)

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
            @if($user->photo_blob)
                <img class="rounded-full border-3 border-green-500 size-[100px] shrink-0 object-cover" src="{{ route('admin.users.photo', $user) }}" alt="{{ $user->first_name }} {{ $user->last_name }}">
            @elseif($user->photo)
                <img class="rounded-full border-3 border-green-500 size-[100px] shrink-0 object-cover" src="{{ asset('storage/' . $user->photo) }}" alt="{{ $user->first_name }} {{ $user->last_name }}">
            @else
                <div class="rounded-full border-3 border-green-500 size-[100px] shrink-0 flex items-center justify-center bg-primary/10 text-primary text-2xl font-semibold">
                    {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                </div>
            @endif
            <div class="flex items-center gap-1.5">
                <div class="text-lg leading-5 font-semibold text-mono">
                    {{ $user->first_name }} {{ $user->last_name }}
                </div>
                @if($user->email_verified_at)
                    <svg class="text-primary" fill="none" height="16" viewbox="0 0 15 16" width="15" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14.5425 6.89749L13.5 5.83999C13.4273 5.76877 13.3699 5.6835 13.3312 5.58937C13.2925 5.49525 13.2734 5.39424 13.275 5.29249V3.79249C13.274 3.58699 13.2324 3.38371 13.1527 3.19432C13.0729 3.00494 12.9565 2.83318 12.8101 2.68892C12.6638 2.54466 12.4904 2.43073 12.2998 2.35369C12.1093 2.27665 11.9055 2.23801 11.7 2.23999H10.2C10.0982 2.24159 9.99722 2.22247 9.9031 2.18378C9.80898 2.1451 9.72371 2.08767 9.65249 2.01499L8.60249 0.957487C8.30998 0.665289 7.91344 0.50116 7.49999 0.50116C7.08654 0.50116 6.68999 0.665289 6.39749 0.957487L5.33999 1.99999C5.26876 2.07267 5.1835 2.1301 5.08937 2.16879C4.99525 2.20747 4.89424 2.22659 4.79249 2.22499H3.29249C3.08699 2.22597 2.88371 2.26754 2.69432 2.34731C2.50494 2.42709 2.33318 2.54349 2.18892 2.68985C2.04466 2.8362 1.93073 3.00961 1.85369 3.20013C1.77665 3.39064 1.73801 3.5945 1.73999 3.79999V5.29999C1.74159 5.40174 1.72247 5.50275 1.68378 5.59687C1.6451 5.691 1.58767 5.77627 1.51499 5.84749L0.457487 6.89749C0.165289 7.19 0.00115967 7.58654 0.00115967 7.99999C0.00115967 8.41344 0.165289 8.80998 0.457487 9.10249L1.49999 10.16C1.57267 10.2312 1.6301 10.3165 1.66878 10.4106C1.70747 10.5047 1.72659 10.6057 1.72499 10.7075V12.2075C1.72597 12.413 1.76754 12.6163 1.84731 12.8056C1.92709 12.995 2.04349 13.1668 2.18985 13.3111C2.3362 13.4553 2.50961 13.5692 2.70013 13.6463C2.89064 13.7233 3.0945 13.762 3.29999 13.76H4.79999C4.90174 13.7584 5.00275 13.7775 5.09687 13.8162C5.191 13.8549 5.27627 13.9123 5.34749 13.985L6.40499 15.0425C6.69749 15.3347 7.09404 15.4988 7.50749 15.4988C7.92094 15.4988 8.31748 15.3347 8.60999 15.0425L9.65999 14C9.73121 13.9273 9.81647 13.8699 9.9106 13.8312C10.0047 13.7925 10.1057 13.7734 10.2075 13.775H11.7075C12.1212 13.775 12.518 13.6106 12.8106 13.3181C13.1031 13.0255 13.2675 12.6287 13.2675 12.215V10.715C13.2659 10.6132 13.285 10.5122 13.3237 10.4181C13.3624 10.324 13.4198 10.2387 13.4925 10.1675L14.55 9.10999C14.6953 8.96452 14.8104 8.79176 14.8887 8.60164C14.9671 8.41152 15.007 8.20779 15.0063 8.00218C15.0056 7.79656 14.9643 7.59311 14.8847 7.40353C14.8051 7.21394 14.6888 7.04197 14.5425 6.89749ZM10.635 6.64999L6.95249 10.25C6.90055 10.3026 6.83864 10.3443 6.77038 10.3726C6.70212 10.4009 6.62889 10.4153 6.55499 10.415C6.48062 10.4139 6.40719 10.3982 6.33896 10.3685C6.27073 10.3389 6.20905 10.2961 6.15749 10.2425L4.37999 8.44249C4.32532 8.39044 4.28169 8.32793 4.25169 8.25867C4.22169 8.18941 4.20593 8.11482 4.20536 8.03934C4.20479 7.96387 4.21941 7.88905 4.24836 7.81934C4.27731 7.74964 4.31999 7.68647 4.37387 7.63361C4.42774 7.58074 4.4917 7.53926 4.56194 7.51163C4.63218 7.484 4.70726 7.47079 4.78271 7.47278C4.85816 7.47478 4.93244 7.49194 5.00112 7.52324C5.0698 7.55454 5.13148 7.59935 5.18249 7.65499L6.56249 9.05749L9.84749 5.84749C9.95296 5.74215 10.0959 5.68298 10.245 5.68298C10.394 5.68298 10.537 5.74215 10.6425 5.84749C10.6953 5.90034 10.737 5.96318 10.7653 6.03234C10.7935 6.1015 10.8077 6.1756 10.807 6.25031C10.8063 6.32502 10.7908 6.39884 10.7612 6.46746C10.7317 6.53608 10.6888 6.59813 10.635 6.64999Z" fill="currentColor"></path>
                    </svg>
                @endif
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                @if($user->company)
                    <div class="flex gap-1.25 items-center">
                        <x-heroicon-o-building-office-2 class="w-4 h-4 text-muted-foreground" />
                        <span class="text-secondary-foreground font-medium">
                            {{ $user->company->name }}
                        </span>
                    </div>
                @endif
                @if($user->phone)
                    <div class="flex gap-1.25 items-center">
                        <i class="ki-filled ki-phone text-muted-foreground text-sm"></i>
                        <span class="text-secondary-foreground font-medium">
                            {{ $user->phone }}
                        </span>
                    </div>
                @endif
                <div class="flex gap-1.25 items-center">
                    <x-heroicon-o-envelope class="w-4 h-4 text-muted-foreground" />
                    <a class="text-secondary-foreground font-medium hover:text-primary" href="mailto:{{ $user->email }}">
                        {{ $user->email }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</div>

<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.users.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        <div class="flex items-center gap-2.5">
            @can('edit-users')
            <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" id="toggle-status-form" class="inline">
                @csrf
                <label class="kt-label flex items-center">
                    @php
                        $isActive = isset($user->is_active) ? $user->is_active : ($user->email_verified_at !== null);
                    @endphp
                    <input type="checkbox" 
                           class="kt-switch kt-switch-sm" 
                           id="toggle-status-checkbox"
                           {{ $isActive ? 'checked' : '' }}/>
                    <span class="ms-2">Actief</span>
                </label>
            </form>
            @else
            <label class="kt-label flex items-center">
                @php
                    $isActive = isset($user->is_active) ? $user->is_active : ($user->email_verified_at !== null);
                @endphp
                <input type="checkbox" 
                       class="kt-switch kt-switch-sm" 
                       {{ $isActive ? 'checked' : '' }} 
                       disabled/>
                <span class="ms-2">Actief</span>
            </label>
            @endcan
            @can('edit-users')
            <span class="text-orange-500">|</span>
            <a href="{{ route('admin.users.edit', $user) }}" class="kt-btn kt-btn-primary ml-auto">
                <i class="ki-filled ki-notepad-edit me-2"></i>
                Bewerken
            </a>
            @endcan
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <!-- begin: grid -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 lg:gap-7.5">
        <!-- Profiel -->
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Profiel
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">
                            Naam
                        </td>
                        <td class="min-w-48 w-full text-foreground font-normal">
                            {{ $user->first_name }} {{ $user->last_name }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            E-mail
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $user->email }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Telefoon
                        </td>
                        <td class="text-foreground font-normal">
                            @if($user->phone)
                                {{ $user->phone }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Functie
                        </td>
                        <td class="text-foreground font-normal">
                            @if($user->function)
                                {{ $user->function }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @if($user->company)
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Bedrijf
                            </td>
                            <td class="text-foreground font-normal">
                                <a class="text-foreground hover:text-primary" href="{{ route('admin.companies.show', $user->company) }}">
                                    {{ $user->company->name }}
                                </a>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Geboortedatum
                        </td>
                        <td class="text-foreground font-normal">
                            @if($user->date_of_birth)
                                {{ \Carbon\Carbon::parse($user->date_of_birth)->format('d-m-Y') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Account Status -->
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Account Status
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">
                            Status
                        </td>
                        <td class="min-w-48 w-full text-foreground font-normal">
                            @php
                                $isActive = isset($user->is_active) ? $user->is_active : ($user->email_verified_at !== null);
                            @endphp
                            @if($isActive)
                                <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Rollen
                        </td>
                        <td class="text-foreground font-normal">
                            @foreach($user->roles as $role)
                                @if($role->name === 'super-admin')
                                    @if(auth()->user()->hasRole('super-admin'))
                                        <span class="kt-badge kt-badge-sm kt-badge-primary me-1">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
                                    @else
                                        <span class="kt-badge kt-badge-sm kt-badge-secondary me-1">Verborgen</span>
                                    @endif
                                @else
                                    <span class="kt-badge kt-badge-sm kt-badge-primary me-1">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
                                @endif
                            @endforeach
                            @if($user->roles->isEmpty())
                                <span class="text-secondary-foreground text-sm">Geen rollen</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            E-mail geverifieerd
                        </td>
                        <td class="text-foreground font-normal">
                            @if($user->email_verified_at)
                                <span class="kt-badge kt-badge-sm kt-badge-success">Ja</span>
                                <span class="text-xs text-secondary-foreground ms-2">
                                    {{ \Carbon\Carbon::parse($user->email_verified_at)->format('d-m-Y H:i') }}
                                </span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-warning">Nee</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Telefoon geverifieerd
                        </td>
                        <td class="text-foreground font-normal">
                            @if($user->phone_verified_at)
                                <span class="kt-badge kt-badge-sm kt-badge-success">Ja</span>
                                <span class="text-xs text-secondary-foreground ms-2">
                                    {{ \Carbon\Carbon::parse($user->phone_verified_at)->format('d-m-Y H:i') }}
                                </span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-warning">Nee</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Aangemaakt op
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $user->created_at->format('d-m-Y H:i') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Laatst bijgewerkt
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $user->updated_at->format('d-m-Y H:i') }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <!-- end: grid -->
</div>
<!-- End of Container -->

@push('scripts')
@can('edit-users')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // User status toggle
        const checkbox = document.getElementById('toggle-status-checkbox');
        const form = document.getElementById('toggle-status-form');
        
        if (checkbox && form) {
            checkbox.addEventListener('change', function(e) {
                e.preventDefault();
                
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
                        // Reload page to update the UI
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    console.error('Error details:', {
                        message: error.message,
                        stack: error.stack
                    });
                    // Revert checkbox state on error
                    checkbox.checked = !originalChecked;
                    // Show detailed error message
                    const errorMessage = error.message || 'Er is een fout opgetreden bij het wijzigen van de status.';
                    alert('Fout: ' + errorMessage);
                });
            });
        }
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
</style>
@endpush

@endsection
