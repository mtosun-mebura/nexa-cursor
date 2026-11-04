/**
 * Nederlandse kenteken-sidecodes (zelfde logica als mobile-app/src/dutchPlate.ts).
 * L = letter, D = cijfer; streepjes op groepsgrenzen.
 */
(function (global) {
    'use strict';

    var FORMATS = [
        'LL-DD-DD',
        'DD-DD-LL',
        'DD-LL-DD',
        'LL-DD-LL',
        'LL-LL-DD',
        'DD-LL-LL',
        'DD-LLL-D',
        'D-LLL-DD',
        'LL-DDD-L',
        'L-DDD-LL',
        'LLL-DD-L',
        'L-DD-LLL',
        'D-LL-DDD',
        'DDD-LL-D',
    ];

    function matchesPrefix(format, chars) {
        var slots = format.replace(/-/g, '');
        if (chars.length > slots.length) return false;
        for (var i = 0; i < chars.length; i++) {
            var digit = chars[i] >= '0' && chars[i] <= '9';
            if (slots[i] === 'D' && !digit) return false;
            if (slots[i] === 'L' && digit) return false;
        }
        return true;
    }

    function dashBefore(format) {
        var dashes = {};
        var index = 0;
        for (var i = 0; i < format.length; i++) {
            if (format[i] === '-') dashes[index] = true;
            else index += 1;
        }
        return dashes;
    }

    /** Zet streepjes in een Nederlands kenteken op basis van de ingevoerde tekens. */
    function formatDutchPlate(input) {
        var chars = String(input || '')
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '')
            .slice(0, 6);
        if (!chars) return '';
        var matches = FORMATS.filter(function (format) {
            return matchesPrefix(format, chars);
        });
        if (matches.length === 0) return chars;
        var out = '';
        for (var i = 0; i < chars.length; i++) {
            if (i > 0 && matches.every(function (format) {
                return dashBefore(format)[i];
            })) {
                out += '-';
            }
            out += chars[i];
        }
        return out;
    }

    /**
     * Zelfde normalisatie als formatDutchPlate. Een vol kenteken zonder sidecode
     * (bijv. MAMMBO) krijgt alsnog streepjes per twee tekens, alleen voor weergave.
     */
    function formatDutchPlateDisplay(input) {
        var formatted = formatDutchPlate(input);
        if (!formatted || formatted.indexOf('-') !== -1) return formatted;
        if (formatted.length === 6) {
            return formatted.slice(0, 2) + '-' + formatted.slice(2, 4) + '-' + formatted.slice(4, 6);
        }
        return formatted;
    }

    function bindDutchPlateInputs(root) {
        var scope = root || document;
        var inputs = scope.querySelectorAll('input[data-dutch-plate="true"]');
        inputs.forEach(function (input) {
            if (input.dataset.dutchPlateBound === '1') return;
            input.dataset.dutchPlateBound = '1';

            function applyFormat() {
                var next = formatDutchPlate(input.value);
                if (input.value !== next) {
                    input.value = next;
                }
            }

            input.addEventListener('input', applyFormat);
            input.addEventListener('blur', function () {
                var next = formatDutchPlateDisplay(input.value);
                if (input.value !== next) {
                    input.value = next;
                }
            });

            if (input.value) {
                input.value = formatDutchPlateDisplay(input.value);
            }
        });
    }

    global.NexaDutchPlate = {
        format: formatDutchPlate,
        formatDisplay: formatDutchPlateDisplay,
        bind: bindDutchPlateInputs,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            bindDutchPlateInputs(document);
        });
    } else {
        bindDutchPlateInputs(document);
    }
})(typeof window !== 'undefined' ? window : this);
