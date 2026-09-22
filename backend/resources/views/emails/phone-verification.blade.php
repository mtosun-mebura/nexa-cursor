<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Bevestig je telefoonnummer</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#f4f4f4;color:#111827;color-scheme:light;">
<table role="presentation" style="width:100%;border-collapse:collapse;">
<tr><td style="padding:24px 12px;">
<table role="presentation" width="100%" style="width:100%;max-width:600px;margin:0 auto;background-color:#ffffff;border:1px solid #d1d5db;border-radius:8px;border-collapse:separate;border-spacing:0;overflow:hidden;">
<tr>
    <td bgcolor="#0f172a" style="padding:28px 32px;background-color:#0f172a;">
        {!! $nexaLogoHtml ?? \App\Support\NexaBranding::emailLogoPreviewHtml() !!}
        <h1 style="margin:0;font-size:22px;line-height:1.3;color:#ffffff;">Bevestig je telefoonnummer</h1>
    </td>
</tr>
<tr>
    <td style="padding:28px 32px;">
        <p style="margin:0 0 16px;font-size:16px;">Beste {{ $user->first_name }} {{ $user->last_name }},</p>
        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
            Klik op de onderstaande knop om te bevestigen dat telefoonnummer <strong>{{ $user->phone }}</strong> van u is.
        </p>
        <p style="margin:0 0 18px;text-align:center;">
            <a href="{{ $verificationUrl }}" style="display:inline-block;background-color:#ea580c;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 22px;border-radius:6px;">
                <span style="color:#ffffff;">Telefoonnummer bevestigen</span>
            </a>
        </p>
        <table role="presentation" width="100%" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#e8eef5;border:1px solid #cbd5e1;border-radius:12px;margin:0 0 20px;">
            <tr><td style="padding:16px 18px;border-radius:12px;background-color:#e8eef5;">
                <p style="margin:0 0 8px;font-size:12px;color:#0f172a;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Of kopieer deze link</p>
                <p style="margin:0;font-size:13px;line-height:1.5;word-break:break-all;color:#334155;">{{ $verificationUrl }}</p>
            </td></tr>
        </table>
        <p style="margin:0 0 20px;font-size:15px;line-height:1.6;">Deze link is 7 dagen geldig.</p>
        <p style="margin:0;font-size:13px;color:#6b7280;text-align:center;line-height:1.6;">Heeft u deze e-mail niet verwacht? Dan kunt u deze e-mail negeren.</p>
        <p style="margin:20px 0 0;font-size:13px;color:#6b7280;text-align:center;line-height:1.6;">Powered by NEXA Suite.</p>
    </td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
