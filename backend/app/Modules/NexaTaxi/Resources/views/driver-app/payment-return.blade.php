<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Betaling {{ $paid ? 'voltooid' : 'status' }}</title>
    <meta http-equiv="refresh" content="2;url={{ $appUrl }}">
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #0f172a; color: #f8fafc; padding: 1.5rem; text-align: center; }
        p { color: #94a3b8; }
        a { color: #4ade80; }
    </style>
</head>
<body>
<div>
    @if($paid)
        <h1>Betaling ontvangen</h1>
        <p>Je wordt teruggeleid naar de chauffeur-app…</p>
    @else
        <h1>Betaling wordt verwerkt</h1>
        <p>Even geduld… <a href="{{ $appUrl }}">Terug naar de app</a></p>
    @endif
</div>
<script>
    setTimeout(function () {
        window.location.replace(@json($appUrl));
    }, 1500);
</script>
</body>
</html>
