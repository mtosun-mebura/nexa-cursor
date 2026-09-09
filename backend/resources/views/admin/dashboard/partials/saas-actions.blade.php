<div class="grid gap-5 grid-cols-1">
    <div class="kt-card w-full min-w-0">
        <div class="kt-card-header admin-dashboard-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
            <h3 class="kt-card-title mb-0">
                <i class="ki-filled ki-notification-bing me-2"></i>
                Acties
            </h3>
            <a href="{{ route('admin.platform-billing.invoices.index') }}" class="kt-btn kt-btn-sm kt-btn-outline shrink-0 admin-card-header-action">
                NEXA-facturen
            </a>
        </div>
        <div class="kt-card-content p-5">
            @if(empty($saasActionItems))
                <p class="text-sm text-muted-foreground mb-0">
                    Geen openstaande acties. Alle NEXA-facturen van deze maand zijn bij, en er zijn geen blokkades of aflopende proefperiodes.
                </p>
            @else
                <ul class="m-0 flex flex-col gap-3 p-0 list-none">
                    @foreach($saasActionItems as $item)
                        @php
                            $severity = $item['severity'] ?? 'info';
                            $tone = match ($severity) {
                                'danger' => 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-100',
                                'warning' => 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100',
                                default => 'border-sky-200 bg-sky-50 text-sky-950 dark:border-sky-900/40 dark:bg-sky-950/40 dark:text-sky-100',
                            };
                        @endphp
                        <li class="rounded-lg border px-4 py-3 {{ $tone }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold">{{ $item['company_name'] ?? 'Tenant' }}</div>
                                    <div class="text-sm">{{ $item['title'] ?? '' }}</div>
                                    <div class="text-xs opacity-80 mt-1">{{ $item['detail'] ?? '' }}</div>
                                </div>
                                @if(!empty($item['url']))
                                    <a href="{{ $item['url'] }}" class="kt-btn kt-btn-sm kt-btn-outline shrink-0">
                                        Openen
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
