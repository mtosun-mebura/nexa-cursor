<div class="flex flex-col">
    @if(isset($activities) && count($activities) > 0)
        @foreach($activities as $index => $activity)
            <div class="flex items-start relative">
                @if($index < count($activities) - 1)
                    <div class="w-9 start-0 top-9 absolute bottom-0 rtl:-translate-x-1/2 translate-x-1/2 border-s border-s-input"></div>
                @endif
                <div class="flex items-center justify-center shrink-0 rounded-full bg-accent/60 border border-input size-9 text-secondary-foreground">
                    <i class="{{ $activity['icon'] }} text-base"></i>
                </div>
                <div class="ps-2.5 mb-7 text-base grow">
                    <div class="flex flex-col">
                        <div class="text-sm text-foreground">
                            {{ $activity['title'] }}
                            @if($activity['description'])
                                <span class="text-secondary-foreground"> - {{ $activity['description'] }}</span>
                            @endif
                        </div>
                        <span class="text-xs text-secondary-foreground">
                            {{ $activity['date']->diffForHumans() }} ({{ $activity['date']->format('d-m-Y H:i') }})
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="text-sm text-muted-foreground py-4">
            Geen activiteiten gevonden
        </div>
    @endif
</div>

