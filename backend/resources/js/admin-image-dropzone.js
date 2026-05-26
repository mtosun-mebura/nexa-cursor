/**
 * Admin drag-and-drop / klik-upload zones: voorkom dubbele bestandskiezer.
 * Oorzaak: link + area handlers die allebei input.click() aanroepen, of meerdere keren binden.
 */

let adminFilePickerLock = false;

export function openAdminFilePicker(fileInput, options = {}) {
    if (!fileInput || typeof fileInput.click !== 'function') {
        return;
    }
    if (adminFilePickerLock) {
        return;
    }
    adminFilePickerLock = true;

    const clearFirst = options.clearInputFirst !== false;
    if (clearFirst) {
        try {
            fileInput.value = '';
        } catch (e) {
            /* ignore */
        }
    }

    fileInput.click();

    const unlock = () => {
        adminFilePickerLock = false;
        window.removeEventListener('focus', unlock);
    };
    window.addEventListener('focus', unlock, { once: true });
    setTimeout(unlock, 1500);
}

/**
 * Mag een klik op de dropzone de file picker openen? (niet als er al een link/knop het doet.)
 */
export function shouldAreaOpenFilePicker(event, area) {
    if (!event || !area) {
        return false;
    }
    const target = event.target;
    if (!target || !area.contains(target)) {
        return false;
    }
    if (target.closest('input[type="file"], button, label, .image-remove-btn')) {
        return false;
    }
    if (target.closest('a')) {
        return false;
    }

    return true;
}

/**
 * Koppel klik op zone (+ optionele link) aan één file input.
 */
export function bindAdminDropzoneClick(area, fileInput, linkEl, options = {}) {
    if (!area || !fileInput) {
        return;
    }
    if (area.dataset.nexaDropzoneBound === '1') {
        return;
    }
    area.dataset.nexaDropzoneBound = '1';

    const open = () => openAdminFilePicker(fileInput, options);

    if (linkEl) {
        linkEl.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            open();
        });
    }

    area.addEventListener('click', function (e) {
        if (!shouldAreaOpenFilePicker(e, area)) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        open();
    });
}

/**
 * Alleen file picker (geen link), bv. hero-image-upload-area.
 */
export function bindAdminUploadAreaClick(area, fileInput, options = {}) {
    if (!area || !fileInput) {
        return;
    }
    if (area.dataset.nexaUploadAreaBound === '1') {
        return;
    }
    area.dataset.nexaUploadAreaBound = '1';

    area.addEventListener('click', function (e) {
        if (e.target.closest('input[type="file"], button, label, .image-remove-btn')) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        openAdminFilePicker(fileInput, options);
    });
}
