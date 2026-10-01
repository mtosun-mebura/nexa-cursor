<x-email-card
    heading="Network-partnerverzoek"
    page-title="NEXA Network — partnerverzoek"
    :logo-html="$nexaLogoHtml ?? \App\Support\NexaBranding::emailLogoPreviewHtml()"
    kicker="NEXA Network"
>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
        Hallo{{ !empty($partnerContactName) ? ' '.$partnerContactName : '' }},
    </p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
        <strong>{{ $ownerCompanyName }}</strong> wil jouw taxibedrijf
        (<strong>{{ $partnerCompanyName }}</strong>) koppelen als
        <strong>NEXA Network-partner</strong>.
    </p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
        Als je accepteert, mogen jullie chauffeurs ritten van {{ $ownerCompanyName }} uitvoeren
        wanneer hun eigen vloot geen capaciteit heeft. De klant blijft van hen;
        jullie zijn alleen de uitvoerder.
    </p>

    <table role="presentation" width="100%" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#e8eef5;border:1px solid #cbd5e1;border-radius:12px;margin:0 0 20px;">
        <tr><td style="padding:16px 18px;border-radius:12px;background-color:#e8eef5;">
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Owner (aanvrager):</strong> {{ $ownerCompanyName }}</p>
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Partner (jij):</strong> {{ $partnerCompanyName }}</p>
            <p style="margin:0;font-size:15px;line-height:1.6;"><strong>Status:</strong> Wacht op jouw acceptatie</p>
        </td></tr>
    </table>

    <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#4b5563;">
        Open Chauffeur dispatch → Network-partners om te accepteren of af te wijzen.
        Er wordt geen andere tenantdata gedeeld dan wat nodig is voor deze koppeling.
    </p>

    <p style="margin:0;">
        <a href="{{ $actionUrl }}" style="display:inline-block;padding:10px 16px;background-color:#0f172a;color:#ffffff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:600;">
            Partnerverzoek openen
        </a>
    </p>
</x-email-card>
