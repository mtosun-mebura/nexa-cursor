<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>404 — Pagina niet gevonden | NEXA</title>
    <link href="https://fonts.googleapis.com/css2?family=Arvo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            height: 100%;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            font-family: 'Arvo', serif;
            color: #333;
        }
        .page_404 {
            width: 100%;
            max-width: 720px;
            padding: 40px 20px;
            text-align: center;
        }
        .four_zero_four_bg {
            background-image: url(https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif);
            background-position: center;
            background-repeat: no-repeat;
            background-size: contain;
            height: min(400px, 55vh);
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        .four_zero_four_bg h1 {
            margin: 0;
            font-size: clamp(64px, 12vw, 80px);
            font-weight: 700;
            line-height: 1;
        }
        .contant_box_404 {
            margin-top: -40px;
        }
        .contant_box_404 h3 {
            margin: 0 0 12px;
            font-size: clamp(22px, 4vw, 28px);
            font-weight: 700;
        }
        .contant_box_404 p {
            margin: 0 0 24px;
            font-size: 16px;
            line-height: 1.5;
            color: #555;
        }
        .link_404 {
            color: #fff !important;
            padding: 12px 24px;
            background: #39ac31;
            margin: 8px 0 0;
            display: inline-block;
            text-decoration: none;
            border-radius: 4px;
            font-family: system-ui, -apple-system, sans-serif;
            font-weight: 600;
            font-size: 15px;
            transition: background 0.15s ease;
        }
        .link_404:hover {
            background: #2f9229;
        }
        .page_404-brand {
            margin-bottom: 8px;
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #888;
        }
    </style>
</head>
<body>
    <section class="page_404" role="main" aria-labelledby="error-404-title">
        <div class="page_404-brand">NEXA</div>
        <div class="four_zero_four_bg">
            <h1 id="error-404-title">404</h1>
        </div>
        <div class="contant_box_404">
            <h3>Het lijkt erop dat u verdwaald bent</h3>
            <p>De pagina die u zoekt is niet beschikbaar.</p>
            <a href="{{ url('/') }}" class="link_404">Naar home</a>
        </div>
    </section>
</body>
</html>
