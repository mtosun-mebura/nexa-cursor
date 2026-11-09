@props([
    'heading',
    'pageTitle' => null,
    'logoHtml' => '',
    'kicker' => null,
    'footerHtml' => null,
    'lang' => null,
])
@php
    $pageTitle = $pageTitle ?: $heading;
    $footerHtml = $footerHtml ?? \App\Support\EmailCardHtml::poweredByFooter();
    $htmlLang = in_array((string) $lang, ['nl', 'en'], true) ? (string) $lang : app()->getLocale();
    if (! in_array($htmlLang, ['nl', 'en'], true)) {
        $htmlLang = 'nl';
    }
    $kickerHtml = \App\Support\EmailCardHtml::headerBrandKickerHtml(
        filled($kicker) ? (string) $kicker : null,
        is_string($logoHtml) ? $logoHtml : null
    );
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $pageTitle }}</title>
    <style>:root { color-scheme: light only; }</style>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#e5e7eb;color:#111827;color-scheme:light only;">
<table role="presentation" width="100%" bgcolor="#e5e7eb" style="width:100%;border-collapse:collapse;background-color:#e5e7eb;">
<tr><td bgcolor="#e5e7eb" style="padding:24px 12px;background-color:#e5e7eb;">
<table role="presentation" width="100%" bgcolor="#ffffff" style="width:100%;max-width:600px;margin:0 auto;background-color:#ffffff;border:1px solid #d1d5db;border-radius:12px;border-collapse:separate;border-spacing:0;overflow:hidden;">
<tr>
    <td bgcolor="#ffffff" style="padding:28px 32px 20px;background-color:#ffffff;border-radius:12px 12px 0 0;">
        {!! $logoHtml !!}
        {!! $kickerHtml !!}
        <h1 style="margin:0;font-size:22px;line-height:1.3;color:#0f172a;">{{ $heading }}</h1>
    </td>
</tr>
<tr>
    <td bgcolor="#ffffff" style="padding:8px 32px 28px;background-color:#ffffff;border-radius:0 0 12px 12px;">
        {{ $slot }}
        {!! $footerHtml !!}
    </td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
