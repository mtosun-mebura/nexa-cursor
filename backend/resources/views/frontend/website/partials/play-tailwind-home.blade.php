@include('frontend.website.partials.cms-section-home', [
    'themeSlugForHome' => 'play-tailwind',
    'wrapperClass' => 'play-tailwind-home',
    'heroVariant' => 'overlay',
    'defaultHeroImage' => asset('frontend-themes/play-tailwind/assets/images/hero/hero-image.jpg'),
    'featuresCardClass' => 'rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-10 min-h-[18rem] shadow-sm',
    'iconWrapClass' => 'flex items-center justify-center w-14 h-14 rounded text-white shrink-0',
])
