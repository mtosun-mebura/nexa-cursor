@extends('admin.layouts.app')

@section('title', 'Email communicatie')

@section('content')
@php
    $typeLabels = $typeLabels ?? \App\Models\TenantCustomerEmail::TYPE_LABELS;
    $statusLabels = $statusLabels ?? \App\Models\TenantCustomerEmail::STATUS_LABELS;
@endphp
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Email communicatie</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Alle e-mails naar klanten, van welkomstmail tot ritcommunicatie.
            </div>
        </div>
    </div>

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
                <span data-admin-datatable-info="true">Toon 1 tot {{ $emails->count() }} van {{ $emails->count() }} e-mails</span>
            </h3>
            <div class="admin-filter-panel flex flex-col sm:flex-row flex-wrap gap-2.5 w-full sm:w-auto min-w-0 items-stretch sm:items-center"
                 data-admin-live-filter="off">
                <label class="kt-input w-full sm:w-64 min-w-0">
                    <i class="ki-filled ki-magnifier"></i>
                    <input placeholder="Zoek op e-mail, naam, onderwerp…"
                           type="text"
                           name="search"
                           autocomplete="off"
                           data-admin-datatable-search="#customer_emails_table">
                </label>
                <select class="kt-select w-full sm:w-48"
                        name="type"
                        data-admin-datatable-filter="type"
                        data-kt-select="true">
                    <option value="">Alle types</option>
                    @foreach($typeLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select class="kt-select w-full sm:w-44"
                        name="status"
                        data-admin-datatable-filter="status"
                        data-kt-select="true">
                    <option value="">Alle statussen</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @if(!empty($showCompany))
                <select class="kt-select w-full sm:w-52"
                        name="company"
                        data-admin-datatable-filter="company"
                        data-kt-select="true">
                    <option value="">Alle bedrijven</option>
                    @foreach($emails->pluck('company')->filter()->unique('id')->sortBy('name') as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
                @endif
                <button type="button"
                        data-admin-datatable-reset
                        class="kt-btn kt-btn-outline kt-btn-icon shrink-0 hidden"
                        title="Filters resetten">
                    <i class="ki-filled ki-arrows-circle text-base"></i>
                </button>
            </div>
        </div>

        <div class="kt-card-content p-0 min-w-0">
            @if($emails->count() > 0)
            <div class="grid w-full min-w-0"
                 data-admin-datatable="true"
                 data-admin-datatable-page-size="25"
                 id="customer_emails_table"
                 data-admin-datatable-label="e-mails">
                <div class="customer-emails-table-wrap min-w-0">
                    <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                        <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full" id="customer-emails-table">
                            <thead>
                                <tr>
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Datum">Datum</th>
                                    @if(!empty($showCompany))
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Bedrijf">Bedrijf</th>
                                    @endif
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Type">Type</th>
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Klant">Klant</th>
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Onderwerp">Onderwerp</th>
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Status">Status</th>
                                    <th class="customer-emails-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emails as $row)
                                @php
                                    $typeLabel = $row->typeLabel();
                                    $statusLabel = $row->statusLabel();
                                    $companyName = $row->company->name ?? '';
                                    $sentAt = $row->sent_at ?? $row->created_at;
                                    $searchText = mb_strtolower(implode(' ', array_filter([
                                        $row->recipient_email,
                                        $row->recipient_name,
                                        $row->subject,
                                        $typeLabel,
                                        $row->type,
                                        $statusLabel,
                                        $row->status,
                                        $companyName,
                                        $sentAt?->format('d-m-Y H:i'),
                                    ])), 'UTF-8');
                                @endphp
                                <tr data-row-href="{{ route('admin.customer-emails.show', $row) }}"
                                    data-type="{{ $row->type }}"
                                    data-status="{{ $row->status }}"
                                    data-company="{{ $row->company_id }}"
                                    data-search-text="{{ $searchText }}"
                                    class="cursor-pointer hover:bg-muted/40">
                                    <td class="text-secondary-foreground whitespace-nowrap">
                                        {{ $sentAt?->timezone(config('app.timezone', 'Europe/Amsterdam'))->format('d-m-Y H:i') ?? '—' }}
                                    </td>
                                    @if(!empty($showCompany))
                                    <td class="text-secondary-foreground">{{ $companyName !== '' ? $companyName : '—' }}</td>
                                    @endif
                                    <td>{{ $typeLabel }}</td>
                                    <td>
                                        <span class="font-medium text-foreground">{{ $row->recipient_name ?: '—' }}</span>
                                        <div class="text-secondary-foreground text-xs">{{ $row->recipient_email }}</div>
                                    </td>
                                    <td class="text-secondary-foreground">
                                        {{ $row->subject }}
                                        @if($row->isResend())
                                            <span class="kt-badge kt-badge-outline kt-badge-sm ms-1">Opnieuw verstuurd</span>
                                        @elseif((int) $row->resent_count > 0)
                                            <span class="kt-badge kt-badge-outline kt-badge-sm ms-1">{{ $row->resent_count }}× opnieuw</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row->status === 'sent')
                                            <span class="kt-badge kt-badge-success kt-badge-outline rounded-[30px]">Verzonden</span>
                                        @elseif($row->status === 'failed')
                                            <span class="kt-badge kt-badge-destructive kt-badge-outline rounded-[30px]">Mislukt</span>
                                        @else
                                            <span class="kt-badge kt-badge-secondary kt-badge-outline rounded-[30px]">{{ $statusLabel }}</span>
                                        @endif
                                    </td>
                                    <td class="customer-emails-table__actions-col" data-no-row-link onclick="event.stopPropagation();">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="{{ route('admin.customer-emails.show', $row) }}"
                                               class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon"
                                               title="Bekijken">
                                                <i class="ki-filled ki-eye"></i>
                                            </a>
                                            <form method="POST"
                                                  action="{{ route('admin.customer-emails.resend', $row) }}"
                                                  onsubmit="return confirm('E-mail opnieuw versturen naar {{ addslashes($row->recipient_email) }}?');">
                                                @csrf
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline whitespace-nowrap">
                                                    Opnieuw versturen
                                                </button>
                                            </form>
                                        </div>
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
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
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
            <div class="py-10 px-3 sm:px-5 text-center text-secondary-foreground text-sm">
                Nog geen e-mails naar klanten vastgelegd.
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #content #customer-emails-table .customer-emails-table__actions-col {
        width: 12rem !important;
        min-width: 12rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }
</style>
@endpush
