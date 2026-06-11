@extends('admin.layouts.app')

@section('title', 'AI-chatbot kennis')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-5">
        <div class="min-w-0">
            <h1 class="text-xl font-medium leading-none text-mono">AI-chatbot</h1>
            <p class="text-secondary-foreground text-sm mt-2">Beheer kennisdocumenten voor de taxi-assistent.</p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('ai_chatbot.create') || auth()->user()->can('rides.update') || auth()->user()->can('vehicles.update'))
            <form action="{{ route('admin.taxi.knowledge_documents.generate_from_website') }}" method="POST" class="inline"
                  onsubmit="return confirm('Website-inhoud importeren of bijwerken? Dit omvat diensten, contactgegevens, het boekingsformulier, contactformulier en veelgestelde vragen over de website.');">
                @csrf
                <button type="submit" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-technology-2 me-2"></i>
                    Genereer content obv website
                </button>
            </form>
            <a href="{{ route('admin.taxi.knowledge_documents.create') }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Nieuw document
            </a>
            @endif
        </div>
    </div>

    @include('taxi::admin.ai_chatbot.partials.subnav')

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <i class="ki-filled ki-cross-circle me-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-header py-5 flex-wrap gap-2 min-w-0">
            <h3 class="kt-card-title text-sm pb-3 w-full mb-0">
                <span data-admin-datatable-info="true">Toon 1 tot {{ $documents->count() }} van {{ $documents->count() }} documenten</span>
            </h3>
            <div class="admin-filter-panel flex flex-col sm:flex-row flex-wrap gap-2.5 w-full sm:w-auto min-w-0 items-stretch sm:items-center"
                 data-admin-live-filter="off">
                <label class="kt-input w-full sm:w-64 min-w-0">
                    <i class="ki-filled ki-magnifier"></i>
                    <input placeholder="Zoek titel of inhoud..."
                           type="text"
                           name="search"
                           id="knowledge-search-input"
                           value=""
                           autocomplete="off">
                </label>
                <select class="kt-select w-full sm:w-44" id="knowledge-category-filter" name="category" data-admin-datatable-filter="category">
                    <option value="">Alle categorieën</option>
                    @foreach($categoryLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button"
                        id="knowledge-filter-reset"
                        data-admin-datatable-reset
                        class="kt-btn kt-btn-outline kt-btn-icon shrink-0 hidden"
                        title="Filters resetten">
                    <i class="ki-filled ki-arrows-circle text-base"></i>
                </button>
            </div>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            @if($documents->count() > 0)
            <div class="grid w-full min-w-0" data-admin-datatable="true" data-admin-datatable-page-size="20" id="knowledge_documents_table" data-admin-datatable-label="documenten">
            <div class="knowledge-documents-table-wrap min-w-0">
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full knowledge-documents-table" id="knowledge-documents-table">
                    <colgroup>
                        <col class="knowledge-documents-table__col-title">
                        <col class="knowledge-documents-table__col-category">
                        <col class="knowledge-documents-table__col-content">
                        <col class="knowledge-documents-table__col-created">
                        <col class="knowledge-documents-table__col-actions">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-secondary-foreground font-normal text-left" data-label="Titel">Titel</th>
                            <th class="text-secondary-foreground font-normal text-left" data-label="Categorie">Categorie</th>
                            <th class="text-secondary-foreground font-normal text-left" data-label="Inhoud">Inhoud</th>
                            <th class="text-secondary-foreground font-normal text-left" data-label="Aangemaakt">Aangemaakt</th>
                            <th class="knowledge-documents-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $document)
                        @php
                            $categoryLabel = $categoryLabels[$document->category] ?? $document->category;
                            $plainContent = trim(html_entity_decode(strip_tags((string) $document->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                            $plainContent = preg_replace('/\s+/u', ' ', $plainContent) ?? '';
                            $searchText = mb_strtolower($document->title.' '.$categoryLabel.' '.$document->category.' '.$plainContent, 'UTF-8');
                        @endphp
                        <tr data-row-href="{{ route('admin.taxi.knowledge_documents.show', $document) }}"
                            data-category="{{ $document->category }}"
                            data-search-text="{{ $searchText }}">
                            <td class="font-medium text-mono knowledge-documents-table__title">{{ $document->title }}</td>
                            <td>
                                <span class="sr-only knowledge-row-category" aria-hidden="true" data-category="{{ $document->category }}">{{ $categoryLabel }}</span>
                                <span class="kt-badge kt-badge-outline kt-badge-sm">
                                    {{ $categoryLabel }}
                                </span>
                            </td>
                            <td class="text-secondary-foreground text-sm knowledge-documents-table__content">
                                <span class="sr-only knowledge-row-content" aria-hidden="true">{{ $plainContent }}</span>
                                {{ \Illuminate\Support\Str::limit($plainContent, 140) }}
                            </td>
                            <td class="knowledge-documents-table__datetime text-secondary-foreground text-sm">
                                @if($document->created_at)
                                    <span class="block whitespace-nowrap">{{ $document->created_at->format('d-m-Y') }}</span>
                                    <span class="block whitespace-nowrap text-muted-foreground text-xs">{{ $document->created_at->format('H:i') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            @php
                                $canEditDocument = auth()->user()->hasRole('super-admin') || auth()->user()->can('ai_chatbot.update') || auth()->user()->can('rides.update') || auth()->user()->can('vehicles.update');
                                $canDeleteDocument = auth()->user()->hasRole('super-admin') || auth()->user()->can('ai_chatbot.delete') || auth()->user()->can('rides.delete') || auth()->user()->can('vehicles.delete');
                            @endphp
                            <td class="knowledge-documents-table__actions-col knowledge-documents-table__actions" data-no-row-link>
                                @if($canEditDocument || $canDeleteDocument)
                                <div class="kt-menu flex justify-center" data-kt-menu="true">
                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                        <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" type="button" aria-label="Acties">
                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                        </button>
                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                            @if($canEditDocument)
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.taxi.knowledge_documents.edit', $document) }}">
                                                    <span class="kt-menu-icon">
                                                        <i class="ki-filled ki-pencil"></i>
                                                    </span>
                                                    <span class="kt-menu-title">Bewerken</span>
                                                </a>
                                            </div>
                                            @endif
                                            @if($canDeleteDocument)
                                            @if($canEditDocument)
                                            <div class="kt-menu-separator"></div>
                                            @endif
                                            <div class="kt-menu-item">
                                                <form action="{{ route('admin.taxi.knowledge_documents.destroy', $document) }}" method="POST" class="block" onsubmit="return confirm('Weet je zeker dat je dit document wilt verwijderen?');">
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
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
            <div class="kt-card-footer admin-datatable-footer text-secondary-foreground text-sm font-medium pt-5 min-w-0">
                <div class="admin-datatable-footer__perpage flex flex-wrap items-center gap-2">
                    Toon
                    <select class="kt-select w-24" data-admin-datatable-size="true" data-kt-select="" name="perpage">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="20" selected>20</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    per pagina
                </div>
                <div class="admin-datatable-footer__pagination max-w-full overflow-x-auto">
                    <div class="kt-datatable-pagination" data-admin-datatable-pagination="true"></div>
                </div>
                <span class="admin-datatable-footer__info" data-admin-datatable-info="true"></span>
            </div>
            </div>
            @else
            <div class="py-10 px-3 sm:px-5 text-center text-secondary-foreground">
                <p class="mb-4">Nog geen kennisdocumenten.</p>
                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('ai_chatbot.create') || auth()->user()->can('rides.update') || auth()->user()->can('vehicles.update'))
                <p class="text-sm">Voeg handmatig content toe of gebruik <strong>Genereer content obv website</strong> om diensten en pagina-inhoud automatisch te importeren.</p>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .knowledge-documents-table-wrap .kt-scrollable-x-auto,
    .knowledge-documents-table-wrap .admin-table-scroll-wrap,
    .knowledge-documents-table-wrap .admin-desktop-table-wrap {
        overflow-x: auto !important;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: touch;
        max-width: 100%;
        width: 100%;
        padding: 0 !important;
    }

    #content .knowledge-documents-table-wrap .admin-table-scroll-wrap .kt-table {
        width: 100%;
        min-width: 100%;
    }

    #content #knowledge-documents-table col.knowledge-documents-table__col-title { width: 17%; }
    #content #knowledge-documents-table col.knowledge-documents-table__col-category { width: 11%; }
    #content #knowledge-documents-table col.knowledge-documents-table__col-created { width: 13%; }
    #content #knowledge-documents-table col.knowledge-documents-table__col-actions { width: 4rem; }

    #content #knowledge-documents-table .knowledge-documents-table__title,
    #content #knowledge-documents-table .knowledge-documents-table__content {
        max-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #content #knowledge-documents-table .knowledge-documents-table__datetime {
        vertical-align: middle;
        line-height: 1.35;
        white-space: nowrap;
    }

    #content #knowledge-documents-table .knowledge-documents-table__actions-col {
        width: 4rem !important;
        min-width: 4rem !important;
        max-width: 4rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }

    #content #knowledge-documents-table .knowledge-documents-table__actions .kt-menu {
        display: flex !important;
        justify-content: center !important;
        width: 100%;
        margin-inline: auto;
    }
</style>
@endpush
