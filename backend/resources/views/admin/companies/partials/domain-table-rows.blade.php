@foreach($company->domains->sortByDesc('is_primary') as $d)
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-input bg-muted/15 px-4 py-3 min-w-0">
        <div class="flex flex-col gap-1 min-w-0 flex-1">
            <span class="font-mono text-sm text-foreground break-all">{{ $d->host }}</span>
            @if($d->is_primary)
                <span class="kt-badge kt-badge-sm kt-badge-success w-fit">Primair</span>
            @endif
        </div>
        @can('edit-companies')
            <div class="inline-flex items-center justify-end gap-1 shrink-0">
                @if(!$d->is_primary)
                    <form action="{{ route('admin.companies.domains.primary', [$company, $d]) }}" method="post" class="js-company-domain-action m-0 inline">
                        @csrf
                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-500/10 dark:text-emerald-500 dark:hover:text-emerald-400" title="Instellen als primair tenantdomein" aria-label="Instellen als primair tenantdomein">
                            <i class="ki-filled ki-star text-base"></i>
                        </button>
                    </form>
                @endif
                <form action="{{ route('admin.companies.domains.destroy', [$company, $d]) }}" method="post" class="js-company-domain-action m-0 inline" data-domain-destroy="1">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0 text-red-600 hover:text-red-700 hover:bg-red-500/10 dark:text-red-500 dark:hover:text-red-400 dark:hover:bg-red-500/10" title="Domein verwijderen" aria-label="Domein verwijderen">
                        <i class="ki-filled ki-trash text-base"></i>
                    </button>
                </form>
            </div>
        @endcan
    </div>
@endforeach
