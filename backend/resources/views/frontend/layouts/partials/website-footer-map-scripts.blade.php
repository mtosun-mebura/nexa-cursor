@php
    $googleMapsKeyTrimmed = trim((string) ($googleMapsApiKey ?? ''));
@endphp
@if($googleMapsKeyTrimmed !== '')
<link rel="preconnect" href="https://maps.googleapis.com" crossorigin>
<link rel="preconnect" href="https://maps.gstatic.com" crossorigin>
<link rel="dns-prefetch" href="https://maps.googleapis.com">
<link rel="dns-prefetch" href="https://maps.gstatic.com">
<script>
(function() {
    window.initFooterMap = function() {
        var mapEl = document.getElementById('footer-google-map');
        if (!mapEl || mapEl.getAttribute('data-footer-map-initialized') === '1') return;
        if (typeof google === 'undefined' || !google.maps || !google.maps.Map) return;
        var mapId = (mapEl.getAttribute('data-map-id') || '').trim();
        if (!mapId) mapId = 'DEMO_MAP_ID';
        var useAdvancedMarker = true;
        var latStr = (mapEl.getAttribute('data-lat') || '').trim();
        var lngStr = (mapEl.getAttribute('data-lng') || '').trim();
        var address = (mapEl.getAttribute('data-address') || '').trim();
        var zoomStr = (mapEl.getAttribute('data-zoom') || '').trim();
        var showAddressBalloon = (mapEl.getAttribute('data-show-address-balloon') || '') === '1';
        var lat = parseFloat(latStr);
        var lng = parseFloat(lngStr);
        var zoom = (zoomStr !== '' && !isNaN(parseInt(zoomStr, 10))) ? parseInt(zoomStr, 10) : 17;
        if (zoom < 1 || zoom > 20) zoom = 17;
        var hasCoords = latStr !== '' && lngStr !== '' && !isNaN(lat) && !isNaN(lng);
        var center = { lat: 52.3676, lng: 4.9041 };
        if (hasCoords) center = { lat: lat, lng: lng };
        var useAdvanced = useAdvancedMarker && mapId && mapId.length > 0;
        var mapOptions = {
            center: center,
            zoom: zoom,
            scrollwheel: false,
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControl: true
        };
        if (useAdvanced) mapOptions.mapId = mapId;
        var map;
        try {
            map = new google.maps.Map(mapEl, mapOptions);
        } catch (e) {
            delete mapOptions.mapId;
            useAdvanced = false;
            map = new google.maps.Map(mapEl, mapOptions);
        }
        mapEl.setAttribute('data-footer-map-initialized', '1');
        window._footerGoogleMap = map;
        function addMarkerSafe(m, pos) {
            if (useAdvanced && google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
                try {
                    return new google.maps.marker.AdvancedMarkerElement({ map: m, position: pos });
                } catch (err) {
                    return new google.maps.Marker({ position: pos, map: m });
                }
            }
            return new google.maps.Marker({ position: pos, map: m });
        }
        function openAddressBalloon(marker, addr) {
            addr = (addr != null) ? String(addr).trim() : '';
            if (!addr || !showAddressBalloon || !google.maps.InfoWindow) return;
            var infoWindow = new google.maps.InfoWindow({ content: '<div style="padding: 4px 8px 6px; font-size: 14px; color: #000; line-height: 1.25; margin: 0;">' + addr.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>' });
            infoWindow.open(map, marker);
        }
        if (hasCoords) {
            var marker = addMarkerSafe(map, center);
            openAddressBalloon(marker, address);
        } else if (address && google.maps.Geocoder) {
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({ address: address }, function(results, status) {
                if (status === 'OK' && results && results[0]) {
                    var loc = results[0].geometry.location;
                    map.setCenter(loc);
                    map.setZoom(zoom);
                    var marker = addMarkerSafe(map, loc);
                    openAddressBalloon(marker, address);
                } else {
                    addMarkerSafe(map, center);
                }
            });
        } else {
            addMarkerSafe(map, center);
        }
        function triggerResize() {
            if (google.maps && google.maps.event && map) google.maps.event.trigger(map, 'resize');
        }
        setTimeout(triggerResize, 100);
        setTimeout(triggerResize, 300);
        setTimeout(triggerResize, 600);
        window.addEventListener('load', triggerResize);
        if (typeof ResizeObserver !== 'undefined' && mapEl.parentElement) {
            var ro = new ResizeObserver(function() { setTimeout(triggerResize, 50); });
            ro.observe(mapEl.parentElement);
        }
    };
    window.resizeFooterMap = function() {
        var map = window._footerGoogleMap;
        if (map && typeof google !== 'undefined' && google.maps && google.maps.event) {
            google.maps.event.trigger(map, 'resize');
            var center = map.getCenter && map.getCenter();
            if (center) map.setCenter(center);
            return;
        }
        if (typeof window.initFooterMap === 'function') window.initFooterMap();
    };
})();
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ rawurlencode($googleMapsKeyTrimmed) }}&amp;libraries=marker&amp;callback=initFooterMap&amp;loading=async"></script>
@endif
