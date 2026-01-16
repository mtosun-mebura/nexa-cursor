<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Wachtwoord Resetten</title>
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
    <h1>Wachtwoord Resetten</h1>
    <p>Beste {{ $user->first_name }} {{ $user->last_name }},</p>
    <p>Je hebt een wachtwoord reset aangevraagd voor je Nexa Skillmatching account.</p>
    <p>Klik op de onderstaande knop om je wachtwoord te resetten:</p>
    <p>
        <a href="{{ $resetUrl }}" class="button">Wachtwoord Resetten</a>
    </p>
    <p>Of kopieer en plak deze link in je browser:</p>
    <p style="word-break: break-all; color: #666;">{{ $resetUrl }}</p>
    <p>Deze link is 60 minuten geldig.</p>
    <p>Als je deze aanvraag niet hebt gedaan, kun je deze e-mail negeren. Je wachtwoord blijft ongewijzigd.</p>
    <p>Met vriendelijke groet,<br>Het Nexa Skillmatching Team</p>
</body>
</html>









