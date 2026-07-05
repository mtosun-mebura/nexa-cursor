@once
@push('styles')
<style>
    .settings-collapsible-chevron .settings-collapsible-icon-up {
        display: none;
    }
    .settings-collapsible-chevron .settings-collapsible-icon-down {
        display: inline-block;
    }
    .settings-collapsible-card--collapsed .settings-collapsible-body {
        display: none !important;
    }
    :is(.settings-collapsible-card, .settings-collapsible-section).settings-collapsible-card--collapsed .settings-collapsible-icon-up {
        display: none !important;
    }
    :is(.settings-collapsible-card, .settings-collapsible-section).settings-collapsible-card--collapsed .settings-collapsible-icon-down {
        display: inline-block !important;
    }
    :is(.settings-collapsible-card, .settings-collapsible-section):not(.settings-collapsible-card--collapsed) .settings-collapsible-icon-down {
        display: none !important;
    }
    :is(.settings-collapsible-card, .settings-collapsible-section):not(.settings-collapsible-card--collapsed) .settings-collapsible-icon-up {
        display: inline-block !important;
    }
    .settings-collapsible-toggle:hover .kt-card-title {
        color: var(--color-primary, #3b82f6);
    }
    .settings-collapsible-header {
        cursor: pointer;
    }
    .settings-collapsible-card--collapsed > .settings-collapsible-header,
    .settings-collapsible-section.settings-collapsible-card--collapsed > .settings-collapsible-header {
        border-bottom-width: 0 !important;
        border-bottom: none !important;
    }
    .settings-collapsible-section + .settings-collapsible-section > .settings-collapsible-header {
        border-top-width: 1px;
        border-top-style: solid;
        border-color: var(--border);
    }
    #tenant-sync select.tenant-sync-company-select {
        width: auto !important;
        max-width: min(100%, 36rem) !important;
    }
    .tenant-sync-progress {
        width: 100%;
        max-width: 56rem;
        font-size: 0.75rem;
        line-height: 1.375;
    }
    .tenant-sync-progress .tenant-sync-progress-heading {
        font-size: 0.8125rem;
    }
    .tenant-sync-progress .font-mono {
        min-width: 9rem;
    }
    .tenant-sync-progress-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .tenant-sync-progress-item {
        opacity: 0;
        animation: tenant-sync-fade-in 0.35s ease forwards;
    }
    @keyframes tenant-sync-fade-in {
        from {
            opacity: 0;
            transform: translateY(4px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endpush
@push('scripts')
<script>
(function () {
    var STORAGE_KEY = 'admin-settings-collapsible-open';
    var ROOT_IDS = [
        'settings-collapsible-root',
        'general-settings-collapsible-root',
        'dispatch-settings-collapsible-root',
    ];

    function sectionStorageKey(card, root) {
        if (card.id) {
            return card.id;
        }
        var cards = root.querySelectorAll('.settings-collapsible-card, .settings-collapsible-section');
        for (var i = 0; i < cards.length; i += 1) {
            if (cards[i] === card) {
                return '__index_' + i;
            }
        }
        return null;
    }

    function readStoredOpenKeys(root) {
        if (!root || !root.id) {
            return [];
        }
        try {
            var all = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '{}');
            var openKeys = all[root.id];
            return Array.isArray(openKeys) ? openKeys : [];
        } catch (e) {
            return [];
        }
    }

    function persistCollapsibleState(root) {
        if (!root || !root.id) {
            return;
        }
        var openKeys = [];
        root.querySelectorAll('.settings-collapsible-card, .settings-collapsible-section').forEach(function (card) {
            if (card.classList.contains('settings-collapsible-card--collapsed')) {
                return;
            }
            var key = sectionStorageKey(card, root);
            if (key) {
                openKeys.push(key);
            }
        });
        try {
            var all = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '{}');
            all[root.id] = openKeys;
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(all));
        } catch (e) {
            // sessionStorage kan geblokkeerd zijn
        }
    }

    function restoreCollapsibleState(root) {
        if (!root || !root.id) {
            return;
        }
        var openKeys = readStoredOpenKeys(root);
        if (!openKeys.length) {
            return;
        }
        var cards = root.querySelectorAll('.settings-collapsible-card, .settings-collapsible-section');
        openKeys.forEach(function (key) {
            var card = null;
            if (key.indexOf('__index_') === 0) {
                var index = parseInt(key.replace('__index_', ''), 10);
                if (!isNaN(index) && cards[index]) {
                    card = cards[index];
                }
            } else {
                card = document.getElementById(key);
            }
            if (card && root.contains(card)) {
                setSettingsSectionCollapsed(card, false, { persist: false });
            }
        });
    }

    function findCollapsibleRoot(node) {
        if (!node) {
            return null;
        }
        for (var i = 0; i < ROOT_IDS.length; i += 1) {
            var root = document.getElementById(ROOT_IDS[i]);
            if (root && root.contains(node)) {
                return root;
            }
        }
        return null;
    }

    function setSettingsSectionCollapsed(card, collapsed, options) {
        options = options || {};
        card.classList.toggle('settings-collapsible-card--collapsed', collapsed);
        var btn = card.querySelector('.settings-collapsible-toggle');
        if (btn) {
            btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }
        if (options.persist !== false) {
            var root = findCollapsibleRoot(card);
            if (root) {
                persistCollapsibleState(root);
            }
        }
    }

    function initSettingsCollapsible(root) {
        if (!root) {
            return;
        }
        restoreCollapsibleState(root);
        root.querySelectorAll('.settings-collapsible-card, .settings-collapsible-section').forEach(function (card) {
            var id = card.id;
            if (id && window.location.hash === '#' + id) {
                setSettingsSectionCollapsed(card, false);
            }
        });
        root.addEventListener('click', function (e) {
            var btn = e.target.closest('.settings-collapsible-toggle');
            if (!btn || !root.contains(btn)) {
                return;
            }
            var card = btn.closest('.settings-collapsible-card, .settings-collapsible-section');
            if (!card) {
                return;
            }
            e.preventDefault();
            setSettingsSectionCollapsed(card, !card.classList.contains('settings-collapsible-card--collapsed'));
        });
    }

    function openSettingsSectionById(sectionId) {
        if (!sectionId) {
            return;
        }
        var card = document.getElementById(sectionId);
        if (card) {
            setSettingsSectionCollapsed(card, false);
        }
    }

    function bindCollapsiblePersistOnSubmit() {
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || !form.tagName || form.tagName.toLowerCase() !== 'form') {
                return;
            }
            var method = (form.getAttribute('method') || 'get').toLowerCase();
            if (method !== 'post') {
                return;
            }
            ROOT_IDS.forEach(function (rootId) {
                var root = document.getElementById(rootId);
                if (root && root.contains(form)) {
                    persistCollapsibleState(root);
                }
            });
        }, true);
    }

    function boot() {
        initSettingsCollapsible(document.getElementById('settings-collapsible-root'));
        initSettingsCollapsible(document.getElementById('general-settings-collapsible-root'));
        initSettingsCollapsible(document.getElementById('dispatch-settings-collapsible-root'));
        bindCollapsiblePersistOnSubmit();

        var hash = (window.location.hash || '').replace(/^#/, '');
        if (hash) {
            openSettingsSectionById(hash);
        }
        if (document.querySelector('#tenant-sync-settings-form [role="alert"]')) {
            openSettingsSectionById('tenant-sync');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
@endpush
@endonce
