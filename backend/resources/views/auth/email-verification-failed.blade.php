<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Verificatie mislukt - NEXA Suite</title>
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
        .verify-fail-scene {
            width: 220px;
            height: 168px;
            margin: 0 auto 8px;
            cursor: pointer;
            position: relative;
        }
        .verify-fail-scene svg { width: 100%; height: 100%; display: block; overflow: visible; }
        .blob { fill: #eef2ff; }
        html.dark .blob { fill: #172033; }
        .mail-body { fill: #fff; stroke: #0f172a; stroke-width: 3.5; }
        html.dark .mail-body { fill: #0f172a; stroke: #e2e8f0; }
        .mail-flap { fill: none; stroke: #0f172a; stroke-width: 3.5; stroke-linecap: round; stroke-linejoin: round; }
        html.dark .mail-flap { stroke: #e2e8f0; }
        .mail-line { fill: none; stroke: #94a3b8; stroke-width: 2.5; stroke-linecap: round; }
        .ring {
            fill: none;
            stroke: #cbd5e1;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-dasharray: 132;
            stroke-dashoffset: 132;
            transform-origin: 110px 82px;
            animation: ring-try 1.15s 0.15s cubic-bezier(0.4, 0, 0.2, 1) both;
        }
        .badge {
            transform-origin: 168px 118px;
            animation: badge-pop 0.42s 1.05s cubic-bezier(0.2, 1.4, 0.35, 1) both;
        }
        .mail {
            transform-origin: 110px 86px;
            animation: mail-settle 0.7s cubic-bezier(0.22, 1, 0.36, 1) both, mail-shake 0.12s 1.08s ease 6;
        }
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
        .tip {
            margin-top: 14px;
            font-size: 0.75rem;
            color: #94a3b8;
        }
        html.dark .tip { color: #64748b; }
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
        @keyframes mail-settle {
            from { transform: translateY(10px) scale(0.96); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }
        @keyframes ring-try {
            0% { stroke: #93c5fd; stroke-dashoffset: 132; transform: rotate(-90deg); }
            70% { stroke: #60a5fa; stroke-dashoffset: 28; transform: rotate(200deg); }
            100% { stroke: #ea580c; stroke-dashoffset: 88; transform: rotate(250deg); }
        }
        @keyframes badge-pop {
            from { transform: scale(0.15); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        @keyframes mail-shake {
            0%, 50%, 100% { transform: translate(0, 0); }
            25% { transform: translate(-3px, 1px); }
            75% { transform: translate(3px, -1px); }
        }
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
    <div class="card">
        <div class="verify-fail-scene" id="verify-fail-scene" aria-label="Klik om animatie te herhalen" title="Klik om animatie te herhalen">
            <svg viewBox="0 0 220 168" fill="none" aria-hidden="true">
                <ellipse class="blob" cx="110" cy="92" rx="86" ry="52"/>
                <g class="mail">
                    <rect class="mail-body" x="52" y="52" width="116" height="78" rx="14"/>
                    <path class="mail-flap" d="M54 66 L110 104 L166 66"/>
                    <path class="mail-line" d="M68 112 L92 94"/>
                    <path class="mail-line" d="M152 112 L128 94"/>
                </g>
                <circle class="ring" cx="110" cy="82" r="21"/>
                <g class="badge">
                    <circle cx="168" cy="118" r="22" fill="#ea580c"/>
                    <path d="M160 110 L176 126 M176 110 L160 126" stroke="#fff" stroke-width="4.5" stroke-linecap="round"/>
                </g>
            </svg>
        </div>
        <h1>Verificatie mislukt</h1>
        <p>{{ $message }}</p>
        <p class="tip">Tip: vraag via de beheerder een nieuwe activatielink aan.</p>
        <div class="actions">
            <a class="btn" href="{{ route('admin.login') }}">Ga naar inlogpagina</a>
        </div>
    </div>
    <script>
        (function () {
            var root = document.getElementById('verify-fail-scene');
            if (!root) return;
            root.addEventListener('click', function () {
                var markup = root.innerHTML;
                root.innerHTML = '';
                requestAnimationFrame(function () { root.innerHTML = markup; });
            });
        })();
    </script>
</body>
</html>
