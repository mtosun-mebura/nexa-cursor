@include('frontend.website.partials.cms-section-home', [
    'themeSlugForHome' => 'landwind',
    'wrapperClass' => 'landwind-home',
    'heroVariant' => 'split',
    'defaultHeroImage' => asset('frontend-themes/landwind/images/hero.png'),
    'featuresCardClass' => 'rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-8 min-h-[16rem] shadow-lg shadow-gray-100 dark:shadow-none',
    'iconWrapClass' => 'flex items-center justify-center w-12 h-12 rounded-lg text-white shrink-0',
])
