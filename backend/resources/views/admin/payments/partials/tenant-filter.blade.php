@if(!empty($filterCompany))
    <div class="mb-5 flex flex-wrap items-center gap-2">
        <span class="text-sm text-secondary-foreground">Filter:</span>
        <span class="kt-badge kt-badge-outline kt-badge-primary">{{ $filterCompany->name }}</span>
        <a href="{{ request()->url() }}" class="text-sm text-primary hover:underline">Filter wissen</a>
    </div>
@endif

@if(empty($tenantId) && !empty($tenantRows) && count($tenantRows) > 0)
    <div class="mb-5 flex flex-wrap gap-2">
        @foreach($tenantRows as $row)
            <a href="{{ route($filterRoute ?? 'admin.payments.openstaand', array_merge(request()->except('page'), ['company_id' => $row['company']->id])) }}"
               class="kt-btn kt-btn-sm {{ (int) request('company_id') === (int) $row['company']->id ? 'kt-btn-primary' : 'kt-btn-outline' }}">
                {{ $row['company']->name }}
                <span class="ms-1 opacity-80">({{ $row['open_count'] }}/{{ $row['paid_count'] }})</span>
            </a>
        @endforeach
    </div>
@endif
