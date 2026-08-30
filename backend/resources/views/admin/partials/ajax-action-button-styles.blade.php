@once
@push('styles')
<style>
    .admin-ajax-action-btn .admin-ajax-action-spinner {
        display: none;
        width: 1rem;
        height: 1rem;
        flex-shrink: 0;
        animation: admin-ajax-action-spin 0.7s linear infinite;
    }
    .admin-ajax-action-btn.is-loading .admin-ajax-action-spinner {
        display: block;
    }
    .admin-ajax-action-btn.is-loading .admin-ajax-action-idle-icon {
        display: none;
    }
    .admin-ajax-action-btn.is-success {
        cursor: pointer;
    }
    .admin-ajax-action-btn.is-success .admin-ajax-action-idle-icon {
        color: #22c55e;
    }
    .admin-ajax-action-btn.is-error .admin-ajax-action-idle-icon {
        color: #ef4444;
    }
    @keyframes admin-ajax-action-spin {
        to { transform: rotate(360deg); }
    }
</style>
@endpush
@endonce
