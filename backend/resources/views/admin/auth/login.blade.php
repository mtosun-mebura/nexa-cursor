<!--
Product: Metronic is a toolkit of UI components built with Tailwind CSS for developing scalable web applications quickly and efficiently
Version: v9.3.5
Author: Keenthemes
-->
<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="nl">
<head>
    <base href="{{ url('/') }}">
    <title>Admin Login - NEXA Skillmatching</title>
    <meta charset="utf-8"/>
    <meta content="follow, index" name="robots"/>
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport"/>
    <meta content="Admin login page for NEXA Skillmatching Platform" name="description"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background">
    <!-- Theme Mode -->
    <script>
        const defaultThemeMode = 'light';
        let themeMode;

        if (document.documentElement) {
            if (localStorage.getItem('kt-theme')) {
                themeMode = localStorage.getItem('kt-theme');
            } else if (document.documentElement.hasAttribute('data-kt-theme-mode')) {
                themeMode = document.documentElement.getAttribute('data-kt-theme-mode');
            } else {
                themeMode = defaultThemeMode;
            }

            if (themeMode === 'system') {
                themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            document.documentElement.classList.add(themeMode);
        }
    </script>
    <!-- End of Theme Mode -->
    
    <!-- Page -->
    <style>
        .branded-bg {
            background-image: url('{{ asset('assets/media/images/2600x1600/1.png') }}');
        }
        .dark .branded-bg {
            background-image: url('{{ asset('assets/media/images/2600x1600/1-dark.png') }}');
        }
    </style>
    
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="kt-card max-w-[370px] w-full">
            <form action="{{ route('admin.login.post') }}" class="kt-card-content flex flex-col gap-5 p-10" id="sign_in_form" method="POST">
                @csrf
                
                <div class="text-center mb-2.5">
                    <h3 class="text-lg font-medium text-mono leading-none mb-2.5">
                        Inloggen
                    </h3>
                    <div class="flex items-center justify-center font-medium">
                        <span class="text-sm text-secondary-foreground me-1.5">
                            Heeft u geen account?
                        </span>
                        <a class="text-sm link" href="#">
                            Registreren
                        </a>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="kt-alert kt-alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('error'))
                    <div class="kt-alert kt-alert-danger">
                        <i class="ki-filled ki-information-5"></i> {{ session('error') }}
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-2.5">
                    <a class="kt-btn kt-btn-outline justify-center" href="#">
                        <img alt="" class="size-3.5 shrink-0" src="{{ asset('assets/media/brand-logos/google.svg') }}"/>
                        Gebruik Google
                    </a>
                    <a class="kt-btn kt-btn-outline justify-center" href="#">
                        <img alt="" class="size-3.5 shrink-0 dark:hidden" src="{{ asset('assets/media/brand-logos/apple-black.svg') }}"/>
                        <img alt="" class="size-3.5 shrink-0 light:hidden" src="{{ asset('assets/media/brand-logos/apple-white.svg') }}"/>
                        Gebruik Apple
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <span class="border-t border-border w-full"></span>
                    <span class="text-xs text-muted-foreground font-medium uppercase">
                        OF
                    </span>
                    <span class="border-t border-border w-full"></span>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">
                        E-mail
                    </label>
                    <input class="kt-input @error('email') border-danger @enderror" 
                           placeholder="email@email.com" 
                           type="email" 
                           name="email"
                           value="{{ old('email') }}" 
                           required
                           autofocus/>
                    @error('email')
                        <div class="text-sm text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">
                        Wachtwoord
                    </label>
                    <div class="kt-input" data-kt-toggle-password="true">
                        <input name="password" 
                               placeholder="Voer wachtwoord in" 
                               type="password" 
                               value=""
                               required/>
                        <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" 
                                data-kt-toggle-password-trigger="true" 
                                type="button">
                            <span class="kt-toggle-password-active:hidden">
                                <i class="ki-filled ki-eye text-muted-foreground"></i>
                            </span>
                            <span class="hidden kt-toggle-password-active:block">
                                <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                            </span>
                        </button>
                    </div>
                    @error('password')
                        <div class="text-sm text-danger mt-1">{{ $message }}</div>
                    @enderror
                    <a class="text-sm link text-primary mt-1" href="#">
                        Wachtwoord vergeten?
                    </a>
                </div>

                <label class="kt-label">
                    <input class="kt-checkbox kt-checkbox-sm" 
                           name="remember" 
                           type="checkbox" 
                           value="1"/>
                    <span class="kt-checkbox-label">
                        Onthoud mij
                    </span>
                </label>

                <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">
                    Inloggen
                </button>
            </form>
        </div>
    </div>
    <!-- End of Page -->
    
    <!-- Scripts -->
    <script src="{{ asset('assets/js/core.bundle.js') }}"></script>
    <script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
    <!-- End of Scripts -->
</body>
</html>
