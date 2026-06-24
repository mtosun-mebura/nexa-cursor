@if(!empty($report) && is_array($report))
<div class="tenant-sync-report rounded-md border border-border bg-muted/20 p-4 text-xs leading-snug text-left w-full max-w-4xl">
    <p class="font-medium text-foreground mb-3">{{ $report['summary'] ?? 'Tenant-sync voltooid.' }}</p>

    @if(!empty($report['errors']))
        <div class="mb-4 space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-destructive">Fouten</p>
            <ul class="space-y-1.5">
                @foreach($report['errors'] as $error)
                    <li class="text-destructive">
                        <span class="font-medium">{{ $error['section'] ?? 'Algemeen' }}:</span>
                        {{ $error['message'] ?? '' }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @foreach(($report['sections'] ?? []) as $section)
        @php
            $rows = collect($section['rows'] ?? [])->filter(function ($row) {
                $inserted = (int) ($row['inserted'] ?? 0);
                $updated = (int) ($row['updated'] ?? 0);
                $skipped = (int) ($row['skipped'] ?? 0);
                $status = (string) ($row['status'] ?? 'ok');

                return ($inserted + $updated + $skipped) > 0
                    || in_array($status, ['error', 'warning', 'skipped'], true)
                    || ! empty($row['error']);
            });
        @endphp
        @if($rows->isNotEmpty())
            <div class="mb-4 last:mb-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">{{ $section['title'] ?? 'Overig' }}</p>
                <ul class="space-y-1.5">
                    @foreach($rows as $row)
                        <li class="flex flex-col gap-0.5 sm:flex-row sm:flex-wrap sm:items-baseline sm:gap-x-3">
                            <span class="font-mono text-foreground min-w-[12rem]">{{ $row['label'] ?? '—' }}</span>
                            <span class="text-muted-foreground">
                                +{{ (int) ($row['inserted'] ?? 0) }} toegevoegd,
                                ~{{ (int) ($row['updated'] ?? 0) }} bijgewerkt,
                                -{{ (int) ($row['skipped'] ?? 0) }} overgeslagen
                            </span>
                            @if(!empty($row['error']))
                                <span class="text-destructive sm:basis-full">{{ $row['error'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endforeach

    @if(!empty($report['notes']))
        <div class="mt-4 pt-3 border-t border-border/70 space-y-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Opmerkingen</p>
            @foreach($report['notes'] as $note)
                <p class="text-muted-foreground mb-0">{{ $note }}</p>
            @endforeach
        </div>
    @endif
</div>
@endif
