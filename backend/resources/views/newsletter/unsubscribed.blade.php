<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Afgemeld — NEXA Suite</title>
    <style>
        body { margin: 0; background: #f4f4f4; font-family: Arial, Helvetica, sans-serif; color: #0f172a; }
        .wrap { max-width: 480px; margin: 64px auto; background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 32px; }
        h1 { font-size: 22px; margin: 0 0 12px; }
        p { font-size: 15px; line-height: 1.6; color: #334155; margin: 0 0 12px; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>U bent afgemeld</h1>
        <p>
            @if(!empty($companyName))
                {{ $companyName }} ontvangt geen NEXA Suite-nieuwsbrieven meer.
            @else
                Dit adres ontvangt geen NEXA Suite-nieuwsbrieven meer.
            @endif
        </p>
        <p>De volgende verzending slaat dit adres over. Vragen? <a href="mailto:info@nexasuite.nl">info@nexasuite.nl</a></p>
    </div>
</body>
</html>
