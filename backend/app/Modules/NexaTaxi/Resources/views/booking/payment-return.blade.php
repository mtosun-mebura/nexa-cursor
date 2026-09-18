<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if(! empty($refreshUrl))
        <meta http-equiv="refresh" content="2;url={{ e($refreshUrl) }}">
    @endif
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
        <p>Je wordt teruggestuurd naar je boeking…</p>
    @else
        <h1>Betaling wordt verwerkt</h1>
        <p>Even geduld… Sluit dit venster niet. Zodra de betaling binnen is, gaan we terug naar de boekingspagina.</p>
    @endif
</div>
</body>
</html>
