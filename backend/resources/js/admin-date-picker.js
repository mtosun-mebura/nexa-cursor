/**
 * KT / Vanilla Calendar: popup aan input houden, omhoog openen bij weinig ruimte onder.
 */

const OPEN_CALENDAR_SELECTOR = '[data-vc="calendar"][data-vc-input]:not([data-vc-calendar-hidden])';
const FLOATING_CLASS = 'admin-date-picker-floating';
const POSITIONED_ATTR = 'data-admin-date-picker-positioned';
const READY_ATTR = 'data-admin-date-picker-ready';
const PATCHED_ATTR = 'data-admin-date-picker-patched';
const GAP_PX = 4;
const VIEWPORT_PADDING_PX = 8;
const Z_INDEX = 1060;
const CALENDAR_FALLBACK_HEIGHT = 336;
const CALENDAR_FALLBACK_WIDTH = 280;

let activeDatePickerInput = null;
let openCalendarToken = 0;
let calendarPointerActive = false;
let deferCalendarHide = false;

function getPositionMode(input) {
    return input.getAttribute('data-kt-date-picker-position-to-input') || 'left';
}

function findOpenCalendar() {
    return document.querySelector(OPEN_CALENDAR_SELECTOR);
}

function resolveActiveInput() {
    if (activeDatePickerInput && document.contains(activeDatePickerInput)) {
        return activeDatePickerInput;
    }

    return (
        document.querySelector('[data-kt-date-picker][data-vc-input-focus]') ||
        document.querySelector('[data-vc-input-focus]') ||
        (document.activeElement?.matches?.('[data-kt-date-picker]') ? document.activeElement : null)
    );
}

function resolveInputForCalendar(calendar) {
    const activeInput = resolveActiveInput();
    if (activeInput) {
        return activeInput;
    }

    if (!(calendar instanceof HTMLElement) || typeof window.KTDatePicker === 'undefined') {
        return null;
    }

    for (const input of document.querySelectorAll('[data-kt-date-picker]')) {
        const instance = window.KTDatePicker.getInstance(input);
        if (!instance) {
            continue;
        }

        const linkedCalendar = resolveCalendarElement(input, instance.getCalendar?.());

        if (linkedCalendar === calendar) {
            return input;
        }
    }

    return null;
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

function ensureCalendarInBody(calendar) {
    if (calendar.parentElement !== document.body) {
        document.body.appendChild(calendar);
    }
}

function clearFloatingCalendar(calendar) {
    if (!calendar) {
        return;
    }

    calendar.classList.remove(FLOATING_CLASS);
    calendar.removeAttribute('data-admin-date-picker-placement');
    calendar.removeAttribute(POSITIONED_ATTR);
    calendar.removeAttribute('data-admin-date-picker-staging');
    calendar.removeAttribute(READY_ATTR);
    calendar.style.removeProperty('position');
    calendar.style.removeProperty('top');
    calendar.style.removeProperty('left');
    calendar.style.removeProperty('right');
    calendar.style.removeProperty('bottom');
    calendar.style.removeProperty('margin');
    calendar.style.removeProperty('z-index');
    calendar.style.removeProperty('opacity');
    calendar.style.removeProperty('visibility');
}

function applyFloatingPosition(calendar, top, left, placement) {
    ensureCalendarInBody(calendar);
    calendar.classList.add(FLOATING_CLASS);
    calendar.setAttribute('data-admin-date-picker-placement', placement);
    calendar.setAttribute(POSITIONED_ATTR, '1');
    calendar.removeAttribute('data-admin-date-picker-staging');
    calendar.style.setProperty('position', 'fixed', 'important');
    calendar.style.setProperty('top', `${Math.round(top)}px`, 'important');
    calendar.style.setProperty('left', `${Math.round(left)}px`, 'important');
    calendar.style.setProperty('right', 'auto', 'important');
    calendar.style.setProperty('bottom', 'auto', 'important');
    calendar.style.setProperty('margin', '0', 'important');
    calendar.style.setProperty('z-index', String(Z_INDEX), 'important');
    calendar.style.removeProperty('opacity');
    calendar.style.removeProperty('visibility');
}

function markCalendarReady(calendar) {
    if (!(calendar instanceof HTMLElement)) {
        return;
    }

    calendar.removeAttribute('data-admin-date-picker-staging');
    calendar.style.removeProperty('opacity');
    calendar.style.removeProperty('visibility');
    calendar.setAttribute(READY_ATTR, '1');
}

function repositionOpenDatePicker(calendarOverride) {
    const calendar = calendarOverride ?? findOpenCalendar();
    if (!calendar) {
        return false;
    }

    const input = resolveInputForCalendar(calendar);
    if (!input) {
        return false;
    }

    activeDatePickerInput = input;

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

    return true;
}

function scheduleReposition() {
    if (calendarPointerActive) {
        return;
    }

    requestAnimationFrame(() => {
        repositionOpenDatePicker();
    });
}

function ensureCalendarVisible(calendar) {
    if (!(calendar instanceof HTMLElement)) {
        return;
    }

    const token = ++openCalendarToken;

    const attempt = (frame = 0) => {
        if (token !== openCalendarToken) {
            return;
        }

        if (!calendar.isConnected || calendar.hasAttribute('data-vc-calendar-hidden')) {
            return;
        }

        if (repositionOpenDatePicker(calendar)) {
            markCalendarReady(calendar);
            return;
        }

        if (frame < 6) {
            requestAnimationFrame(() => attempt(frame + 1));
        }
    };

    attempt(0);
}

function isCalendarOpen(calendar) {
    return calendar instanceof HTMLElement && !calendar.hasAttribute('data-vc-calendar-hidden');
}

function resolveCalendarElement(input, calendarApi) {
    const main = calendarApi?.context?.mainElement;

    if (main instanceof HTMLElement) {
        if (main.matches('[data-vc="calendar"]')) {
            return main;
        }

        const closest = main.closest('[data-vc="calendar"]');
        if (closest instanceof HTMLElement) {
            return closest;
        }
    }

    for (const candidate of document.querySelectorAll('[data-vc="calendar"][data-vc-input]')) {
        if (resolveInputForCalendar(candidate) === input) {
            return candidate;
        }
    }

    return null;
}

function getLinkedCalendar(input) {
    const instance = window.KTDatePicker?.getInstance?.(input);
    const calendarApi = instance?.getCalendar?.();
    const linkedCalendar = resolveCalendarElement(input, calendarApi);

    return { instance, calendarApi, linkedCalendar };
}

function forceResetClosedCalendarState(calendarApi, calendarEl) {
    const context = calendarApi?.context;
    if (!context) {
        return;
    }

    const calendarOpen =
        calendarEl instanceof HTMLElement && !calendarEl.hasAttribute('data-vc-calendar-hidden');

    if (calendarOpen) {
        return;
    }

    context.isShowInInputMode = false;

    if (context.cleanupHandlers?.length) {
        context.cleanupHandlers.forEach((cleanup) => cleanup());
        context.cleanupHandlers = [];
    }
}

function openDatePickerInput(input) {
    if (!(input instanceof HTMLElement)) {
        return;
    }

    activeDatePickerInput = input;

    const { calendarApi, linkedCalendar } = getLinkedCalendar(input);

    forceResetClosedCalendarState(calendarApi, linkedCalendar);

    if (linkedCalendar instanceof HTMLElement && isCalendarOpen(linkedCalendar)) {
        ensureCalendarVisible(linkedCalendar);
        return;
    }

    if (document.activeElement !== input) {
        input.focus({ preventScroll: true });
    }

    calendarApi?.show?.();

    const { linkedCalendar: openedCalendar } = getLinkedCalendar(input);
    if (openedCalendar instanceof HTMLElement && isCalendarOpen(openedCalendar)) {
        ensureCalendarVisible(openedCalendar);
    }
}

function prepareDatePickerInteraction(input) {
    if (!(input instanceof HTMLElement)) {
        return;
    }

    activeDatePickerInput = input;

    const { calendarApi, linkedCalendar } = getLinkedCalendar(input);
    forceResetClosedCalendarState(calendarApi, linkedCalendar);
}

function patchAdminDatePickerInstance(input) {
    if (!(input instanceof HTMLElement)) {
        return;
    }

    const instance = window.KTDatePicker?.getInstance?.(input);
    const calendar = instance?.getCalendar?.();
    if (!calendar?.hide || calendar.__adminHidePatched) {
        return;
    }

    input.setAttribute(PATCHED_ATTR, '1');
    calendar.__adminHidePatched = true;

    const originalShow = calendar.show?.bind(calendar);
    if (originalShow && !calendar.__adminShowPatched) {
        calendar.__adminShowPatched = true;
        calendar.show = function adminDatePickerShow(...args) {
            forceResetClosedCalendarState(calendar, resolveCalendarElement(input, calendar));
            return originalShow(...args);
        };
    }

    const originalHide = calendar.hide.bind(calendar);
    calendar.hide = function adminDatePickerHide(...args) {
        if (deferCalendarHide) {
            window.setTimeout(() => {
                deferCalendarHide = false;
                originalHide(...args);
            }, 0);
            return;
        }

        return originalHide(...args);
    };
}

function patchAllAdminDatePickers() {
    document.querySelectorAll('[data-kt-date-picker]').forEach(patchAdminDatePickerInstance);
}

function ensureDatePickersInitialized() {
    if (typeof window.KTDatePicker !== 'undefined' && typeof window.KTDatePicker.createInstances === 'function') {
        window.KTDatePicker.createInstances();
    }

    patchAllAdminDatePickers();
    bindDatePickerInputOpens();
}

function bindDatePickerDeferHideOnDayClick() {
    document.addEventListener(
        'pointerdown',
        (event) => {
            if (event.button !== 0) {
                return;
            }

            const dayTarget = event.target.closest?.('[data-vc-date], .vc-date__btn');
            if (!dayTarget) {
                return;
            }

            const calendar = dayTarget.closest('[data-vc="calendar"][data-vc-input]');
            const input = calendar ? resolveInputForCalendar(calendar) : null;
            if (!input) {
                return;
            }

            activeDatePickerInput = input;
            deferCalendarHide = true;
        },
        true,
    );
}

function bindDatePickerOutsideClickGuard() {
    document.addEventListener(
        'click',
        (event) => {
            if (event.button !== 0) {
                return;
            }

            const wrapper = event.target.closest?.('.kt-input');
            if (!wrapper) {
                return;
            }

            const input = wrapper.querySelector('[data-kt-date-picker]');
            if (!input) {
                return;
            }

            const { linkedCalendar } = getLinkedCalendar(input);

            if (!linkedCalendar || linkedCalendar.hasAttribute('data-vc-calendar-hidden')) {
                return;
            }

            if (event.target === input || linkedCalendar.contains(event.target)) {
                return;
            }

            event.stopImmediatePropagation();
        },
        true,
    );
}

function bindDatePickerInputOpens() {
    document.querySelectorAll('[data-kt-date-picker]').forEach((input) => {
        if (input.dataset.adminDatePickerInputOpenBound === '1') {
            return;
        }

        input.dataset.adminDatePickerInputOpenBound = '1';

        input.addEventListener(
            'pointerdown',
            (event) => {
                if (event.button !== 0) {
                    return;
                }

                prepareDatePickerInteraction(input);
            },
            true,
        );

        input.addEventListener(
            'click',
            (event) => {
                if (event.button !== 0) {
                    return;
                }

                prepareDatePickerInteraction(input);
            },
            true,
        );
    });
}

function bindDatePickerWrapperClicks() {
    document.querySelectorAll('.kt-input').forEach((wrapper) => {
        const input = wrapper.querySelector('[data-kt-date-picker]');
        if (!input || wrapper.dataset.adminDatePickerClickBound === '1') {
            return;
        }

        wrapper.dataset.adminDatePickerClickBound = '1';
        wrapper.style.cursor = 'pointer';

        wrapper.addEventListener(
            'pointerdown',
            (event) => {
                if (event.button !== 0) {
                    return;
                }

                if (event.target.closest('[data-vc="calendar"]')) {
                    return;
                }

                prepareDatePickerInteraction(input);
            },
            true,
        );

        wrapper.addEventListener(
            'click',
            (event) => {
                if (event.button !== 0) {
                    return;
                }

                if (event.target.closest('[data-vc="calendar"]')) {
                    return;
                }

                const { linkedCalendar } = getLinkedCalendar(input);

                if (linkedCalendar instanceof HTMLElement && isCalendarOpen(linkedCalendar)) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();
                openDatePickerInput(input);
            },
            true,
        );
    });
}

function bindAdminDatePickerScrollFix() {
    if (document.body.dataset.adminDatePickerScrollFix === '1') {
        return;
    }
    document.body.dataset.adminDatePickerScrollFix = '1';

    bindDatePickerOutsideClickGuard();
    bindDatePickerDeferHideOnDayClick();
    ensureDatePickersInitialized();
    bindDatePickerWrapperClicks();

    document.addEventListener(
        'pointerdown',
        (event) => {
            const input = event.target.closest?.('[data-kt-date-picker]');
            if (input) {
                activeDatePickerInput = input;
            }

            calendarPointerActive = !!event.target.closest?.('[data-vc="calendar"]');
        },
        true,
    );

    document.addEventListener(
        'pointerup',
        () => {
            window.setTimeout(() => {
                calendarPointerActive = false;
            }, 0);
        },
        true,
    );

    window.addEventListener('scroll', scheduleReposition, true);
    window.addEventListener('resize', scheduleReposition);

    const observer = new MutationObserver((mutations) => {
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

                    const linkedInput = resolveInputForCalendar(target);
                    if (linkedInput) {
                        const { calendarApi } = getLinkedCalendar(linkedInput);
                        forceResetClosedCalendarState(calendarApi, target);
                    }
                } else {
                    ensureCalendarVisible(target);
                }
            }
        }

        if (closed && !findOpenCalendar()) {
            openCalendarToken += 1;
            activeDatePickerInput = null;
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
            bindDatePickerInputOpens();
        });
        contentObserver.observe(content, { childList: true, subtree: true });
    }
}

function bootAdminDatePicker() {
    bindAdminDatePickerScrollFix();
    window.setTimeout(ensureDatePickersInitialized, 0);
    window.setTimeout(bindDatePickerWrapperClicks, 0);
    window.setTimeout(bindDatePickerInputOpens, 0);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAdminDatePicker);
} else {
    bootAdminDatePicker();
}
