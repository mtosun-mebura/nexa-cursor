@php
    $tenantName = trim((string) (($websiteTenantContext['effective_company']->name ?? '') ?: ''));
    $selectedLogoSize = (int) ($websiteLogoSize ?? 26);
    $selectedLogoPaddingLeft = (int) ($websiteLogoPaddingLeft ?? 0);
    $logoSizeChoices = $websiteLogoSizeChoices ?? range(20, 100, 2);
    $logoPaddingLeftChoices = $websiteLogoPaddingLeftChoices ?? range(0, 48, 2);
@endphp
<div class="kt-card mb-5">
    <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
        <h5 class="kt-card-title mb-0">Website-logo</h5>
    </div>
    <div class="kt-card-content p-5">
        <p class="text-sm text-muted-foreground mb-4">
            @if($tenantName !== '')
                Formaat van het tenantlogo in de header van de website voor <span class="text-foreground font-medium">{{ $tenantName }}</span>.
            @else
                Formaat van het tenantlogo in de header van de website.
            @endif
            Dit is los van het NEXA Suite-logo onder Algemene configuraties.
        </p>
        <form method="POST" action="{{ route('admin.website-pages.logo-size.update') }}" class="flex flex-wrap items-end gap-3 mb-0">
            @csrf
            @foreach ($wizardIndexQuery ?? [] as $wizKey => $wizVal)
                <input type="hidden" name="{{ $wizKey }}" value="{{ $wizVal }}">
            @endforeach
            <div class="admin-field-fit">
                <label for="website_logo_size" class="text-sm font-medium text-foreground block mb-1">Logo grootte</label>
                <select name="website_logo_size"
                        id="website_logo_size"
                        class="kt-select admin-field-fit @error('website_logo_size') border-destructive @enderror"
                        data-kt-select="true"
                        required>
                    @foreach($logoSizeChoices as $size)
                        <option value="{{ $size }}" {{ (int) old('website_logo_size', $selectedLogoSize) === (int) $size ? 'selected' : '' }}>{{ $size }}px</option>
                    @endforeach
                </select>
                @error('website_logo_size')
                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="admin-field-fit">
                <label for="website_logo_padding_left" class="text-sm font-medium text-foreground block mb-1">Padding links</label>
                <select name="website_logo_padding_left"
                        id="website_logo_padding_left"
                        class="kt-select admin-field-fit @error('website_logo_padding_left') border-destructive @enderror"
                        data-kt-select="true"
                        required>
                    @foreach($logoPaddingLeftChoices as $pad)
                        <option value="{{ $pad }}" {{ (int) old('website_logo_padding_left', $selectedLogoPaddingLeft) === (int) $pad ? 'selected' : '' }}>{{ $pad }}px</option>
                    @endforeach
                </select>
                @error('website_logo_padding_left')
                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
        </form>
    </div>
</div>
