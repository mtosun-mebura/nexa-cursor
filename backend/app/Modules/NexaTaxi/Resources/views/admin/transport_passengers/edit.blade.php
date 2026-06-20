@extends('admin.layouts.app')

@section('title', $passenger->full_name)

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">{{ $passenger->full_name }}</h1>
        <p class="text-sm text-muted-foreground pt-2">{{ $contract->name }} · {{ $customer->name }}</p>
        <div class="pt-3">
            <a href="{{ $backUrl }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!empty($groupNames))
    <div class="kt-alert kt-alert-info mb-5" role="status">
        <i class="ki-filled ki-information-2 me-2"></i>
        Lid van groep(en): {{ implode(', ', $groupNames) }}.
    </div>
    @endif

    <form method="POST" action="{{ route('admin.taxi.transport_passengers.update', [$customer->id, $contract->id, $passenger->id]) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="return" value="{{ old('return', $backUrl) }}">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Passagiergegevens</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        @include('taxi::admin.transport_passengers.partials.form-fields', ['passenger' => $passenger])
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5 w-full min-w-0">
            <a href="{{ $backUrl }}" class="kt-btn kt-btn-outline">Annuleren</a>
            <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
        </div>
    </form>
</div>
@endsection
