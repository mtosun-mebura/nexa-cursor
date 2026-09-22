<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rit annuleren</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8fafc; color: #0f172a; padding: 1.5rem; }
        .card { max-width: 26rem; width: 100%; background: #fff; border-radius: 1rem; padding: 2rem; box-shadow: 0 4px 24px rgba(15,23,42,0.08); }
        h1 { font-size: 1.25rem; margin: 0 0 0.75rem; }
        p { margin: 0 0 0.75rem; color: #475569; line-height: 1.5; }
        .meta { background: #e8eef5; border-radius: 0.75rem; padding: 0.9rem 1rem; margin: 0 0 1.25rem; }
        .meta p { margin: 0 0 0.35rem; color: #0f172a; font-size: 0.95rem; }
        .meta p:last-child { margin-bottom: 0; }
        .actions { display: flex; flex-direction: column; gap: 0.65rem; }
        .btn { display: inline-block; text-align: center; border: none; border-radius: 0.5rem; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.95rem; cursor: pointer; text-decoration: none; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-muted { background: #e2e8f0; color: #0f172a; }
        .note { font-size: 0.85rem; color: #64748b; margin-top: 0.25rem; }
    </style>
</head>
<body>
<div class="card">
    <h1>Rit annuleren</h1>
    @if($alreadyCancelled)
        <p>Deze rit is al geannuleerd.</p>
    @elseif(! $canCancel)
        <p>Deze rit kan niet meer via deze link worden geannuleerd. Neem contact op met de taxi als u hulp nodig heeft.</p>
    @else
        <p>Weet u zeker dat u deze rit wilt annuleren?</p>
        <div class="meta">
            <p><strong>Rit #{{ (int) $ride->id }}</strong></p>
            <p>Datum/tijd: {{ $pickupAt }}</p>
            <p>Ophalen: {{ $ride->pickup_address ?: '—' }}</p>
            <p>Afzetten: {{ $ride->dropoff_address ?: '—' }}</p>
        </div>
        @if($wasPaid)
            <p class="note">Het vooraf betaalde bedrag wordt automatisch teruggestort.</p>
        @endif
        <div class="actions">
            <form method="POST" action="{{ $confirmUrl }}">
                @csrf
                <button type="submit" class="btn btn-danger" style="width:100%">Ja, rit annuleren</button>
            </form>
        </div>
    @endif
</div>
</body>
</html>
