@extends('admin.layouts.app')

@section('title', __('Prospect').' · '.$prospect->company_name)

@section('content')
<div class="kt-container-fixed min-w-0 w-full">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono min-w-0 break-words">{{ $prospect->company_name }}</h1>
        <a href="{{ route('admin.taxi-partner-finder.index') }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            {{ __('Terug') }}
        </a>
    </div>

    <form method="POST" action="{{ route('admin.taxi-partner-finder.update', $prospect) }}" data-validate="true" novalidate>
        @csrf
        @method('PUT')
        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5">
                    <h3 class="kt-card-title mb-0">{{ __('Bedrijfsgegevens') }}</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Bedrijfsnaam') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="company_name" class="kt-input @error('company_name') border-destructive @enderror" value="{{ old('company_name', $prospect->company_name) }}" required>
                                    @error('company_name')<p class="text-sm text-destructive mt-1 mb-0">{{ $message }}</p>@enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Handelsnaam') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="trade_name" class="kt-input" value="{{ old('trade_name', $prospect->trade_name) }}">
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('KvK') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="kvk_number" class="kt-input w-40" maxlength="8" value="{{ old('kvk_number', $prospect->kvk_number) }}">
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Straat / nr') }}</td>
                                <td class="min-w-48 w-full">
                                    <div class="flex flex-wrap gap-2">
                                        <input type="text" name="street" class="kt-input grow" value="{{ old('street', $prospect->street) }}">
                                        <input type="text" name="house_number" class="kt-input w-24" value="{{ old('house_number', $prospect->house_number) }}">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Postcode / plaats') }}</td>
                                <td class="min-w-48 w-full">
                                    <div class="flex flex-wrap gap-2">
                                        <input type="text" name="postal_code" class="kt-input w-28" value="{{ old('postal_code', $prospect->postal_code) }}">
                                        <input type="text" name="city" class="kt-input grow" value="{{ old('city', $prospect->city) }}">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Provincie') }}</td>
                                <td class="min-w-48 w-full">
                                    <select name="province" class="kt-select admin-field-fit" data-kt-select="true">
                                        <option value="">—</option>
                                        @foreach($provinces as $province)
                                            <option value="{{ $province }}" @selected(old('province', $prospect->province) === $province)>{{ $province }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Telefoon') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="phone" class="kt-input" value="{{ old('phone', $prospect->phone) }}">
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('E-mail') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="email" name="email" class="kt-input" value="{{ old('email', $prospect->email) }}">
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Website') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="website" class="kt-input" value="{{ old('website', $prospect->website) }}">
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Leadstatus') }}</td>
                                <td class="min-w-48 w-full">
                                    <select name="lead_status" class="kt-select admin-field-fit" data-kt-select="true">
                                        @foreach($leadStatuses as $status)
                                            <option value="{{ $status }}" @selected(old('lead_status', $prospect->lead_status) === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Notities') }}</td>
                                <td class="min-w-48 w-full">
                                    <textarea name="notes" class="kt-textarea" rows="3">{{ old('notes', $prospect->notes) }}</textarea>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-0 w-full min-w-0">
                <a href="{{ route('admin.taxi-partner-finder.index') }}" class="kt-btn kt-btn-outline">{{ __('Annuleren') }}</a>
                <button type="submit" class="kt-btn kt-btn-primary">{{ __('Opslaan') }}</button>
            </div>
        </div>
    </form>

    <div class="grid gap-5 lg:grid-cols-2 mt-5">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h5 class="kt-card-title mb-0">{{ __('Bronvermeldingen') }}</h5>
            </div>
            <div class="kt-card-content p-5">
                @forelse($prospect->sources as $source)
                    <div class="mb-3 pb-3 border-b border-input last:border-0 last:mb-0 last:pb-0">
                        <p class="font-medium mb-1">{{ $source->source_type }}</p>
                        <p class="text-xs text-muted-foreground mb-0">
                            {{ __('Ref: :reference · geraadpleegd :date', ['reference' => $source->external_reference ?: '—', 'date' => $source->last_checked_at?->format('d-m-Y H:i') ?: '—']) }}
                        </p>
                        @if($source->source_url)
                            <a href="{{ $source->source_url }}" class="text-xs text-primary" target="_blank" rel="noopener">{{ $source->source_url }}</a>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-muted-foreground mb-0">{{ __('Geen bronnen.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h5 class="kt-card-title mb-0">{{ __('Acties') }}</h5>
            </div>
            <div class="kt-card-content p-5 flex flex-col gap-3">
                <form method="POST" action="{{ route('admin.taxi-partner-finder.push-newsletter', $prospect) }}">
                    @csrf
                    <button type="submit" class="kt-btn kt-btn-primary w-full sm:w-auto">{{ __('Naar nieuwsbrief-klantenlijst') }}</button>
                </form>
                <form method="POST" action="{{ route('admin.taxi-partner-finder.opt-out', $prospect) }}">
                    @csrf
                    <button type="submit" class="kt-btn kt-btn-outline w-full sm:w-auto">{{ __('Opt-out markeren') }}</button>
                </form>
                <p class="text-xs text-muted-foreground mb-0">
                    {{ __('KNV: :knv · verificatie: :verification · score :score · ontdekt :date', ['knv' => $prospect->knv_member_status, 'verification' => $prospect->verification_status, 'score' => $prospect->confidence_score, 'date' => $prospect->discovered_at?->format('d-m-Y') ?: '—']) }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
