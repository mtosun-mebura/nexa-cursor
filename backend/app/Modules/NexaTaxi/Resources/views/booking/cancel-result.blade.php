<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $success ? 'Rit geannuleerd' : 'Annuleren mislukt' }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8fafc; color: #0f172a; padding: 1.5rem; text-align: center; }
        .card { max-width: 26rem; width: 100%; background: #fff; border-radius: 1rem; padding: 2rem; box-shadow: 0 4px 24px rgba(15,23,42,0.08); }
        h1 { font-size: 1.25rem; margin: 0 0 0.75rem; }
        p { margin: 0; color: #475569; line-height: 1.5; }
    </style>
</head>
<body>
<div class="card">
    <h1>{{ $success ? 'Rit geannuleerd' : 'Annuleren niet mogelijk' }}</h1>
    <p>{{ $message }}</p>
</div>
</body>
</html>
