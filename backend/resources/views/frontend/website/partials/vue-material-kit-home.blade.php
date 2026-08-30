@include('frontend.website.partials.cms-section-home', [
    'themeSlugForHome' => 'vue-material-kit',
    'wrapperClass' => 'vue-material-kit-home',
    'heroVariant' => 'material',
    'defaultHeroImage' => asset('frontend-themes/vue-material-kit/src/assets/img/vue-mk-header.jpg'),
    'featuresCardClass' => 'rounded-3xl bg-white dark:bg-gray-800 p-10 min-h-[18rem] shadow-xl shadow-gray-200/80 dark:shadow-none',
    'iconWrapClass' => 'flex items-center justify-center w-16 h-16 rounded-xl text-white shrink-0 shadow-lg',
    'ctaSectionStyle' => 'background-image: linear-gradient(195deg, var(--theme-primary), #1e293b);',
])
