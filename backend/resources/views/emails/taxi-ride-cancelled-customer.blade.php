<x-email-card
    heading="Rit geannuleerd #{{ (int) $ride_id }}"
    page-title="Rit geannuleerd"
    :logo-html="$logoHtml ?? \App\Services\CompanyEmailLogoService::HTML_PLACEHOLDER"
    :kicker="$company_name ?? null"
>
    @if(!empty($customer_name))
    <p style="margin:0 0 16px;font-size:16px;">Beste {{ $customer_name }},</p>
    @else
    <p style="margin:0 0 16px;font-size:16px;">Beste klant,</p>
    @endif

    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
        {{ $intro_text }}
    </p>

    <table role="presentation" width="100%" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#e8eef5;border:1px solid #cbd5e1;border-radius:12px;margin:0 0 20px;">
        <tr><td style="padding:16px 18px;border-radius:12px;background-color:#e8eef5;">
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Datum/tijd:</strong> {{ $pickup_at }}</p>
            <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Ophalen:</strong> {{ $pickup_address ?? '—' }}</p>
            <p style="margin:0;font-size:15px;line-height:1.6;"><strong>Afzetten:</strong> {{ $dropoff_address ?? '—' }}</p>
        </td></tr>
    </table>

    @if(!empty($refund_message))
    <table role="presentation" width="100%" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;margin:0 0 20px;">
        <tr><td style="padding:16px 18px;border-radius:12px;background-color:#ecfdf5;">
            <p style="margin:0 0 6px;font-size:12px;color:#065f46;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Terugbetaling</p>
            <p style="margin:0;font-size:15px;line-height:1.6;color:#064e3b;">{{ $refund_message }}</p>
        </td></tr>
    </table>
    @endif

    <p style="margin:0;font-size:13px;color:#6b7280;text-align:center;line-height:1.6;">
        Vragen? Neem contact op met {!! \App\Support\EmailCardHtml::companyNameHtml($company_name ?? 'ons', $company_website_url ?? null) !!}.
    </p>
</x-email-card>
