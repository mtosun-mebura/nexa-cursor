@if(! empty($adminHeaderFlashSuccess))
    <div class="admin-header-toast kt-alert kt-alert-success" role="status" aria-live="polite">
        <i class="ki-filled ki-check-circle me-2"></i>
        {{ $adminHeaderFlashSuccess }}
    </div>
@endif
@if(! empty($adminHeaderFlashError))
    <div class="admin-header-toast kt-alert kt-alert-danger" role="alert">
        <i class="ki-filled ki-information me-2"></i>
        {{ $adminHeaderFlashError }}
    </div>
@endif
@if(! empty($adminHeaderFlashWarning))
    <div class="admin-header-toast kt-alert kt-alert-warning" role="status">
        <i class="ki-filled ki-information me-2"></i>
        {{ $adminHeaderFlashWarning }}
    </div>
@endif
