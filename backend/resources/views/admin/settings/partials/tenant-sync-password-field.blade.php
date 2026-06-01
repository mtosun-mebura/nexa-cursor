@props([
    'name',
    'id',
    'clearFlagName',
    'label',
    'inputClass' => 'kt-input w-full text-sm',
    'hasStored' => false,
    'placeholder' => '',
    'hint' => null,
])
<div class="tenant-sync-password-field" data-has-stored="{{ $hasStored ? '1' : '0' }}">
    <label for="{{ $id }}" class="text-sm text-secondary-foreground block mb-1">{{ $label }}</label>
    <div class="relative">
        <input type="password"
               name="{{ $name }}"
               id="{{ $id }}"
               class="{{ $inputClass }} pe-16 tenant-sync-password-input"
               autocomplete="new-password"
               data-no-password-toggle
               placeholder="{{ $placeholder }}">
        <input type="hidden" name="{{ $clearFlagName }}" id="{{ $clearFlagName }}" value="0">
        <button type="button"
                class="tenant-sync-password-reveal kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost absolute end-1 top-1/2 z-[2] -translate-y-1/2"
                data-password-input="{{ $id }}"
                aria-label="Wachtwoord tonen"
                title="Wachtwoord tonen"
                tabindex="-1">
            <i class="ki-filled ki-eye text-sm tenant-sync-eye-show"></i>
            <i class="ki-filled ki-eye-slash text-sm tenant-sync-eye-hide hidden"></i>
        </button>
        <button type="button"
                class="tenant-sync-password-clear kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost absolute end-9 top-1/2 z-[2] -translate-y-1/2 hidden"
                data-password-input="{{ $id }}"
                data-clear-flag="{{ $clearFlagName }}"
                aria-label="Wachtwoord wissen"
                title="Wachtwoord wissen"
                tabindex="-1">
            <i class="ki-filled ki-cross text-sm"></i>
        </button>
    </div>
    @once
        <script>
        (function () {
            if (window.__tenantSyncPwRevealInit) return;
            window.__tenantSyncPwRevealInit = true;
            function wire() {
                var btns = document.querySelectorAll('.tenant-sync-password-reveal');
                for (var i = 0; i < btns.length; i++) {
                    (function (btn) {
                        if (btn.dataset.wired === '1') return;
                        btn.dataset.wired = '1';
                        var input = document.getElementById(btn.getAttribute('data-password-input'));
                        if (!input) return;
                        var show = btn.querySelector('.tenant-sync-eye-show');
                        var hide = btn.querySelector('.tenant-sync-eye-hide');
                        btn.addEventListener('click', function () {
                            var reveal = input.type === 'password';
                            input.type = reveal ? 'text' : 'password';
                            if (show) show.classList.toggle('hidden', reveal);
                            if (hide) hide.classList.toggle('hidden', !reveal);
                            btn.setAttribute('aria-label', reveal ? 'Wachtwoord verbergen' : 'Wachtwoord tonen');
                            btn.setAttribute('title', reveal ? 'Wachtwoord verbergen' : 'Wachtwoord tonen');
                        });
                    })(btns[i]);
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wire);
            } else {
                wire();
            }
        })();
        </script>
    @endonce
    @if ($hint)
        <p class="text-xs text-muted-foreground mt-1">{!! $hint !!}</p>
        <p class="text-xs text-muted-foreground mt-0.5">Typ het wachtwoord normaal (bijv. <code class="text-xs">Welkom01!</code>). Na tab uit het veld wordt het optioneel URL-encoded getoond; bij opslaan wordt het echte wachtwoord gebruikt.</p>
    @endif
</div>
