<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Tijdelijk geblokkeerd | {{ config('app.name') }}</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f172a;
            color: #e2e8f0;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            padding: 1.5rem;
        }
        .card {
            max-width: 32rem;
            width: 100%;
            background: #1e293b;
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 1rem;
            padding: 2rem;
        }
        h1 {
            font-size: 1.25rem;
            margin: 0 0 0.75rem;
        }
        p {
            margin: 0;
            line-height: 1.6;
            color: #cbd5e1;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Omgeving tijdelijk geblokkeerd</h1>
        <p>{{ $message }}</p>
    </div>
</body>
</html>
