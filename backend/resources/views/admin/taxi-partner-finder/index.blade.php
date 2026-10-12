@extends('admin.layouts.app')

@section('title', 'Taxi Partner Finder')

@section('content')
<div class="kt-container-fixed min-w-0 w-full">
    <div class="pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Taxi Partner Finder</h1>
        <p class="text-sm text-muted-foreground mt-2 mb-0">
            Landelijke ontdekking van taxibedrijven (KNV, CSV, Google Places). Gevonden bedrijven worden
            <strong>niet</strong> automatisch NexaTaxi-partners. Voor nieuwsbriefbenadering kun je contact_ready
            prospects naar de klantenlijst pushen.
        </p>
        <div class="pt-3 flex flex-wrap w-full items-center gap-3">
            <a href="{{ route('admin.newsletters.prospects') }}" class="kt-btn kt-btn-outline shrink-0">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Klantenlijst nieuwsbrief
            </a>
            <a href="{{ route('admin.taxi-partner-finder.export') }}" class="kt-btn kt-btn-outline shrink-0 ms-auto">
                CSV-export
            </a>
        </div>
    </div>

    <div class="grid gap-5 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 mb-5">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-content p-5">
                <p class="text-xs text-muted-foreground mb-1">Totaal gevonden</p>
                <p class="text-2xl font-semibold text-mono mb-0">{{ $stats['total'] }}</p>
            </div>
        </div>
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-content p-5">
                <p class="text-xs text-muted-foreground mb-1">KNV-leden</p>
                <p class="text-2xl font-semibold text-mono mb-0">{{ $stats['knv'] }}</p>
            </div>
        </div>
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-content p-5">
                <p class="text-xs text-muted-foreground mb-1">Zonder e-mail</p>
                <p class="text-2xl font-semibold text-mono mb-0">{{ $stats['missing_email'] }}</p>
            </div>
        </div>
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-content p-5">
                <p class="text-xs text-muted-foreground mb-1">Contact ready</p>
                <p class="text-2xl font-semibold text-mono mb-0">{{ $stats['contact_ready'] }}</p>
            </div>
        </div>
    </div>

    <div class="grid gap-5 grid-cols-1 lg:grid-cols-2 mb-5">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h5 class="kt-card-title mb-0">KNV-ledenoverzicht</h5>
            </div>
            <div class="kt-card-content p-5 lg:p-6">
                <p class="text-sm text-muted-foreground mb-4">
                    Bron: openbaar ledenoverzicht op knv.nl. Respecteert rate limits; geen CAPTCHA-omzeiling.
                    Herhaalde sync werkt bestaande records bij zonder duplicaten.
                </p>
                <form method="POST" action="{{ route('admin.taxi-partner-finder.sync-knv') }}" data-validate="true" novalidate>
                    @csrf
                    <label class="kt-label flex items-center gap-2 mb-3">
                        <input type="checkbox" name="push_newsletter" value="1" class="kt-checkbox">
                        <span class="text-sm text-muted-foreground">Ook naar nieuwsbrief-klantenlijst (alleen mét e-mail)</span>
                    </label>
                    <label class="kt-label flex items-center gap-2 mb-4">
                        <input type="checkbox" name="async" value="1" class="kt-checkbox">
                        <span class="text-sm text-muted-foreground">Via queue (achtergrond)</span>
                    </label>
                    <button type="submit" class="kt-btn kt-btn-primary">KNV synchroniseren</button>
                </form>
            </div>
        </div>

        <div class="kt-card w-full min-w-0" id="csv">
            <div class="kt-card-header px-5 py-5">
                <h5 class="kt-card-title mb-0">KNV CSV-import</h5>
            </div>
            <div class="kt-card-content p-5 lg:p-6">
                <p class="text-sm text-muted-foreground mb-4">
                    Voor een door KNV verstrekte ledenlijst. Kolommen o.a. bedrijfsnaam, postcode, plaats, telefoon, e-mail, website, kvk.
                </p>
                <form method="POST" action="{{ route('admin.taxi-partner-finder.import-csv') }}" enctype="multipart/form-data" data-validate="true" novalidate>
                    @csrf
                    <label class="kt-label mb-1" for="csv">CSV-bestand</label>
                    <input type="file" name="csv" id="csv" accept=".csv,text/csv,text/plain" class="kt-input w-full mb-3" required>
                    <label class="kt-label flex items-center gap-2 mb-4">
                        <input type="checkbox" name="push_newsletter" value="1" class="kt-checkbox">
                        <span class="text-sm text-muted-foreground">Ook naar nieuwsbrief-klantenlijst</span>
                    </label>
                    <button type="submit" class="kt-btn kt-btn-outline">CSV importeren</button>
                </form>
            </div>
        </div>
    </div>

    @if($stats['by_province']->isNotEmpty())
        <div class="kt-card mb-5 w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h5 class="kt-card-title mb-0">Per provincie</h5>
            </div>
            <div class="kt-card-content p-5">
                <div class="flex flex-wrap gap-3">
                    @foreach($stats['by_province'] as $province => $count)
                        <a href="{{ route('admin.taxi-partner-finder.index', ['province' => $province]) }}"
                           class="inline-flex items-center gap-2 rounded-md border border-input px-3 py-1.5 text-sm hover:border-primary">
                            <span>{{ $province }}</span>
                            <span class="text-mono font-semibold">{{ $count }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="kt-card w-full min-w-0 mb-5">
        <div class="kt-card-header px-5 py-5">
            <h5 class="kt-card-title mb-0">Filters</h5>
        </div>
        <div class="kt-card-content p-5">
            <form method="GET" class="admin-filter-panel flex flex-col sm:flex-row flex-wrap gap-2.5 items-stretch sm:items-end">
                <label class="kt-input w-full sm:w-64 min-w-0">
                    <i class="ki-filled ki-magnifier"></i>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Bedrijf, plaats, e-mail…">
                </label>
                <select name="province" class="kt-select admin-field-fit" data-kt-select="true">
                    <option value="">Alle provincies</option>
                    @foreach($provinces as $province)
                        <option value="{{ $province }}" @selected(($filters['province'] ?? '') === $province)>{{ $province }}</option>
                    @endforeach
                </select>
                <select name="knv" class="kt-select admin-field-fit" data-kt-select="true">
                    <option value="">KNV: alle</option>
                    <option value="member" @selected(($filters['knv'] ?? '') === 'member')>KNV-lid</option>
                    <option value="unknown" @selected(($filters['knv'] ?? '') === 'unknown')>Onbekend</option>
                    <option value="not_member" @selected(($filters['knv'] ?? '') === 'not_member')>Geen lid</option>
                </select>
                <select name="source" class="kt-select admin-field-fit" data-kt-select="true">
                    <option value="">Bron: alle</option>
                    <option value="knv" @selected(($filters['source'] ?? '') === 'knv')>KNV</option>
                    <option value="knv_csv" @selected(($filters['source'] ?? '') === 'knv_csv')>KNV CSV</option>
                    <option value="google_places" @selected(($filters['source'] ?? '') === 'google_places')>Google Places</option>
                    <option value="kvk" @selected(($filters['source'] ?? '') === 'kvk')>KvK</option>
                </select>
                <select name="has_email" class="kt-select admin-field-fit" data-kt-select="true">
                    <option value="">E-mail: alle</option>
                    <option value="1" @selected(($filters['has_email'] ?? '') === '1')>Met e-mail</option>
                    <option value="0" @selected(($filters['has_email'] ?? '') === '0')>Zonder e-mail</option>
                </select>
                <select name="lead_status" class="kt-select admin-field-fit" data-kt-select="true">
                    <option value="">Status: alle</option>
                    @foreach($leadStatuses as $status)
                        <option value="{{ $status }}" @selected(($filters['lead_status'] ?? '') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <button type="submit" class="kt-btn kt-btn-primary">Filter</button>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.taxi-partner-finder.prepare-invitations') }}" class="w-full min-w-0">
        @csrf
        <div class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header px-5 py-5 flex flex-wrap items-center justify-between gap-3">
                <h5 class="kt-card-title mb-0">Gevonden bedrijven</h5>
                <button type="submit" class="kt-btn kt-btn-outline shrink-0">Uitnodigingen voorbereiden (selectie)</button>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap min-w-0">
                    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                        <tr>
                            <th class="admin-table__check-col text-center" data-no-row-link data-label="">
                                <label class="kt-label mb-0 inline-flex items-center justify-center cursor-pointer">
                                    <input type="checkbox" class="kt-checkbox" id="tpf-select-all" aria-label="Alles selecteren">
                                </label>
                            </th>
                            <th data-label="Bedrijf">Bedrijf</th>
                            <th data-label="Plaats">Plaats</th>
                            <th data-label="Provincie">Provincie</th>
                            <th data-label="Contact">Contact</th>
                            <th data-label="KNV">KNV</th>
                            <th data-label="Status">Status</th>
                            <th data-label="Bron">Bron</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($prospects as $prospect)
                            <tr>
                                <td class="admin-table__check-col text-center" data-no-row-link>
                                    <label class="kt-label mb-0 inline-flex items-center justify-center cursor-pointer">
                                        <input type="checkbox" name="ids[]" value="{{ $prospect->id }}" class="kt-checkbox tpf-row-check">
                                    </label>
                                </td>
                                <td data-label="Bedrijf">
                                    <a href="{{ route('admin.taxi-partner-finder.show', $prospect) }}" class="font-medium text-foreground hover:text-primary">
                                        {{ $prospect->company_name }}
                                    </a>
                                </td>
                                <td data-label="Plaats">{{ $prospect->city ?: '—' }}</td>
                                <td data-label="Provincie">{{ $prospect->province ?: '—' }}</td>
                                <td data-label="Contact">
                                    <div class="text-xs min-w-0">
                                        <div class="break-all">{{ $prospect->email ?: 'geen e-mail' }}</div>
                                        <div class="text-muted-foreground">{{ $prospect->phone ?: '' }}</div>
                                    </div>
                                </td>
                                <td data-label="KNV">{{ $prospect->knv_member_status }}</td>
                                <td data-label="Status">{{ $prospect->lead_status }}</td>
                                <td data-label="Bron">{{ $prospect->sources->pluck('source_type')->unique()->implode(', ') ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-5 text-sm text-muted-foreground">Nog geen prospects. Start een KNV-sync of CSV-import.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($prospects->hasPages())
                    <div class="px-5 py-5">{{ $prospects->links() }}</div>
                @endif
            </div>
        </div>
    </form>

    @if($runs->isNotEmpty())
        <div class="kt-card mt-5 w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h5 class="kt-card-title mb-0">Recente discovery-runs</h5>
            </div>
            <div class="kt-card-content p-5">
                <ul class="text-sm space-y-2 mb-0">
                    @foreach($runs as $run)
                        <li>
                            <span class="font-medium">{{ $run->source_type }}</span>
                            — {{ $run->status }}
                            (nieuw {{ $run->discovered_count }}, update {{ $run->updated_count }}, dup {{ $run->duplicate_count }})
                            <span class="text-muted-foreground">{{ $run->started_at?->format('d-m-Y H:i') }}</span>
                            @if($run->error_summary)
                                <span class="text-destructive">{{ $run->error_summary }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const all = document.getElementById('tpf-select-all');
    if (!all) return;
    all.addEventListener('change', function () {
        document.querySelectorAll('.tpf-row-check').forEach((el) => { el.checked = all.checked; });
    });
})();
</script>
@endpush
