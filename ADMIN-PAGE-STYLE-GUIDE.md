# Admin Pagina Opmaak Standaard

Dit document beschrijft de standaard opmaak voor admin pagina's in het Nexa Skillmatching platform. Volg deze richtlijnen voor alle nieuwe admin pagina's om consistentie te waarborgen.

## Structuur

### 1. Basis Layout
```blade
@extends('admin.layouts.app')

@section('title', 'Pagina Titel')

@section('content')
<div class="kt-container-fixed">
    <!-- Content hier -->
</div>
@endsection
```

### 2. Header Sectie
```blade
<div class="flex flex-col gap-5 pb-7.5">
    <div class="flex flex-wrap items-center justify-between gap-5">
        <div class="flex items-center gap-3">
            <i class="[icon-class] text-2xl text-primary"></i>
            <div>
                <h1 class="text-xl font-medium leading-none text-mono">
                    Pagina Titel
                </h1>
                <p class="text-sm text-muted-foreground mt-0.5">Ondertitel (optioneel)</p>
            </div>
        </div>
    </div>
    <div class="flex items-center">
        <a href="{{ route('admin.[route].index') }}" class="kt-btn kt-btn-outline">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug
        </a>
    </div>
</div>
```

### 3. Formulier Structuur
```blade
<form action="{{ route('admin.[route].store') }}" method="POST" data-validate="true">
    @csrf

    <div class="grid gap-5 lg:gap-7.5">
        <x-error-card :errors="$errors" />

        <!-- Success/Error Alerts -->
        @if(session('success'))
            <div class="kt-alert kt-alert-success" id="success-alert" role="alert">
                <i class="ki-filled ki-check-circle me-2"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="kt-alert kt-alert-danger" id="error-alert" role="alert">
                <i class="ki-filled ki-cross-circle me-2"></i>
                {{ session('error') }}
            </div>
        @endif

        <!-- Cards -->
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Card Titel</h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <!-- Content hier -->
            </div>
            <!-- Optionele footer -->
            <div class="kt-card-footer flex items-center justify-end gap-2 pt-3">
                <!-- Buttons hier -->
            </div>
        </div>
    </div>
</form>
```

## Componenten

### Cards
- Gebruik `kt-card min-w-full` voor volledige breedte cards
- Header: `kt-card-header` met `kt-card-title`
- Body: `kt-card-body` of `kt-card-table kt-scrollable-x-auto pb-3` voor tabellen
- Footer: `kt-card-footer flex items-center justify-end gap-2 pt-3` voor acties

### Tabellen
```blade
<table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
    <thead>
        <tr>
            <th class="min-w-[200px] text-secondary-foreground font-normal">Kolom</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="text-secondary-foreground font-normal">Label</td>
            <td class="min-w-48 w-full">
                <!-- Input veld -->
            </td>
        </tr>
    </tbody>
</table>
```

### Checkboxes
Gebruik de volgende styling voor checkboxes:

```css
.module-config-checkbox,
.kt-checkbox {
    width: 20px !important;
    height: 20px !important;
    min-width: 20px !important;
    min-height: 20px !important;
    border-width: 1px !important;
    border-color: #555555;
    color: #555555;
    padding-right: 0 !important;
    cursor: pointer;
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    background-color: transparent !important;
    transition: all 0.2s !important;
}

.module-config-checkbox:hover,
.kt-checkbox:hover {
    border-color: #555555;
    background-color: rgba(85, 85, 85, 0.1);
}

.module-config-checkbox:checked,
.kt-checkbox:checked {
    border-color: #10b981 !important;
    background-color: transparent !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20' fill='none'%3E%3Cpath fill-rule='evenodd' d='M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z' clip-rule='evenodd' fill='%2310b981'/%3E%3C/svg%3E") !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
    background-size: 20px 20px !important;
    border-width: 1px !important;
    color: #10b981 !important;
}
```

### Input Velden
```blade
<input type="text" 
       class="kt-input @error('field') border-destructive @enderror" 
       name="field" 
       value="{{ old('field') }}" 
       required>
@error('field')
    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
@enderror
```

### Code Elementen
Voor code elementen (routes, technische namen, etc.) gebruik dark mode vriendelijke styling:

```blade
<code class="px-1 py-0.5 rounded text-xs">{{ $route }}</code>
```

Met bijbehorende CSS (in `@push('styles')`):
```css
/* Code element styling voor dark mode compatibiliteit */
code {
    background-color: rgba(0, 0, 0, 0.05) !important;
    color: #1f2937 !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
}

.dark code {
    background-color: rgba(255, 255, 255, 0.1) !important;
    color: #e5e7eb !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
}
```

**Belangrijk**: Gebruik **niet** `bg-muted` voor code elementen, dit werkt niet goed in dark mode. Gebruik in plaats daarvan de bovenstaande CSS met expliciete dark mode styling.

### Buttons
```blade
<!-- Primary button -->
<button type="submit" class="kt-btn kt-btn-primary">
    <i class="ki-filled ki-check me-2"></i>
    Opslaan
</button>

<!-- Outline button -->
<a href="{{ route('admin.[route].index') }}" class="kt-btn kt-btn-outline">
    Annuleren
</a>
```

### Info Cards
```blade
<div class="kt-card min-w-full" style="background-color: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.25);">
    <div class="kt-card-content p-5 lg:px-7 lg:py-6">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <i class="ki-filled ki-information-5 text-2xl" style="color: rgb(59, 130, 246);"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="font-semibold mb-1 text-foreground">Titel</h4>
                <p class="text-sm text-muted-foreground">Beschrijving</p>
            </div>
        </div>
    </div>
</div>
```

## Spacing & Layout

- Container: `kt-container-fixed`
- Header gap: `gap-5 pb-7.5`
- Form grid: `grid gap-5 lg:gap-7.5`
- Card spacing: `min-w-full` voor volledige breedte
- Button gaps: `gap-2` voor button groepen

## Kleuren & Typography

- Primary text: `text-foreground`
- Secondary text: `text-muted-foreground`
- Labels: `text-secondary-foreground font-normal`
- Headers: `text-xl font-medium leading-none text-mono`
- Card titles: `kt-card-title`
- Icons: `text-2xl text-primary` voor grote icons, `text-lg` voor kleine

## Voorbeeld: Volledige Configuratie Pagina

Zie `backend/resources/views/admin/modules/config.blade.php` voor een volledig voorbeeld van een goed opgemaakte admin pagina volgens deze standaard.

## Best Practices

1. **Gebruik altijd** `x-error-card` component voor formulier validatie errors
2. **Gebruik altijd** `data-validate="true"` op formulieren die validatie nodig hebben
3. **Gebruik altijd** `old()` helper voor input values bij formulier errors
4. **Gebruik altijd** `@error` directive voor field-specifieke error messages
5. **Gebruik altijd** `kt-card min-w-full` voor cards (niet `max-w-2xl` of andere beperkingen)
6. **Gebruik altijd** tabel structuur voor formulier velden in cards
7. **Gebruik altijd** `kt-card-footer` voor actie buttons onderaan cards
8. **Gebruik altijd** consistente checkbox styling zoals hierboven beschreven
9. **Gebruik altijd** info cards met blauwe achtergrond voor uitleg/help tekst
10. **Gebruik altijd** `flex items-center justify-end gap-2` voor button groepen
