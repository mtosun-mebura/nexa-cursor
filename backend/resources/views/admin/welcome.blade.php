@extends('admin.layouts.app')

@section('title', 'Welkom')

@section('content')
<!--begin::Container-->
<div class="kt-container-fixed">
    <!--begin::Header-->
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-3xl font-semibold leading-none text-mono">
                Welkom, {{ auth()->user()->first_name ?? auth()->user()->email }}!
            </h1>
            <div class="flex items-center gap-2 text-base font-normal text-secondary-foreground">
                Welkom in het admin panel van Nexa Skillmatching
            </div>
        </div>
    </div>
    <!--end::Header-->

    <!--begin::Welcome Content-->
    <div class="grid gap-5 lg:gap-7.5">
        <!--begin::Welcome Card-->
        <div class="kt-card" data-permission="view-dashboard">
            <div class="kt-card-body p-8 lg:p-10">
                <div class="flex flex-col gap-6">
                    <!-- Welcome Icon -->
                    <div class="flex justify-center">
                        <div class="w-20 h-20 bg-primary/10 dark:bg-primary/20 rounded-full flex items-center justify-center">
                            <i class="ki-filled ki-abstract-26 text-4xl text-primary"></i>
                        </div>
                    </div>

                    <!-- Welcome Message -->
                    <div class="text-center space-y-4">
                        <h2 class="text-2xl font-semibold text-mono">
                            Welkom in het Admin Panel
                        </h2>
                        <p class="text-base text-secondary-foreground max-w-2xl mx-auto leading-relaxed">
                            Je account is succesvol aangemaakt en geactiveerd. Je hebt nu toegang tot het admin panel van Nexa Skillmatching.
                        </p>
                    </div>

                    <!-- User Info -->
                    <div class="kt-card bg-background border border-input mt-4" data-permission="view-dashboard">
                        <div class="kt-card-body p-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="flex flex-col gap-2">
                                    <span class="text-sm font-medium text-secondary-foreground">E-mailadres</span>
                                    <span class="text-base font-medium text-mono">{{ auth()->user()->email }}</span>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <span class="text-sm font-medium text-secondary-foreground">Naam</span>
                                    <span class="text-base font-medium text-mono">
                                        {{ auth()->user()->first_name ?? 'Niet ingevuld' }} 
                                        {{ auth()->user()->last_name ?? '' }}
                                    </span>
                                </div>
                                @if(auth()->user()->roles->count() > 0)
                                <div class="flex flex-col gap-2">
                                    <span class="text-sm font-medium text-secondary-foreground">Rol(len)</span>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach(auth()->user()->roles as $role)
                                            <span class="kt-badge kt-badge-primary">{{ $role->name }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                                @if(auth()->user()->getAllPermissions()->count() > 0)
                                <div class="flex flex-col gap-2">
                                    <span class="text-sm font-medium text-secondary-foreground">Aantal Rechten</span>
                                    <span class="text-base font-medium text-mono">
                                        {{ auth()->user()->getAllPermissions()->count() }} rechten toegewezen
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    @php
                        $userPermissions = auth()->user()->getAllPermissions()->pluck('name')->toArray();
                        $availableActions = [];
                        
                        if (in_array('view-agenda', $userPermissions)) {
                            $availableActions[] = [
                                'title' => 'Agenda',
                                'description' => 'Bekijk en beheer je agenda',
                                'route' => 'admin.agenda.index',
                                'icon' => 'ki-filled ki-calendar',
                                'color' => 'primary'
                            ];
                        }
                        if (in_array('view-users', $userPermissions)) {
                            $availableActions[] = [
                                'title' => 'Gebruikers',
                                'description' => 'Beheer gebruikers',
                                'route' => 'admin.users.index',
                                'icon' => 'ki-filled ki-people',
                                'color' => 'primary'
                            ];
                        }
                        if (in_array('view-vacancies', $userPermissions)) {
                            $availableActions[] = [
                                'title' => 'Vacatures',
                                'description' => 'Bekijk en beheer vacatures',
                                'route' => 'admin.vacancies.index',
                                'icon' => 'ki-filled ki-briefcase',
                                'color' => 'primary'
                            ];
                        }
                        if (in_array('view-companies', $userPermissions)) {
                            $availableActions[] = [
                                'title' => 'Bedrijven',
                                'description' => 'Beheer bedrijven',
                                'route' => 'admin.companies.index',
                                'icon' => 'ki-filled ki-office-bag',
                                'color' => 'primary'
                            ];
                        }
                        if (in_array('view-matches', $userPermissions)) {
                            $availableActions[] = [
                                'title' => 'Matches',
                                'description' => 'Bekijk matches',
                                'route' => 'admin.matches.index',
                                'icon' => 'ki-filled ki-heart',
                                'color' => 'primary'
                            ];
                        }
                        if (in_array('view-interviews', $userPermissions)) {
                            $availableActions[] = [
                                'title' => 'Interviews',
                                'description' => 'Beheer interviews',
                                'route' => 'admin.interviews.index',
                                'icon' => 'ki-filled ki-chat',
                                'color' => 'primary'
                            ];
                        }
                        if (in_array('view-email-templates', $userPermissions)) {
                            $availableActions[] = [
                                'title' => 'E-mail Templates',
                                'description' => 'Beheer e-mail templates',
                                'route' => 'admin.email-templates.index',
                                'icon' => 'ki-filled ki-sms',
                                'color' => 'primary'
                            ];
                        }
                        if (in_array('view-notifications', $userPermissions)) {
                            $availableActions[] = [
                                'title' => 'Notificaties',
                                'description' => 'Bekijk notificaties',
                                'route' => 'admin.notifications.index',
                                'icon' => 'ki-filled ki-notification-bing',
                                'color' => 'primary'
                            ];
                        }
                        if (in_array('view-settings', $userPermissions)) {
                            $availableActions[] = [
                                'title' => 'Instellingen',
                                'description' => 'Beheer instellingen',
                                'route' => 'admin.settings.index',
                                'icon' => 'ki-filled ki-setting-2',
                                'color' => 'primary'
                            ];
                        }
                    @endphp

                    @if(count($availableActions) > 0)
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-mono mb-4">Snelle Acties</h3>
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($availableActions as $action)
                                @if(Route::has($action['route']))
                                <a href="{{ route($action['route']) }}" class="kt-card hover:shadow-lg transition-shadow" data-route="{{ $action['route'] }}">
                                    <div class="kt-card-body p-6">
                                        <div class="flex items-start gap-4">
                                            <div class="w-12 h-12 bg-primary/10 dark:bg-primary/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <i class="{{ $action['icon'] }} text-2xl text-primary"></i>
                                            </div>
                                            <div class="flex flex-col gap-1 flex-grow">
                                                <span class="text-base font-semibold text-mono">{{ $action['title'] }}</span>
                                                <span class="text-sm text-secondary-foreground">{{ $action['description'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Help Section -->
                    <div class="kt-alert kt-alert-info mt-6">
                        <div class="kt-alert-icon">
                            <i class="ki-filled ki-information-2 text-2xl"></i>
                        </div>
                        <div class="kt-alert-content">
                            <h4 class="kt-alert-title">Hulp nodig?</h4>
                            <p class="kt-alert-text">
                                Als je vragen hebt over het gebruik van het admin panel, neem dan contact op met je beheerder of bekijk de documentatie.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Welcome Card-->
    </div>
    <!--end::Welcome Content-->
</div>
<!--end::Container-->
@endsection

