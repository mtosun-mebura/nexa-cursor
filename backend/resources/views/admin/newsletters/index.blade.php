@extends('admin.layouts.app')

@section('title', 'Nieuwsbrieven')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div class="min-w-0">
            <h1 class="text-xl font-medium leading-none text-mono">Nieuwsbrieven</h1>
            <p class="text-sm text-muted-foreground mt-2 mb-0">
                Ontwerp een wervende mail, zoek taxibedrijven per provincie en verstuur naar de opt-inlijst.
            </p>
        </div>
        <a href="{{ route('admin.newsletters.create') }}" class="kt-btn kt-btn-primary shrink-0">
            <i class="ki-filled ki-plus me-2"></i>
            Nieuwe nieuwsbrief
        </a>
    </div>

    <div class="kt-card mb-5 w-full min-w-0">
        <div class="kt-card-content p-5">
            <div class="flex flex-col sm:flex-row py-1.5 gap-2 min-w-0">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl leading-none font-semibold">{{ $campaigns->count() }}</span>
                    <span class="text-secondary-foreground text-sm">Campagnes</span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl leading-none font-semibold">{{ $subscribedCount }}</span>
                    <span class="text-secondary-foreground text-sm">Opt-in</span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl leading-none font-semibold">{{ $unsubscribedCount }}</span>
                    <span class="text-secondary-foreground text-sm">Afgemeld</span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl leading-none font-semibold">{{ $prospectCount }}</span>
                    <span class="text-secondary-foreground text-sm">Totaal in lijst</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-7.5">
        <a href="{{ route('admin.newsletters.create') }}" class="kt-card hover:border-primary transition-colors w-full min-w-0">
            <div class="kt-card-content p-5">
                <h3 class="text-base font-semibold text-mono mb-1">Designer</h3>
                <p class="text-sm text-muted-foreground mb-0">AI schrijft de tekst. U kiest de plaatjes en de contactknop.</p>
            </div>
        </a>
        <a href="{{ route('admin.newsletters.prospects') }}" class="kt-card hover:border-primary transition-colors w-full min-w-0">
            <div class="kt-card-content p-5">
                <h3 class="text-base font-semibold text-mono mb-1">Klantenlijst</h3>
                <p class="text-sm text-muted-foreground mb-0">Zoek taxi-bedrijven per branche en provincie. Opslaan als opt-in.</p>
            </div>
        </a>
        <a href="{{ route('admin.newsletters.send') }}" class="kt-card hover:border-primary transition-colors w-full min-w-0 sm:col-span-2 lg:col-span-1">
            <div class="kt-card-content p-5">
                <h3 class="text-base font-semibold text-mono mb-1">Versturen</h3>
                <p class="text-sm text-muted-foreground mb-0">Alleen naar ingeschreven adressen. Afmelden stopt de volgende ronde.</p>
            </div>
        </a>
    </div>

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-header py-5 px-5">
            <h3 class="kt-card-title mb-0">Campagnes</h3>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            @if($campaigns->isEmpty())
                <p class="text-sm text-muted-foreground mb-0 p-5">Nog geen nieuwsbrief. Start in de designer; AI vult de eerste versie.</p>
            @else
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap min-w-0">
                    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="Naam">Naam</th>
                                <th data-label="Onderwerp">Onderwerp</th>
                                <th data-label="Status">Status</th>
                                <th data-label="Laatst verstuurd">Laatst verstuurd</th>
                                <th data-label="Acties" class="text-center">Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaigns as $campaign)
                                <tr>
                                    <td data-label="Naam" class="font-medium text-mono">{{ $campaign->name }}</td>
                                    <td data-label="Onderwerp">{{ $campaign->subject }}</td>
                                    <td data-label="Status">
                                        <span class="kt-badge {{ $campaign->status === 'sent' ? 'kt-badge-success' : 'kt-badge-secondary' }}">
                                            {{ $campaign->status === 'sent' ? 'Verstuurd' : 'Concept' }}
                                        </span>
                                    </td>
                                    <td data-label="Laatst verstuurd">{{ $campaign->last_sent_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') ?: '—' }}</td>
                                    <td data-label="Acties" class="text-end">
                                        <a href="{{ route('admin.newsletters.edit', $campaign) }}" class="kt-btn kt-btn-sm kt-btn-outline">Bewerken</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
