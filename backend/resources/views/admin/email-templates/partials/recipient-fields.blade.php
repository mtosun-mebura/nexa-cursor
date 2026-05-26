@php
    $emailTemplate = $emailTemplate ?? null;
@endphp
{{-- Ontvanger: opgeslagen bij template; gebruikt bij testmail en bij informatieaanvraag van het websiteformulier. --}}
<tr>
    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-2">Ontvanger</td>
    <td class="min-w-48 w-full">
        <div class="flex flex-col gap-3">
            <label class="kt-label flex items-center gap-2">
                <input type="radio" name="recipient_type" value="user" {{ old('recipient_type', $emailTemplate?->recipient_type) === 'user' ? 'checked' : '' }} class="recipient-type-radio main-form-recipient">
                Gebruiker uit lijst
            </label>
            <div class="recipient-user-wrap main-form-recipient-wrap">
                <select class="kt-select @error('recipient_user_id') border-destructive @enderror" name="recipient_user_id">
                    <option value="">— Kies een gebruiker —</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ old('recipient_user_id', $emailTemplate?->recipient_user_id) == $u->id ? 'selected' : '' }}>
                            {{ $u->first_name }} {{ $u->last_name }} ({{ $u->email }})
                        </option>
                    @endforeach
                </select>
                @if($users->isEmpty())
                    <p class="text-xs text-muted-foreground mt-1">Geen gebruikers in dit bedrijf. Gebruik een vrije e-mailadres.</p>
                @endif
            </div>
            <label class="kt-label flex items-center gap-2 mt-2">
                <input type="radio" name="recipient_type" value="email" {{ old('recipient_type', $emailTemplate?->recipient_type) === 'email' ? 'checked' : '' }} class="recipient-type-radio main-form-recipient">
                Vrije e-mailadres
            </label>
            <div class="recipient-email-wrap main-form-recipient-wrap">
                <input type="email" class="kt-input @error('recipient_email') border-destructive @enderror"
                       name="recipient_email" placeholder="bijv. info@voorbeeld.nl" value="{{ old('recipient_email', $emailTemplate?->recipient_email ?? '') }}">
                @error('recipient_email')
                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <p class="text-xs text-muted-foreground mt-2">De e-mail (testmail en informatieaanvragen van het websiteformulier) wordt naar dit adres verstuurd.</p>
    </td>
</tr>
