@props([
    'title' => '',
    'tag' => 'h5',
    'class' => 'text-sm font-medium text-foreground m-0',
    'info' => null,
    'infoId' => null,
])

<div class="flex items-center justify-between gap-2 min-w-0">
    <{{ $tag }} @class([$class, 'min-w-0'])>{!! $title !!}</{{ $tag }}>
    @if ($info)
        @include('admin.settings.partials.info-hover-icon', [
            'id' => $infoId ?? ('admin-info-' . md5(strip_tags((string) $info))),
            'content' => $info,
        ])
    @endif
</div>
