<x-email-card
    heading="Nieuw contactformulierbericht"
    page-title="Nieuw contactformulier bericht"
    :logo-html="$nexaLogoHtml ?? \App\Support\NexaBranding::emailLogoPreviewHtml()"
>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Er is een nieuw bericht binnengekomen via het contactformulier.</p>
    <table role="presentation" width="100%" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#e8eef5;border:1px solid #cbd5e1;border-radius:12px;margin:0 0 20px;">
        <tr><td style="padding:16px 18px;border-radius:12px;background-color:#e8eef5;">
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Voornaam:</strong> {{ $first_name }}</p>
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Achternaam:</strong> {{ $last_name }}</p>
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>E-mailadres:</strong> <a href="mailto:{{ $email }}" style="color:#0f172a;">{{ $email }}</a></p>
            @if($phone)
            <p style="margin:0;font-size:15px;line-height:1.6;"><strong>Telefoonnummer:</strong> {{ $phone }}</p>
            @endif
        </td></tr>
    </table>
    <p style="margin:0 0 8px;font-size:12px;color:#0f172a;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Bericht</p>
    <p style="margin:0;font-size:15px;line-height:1.6;white-space:pre-wrap;">{!! $user_message !!}</p>
</x-email-card>
