@php
    $variant = $adminTenantScopeVariant ?? 'default';
@endphp

<div class="tenant-scope-notice mb-5 flex gap-3 rounded-xl px-4 py-4 text-sm shadow-md" role="status">
    <i class="ki-filled ki-information mt-0.5 shrink-0 text-2xl text-white/90"></i>
    <div class="min-w-0 flex-1 leading-relaxed text-white">
        @if($variant === 'settings')
            <div class="space-y-4">
                <div>
                    <p class="mb-0 text-base font-semibold text-white">{{ __('Tenant kiezen') }}</p>
                    <p class="mt-1.5 mb-0 text-white">{!! __('Kies links in de zijbalk een <strong class="font-semibold text-white">tenant (bedrijf)</strong> voordat u tenant-specifieke instellingen opslaat.') !!}</p>
                </div>
                <div>
                    <p class="mb-0 text-base font-semibold text-white">{{ __('Per bedrijf opgeslagen') }}</p>
                    <p class="mt-1.5 mb-0 text-white">{{ __('Mail, SEO, Maps, WhatsApp, Google Reviews en overige tenant-instellingen worden per bedrijf opgeslagen.') }}</p>
                </div>
                <div>
                    <p class="mb-0 text-base font-semibold text-white">{{ __('Platform-breed') }}</p>
                    <p class="mt-1.5 mb-0 text-white">{!! __('<strong class="font-semibold text-white">Upgrade</strong> en <strong class="font-semibold text-white">Omgeving-sync</strong> onder <span class="whitespace-nowrap">Configuraties</span> gelden voor het hele platform (niet per tenant).') !!}</p>
                </div>
            </div>
        @elseif($variant === 'website-pages')
            <p class="mb-0 text-base font-semibold text-white">{{ __('Tenant kiezen') }}</p>
            <p class="mt-1.5 mb-0 text-white">{!! __('Kies links in de zijbalk een <strong class="font-semibold text-white">tenant (bedrijf)</strong> voordat u website-pagina\'s kunt bekijken en beheren.') !!}</p>
        @elseif($variant === 'module-config')
            <p class="mb-0 text-base font-semibold text-white">{{ __('Tenant kiezen') }}</p>
            <p class="mt-1.5 mb-0 text-white">{!! __('Kies links in de zijbalk een <strong class="font-semibold text-white">tenant (bedrijf)</strong> voordat u module-instellingen kunt opslaan.') !!}</p>
        @else
            <p class="mb-0 text-base font-semibold text-white">{{ __('Tenant kiezen') }}</p>
            <p class="mt-1.5 mb-0 text-white">{!! $adminTenantScopeMessage ?? e(__(app(\App\Support\Admin\AdminTenantScope::class)->defaultNoticeMessage())) !!}</p>
        @endif
    </div>
</div>
