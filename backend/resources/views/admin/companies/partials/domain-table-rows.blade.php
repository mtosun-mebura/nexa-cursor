@foreach($company->domains->sortByDesc('is_primary')->values() as $d)
    <div class="flex flex-col gap-3 rounded-xl border border-input bg-muted/15 px-4 py-3 min-w-0" data-domain-row="{{ $d->id }}">
        <div class="flex flex-wrap items-center justify-between gap-3 min-w-0">
            <div class="flex flex-col gap-1 min-w-0 flex-1" data-domain-view>
                <span class="font-mono text-sm text-foreground break-all" data-domain-host-label>{{ $d->host }}</span>
                @if($d->is_primary)
                    <span class="kt-badge kt-badge-sm kt-badge-success w-fit">Primair</span>
                @endif
            </div>
            @can('edit-companies')
                <div class="inline-flex items-center justify-end gap-1 shrink-0" data-domain-actions>
                    <form
                        action="{{ route('admin.companies.domains.primary', [$company, $d]) }}"
                        method="post"
                        class="js-company-domain-action m-0 inline-flex items-center"
                        data-domain-primary-toggle="1"
                    >
                        @csrf
                        @if($d->is_primary)
                            <input type="hidden" name="clear" value="1">
                        @endif
                        <label class="kt-label mb-0 inline-flex items-center gap-2 cursor-pointer" title="{{ $d->is_primary ? 'Primair uitzetten (ander domein wordt primair)' : 'Instellen als primair tenantdomein' }}">
                            <span class="text-xs text-muted-foreground whitespace-nowrap">Primair</span>
                            <input
                                type="checkbox"
                                class="kt-switch kt-switch-sm shrink-0 js-domain-primary-switch"
                                {{ $d->is_primary ? 'checked' : '' }}
                                aria-label="{{ $d->is_primary ? 'Primair uitzetten' : 'Primair aanzetten' }}"
                            >
                        </label>
                    </form>
                    <button
                        type="button"
                        class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0 text-foreground hover:bg-muted js-domain-edit-toggle"
                        title="Domein wijzigen"
                        aria-label="Domein wijzigen"
                        aria-expanded="false"
                    >
                        <i class="ki-filled ki-pencil text-base"></i>
                    </button>
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
        @can('edit-companies')
            <form
                action="{{ route('admin.companies.domains.update', [$company, $d]) }}"
                method="post"
                class="js-company-domain-action hidden flex flex-col sm:flex-row sm:items-end gap-2 min-w-0"
                data-domain-edit-form
            >
                @csrf
                @method('PUT')
                <div class="flex flex-col gap-1.5 min-w-0 flex-1">
                    <label class="text-xs font-medium text-muted-foreground" for="domain-host-edit-{{ $d->id }}">Hostnaam</label>
                    <input
                        type="text"
                        name="host"
                        id="domain-host-edit-{{ $d->id }}"
                        value="{{ $d->host }}"
                        class="kt-input w-full font-mono text-sm"
                        autocomplete="off"
                        required
                    >
                    <div class="text-xs text-destructive hidden" data-domain-edit-error role="alert"></div>
                </div>
                <div class="inline-flex items-center gap-2 shrink-0">
                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-primary">Opslaan</button>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-outline js-domain-edit-cancel">Annuleren</button>
                </div>
            </form>
        @endcan
    </div>
@endforeach
