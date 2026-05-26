{{-- Metronic demo1-style footer (shared by admin + backend layouts) --}}
@php
    $adminFooterBrand = trim((string) \App\Models\GeneralSetting::get('admin_footer_brand', ''));
    if ($adminFooterBrand === '') {
        $adminFooterBrand = 'Nexa Skillmatching';
    }
@endphp
<footer class="kt-footer">
    <div class="kt-container-fixed">
        <div class="flex flex-col md:flex-row justify-center md:justify-between items-center gap-3 py-5">
            <div class="flex order-2 md:order-1 gap-2 font-normal text-sm">
                <span class="text-secondary-foreground">{{ date('Y') }}©</span>
                <span class="text-secondary-foreground">{{ $adminFooterBrand }}</span>
            </div>
        </div>
    </div>
</footer>
