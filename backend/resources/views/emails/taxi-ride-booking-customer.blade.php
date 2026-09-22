<x-email-card
    heading="Bevestiging taxiboeking #{{ (int) $ride_id }}"
    page-title="Bevestiging taxiboeking"
    :logo-html="$logoHtml ?? \App\Services\CompanyEmailLogoService::HTML_PLACEHOLDER"
    :kicker="$company_name ?? null"
>
    @if(!empty($customer_name))
    <p style="margin:0 0 16px;font-size:16px;">Beste {{ $customer_name }},</p>
    @else
    <p style="margin:0 0 16px;font-size:16px;">Beste klant,</p>
    @endif
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
        Bedankt voor uw boeking. Wij hebben uw ritaanvraag ontvangen en gaan deze zo snel mogelijk inplannen.
    </p>
    <table role="presentation" width="100%" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#e8eef5;border:1px solid #cbd5e1;border-radius:12px;margin:0 0 20px;">
        <tr><td style="padding:16px 18px;border-radius:12px;background-color:#e8eef5;">
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Datum/tijd:</strong> {{ $pickup_at }}</p>
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Ophalen:</strong> {{ $pickup_address ?? '—' }}</p>
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Afzetten:</strong> {{ $dropoff_address ?? '—' }}</p>
            @if(isset($quoted_price) && $quoted_price !== null && $quoted_price !== '')
            <p style="margin:0;font-size:15px;line-height:1.6;"><strong>Prijsindicatie:</strong> € {{ number_format((float) $quoted_price, 2, ',', '.') }}</p>
            @endif
        </td></tr>
    </table>
    <p style="margin:0 0 8px;font-size:12px;color:#0f172a;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Samenvatting van uw boeking</p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;white-space:pre-wrap;">{{ $summary_text }}</p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">U ontvangt een aparte melding zodra een chauffeur uw rit heeft geaccepteerd.</p>
    @if(!empty($portal_login_url))
    <p style="margin:0 0 18px;text-align:center;">
        <a href="{{ $portal_login_url }}" style="display:inline-block;background-color:#ea580c;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 22px;border-radius:6px;">
            <span style="color:#ffffff;">Open Mijn Taxi</span>
        </a>
    </p>
    @endif
    <p style="margin:0;font-size:13px;color:#6b7280;text-align:center;line-height:1.6;">Vragen? Neem contact op met {{ $company_name ?? 'ons' }}.</p>
</x-email-card>
