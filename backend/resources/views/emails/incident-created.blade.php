<x-email-card
    heading="Nieuw incident {{ $incident->reference }}"
    page-title="Nieuw incident {{ $incident->reference }}"
    :logo-html="$nexaLogoHtml ?? \App\Support\NexaBranding::emailLogoPreviewHtml()"
>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Er is een nieuw incident gemeld door een klant.</p>
    <table role="presentation" width="100%" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#e8eef5;border:1px solid #cbd5e1;border-radius:12px;margin:0 0 20px;">
        <tr><td style="padding:16px 18px;border-radius:12px;background-color:#e8eef5;">
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Referentie:</strong> {{ $incident->reference }}</p>
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Type:</strong> {{ $kindLabel }}</p>
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Prioriteit:</strong> {{ $priorityLabel }}</p>
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Bedrijf:</strong> {{ $companyName }}</p>
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Melder:</strong> {{ $reporterName }}@if($reporterEmail) ({{ $reporterEmail }})@endif</p>
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Titel:</strong> {{ $incident->title }}</p>
            @if($pageUrl)
            <p style="margin:0;font-size:15px;line-height:1.6;"><strong>Pagina:</strong> <a href="{{ $pageUrl }}" style="color:#0f172a;">{{ $pageUrl }}</a></p>
            @endif
        </td></tr>
    </table>
    <p style="margin:0 0 8px;font-size:12px;color:#0f172a;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Beschrijving</p>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.6;white-space:pre-wrap;">{{ $incident->description }}</p>
    <p style="margin:0;">
        <a href="{{ $url }}" style="display:inline-block;padding:10px 16px;background-color:#0f172a;color:#ffffff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:600;">
            Incident openen
        </a>
    </p>
</x-email-card>
