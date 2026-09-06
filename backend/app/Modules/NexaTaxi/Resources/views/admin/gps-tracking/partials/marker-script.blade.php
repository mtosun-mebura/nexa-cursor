<script>
window.NexaGpsCarAssets = {
    sedan: @json(asset('images/gps/car-sedan.png').'?v='.filemtime(public_path('images/gps/car-sedan.png'))),
    van: @json(asset('images/gps/car-van.png').'?v='.filemtime(public_path('images/gps/car-van.png'))),
    bus: @json(asset('images/gps/car-bus.png').'?v='.filemtime(public_path('images/gps/car-bus.png')))
};
window.NexaGpsMarker = (function () {
    var assetCache = {};
    var tintCache = {};

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function hexToRgb(hex) {
        var h = String(hex || '').replace('#', '');
        if (h.length !== 6) return { r: 234, g: 88, b: 12 };
        return {
            r: parseInt(h.slice(0, 2), 16),
            g: parseInt(h.slice(2, 4), 16),
            b: parseInt(h.slice(4, 6), 16)
        };
    }

    function carBox(style, sizeKey) {
        var key = sizeKey === 'picker' || sizeKey === 'preview' ? sizeKey : 'map';
        var sizes = {
            map: { sedan: [28, 38], van: [30, 42], bus: [34, 56] },
            preview: { sedan: [36, 48], van: [40, 54], bus: [44, 70] },
            picker: { sedan: [52, 70], van: [56, 78], bus: [58, 96] }
        };
        return sizes[key][style] || sizes[key].sedan;
    }

    function fallbackSvg(style, color, sizeKey) {
        var box = carBox(style, sizeKey);
        var body = color || '#ea580c';
        var dark = 'rgba(15,23,42,0.28)';
        var light = 'rgba(255,255,255,0.22)';
        if (style === 'bus') {
            return '<svg class="nexa-gps-car-svg" viewBox="0 0 56 140" width="' + box[0] + '" height="' + box[1] + '" aria-hidden="true">' +
                '<rect x="4" y="22" width="8" height="18" rx="2.5" fill="#111827"/>' +
                '<rect x="44" y="22" width="8" height="18" rx="2.5" fill="#111827"/>' +
                '<rect x="4" y="102" width="8" height="18" rx="2.5" fill="#111827"/>' +
                '<rect x="44" y="102" width="8" height="18" rx="2.5" fill="#111827"/>' +
                '<rect x="10" y="4" width="36" height="132" rx="10" fill="' + body + '"/>' +
                '<rect x="16" y="12" width="24" height="14" rx="3" fill="#dbeafe" opacity="0.95"/>' +
                '<rect x="16" y="32" width="24" height="10" rx="2" fill="#dbeafe" opacity="0.85"/>' +
                '<rect x="16" y="48" width="24" height="10" rx="2" fill="#dbeafe" opacity="0.85"/>' +
                '<rect x="16" y="64" width="24" height="10" rx="2" fill="#dbeafe" opacity="0.85"/>' +
                '<rect x="16" y="80" width="24" height="10" rx="2" fill="#dbeafe" opacity="0.85"/>' +
                '<rect x="16" y="112" width="24" height="12" rx="2" fill="#dbeafe" opacity="0.8"/>' +
                '</svg>';
        }
        if (style === 'van') {
            return '<svg class="nexa-gps-car-svg" viewBox="0 0 56 118" width="' + box[0] + '" height="' + box[1] + '" aria-hidden="true">' +
                '<defs><linearGradient id="gvan" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="' + dark + '"/><stop offset=".45" stop-color="' + light + '"/><stop offset="1" stop-color="' + dark + '"/></linearGradient></defs>' +
                '<rect x="4" y="26" width="8" height="20" rx="2.5" fill="#111827"/>' +
                '<rect x="44" y="26" width="8" height="20" rx="2.5" fill="#111827"/>' +
                '<rect x="4" y="78" width="8" height="20" rx="2.5" fill="#111827"/>' +
                '<rect x="44" y="78" width="8" height="20" rx="2.5" fill="#111827"/>' +
                '<rect x="10" y="6" width="36" height="106" rx="10" fill="' + body + '"/>' +
                '<rect x="10" y="6" width="36" height="106" rx="10" fill="url(#gvan)"/>' +
                '<rect x="16" y="14" width="24" height="16" rx="3" fill="#dbeafe" opacity="0.95"/>' +
                '<rect x="16" y="90" width="24" height="12" rx="2" fill="#dbeafe" opacity="0.8"/>' +
                '</svg>';
        }
        return '<svg class="nexa-gps-car-svg" viewBox="0 0 52 110" width="' + box[0] + '" height="' + box[1] + '" aria-hidden="true">' +
            '<path d="M12 6h28c7 0 10 6 10 13v72c0 8-4 13-12 13H14C6 104 2 99 2 91V19C2 12 5 6 12 6z" fill="' + body + '"/>' +
            '<rect x="14" y="20" width="24" height="18" rx="4" fill="#dbeafe" opacity="0.95"/>' +
            '</svg>';
    }

    function isMagentaPixel(r, g, b, a) {
        if (a < 16) return false;
        var magenta = (r + b) / 2 - g;
        return magenta > 28 && g < 170 && r > 40 && b > 40;
    }

    function tintImage(source, hex) {
        var canvas = document.createElement('canvas');
        canvas.width = source.naturalWidth || source.width;
        canvas.height = source.naturalHeight || source.height;
        var ctx = canvas.getContext('2d');
        if (!ctx || !canvas.width) return '';
        ctx.drawImage(source, 0, 0);
        var image = ctx.getImageData(0, 0, canvas.width, canvas.height);
        var px = image.data;
        var rgb = hexToRgb(hex);
        for (var i = 0; i < px.length; i += 4) {
            if (px[i + 3] < 16) {
                px[i] = 0;
                px[i + 1] = 0;
                px[i + 2] = 0;
                px[i + 3] = 0;
                continue;
            }
            if (!isMagentaPixel(px[i], px[i + 1], px[i + 2], px[i + 3])) continue;
            var shade = Math.max(px[i], px[i + 2]) / 255;
            shade = Math.max(0.18, Math.min(1, shade));
            px[i] = Math.round(rgb.r * shade);
            px[i + 1] = Math.round(rgb.g * shade);
            px[i + 2] = Math.round(rgb.b * shade);
        }
        ctx.putImageData(image, 0, 0);
        return canvas.toDataURL('image/png');
    }

    function loadAsset(style, done) {
        var src = (window.NexaGpsCarAssets || {})[style] || (window.NexaGpsCarAssets || {}).sedan;
        if (assetCache[src] && assetCache[src].complete) {
            done(assetCache[src]);
            return;
        }
        var img = new Image();
        img.onload = function () {
            assetCache[src] = img;
            done(img);
        };
        img.onerror = function () { done(null); };
        img.src = src;
    }

    function applyTint(imgEl, style, color) {
        var key = style + '|' + String(color || '').toLowerCase();
        if (tintCache[key]) {
            imgEl.src = tintCache[key];
            imgEl.style.visibility = 'visible';
            return;
        }
        loadAsset(style, function (source) {
            if (!source) {
                imgEl.style.visibility = 'visible';
                return;
            }
            try {
                var url = tintImage(source, color);
                if (url) {
                    tintCache[key] = url;
                    imgEl.src = url;
                }
            } catch (e) {}
            imgEl.style.visibility = 'visible';
        });
    }

    function carElement(style, color, sizeKey) {
        style = style === 'van' || style === 'bus' ? style : 'sedan';
        var box = carBox(style, sizeKey);
        var src = (window.NexaGpsCarAssets || {})[style];
        if (!src) {
            var wrap = document.createElement('span');
            wrap.innerHTML = fallbackSvg(style, color, sizeKey);
            return wrap.firstChild;
        }
        var img = document.createElement('img');
        img.className = 'nexa-gps-car-img';
        img.alt = '';
        img.width = box[0];
        img.height = box[1];
        img.draggable = false;
        img.style.width = box[0] + 'px';
        img.style.height = box[1] + 'px';
        img.style.visibility = 'hidden';
        img.src = src;
        applyTint(img, style, color);
        return img;
    }

    function carSvg(style, color, sizeKey) {
        var holder = document.createElement('div');
        holder.appendChild(carElement(style, color, sizeKey));
        return holder.innerHTML;
    }

    function paintCarInto(container, style, color, sizeKey) {
        if (!container) return;
        container.innerHTML = '';
        container.appendChild(carElement(style, color, sizeKey));
    }

    function bodyColor(item, appearance) {
        var style = (item && item.car_style) || (appearance && appearance.car_style) || 'sedan';
        if (appearance && appearance.car_color_mode === 'single') {
            var typeColors = appearance.type_colors || {};
            return typeColors[style] || appearance.car_color || '#ea580c';
        }
        if (item && item.color) {
            return item.color;
        }
        var colors = appearance && appearance.vehicle_colors ? appearance.vehicle_colors : {};
        var vid = item && item.vehicle_id != null ? String(item.vehicle_id) : '';
        if (vid && colors[vid]) {
            return colors[vid];
        }
        return (appearance && appearance.car_color) || '#ea580c';
    }

    function markerNode(item, appearance, heading, sizeKey) {
        appearance = appearance || {};
        var wrap = document.createElement('div');
        wrap.className = 'nexa-gps-marker' + (item.is_online ? '' : ' is-offline');
        wrap.title = (item.license_plate || 'Geen kenteken') + ' · ' + (item.driver_name || '');
        if (item.id) wrap.setAttribute('data-gps-marker-id', item.id);

        var plate = document.createElement('div');
        plate.className = 'nexa-gps-plate';
        plate.style.background = appearance.plate_background || '#f7e125';
        plate.style.color = appearance.plate_text_color || '#111827';
        plate.style.borderColor = appearance.plate_border_color || '#111827';
        plate.textContent = item.license_plate || item.driver_name || '—';

        var rot = document.createElement('div');
        rot.className = 'nexa-gps-car-rot';
        rot.style.transform = 'rotate(' + (Number(heading) || 0) + 'deg)';
        rot.appendChild(carElement(item.car_style || appearance.car_style || 'sedan', bodyColor(item, appearance), sizeKey || 'map'));

        wrap.appendChild(plate);
        wrap.appendChild(rot);
        return wrap;
    }

    ['sedan', 'van', 'bus'].forEach(function (style) {
        loadAsset(style, function () {});
    });

    return {
        carSvg: carSvg,
        carElement: carElement,
        paintCarInto: paintCarInto,
        markerNode: markerNode,
        bodyColor: bodyColor,
        escapeHtml: escapeHtml
    };
})();
</script>
