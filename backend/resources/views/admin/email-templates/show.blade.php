@extends('admin.layouts.app')

@section('title', 'E-mail Template Details - ' . $emailTemplate->name)

@section('content')

<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
    /* Alleen divs met inline background in de e-mailpreview: lichtgrijze achtergrond in dark mode */
    .dark .email-preview-content div[style*="background"] {
        background: #a3a3a3 !important;
        color: #1f2937 !important;
    }
</style>

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
            <div class="kt-card-header">
                <h5 class="kt-card-title">E-mail Preview</h5>
            </div>
            <div class="kt-card-content">
                <div class="mb-4">
                    <h6 class="text-sm font-semibold text-foreground mb-2">Onderwerp:</h6>
                    <p class="text-sm text-muted-foreground">{{ $emailTemplate->subject }}</p>
                </div>
                
                @if($emailTemplate->html_content)
                <div class="mb-4">
                    <h6 class="text-sm font-semibold text-foreground mb-2">HTML Inhoud:</h6>
                    <div class="border border-border rounded-lg p-4 bg-muted/30 email-preview-content">
                        <div class="prose prose-sm max-w-none dark:prose-invert">
                            {!! $emailTemplate->html_content !!}
                        </div>
                    </div>
                </div>
                @endif
                
                @if($emailTemplate->text_content)
                <div>
                    <h6 class="text-sm font-semibold text-foreground mb-2">Tekst Inhoud:</h6>
                    <div class="border border-border rounded-lg p-4 bg-muted/30 email-preview-content">
                        <pre class="text-sm text-foreground whitespace-pre-wrap font-mono">{{ $emailTemplate->text_content }}</pre>
                    </div>
                </div>
                @endif
                
                @if(!$emailTemplate->html_content && !$emailTemplate->text_content)
                <div class="text-center py-8 text-muted-foreground">
                    <i class="ki-filled ki-information-2 text-4xl mb-2"></i>
                    <p>Geen inhoud beschikbaar</p>
                </div>
                @endif
            </div>
        </div>
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
