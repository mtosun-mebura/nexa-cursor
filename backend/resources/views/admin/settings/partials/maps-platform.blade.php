{{-- Platform Google Maps (Configuraties → Algemene configuraties) --}}
<div class="kt-card mb-8 settings-collapsible-card settings-collapsible-card--collapsed" id="maps">
    @include('admin.settings.partials.collapsible-header', ['titleHtml' => '<i class="ki-filled ki-geolocation me-2"></i> Google Maps Configuratie'])
    <div class="settings-collapsible-body">
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <p class="settings-section-intro">
                Eén Google Maps-configuratie voor de hele NEXA Suite-applicatie: website-footers, admin-kaarten, adresvelden, Google Reviews en boekingsmodules gebruiken deze instellingen.
            </p>
            <form method="POST" action="{{ route('admin.settings.maps.update') }}" data-validate="true" id="maps-platform-form">
                @csrf
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Google Maps API Key *</td>
                        <td class="min-w-48 w-full">
                            <div class="relative">
                                <input type="text"
                                       class="kt-input @error('GOOGLE_MAPS_API_KEY') border-destructive @enderror"
                                       id="GOOGLE_MAPS_API_KEY"
                                       name="GOOGLE_MAPS_API_KEY"
                                       value="{{ old('GOOGLE_MAPS_API_KEY', $mapsSettings['GOOGLE_MAPS_API_KEY'] ?? '') }}"
                                       placeholder="AIzaSy..."
                                       required>
                            </div>
                            <div class="text-xs text-muted-foreground mt-1">Google Maps JavaScript API key voor kaarten, geocoding en postcode/adres-lookup als OpenPostcode of PDOK geen straat vindt.</div>
                            @error('GOOGLE_MAPS_API_KEY')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Map ID (optioneel)</td>
                        <td class="min-w-48 w-full">
                            <div class="relative">
                                <input type="text"
                                       class="kt-input @error('GOOGLE_MAPS_MAP_ID') border-destructive @enderror"
                                       id="GOOGLE_MAPS_MAP_ID"
                                       name="GOOGLE_MAPS_MAP_ID"
                                       value="{{ old('GOOGLE_MAPS_MAP_ID', $mapsSettings['GOOGLE_MAPS_MAP_ID'] ?? '') }}"
                                       placeholder="bijv. abc123def456">
                            </div>
                            <div class="text-xs text-muted-foreground mt-1">Voor Advanced Markers (geen deprecation-warning). Aanmaken in <a href="https://console.cloud.google.com/google/maps-apis/studio/maps" target="_blank" rel="noopener" class="underline">Google Cloud Console → Map Management</a></div>
                            @error('GOOGLE_MAPS_MAP_ID')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Standaard Zoom Level</td>
                        <td class="min-w-48 w-full">
                            <div class="relative">
                                <input type="number"
                                       class="kt-input @error('GOOGLE_MAPS_ZOOM') border-destructive @enderror"
                                       id="GOOGLE_MAPS_ZOOM"
                                       name="GOOGLE_MAPS_ZOOM"
                                       value="{{ old('GOOGLE_MAPS_ZOOM', $mapsSettings['GOOGLE_MAPS_ZOOM'] ?? '12') }}"
                                       placeholder="12"
                                       min="1"
                                       max="20">
                            </div>
                            <div class="text-xs text-muted-foreground mt-1">Standaard zoom level voor kaarten (1-20, standaard: 12)</div>
                            @error('GOOGLE_MAPS_ZOOM')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Standaard Centrum (Latitude)</td>
                        <td class="min-w-48 w-full">
                            <div class="relative">
                                <input type="text"
                                       class="kt-input @error('GOOGLE_MAPS_CENTER_LAT') border-destructive @enderror"
                                       id="GOOGLE_MAPS_CENTER_LAT"
                                       name="GOOGLE_MAPS_CENTER_LAT"
                                       value="{{ old('GOOGLE_MAPS_CENTER_LAT', $mapsSettings['GOOGLE_MAPS_CENTER_LAT'] ?? '52.3676') }}"
                                       placeholder="52.3676">
                            </div>
                            <div class="text-xs text-muted-foreground mt-1">Standaard latitude voor kaart centrum (bijv. 52.3676 voor Amsterdam)</div>
                            @error('GOOGLE_MAPS_CENTER_LAT')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Standaard Centrum (Longitude)</td>
                        <td class="min-w-48 w-full">
                            <div class="relative">
                                <input type="text"
                                       class="kt-input @error('GOOGLE_MAPS_CENTER_LNG') border-destructive @enderror"
                                       id="GOOGLE_MAPS_CENTER_LNG"
                                       name="GOOGLE_MAPS_CENTER_LNG"
                                       value="{{ old('GOOGLE_MAPS_CENTER_LNG', $mapsSettings['GOOGLE_MAPS_CENTER_LNG'] ?? '4.9041') }}"
                                       placeholder="4.9041">
                            </div>
                            <div class="text-xs text-muted-foreground mt-1">Standaard longitude voor kaart centrum (bijv. 4.9041 voor Amsterdam)</div>
                            @error('GOOGLE_MAPS_CENTER_LNG')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Map Type</td>
                        <td class="min-w-48 w-full">
                            <div class="relative">
                                <select class="kt-select @error('GOOGLE_MAPS_TYPE') border-destructive @enderror"
                                        id="GOOGLE_MAPS_TYPE"
                                        name="GOOGLE_MAPS_TYPE">
                                    <option value="roadmap" {{ old('GOOGLE_MAPS_TYPE', $mapsSettings['GOOGLE_MAPS_TYPE'] ?? 'roadmap') === 'roadmap' ? 'selected' : '' }}>Roadmap</option>
                                    <option value="satellite" {{ old('GOOGLE_MAPS_TYPE', $mapsSettings['GOOGLE_MAPS_TYPE'] ?? 'roadmap') === 'satellite' ? 'selected' : '' }}>Satellite</option>
                                    <option value="hybrid" {{ old('GOOGLE_MAPS_TYPE', $mapsSettings['GOOGLE_MAPS_TYPE'] ?? 'roadmap') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                                    <option value="terrain" {{ old('GOOGLE_MAPS_TYPE', $mapsSettings['GOOGLE_MAPS_TYPE'] ?? 'roadmap') === 'terrain' ? 'selected' : '' }}>Terrain</option>
                                </select>
                            </div>
                            <div class="text-xs text-muted-foreground mt-1">Standaard kaart type</div>
                            @error('GOOGLE_MAPS_TYPE')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    @php
                        $pdokFallbackOn = old('POSTCODE_PDOK_FALLBACK', \App\Models\GeneralSetting::get('POSTCODE_PDOK_FALLBACK', '1')) !== '0';
                    @endphp
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Gratis postcode-fallback</td>
                        <td class="min-w-48 w-full">
                            <input type="hidden" name="POSTCODE_PDOK_FALLBACK" value="0">
                            <label class="kt-label flex items-center gap-2" for="POSTCODE_PDOK_FALLBACK">
                                <input type="checkbox"
                                       class="kt-switch kt-switch-sm shrink-0"
                                       id="POSTCODE_PDOK_FALLBACK"
                                       name="POSTCODE_PDOK_FALLBACK"
                                       value="1"
                                       {{ $pdokFallbackOn ? 'checked' : '' }}>
                                <span>PDOK Locatieserver (Kadaster) als 2e check</span>
                            </label>
                            <div class="text-xs text-muted-foreground mt-1">Gratis BAG-adressen van het Kadaster. Wordt gebruikt als OpenPostcode geen straat teruggeeft. Geen API-sleutel nodig.</div>
                        </td>
                    </tr>
                </table>
                <div class="kt-card-footer flex justify-end items-center gap-5 pt-5 border-t border-border">
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-check me-2"></i> Google Maps opslaan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
