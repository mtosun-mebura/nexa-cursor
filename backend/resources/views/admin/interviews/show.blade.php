@extends('admin.layouts.app')

@section('title', 'Interview Details - #' . $interview->id)

@section('content')
<style>
    :root {
        --primary-color: #607d8b;
        --primary-light: #90a4ae;
        --primary-dark: #455a64;
        --primary-hover: #78909c;
        --success-color: #4caf50;
        --warning-color: #ff9800;
        --danger-color: #f44336;
        --info-color: #2196f3;
        --secondary-color: #757575;
        --light-bg: #f5f5f5;
        --border-color: #e0e0e0;
        --text-primary: #212121;
        --text-secondary: #757575;
        --shadow: 0 2px 4px rgba(0,0,0,0.1);
        --shadow-hover: 0 4px 8px rgba(0,0,0,0.15);
        --border-radius: 8px;
        --transition: all 0.3s ease;
    }

    .material-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin-bottom: 24px;
        overflow: hidden;
        transition: var(--transition);
    }

    .material-card:hover {
        box-shadow: var(--shadow-hover);
    }

    .card-header {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .card-header h5 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .material-header-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .material-btn {
        padding: 10px 20px;
        border: none;
        border-radius: var(--border-radius);
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
        cursor: pointer;
        font-size: 14px;
        height: 44px;
        min-height: 44px;
    }

    .material-btn-warning {
        background: var(--warning-color);
        color: white;
    }

    .material-btn-warning:hover {
        background: #f57c00;
        color: white;
        transform: translateY(-2px);
    }

    .material-btn-secondary {
        background: var(--light-bg);
        color: var(--text-primary);
    }

    .material-btn-secondary:hover {
        background: #e0e0e0;
        color: var(--text-primary);
        transform: translateY(-2px);
    }

    .card-body {
        padding: 24px;
    }

    .interview-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: var(--border-radius);
        padding: 24px;
        margin-bottom: 24px;
        border-left: 4px solid var(--primary-color);
    }

    .interview-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 12px;
        line-height: 1.2;
    }

    .interview-meta {
        display: flex;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-secondary);
        font-size: 14px;
    }

    .meta-item i {
        color: var(--primary-color);
        width: 16px;
    }

    .interview-status {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .interview-status:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .status-scheduled {
        background: linear-gradient(135deg, #e3f2fd 0%, #64b5f6 100%);
        color: #1976d2;
        border: 2px solid #64b5f6;
    }

    .status-completed {
        background: linear-gradient(135deg, #f1f8e9 0%, #81c784 100%);
        color: #388e3c;
        border: 2px solid #81c784;
    }

    .status-cancelled {
        background: linear-gradient(135deg, #ffcdd2 0%, #e57373 100%);
        color: #d32f2f;
        border: 2px solid #e57373;
    }

    .status-pending {
        background: linear-gradient(135deg, #fff3e0 0%, #ffcc02 100%);
        color: #f57c00;
        border: 2px solid #ffcc02;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        margin-bottom: 24px;
    }

    .info-section {
        background: white;
        border-radius: var(--border-radius);
        padding: 20px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--primary-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
    }

    .info-table tr {
        border-bottom: 1px solid var(--border-color);
    }

    .info-table tr:last-child {
        border-bottom: none;
    }

    .info-table td {
        padding: 12px 0;
        vertical-align: top;
    }

    .info-table td:first-child {
        font-weight: 600;
        color: var(--text-primary);
        width: 140px;
        min-width: 140px;
    }

    .info-table td:last-child {
        color: var(--text-secondary);
    }

    .material-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }

    .material-badge-primary {
        background: var(--primary-color);
        color: white;
    }

    .material-badge-secondary {
        background: var(--secondary-color);
        color: white;
    }

    .material-badge-success {
        background: var(--success-color);
        color: white;
    }

    .material-badge-warning {
        background: var(--warning-color);
        color: white;
    }

    .material-badge-danger {
        background: var(--danger-color);
        color: white;
    }

    .material-badge-info {
        background: var(--info-color);
        color: white;
    }

    .material-text-muted {
        color: var(--text-secondary);
        font-style: italic;
    }
</style>

<div class="container-fluid">
    <div class="material-card">
        <div class="card-header">
            <h5>
                <i class="fas fa-calendar-alt"></i>
                Interview Details: #{{ $interview->id }}
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.interviews.edit', $interview) }}" class="material-btn material-btn-warning me-2">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                <a href="{{ route('admin.interviews.index') }}" class="material-btn material-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Interview Header Section -->
            <div class="interview-header">
                <h1 class="interview-title">Interview #{{ $interview->id }}</h1>
                <div class="interview-meta">
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span>{{ $interview->match->user->first_name }} {{ $interview->match->user->last_name }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-briefcase"></i>
                        <span>{{ $interview->match->vacancy->title }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-building"></i>
                        <span>{{ $interview->match->vacancy->company->name }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>{{ $interview->scheduled_at->format('d-m-Y H:i') }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>{{ $interview->location ?? 'Locatie onbekend' }}</span>
                    </div>
                </div>
                <div class="interview-status status-{{ $interview->status }}">
                    <i class="fas fa-circle"></i>
                    {{ ucfirst($interview->status) }}
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-user"></i>
                        Kandidaat Informatie
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>Naam</td>
                            <td>{{ $interview->match->user->first_name }} {{ $interview->match->user->last_name }}</td>
                        </tr>
                        <tr>
                            <td>E-mail</td>
                            <td>{{ $interview->match->user->email }}</td>
                        </tr>
                        <tr>
                            <td>Telefoon</td>
                            <td>{{ $interview->match->user->phone ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>Bedrijf</td>
                            <td>{{ $interview->match->user->company->name ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-briefcase"></i>
                        Vacature Informatie
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>Titel</td>
                            <td>{{ $interview->match->vacancy->title }}</td>
                        </tr>
                        <tr>
                            <td>Bedrijf</td>
                            <td>{{ $interview->match->vacancy->company->name }}</td>
                        </tr>
                        <tr>
                            <td>Locatie</td>
                            <td>{{ $interview->match->vacancy->location ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>Match Score</td>
                            <td>{{ $interview->match->match_score ?? 'N/A' }}%</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-calendar-check"></i>
                        Interview Details
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>Type</td>
                            <td>{{ ucfirst($interview->type) }}</td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <span class="material-badge material-badge-{{ $interview->status == 'scheduled' ? 'info' : ($interview->status == 'completed' ? 'success' : ($interview->status == 'cancelled' ? 'danger' : 'warning')) }}">
                                    {{ ucfirst($interview->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Datum & Tijd</td>
                            <td>{{ $interview->scheduled_at->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td>Locatie</td>
                            <td>{{ $interview->location ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Duur</td>
                            <td>{{ $interview->duration ?? 'Niet opgegeven' }}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-cog"></i>
                        Systeem Informatie
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>ID</td>
                            <td>{{ $interview->id }}</td>
                        </tr>
                        <tr>
                            <td>Aangemaakt op</td>
                            <td>{{ $interview->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td>Laatst bijgewerkt</td>
                            <td>{{ $interview->updated_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($interview->notes || $interview->feedback)
                <div class="info-grid">
                    <div class="info-section">
                        <h6 class="section-title">
                            <i class="fas fa-comments"></i>
                            Notities & Feedback
                        </h6>
                        <table class="info-table">
                            @if($interview->notes)
                                <tr>
                                    <td>Notities</td>
                                    <td>{{ $interview->notes }}</td>
                                </tr>
                            @endif
                            @if($interview->feedback)
                                <tr>
                                    <td>Feedback</td>
                                    <td>{{ $interview->feedback }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
