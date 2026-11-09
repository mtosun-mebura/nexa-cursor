<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light">
    <title>Beste {{ $greetingName }}</title>
    <style>:root { color-scheme: light only; }</style>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #e5e7eb; color-scheme: light only;">
    <table role="presentation" width="100%" bgcolor="#e5e7eb" style="width: 100%; border-collapse: collapse; background-color: #e5e7eb;">
        <tr>
            <td bgcolor="#e5e7eb" style="padding: 24px 12px; background-color: #e5e7eb; text-align: center;">
                <table role="presentation" width="100%" bgcolor="#ffffff" style="width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #d1d5db; border-radius: 12px; border-collapse: separate; border-spacing: 0; overflow: hidden;">
                    <tr>
                        <td width="100%" bgcolor="#ffffff" style="padding: 28px 32px 20px; background-color: #ffffff; border-radius: 12px 12px 0 0; width: 100%; text-align: left;">
                            @if(!empty($logoHtml))
                                {!! $logoHtml !!}
                            @endif
                            {!! \App\Support\EmailCardHtml::headerBrandKickerHtml(
                                !empty($companyName) ? (string) $companyName : null,
                                !empty($logoHtml) ? (string) $logoHtml : null
                            ) !!}
                            <h1 style="margin: 0; color: #0f172a; font-size: 22px; line-height: 1.3;">Beste {{ $greetingName }},</h1>
                        </td>
                    </tr>
                    <tr>
                        <td width="100%" bgcolor="#ffffff" style="padding: 8px 32px 28px; background-color: #ffffff; color: #333333; width: 100%; text-align: left;">
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
                        <td width="100%" bgcolor="#f9fafb" style="padding: 20px 30px; background-color: #f9fafb; border-radius: 0 0 12px 12px; border-top: 1px solid #e5e7eb; width: 100%;">
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
