@extends('admin.layouts.app')

@section('title', 'E-mail Template Details - ' . $emailTemplate->name)

@section('content')

@include('admin.email-templates.partials.preview-styles')

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            <div class="rounded-full border-3 border-green-500 size-[100px] shrink-0 flex items-center justify-center bg-primary/10 text-primary text-2xl font-semibold">
                <i class="ki-filled ki-sms text-4xl"></i>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="text-lg leading-5 font-semibold text-mono">
                    {{ $emailTemplate->name }}
                </div>
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                @if($emailTemplate->company)
                    <div class="flex gap-1.25 items-center">
                        <x-heroicon-o-building-office-2 class="w-4 h-4 text-muted-foreground" />
                        <span class="text-secondary-foreground font-medium">
                            {{ $emailTemplate->company->name }}
                        </span>
                    </div>
                @endif
                <div class="flex gap-1.25 items-center">
                    <span class="text-secondary-foreground font-medium">
                        {{ ucfirst(str_replace('_', ' ', $emailTemplate->type)) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.email-templates.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        <div class="flex items-center gap-2.5">
            @can('edit-email-templates')
            <form action="{{ route('admin.email-templates.toggle-status', $emailTemplate) }}" method="POST" id="toggle-status-form" class="inline">
                @csrf
                <label class="kt-label flex items-center">
                    <input type="checkbox"
                           class="kt-switch kt-switch-sm"
                           id="toggle-status-checkbox"
                           {{ $emailTemplate->is_active ? 'checked' : '' }}/>
                    <span class="ms-2">Actief</span>
                </label>
            </form>
            @else
            <label class="kt-label flex items-center">
                <input type="checkbox"
                       class="kt-switch kt-switch-sm"
                       {{ $emailTemplate->is_active ? 'checked' : '' }}
                       disabled/>
                <span class="ms-2">Actief</span>
            </label>
            @endcan
            @can('create-email-templates')
            <form action="{{ route('admin.email-templates.duplicate', $emailTemplate) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-copy me-2"></i>
                    Dupliceren
                </button>
            </form>
            @endcan
            @can('edit-email-templates')
            <span class="text-muted-foreground">|</span>
            <a href="{{ route('admin.email-templates.edit', $emailTemplate) }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-notepad-edit me-2"></i>
                Bewerken
            </a>
            @endcan
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <!-- Template Info Card -->
        <div class="kt-card">
            <div class="kt-card-header">
                <h5 class="kt-card-title">Template Informatie</h5>
            </div>
            <div class="kt-card-content">
                <table class="kt-table kt-table-border align-middle text-sm w-full">
                    <tbody>
                        <tr>
                            <td class="text-secondary-foreground font-normal min-w-[200px]">Naam</td>
                            <td class="text-foreground font-medium">{{ $emailTemplate->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Type</td>
                            <td class="text-foreground font-normal">
                                <span class="kt-badge kt-badge-sm kt-badge-info">
                                    {{ ucfirst(str_replace('_', ' ', $emailTemplate->type)) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Onderwerp</td>
                            <td class="text-foreground font-normal">{{ $emailTemplate->subject }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Status</td>
                            <td>
                                @if($emailTemplate->is_active)
                                    <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                                @else
                                    <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Bedrijf</td>
                            <td class="text-foreground font-normal">
                                {{ $emailTemplate->company->name ?? 'Algemeen' }}
                            </td>
                        </tr>
                        @if($emailTemplate->description)
                        <tr>
                            <td class="text-secondary-foreground font-normal">Beschrijving</td>
                            <td class="text-foreground font-normal">{{ $emailTemplate->description }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-secondary-foreground font-normal">Aangemaakt op</td>
                            <td class="text-foreground font-normal">{{ $emailTemplate->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Laatst bijgewerkt</td>
                            <td class="text-foreground font-normal">{{ $emailTemplate->updated_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Email Content Preview -->
        <div class="kt-card">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3">
                <h5 class="kt-card-title mb-0">E-mail Preview</h5>
                @can('edit-email-templates')
                <a href="{{ route('admin.email-templates.edit', $emailTemplate) }}#html_content" class="kt-btn kt-btn-sm kt-btn-outline">
                    <i class="ki-filled ki-notepad-edit me-1"></i>
                    Template bewerken
                </a>
                @endcan
            </div>
            <div class="kt-card-content">
                <div class="mb-4">
                    <h6 class="text-sm font-semibold text-foreground mb-2">Onderwerp:</h6>
                    <p class="text-sm text-muted-foreground">{{ $emailTemplate->subject }}</p>
                </div>
                
                @if($emailTemplate->html_content)
                <div class="mb-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <h6 class="text-sm font-semibold text-foreground mb-0">HTML Inhoud:</h6>
                        <p class="text-xs text-muted-foreground mb-0">Preview toont light mode — zoals in de meeste e-mailclients.</p>
                    </div>
                    @if(!$emailTemplate->company_id)
                    <p class="text-xs text-muted-foreground mb-3">
                        Dit is een <strong>algemeen</strong> template. In de preview en bij verzenden wordt het logo (en bedrijfsnaam) van de
                        <strong>ontvangende tenant</strong> gebruikt — bij een boeking of rit het bedrijf van die rit, in de admin-preview het geselecteerde of huidige tenant-domein.
                        Zie je alleen tekst i.p.v. een logo? Upload dan een logo bij dat bedrijf (Algemene instellingen of bedrijfsprofiel).
                    </p>
                    @endif
                    <div class="border border-border rounded-xl p-4 bg-muted/30 email-preview-content">
                        <div class="prose prose-sm max-w-none text-left">
                            {!! $previewHtml ?? $emailTemplate->html_content !!}
                        </div>
                    </div>
                </div>
                @endif
                
                @if(!$emailTemplate->html_content)
                <div class="text-center py-8 text-muted-foreground">
                    <i class="ki-filled ki-information-2 text-4xl mb-2"></i>
                    <p>Geen inhoud beschikbaar</p>
                </div>
                @endif
            </div>
        </div>

        @can('edit-email-templates')
        @include('admin.email-templates.partials.send-test-form')
        @endcan
    </div>
</div>

@push('scripts')
@can('edit-email-templates')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var checkbox = document.getElementById('toggle-status-checkbox');
        var form = document.getElementById('toggle-status-form');
        if (checkbox && form) {
            checkbox.addEventListener('change', function(e) {
                e.preventDefault();
                var formData = new FormData(form);
                var url = form.action;
                var originalChecked = this.checked;
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
                .then(function(response) {
                    if (!response.ok) {
                        return response.text().then(function(text) {
                            var errorMessage = 'Network response was not ok';
                            try {
                                var jsonData = JSON.parse(text);
                                errorMessage = jsonData.message || jsonData.error || errorMessage;
                            } catch (err) {}
                            throw new Error(errorMessage);
                        });
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        window.location.reload();
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    checkbox.checked = !originalChecked;
                    alert('Fout: ' + (error.message || 'Er is een fout opgetreden bij het wijzigen van de status.'));
                });
            });
        }
    });
</script>
@endcan
@endpush

@endsection
