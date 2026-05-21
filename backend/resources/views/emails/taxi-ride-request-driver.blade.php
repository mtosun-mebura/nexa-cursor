<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nieuwe taxirit</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb; border-bottom: 2px solid #2563eb; padding-bottom: 10px;">
            Nieuwe taxirit #{{ (int) $ride_id }}
        </h2>

        @if(!empty($driver_name))
        <p>Hallo {{ htmlspecialchars(trim($driver_name), ENT_QUOTES, 'UTF-8') }},</p>
        @endif

        <p>Er is een nieuwe rit aangevraagd via de boekingsmodule.</p>

        <div style="background-color: #f9fafb; padding: 20px; border-radius: 8px; margin-top: 16px;">
            <p><strong>Datum/tijd:</strong> {{ htmlspecialchars($pickup_at, ENT_QUOTES, 'UTF-8') }}</p>
            <p><strong>Ophalen:</strong> {{ htmlspecialchars($pickup_address ?? '—', ENT_QUOTES, 'UTF-8') }}</p>
            <p><strong>Afzetten:</strong> {{ htmlspecialchars($dropoff_address ?? '—', ENT_QUOTES, 'UTF-8') }}</p>
            @if(!empty($customer_name))
            <p><strong>Klant:</strong> {{ htmlspecialchars($customer_name, ENT_QUOTES, 'UTF-8') }}</p>
            @endif
            @if(!empty($customer_phone))
            <p><strong>Telefoon:</strong> {{ htmlspecialchars($customer_phone, ENT_QUOTES, 'UTF-8') }}</p>
            @endif
            @if(!empty($customer_email))
            <p><strong>E-mail klant:</strong> <a href="mailto:{{ htmlspecialchars($customer_email, ENT_QUOTES, 'UTF-8') }}">{{ htmlspecialchars($customer_email, ENT_QUOTES, 'UTF-8') }}</a></p>
            @endif
            @if(isset($quoted_price) && $quoted_price !== null && $quoted_price !== '')
            <p><strong>Prijsindicatie:</strong> € {{ number_format((float) $quoted_price, 2, ',', '.') }}</p>
            @endif
        </div>

        <div style="margin-top: 20px;">
            <h3 style="color: #1f2937;">Volledige samenvatting</h3>
            <pre style="background-color: #ffffff; padding: 15px; border-left: 4px solid #2563eb; margin-top: 10px; white-space: pre-wrap; font-family: inherit;">{{ $summary_text }}</pre>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280;">
            <p>Dit bericht is automatisch verzonden door {{ config('app.name', 'NEXA') }}.</p>
        </div>
    </div>
</body>
</html>
