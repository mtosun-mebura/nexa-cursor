<x-email-card
    heading="Nieuwe taxirit #{{ (int) $ride_id }}"
    page-title="Nieuwe taxirit"
    :logo-html="$logoHtml ?? \App\Services\CompanyEmailLogoService::HTML_PLACEHOLDER"
    :kicker="$company_name ?? null"
>
    @if(!empty($driver_name))
    <p style="margin:0 0 16px;font-size:16px;">Hallo {{ $driver_name }},</p>
    @endif
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Er is een nieuwe rit aangevraagd via de boekingsmodule.</p>
    <table role="presentation" width="100%" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#e8eef5;border:1px solid #cbd5e1;border-radius:12px;margin:0 0 20px;">
        <tr><td style="padding:16px 18px;border-radius:12px;background-color:#e8eef5;">
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Datum/tijd:</strong> {{ $pickup_at }}</p>
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Ophalen:</strong> {{ $pickup_address ?? '—' }}</p>
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Afzetten:</strong> {{ $dropoff_address ?? '—' }}</p>
            @if(!empty($customer_name))
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Klant:</strong> {{ $customer_name }}</p>
            @endif
            @if(!empty($customer_phone))
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Telefoon:</strong> {{ $customer_phone }}</p>
            @endif
            @if(!empty($customer_email))
            <p style="margin:0;font-size:15px;line-height:1.6;"><strong>E-mail klant:</strong> <a href="mailto:{{ $customer_email }}" style="color:#0f172a;">{{ $customer_email }}</a></p>
            @endif
            @if(isset($quoted_price) && $quoted_price !== null && $quoted_price !== '')
            <p style="margin:8px 0 0;font-size:15px;line-height:1.6;"><strong>Prijsindicatie:</strong> € {{ number_format((float) $quoted_price, 2, ',', '.') }}</p>
            @endif
        </td></tr>
    </table>
    <p style="margin:0 0 8px;font-size:12px;color:#0f172a;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Volledige samenvatting</p>
    <p style="margin:0;font-size:15px;line-height:1.6;white-space:pre-wrap;">{{ $summary_text }}</p>
</x-email-card>
