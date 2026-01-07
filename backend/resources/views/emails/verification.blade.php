<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verifieer je e-mailadres</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #f97316;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #ea580c;
        }
    </style>
</head>
<body>
    <h1>Verifieer je e-mailadres</h1>
    <p>Beste {{ $user->first_name }} {{ $user->last_name }},</p>
    <p>Bedankt voor je registratie bij Nexa Skillmatching. Klik op de onderstaande knop om je e-mailadres te verifiëren:</p>
    <p>
        <a href="{{ $verificationUrl }}" class="button">Verifieer e-mailadres</a>
    </p>
    <p>Of kopieer en plak deze link in je browser:</p>
    <p style="word-break: break-all; color: #666;">{{ $verificationUrl }}</p>
    <p>Deze link is 7 dagen geldig.</p>
    <p>Als je geen account hebt aangemaakt bij Nexa Skillmatching, kun je deze e-mail negeren.</p>
    <p>Met vriendelijke groet,<br>Het Nexa Skillmatching Team</p>
</body>
</html>




