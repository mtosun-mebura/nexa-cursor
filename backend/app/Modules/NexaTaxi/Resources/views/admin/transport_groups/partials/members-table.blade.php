<div class="kt-scrollable-x-auto admin-table-scroll-wrap">
    <table id="transport-group-members-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
        <thead>
            <tr>
                <th data-label="Naam">Naam</th>
                <th data-label="Ophaaladres">Ophaaladres</th>
                <th data-label="Sinds">Sinds</th>
                @can('rides.update')
                <th class="transport-group-members-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties"></th>
                @endcan
            </tr>
        </thead>
        <tbody>
            @forelse($activeMembers as $member)
            <tr>
                <td class="font-medium">{{ $member->passenger?->full_name ?? '—' }}</td>
                <td class="text-muted-foreground">{{ Str::limit($member->passenger?->pickup_address ?? '—', 50) }}</td>
                <td class="text-muted-foreground">
                    {{ $member->valid_from ? $member->valid_from->format('d-m-Y') : '—' }}
                </td>
                @can('rides.update')
                <td class="transport-group-members-table__actions-col">
                    <form method="POST" action="{{ route('admin.taxi.transport_groups.member_remove', [$customer->id, $contract->id, $group->id, $member->id]) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-destructive" title="Uit groep halen" aria-label="Uit groep halen">
                            <i class="ki-filled ki-trash"></i>
                        </button>
                    </form>
                </td>
                @endcan
            </tr>
            @empty
            <tr>
                <td colspan="{{ auth()->user()->can('rides.update') ? 4 : 3 }}" class="text-center text-muted-foreground py-8">
                    Nog geen leden in deze groep.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
