@extends('admin.layouts.app')

@section('content')
<div class="kt-container-fixed">
    <div class="kt-container-fixed mt-5">
        <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10 mt-5">
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl lg:text-3xl font-bold text-mono">Algemene configuraties</h1>
            </div>
        </div>

        @if(session('success'))
            <div class="kt-alert kt-alert-success mb-5">
                <div class="kt-alert-content">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="kt-alert kt-alert-danger mb-5">
                <div class="kt-alert-content">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="kt-alert kt-alert-danger mb-5">
                <div class="kt-alert-content">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Logo & Favicon</h3>
            </div>
            <div class="kt-card-content">
                <form action="{{ route('admin.settings.general.update') }}" method="POST" enctype="multipart/form-data" id="general-settings-form">
                    @csrf
                    
                    <!-- Logo Upload -->
                    <div class="mb-6">
                        <label class="kt-form-label mb-2">Logo</label>
                        <p class="text-sm text-muted-foreground mb-3">Het logo wordt gebruikt in de sidebar header.</p>
                        
                        <div class="flex flex-wrap sm:flex-nowrap gap-5 lg:gap-7.5 max-w-96 w-full">
                            @if($logo && Storage::disk('public')->exists($logo))
                                <img alt="Logo Preview" class="h-[35px] mt-2" 
                                     src="{{ route('admin.settings.logo') }}" 
                                     id="logo-preview"/>
                            @else
                                <img alt="Logo Preview" class="h-[35px] mt-2 hidden" 
                                     src="" 
                                     id="logo-preview"/>
                            @endif
                            <div class="flex bg-center w-full p-5 lg:p-7 bg-no-repeat bg-[length:550px] border border-input rounded-xl border-dashed branding-bg" id="logo-upload-area">
                                <div class="flex flex-col place-items-center place-content-center text-center rounded-xl w-full">
                                    <div class="flex items-center mb-2.5">
                                        <div class="relative size-11 shrink-0">
                                            <svg class="w-full h-full stroke-primary/10 fill-light" fill="none" height="48" viewbox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z" fill=""></path>
                                                <path d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z" stroke="" stroke-opacity="0.2"></path>
                                            </svg>
                                            <div class="absolute leading-none left-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4">
                                                <i class="ki-filled ki-picture text-xl ps-px text-primary"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <a class="text-mono text-xs font-medium hover:text-primary mb-px cursor-pointer" id="logo-upload-link">
                                        Klik of Sleep & Drop
                                    </a>
                                    <span class="text-xs text-secondary-foreground text-nowrap">
                                        SVG, PNG, JPG, GIF (max. 2MB)
                                    </span>
                                </div>
                            </div>
                            <input type="file" 
                                   name="logo" 
                                   id="logo-input" 
                                   accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml"
                                   class="hidden">
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">Ondersteunde formaten: JPEG, PNG, JPG, GIF, SVG (max. 2MB)</p>
                    </div>

                    <!-- Logo Size -->
                    <div class="mb-6">
                        <label for="logo_size" class="kt-form-label mb-2">Logo grootte (px)</label>
                        <p class="text-sm text-muted-foreground mb-3">Stel de hoogte van het logo in pixels in.</p>
                        <select name="logo_size" id="logo_size" class="kt-input" required>
                            <option value="26" {{ $logoSize == '26' ? 'selected' : '' }}>26px</option>
                            <option value="30" {{ $logoSize == '30' ? 'selected' : '' }}>30px</option>
                            <option value="34" {{ $logoSize == '34' ? 'selected' : '' }}>34px</option>
                            <option value="38" {{ $logoSize == '38' ? 'selected' : '' }}>38px</option>
                            <option value="40" {{ $logoSize == '40' ? 'selected' : '' }}>40px</option>
                        </select>
                    </div>

                    <!-- Favicon Upload -->
                    <div class="mb-6">
                        <label class="kt-form-label mb-2">Favicon</label>
                        <p class="text-sm text-muted-foreground mb-3">Het favicon wordt gebruikt in de browser tab.</p>
                        
                        <div class="flex flex-wrap sm:flex-nowrap gap-5 lg:gap-7.5 max-w-96 w-full">
                            @if($favicon && Storage::disk('public')->exists($favicon))
                                <img alt="Favicon Preview" class="w-16 h-16 mt-2 object-contain" 
                                     src="{{ route('admin.settings.favicon') }}" 
                                     id="favicon-preview"/>
                            @else
                                <img alt="Favicon Preview" class="w-16 h-16 mt-2 object-contain hidden" 
                                     src="" 
                                     id="favicon-preview"/>
                            @endif
                            <div class="flex bg-center w-full p-5 lg:p-7 bg-no-repeat bg-[length:550px] border border-input rounded-xl border-dashed branding-bg" id="favicon-upload-area">
                                <div class="flex flex-col place-items-center place-content-center text-center rounded-xl w-full">
                                    <div class="flex items-center mb-2.5">
                                        <div class="relative size-11 shrink-0">
                                            <svg class="w-full h-full stroke-primary/10 fill-light" fill="none" height="48" viewbox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z" fill=""></path>
                                                <path d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z" stroke="" stroke-opacity="0.2"></path>
                                            </svg>
                                            <div class="absolute leading-none left-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4">
                                                <i class="ki-filled ki-picture text-xl ps-px text-primary"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <a class="text-mono text-xs font-medium hover:text-primary mb-px cursor-pointer" id="favicon-upload-link">
                                        Klik of Sleep & Drop
                                    </a>
                                    <span class="text-xs text-secondary-foreground text-nowrap">
                                        ICO, PNG, JPG (max. 2MB)
                                    </span>
                                </div>
                            </div>
                            <input type="file" 
                                   name="favicon" 
                                   id="favicon-input" 
                                   accept="image/x-icon,image/png,image/jpeg"
                                   class="hidden">
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">Ondersteunde formaten: ICO, PNG, JPG (max. 2MB)</p>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logo upload handling
    const logoInput = document.getElementById('logo-input');
    const logoUploadArea = document.getElementById('logo-upload-area');
    const logoUploadLink = document.getElementById('logo-upload-link');
    const logoPreview = document.getElementById('logo-preview');
    
    if (logoInput && logoUploadArea && logoUploadLink) {
        // Click to upload
        logoUploadLink.addEventListener('click', function(e) {
            e.preventDefault();
            logoInput.click();
        });
        
        logoUploadArea.addEventListener('click', function(e) {
            if (e.target === logoUploadArea || e.target.closest('#logo-upload-area')) {
                logoInput.click();
            }
        });
        
        // Drag and drop
        logoUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            logoUploadArea.classList.add('border-primary');
        });
        
        logoUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            logoUploadArea.classList.remove('border-primary');
        });
        
        logoUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            logoUploadArea.classList.remove('border-primary');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleLogoFile(files[0]);
            }
        });
        
        // File input change
        logoInput.addEventListener('change', function(e) {
            if (this.files && this.files.length > 0) {
                handleLogoFile(this.files[0]);
            }
        });
        
        function handleLogoFile(file) {
            console.log('handleLogoFile called with file:', file.name, file.type, file.size);
            
            // Validate file type
            const allowedTypes = ['image/svg+xml', 'image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Alleen SVG, PNG, JPG en GIF bestanden zijn toegestaan.');
                logoInput.value = '';
                return;
            }
            
            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Het bestand mag maximaal 2MB groot zijn.');
                logoInput.value = '';
                return;
            }
            
            // Create preview immediately
            const reader = new FileReader();
            reader.onload = function(e) {
                logoPreview.src = e.target.result;
                logoPreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
            
            // Upload logo immediately via AJAX
            const formData = new FormData();
            formData.append('logo', file);
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                formData.append('_token', csrfToken.getAttribute('content'));
            } else {
                console.error('CSRF token not found!');
                alert('CSRF token niet gevonden. Ververs de pagina en probeer opnieuw.');
                return;
            }
            
            console.log('Starting logo upload to:', '{{ route("admin.settings.upload-logo") }}');
            fetch('{{ route("admin.settings.upload-logo") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Network response was not ok');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && logoPreview) {
                    // Update preview with server URL (add timestamp to force refresh)
                    logoPreview.src = data.logo_url;
                    logoPreview.classList.remove('hidden');
                    console.log('Logo succesvol geüpload.');
                    
                    // Update sidebar logo immediately
                    const sidebarLogos = document.querySelectorAll('.default-logo, .small-logo');
                    sidebarLogos.forEach(img => {
                        img.src = data.logo_url + '?t=' + new Date().getTime();
                    });
                } else {
                    alert(data.message || 'Er is een fout opgetreden bij het uploaden van het logo.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'Er is een fout opgetreden bij het uploaden van het logo.');
                // Keep the preview even if upload fails
            });
        }
    }
    
    // Favicon upload handling
    const faviconInput = document.getElementById('favicon-input');
    const faviconUploadArea = document.getElementById('favicon-upload-area');
    const faviconUploadLink = document.getElementById('favicon-upload-link');
    const faviconPreview = document.getElementById('favicon-preview');
    
    if (faviconInput && faviconUploadArea && faviconUploadLink) {
        // Click to upload
        faviconUploadLink.addEventListener('click', function(e) {
            e.preventDefault();
            faviconInput.click();
        });
        
        faviconUploadArea.addEventListener('click', function(e) {
            if (e.target === faviconUploadArea || e.target.closest('#favicon-upload-area')) {
                faviconInput.click();
            }
        });
        
        // Drag and drop
        faviconUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            faviconUploadArea.classList.add('border-primary');
        });
        
        faviconUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            faviconUploadArea.classList.remove('border-primary');
        });
        
        faviconUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            faviconUploadArea.classList.remove('border-primary');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFaviconFile(files[0]);
            }
        });
        
        // File input change
        faviconInput.addEventListener('change', function(e) {
            if (this.files && this.files.length > 0) {
                handleFaviconFile(this.files[0]);
            }
        });
        
        function handleFaviconFile(file) {
            console.log('handleFaviconFile called with file:', file.name, file.type, file.size);
            
            // Validate file type
            const allowedTypes = ['image/x-icon', 'image/png', 'image/jpeg', 'image/jpg'];
            if (!allowedTypes.includes(file.type)) {
                alert('Alleen ICO, PNG en JPG bestanden zijn toegestaan.');
                faviconInput.value = '';
                return;
            }
            
            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Het bestand mag maximaal 2MB groot zijn.');
                faviconInput.value = '';
                return;
            }
            
            // Create preview immediately
            const reader = new FileReader();
            reader.onload = function(e) {
                faviconPreview.src = e.target.result;
                faviconPreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
            
            // Upload favicon immediately via AJAX
            const formData = new FormData();
            formData.append('favicon', file);
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                formData.append('_token', csrfToken.getAttribute('content'));
            } else {
                console.error('CSRF token not found!');
                alert('CSRF token niet gevonden. Ververs de pagina en probeer opnieuw.');
                return;
            }
            
            console.log('Starting favicon upload to:', '{{ route("admin.settings.upload-favicon") }}');
            fetch('{{ route("admin.settings.upload-favicon") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => {
                console.log('Favicon upload response status:', response.status);
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Network response was not ok');
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Favicon upload response:', data);
                if (data.success && faviconPreview) {
                    // Update preview with server URL (add timestamp to force refresh)
                    faviconPreview.src = data.favicon_url;
                    faviconPreview.classList.remove('hidden');
                    console.log('Favicon succesvol geüpload.');
                } else {
                    alert(data.message || 'Er is een fout opgetreden bij het uploaden van het favicon.');
                }
            })
            .catch(error => {
                console.error('Error uploading favicon:', error);
                alert(error.message || 'Er is een fout opgetreden bij het uploaden van het favicon.');
                // Keep the preview even if upload fails
            });
        }
    }
    
    // Logo size change handler - save immediately on change
    const logoSizeSelect = document.getElementById('logo_size');
    if (logoSizeSelect) {
        logoSizeSelect.addEventListener('change', function() {
            const logoSize = this.value;
            console.log('Logo size changed to:', logoSize);
            
            const formData = new FormData();
            formData.append('logo_size', logoSize);
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                formData.append('_token', csrfToken.getAttribute('content'));
            } else {
                console.error('CSRF token not found!');
                return;
            }
            
            fetch('{{ route("admin.settings.logo-size.update") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Network response was not ok');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log('Logo grootte succesvol bijgewerkt.');
                    
                    // Update sidebar logo height immediately
                    const sidebarLogos = document.querySelectorAll('.default-logo, .small-logo');
                    sidebarLogos.forEach(img => {
                        img.style.height = data.logo_size + 'px';
                    });
                } else {
                    alert(data.message || 'Er is een fout opgetreden bij het bijwerken van de logo grootte.');
                }
            })
            .catch(error => {
                console.error('Error updating logo size:', error);
                alert(error.message || 'Er is een fout opgetreden bij het bijwerken van de logo grootte.');
            });
        });
    }
});
</script>
@endpush
