@if(!empty($seoTracking['site_verification']))
    <meta name="google-site-verification" content="{{ $seoTracking['site_verification'] }}">
@endif
@if(!empty($seoTracking['tag_manager_id']))
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{{ $seoTracking['tag_manager_id'] }}');</script>
@endif
@if(!empty($seoTracking['analytics_id']) && str_starts_with($seoTracking['analytics_id'], 'G-'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $seoTracking['analytics_id'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($seoTracking['analytics_id']));
    </script>
@endif
