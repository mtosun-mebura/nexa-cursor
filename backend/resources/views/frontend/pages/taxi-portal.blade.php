@extends('frontend.layouts.app')

@section('title', ($branding['dashboard_link_label'] ?? 'Mijn Taxi').' - '.($branding['site_name'] ?? 'Nexa'))

@section('content')
<section class="py-16 bg-gray-50 dark:bg-gray-900 flex-1">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ $branding['dashboard_link_label'] ?? 'Mijn Taxi' }}
        </h1>
        <p class="mt-4 text-gray-600 dark:text-gray-300">
            Welkom in je persoonlijke taxi-omgeving. Hier komt binnenkort je overzicht van ritten en boekingen.
        </p>
    </div>
</section>
@endsection
