@extends('admin.layouts.app')

@section('title', 'Nieuwe Job Configuratie')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Nieuwe Job Configuratie
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.job-configurations.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.job-configurations.store') }}" method="POST">
        @csrf

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Configuratie Informatie</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Type *</td>
                            <td class="min-w-48 w-full">
                                <select class="kt-select @error('type_id') border-destructive @enderror" name="type_id" data-kt-select="true" required>
                                    <option value="">Selecteer type</option>
                                    @foreach($types ?? [] as $type)
                                        <option value="{{ $type->id }}" {{ old('type_id') == $type->id ? 'selected' : '' }}>
                                            {{ $type->display_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="text-xs text-muted-foreground mt-1">Selecteer het type configuratie</div>
                                @error('type_id')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Waarde *</td>
                            <td>
                                <input type="text" class="kt-input @error('value') border-destructive @enderror" name="value" value="{{ old('value') }}" required maxlength="100" placeholder="Bijv. Fulltime, 08:00-16:00, Open">
                                <div class="text-xs text-muted-foreground mt-1">Voer de waarde in (max. 100 karakters)</div>
                                @error('value')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Bedrijf</td>
                            <td>
                                <select class="kt-select @error('company_id') border-destructive @enderror" name="company_id">
                                    <option value="">Globaal (voor alle bedrijven)</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="text-xs text-muted-foreground mt-1">Laat leeg voor een globale configuratie, of selecteer een specifiek bedrijf</div>
                                @error('company_id')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.job-configurations.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Opslaan
                </button>
            </div>
        </div>
    </form>
</div>

@endsection

