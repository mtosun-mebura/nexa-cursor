/**
 * KT / Vanilla Calendar: popup aan input houden, omhoog openen bij weinig ruimte onder.
 */

const OPEN_CALENDAR_SELECTOR = '[data-vc="calendar"][data-vc-input]:not([data-vc-calendar-hidden])';
const FLOATING_CLASS = 'admin-date-picker-floating';
const GAP_PX = 4;
const VIEWPORT_PADDING_PX = 8;
const Z_INDEX = 1060;
const CALENDAR_FALLBACK_HEIGHT = 336;
const CALENDAR_FALLBACK_WIDTH = 280;

function getPositionMode(input) {
    return input.getAttribute('data-kt-date-picker-position-to-input') || 'left';
}

function findOpenCalendar() {
    return document.querySelector(OPEN_CALENDAR_SELECTOR);
}

function getCalendarSize(calendar) {
    const rect = calendar.getBoundingClientRect();
    const measuredHeight = Math.max(rect.height, calendar.offsetHeight, calendar.scrollHeight);
    const measuredWidth = Math.max(rect.width, calendar.offsetWidth, calendar.scrollWidth);

    return {
        width: measuredWidth > 0 ? measuredWidth : CALENDAR_FALLBACK_WIDTH,
        height: measuredHeight > 80 ? measuredHeight : CALENDAR_FALLBACK_HEIGHT,
    };
}

function computeHorizontalLeft(anchorRect, calendarWidth, positionMode, viewportWidth) {
    let left;

    switch (positionMode) {
        case 'right':
            left = anchorRect.right - calendarWidth;
            break;
        case 'center':
            left = anchorRect.left + (anchorRect.width - calendarWidth) / 2;
            break;
        default:
            left = anchorRect.left;
            break;
    }

    const maxLeft = viewportWidth - calendarWidth - VIEWPORT_PADDING_PX;
    return Math.min(Math.max(VIEWPORT_PADDING_PX, left), Math.max(VIEWPORT_PADDING_PX, maxLeft));
}

function computeVerticalTop(anchorRect, calendarHeight, viewportHeight) {
    const spaceBelow = viewportHeight - VIEWPORT_PADDING_PX - anchorRect.bottom;
    const spaceAbove = anchorRect.top - VIEWPORT_PADDING_PX;
    const needed = calendarHeight + GAP_PX;

    if (spaceBelow < needed && spaceAbove >= needed) {
        return anchorRect.top - calendarHeight - GAP_PX;
    }

    if (spaceBelow < needed && spaceAbove > spaceBelow) {
        return Math.max(VIEWPORT_PADDING_PX, anchorRect.top - calendarHeight - GAP_PX);
    }

    return anchorRect.bottom + GAP_PX;
}

function clearFloatingCalendar(calendar) {
    if (!calendar) {
        return;
    }

    calendar.classList.remove(FLOATING_CLASS);
    calendar.removeAttribute('data-admin-date-picker-placement');
    calendar.style.removeProperty('position');
    calendar.style.removeProperty('top');
    calendar.style.removeProperty('left');
    calendar.style.removeProperty('right');
    calendar.style.removeProperty('bottom');
    calendar.style.removeProperty('margin');
    calendar.style.removeProperty('z-index');
}

function applyFloatingPosition(calendar, top, left, placement) {
    calendar.classList.add(FLOATING_CLASS);
    calendar.setAttribute('data-admin-date-picker-placement', placement);
    calendar.style.setProperty('position', 'fixed', 'important');
    calendar.style.setProperty('top', `${Math.round(top)}px`, 'important');
    calendar.style.setProperty('left', `${Math.round(left)}px`, 'important');
    calendar.style.setProperty('right', 'auto', 'important');
    calendar.style.setProperty('bottom', 'auto', 'important');
    calendar.style.setProperty('margin', '0', 'important');
    calendar.style.setProperty('z-index', String(Z_INDEX), 'important');
}

function repositionOpenDatePicker() {
    const calendar = findOpenCalendar();
    if (!calendar) {
        return;
    }

    const input = document.querySelector('[data-vc-input-focus]') ||
        document.querySelector('[data-kt-date-picker][data-vc-input-focus]') ||
        document.activeElement?.matches?.('[data-kt-date-picker]') ? document.activeElement : null;

    if (!input) {
        return;
    }

    const anchor = input.closest('.kt-input') || input;
    const anchorRect = anchor.getBoundingClientRect();
    const calendarSize = getCalendarSize(calendar);
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;

    let top = computeVerticalTop(anchorRect, calendarSize.height, viewportHeight);
    const placement = top < anchorRect.top ? 'top' : 'bottom';
    const left = computeHorizontalLeft(anchorRect, calendarSize.width, getPositionMode(input), viewportWidth);

    if (top + calendarSize.height > viewportHeight - VIEWPORT_PADDING_PX) {
        top = viewportHeight - VIEWPORT_PADDING_PX - calendarSize.height;
    }
    top = Math.max(VIEWPORT_PADDING_PX, top);

    applyFloatingPosition(calendar, top, left, placement);
}

function scheduleReposition() {
    requestAnimationFrame(repositionOpenDatePicker);
}

function burstReposition() {
    [0, 20, 50, 100, 200].forEach((delay) => {
        window.setTimeout(scheduleReposition, delay);
    });
}

function ensureDatePickersInitialized() {
    if (typeof window.KTDatePicker !== 'undefined' && typeof window.KTDatePicker.createInstances === 'function') {
        window.KTDatePicker.createInstances();
    }
}

function bindDatePickerWrapperClicks() {
    document.querySelectorAll('.kt-input').forEach((wrapper) => {
        const input = wrapper.querySelector('[data-kt-date-picker]');
        if (!input || wrapper.dataset.adminDatePickerClickBound === '1') {
            return;
        }

        wrapper.dataset.adminDatePickerClickBound = '1';
        wrapper.addEventListener('click', (event) => {
            if (event.target === input) {
                return;
            }

            event.preventDefault();
            input.focus();
            input.click();
        });
    });
}

function bindAdminDatePickerScrollFix() {
    if (document.body.dataset.adminDatePickerScrollFix === '1') {
        return;
    }
    document.body.dataset.adminDatePickerScrollFix = '1';

    ensureDatePickersInitialized();
    bindDatePickerWrapperClicks();

    window.addEventListener('scroll', scheduleReposition, true);
    window.addEventListener('resize', scheduleReposition);

    document.addEventListener('click', (event) => {
        const input = event.target.closest?.('[data-kt-date-picker]');
        if (input) {
            window.setTimeout(burstReposition, 0);
        }
    }, true);

    const observer = new MutationObserver((mutations) => {
        let opened = false;
        let closed = false;

        for (const mutation of mutations) {
            const target = mutation.target;
            if (!(target instanceof HTMLElement) || !target.matches('[data-vc="calendar"][data-vc-input]')) {
                continue;
            }

            if (mutation.attributeName === 'data-vc-calendar-hidden') {
                if (target.hasAttribute('data-vc-calendar-hidden')) {
                    closed = true;
                    clearFloatingCalendar(target);
                } else {
                    opened = true;
                }
            }
        }

        if (opened) {
            burstReposition();
        }

        if (closed && !findOpenCalendar()) {
            document.querySelectorAll(`.${FLOATING_CLASS}`).forEach(clearFloatingCalendar);
        }
    });

    observer.observe(document.body, {
        attributes: true,
        subtree: true,
        attributeFilter: ['data-vc-calendar-hidden'],
    });

    const content = document.getElementById('content');
    if (content && !content.dataset.adminDatePickerObserver) {
        content.dataset.adminDatePickerObserver = '1';
        const contentObserver = new MutationObserver(() => {
            ensureDatePickersInitialized();
            bindDatePickerWrapperClicks();
        });
        contentObserver.observe(content, { childList: true, subtree: true });
    }
}

function bootAdminDatePicker() {
    bindAdminDatePickerScrollFix();
    window.setTimeout(ensureDatePickersInitialized, 0);
    window.setTimeout(bindDatePickerWrapperClicks, 0);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAdminDatePicker);
} else {
    bootAdminDatePicker();
}
