@props([
    'for' => null,
    'label' => '',
    'class' => 'text-sm text-secondary-foreground',
    'info' => null,
    'infoId' => null,
])

<div class="flex items-center justify-between gap-2 mb-1 min-w-0">
    <label @if ($for) for="{{ $for }}" @endif @class([$class, 'mb-0 min-w-0'])>{!! $label !!}</label>
    @if ($info)
        @include('admin.settings.partials.info-hover-icon', [
            'id' => $infoId ?? ('admin-info-' . md5(strip_tags((string) $info))),
            'content' => $info,
        ])
    @endif
</div>
