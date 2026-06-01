<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bevestiging taxiboeking</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb; border-bottom: 2px solid #2563eb; padding-bottom: 10px;">
            Uw taxiboeking #{{ (int) $ride_id }}
        </h2>

        @if(!empty($customer_name))
        <p>Beste {{ htmlspecialchars(trim($customer_name), ENT_QUOTES, 'UTF-8') }},</p>
        @else
        <p>Beste klant,</p>
        @endif

        <p>Bedankt voor uw boeking. Wij hebben uw rit aanvraag ontvangen en gaan deze zo snel mogelijk inplannen.</p>

        <div style="background-color: #f9fafb; padding: 20px; border-radius: 8px; margin-top: 16px;">
            <p><strong>Datum/tijd:</strong> {{ htmlspecialchars($pickup_at, ENT_QUOTES, 'UTF-8') }}</p>
            <p><strong>Ophalen:</strong> {{ htmlspecialchars($pickup_address ?? '—', ENT_QUOTES, 'UTF-8') }}</p>
            <p><strong>Afzetten:</strong> {{ htmlspecialchars($dropoff_address ?? '—', ENT_QUOTES, 'UTF-8') }}</p>
            @if(isset($quoted_price) && $quoted_price !== null && $quoted_price !== '')
            <p><strong>Prijsindicatie:</strong> € {{ number_format((float) $quoted_price, 2, ',', '.') }}</p>
            @endif
        </div>

        <div style="margin-top: 20px;">
            <h3 style="color: #1f2937;">Samenvatting van uw boeking</h3>
            <pre style="background-color: #ffffff; padding: 15px; border-left: 4px solid #2563eb; margin-top: 10px; white-space: pre-wrap; font-family: inherit;">{{ $summary_text }}</pre>
        </div>

        <p style="margin-top: 20px;">U ontvangt een aparte melding zodra een chauffeur uw rit heeft geaccepteerd.</p>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280;">
            <p>Dit bericht is automatisch verzonden door {{ config('app.name', 'NEXA') }}.</p>
        </div>
    </div>
</body>
</html>
