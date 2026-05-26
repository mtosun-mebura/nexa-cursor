<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Betaling {{ $paid ? 'voltooid' : 'status' }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8fafc; color: #0f172a; padding: 1.5rem; text-align: center; }
        .card { max-width: 24rem; background: #fff; border-radius: 1rem; padding: 2rem; box-shadow: 0 4px 24px rgba(15,23,42,0.08); }
        h1 { font-size: 1.25rem; margin: 0 0 0.75rem; }
        p { margin: 0; color: #475569; line-height: 1.5; }
    </style>
</head>
<body>
<div class="card">
    @if($paid)
        <h1>Betaling ontvangen</h1>
        <p>Bedankt! Je boeking is bevestigd en wordt verwerkt.</p>
    @else
        <h1>Betaling wordt verwerkt</h1>
        <p>Sluit dit venster niet als je net hebt betaald. De status wordt zo bijgewerkt.</p>
    @endif
</div>
</body>
</html>
