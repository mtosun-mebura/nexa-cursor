@if($rides->isEmpty())
    <p class="text-sm text-muted-foreground px-5 py-6 mb-0">
        Nog geen voorstellen. Stuur een ophaalvoorstel vanuit de chauffeur-app, of kies een tenant en klik op <strong>Testdata aanmaken</strong>.
    </p>
@else
    <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
        <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full" id="whatsapp-mock-table">
            <thead>
                <tr>
                    <th class="text-center whatsapp-mock-table__check-col" data-label="">
                        <label class="kt-label mb-0 inline-flex items-center justify-center cursor-pointer">
                            <input type="checkbox" class="kt-checkbox" id="whatsapp-mock-select-all" aria-label="Alles selecteren">
                        </label>
                    </th>
                    <th data-label="Rit">Rit</th>
                    <th data-label="Bron">Bron</th>
                    <th data-label="Klant / nummer">Klant / nummer</th>
                    <th data-label="Voorstelstatus">Voorstelstatus</th>
                    <th data-label="wamid">wamid</th>
                    <th data-label="Opmerking">Opmerking</th>
                    <th class="text-center whatsapp-mock-table__actions-col" data-label="Acties">Acties</th>
                </tr>
            </thead>
            <tbody>
            @foreach($rides as $ride)
                @php
                    $proposalStatus = $ride->pickup_proposal_status;
                    $proposalLabel = $proposalLabels[$proposalStatus] ?? ($proposalStatus ?: '—');
                    $pending = $ride->hasPendingPickupProposal();
                    $declined = $ride->hasDeclinedPickupProposal();
                    $isSeeded = str_contains((string) $ride->customer_note, \App\Modules\NexaTaxi\Services\WhatsAppPickupProposalMockService::NOTE_MARKER);
                @endphp
                <tr data-ride-id="{{ $ride->id }}">
                    <td class="text-center whatsapp-mock-table__check-col" data-no-row-link>
                        <label class="kt-label mb-0 inline-flex items-center justify-center cursor-pointer">
                            <input type="checkbox" class="kt-checkbox whatsapp-mock-row-check" value="{{ $ride->id }}" aria-label="Selecteer rit #{{ $ride->id }}">
                        </label>
                    </td>
                    <td class="whitespace-nowrap">
                        <div class="font-medium text-foreground">#{{ $ride->id }}</div>
                        <div class="text-xs text-muted-foreground">{{ $rideStatusLabels[$ride->status] ?? $ride->status }}</div>
                    </td>
                    <td class="whitespace-nowrap">
                        <span class="text-xs text-muted-foreground">{{ $isSeeded ? 'Testdata' : 'Chauffeur-app' }}</span>
                    </td>
                    <td>
                        <div>{{ $ride->customer_name }}</div>
                        <div class="text-xs text-muted-foreground">{{ $ride->customer_phone }}</div>
                    </td>
                    <td>
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                            @if($pending) bg-amber-500/15 text-amber-800 dark:text-amber-300
                            @elseif($proposalStatus === 'accepted') bg-emerald-500/15 text-emerald-800 dark:text-emerald-300
                            @elseif($declined) bg-destructive/10 text-destructive
                            @else bg-muted text-muted-foreground
                            @endif">
                            {{ $proposalLabel }}
                        </span>
                    </td>
                    <td class="max-w-[10rem]">
                        <code class="text-xs break-all line-clamp-2" title="{{ $ride->pickup_proposal_whatsapp_wamid ?: '' }}">{{ $ride->pickup_proposal_whatsapp_wamid ?: '—' }}</code>
                    </td>
                    <td class="max-w-[8rem] text-xs text-muted-foreground truncate" title="{{ $ride->pickup_proposal_customer_remark ?: '' }}">
                        {{ $ride->pickup_proposal_customer_remark ?: '—' }}
                    </td>
                    <td class="whatsapp-mock-table__actions-col whitespace-nowrap" data-no-row-link>
                        @php
                            $messagePreview = app(\App\Modules\NexaTaxi\Services\WhatsAppPickupProposalMockService::class)->messagePreview($ride);
                        @endphp
                        <div class="flex items-center justify-center gap-0.5">
                            <button type="button"
                                    class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost whatsapp-mock-view-message"
                                    data-tooltip="Bericht bekijken"
                                    aria-label="Bericht bekijken"
                                    data-ride-id="{{ $ride->id }}"
                                    data-message="{{ $messagePreview }}">
                                <i class="ki-filled ki-eye text-muted-foreground"></i>
                            </button>
                            @if($mockAllowed && ($pending || $declined))
                                @if($pending)
                                    <form method="POST" action="{{ route('admin.whatsapp-pickup-proposal-mock.simulate') }}">
                                        @csrf
                                        <input type="hidden" name="ride_id" value="{{ $ride->id }}">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-tooltip="Accepteren" aria-label="Accepteren">
                                            <i class="ki-filled ki-check text-emerald-600 dark:text-emerald-400"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.whatsapp-pickup-proposal-mock.simulate') }}">
                                        @csrf
                                        <input type="hidden" name="ride_id" value="{{ $ride->id }}">
                                        <input type="hidden" name="action" value="decline">
                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-tooltip="Weigeren" aria-label="Weigeren">
                                            <i class="ki-filled ki-cross text-destructive"></i>
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.whatsapp-pickup-proposal-mock.simulate') }}">
                                    @csrf
                                    <input type="hidden" name="ride_id" value="{{ $ride->id }}">
                                    <input type="hidden" name="action" value="remark">
                                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-tooltip="Opmerking" aria-label="Opmerking">
                                        <i class="ki-filled ki-message-text text-muted-foreground"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
