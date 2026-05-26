@php
    $steps = [
        1 => ['label' => 'Bedrijf & logo', 'icon' => 'ki-notepad'],
        2 => ['label' => 'Vestigingen', 'icon' => 'ki-geolocation'],
        3 => ['label' => 'Domein', 'icon' => 'ki-cloud'],
        4 => ['label' => 'Modules', 'icon' => 'ki-element-11'],
        5 => ['label' => 'Gebruikers', 'icon' => 'ki-users'],
        6 => ['label' => 'Website', 'icon' => 'ki-screen'],
        7 => ['label' => 'Afronden', 'icon' => 'ki-verify'],
    ];
@endphp

<div class="border-b border-input mb-6">
    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-secondary-foreground">
        @foreach($steps as $num => $meta)
            @php
                $isActive = (int) $currentStep === $num;
                $canClick = $company && $num <= (int) $maxReachable;
                $isFuture = $company && $num > (int) $maxReachable;
                if (!$company) {
                    $canClick = $num === 1;
                    $isFuture = $num > 1;
                }
            @endphp
            <li class="me-2">
                @if($isFuture)
                    <span class="inline-flex items-center justify-center p-4 text-muted-foreground rounded-t-lg cursor-not-allowed select-none opacity-60" title="Voltooi eerst de vorige stappen">
                        <i class="ki-filled {{ $meta['icon'] }} w-4 h-4 me-2"></i>
                        {{ $meta['label'] }}
                    </span>
                @elseif($canClick && !$isActive)
                    <a href="{{ $company ? route('admin.companies.wizard.step', [$company, $num]) : route('admin.companies.wizard.start') }}"
                       class="inline-flex items-center justify-center p-4 border-b border-transparent rounded-t-lg hover:text-primary hover:border-primary group">
                        <i class="ki-filled {{ $meta['icon'] }} w-4 h-4 me-2 text-secondary-foreground group-hover:text-primary"></i>
                        {{ $meta['label'] }}
                    </a>
                @else
                    <span class="inline-flex items-center justify-center p-4 text-primary border-b border-primary rounded-t-lg group" @if($isActive) aria-current="page" @endif>
                        <i class="ki-filled {{ $meta['icon'] }} w-4 h-4 me-2 text-primary"></i>
                        {{ $meta['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
