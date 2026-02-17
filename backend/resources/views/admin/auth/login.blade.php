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
        
        /* Login form input fields 100% width */
        #sign_in_form .kt-input {
            width: 100% !important;
        }
        
        #sign_in_form .kt-input input {
            width: 100% !important;
        }
        
        /* Autofill achtergrond licht grijs */
        #sign_in_form input:-webkit-autofill,
        #sign_in_form input:-webkit-autofill:hover,
        #sign_in_form input:-webkit-autofill:focus,
        #sign_in_form input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #f3f4f6 inset !important;
            -webkit-text-fill-color: #1f2937 !important;
            background-color: #f3f4f6 !important;
        }
        
        .dark #sign_in_form input:-webkit-autofill,
        .dark #sign_in_form input:-webkit-autofill:hover,
        .dark #sign_in_form input:-webkit-autofill:focus,
        .dark #sign_in_form input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #374151 inset !important;
            -webkit-text-fill-color: #f9fafb !important;
            background-color: #374151 !important;
        }
    </style>
    
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="kt-card max-w-[370px] w-full">
            <form action="{{ route('admin.login.post') }}" class="kt-card-content flex flex-col gap-5 p-10" id="sign_in_form" method="POST">
                @csrf
                @php $intendedValue = old('intended', request()->query('intended') ?? session('url.intended')); @endphp
                @if($intendedValue)
                    <input type="hidden" name="intended" value="{{ $intendedValue }}">
                @endif
                
                <div class="text-center mb-2.5">
                    <div class="mb-4">
                        <img
                            src="{{ asset('images/nexa-skillmatching-logo.png') }}"
                            alt="Nexa Skillmatching"
                            class="h-10 w-auto mx-auto object-contain"
                        />
                        <div class="mt-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                            Administratie paneel
                        </div>
                    </div>
                    <h3 class="text-lg font-medium text-mono leading-none mb-2.5">
                        Inloggen
                    </h3>
                </div>

                @error('email')
                    <div class="kt-alert kt-alert-danger flex items-center gap-2.5 p-4 rounded-lg border border-red-500 bg-red-50 dark:bg-red-900/20">
                        <i class="ki-filled ki-information-5 text-xl text-red-600 dark:text-red-400"></i>
                        <div class="text-sm font-medium text-red-800 dark:text-red-200">{{ $message }}</div>
                    </div>
                @enderror

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
                    <a class="text-sm link text-primary mt-1" href="{{ route('admin.password.request') }}">
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
