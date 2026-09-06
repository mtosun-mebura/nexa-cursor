/**
 * Overal in admin: een AI-gegenereerde afbeelding (uit /admin/ai-images) kunnen slepen of
 * plakken in een bestaand upload-veld, zonder dat elk formulier/dropzone aangepast hoeft te
 * worden. Werkt door het gedropte/geplakte beeld om te zetten naar een echt File-object en
 * daarna hetzelfde event te simuleren dat een normale bestandskeuze al zou triggeren:
 * - bij een <input type="file">: .files zetten + 'change' dispatchen (bestaande upload-logica
 *   reageert daar al op);
 * - bij een page-builder dropzone (.builder-media-upload-area): een echte 'drop' DragEvent met
 *   het bestand opnieuw dispatchen, zodat Vue's eigen onMediaDrop-flow het gewoon verwerkt.
 *
 * De drag-bron (galerij-thumbnails in resources/views/admin/ai-images/index.blade.php) zet een
 * custom MIME-type 'application/x-nexa-ai-image' met { uuid, url }. Zonder die marker doet deze
 * module niets — normale bestandssleep/drop-acties blijven ongewijzigd.
 */

const CUSTOM_TYPE = 'application/x-nexa-ai-image';

function findNearestFileInput(el) {
    if (!el) {
        return null;
    }
    if (el.matches && el.matches('input[type="file"]')) {
        return el;
    }
    let node = el;
    let depth = 0;
    while (node && depth < 8) {
        if (node.querySelector) {
            const input = node.querySelector('input[type="file"]');
            if (input) {
                return input;
            }
        }
        if (node === document.body) {
            break;
        }
        node = node.parentElement;
        depth++;
    }
    return null;
}

function injectFile(target, file) {
    const uploadArea = target && target.closest ? target.closest('.builder-media-upload-area') : null;
    if (uploadArea) {
        const dt = new DataTransfer();
        dt.items.add(file);
        uploadArea.dispatchEvent(new DragEvent('drop', { bubbles: true, cancelable: true, dataTransfer: dt }));
        return true;
    }

    const fileInput = findNearestFileInput(target);
    if (fileInput) {
        const dt2 = new DataTransfer();
        dt2.items.add(file);
        fileInput.files = dt2.files;
        fileInput.dispatchEvent(new Event('change', { bubbles: true }));
        return true;
    }

    return false;
}

function isUploadTarget(el) {
    if (!el || !el.closest) {
        return false;
    }
    if (el.matches && el.matches('input[type="file"]')) {
        return true;
    }
    return !!(el.closest('.builder-media-upload-area') || findNearestFileInput(el));
}

let lastHoveredUploadTarget = null;
document.addEventListener('mouseover', function (event) {
    const el = event.target;
    if (isUploadTarget(el)) {
        lastHoveredUploadTarget = el;
    }
}, true);

// Sta de drop toe zodra de browser ziet dat het onze eigen galerij-drag is.
document.addEventListener('dragover', function (event) {
    if (event.dataTransfer && Array.from(event.dataTransfer.types || []).includes(CUSTOM_TYPE)) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
    }
}, true);

document.addEventListener('drop', function (event) {
    if (!event.dataTransfer || !Array.from(event.dataTransfer.types || []).includes(CUSTOM_TYPE)) {
        return;
    }
    const raw = event.dataTransfer.getData(CUSTOM_TYPE);
    if (!raw) {
        return;
    }
    let payload;
    try {
        payload = JSON.parse(raw);
    } catch (e) {
        return;
    }
    if (!payload || !payload.url) {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    const target = event.target;
    fetch(payload.url)
        .then((res) => res.blob())
        .then((blob) => {
            const file = new File([blob], 'ai-image.png', { type: blob.type || 'image/png' });
            injectFile(target, file);
        })
        .catch(() => { /* stil negeren: geen bestand beschikbaar */ });
}, true);

document.addEventListener('paste', function (event) {
    const clipboardFiles = event.clipboardData ? event.clipboardData.files : null;
    if (!clipboardFiles || !clipboardFiles.length) {
        return;
    }
    const file = clipboardFiles[0];
    if (!file || !/^image\//.test(file.type)) {
        return;
    }

    const active = document.activeElement;
    const target = (active && isUploadTarget(active)) ? active : lastHoveredUploadTarget;
    if (!target || !isUploadTarget(target)) {
        return;
    }

    event.preventDefault();
    injectFile(target, file);
});
