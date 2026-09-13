{{-- Bescheiden tenantlogo middenboven, onder notch / selfiecamera. --}}
<style>
    .tenant-logo-bar {
        display: none;
        flex-shrink: 0;
        justify-content: center;
        align-items: flex-end;
        background: var(--chrome);
        padding-left: 1rem;
        padding-right: 1rem;
        padding-bottom: 0.3rem;
        padding-top: calc(var(--safe-top) + 0.35rem);
    }
    .tenant-logo-bar.is-visible {
        display: flex;
    }
    /* Zelfde middenas als .driver-app-header (1fr / auto / 1fr), zodat logo en tellers boven elkaar staan */
    #app:has(#screen-dispatch.is-active) .tenant-logo-bar.is-visible {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: end;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    #app:has(#screen-dispatch.is-active) .tenant-logo-bar img {
        grid-column: 2;
        justify-self: center;
    }
    @media (max-width: 48rem) {
        .tenant-logo-bar {
            /* Notch / Dynamic Island: env() is in Safari soms 0 */
            padding-top: max(calc(var(--safe-top) + 0.35rem), 3.75rem);
        }
    }
    .tenant-logo-bar img {
        display: block;
        height: 1.75rem;
        width: auto;
        max-width: 9.5rem;
        object-fit: contain;
        margin-left: auto;
        margin-right: auto;
    }
    #app:has(.tenant-logo-bar.is-visible) .dispatch-top,
    #app:has(.tenant-logo-bar.is-visible) .home-top {
        padding-top: 0.45rem;
    }
    #app:has(.tenant-logo-bar.is-visible) #screen-login.screen {
        padding-top: 0.85rem;
    }
</style>
<div class="tenant-logo-bar" id="tenant-logo-bar" hidden>
    <img id="tenant-logo" alt="" decoding="async">
</div>
<script>
(function () {
    var bar = document.getElementById('tenant-logo-bar');
    var img = document.getElementById('tenant-logo');
    if (!bar || !img) {
        return;
    }

    img.addEventListener('error', function () {
        img.removeAttribute('src');
        img.alt = '';
        bar.hidden = true;
        bar.classList.remove('is-visible');
        if (window.nexaPwaSyncThemeToggleTop) {
            window.nexaPwaSyncThemeToggleTop();
        }
    });

    window.nexaApplyTenantLogo = function (user) {
        window.__nexaTenantLogoUser = user || null;
        var theme = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
        var url = '';
        if (user) {
            url = theme === 'light'
                ? String(user.company_logo_url || user.company_logo_dark_url || '')
                : String(user.company_logo_dark_url || user.company_logo_url || '');
        }
        if (!url) {
            img.removeAttribute('src');
            img.alt = '';
            bar.hidden = true;
            bar.classList.remove('is-visible');
        } else {
            img.alt = (user && user.company_name) ? String(user.company_name) : 'Logo';
            if (img.getAttribute('src') !== url) {
                img.src = url;
            }
            bar.hidden = false;
            bar.classList.add('is-visible');
        }
        if (window.nexaPwaSyncThemeToggleTop) {
            window.nexaPwaSyncThemeToggleTop();
        }
    };

    window.addEventListener('nexa-pwa-theme-change', function () {
        if (typeof window.nexaApplyTenantLogo === 'function') {
            window.nexaApplyTenantLogo(window.__nexaTenantLogoUser || null);
        }
    });
})();
</script>
