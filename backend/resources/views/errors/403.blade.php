@extends('admin.layouts.app')

@section('title', 'Toegang Geweigerd')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-body text-center">
                    <div class="error-content">
                        <div class="error-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h2>403 - Toegang Geweigerd</h2>
                        <p class="error-message">
                            Je hebt geen rechten om deze pagina te bekijken of deze actie uit te voeren.
                        </p>
                        <p class="error-description">
                            Neem contact op met je beheerder als je denkt dat dit een fout is.
                        </p>
                        <div class="error-actions">
                            @php
                                $currentUrl = request()->url();
                                $backUrl = route('admin.dashboard'); // Default fallback
                                
                                // Determine where to go back based on current URL
                                if (str_contains($currentUrl, '/admin/vacancies/')) {
                                    if (auth()->user()->hasRole('super-admin') || auth()->user()->can('view-vacancies')) {
                                        $backUrl = route('admin.vacancies.index');
                                    }
                                } elseif (str_contains($currentUrl, '/admin/companies/')) {
                                    if (auth()->user()->hasRole('super-admin') || auth()->user()->can('view-companies')) {
                                        $backUrl = route('admin.companies.index');
                                    }
                                } elseif (str_contains($currentUrl, '/admin/users/')) {
                                    if (auth()->user()->hasRole('super-admin') || auth()->user()->can('view-users')) {
                                        $backUrl = route('admin.users.index');
                                    }
                                } elseif (str_contains($currentUrl, '/admin/categories/')) {
                                    if (auth()->user()->hasRole('super-admin') || auth()->user()->can('view-categories')) {
                                        $backUrl = route('admin.categories.index');
                                    }
                                } elseif (str_contains($currentUrl, '/admin/notifications/')) {
                                    if (auth()->user()->hasRole('super-admin') || auth()->user()->can('view-notifications')) {
                                        $backUrl = route('admin.notifications.index');
                                    }
                                } elseif (str_contains($currentUrl, '/admin/matches/')) {
                                    if (auth()->user()->hasRole('super-admin') || auth()->user()->can('view-matches')) {
                                        $backUrl = route('admin.matches.index');
                                    }
                                } elseif (str_contains($currentUrl, '/admin/interviews/')) {
                                    if (auth()->user()->hasRole('super-admin') || auth()->user()->can('view-interviews')) {
                                        $backUrl = route('admin.interviews.index');
                                    }
                                } elseif (str_contains($currentUrl, '/admin/roles/')) {
                                    if (auth()->user()->hasRole('super-admin') || auth()->user()->can('view-roles')) {
                                        $backUrl = route('admin.roles.index');
                                    }
                                } elseif (str_contains($currentUrl, '/admin/permissions/')) {
                                    if (auth()->user()->hasRole('super-admin') || auth()->user()->can('view-permissions')) {
                                        $backUrl = route('admin.permissions.index');
                                    }
                                } elseif (str_contains($currentUrl, '/admin/candidates/')) {
                                    if (auth()->user()->hasRole('super-admin') || auth()->user()->can('view-candidates')) {
                                        $backUrl = route('admin.candidates.index');
                                    }
                                } elseif (str_contains($currentUrl, '/admin/payment-providers/')) {
                                    if (auth()->user()->hasRole('super-admin') || auth()->user()->can('view-payment-providers')) {
                                        $backUrl = route('admin.payment-providers.index');
                                    }
                                } elseif (str_contains($currentUrl, '/admin/email-templates/')) {
                                    if (auth()->user()->hasRole('super-admin') || auth()->user()->can('view-email-templates')) {
                                        $backUrl = route('admin.email-templates.index');
                                    }
                                }
                            @endphp
                            <a href="{{ $backUrl }}" class="material-btn material-btn-secondary me-3">
                                <i class="fas fa-arrow-left me-2"></i> Terug
                            </a>
                            <a href="{{ route('admin.dashboard') }}" class="material-btn material-btn-primary">
                                <i class="fas fa-home me-2"></i> Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .error-content {
        padding: 60px 20px;
    }
    
    .error-icon {
        font-size: 4rem;
        color: #f44336;
        margin-bottom: 2rem;
    }
    
    .error-content h2 {
        color: #333;
        margin-bottom: 1rem;
        font-weight: 600;
    }
    
    .error-message {
        font-size: 1.2rem;
        color: #666;
        margin-bottom: 1rem;
        font-weight: 500;
    }
    
    .error-description {
        color: #888;
        margin-bottom: 2rem;
    }
    
    .error-actions {
        margin-top: 2rem;
    }
    
    .material-btn {
        display: inline-flex;
        align-items: center;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .material-btn-primary {
        background: linear-gradient(135deg, #2196f3 0%, #64b5f6 100%);
        color: white;
    }
    
    .material-btn-primary:hover {
        background: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
    }
    
    .material-btn-secondary {
        background: #f5f5f5;
        color: #333;
        border: 1px solid #ddd;
    }
    
    .material-btn-secondary:hover {
        background: #e0e0e0;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
</style>
@endsection