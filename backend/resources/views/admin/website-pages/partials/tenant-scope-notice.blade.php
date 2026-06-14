{{-- Alleen super-admin zonder gekozen tenant in de zijbalk --}}
@if(auth()->user()->hasRole('super-admin') && !($websitePagesTenantScopedActive ?? false))
    <div class="tenant-scope-notice mb-5 flex gap-3 rounded-xl px-4 py-4 text-sm shadow-md" role="status">
        <i class="ki-filled ki-information mt-0.5 shrink-0 text-2xl text-white/90"></i>
        <div class="min-w-0 flex-1 leading-relaxed text-white">
            <p class="mb-0 text-base font-semibold text-white">Tenant kiezen</p>
            <p class="mt-1.5 mb-0 text-white">Kies links in de zijbalk een <strong class="font-semibold text-white">tenant (bedrijf)</strong> voordat u website-pagina&apos;s kunt bekijken en beheren.</p>
        </div>
    </div>
@endif
