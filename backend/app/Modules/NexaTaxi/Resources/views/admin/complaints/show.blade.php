@extends('admin.layouts.app')

@section('title', __('Klacht #:id', ['id' => $complaint->id]))

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">{{ __('Klacht #:id', ['id' => $complaint->id]) }}</h1>
        <a href="{{ route('admin.taxi.complaints.index') }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            {{ __('Terug') }}
        </a>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">{{ __('Details') }}</h3>
            </div>
            <div class="kt-card-content p-0">
                <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Status') }}</td>
                            <td class="min-w-48 w-full">{{ __($detail['status_label']) }}</td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Categorie') }}</td>
                            <td class="min-w-48 w-full">{{ __($detail['category_label']) }}</td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Onderwerp') }}</td>
                            <td class="min-w-48 w-full">{{ $detail['subject'] ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Omschrijving') }}</td>
                            <td class="min-w-48 w-full whitespace-pre-wrap">{{ $detail['description'] }}</td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Klant') }}</td>
                            <td class="min-w-48 w-full">
                                {{ $detail['customer_name'] ?: '—' }}<br>
                                <span class="text-xs">{{ $detail['customer_email'] ?: '—' }} · {{ $detail['customer_phone'] ?: '—' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Bedrijf') }}</td>
                            <td class="min-w-48 w-full">{{ $detail['company']['name'] ?: ('#'.$detail['company']['id']) }}</td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Chauffeur') }}</td>
                            <td class="min-w-48 w-full">
                                {{ $detail['driver']['name'] ?: ('#'.$detail['driver']['id']) }}
                                @if(!empty($detail['driver']['blocked']))
                                    <span class="ms-2 text-destructive text-xs font-medium">{{ __('Geblokkeerd') }}</span>
                                @endif
                                <div class="text-xs">{{ $detail['driver']['email'] }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Voertuig') }}</td>
                            <td class="min-w-48 w-full">
                                {{ $detail['vehicle']['name'] ?: '—' }}
                                @if(!empty($detail['vehicle']['license_plate']))
                                    · {{ $detail['vehicle']['license_plate'] }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Rit') }}</td>
                            <td class="min-w-48 w-full">
                                #{{ $detail['ride']['id'] }}<br>
                                {{ $detail['ride']['pickup_address'] }} → {{ $detail['ride']['dropoff_address'] }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('complaints.manage'))
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">{{ __('Status bijwerken') }}</h3>
            </div>
            <div class="kt-card-content p-0">
                <form method="POST" action="{{ route('admin.taxi.complaints.status', $complaint->id) }}" class="px-3 sm:px-5 pb-5" data-validate="true" novalidate>
                    @csrf
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Status') }}</td>
                            <td class="min-w-48 w-full">
                                <select name="status" class="kt-select admin-field-fit" data-kt-select="true" required>
                                    @foreach($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $complaint->status) === $value)>{{ __($label) }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Interne notities') }}</td>
                            <td class="min-w-48 w-full">
                                <textarea name="admin_notes" class="kt-textarea" rows="3">{{ old('admin_notes', $complaint->admin_notes) }}</textarea>
                            </td>
                        </tr>
                    </table>
                    <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5">
                        <button type="submit" class="kt-btn kt-btn-primary">{{ __('Opslaan') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">{{ __('Bericht naar chauffeur') }}</h3>
            </div>
            <div class="kt-card-content p-0">
                <form method="POST" action="{{ route('admin.taxi.complaints.message', $complaint->id) }}" class="px-3 sm:px-5 pb-5" data-validate="true" novalidate>
                    @csrf
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Titel') }}</td>
                            <td class="min-w-48 w-full">
                                <input type="text" name="title" class="kt-input" required maxlength="160" value="{{ old('title', __('Melding van beheer')) }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Bericht') }}</td>
                            <td class="min-w-48 w-full">
                                <textarea name="body" class="kt-textarea" rows="4" required maxlength="4000">{{ old('body') }}</textarea>
                                <p class="text-xs text-muted-foreground mt-1 mb-0">{{ __('Verschijnt in de chauffeur-app onder het bel-icoon.') }}</p>
                            </td>
                        </tr>
                    </table>
                    <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5">
                        <button type="submit" class="kt-btn kt-btn-primary">{{ __('Versturen') }}</button>
                    </div>
                </form>
            </div>
        </div>

        @if(empty($detail['driver']['blocked']))
        <div class="kt-card w-full min-w-0 border-destructive/30">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0 text-destructive">{{ __('Chauffeur blokkeren') }}</h3>
            </div>
            <div class="kt-card-content p-0">
                <form method="POST" action="{{ route('admin.taxi.complaints.block', $complaint->id) }}" class="px-3 sm:px-5 pb-5" data-validate="true" novalidate onsubmit="return confirm(@json(__('Chauffeur blokkeren? De app-sessie wordt meteen beëindigd.')));">
                    @csrf
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Reden') }}</td>
                            <td class="min-w-48 w-full">
                                <input type="text" name="reason" class="kt-input" maxlength="500" placeholder="{{ __('Optioneel') }}" value="{{ old('reason') }}">
                            </td>
                        </tr>
                    </table>
                    <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5">
                        <button type="submit" class="kt-btn kt-btn-destructive">{{ __('Blokkeren') }}</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
