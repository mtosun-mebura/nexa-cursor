@extends('admin.layouts.app')

@section('title', 'Vacature Details')

@section('content')
<style>
    :root {
        --primary-color: #9c27b0;
        --primary-light: #ba68c8;
        --primary-dark: #7b1fa2;
        --primary-hover: #ab47bc;
    }
</style>

@include('admin.material-design-template')


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5 >
                        <i class="fas fa-briefcase"></i> Vacature Details
                    </h5>
                    <div class="material-header-actions">
                        <a href="{{ route('admin.vacancies.edit', $vacancy) }}" class="material-btn material-btn-warning">
                            <i class="fas fa-edit"></i> Bewerken
                        </a>
                        <a href="{{ route('admin.vacancies.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4>{{ $vacancy->title }}</h4>
                            <p class="material-section-title">
                                <i class="fas fa-building"></i> {{ $vacancy->company->name }}
                                @if($vacancy->location)
                                    <span class="ms-3"><i class="fas fa-map-marker-alt"></i> {{ $vacancy->location }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="material-badge material-badge-{{ $vacancy->status == 'active' ? 'success' : ($vacancy->status == 'inactive' ? 'secondary' : 'warning') }}">
                                {{ ucfirst($vacancy->status) }}
                            </span>
                        </div>
                    </div>

                    <hr class="material-divider">

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="material-section-title">Vacature Informatie</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>ID:</strong></td>
                                    <td>{{ $vacancy->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Bedrijf:</strong></td>
                                    <td>{{ $vacancy->company->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Categorie:</strong></td>
                                    <td>{{ $vacancy->category->name ?? 'Geen categorie' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Locatie:</strong></td>
                                    <td>{{ $vacancy->location ?? 'Niet opgegeven' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Type Werk:</strong></td>
                                    <td>
                                        @if($vacancy->employment_type)
                                            @switch($vacancy->employment_type)
                                                @case('full-time')
                                                    Volledig
                                                    @break
                                                @case('part-time')
                                                    Deeltijd
                                                    @break
                                                @case('contract')
                                                    Contract
                                                    @break
                                                @case('temporary')
                                                    Tijdelijk
                                                    @break
                                                @case('internship')
                                                    Stage
                                                    @break
                                                @default
                                                    {{ $vacancy->employment_type }}
                                            @endswitch
                                        @else
                                            Niet opgegeven
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="material-section-title">Salaris & Status</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $vacancy->status == 'active' ? 'success' : ($vacancy->status == 'inactive' ? 'secondary' : 'warning') }}">
                                            {{ ucfirst($vacancy->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Salaris:</strong></td>
                                    <td>
                                        @if($vacancy->salary_min && $vacancy->salary_max)
                                            €{{ number_format($vacancy->salary_min) }} - €{{ number_format($vacancy->salary_max) }}
                                        @elseif($vacancy->salary_min)
                                            Vanaf €{{ number_format($vacancy->salary_min) }}
                                        @elseif($vacancy->salary_max)
                                            Tot €{{ number_format($vacancy->salary_max) }}
                                        @else
                                            Niet opgegeven
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Aangemaakt:</strong></td>
                                    <td>{{ $vacancy->created_at->format('d-m-Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Bijgewerkt:</strong></td>
                                    <td>{{ $vacancy->updated_at->format('d-m-Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr class="material-divider">

                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="material-section-title">Beschrijving</h6>
                            <div class="material-card">
                                <div class="card-body">
                                    {!! nl2br(e($vacancy->description)) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($vacancy->requirements)
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="material-section-title">Vereisten</h6>
                                <div class="material-card">
                                    <div class="card-body">
                                        {!! nl2br(e($vacancy->requirements)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($vacancy->benefits)
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="material-section-title">Voordelen</h6>
                                <div class="material-card">
                                    <div class="card-body">
                                        {!! nl2br(e($vacancy->benefits)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
