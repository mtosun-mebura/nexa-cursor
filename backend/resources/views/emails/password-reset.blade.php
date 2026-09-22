<x-email-card
    heading="Wachtwoord resetten"
    page-title="Wachtwoord resetten"
    :logo-html="$nexaLogoHtml ?? \App\Support\NexaBranding::emailLogoPreviewHtml()"
>
    <p style="margin:0 0 16px;font-size:16px;">Beste {{ $user->first_name }} {{ $user->last_name }},</p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
        Je hebt een wachtwoordreset aangevraagd voor je NEXA Suite-account.
        Klik op de onderstaande knop om een nieuw wachtwoord in te stellen.
    </p>
    <p style="margin:0 0 18px;text-align:center;">
        <a href="{{ $resetUrl }}" style="display:inline-block;background-color:#ea580c;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 22px;border-radius:6px;">
            <span style="color:#ffffff;">Wachtwoord resetten</span>
        </a>
    </p>
    <table role="presentation" width="100%" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#e8eef5;border:1px solid #cbd5e1;border-radius:12px;margin:0 0 20px;">
        <tr><td style="padding:16px 18px;border-radius:12px;background-color:#e8eef5;">
            <p style="margin:0 0 8px;font-size:12px;color:#0f172a;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Of kopieer deze link</p>
            <p style="margin:0;font-size:13px;line-height:1.5;word-break:break-all;color:#334155;">{{ $resetUrl }}</p>
        </td></tr>
    </table>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.6;">Deze link is 60 minuten geldig.</p>
    <p style="margin:0;font-size:13px;color:#6b7280;text-align:center;line-height:1.6;">Heb je deze aanvraag niet gedaan? Dan kun je deze e-mail negeren. Je wachtwoord blijft ongewijzigd.</p>
</x-email-card>
