@extends('admin.layouts.app')

@section('title', 'Uitlegoverzicht')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-col items-start gap-3 pb-7.5">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-xl font-medium leading-none text-mono mb-0">Uitlegoverzicht</h1>
            <span class="kt-badge kt-badge-light">Alleen super-admin</span>
        </div>
        <p class="text-sm text-muted-foreground mb-0 max-w-3xl">
            Alle plekken in Nexa waar een werkwijze of uitleg staat. Elke kaart is een bestaande pagina.
        </p>
    </div>

    <div class="grid gap-5 lg:grid-cols-2 lg:gap-7.5">
        @foreach($groups as $group)
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header flex flex-col items-start gap-1 px-5 py-5">
                    <h2 class="kt-card-title mb-0">{{ $group['title'] }}</h2>
                    @if(!empty($group['intro']))
                        <p class="text-sm text-muted-foreground mb-0">{{ $group['intro'] }}</p>
                    @endif
                </div>
                <div class="kt-card-content p-5">
                    <ul class="mb-0 space-y-4">
                        @foreach($group['items'] as $item)
                            <li class="min-w-0">
                                <a href="{{ $item['url'] }}" class="text-sm font-medium text-primary hover:underline">
                                    {{ $item['title'] }}
                                </a>
                                <p class="text-sm text-muted-foreground mb-0 mt-0.5">{{ $item['summary'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
