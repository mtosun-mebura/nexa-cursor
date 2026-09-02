<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proefperiode beëindigd — NEXA</title>
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f1f5f9; color: #0f172a; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { width: 100%; max-width: 32rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
        h1 { margin: 0 0 12px; font-size: 1.25rem; }
        p { margin: 0 0 12px; font-size: 0.95rem; line-height: 1.55; color: #334155; }
        .error { color: #b91c1c; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            @if(session('error'))
                <h1>Niet gestopt</h1>
                <p class="error">{{ session('error') }}</p>
            @else
                <h1>Proefperiode beëindigd</h1>
                <p>Het abonnement is niet ingegaan. De tenant is inactief gezet en er volgt geen incasso.</p>
            @endif
            <p>Vragen? Mail <a href="mailto:info@nexasuite.nl">info@nexasuite.nl</a>.</p>
        </div>
    </div>
</body>
</html>
