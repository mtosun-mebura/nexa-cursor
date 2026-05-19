{{--
    Standaard kenmerken-kaart (zelfde hover-lift als NexaTaxi-tarievenkaarten).
    @param array $item title, description, icon, icon_size, icon_align
    @param int $index Voor afwisselende icoonkleuren (even = blauw, oneven = groen)
--}}
@props([
    'item' => [],
    'index' => 0,
    'class' => '',
])

@php
    $fi = (int) $index;
    $iconName = $item['icon'] ?? ($fi === 0 ? 'light-bulb' : 'bolt');
    $iconDef = config('heroicons.icons.'.$iconName);
    if (! is_array($iconDef) || empty($iconDef['svg'])) {
        $iconDef = config('heroicons.icons.light-bulb') ?? ['svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />'];
    }
    $iconSize = $item['icon_size'] ?? 'medium';
    $sizeDef = config('heroicons.sizes.'.$iconSize);
    $iconSizeClass = is_array($sizeDef) && ! empty($sizeDef['class']) ? $sizeDef['class'] : 'w-10 h-10';
    $iconAlign = $item['icon_align'] ?? 'center';
    $iconAlignItems = $iconAlign === 'right' ? 'items-end' : ($iconAlign === 'left' ? 'items-start' : 'items-center');
    $iconAlignText = $iconAlign === 'right' ? 'text-right' : ($iconAlign === 'left' ? 'text-left' : 'text-center');
    $isBlue = $fi % 2 === 0;
    $cardBaseClass = 'features-card rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-6 h-full shadow-lg transition-transform duration-200 hover:-translate-y-2';
@endphp

<div {{ $attributes->merge(['class' => trim($cardBaseClass.' '.$class)]) }}>
    <div class="flex flex-col w-full {{ $iconAlignItems }} {{ $iconAlignText }}">
        <div class="features-card-icon w-12 h-12 {{ $isBlue ? 'bg-blue-100 dark:bg-blue-500/20' : 'bg-green-100 dark:bg-green-500/20' }} rounded-lg flex items-center justify-center shrink-0">
            <svg class="{{ $iconSizeClass }} {{ $isBlue ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">{!! $iconDef['svg'] ?? '' !!}</svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mt-4 mb-3">{{ $item['title'] ?? '' }}</h3>
        <div class="text-gray-600 dark:text-gray-300 prose prose-sm dark:prose-invert prose-p:my-1 prose-ul:my-1 prose-ol:my-1 max-w-none">{!! $item['description'] ?? '' !!}</div>
    </div>
</div>

@once
@push('styles')
<style>
    @keyframes features-card-icon-bounce-left {
        0% { transform: translateX(0); }
        50% { transform: translateX(-8px); }
        100% { transform: translateX(-3px); }
    }
    .features-card:hover .features-card-icon {
        animation: features-card-icon-bounce-left 0.4s ease-out forwards;
    }
</style>
@endpush
@endonce
