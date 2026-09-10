@extends('admin.layouts.app')

@section('title', 'Notificatie Details - #' . $notification->id)

@php
    $canDeleteNotification = auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-notifications');
@endphp

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
    .notification-detail-fields {
        container-type: inline-size;
        container-name: notification-detail;
    }
    .notification-detail-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.25rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border, #e2e8f0);
    }
    .notification-detail-row:first-child {
        padding-top: 0;
    }
    .notification-detail-row:last-child {
        padding-bottom: 0;
        border-bottom: none;
    }
    .notification-detail-row dt {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 400;
        color: var(--muted-foreground, #64748b);
    }
    .notification-detail-row dd {
        margin: 0;
        min-width: 0;
        font-size: 0.875rem;
        font-weight: 400;
        color: var(--foreground, #0f172a);
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    @container notification-detail (min-width: 36rem) {
        .notification-detail-row {
            grid-template-columns: 8.5rem minmax(0, 1fr);
            gap: 0 1rem;
            align-items: start;
        }
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
            @php
                // Check if this is a change notification
                $notificationData = $notification->data ? json_decode($notification->data, true) : [];
                $isChangeNotification = ($notification->title === 'Interview gewijzigd') || 
                                       (isset($notificationData['is_change_notification']) && $notificationData['is_change_notification']);
                
                // Check if there's a scheduled interview
                $hasScheduledInterview = false;
                if (isset($notificationData['interview_id']) && $notificationData['interview_id']) {
                    $interview = \App\Models\Interview::find($notificationData['interview_id']);
                    if ($interview && $interview->status === 'scheduled') {
                        $hasScheduledInterview = true;
                    }
                }
                
                // Hide edit button if it's a change notification or if there's a scheduled interview
                $showEditButton = !$isChangeNotification && !$hasScheduledInterview;
            @endphp
            @if($canDeleteNotification)
            <form method="POST"
                  action="{{ route('admin.notifications.destroy', $notification) }}"
                  id="notification-delete-form"
                  class="m-0">
                @csrf
                @method('DELETE')
            </form>
            <button type="button"
                    id="notification-delete-open"
                    class="kt-btn kt-btn-destructive"
                    aria-haspopup="dialog"
                    aria-controls="notifications-delete-modal">
                <i class="ki-filled ki-trash me-2"></i>
                Verwijderen
            </button>
            @endif
            @if((auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-notifications')) && $showEditButton)
            <a href="{{ route('admin.notifications.edit', $notification) }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-notepad-edit me-2"></i>
                Bewerken
            </a>
            @endif
        </div>
    </div>

    <div class="flex flex-col xl:flex-row gap-5 lg:gap-7.5 items-stretch min-w-0">
        <!-- Notificatie Informatie -->
        <div class="kt-card flex-1 min-w-0">
            <div class="kt-card-header flex items-center px-5 py-5">
                <h3 class="kt-card-title mb-0">
                    Notificatie Informatie
                </h3>
            </div>
            <div class="kt-card-content p-5 notification-detail-fields">
                <dl class="m-0">
                    <div class="notification-detail-row">
                        <dt>Ontvanger</dt>
                        <dd>
                            @if($notification->user)
                                {{ trim($notification->user->first_name . ' ' . $notification->user->last_name) }}
                            @else
                                Onbekend
                            @endif
                        </dd>
                    </div>
                    <div class="notification-detail-row">
                        <dt>E-mail</dt>
                        <dd>
                            @if($notification->user)
                                {{ $notification->user->email }}
                            @else
                                Onbekend
                            @endif
                        </dd>
                    </div>
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
                    <div class="notification-detail-row">
                        <dt>Vacature</dt>
                        <dd>{{ $match->vacancy->title }}</dd>
                    </div>
                    @endif
                    @endif
                    <div class="notification-detail-row">
                        <dt>Bedrijf</dt>
                        <dd>
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
                        </dd>
                    </div>
                    <div class="notification-detail-row">
                        <dt>Afzender</dt>
                        <dd>
                            @if($sender)
                                {{ trim($sender->first_name . ' ' . $sender->last_name) }}
                            @else
                                Systeem
                            @endif
                        </dd>
                    </div>
                    @if($sender)
                    <div class="notification-detail-row">
                        <dt>Afzender E-mail</dt>
                        <dd>{{ $sender->email }}</dd>
                    </div>
                    @endif
                    <div class="notification-detail-row">
                        <dt>Categorie</dt>
                        <dd>
                            @php
                                $categoryLabels = [
                                    'info' => 'Informatie',
                                    'warning' => 'Waarschuwing',
                                    'success' => 'Succes',
                                    'error' => 'Fout',
                                    'reminder' => 'Herinnering',
                                    'update' => 'Update',
                                    'incident' => 'Incident',
                                ];
                                echo $categoryLabels[$notification->category ?? ''] ?? ucfirst($notification->category ?? 'Onbekend');
                            @endphp
                        </dd>
                    </div>
                    @if($notification->scheduled_at)
                    <div class="notification-detail-row">
                        <dt>Geplande Datum & Tijd</dt>
                        <dd>{{ $notification->scheduled_at->format('d-m-Y H:i') }}</dd>
                    </div>
                    @endif
                    @if($notification->location)
                    <div class="notification-detail-row">
                        <dt>Locatie</dt>
                        <dd>{{ $notification->location }}</dd>
                    </div>
                    @endif
                    @if($notification->priority)
                    <div class="notification-detail-row">
                        <dt>Prioriteit</dt>
                        <dd>
                            <span class="kt-badge kt-badge-sm kt-badge-{{ $notification->priority == 'urgent' ? 'danger' : ($notification->priority == 'high' ? 'warning' : ($notification->priority == 'low' ? 'secondary' : 'info')) }}">
                                {{ ucfirst($notification->priority) }}
                            </span>
                        </dd>
                    </div>
                    @endif
                    <div class="notification-detail-row">
                        <dt>Aangemaakt op</dt>
                        <dd>{{ $notification->created_at->format('d-m-Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Bericht & Bestand -->
        <div class="kt-card flex-1 min-w-0">
            <div class="kt-card-header flex items-center px-5 py-5">
                <h3 class="kt-card-title mb-0">
                    Bericht & Bestand
                </h3>
            </div>
            <div class="kt-card-content p-5 notification-detail-fields">
                <dl class="m-0">
                    <div class="notification-detail-row">
                        <dt>Titel</dt>
                        <dd>{{ $notification->title }}</dd>
                    </div>
                    <div class="notification-detail-row">
                        <dt>Type</dt>
                        <dd>
                            @php
                                $typeLabels = [
                                    'match' => 'Match',
                                    'interview' => 'Sollicitatie',
                                    'system' => 'Systeem',
                                    'email' => 'E-mail',
                                    'reminder' => 'Herinnering',
                                    'file' => 'Bestand',
                                    'incident' => 'Incident',
                                    'config_access' => 'Configuratie',
                                ];
                                echo $typeLabels[$notification->type ?? ''] ?? ucfirst($notification->type ?? 'Onbekend');
                            @endphp
                        </dd>
                    </div>
                    <div class="notification-detail-row">
                        <dt>Status</dt>
                        <dd>
                            <span class="kt-badge kt-badge-sm kt-badge-{{ $notification->read_at ? 'success' : 'warning' }}">
                                {{ $notification->read_at ? 'Gelezen' : 'Ongelezen' }}
                            </span>
                        </dd>
                    </div>
                    @if($notification->read_at)
                    <div class="notification-detail-row">
                        <dt>Gelezen op</dt>
                        <dd>{{ $notification->read_at->format('d-m-Y H:i') }}</dd>
                    </div>
                    @endif
                    @if($notification->message)
                    <div class="notification-detail-row">
                        <dt>Bericht</dt>
                        <dd>
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
                        </dd>
                    </div>
                    @endif
                </dl>
                @if($notification->file_path)
                <div class="mt-5 pt-4 border-t border-border">
                    <h4 class="text-sm font-semibold text-secondary-foreground mb-2">Bestand</h4>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ \Storage::url($notification->file_path) }}" target="_blank" class="kt-btn kt-btn-outline">
                            <i class="ki-filled ki-file me-2"></i>
                            {{ $notification->file_name ?? 'Download bestand' }}
                        </a>
                        @if($notification->file_size)
                        <span class="text-xs text-muted-foreground">
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

@if(!empty($canDeleteNotification))
<div id="notifications-delete-modal"
     class="hidden fixed inset-0 items-center justify-center p-4 z-[100000]"
     role="dialog"
     aria-modal="true"
     aria-labelledby="notifications-delete-modal-title"
     hidden>
    <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-md" data-notifications-delete-dismiss></div>
    <div class="notifications-delete-panel relative z-10 w-full max-w-md rounded-2xl border shadow-2xl">
        <div class="notifications-delete-panel__head px-5 py-5">
            <h3 id="notifications-delete-modal-title" class="notifications-delete-panel__title mb-0">Notificatie verwijderen</h3>
        </div>
        <div class="notifications-delete-panel__body px-5 py-5">
            <p class="notifications-delete-panel__text mb-0">
                Weet je zeker dat je deze notificatie wilt verwijderen? Dit kan niet ongedaan worden gemaakt.
            </p>
        </div>
        <div class="notifications-delete-panel__foot px-5 py-5 flex flex-wrap justify-end gap-2">
            <button type="button" class="kt-btn kt-btn-outline notifications-delete-panel__cancel" data-notifications-delete-dismiss>Annuleren</button>
            <button type="button" class="kt-btn kt-btn-destructive notifications-delete-panel__confirm" data-notifications-delete-confirm>Verwijderen</button>
        </div>
    </div>
</div>
@endif
@endsection

@if(!empty($canDeleteNotification))
@push('styles')
<style>
    #notifications-delete-modal.hidden,
    #notifications-delete-modal[hidden] {
        display: none !important;
        pointer-events: none !important;
    }
    #notifications-delete-modal.flex:not([hidden]):not(.hidden) {
        display: flex !important;
        pointer-events: auto !important;
    }
    .notifications-delete-panel {
        background-color: #ffffff;
        color: #0f172a;
        border-color: #e2e8f0;
        box-shadow:
            0 25px 50px -12px rgba(2, 6, 23, 0.35),
            0 0 0 1px rgba(15, 23, 42, 0.06);
    }
    .notifications-delete-panel__head {
        border-bottom: 1px solid #e2e8f0;
    }
    .notifications-delete-panel__foot {
        border-top: 1px solid #e2e8f0;
    }
    .notifications-delete-panel__title {
        font-size: 1.125rem;
        font-weight: 600;
        line-height: 1.4;
        color: #0f172a;
    }
    .notifications-delete-panel__text {
        font-size: 0.875rem;
        line-height: 1.5;
        color: #64748b;
    }
    html.dark .notifications-delete-panel,
    html[data-kt-theme-mode="dark"] .notifications-delete-panel,
    .dark .notifications-delete-panel {
        background-color: #0b0f19;
        color: #f8fafc;
        border-color: rgba(148, 163, 184, 0.18);
        box-shadow:
            0 25px 50px -12px rgba(0, 0, 0, 0.65),
            0 0 0 1px rgba(148, 163, 184, 0.12);
    }
    html.dark .notifications-delete-panel__head,
    html[data-kt-theme-mode="dark"] .notifications-delete-panel__head,
    .dark .notifications-delete-panel__head {
        border-bottom-color: rgba(148, 163, 184, 0.18);
    }
    html.dark .notifications-delete-panel__foot,
    html[data-kt-theme-mode="dark"] .notifications-delete-panel__foot,
    .dark .notifications-delete-panel__foot {
        border-top-color: rgba(148, 163, 184, 0.18);
    }
    html.dark .notifications-delete-panel__title,
    html[data-kt-theme-mode="dark"] .notifications-delete-panel__title,
    .dark .notifications-delete-panel__title {
        color: #f8fafc;
    }
    html.dark .notifications-delete-panel__text,
    html[data-kt-theme-mode="dark"] .notifications-delete-panel__text,
    .dark .notifications-delete-panel__text {
        color: #94a3b8;
    }
    .notifications-delete-panel__cancel {
        background-color: #ffffff !important;
        color: #0f172a !important;
        border-color: #e2e8f0 !important;
    }
    .notifications-delete-panel__confirm {
        background-color: #dc2626 !important;
        color: #ffffff !important;
        border-color: #dc2626 !important;
    }
    html.dark .notifications-delete-panel__cancel,
    html[data-kt-theme-mode="dark"] .notifications-delete-panel__cancel,
    .dark .notifications-delete-panel__cancel {
        background-color: transparent !important;
        color: #f8fafc !important;
        border-color: rgba(148, 163, 184, 0.28) !important;
    }
    html.dark .notifications-delete-panel__confirm,
    html[data-kt-theme-mode="dark"] .notifications-delete-panel__confirm,
    .dark .notifications-delete-panel__confirm {
        background-color: #dc2626 !important;
        color: #ffffff !important;
        border-color: #dc2626 !important;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    let openedAt = 0;

    function getModal() {
        return document.getElementById('notifications-delete-modal');
    }

    function getForm() {
        return document.getElementById('notification-delete-form');
    }

    function openModal(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        const modal = getModal();
        if (!modal) {
            return;
        }
        modal.hidden = false;
        modal.removeAttribute('hidden');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        openedAt = Date.now();
    }

    function closeModal(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        if (openedAt && (Date.now() - openedAt) < 400) {
            return;
        }
        const modal = getModal();
        if (!modal) {
            return;
        }
        modal.hidden = true;
        modal.setAttribute('hidden', 'hidden');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function submitDelete(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        if (openedAt && (Date.now() - openedAt) < 400) {
            return;
        }
        const form = getForm();
        if (form) {
            form.submit();
        }
    }

    window.openNotificationDeleteModal = openModal;
    window.closeNotificationDeleteModal = closeModal;
    window.submitNotificationDeleteForm = submitDelete;

    document.addEventListener('click', function (event) {
        if (event.target.closest('#notification-delete-open')) {
            if (typeof window.showAdminConfirm === 'function') {
                event.preventDefault();
                event.stopPropagation();
                window.showAdminConfirm({
                    title: 'Notificatie verwijderen',
                    message: 'Weet je zeker dat je deze notificatie wilt verwijderen? Dit kan niet ongedaan worden gemaakt.',
                    confirmLabel: 'Verwijderen'
                }).then(function (ok) {
                    if (ok) {
                        submitDelete();
                    }
                });
                return;
            }
            openModal(event);
            return;
        }

        const modal = getModal();
        if (!modal || modal.hidden) {
            return;
        }

        if (event.target.closest('[data-notifications-delete-confirm]')) {
            submitDelete(event);
            return;
        }

        if (event.target.closest('[data-notifications-delete-dismiss]')) {
            closeModal(event);
        }
    }, true);

    document.addEventListener('keydown', function (event) {
        const modal = getModal();
        if (event.key === 'Escape' && modal && !modal.hidden) {
            closeModal(event);
        }
    });
})();
</script>
@endpush
@endif
