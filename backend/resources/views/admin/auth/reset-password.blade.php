<!--
Product: Metronic is a toolkit of UI components built with Tailwind CSS for developing scalable web applications quickly and efficiently
Version: v9.3.5
Author: Keenthemes
-->
<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="nl">
<head>
    <base href="{{ url('/') }}">
    <title>Wachtwoord Resetten - NEXA Skillmatching</title>
    <meta charset="utf-8"/>
    <meta content="follow, index" name="robots"/>
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport"/>
    <meta content="Wachtwoord resetten pagina voor NEXA Skillmatching Platform" name="description"/>
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
        .page-bg {
            background-image: url('{{ asset('assets/media/images/2600x1200/bg-10.png') }}');
        }
        .dark .page-bg {
            background-image: url('{{ asset('assets/media/images/2600x1200/bg-10-dark.png') }}');
        }
        
        /* Form input fields 100% width */
        #reset_password_change_password_form .kt-input {
            width: 100% !important;
        }
        
        #reset_password_change_password_form .kt-input input {
            width: 100% !important;
        }
        
        /* Autofill achtergrond licht grijs */
        #reset_password_change_password_form input:-webkit-autofill,
        #reset_password_change_password_form input:-webkit-autofill:hover,
        #reset_password_change_password_form input:-webkit-autofill:focus,
        #reset_password_change_password_form input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #f3f4f6 inset !important;
            -webkit-text-fill-color: #1f2937 !important;
            background-color: #f3f4f6 !important;
        }
        
        .dark #reset_password_change_password_form input:-webkit-autofill,
        .dark #reset_password_change_password_form input:-webkit-autofill:hover,
        .dark #reset_password_change_password_form input:-webkit-autofill:focus,
        .dark #reset_password_change_password_form input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #374151 inset !important;
            -webkit-text-fill-color: #f9fafb !important;
            background-color: #374151 !important;
        }
    </style>
    
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="kt-card max-w-[370px] w-full">
            <form action="{{ route('admin.password.update') }}" class="kt-card-content flex flex-col gap-5 p-10" id="reset_password_change_password_form" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                
                <div class="text-center">
                    <h3 class="text-lg font-medium text-mono">
                        Wachtwoord Resetten
                    </h3>
                    <span class="text-sm text-secondary-foreground">
                        Voer je nieuwe wachtwoord in
                    </span>
                </div>

                @error('email')
                    <div class="kt-alert kt-alert-danger flex items-center gap-2.5 p-4 rounded-lg border border-red-500 bg-red-50 dark:bg-red-900/20">
                        <i class="ki-filled ki-information-5 text-xl text-red-600 dark:text-red-400"></i>
                        <div class="text-sm font-medium text-red-800 dark:text-red-200">{{ $message }}</div>
                    </div>
                @enderror

                @error('password')
                    <div class="kt-alert kt-alert-danger flex items-center gap-2.5 p-4 rounded-lg border border-red-500 bg-red-50 dark:bg-red-900/20">
                        <i class="ki-filled ki-information-5 text-xl text-red-600 dark:text-red-400"></i>
                        <div class="text-sm font-medium text-red-800 dark:text-red-200">{{ $message }}</div>
                    </div>
                @enderror

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label text-mono">
                        Nieuw Wachtwoord
                    </label>
                    <label class="kt-input" data-kt-toggle-password="true">
                        <input name="password" 
                               placeholder="Voer een nieuw wachtwoord in" 
                               type="password" 
                               required/>
                        <div class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true">
                            <span class="kt-toggle-password-active:hidden">
                                <i class="ki-filled ki-eye text-muted-foreground"></i>
                            </span>
                            <span class="hidden kt-toggle-password-active:block">
                                <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                            </span>
                        </div>
                    </label>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">
                        Bevestig Nieuw Wachtwoord
                    </label>
                    <label class="kt-input" data-kt-toggle-password="true">
                        <input name="password_confirmation" 
                               placeholder="Voer opnieuw een nieuw wachtwoord in" 
                               type="password" 
                               required/>
                        <div class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true">
                            <span class="kt-toggle-password-active:hidden">
                                <i class="ki-filled ki-eye text-muted-foreground"></i>
                            </span>
                            <span class="hidden kt-toggle-password-active:block">
                                <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                            </span>
                        </div>
                    </label>
                </div>

                <input type="hidden" name="email" value="{{ old('email', $email) }}">

                <button type="submit" class="kt-btn flex justify-center grow" style="background-color: #f97316; color: white !important; border-color: #f97316;">
                    Wachtwoord Resetten
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
