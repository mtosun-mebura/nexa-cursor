<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>E-mailadres geverifieerd - NEXA Suite</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        html.dark body { background: #070b14; color: #e2e8f0; }
        .card {
            width: 100%;
            max-width: 440px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 24px 60px -24px rgba(15, 23, 42, 0.28);
            padding: 40px 32px 36px;
            text-align: center;
        }
        html.dark .card {
            background: #0b1220;
            border-color: #1e293b;
            box-shadow: 0 24px 60px -24px rgba(0, 0, 0, 0.55);
        }
        .art {
            display: flex;
            justify-content: center;
            margin-bottom: 8px;
        }
        .art img { max-height: 130px; width: auto; }
        html.dark .art .light-only { display: none; }
        html:not(.dark) .art .dark-only { display: none; }
        h1 {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 12px 0 10px;
            letter-spacing: -0.01em;
        }
        p {
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.55;
            color: #64748b;
        }
        html.dark p { color: #94a3b8; }
        a.inline {
            color: #0f172a;
            font-weight: 600;
            text-decoration: none;
        }
        html.dark a.inline { color: #e2e8f0; }
        a.inline:hover { color: #2563eb; }
        .actions { margin-top: 28px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 18px;
            border-radius: 8px;
            background: #2563eb;
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
        }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <script>
        (function () {
            var stored = localStorage.getItem('kt-theme');
            var mode = stored || 'light';
            if (mode === 'system') {
                mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.classList.add(mode);
        })();
    </script>
    @php
        $channelLabel = $channelLabel ?? 'e-mailadres';
    @endphp
    <div class="card">
        <div class="art">
            <img alt="" class="light-only" src="{{ asset('assets/media/illustrations/30.svg') }}">
            <img alt="" class="dark-only" src="{{ asset('assets/media/illustrations/30-dark.svg') }}">
        </div>
        <h1>
            @if($wasAlreadyVerified)
                {{ ucfirst($channelLabel) }} al geverifieerd
            @else
                {{ ucfirst($channelLabel) }} succesvol geverifieerd!
            @endif
        </h1>
        <p>
            @if($wasAlreadyVerified)
                Je {{ $channelLabel }} was al geverifieerd. Je kunt direct inloggen.
            @else
                Bedankt {{ $user->first_name }}! Je {{ $channelLabel }}
                @if(!empty($channelValue) || $user->email)
                    <a class="inline" href="{{ ($channelLabel === 'telefoonnummer') ? 'tel:'.($channelValue ?? $user->phone) : 'mailto:'.($channelValue ?? $user->email) }}">
                        {{ $channelValue ?? $user->email }}
                    </a>
                @endif
                is nu geverifieerd. Je kunt nu inloggen op je account.
            @endif
        </p>
        <div class="actions">
            <a class="btn" href="{{ route('admin.login') }}">Ga naar inlogpagina</a>
        </div>
    </div>
</body>
</html>
