function copyWithSelection(text) {
    const span = document.createElement('span');
    span.textContent = text;
    span.style.cssText = 'position:fixed;top:0;left:0;white-space:pre;';
    document.body.appendChild(span);
    const selection = window.getSelection();
    const range = document.createRange();
    range.selectNodeContents(span);
    selection.removeAllRanges();
    selection.addRange(range);
    let copied = false;
    try {
        copied = document.execCommand('copy');
    } catch (err) {
        copied = false;
    }
    selection.removeAllRanges();
    span.remove();

    return copied;
}

function copyFromVisibleEmail(button) {
    const label = button.parentElement?.querySelector('.admin-email-text');
    if (!label) {
        return false;
    }
    const selection = window.getSelection();
    const range = document.createRange();
    range.selectNodeContents(label);
    selection.removeAllRanges();
    selection.addRange(range);
    let copied = false;
    try {
        copied = document.execCommand('copy');
    } catch (err) {
        copied = false;
    }
    selection.removeAllRanges();

    return copied;
}

function markEmailCopied(button) {
    const icon = button.querySelector('i');
    button.setAttribute('title', 'Gekopieerd');
    button.setAttribute('aria-label', 'Gekopieerd');
    if (icon) {
        icon.classList.remove('ki-copy');
        icon.classList.add('ki-check');
    }
    window.setTimeout(() => {
        button.setAttribute('title', 'E-mailadres kopiëren');
        button.setAttribute('aria-label', 'E-mailadres kopiëren');
        if (icon) {
            icon.classList.remove('ki-check');
            icon.classList.add('ki-copy');
        }
    }, 1500);
}

document.addEventListener('click', (event) => {
    const button = event.target?.closest?.('.admin-email-copy');
    if (!button) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    const text = button.getAttribute('data-copy-text') || '';
    if (!text) {
        return;
    }

    try {
        window.focus();
        button.focus();
    } catch (err) {
        // Ignore focus errors in restricted browsers.
    }

    const clipboardPromise = navigator.clipboard && typeof navigator.clipboard.writeText === 'function'
        ? navigator.clipboard.writeText(text)
        : null;

    const copiedNow = copyFromVisibleEmail(button) || copyWithSelection(text);
    if (copiedNow) {
        markEmailCopied(button);
    }

    if (clipboardPromise) {
        clipboardPromise.then(() => {
            markEmailCopied(button);
        }).catch(() => {
            if (!copiedNow) {
                window.prompt('Kopieer dit e-mailadres:', text);
            }
        });
    } else if (!copiedNow) {
        window.prompt('Kopieer dit e-mailadres:', text);
    }
}, true);
