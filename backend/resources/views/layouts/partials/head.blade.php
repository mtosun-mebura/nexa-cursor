<title>Metronic - Tailwind CSS</title>
<meta charset="utf-8" />
<meta content="follow, index" name="robots" />
<link href="{{ url(request()->path()) }}" rel="canonical" />
<meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport" />
<meta content="" name="description" />
<meta content="@keenthemes" name="twitter:site" />
<meta content="@keenthemes" name="twitter:creator" />
<meta content="summary_large_image" name="twitter:card" />
<meta content="Metronic - Tailwind CSS " name="twitter:title" />
<meta content="" name="twitter:description" />
<meta content="{{ asset('assets/media/app/og-image.png') }}" name="twitter:image" />
<meta content="{{ url(request()->path()) }}" property="og:url" />
<meta content="en_US" property="og:locale" />
<meta content="website" property="og:type" />
<meta content="@keenthemes" property="og:site_name" />
<meta content="Metronic - Tailwind CSS " property="og:title" />
<meta content="" property="og:description" />
<meta content="{{ asset('assets/media/app/og-image.png') }}" property="og:image" />
<link href="{{ asset('images/nexa-x-logo.png') }}" rel="apple-touch-icon" sizes="180x180" />
<link href="{{ asset('images/nexa-x-logo.png') }}" rel="icon" sizes="32x32" type="image/png" />
<link href="{{ asset('images/nexa-x-logo.png') }}" rel="icon" sizes="16x16" type="image/png" />
<link href="{{ asset('images/nexa-x-logo.png') }}" rel="shortcut icon" type="image/png" />
<link href="{{ asset('images/nexa-x-logo.png') }}" rel="icon" type="image/png" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
<link href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/css/badge-danger-fix.css') }}" rel="stylesheet" />

<!-- Global custom styles -->
<style>
    /* Make textareas with 4 rows actually visible and add padding-top */
    textarea.kt-input[rows="4"] {
        min-height: 100px !important;
        padding-top: 0.25rem !important;
    }
    
    /* Hide scrollbar corner (white line) in textareas when scrollbar is present */
    textarea.kt-input::-webkit-scrollbar-corner {
        background-color: transparent;
        background: transparent;
    }
    
    textarea.kt-input {
        scrollbar-width: thin;
        scrollbar-color: var(--color-input) transparent;
    }
    
    /* Ensure textareas are 100% width (not 50% like regular inputs) */
    @media (min-width: 1024px) {
        textarea.kt-input {
            width: 100% !important;
        }
    }
    
    /* Select boxes on create/edit pages should have auto width */
    /* Target all forms except filter forms on index pages */
    form:not(#filters-form):not(#search-form) select.kt-select,
    form:not(#filters-form):not(#search-form) select.kt-input {
        width: auto !important;
    }
    
    /* Also target forms with data-validate (create/edit forms) */
    form[data-validate="true"] select.kt-select,
    form[data-validate="true"] select.kt-input {
        width: auto !important;
    }
    
    /* Vacancy pages */
    .vacancy-create select.kt-select,
    .vacancy-create select.kt-input,
    .vacancy-edit select.kt-select,
    .vacancy-edit select.kt-input {
        width: auto !important;
    }
</style>

<!-- Stack for page-specific styles -->
@stack('styles')
