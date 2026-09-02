@extends('admin.companies.wizard.layout')

@section('title', 'Stap 6 — Frontend / website')

@section('wizard_content')
<form method="post" action="{{ route('admin.companies.wizard.submit-step', [$company, 6]) }}">
    @csrf
    <div class="kt-card w-full min-w-0 mb-6 overflow-hidden">
        <div class="kt-card-header px-5 py-5">
            <h3 class="kt-card-title mb-0">Website & pagina’s</h3>
        </div>
        <div class="kt-card-content p-5 min-w-0">
            <div class="flex flex-col gap-2 sm:flex-row sm:gap-6 min-w-0">
                <div class="shrink-0 text-sm text-secondary-foreground font-normal w-24 sm:pt-0.5">Uitleg</div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-secondary-foreground mb-4 break-words">
                        Stel de openbare website samen met de website-builder: pagina’s, thema-blokken en componenten. Dit is meestal een globale configuratie; voor tenant-specifieke sites volgt later koppeling per domein.
                    </p>
                    @if(! empty($canConfigureWebsite))
                        <a href="{{ route('admin.website-pages.index', ['from_wizard' => 1, 'wizard_company' => $company->id, 'wizard_step' => $currentStep]) }}" class="kt-btn kt-btn-outline">
                            <i class="ki-filled ki-screen me-2"></i>
                            Website-pagina’s beheren
                        </a>
                    @else
                        <p class="text-sm text-muted-foreground mb-0 break-words">Alleen super-admin of gebruikers met website-toegang kunnen website-pagina’s beheren.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="kt-card min-w-full mb-6 pt-6">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Huidige website-pagina’s</h3>
            <p class="text-sm text-muted-foreground mt-1 mb-0 pb-4">
                Dezelfde pagina’s als onder <strong>Front-end -> Pagina’s</strong> op de openbare site in het actieve thema
                @if($activeTheme)
                    (<strong>{{ $activeTheme->name }}</strong>)
                @endif
                . Na koppeling van een domein aan deze tenant worden ze voor die site gebruikt.
            </p>
        </div>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            @if($websitePages->isEmpty())
                <p class="text-sm text-muted-foreground px-5 py-4 mb-0">Er zijn nog geen website-pagina’s geconfigureerd.</p>
            @else
                <table class="kt-table kt-table-border-dashed align-middle text-sm">
                    <thead>
                        <tr class="text-secondary-foreground font-medium">
                            <th class="min-w-[4rem]">Volgorde</th>
                            <th>Titel</th>
                            <th>Slug</th>
                            <th>Type</th>
                            <th>Module</th>
                            <th>Thema</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($websitePages as $page)
                            <tr>
                                <td>{{ $page->sort_order }}</td>
                                <td class="text-foreground">{{ $page->title }}</td>
                                <td><code class="text-xs">{{ $page->slug }}</code></td>
                                <td>{{ $page->page_type }}</td>
                                <td>{{ $page->module_name ?? '—' }}</td>
                                <td>{{ $page->theme?->name ?? ($activeTheme?->name ?? '—') }}</td>
                                <td>
                                    @if($page->is_active)
                                        <span class="kt-badge kt-badge-success">Actief</span>
                                    @else
                                        <span class="kt-badge kt-badge-secondary">Inactief</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <x-wizard.footer-actions :current-step="$currentStep" :company="$company">
        <button type="submit" class="kt-btn kt-btn-primary">
            Volgende
            <i class="ki-filled ki-arrow-right ms-2"></i>
        </button>
    </x-wizard.footer-actions>
</form>
@endsection
