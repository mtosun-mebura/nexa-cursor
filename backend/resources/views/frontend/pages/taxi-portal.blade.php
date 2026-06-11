@extends('frontend.layouts.app')

@section('title', ($branding['dashboard_link_label'] ?? 'Mijn Taxi').' - '.($branding['site_name'] ?? 'Nexa'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('metronic-v9.4.13/demo1/assets/vendors/keenicons/styles.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('metronic-v9.4.13/demo1/assets/css/styles.css') }}">
    <link href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet"/>
    <style>
        /* Mijn gegevens: label boven veld, vlakke achtergrond gelijk aan kaart */
        #taxi-portal-app .taxi-portal-profile-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.25;
            color: var(--muted-foreground);
        }

        #taxi-portal-app .taxi-portal-profile-input {
            display: block;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            padding: 0.625rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25;
            color: var(--foreground);
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: none;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        #taxi-portal-app .taxi-portal-profile-input::placeholder {
            color: var(--muted-foreground);
            opacity: 0.7;
        }

        #taxi-portal-app .taxi-portal-profile-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgb(59 130 246 / 0.2);
        }

        .dark #taxi-portal-app .taxi-portal-profile-input,
        html.dark #taxi-portal-app .taxi-portal-profile-input {
            color: #f9fafb;
            background-color: #111827;
            border-color: #4b5563;
        }

        .dark #taxi-portal-app .taxi-portal-profile-input:focus,
        html.dark #taxi-portal-app .taxi-portal-profile-input:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgb(96 165 250 / 0.25);
        }

        #taxi-portal-app .taxi-portal-chart-period-select {
            appearance: auto;
            font-size: 0.875rem;
            line-height: 1.25;
            color: var(--foreground);
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.375rem 0.75rem;
            min-width: 9.5rem;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        #taxi-portal-app .taxi-portal-chart-period-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgb(59 130 246 / 0.2);
        }

        #taxi-portal-app .taxi-portal-cost-chart {
            background-color: #ffffff;
        }

        #taxi-portal-app .taxi-portal-cost-chart .apexcharts-canvas,
        #taxi-portal-app .taxi-portal-cost-chart .apexcharts-svg {
            background: transparent !important;
        }

        .dark #taxi-portal-app .taxi-portal-cost-chart,
        html.dark #taxi-portal-app .taxi-portal-cost-chart {
            background-color: #111827;
        }

        .dark #taxi-portal-app .taxi-portal-chart-period-select,
        html.dark #taxi-portal-app .taxi-portal-chart-period-select {
            color: #f9fafb;
            background-color: #111827;
            border-color: #4b5563;
            color-scheme: dark;
        }

        .dark #taxi-portal-app .taxi-portal-chart-period-select:focus,
        html.dark #taxi-portal-app .taxi-portal-chart-period-select:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgb(96 165 250 / 0.25);
        }

        .dark #taxi-portal-app .taxi-portal-chart-period-select option,
        html.dark #taxi-portal-app .taxi-portal-chart-period-select option {
            color: #f9fafb;
            background-color: #111827;
        }

        @media (max-width: 767px) {
            #taxi-portal-app .taxi-portal-profile-form {
                grid-template-columns: 1fr !important;
            }
        }

        #taxi-portal-app #taxi_portal_sidebar,
        #taxi-portal-app #sidebar_content {
            max-width: var(--portal-sidebar-w, 188px);
            overflow-x: hidden;
        }

        footer.taxi-portal-site-footer {
            --portal-footer-h: 45px;
        }

        footer.taxi-portal-site-footer .container-custom {
            max-width: none;
        }

        /* Sidebar: rechterrand doorlopend tot onderkant viewport (footer copyright is volle breedte) */
        @media (min-width: 1024px) {
            #taxi-portal-app #taxi_portal_sidebar.taxi-portal-sidebar {
                position: fixed !important;
                top: calc(4rem + 1px) !important;
                bottom: 0 !important;
                left: 0 !important;
                right: auto !important;
                width: var(--portal-sidebar-w, 188px) !important;
                height: auto !important;
                min-height: calc(100vh - 4rem - 1px) !important;
                inset-inline-end: auto !important;
                transform: none !important;
                translate: none !important;
                z-index: 20;
            }
        }

        @media (min-width: 768px) {
            #taxi-portal-app #taxi_portal_sidebar.taxi-portal-sidebar {
                top: calc(5rem + 1px) !important;
                min-height: calc(100vh - 5rem - 1px) !important;
            }
        }

        #taxi-portal-app #sidebar_content {
            flex: 1 1 auto;
            min-height: 100%;
            align-self: stretch;
        }

        #taxi-portal-app #sidebar_menu .kt-menu-link {
            max-width: 100%;
        }

        #taxi-portal-app th.taxi-portal-th-sort {
            cursor: pointer;
            user-select: none;
            vertical-align: middle;
        }

        #taxi-portal-app th.taxi-portal-th-sort:focus-visible {
            outline: 2px solid var(--color-brand, #3b82f6);
            outline-offset: -2px;
        }

        #taxi-portal-app th.taxi-portal-th-sort .taxi-portal-th-sort-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            width: 100%;
            min-height: 100%;
        }

        #taxi-portal-app th.taxi-portal-th-sort .taxi-portal-th-sort-label {
            flex: 1 1 auto;
            min-width: 0;
            text-align: left;
        }

        #taxi-portal-app th.taxi-portal-th-sort .kt-table-col-sort {
            flex: 0 0 auto;
            margin-inline-start: auto;
        }

        #taxi-portal-app .taxi-portal-route-address {
            max-width: 100%;
        }

        #taxi-portal-app .taxi-portal-route-address .ki-arrow-down {
            font-weight: 700;
        }

        #taxi-portal-app .taxi-portal-ride-action-btn {
            width: 2.25rem;
            height: 2.25rem;
            min-width: 2.25rem;
            min-height: 2.25rem;
            padding: 0;
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            color: var(--muted-foreground);
        }

        #taxi-portal-app .taxi-portal-ride-action-btn:hover:not(:disabled) {
            background: transparent !important;
            color: #2563eb;
        }

        .dark #taxi-portal-app .taxi-portal-ride-action-btn:hover:not(:disabled),
        html.dark #taxi-portal-app .taxi-portal-ride-action-btn:hover:not(:disabled) {
            color: #60a5fa;
        }

        #taxi-portal-app .taxi-portal-ride-action-btn i {
            font-size: 1.35rem;
            line-height: 1;
        }

        #taxi-portal-app .taxi-portal-ride-action-btn:disabled {
            opacity: 0.45;
        }

        #taxi-portal-app .taxi-portal-ride-detail-backdrop {
            background-color: rgb(0 0 0 / 0.35);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        #taxi-portal-app .taxi-portal-datatable-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem 0.75rem;
            margin-bottom: 0.75rem;
        }

        #taxi-portal-app .taxi-portal-datatable-filters {
            display: flex;
            flex: 1 1 auto;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
        }

        #taxi-portal-app .taxi-portal-datatable-count {
            flex: 0 0 auto;
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.25;
            color: var(--muted-foreground);
            white-space: nowrap;
        }

        #taxi-portal-app .taxi-portal-datatable-search {
            flex: 0 1 11rem;
            width: 11rem;
            min-width: 8.5rem;
            max-width: 11rem;
        }

        #taxi-portal-app .taxi-portal-datatable-field-status {
            flex: 0 1 9.5rem;
            width: 9.5rem;
            min-width: 7.5rem;
            max-width: 100%;
        }

        #taxi-portal-app .taxi-portal-datatable-field-amount {
            flex: 0 1 5.5rem;
            width: 5.5rem;
            min-width: 4.75rem;
            max-width: 100%;
        }

        #taxi-portal-app .taxi-portal-datatable-reset {
            flex: 0 0 auto;
            width: 2rem;
            height: 2rem;
            min-width: 2rem;
            min-height: 2rem;
            padding: 0;
            border: 1px solid #e5e7eb !important;
            border-radius: 0.375rem;
            background: transparent !important;
            box-shadow: none !important;
            color: var(--muted-foreground);
        }

        #taxi-portal-app .taxi-portal-datatable-reset:hover:not(:disabled) {
            background: transparent !important;
            border-color: #3b82f6 !important;
            color: #2563eb;
        }

        .dark #taxi-portal-app .taxi-portal-datatable-reset,
        html.dark #taxi-portal-app .taxi-portal-datatable-reset {
            border-color: #4b5563 !important;
        }

        .dark #taxi-portal-app .taxi-portal-datatable-reset:hover:not(:disabled),
        html.dark #taxi-portal-app .taxi-portal-datatable-reset:hover:not(:disabled) {
            border-color: #60a5fa !important;
            color: #60a5fa;
        }

        #taxi-portal-app .taxi-portal-datatable-reset i {
            font-size: 1rem;
            line-height: 1;
        }

        #taxi-portal-app .taxi-portal-datatable-input,
        #taxi-portal-app .taxi-portal-datatable-select {
            display: block;
            width: 100%;
            box-sizing: border-box;
            min-height: 2rem;
            padding: 0.3125rem 0.625rem;
            font-size: 0.8125rem;
            line-height: 1.25;
            color: var(--foreground);
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        #taxi-portal-app .taxi-portal-datatable-input:focus,
        #taxi-portal-app .taxi-portal-datatable-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgb(59 130 246 / 0.2);
        }

        #taxi-portal-app .taxi-portal-datatable-search-inner {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            width: 100%;
            min-height: 2rem;
            padding: 0 0.625rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            background-color: #ffffff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        #taxi-portal-app .taxi-portal-datatable-search-inner:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgb(59 130 246 / 0.2);
        }

        #taxi-portal-app .taxi-portal-datatable-search-inner .ki-magnifier {
            color: var(--muted-foreground);
            flex-shrink: 0;
            font-size: 0.875rem;
        }

        #taxi-portal-app .taxi-portal-datatable-search-inner .taxi-portal-datatable-input {
            border: 0;
            box-shadow: none;
            min-height: 0;
            padding: 0.3125rem 0;
            background: transparent;
        }

        #taxi-portal-app .taxi-portal-datatable-search-inner .taxi-portal-datatable-input:focus {
            box-shadow: none;
        }

        @media (min-width: 640px) {
            #taxi-portal-app .taxi-portal-datatable-filters {
                flex-wrap: nowrap;
            }
        }

        @media (max-width: 639px) {
            #taxi-portal-app .taxi-portal-datatable-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            #taxi-portal-app .taxi-portal-datatable-search {
                flex: 1 1 100%;
                width: 100%;
                max-width: 100%;
            }

            #taxi-portal-app .taxi-portal-datatable-field-status {
                flex: 1 1 calc(50% - 0.25rem);
                width: auto;
            }

            #taxi-portal-app .taxi-portal-datatable-field-amount {
                flex: 1 1 calc(50% - 0.25rem);
                width: auto;
            }

            #taxi-portal-app .taxi-portal-datatable-count {
                width: 100%;
            }
        }

        .dark #taxi-portal-app .taxi-portal-datatable-input,
        html.dark #taxi-portal-app .taxi-portal-datatable-input,
        .dark #taxi-portal-app .taxi-portal-datatable-select,
        html.dark #taxi-portal-app .taxi-portal-datatable-select,
        .dark #taxi-portal-app .taxi-portal-datatable-search-inner,
        html.dark #taxi-portal-app .taxi-portal-datatable-search-inner {
            color: #f9fafb;
            background-color: #111827;
            border-color: #4b5563;
        }

        .dark #taxi-portal-app .taxi-portal-datatable-search-inner .taxi-portal-datatable-input,
        html.dark #taxi-portal-app .taxi-portal-datatable-search-inner .taxi-portal-datatable-input {
            background: transparent;
        }

        #taxi-portal-app .taxi-portal-datatable-footer.admin-datatable-footer {
            display: grid;
            grid-template-columns: max-content 1fr max-content;
            align-items: center;
            gap: 0.75rem 1rem;
            width: 100%;
        }

        #taxi-portal-app .taxi-portal-datatable-footer .admin-datatable-footer__perpage {
            justify-self: start;
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            flex-shrink: 0;
        }

        #taxi-portal-app .taxi-portal-datatable-footer .admin-datatable-footer__perpage span {
            white-space: nowrap;
        }

        #taxi-portal-app .taxi-portal-datatable-footer .admin-datatable-footer__pagination {
            justify-self: center;
        }

        #taxi-portal-app .taxi-portal-datatable-footer .admin-datatable-footer__info {
            justify-self: end;
            white-space: nowrap;
        }

        #taxi-portal-app .taxi-portal-datatable-footer .kt-datatable-pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.25rem;
        }

        @media (max-width: 1023px) {
            #taxi-portal-app .taxi-portal-datatable-footer.admin-datatable-footer {
                grid-template-columns: 1fr;
                justify-items: center;
                text-align: center;
            }

            #taxi-portal-app .taxi-portal-datatable-footer .admin-datatable-footer__perpage,
            #taxi-portal-app .taxi-portal-datatable-footer .admin-datatable-footer__pagination,
            #taxi-portal-app .taxi-portal-datatable-footer .admin-datatable-footer__info {
                justify-self: center;
                width: 100%;
            }
        }

        /* Boekingsmodule: volle breedte binnen contentpaneel (buiten container-padding) */
        #taxi-portal-app .taxi-portal-booking-slot {
            width: calc(100% + 20px);
            max-width: none;
            margin-left: -10px;
            margin-right: -10px;
        }
    </style>
@endpush

@section('content')
    @php
        $logoAlt = $branding['site_name'] ?? config('app.name', 'Nexa');
        $logoHref = \App\Support\Tenancy\TenantFrontendUrl::for(route('home'));
        $logoLight = $branding['logo_url'] ?? asset('images/nexa-logo.png');
        $logoDark = $branding['logo_dark_url'] ?? $logoLight;
    @endphp

    <div
        id="taxi-portal-app"
        class="w-full flex-1"
        data-logo-alt="{{ $logoAlt }}"
        data-logo-href="{{ $logoHref }}"
        data-logo-light="{{ $logoLight }}"
        data-logo-dark="{{ $logoDark }}"
        data-api-dashboard="{{ route('taxi.portal.api.dashboard') }}"
        data-api-rides="{{ route('taxi.portal.api.rides') }}"
        data-api-rides-base="{{ url('/mijn-taxi/api/rides') }}"
        data-api-invoices="{{ route('taxi.portal.api.invoices') }}"
        data-api-profile="{{ route('taxi.portal.api.profile') }}"
        data-api-profile-update="{{ route('taxi.portal.api.profile.update') }}"
        data-api-profile-password="{{ route('taxi.portal.api.profile.password') }}"
        data-api-invoice-pdf="{{ url('/mijn-taxi/api/invoices') }}"
    ></div>

    {{-- Wordt in het rechter contentpaneel van het portaal geplaatst (sidebar blijft zichtbaar). --}}
    <div id="taxi-portal-booking-source" class="hidden w-full">
        @include('frontend.website.components.nexataxi-boekingsmodule', [
            'homeSections' => $homeSections ?? [],
            'bookingConfig' => $bookingConfig ?? null,
            'sectionKey' => $sectionKey ?? 'component:taxi.boekingsmodule',
            'page' => $page ?? null,
            'googleMapsApiKey' => $googleMapsApiKey ?? '',
            'bookingCustomerPrefill' => $bookingCustomerPrefill ?? [],
            'bookingPortalMode' => $bookingPortalMode ?? false,
            'bookingReturnUrl' => $bookingReturnUrl ?? route('taxi.portal.dashboard'),
        ])
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('metronic-v9.4.13/demo1/assets/vendors/ktui/ktui.min.js') }}"></script>
    <script src="{{ asset('metronic-v9.4.13/demo1/assets/js/core.bundle.js') }}"></script>
    @vite('resources/js/taxi-portal-app.ts')
@endpush
