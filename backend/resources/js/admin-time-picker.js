/**
 * Admin time picker: uren/minuten-kolommen, zelfde look als de datumkiezer.
 */

const HOURS = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'));
const MINUTES = Array.from({ length: 12 }, (_, i) => String(i * 5).padStart(2, '0'));
const POPOVER_ATTR = 'data-admin-time-picker-popover';

function padTimePart(value) {
    const n = Number.parseInt(String(value), 10);
    if (Number.isNaN(n)) {
        return '00';
    }
    return String(Math.min(59, Math.max(0, n))).padStart(2, '0');
}

function parseTime(value) {
    const match = String(value || '').trim().match(/^(\d{1,2}):(\d{2})/);
    if (!match) {
        return { hour: '08', minute: '00' };
    }
    return { hour: padTimePart(match[1]), minute: padTimePart(match[2]) };
}

function minuteOptionsFor(minute) {
    if (MINUTES.includes(minute)) {
        return MINUTES;
    }
    return [...MINUTES, minute].sort();
}

function closeAllTimePickers(except = null) {
    document.querySelectorAll(`[${POPOVER_ATTR}]`).forEach((popover) => {
        if (except && popover === except) {
            return;
        }
        popover.remove();
    });
    document.querySelectorAll('[data-admin-time-picker].is-open').forEach((wrap) => {
        if (except && wrap.contains(except)) {
            return;
        }
        wrap.classList.remove('is-open');
    });
}

function positionPopover(anchor, popover) {
    const rect = anchor.getBoundingClientRect();
    const width = popover.offsetWidth || 152;
    popover.style.left = `${Math.max(8, Math.min(rect.left, window.innerWidth - width - 8))}px`;

    const spaceBelow = window.innerHeight - rect.bottom - 8;
    const spaceAbove = rect.top - 8;
    const height = popover.offsetHeight || 220;
    if (spaceBelow < height && spaceAbove > spaceBelow) {
        popover.style.top = `${Math.max(8, rect.top - height - 4)}px`;
    } else {
        popover.style.top = `${rect.bottom + 4}px`;
    }
}

function scrollItemIntoView(col, button) {
    if (!col || !button) {
        return;
    }
    const top = button.offsetTop - col.clientHeight / 2 + button.offsetHeight / 2;
    col.scrollTop = Math.max(0, top);
}

function openTimePicker(wrap) {
    const input = wrap.querySelector('[data-admin-time-picker-input]');
    if (!input) {
        return;
    }

    closeAllTimePickers();
    wrap.classList.add('is-open');

    const current = parseTime(input.value);
    const minutes = minuteOptionsFor(current.minute);
    const popover = document.createElement('div');
    popover.setAttribute(POPOVER_ATTR, '1');
    popover.className = 'admin-time-picker__popover';
    popover.innerHTML = `
        <div class="admin-time-picker__cols">
            <div class="admin-time-picker__col" data-time-hours role="listbox" aria-label="Uren"></div>
            <div class="admin-time-picker__col" data-time-minutes role="listbox" aria-label="Minuten"></div>
        </div>
    `;

    const hoursCol = popover.querySelector('[data-time-hours]');
    const minutesCol = popover.querySelector('[data-time-minutes]');

    HOURS.forEach((hour) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'admin-time-picker__item';
        btn.textContent = hour;
        btn.setAttribute('role', 'option');
        if (hour === current.hour) {
            btn.classList.add('is-active');
        }
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            hoursCol.querySelectorAll('.is-active').forEach((el) => el.classList.remove('is-active'));
            btn.classList.add('is-active');
            applyTime(input, hour, minutesCol.querySelector('.is-active')?.textContent || current.minute);
        });
        hoursCol.appendChild(btn);
    });

    minutes.forEach((minute) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'admin-time-picker__item';
        btn.textContent = minute;
        btn.setAttribute('role', 'option');
        if (minute === current.minute) {
            btn.classList.add('is-active');
        }
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            minutesCol.querySelectorAll('.is-active').forEach((el) => el.classList.remove('is-active'));
            btn.classList.add('is-active');
            applyTime(input, hoursCol.querySelector('.is-active')?.textContent || current.hour, minute);
            closeAllTimePickers();
        });
        minutesCol.appendChild(btn);
    });

    document.body.appendChild(popover);
    positionPopover(wrap.querySelector('.kt-input') || wrap, popover);
    scrollItemIntoView(hoursCol, hoursCol.querySelector('.is-active'));
    scrollItemIntoView(minutesCol, minutesCol.querySelector('.is-active'));
}

function applyTime(input, hour, minute) {
    const next = `${padTimePart(hour)}:${padTimePart(minute)}`;
    if (input.value !== next) {
        input.value = next;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function bindTimePickers(root = document) {
    root.querySelectorAll('[data-admin-time-picker]').forEach((wrap) => {
        if (wrap.dataset.timePickerBound === '1') {
            return;
        }
        wrap.dataset.timePickerBound = '1';
        wrap.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (wrap.classList.contains('is-open')) {
                closeAllTimePickers();
                return;
            }
            openTimePicker(wrap);
        });
    });
}

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-admin-time-picker], [data-admin-time-picker-popover]')) {
        return;
    }
    closeAllTimePickers();
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeAllTimePickers();
    }
});

window.addEventListener('resize', () => closeAllTimePickers());
window.addEventListener('scroll', (event) => {
    const popover = document.querySelector(`[${POPOVER_ATTR}]`);
    if (!popover) {
        return;
    }
    if (event.target instanceof Element && event.target.closest(`[${POPOVER_ATTR}]`)) {
        return;
    }
    const wrap = document.querySelector('[data-admin-time-picker].is-open');
    if (wrap) {
        positionPopover(wrap.querySelector('.kt-input') || wrap, popover);
    }
}, true);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => bindTimePickers());
} else {
    bindTimePickers();
}

export { bindTimePickers };
