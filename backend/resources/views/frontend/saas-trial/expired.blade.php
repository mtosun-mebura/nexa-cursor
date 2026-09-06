<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proefperiode voorbij — NEXA</title>
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f1f5f9; color: #0f172a; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { width: 100%; max-width: 32rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
        h1 { margin: 0 0 12px; font-size: 1.25rem; }
        p { margin: 0; font-size: 0.95rem; line-height: 1.55; color: #334155; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>Proefperiode is al voorbij</h1>
            <p>Het abonnement van {{ $company->name }} is al ingegaan. Stoppen kan alleen tijdens de proefperiode. Neem contact op via <a href="mailto:info@nexasuite.nl">info@nexasuite.nl</a>.</p>
        </div>
    </div>
</body>
</html>
