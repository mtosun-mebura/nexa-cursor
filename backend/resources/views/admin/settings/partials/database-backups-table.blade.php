<div id="database-backups-table" class="kt-scrollable-x-auto admin-table-scroll-wrap min-w-0">
    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full database-backups-table">
        <colgroup>
            <col class="database-backups-col-select">
            <col class="database-backups-col-file">
            <col class="database-backups-col-db">
            <col class="database-backups-col-size">
            <col class="database-backups-col-status">
            <col class="database-backups-col-date">
            <col class="admin-table__actions-col">
        </colgroup>
        <thead>
            <tr>
                <th class="database-backups-col-select text-center" data-no-row-link data-label="">
                    <label class="kt-label mb-0 inline-flex items-center justify-center cursor-pointer">
                        <input type="checkbox"
                               class="kt-checkbox"
                               id="database-backups-select-all"
                               title="Alle backups selecteren"
                               aria-label="Alle backups selecteren"
                               @disabled(count($databaseBackups ?? []) === 0)>
                    </label>
                </th>
                <th data-label="Bestand">Bestand</th>
                <th data-label="Database">Database</th>
                <th data-label="Grootte">Grootte</th>
                <th data-label="Status">Status</th>
                <th data-label="Datum">Datum</th>
                <th class="text-center admin-table__actions-col" data-label="Acties">Acties</th>
            </tr>
        </thead>
        <tbody>
            @forelse($databaseBackups ?? [] as $backup)
                @php
                    $isPending = $backup->status === \App\Models\DatabaseBackup::STATUS_PENDING;
                @endphp
                <tr data-id="{{ $backup->id }}" data-filename="{{ $backup->filename }}" data-status="{{ $backup->status }}" data-size="{{ (int) $backup->size_bytes }}">
                    <td class="database-backups-col-select text-center" data-no-row-link data-label="">
                        <label class="kt-label mb-0 inline-flex items-center justify-center {{ $isPending ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
                            <input type="checkbox"
                                   class="kt-checkbox database-backup-checkbox"
                                   name="backup_ids[]"
                                   value="{{ $backup->id }}"
                                   aria-label="Selecteer {{ $backup->filename }}"
                                   @disabled($isPending)
                                   @if($isPending) title="Backup is nog bezig" @endif>
                        </label>
                    </td>
                    <td class="database-backups-col-file font-mono text-xs min-w-0" data-label="Bestand" title="{{ $backup->filename }}">{{ $backup->filename }}</td>
                    <td class="database-backups-col-db min-w-0" data-label="Database" title="{{ $backup->database_name }}">{{ $backup->database_name }}</td>
                    <td class="database-backups-col-size tabular-nums" data-label="Grootte">
                        <div>{{ $backup->humanSize() }}</div>
                        <div class="text-xs text-muted-foreground">({{ $backup->humanSizeMegabytes() }})</div>
                    </td>
                    <td class="database-backups-col-status" data-label="Status">
                        <div class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                            @if($backup->status === \App\Models\DatabaseBackup::STATUS_COMPLETED)
                                <span class="kt-badge kt-badge-sm kt-badge-success">Gereed</span>
                            @elseif($backup->status === \App\Models\DatabaseBackup::STATUS_FAILED)
                                <span class="kt-badge kt-badge-sm kt-badge-danger" title="{{ $backup->error_message }}">Mislukt</span>
                            @elseif($isPending)
                                <span class="kt-badge kt-badge-sm kt-badge-warning">Bezig</span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-secondary">{{ $backup->status }}</span>
                            @endif
                            <span class="text-xs text-muted-foreground">{{ $backup->trigger === 'scheduled' ? 'gepland' : 'handmatig' }}</span>
                        </div>
                    </td>
                    <td class="database-backups-col-date whitespace-nowrap tabular-nums" data-label="Datum">{{ $backup->localCreatedAt()?->format('d-m-Y H:i') }}</td>
                    <td class="text-center admin-table__actions-col" data-no-row-link data-label="Acties">
                        <div class="kt-menu flex justify-center" data-kt-menu="true">
                            <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                <button type="button" class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Acties">
                                    <i class="ki-filled ki-dots-vertical text-lg"></i>
                                </button>
                                <div class="kt-menu-dropdown kt-menu-default w-full max-w-[200px]" data-kt-menu-dismiss="true">
                                    @if($backup->status === \App\Models\DatabaseBackup::STATUS_COMPLETED && $backup->fileExists())
                                        <div class="kt-menu-item">
                                            <a class="kt-menu-link" href="{{ route('admin.settings.database-backups.download', $backup) }}">
                                                <span class="kt-menu-icon"><i class="ki-filled ki-file-down"></i></span>
                                                <span class="kt-menu-title">Downloaden</span>
                                            </a>
                                        </div>
                                        <div class="kt-menu-item">
                                            <form method="POST" action="{{ route('admin.settings.database-backups.restore', $backup) }}"
                                                  onsubmit="return confirm('WAARSCHUWING: dit overschrijft de huidige database met deze backup. Weet je het zeker?');">
                                                @csrf
                                                <input type="hidden" name="confirm_restore" value="1">
                                                <button type="submit" class="kt-menu-link w-full text-left text-amber-600 dark:text-amber-400">
                                                    <span class="kt-menu-icon"><i class="ki-filled ki-arrows-circle"></i></span>
                                                    <span class="kt-menu-title">Herstellen</span>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                    @unless($isPending)
                                        <div class="kt-menu-item">
                                            <form method="POST" action="{{ route('admin.settings.database-backups.destroy', $backup) }}"
                                                  onsubmit="return confirm('Deze backup permanent verwijderen?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="kt-menu-link w-full text-left text-destructive">
                                                    <span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span>
                                                    <span class="kt-menu-title">Verwijderen</span>
                                                </button>
                                            </form>
                                        </div>
                                    @endunless
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="database-backups-empty-row">
                    <td colspan="7" class="text-center text-muted-foreground py-8">Nog geen backups. Sla de instellingen op en maak een backup, of wacht op de planner.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
