<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Proefperiode stoppen — NEXA</title>
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f1f5f9; color: #0f172a; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { width: 100%; max-width: 32rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
        .head { padding: 24px 28px; border-bottom: 1px solid #e2e8f0; }
        .body { padding: 24px 28px; }
        h1 { margin: 0; font-size: 1.25rem; }
        p { margin: 0 0 12px; font-size: 0.95rem; line-height: 1.55; color: #334155; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; padding: 10px 16px; font-size: 0.9rem; font-weight: 700; text-decoration: none; cursor: pointer; border: 0; }
        .btn-danger { background: #ea580c; color: #fff; }
        .btn-ghost { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="head">
                <h1>Proefperiode stoppen</h1>
            </div>
            <div class="body">
                <p>Je staat op het punt de proefperiode van <strong>{{ $company->name }}</strong> te beëindigen.</p>
                @if($trialEndsAt)
                    <p>Zonder deze actie gaat het abonnement in op {{ $trialEndsAt->translatedFormat('j F Y') }} en start de eerste incasso. Het jaarcontract telt vanaf de start van de proef.</p>
                @endif
                <p>Als je stopt, zetten we de tenant op inactief. Er wordt niets geïncasseerd.</p>
                <form method="post" action="{{ route('saas.trial.stop') }}" class="actions">
                    @csrf
                    <button type="submit" class="btn btn-danger">Ja, proefperiode stoppen</button>
                    <a class="btn btn-ghost" href="https://nexasuite.nl">Annuleren</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
