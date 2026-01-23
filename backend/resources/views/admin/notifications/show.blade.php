@extends('admin.layouts.app')

@section('title', 'Notificatie Details - #' . $notification->id)

@section('content')

<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
    /* Notities en feedback tekst links uitlijnen en witte ruimte verwijderen */
    .kt-card-content .kt-input {
        text-align: left !important;
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    .kt-card-content .kt-input:empty {
        display: none;
    }
</style>

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            @php
                $borderColor = 'border-primary';
                $bgColor = 'bg-primary/10';
                $textColor = 'text-primary';
                // Determine color based on notification type or priority
                if ($notification->type === 'interview') {
                    $borderColor = 'border-blue-500';
                    $bgColor = 'bg-blue-500/10';
                    $textColor = 'text-blue-500';
                } elseif ($notification->priority === 'urgent') {
                    $borderColor = 'border-red-500';
                    $bgColor = 'bg-red-500/10';
                    $textColor = 'text-red-500';
                } elseif ($notification->priority === 'high') {
                    $borderColor = 'border-orange-500';
                    $bgColor = 'bg-orange-500/10';
                    $textColor = 'text-orange-500';
                }
            @endphp
            <div class="rounded-full border-3 {{ $borderColor }} size-[100px] shrink-0 flex items-center justify-center {{ $bgColor }} {{ $textColor }} text-2xl font-semibold">
                <i class="ki-filled ki-notification text-4xl"></i>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="text-xl lg:text-2xl leading-6 font-semibold text-mono">
                    Notificatie voor {{ trim(($notification->user->first_name ?? '') . ' ' . ($notification->user->last_name ?? '')) ?: 'Onbekend' }}
                </div>
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-calendar-tick text-base"></i>
                    <span class="text-secondary-foreground">
                        {{ $notification->created_at->format('d-m-Y H:i') }}
                    </span>
                </div>
                <div class="flex gap-1.25 items-center">
                    @php
                        $statusLabel = $notification->read_at ? 'Gelezen' : 'Ongelezen';
                    @endphp
                    <span class="kt-badge kt-badge-sm kt-badge-{{ $notification->read_at ? 'success' : 'warning' }}">
                        {{ $statusLabel }}
                    </span>
                </div>
                @if($notification->priority)
                <div class="flex gap-1.25 items-center">
                    <span class="kt-badge kt-badge-sm kt-badge-{{ $notification->priority == 'urgent' ? 'danger' : ($notification->priority == 'high' ? 'warning' : ($notification->priority == 'low' ? 'secondary' : 'info')) }}">
                        {{ ucfirst($notification->priority) }} prioriteit
                    </span>
                </div>
                @endif
                @php
                    // Check if this is an interview notification with a response
                    $data = $notification->data ? json_decode($notification->data, true) : null;
                    $hasResponse = false;
                    $responseType = null;
                    if ($data && is_array($data) && isset($data['response'])) {
                        $hasResponse = true;
                        $responseType = $data['response'];
                    }
                @endphp
                @if($notification->type === 'interview' && $hasResponse)
                <div class="flex gap-1.25 items-center">
                    @if($responseType === 'accept')
                        <i class="ki-filled ki-check-circle text-green-500 text-lg" title="Geaccepteerd"></i>
                    @elseif($responseType === 'decline')
                        <i class="ki-filled ki-cross-circle text-red-500 text-lg" title="Afgewezen"></i>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.notifications.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        <div class="flex items-center gap-2.5">
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-notifications'))
            <a href="{{ route('admin.notifications.edit', $notification) }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-notepad-edit me-2"></i>
                Bewerken
            </a>
            @endif
        </div>
    </div>

    <div class="flex flex-col xl:flex-row gap-5 lg:gap-7.5 items-stretch">
        <!-- Notificatie Informatie -->
        <div class="kt-card flex-1">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Notificatie Informatie
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">
                            Ontvanger
                        </td>
                        <td class="min-w-48 w-full text-foreground font-normal">
                            @if($notification->user)
                                {{ trim($notification->user->first_name . ' ' . $notification->user->last_name) }}
                            @else
                                Onbekend
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            E-mail
                        </td>
                        <td class="text-foreground font-normal">
                            @if($notification->user)
                                {{ $notification->user->email }}
                            @else
                                Onbekend
                            @endif
                        </td>
                    </tr>
                    @if($notification->user && $notification->user->hasRole('candidate'))
                    @php
                        $candidate = \App\Models\Candidate::where('email', $notification->user->email)->first();
                        $match = null;
                        if ($candidate && $notification->company_id) {
                            $match = \App\Models\JobMatch::whereHas('vacancy', function($vq) use ($notification) {
                                $vq->where('company_id', $notification->company_id);
                            })->where('candidate_id', $candidate->id)->orderBy('created_at', 'desc')->first();
                        }
                    @endphp
                    @if($match && $match->vacancy)
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Vacature
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $match->vacancy->title }}
                        </td>
                    </tr>
                    @endif
                    @endif
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Bedrijf
                        </td>
                        <td class="text-foreground font-normal">
                            @if($notification->user && $notification->user->company)
                                {{ $notification->user->company->name }}
                            @elseif($notification->company_id)
                                @php
                                    $company = \App\Models\Company::find($notification->company_id);
                                @endphp
                                {{ $company ? $company->name : 'Onbekend' }}
                            @else
                                Onbekend
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Afzender
                        </td>
                        <td class="text-foreground font-normal">
                            @if($sender)
                                {{ trim($sender->first_name . ' ' . $sender->last_name) }}
                            @else
                                Systeem
                            @endif
                        </td>
                    </tr>
                    @if($sender)
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Afzender E-mail
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $sender->email }}
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Categorie
                        </td>
                        <td class="text-foreground font-normal">
                            @php
                                $categoryLabels = [
                                    'info' => 'Informatie',
                                    'warning' => 'Waarschuwing',
                                    'success' => 'Succes',
                                    'error' => 'Fout',
                                    'reminder' => 'Herinnering',
                                    'update' => 'Update',
                                ];
                                echo $categoryLabels[$notification->category ?? ''] ?? ucfirst($notification->category ?? 'Onbekend');
                            @endphp
                        </td>
                    </tr>
                    @if($notification->scheduled_at)
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Geplande Datum & Tijd
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $notification->scheduled_at->format('d-m-Y H:i') }}
                        </td>
                    </tr>
                    @endif
                    @if($notification->location)
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Locatie
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $notification->location }}
                        </td>
                    </tr>
                    @endif
                    @if($notification->priority)
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Prioriteit
                        </td>
                        <td class="text-foreground font-normal">
                            <span class="kt-badge kt-badge-sm kt-badge-{{ $notification->priority == 'urgent' ? 'danger' : ($notification->priority == 'high' ? 'warning' : ($notification->priority == 'low' ? 'secondary' : 'info')) }}">
                                {{ ucfirst($notification->priority) }}
                            </span>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Aangemaakt op
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $notification->created_at->format('d-m-Y H:i') }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Bericht & Bestand -->
        <div class="kt-card flex-1">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Bericht & Bestand
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal border-b border-border">
                            Titel
                        </td>
                        <td class="min-w-48 w-full text-foreground font-normal border-b border-border">
                            {{ $notification->title }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal border-b border-border">
                            Type
                        </td>
                        <td class="text-foreground font-normal border-b border-border">
                            @php
                                $typeLabels = [
                                    'match' => 'Match',
                                    'interview' => 'Interview',
                                    'application' => 'Sollicitatie',
                                    'system' => 'Systeem',
                                    'email' => 'E-mail',
                                    'reminder' => 'Herinnering',
                                    'file' => 'Bestand',
                                ];
                                echo $typeLabels[$notification->type ?? ''] ?? ucfirst($notification->type ?? 'Onbekend');
                            @endphp
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal border-b border-border">
                            Status
                        </td>
                        <td class="text-foreground font-normal border-b border-border">
                            <span class="kt-badge kt-badge-sm kt-badge-{{ $notification->read_at ? 'success' : 'warning' }}">
                                {{ $notification->read_at ? 'Gelezen' : 'Ongelezen' }}
                            </span>
                        </td>
                    </tr>
                    @if($notification->read_at)
                    <tr>
                        <td class="text-secondary-foreground font-normal border-b border-border">
                            Gelezen op
                        </td>
                        <td class="text-foreground font-normal border-b border-border">
                            {{ $notification->read_at->format('d-m-Y H:i') }}
                        </td>
                    </tr>
                    @endif
                    @if($notification->message)
                    <tr>
                        <td class="text-secondary-foreground font-normal border-b border-border">
                            Bericht
                        </td>
                        <td class="text-foreground font-normal border-b border-border break-words">
                            @php
                                $messageText = trim($notification->message);
                                $formattedMessage = $messageText;

                                // Parse message to format "Datum:" and "Bericht:" on separate lines
                                if (strpos($messageText, 'Datum:') !== false || strpos($messageText, 'Bericht:') !== false) {
                                    $parts = [];

                                    // Extract message if present
                                    if (preg_match('/Bericht:\s*(.+?)(?:\s*$|(?=\s*(?:Datum:|Locatie:|Afspraakdetails:)))/s', $messageText, $berichtMatches)) {
                                        $berichtText = trim($berichtMatches[1]);
                                        // Remove any trailing "Datum:" or "Locatie:" that might be in the message
                                        $berichtText = preg_replace('/\s*(?:Datum:|Locatie:).*$/s', '', $berichtText);
                                        $berichtText = trim($berichtText);
                                        if ($berichtText) {
                                            $parts[] =  'Bericht: ' . $berichtText;
                                        }
                                    }

                                    // Extract location if present (Afspraakdetails)
                                    if (preg_match('/Afspraakdetails:\s*(.+?)(?:\s*$)/s', $messageText, $locationMatches)) {
                                        $parts[] = 'Afspraakdetails: ' . trim($locationMatches[1]);
                                    }

                                    // If we found structured parts, use them; otherwise use original message
                                    if (!empty($parts)) {
                                        // Get the part before "Datum:" if it exists (like "Name Heeft je interview uitnodiging geaccepteerd.")
                                        $beforeDatum = '';
                                        if (preg_match('/^(.+?)(?=\s*Datum:)/', $messageText, $beforeMatches)) {
                                            $beforeDatum = trim($beforeMatches[1]);
                                        }

                                        $formattedMessage = '';
                                        if ($beforeDatum) {
                                            $formattedMessage = $beforeDatum;
                                        }
                                        if (!empty($parts)) {
                                            // Add line break before bericht text if there's content before it
                                            if (!empty($formattedMessage)) {
                                                $formattedMessage .= '<br><br>';
                                            }
                                            $formattedMessage .= implode('<br>', $parts);
                                        }
                                    }
                                }
                            @endphp
                            {!! $formattedMessage !!}
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
            <div class="kt-card-content">
                @if($notification->file_path)
                <div>
                    <h4 class="text-sm font-semibold text-secondary-foreground mb-2">Bestand</h4>
                    <div>
                        <a href="{{ \Storage::url($notification->file_path) }}" target="_blank" class="kt-btn kt-btn-outline">
                            <i class="ki-filled ki-file me-2"></i>
                            {{ $notification->file_name ?? 'Download bestand' }}
                        </a>
                        @if($notification->file_size)
                        <span class="text-xs text-muted-foreground ml-2">
                            ({{ number_format($notification->file_size / 1024, 2) }} KB)
                        </span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
