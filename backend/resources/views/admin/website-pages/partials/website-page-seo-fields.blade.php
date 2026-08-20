<tr id="website-page-seo-row">
    <td class="text-secondary-foreground font-normal align-top">
        SEO &amp; vindbaarheid
    </td>
    <td class="min-w-0 w-full">
        <label class="block text-sm font-medium text-secondary-foreground mb-1" for="meta_description">
            Meta-omschrijving
        </label>
        <textarea name="meta_description"
                  id="meta_description"
                  rows="3"
                  maxlength="500"
                  class="kt-input w-full min-h-[4.5rem] @error('meta_description') border-destructive @enderror"
                  placeholder="Google-snippet: wie u bent, wat u biedt, welke actie de bezoeker kan nemen.">{{ old('meta_description', $metaDescriptionValue ?? '') }}</textarea>
        <div class="flex flex-wrap items-center justify-between gap-2 mt-1.5">
            <p class="text-xs text-muted-foreground mb-0 flex-1 min-w-[12rem]">
                Google kapt af rond 160 tekens. <strong>SEO genereren</strong> schrijft een zoekwoordrijke snippet — geen knopteksten of afgekapte koppen.
            </p>
            <span id="meta-description-length" class="text-xs text-muted-foreground tabular-nums shrink-0">0 / 160</span>
        </div>
        @error('meta_description')
            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
        @enderror
        <div id="website-page-seo-tips" class="hidden mt-3 rounded-lg border border-border bg-muted/30 p-3 text-xs text-muted-foreground space-y-1.5" aria-live="polite"></div>
        <label class="kt-label flex items-center gap-2 mt-3 mb-0" for="website-page-seo-apply-sections">
            <input type="checkbox" id="website-page-seo-apply-sections" class="kt-checkbox" checked>
            <span class="text-sm text-muted-foreground">Ook hero-teksten invullen (indien aanwezig)</span>
        </label>
    </td>
</tr>
