@php
    $totalVacancies = $company->vacancies()->where('status', 'active')->count();
    $totalUsers = \App\Models\User::where('company_id', $company->id)->count();
    $totalRevenue = \App\Models\Payment::where('company_id', $company->id)->where('status', 'paid')->sum('amount');
    $companyRank = \App\Models\Company::where('created_at', '<=', $company->created_at)->count();
    $locationsCount = $company->locations()->count();
    $activeVacancies = $company->vacancies()->where('status', 'active')->get();
    $totalMatches = \App\Models\JobMatch::whereHas('vacancy', function($q) use ($company) {
        $q->where('company_id', $company->id);
    })->count();
    $companyUsers = $company->users()->limit(8)->get();
@endphp

<style>
    .hero-bg {
        background-image: url('assets/media/images/2600x1200/bg-1.png');
    }
    .dark .hero-bg {
        background-image: url('assets/media/images/2600x1200/bg-1-dark.png');
    }
</style>

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            @if($company->logo_blob)
                <div class="flex items-center justify-center rounded-full border-2 border-green-200 dark:border-green-950 size-[100px] shrink-0 bg-background">
                    <img class="size-[50px] max-h-[50px] w-auto object-contain" src="{{ route('admin.companies.logo', $company) }}" alt="{{ $company->name }}">
                </div>
            @else
                <div class="flex items-center justify-center rounded-full border-2 border-green-200 dark:border-green-950 size-[100px] shrink-0 bg-background">
                    <i class="ki-filled ki-abstract-26 text-4xl text-muted-foreground"></i>
                </div>
            @endif
            <div class="flex items-center gap-1.5">
                <div class="text-lg leading-5 font-semibold text-mono">
                    {{ $company->name }}
                </div>
                @if($company->is_main || $company->mainLocation)
                    <svg class="text-primary" fill="none" height="16" viewbox="0 0 15 16" width="15" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14.5425 6.89749L13.5 5.83999C13.4273 5.76877 13.3699 5.6835 13.3312 5.58937C13.2925 5.49525 13.2734 5.39424 13.275 5.29249V3.79249C13.274 3.58699 13.2324 3.38371 13.1527 3.19432C13.0729 3.00494 12.9565 2.83318 12.8101 2.68892C12.6638 2.54466 12.4904 2.43073 12.2998 2.35369C12.1093 2.27665 11.9055 2.23801 11.7 2.23999H10.2C10.0982 2.24159 9.99722 2.22247 9.9031 2.18378C9.80898 2.1451 9.72371 2.08767 9.65249 2.01499L8.60249 0.957487C8.30998 0.665289 7.91344 0.50116 7.49999 0.50116C7.08654 0.50116 6.68999 0.665289 6.39749 0.957487L5.33999 1.99999C5.26876 2.07267 5.1835 2.1301 5.08937 2.16879C4.99525 2.20747 4.89424 2.22659 4.79249 2.22499H3.29249C3.08699 2.22597 2.88371 2.26754 2.69432 2.34731C2.50494 2.42709 2.33318 2.54349 2.18892 2.68985C2.04466 2.8362 1.93073 3.00961 1.85369 3.20013C1.77665 3.39064 1.73801 3.5945 1.73999 3.79999V5.29999C1.74159 5.40174 1.72247 5.50275 1.68378 5.59687C1.6451 5.691 1.58767 5.77627 1.51499 5.84749L0.457487 6.89749C0.165289 7.19 0.00115967 7.58654 0.00115967 7.99999C0.00115967 8.41344 0.165289 8.80998 0.457487 9.10249L1.49999 10.16C1.57267 10.2312 1.6301 10.3165 1.66878 10.4106C1.70747 10.5047 1.72659 10.6057 1.72499 10.7075V12.2075C1.72597 12.413 1.76754 12.6163 1.84731 12.8056C1.92709 12.995 2.04349 13.1668 2.18985 13.3111C2.3362 13.4553 2.50961 13.5692 2.70013 13.6463C2.89064 13.7233 3.0945 13.762 3.29999 13.76H4.79999C4.90174 13.7584 5.00275 13.7775 5.09687 13.8162C5.191 13.8549 5.27627 13.9123 5.34749 13.985L6.40499 15.0425C6.69749 15.3347 7.09404 15.4988 7.50749 15.4988C7.92094 15.4988 8.31748 15.3347 8.60999 15.0425L9.65999 14C9.73121 13.9273 9.81647 13.8699 9.9106 13.8312C10.0047 13.7925 10.1057 13.7734 10.2075 13.775H11.7075C12.1212 13.775 12.518 13.6106 12.8106 13.3181C13.1031 13.0255 13.2675 12.6287 13.2675 12.215V10.715C13.2659 10.6132 13.285 10.5122 13.3237 10.4181C13.3624 10.324 13.4198 10.2387 13.4925 10.1675L14.55 9.10999C14.6953 8.96452 14.8104 8.79176 14.8887 8.60164C14.9671 8.41152 15.007 8.20779 15.0063 8.00218C15.0056 7.79656 14.9643 7.59311 14.8847 7.40353C14.8051 7.21394 14.6888 7.04197 14.5425 6.89749ZM10.635 6.64999L6.95249 10.25C6.90055 10.3026 6.83864 10.3443 6.77038 10.3726C6.70212 10.4009 6.62889 10.4153 6.55499 10.415C6.48062 10.4139 6.40719 10.3982 6.33896 10.3685C6.27073 10.3389 6.20905 10.2961 6.15749 10.2425L4.37999 8.44249C4.32532 8.39044 4.28169 8.32793 4.25169 8.25867C4.22169 8.18941 4.20593 8.11482 4.20536 8.03934C4.20479 7.96387 4.21941 7.88905 4.24836 7.81934C4.27731 7.74964 4.31999 7.68647 4.37387 7.63361C4.42774 7.58074 4.4917 7.53926 4.56194 7.51163C4.63218 7.484 4.70726 7.47079 4.78271 7.47278C4.85816 7.47478 4.93244 7.49194 5.00112 7.52324C5.0698 7.55454 5.13148 7.59935 5.18249 7.65499L6.56249 9.05749L9.84749 5.84749C9.95296 5.74215 10.0959 5.68298 10.245 5.68298C10.394 5.68298 10.537 5.74215 10.6425 5.84749C10.6953 5.90034 10.737 5.96318 10.7653 6.03234C10.7935 6.1015 10.8077 6.1756 10.807 6.25031C10.8063 6.32502 10.7908 6.39884 10.7612 6.46746C10.7317 6.53608 10.6888 6.59813 10.635 6.64999Z" fill="currentColor"></path>
                    </svg>
                @endif
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-abstract-41 text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">
                        {{ $company->is_active ? 'Actief Bedrijf' : 'Inactief Bedrijf' }}
                    </span>
                </div>
                @if($company->city)
                    <div class="flex gap-1.25 items-center">
                        <i class="ki-filled ki-geolocation text-muted-foreground text-sm"></i>
                        <span class="text-secondary-foreground font-medium">
                            {{ $company->city }}{{ $company->country ? ', ' . $company->country : '' }}
                        </span>
                    </div>
                @endif
                @if($company->email)
                    <div class="flex gap-1.25 items-center">
                        <i class="ki-filled ki-sms text-muted-foreground text-sm"></i>
                        <a class="text-secondary-foreground font-medium hover:text-primary" href="mailto:{{ $company->email }}">
                            {{ $company->email }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed mb-5 lg:mb-7.5">
    <div class="kt-card">
        <div class="kt-card-content">
            <div class="flex lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $totalVacancies }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Vacatures
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ number_format($totalUsers, 0, ',', '.') }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Gebruikers
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $totalMatches }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Matches
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5 mt-5 lg:mt-7.5">
        <div class="col-span-1">
            <div class="flex flex-col gap-5 lg:gap-7.5">
                {{-- Bedrijfsinformatie --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">
                            Bedrijfsinformatie
                        </h3>
                    </div>
                    <div class="kt-card-content pt-3.5 pb-3.5">
                        <table class="w-full">
                            <tbody>
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-4 pe-4 lg:pe-10 align-top" style="min-width: 120px;">
                                        Vestigingen:
                                    </td>
                                    <td class="text-sm text-mono pb-4 align-top">
                                        {{ $locationsCount + 1 }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-4 pe-4 lg:pe-10 align-top" style="min-width: 120px;">
                                        Opgericht:
                                    </td>
                                    <td class="text-sm text-mono pb-4 align-top">
                                        {{ $company->created_at->format('Y') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-4 pe-4 lg:pe-10 align-top" style="min-width: 120px;">
                                        Status:
                                    </td>
                                    <td class="text-sm text-mono pb-4 align-top">
                                        <span class="kt-badge kt-badge-sm {{ $company->is_active ? 'kt-badge-success' : 'kt-badge-warning' }} kt-badge-outline">
                                            {{ $company->is_active ? 'Actief' : 'Inactief' }}
                                        </span>
                                    </td>
                                </tr>
                                @if($company->country)
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-4 pe-4 lg:pe-10 align-top" style="min-width: 120px;">
                                        Land:
                                    </td>
                                    <td class="text-sm text-mono pb-4 align-top">
                                        {{ $company->country }}
                                    </td>
                                </tr>
                                @endif
                                @if($company->industry)
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-4 pe-4 lg:pe-10 align-top" style="min-width: 120px;">
                                        Sector:
                                    </td>
                                    <td class="text-sm text-mono pb-4 align-top">
                                        {{ $company->industry }}
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Open Jobs --}}
                @if($activeVacancies->count() > 0)
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">
                            Openstaande Vacatures
                        </h3>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-5">
                            @foreach($activeVacancies as $vacancy)
                            <div class="flex align-start gap-3.5">
                                <div class="flex items-center justify-center w-[1.875rem] h-[1.875rem] bg-accent/60 rounded-lg border border-input">
                                    <i class="ki-filled ki-briefcase text-base text-secondary-foreground"></i>
                                </div>
                                <div class="flex flex-col justify-start gap-1">
                                    <a class="text-sm font-semibold leading-none kt-link" href="{{ route('admin.vacancies.show', $vacancy) }}">
                                        {{ $vacancy->category->name ?? 'Vacature' }}
                                    </a>
                                    <span class="text-sm font-medium text-mono">
                                        {{ $vacancy->title }}
                                    </span>
                                    @if($vacancy->min_salary || $vacancy->max_salary)
                                        <span class="text-xs text-secondary-foreground">
                                            @if($vacancy->min_salary && $vacancy->max_salary)
                                                €{{ number_format($vacancy->min_salary, 0, ',', '.') }} - €{{ number_format($vacancy->max_salary, 0, ',', '.') }}
                                            @elseif($vacancy->min_salary)
                                                Vanaf €{{ number_format($vacancy->min_salary, 0, ',', '.') }}
                                            @elseif($vacancy->max_salary)
                                                Tot €{{ number_format($vacancy->max_salary, 0, ',', '.') }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @if($activeVacancies->count() > 10)
                    <div class="kt-card-footer justify-center">
                        <a class="kt-link kt-link-underlined kt-link-dashed" href="{{ route('admin.vacancies.index', ['company_id' => $company->id]) }}">
                            Bekijk alle vacatures
                        </a>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Gebruikers --}}
                @if($companyUsers->count() > 0)
                <div class="kt-card">
                    <div class="kt-card-header gap-2">
                        <h3 class="kt-card-title">
                            Gebruikers
                        </h3>
                    </div>
                    <div class="kt-card-content">
                        <div class="flex flex-col gap-3">
                            @foreach($companyUsers as $user)
                                <div class="flex items-center gap-3">
                                    @if($user->photo_blob)
                                        <img class="rounded-full h-[36px] w-[36px] object-cover shrink-0" src="{{ route('admin.users.photo', $user) }}" alt="{{ $user->first_name }} {{ $user->last_name }}"/>
                                    @else
                                        <div class="rounded-full h-[36px] w-[36px] bg-accent/60 border border-input flex items-center justify-center shrink-0">
                                            <span class="text-xs font-semibold text-secondary-foreground">
                                                {{ strtoupper(substr($user->first_name ?? 'U', 0, 1) . substr($user->last_name ?? '', 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif
                                    <div class="flex flex-col">
                                        <span class="text-sm font-semibold text-foreground leading-none">
                                            {{ $user->first_name }} {{ $user->last_name }}
                                        </span>
                                        @if($user->email)
                                        <span class="text-xs text-secondary-foreground leading-none mt-1">
                                            {{ $user->email }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @if($company->users()->count() > 8)
                    <div class="kt-card-footer justify-center">
                        <a class="kt-link kt-link-underlined kt-link-dashed" href="{{ route('admin.users.index', ['company_id' => $company->id]) }}">
                            Bekijk alle gebruikers
                        </a>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <div class="col-span-1 lg:col-span-2">
            <div class="flex flex-col gap-5 lg:gap-7.5">
                {{-- Company Profile --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">
                            Bedrijfsprofiel
                        </h3>
                    </div>
                    <div class="kt-card-content">
                        <h3 class="text-base font-semibold text-mono leading-none mb-5">
                            Hoofdkantoor
                        </h3>
                        <div class="flex flex-wrap items-center gap-5 mb-10">
                            <div class="rounded-xl w-full md:w-80 min-h-52" id="company_profile_map">
                            </div>
                            <div class="flex flex-col gap-2.5">
                                @if($company->website)
                                <div class="flex items-center gap-2.5">
                                    <span>
                                        <i class="ki-filled ki-dribbble text-lg text-muted-foreground"></i>
                                    </span>
                                    <a class="link text-sm font-medium" href="{{ $company->website }}" target="_blank">
                                        {{ $company->website }}
                                    </a>
                                </div>
                                @endif
                                @if($company->email)
                                <div class="flex items-center gap-2.5">
                                    <span>
                                        <i class="ki-filled ki-sms text-lg text-muted-foreground"></i>
                                    </span>
                                    <a class="link text-sm font-medium" href="mailto:{{ $company->email }}">
                                        {{ $company->email }}
                                    </a>
                                </div>
                                @endif
                                @if($company->phone)
                                <div class="flex items-center gap-2.5">
                                    <span>
                                        <i class="ki-filled ki-whatsapp text-lg text-muted-foreground"></i>
                                    </span>
                                    <span class="text-sm text-mono">
                                        {{ $company->phone }}
                                    </span>
                                </div>
                                @endif
                                @php
                                    $address = '';
                                    if ($company->mainLocation) {
                                        $address = $company->mainLocation->street . ' ' . $company->mainLocation->house_number . ($company->mainLocation->house_number_extension ? '-' . $company->mainLocation->house_number_extension : '') . ', ' . $company->mainLocation->postal_code . ' ' . $company->mainLocation->city . ($company->mainLocation->country ? ', ' . $company->mainLocation->country : '');
                                    } elseif ($company->street) {
                                        $address = $company->street . ' ' . $company->house_number . ($company->house_number_extension ? '-' . $company->house_number_extension : '') . ', ' . $company->postal_code . ' ' . $company->city . ($company->country ? ', ' . $company->country : '');
                                    } else {
                                        $address = '430 E 6th St, New York, 10009.';
                                    }
                                @endphp
                                @if($address)
                                <div class="flex items-center gap-2.5">
                                    <span>
                                        <i class="ki-filled ki-map text-lg text-muted-foreground"></i>
                                    </span>
                                    <span class="text-sm text-mono">
                                        {{ $address }}
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @if($company->description)
                        <div class="grid gap-2.5 mb-7">
                            <div class="text-base font-semibold text-mono">
                                Over
                            </div>
                            <p class="text-sm text-foreground leading-5.5">
                                {{ $company->description }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Locations --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">
                            Vestigingen
                        </h3>
                    </div>
                    <div class="kt-card-content p-5 lg:p-7.5 lg:pb-7">
                        <div class="flex gap-5 kt-scrollable-x">
                            @if($company->locations->count() > 0)
                                @foreach($company->locations as $index => $location)
                                    <div class="kt-card shadow-none w-[280px] border-0 mb-4">
                                        <img alt="{{ $location->name }}" class="rounded-t-xl max-w-[280px] shrink-0" src="{{ asset('assets/media/images/600x400/' . (($index % 3) + 10) . '.jpg') }}"/>
                                        <div class="kt-card-border kt-card-rounded-b px-3.5 h-full pt-3 pb-3.5">
                                            <a class="font-medium block text-mono hover:text-primary text-base mb-2" href="{{ route('admin.companies.locations.show', [$company, $location]) }}">
                                                {{ $location->name }}
                                            </a>
                                            <p class="text-sm text-secondary-foreground">
                                                {{ $location->street }} {{ $location->house_number }}{{ $location->house_number_extension ? '-' . $location->house_number_extension : '' }} <br> {{ $location->postal_code }} {{ $location->city }} <br> {{ $location->country ? $location->country : '' }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                {{-- Dummy locations --}}
                                <div class="kt-card shadow-none w-[280px] border-0 mb-4">
                                    <img alt="Duolingo Tech Hub" class="rounded-t-xl max-w-[280px] shrink-0" src="{{ asset('assets/media/images/600x400/10.jpg') }}"/>
                                    <div class="kt-card-border kt-card-rounded-b px-3.5 h-full pt-3 pb-3.5">
                                        <a class="font-medium block text-mono hover:text-primary text-base mb-2" href="#">
                                            Duolingo Tech Hub
                                        </a>
                                        <p class="text-sm text-secondary-foreground">
                                            456 Innovation Street, Floor 6, Techland, New York 54321
                                        </p>
                                    </div>
                                </div>
                                <div class="kt-card shadow-none w-[280px] border-0 mb-4">
                                    <img alt="Duolingo Language Lab" class="rounded-t-xl max-w-[280px] shrink-0" src="{{ asset('assets/media/images/600x400/11.jpg') }}"/>
                                    <div class="kt-card-border kt-card-rounded-b px-3.5 h-full pt-3 pb-3.5">
                                        <a class="font-medium block text-mono hover:text-primary text-base mb-2" href="#">
                                            Duolingo Language Lab
                                        </a>
                                        <p class="text-sm text-secondary-foreground">
                                            789 Learning Lane, 3rd Floor, Lingoville, Texas 98765
                                        </p>
                                    </div>
                                </div>
                                <div class="kt-card shadow-none w-[280px] border-0 mb-4">
                                    <img alt="Duolingo Research Institute" class="rounded-t-xl max-w-[280px] shrink-0" src="{{ asset('assets/media/images/600x400/12.jpg') }}"/>
                                    <div class="kt-card-border kt-card-rounded-b px-3.5 h-full pt-3 pb-3.5">
                                        <a class="font-medium block text-mono hover:text-primary text-base mb-2" href="#">
                                            Duolingo Research Institute
                                        </a>
                                        <p class="text-sm text-secondary-foreground">
                                            246 Innovation Road, Research Wing, Innovacity, Arizona 13579
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="{{ asset('assets/vendors/leaflet/leaflet.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@push('scripts')
<script src="{{ asset('assets/vendors/leaflet/leaflet.bundle.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mapElement = document.getElementById('company_profile_map');
    if (!mapElement) return;

    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.error('Leaflet is not loaded');
        return;
    }

    @php
        // Get address for geocoding
        $address = '';
        $lat = 40.725;
        $lng = -73.985;
        if ($company->mainLocation) {
            $address = $company->mainLocation->street . ' ' . $company->mainLocation->house_number . ($company->mainLocation->house_number_extension ? '-' . $company->mainLocation->house_number_extension : '') . ', ' . $company->mainLocation->postal_code . ' ' . $company->mainLocation->city . ($company->mainLocation->country ? ', ' . $company->mainLocation->country : '');
        } elseif ($company->street) {
            $address = $company->street . ' ' . $company->house_number . ($company->house_number_extension ? '-' . $company->house_number_extension : '') . ', ' . $company->postal_code . ' ' . $company->city . ($company->country ? ', ' . $company->country : '');
        } else {
            $address = '430 E 6th St, New York, 10009.';
        }
    @endphp

    // Initialize map with default location
    const leaflet = L.map('company_profile_map', {
        center: [{{ $lat }}, {{ $lng }}],
        zoom: 14,  // More zoomed in
        zoomControl: false  // Disable default zoom controls
    });

    // Add zoom controls in bottom-left corner
    L.control.zoom({
        position: 'bottomleft'
    }).addTo(leaflet);

    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(leaflet);

    // Custom marker icon
    const leafletIcon = L.divIcon({
        html: '<i class="ki-solid ki-geolocation text-3xl text-green-500"></i>',
        bgPos: [10, 10],
        iconAnchor: [20, 37],
        popupAnchor: [0, -37],
        className: 'leaflet-marker'
    });

    // Try to geocode the address
    const address = @json($address);

    // Use Nominatim (OpenStreetMap geocoding service) - free and no API key needed
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);

                // Update map center - more zoomed in
                leaflet.setView([lat, lng], 16);

                // Add marker with popup (balloon) always visible
                const marker = L.marker([lat, lng], {
                    icon: leafletIcon
                }).addTo(leaflet);

                // Create popup and ensure it's always visible
                marker.bindPopup(address, {
                    closeButton: false,
                    autoPan: true,
                    autoPanPadding: [20, 20],  // Minimal padding since zoom is bottom-left
                    offset: [0, -40],  // Offset popup up to center it better
                    keepInView: true  // Keep popup in view when panning
                });

                // Open popup immediately and keep it open
                marker.openPopup();

                // Ensure popup stays visible
                setTimeout(() => {
                    marker.openPopup();
                }, 100);
            } else {
                // Fallback to default location - more zoomed in
                leaflet.setView([{{ $lat }}, {{ $lng }}], 16);

                const marker = L.marker([{{ $lat }}, {{ $lng }}], {
                    icon: leafletIcon
                }).addTo(leaflet);

                marker.bindPopup(address, {
                    closeButton: false,
                    autoPan: true,
                    autoPanPadding: [20, 20],  // Minimal padding since zoom is bottom-left
                    offset: [0, -40],  // Offset popup up to center it better
                    keepInView: true
                });

                // Open popup immediately and keep it open
                marker.openPopup();

                // Ensure popup stays visible
                setTimeout(() => {
                    marker.openPopup();
                }, 100);
            }
        })
        .catch(error => {
            console.error('Geocoding error:', error);
            // Fallback to default location - more zoomed in
            leaflet.setView([{{ $lat }}, {{ $lng }}], 16);

            const marker = L.marker([{{ $lat }}, {{ $lng }}], {
                icon: leafletIcon
            }).addTo(leaflet);

            marker.bindPopup(address, {
                closeButton: false,
                autoPan: true,
                autoPanPadding: [20, 20],  // Minimal padding since zoom is bottom-left
                offset: [0, -40],  // Offset popup up to center it better
                keepInView: true
            });

            // Open popup immediately and keep it open
            marker.openPopup();

            // Ensure popup stays visible
            setTimeout(() => {
                marker.openPopup();
            }, 100);
        });
});
</script>
@endpush

