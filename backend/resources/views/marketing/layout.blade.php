<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'NEXA Marketing' }}</title>
    <style>
        :root {
            --bg: #0b1220;
            --bg-soft: #121a2b;
            --card: #162033;
            --text: #f3f6fb;
            --muted: #9aa8bd;
            --accent: #f97316;
            --accent-2: #2563eb;
            --line: rgba(148, 163, 184, 0.22);
            --ok: #34d399;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: radial-gradient(1200px 600px at 10% -10%, rgba(37,99,235,.25), transparent 55%),
                        radial-gradient(900px 500px at 100% 0%, rgba(249,115,22,.18), transparent 50%),
                        var(--bg);
            color: var(--text);
            line-height: 1.55;
        }
        a { color: #93c5fd; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .wrap { max-width: 1080px; margin: 0 auto; padding: 1.25rem 1.25rem 3rem; }
        .topnav {
            display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;
            justify-content: space-between; margin-bottom: 1.5rem;
            padding-bottom: 1rem; border-bottom: 1px solid var(--line);
        }
        .brand { font-weight: 800; letter-spacing: 0.04em; font-size: 1.15rem; color: var(--text); }
        .brand span { color: var(--accent); }
        .nav-links { display: flex; flex-wrap: wrap; gap: 0.4rem; }
        .nav-links a {
            color: var(--muted); font-size: 0.875rem; font-weight: 600;
            padding: 0.35rem 0.65rem; border-radius: 999px; border: 1px solid transparent;
        }
        .nav-links a.is-active, .nav-links a:hover {
            color: var(--text); background: var(--card); border-color: var(--line); text-decoration: none;
        }
        h1 { font-size: clamp(1.75rem, 3vw, 2.4rem); line-height: 1.15; margin: 0 0 0.75rem; }
        h2 { font-size: 1.35rem; margin: 2rem 0 0.75rem; }
        h3 { font-size: 1.05rem; margin: 1.25rem 0 0.4rem; }
        p { color: var(--muted); margin: 0 0 0.85rem; }
        .lead { font-size: 1.125rem; color: #cbd5e1; max-width: 48rem; }
        .hero {
            display: grid; gap: 1.25rem; align-items: center;
            grid-template-columns: 1.2fr 1fr; margin-bottom: 1.5rem;
        }
        @media (max-width: 860px) { .hero { grid-template-columns: 1fr; } }
        .hero-visual, .feature-visual {
            border-radius: 1rem; overflow: hidden; border: 1px solid var(--line);
            background: var(--card); min-height: 180px;
        }
        .hero-visual img, .feature-visual img { display: block; width: 100%; height: auto; }
        .grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .card {
            background: var(--card); border: 1px solid var(--line); border-radius: 0.9rem;
            padding: 1rem 1.1rem;
        }
        .card h3 { margin-top: 0; color: var(--text); }
        .card p { margin-bottom: 0.4rem; }
        .badge {
            display: inline-flex; align-items: center; gap: 0.35rem;
            font-size: 0.75rem; font-weight: 700; letter-spacing: 0.03em;
            text-transform: uppercase; color: var(--ok); margin-bottom: 0.5rem;
        }
        .badge.warn { color: #fbbf24; }
        ul { color: var(--muted); padding-left: 1.15rem; margin: 0.4rem 0 1rem; }
        li + li { margin-top: 0.3rem; }
        .cta-row { display: flex; flex-wrap: wrap; gap: 0.65rem; margin-top: 1rem; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0.7rem 1rem; border-radius: 0.65rem; font-weight: 700; font-size: 0.9375rem;
            border: 0; cursor: pointer; text-decoration: none !important;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-secondary { background: transparent; color: var(--text); border: 1px solid var(--line); }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; margin: 0.75rem 0 1.25rem; }
        th, td { text-align: left; padding: 0.55rem 0.65rem; border-bottom: 1px solid var(--line); vertical-align: top; }
        th { color: var(--text); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; }
        td { color: var(--muted); }
        .note {
            background: rgba(37,99,235,.12); border: 1px solid rgba(37,99,235,.35);
            color: #bfdbfe; border-radius: 0.75rem; padding: 0.85rem 1rem; font-size: 0.9rem;
        }
        .footer { margin-top: 2.5rem; padding-top: 1rem; border-top: 1px solid var(--line); color: var(--muted); font-size: 0.85rem; }
        code { font-size: 0.85em; background: rgba(15,23,42,.6); padding: 0.1rem 0.35rem; border-radius: 0.3rem; }
    </style>
</head>
<body>
@php
    $nav = [
        'index' => ['label' => 'Overzicht', 'url' => route('marketing.index')],
        'strategie' => ['label' => 'Strategie', 'url' => route('marketing.show', 'strategie')],
        'taxi' => ['label' => 'Taxi', 'url' => route('marketing.show', 'taxi')],
        'contractvervoer' => ['label' => 'Contractvervoer', 'url' => route('marketing.show', 'contractvervoer')],
        'skillmatching' => ['label' => 'Skillmatching', 'url' => route('marketing.show', 'skillmatching')],
        'website' => ['label' => 'Website', 'url' => route('marketing.show', 'website')],
        'website-copy' => ['label' => 'Site-copy', 'url' => route('marketing.show', 'website-copy')],
    ];
    $pageKey = $pageKey ?? 'index';
@endphp
<div class="wrap">
    <header class="topnav">
        <a class="brand" href="{{ route('marketing.index') }}">NEXA <span>Marketing</span></a>
        <nav class="nav-links" aria-label="Marketing">
            @foreach($nav as $key => $item)
                <a href="{{ $item['url'] }}" class="{{ $pageKey === $key ? 'is-active' : '' }}">{{ $item['label'] }}</a>
            @endforeach
        </nav>
    </header>
    @yield('content')
    <footer class="footer">
        Interne preview · niet voor tenants ·
        <a href="{{ route('home') }}">Naar homepage</a> ·
        Publiceren naar nexasuite.nl: zie onderaan <a href="{{ route('marketing.show', 'website-copy') }}">Site-copy</a>
    </footer>
</div>
</body>
</html>
