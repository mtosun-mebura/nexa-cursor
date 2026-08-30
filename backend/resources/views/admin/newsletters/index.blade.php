@extends('admin.layouts.app')

@section('title', 'Nieuwsbrieven')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">Nieuwsbrieven</h1>
            <p class="text-sm text-muted-foreground mt-2 mb-0">
                Ontwerp een wervende mail, zoek taxibedrijven per provincie en verstuur naar de opt-inlijst.
            </p>
        </div>
        <a href="{{ route('admin.newsletters.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>
            Nieuwe nieuwsbrief
        </a>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <div class="flex flex-col sm:flex-row lg:px-10 py-1.5 gap-2">
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

    <div class="grid gap-5 md:grid-cols-3 mb-7.5">
        <a href="{{ route('admin.newsletters.create') }}" class="kt-card hover:border-primary transition-colors">
            <div class="kt-card-content p-5">
                <h3 class="text-base font-semibold text-mono mb-1">Designer</h3>
                <p class="text-sm text-muted-foreground mb-0">AI schrijft de tekst. U kiest de plaatjes en de contactknop.</p>
            </div>
        </a>
        <a href="{{ route('admin.newsletters.prospects') }}" class="kt-card hover:border-primary transition-colors">
            <div class="kt-card-content p-5">
                <h3 class="text-base font-semibold text-mono mb-1">Klantenlijst</h3>
                <p class="text-sm text-muted-foreground mb-0">Zoek taxi-bedrijven per branche en provincie. Opslaan als opt-in.</p>
            </div>
        </a>
        <a href="{{ route('admin.newsletters.send') }}" class="kt-card hover:border-primary transition-colors">
            <div class="kt-card-content p-5">
                <h3 class="text-base font-semibold text-mono mb-1">Versturen</h3>
                <p class="text-sm text-muted-foreground mb-0">Alleen naar ingeschreven adressen. Afmelden stopt de volgende ronde.</p>
            </div>
        </a>
    </div>

    <div class="kt-card kt-card-grid min-w-full">
        <div class="kt-card-header py-5 px-5">
            <h3 class="kt-card-title mb-0">Campagnes</h3>
        </div>
        <div class="kt-card-content">
            @if($campaigns->isEmpty())
                <p class="text-sm text-muted-foreground mb-0 p-5">Nog geen nieuwsbrief. Start in de designer; AI vult de eerste versie.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="kt-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th>Naam</th>
                                <th>Onderwerp</th>
                                <th>Status</th>
                                <th>Laatst verstuurd</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaigns as $campaign)
                                <tr>
                                    <td class="font-medium text-mono">{{ $campaign->name }}</td>
                                    <td>{{ $campaign->subject }}</td>
                                    <td>
                                        <span class="kt-badge {{ $campaign->status === 'sent' ? 'kt-badge-success' : 'kt-badge-secondary' }}">
                                            {{ $campaign->status === 'sent' ? 'Verstuurd' : 'Concept' }}
                                        </span>
                                    </td>
                                    <td>{{ $campaign->last_sent_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') ?: '—' }}</td>
                                    <td class="text-end">
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
