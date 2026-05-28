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
</style>
@endpush
@push('scripts')
<script>
(function () {
    function setSettingsSectionCollapsed(card, collapsed) {
        card.classList.toggle('settings-collapsible-card--collapsed', collapsed);
        var btn = card.querySelector('.settings-collapsible-toggle');
        if (btn) {
            btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }
    }

    function initSettingsCollapsible(root) {
        if (!root) {
            return;
        }
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

    function boot() {
        initSettingsCollapsible(document.getElementById('settings-collapsible-root'));
        initSettingsCollapsible(document.getElementById('general-settings-collapsible-root'));
        initSettingsCollapsible(document.getElementById('dispatch-settings-collapsible-root'));

        var hash = (window.location.hash || '').replace(/^#/, '');
        if (hash) {
            openSettingsSectionById(hash);
        }
        var params = new URLSearchParams(window.location.search);
        if (params.has('saved') || params.has('updated') || params.has('created')) {
            openSettingsSectionById(hash || 'tenant-sync');
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
