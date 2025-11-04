<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nieuw contactformulier bericht</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb; border-bottom: 2px solid #2563eb; padding-bottom: 10px;">
            Nieuw contactformulier bericht
        </h2>
        
        <div style="background-color: #f9fafb; padding: 20px; border-radius: 8px; margin-top: 20px;">
            <p><strong>Voornaam:</strong> {{ htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8') }}</p>
            <p><strong>Achternaam:</strong> {{ htmlspecialchars($last_name, ENT_QUOTES, 'UTF-8') }}</p>
            <p><strong>E-mailadres:</strong> <a href="mailto:{{ htmlspecialchars($email, ENT_QUOTES, 'UTF-8') }}">{{ htmlspecialchars($email, ENT_QUOTES, 'UTF-8') }}</a></p>
            @if($phone)
            <p><strong>Telefoonnummer:</strong> {{ htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') }}</p>
            @endif
        </div>
        
        <div style="margin-top: 20px;">
            <h3 style="color: #1f2937;">Bericht:</h3>
            <div style="background-color: #ffffff; padding: 15px; border-left: 4px solid #2563eb; margin-top: 10px;">
                <p style="white-space: pre-wrap;">{!! $user_message !!}</p>
            </div>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280;">
            <p>Dit bericht is verzonden via het contactformulier op {{ config('app.name', 'NEXA Skillmatching') }}.</p>
        </div>
    </div>
</body>
</html>

