@php
    $tenantName = trim((string) (($websiteTenantContext['effective_company']->name ?? '') ?: ''));
    $selectedLogoSize = (int) ($websiteLogoSize ?? 26);
    $logoSizeChoices = $websiteLogoSizeChoices ?? range(20, 80, 2);
@endphp
<div class="kt-card mb-5">
    <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
        <h5 class="kt-card-title mb-0">Website-logo</h5>
    </div>
    <div class="kt-card-content p-5">
        <p class="text-sm text-muted-foreground mb-4">
            @if($tenantName !== '')
                Hoogte van het tenantlogo in de header van de website voor <span class="text-foreground font-medium">{{ $tenantName }}</span>.
            @else
                Hoogte van het tenantlogo in de header van de website.
            @endif
            Dit is los van het NEXA Suite-logo onder Algemene configuraties.
        </p>
        <form method="POST" action="{{ route('admin.website-pages.logo-size.update') }}" class="flex flex-wrap items-end gap-3 mb-0">
            @csrf
            @foreach ($wizardIndexQuery ?? [] as $wizKey => $wizVal)
                <input type="hidden" name="{{ $wizKey }}" value="{{ $wizVal }}">
            @endforeach
            <div class="min-w-[10rem]">
                <label for="website_logo_size" class="text-sm font-medium text-foreground block mb-1">Logo grootte</label>
                <select name="website_logo_size"
                        id="website_logo_size"
                        class="kt-input @error('website_logo_size') border-destructive @enderror"
                        required>
                    @foreach($logoSizeChoices as $size)
                        <option value="{{ $size }}" {{ (int) old('website_logo_size', $selectedLogoSize) === (int) $size ? 'selected' : '' }}>{{ $size }}px</option>
                    @endforeach
                </select>
                @error('website_logo_size')
                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
        </form>
    </div>
</div>
