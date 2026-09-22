@props([
    'heading',
    'pageTitle' => null,
    'logoHtml' => '',
    'kicker' => null,
    'footerHtml' => null,
])
@php
    $pageTitle = $pageTitle ?: $heading;
    $footerHtml = $footerHtml ?? '<p style="margin:20px 0 0;font-size:13px;color:#6b7280;text-align:center;line-height:1.6;">Powered by NEXA Suite.</p>';
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $pageTitle }}</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#f4f4f4;color:#111827;color-scheme:light;">
<table role="presentation" style="width:100%;border-collapse:collapse;">
<tr><td style="padding:24px 12px;">
<table role="presentation" width="100%" style="width:100%;max-width:600px;margin:0 auto;background-color:#ffffff;border:1px solid #d1d5db;border-radius:8px;border-collapse:separate;border-spacing:0;overflow:hidden;">
<tr>
    <td bgcolor="#0f172a" style="padding:28px 32px;background-color:#0f172a;">
        {!! $logoHtml !!}
        @if(filled($kicker) && ! \App\Support\EmailCardHtml::isRedundantBrandKicker((string) $kicker))
            <p style="margin:0 0 6px;color:#94a3b8;font-size:13px;letter-spacing:0.04em;">{{ $kicker }}</p>
        @endif
        <h1 style="margin:0;font-size:22px;line-height:1.3;color:#ffffff;">{{ $heading }}</h1>
    </td>
</tr>
<tr>
    <td style="padding:28px 32px;">
        {{ $slot }}
        {!! $footerHtml !!}
    </td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
