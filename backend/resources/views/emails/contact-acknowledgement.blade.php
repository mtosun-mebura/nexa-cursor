<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Beste {{ $greetingName }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 20px 0; text-align: center;">
                <table role="presentation" width="100%" style="width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-collapse: separate; border-spacing: 0; overflow: hidden;">
                    <tr>
                        <td width="100%" bgcolor="#0f172a" style="padding: 24px 30px; background-color: #0f172a; border-radius: 8px 8px 0 0; width: 100%; text-align: left;">
                            @if(!empty($logoHtml))
                                {!! $logoHtml !!}
                            @endif
                            @if(!empty($companyName) && ! \App\Support\EmailCardHtml::isRedundantBrandKicker((string) $companyName))
                                <p style="margin: 0 0 6px; color: #94a3b8; font-size: 13px; letter-spacing: 0.04em;">{{ $companyName }}</p>
                            @endif
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; line-height: 1.3;">Beste {{ $greetingName }},</h1>
                        </td>
                    </tr>
                    <tr>
                        <td width="100%" bgcolor="#ffffff" style="padding: 30px; background-color: #ffffff; color: #333333; width: 100%; text-align: left;">
                            <p style="margin: 0 0 16px; color: #333333; font-size: 16px; line-height: 1.6;">
                                Hartelijk dank voor uw bericht via ons contactformulier. Wij bevestigen dat uw aanvraag in goede orde bij ons is binnengekomen.
                            </p>
                            <p style="margin: 0 0 16px; color: #333333; font-size: 16px; line-height: 1.6;">
                                Ons team neemt uw verzoek zo spoedig mogelijk in behandeling en komt hierop bij u terug. U hoeft hiervoor verder niets te doen.
                            </p>
                            <p style="margin: 0; color: #333333; font-size: 16px; line-height: 1.6;">
                                Wilt u in de tussentijd iets toevoegen of wijzigen? Beantwoord dan gerust deze e-mail.
                            </p>
                            <p style="margin: 24px 0 0; color: #333333; font-size: 16px; line-height: 1.6;">
                                Met vriendelijke groet,<br>
                                <strong>{{ $companyName }}</strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td width="100%" bgcolor="#f9fafb" style="padding: 20px 30px; background-color: #f9fafb; border-radius: 0 0 8px 8px; border-top: 1px solid #e5e7eb; width: 100%;">
                            <p style="margin: 0; color: #6b7280; font-size: 14px; text-align: center;">
                                Dit is een automatische bevestiging. U ontvangt zo spoedig mogelijk een persoonlijk antwoord.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
