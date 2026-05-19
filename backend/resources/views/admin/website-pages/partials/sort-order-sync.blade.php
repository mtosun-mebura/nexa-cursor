{{-- Houd _sort_order (bovenaan formulier) gelijk aan #sort_order — overleeft max_input_vars. --}}
<script>
(function() {
    function syncWebsitePageSortOrderFallback() {
        var inp = document.getElementById('sort_order');
        var fb = document.getElementById('sort-order-fallback-input');
        if (!inp || !fb) return;
        var v = inp.value;
        fb.value = (v === '' || v === null || typeof v === 'undefined') ? '0' : String(v);
    }
    window.syncWebsitePageSortOrderFallback = syncWebsitePageSortOrderFallback;
    var sortOrderInp = document.getElementById('sort_order');
    if (sortOrderInp) {
        syncWebsitePageSortOrderFallback();
        sortOrderInp.addEventListener('input', syncWebsitePageSortOrderFallback);
        sortOrderInp.addEventListener('change', syncWebsitePageSortOrderFallback);
    }
})();
</script>
