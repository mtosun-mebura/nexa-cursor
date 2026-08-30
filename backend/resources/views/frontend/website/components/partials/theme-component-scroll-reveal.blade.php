@once
<style>
    [data-theme-component].theme-scroll-reveal .theme-fade {
        opacity: 0;
        transition: opacity 0.55s ease;
        transition-delay: var(--theme-reveal-delay, 0ms);
    }
    [data-theme-component].theme-scroll-reveal.is-in-view .theme-fade { opacity: 1; }

    /* FAQ: links uitklappen als een gordijn */
    [data-theme-anim="wipe"] .theme-reveal-item {
        opacity: 0;
        transform: translateX(-28px);
        clip-path: inset(0 70% 0 0);
        transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1), clip-path 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        transition-delay: var(--theme-reveal-delay, 0ms);
    }
    [data-theme-anim="wipe"].is-in-view .theme-reveal-item {
        opacity: 1;
        transform: none;
        clip-path: inset(0 0 0 0);
    }

    /* Merken: eerst stuk voor stuk inladen, daarna de ticker */
    [data-theme-anim="marquee"] .theme-marquee {
        overflow: hidden;
        mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
        -webkit-mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
    }
    [data-theme-anim="marquee"] .theme-marquee__track {
        display: flex;
        width: max-content;
        gap: 0.75rem;
    }
    [data-theme-anim="marquee"].is-in-view .theme-marquee__track {
        animation: theme-marquee 32s linear infinite;
        animation-delay: var(--theme-marquee-delay, 600ms);
    }
    [data-theme-anim="marquee"] .theme-marquee:hover .theme-marquee__track { animation-play-state: paused; }
    @keyframes theme-marquee {
        from { transform: translateX(0); }
        to { transform: translateX(-50%); }
    }
    [data-theme-anim="marquee"] .theme-brand-item {
        opacity: 0;
        transform: translateX(-22px) scale(0.92);
        transition: opacity 0.28s ease, transform 0.32s cubic-bezier(0.16, 1, 0.3, 1);
        transition-delay: var(--theme-reveal-delay, 0ms);
    }
    [data-theme-anim="marquee"].is-in-view .theme-brand-item {
        opacity: 1;
        transform: none;
    }

    /* Checklist: vinkjes tekenen */
    [data-theme-anim="check"] .theme-reveal-left {
        opacity: 0;
        transform: translateX(-48px) scale(0.96);
        transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }
    [data-theme-anim="check"] .theme-reveal-right {
        opacity: 0;
        transform: translateX(36px);
        transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        transition-delay: var(--theme-reveal-delay, 0ms);
    }
    [data-theme-anim="check"].is-in-view .theme-reveal-left,
    [data-theme-anim="check"].is-in-view .theme-reveal-right {
        opacity: 1;
        transform: none;
    }
    [data-theme-anim="check"] .theme-check-icon path {
        stroke-dasharray: 24;
        stroke-dashoffset: 24;
    }
    [data-theme-anim="check"].is-in-view .theme-check-icon path {
        animation: theme-check-draw 0.45s ease forwards;
        animation-delay: var(--theme-reveal-delay, 0ms);
    }
    @keyframes theme-check-draw {
        to { stroke-dashoffset: 0; }
    }

    /* Cijferstrip: pop + count-up */
    [data-theme-anim="pop"] .theme-stat-value {
        opacity: 0;
        transform: scale(0.45);
        filter: blur(8px);
        transform-origin: center bottom;
        transition: opacity 0.5s ease, transform 0.7s cubic-bezier(0.34, 1.4, 0.64, 1), filter 0.5s ease;
        transition-delay: var(--theme-reveal-delay, 0ms);
    }
    [data-theme-anim="pop"].is-in-view .theme-stat-value {
        opacity: 1;
        transform: scale(1);
        filter: blur(0);
    }
    [data-theme-anim="pop"] .theme-stat-rule {
        transform: scaleX(0);
        transform-origin: left center;
        transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        transition-delay: calc(var(--theme-reveal-delay, 0ms) + 200ms);
    }
    [data-theme-anim="pop"].is-in-view .theme-stat-rule { transform: scaleX(1); }

    /* Team: lichte 3D-flip, avatar springt in */
    [data-theme-anim="flip"] .theme-reveal-item {
        opacity: 0;
        transform: perspective(900px) rotateY(18deg) translateY(28px);
        transition: opacity 0.7s ease, transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        transition-delay: var(--theme-reveal-delay, 0ms);
    }
    [data-theme-anim="flip"].is-in-view .theme-reveal-item {
        opacity: 1;
        transform: perspective(900px) rotateY(0) translateY(0);
    }
    [data-theme-anim="flip"] .theme-avatar {
        transform: scale(0);
        transition: transform 0.55s cubic-bezier(0.34, 1.56, 0.64, 1);
        transition-delay: calc(var(--theme-reveal-delay, 0ms) + 160ms);
    }
    [data-theme-anim="flip"].is-in-view .theme-avatar { transform: scale(1); }

    /* Video: zoom + pulserende play */
    [data-theme-anim="video"] .theme-video-frame {
        opacity: 0;
        transform: scale(0.88);
        filter: saturate(0.6);
        transition: opacity 0.8s ease, transform 1s cubic-bezier(0.16, 1, 0.3, 1), filter 0.8s ease;
    }
    [data-theme-anim="video"].is-in-view .theme-video-frame {
        opacity: 1;
        transform: scale(1);
        filter: none;
    }
    [data-theme-anim="video"] .theme-play-ring {
        position: absolute;
        inset: -10px;
        border-radius: 9999px;
        border: 2px solid currentColor;
        opacity: 0;
    }
    [data-theme-anim="video"].is-in-view .theme-play-ring {
        animation: theme-play-ripple 2.2s ease-out infinite;
    }
    @keyframes theme-play-ripple {
        0% { transform: scale(0.85); opacity: 0.55; }
        100% { transform: scale(1.45); opacity: 0; }
    }

    /* Overlappende foto’s */
    [data-theme-anim="overlap"] .theme-overlap-a {
        opacity: 0;
        transform: translate(12px, 16px) rotate(-4deg);
        transition: opacity 0.8s ease, transform 0.9s cubic-bezier(0.16, 1, 0.3, 1);
    }
    [data-theme-anim="overlap"] .theme-overlap-b {
        opacity: 0;
        transform: translate(-12px, -16px) rotate(4deg);
        transition: opacity 0.8s ease, transform 0.95s cubic-bezier(0.16, 1, 0.3, 1);
        transition-delay: 140ms;
    }
    [data-theme-anim="overlap"].is-in-view .theme-overlap-a,
    [data-theme-anim="overlap"].is-in-view .theme-overlap-b {
        opacity: 1;
        transform: none;
    }

    /* Blog: beeld omhoog clippen */
    [data-theme-anim="clip"] .theme-reveal-item {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.6s ease, transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        transition-delay: var(--theme-reveal-delay, 0ms);
    }
    [data-theme-anim="clip"].is-in-view .theme-reveal-item {
        opacity: 1;
        transform: none;
    }
    [data-theme-anim="clip"] .theme-clip-img {
        clip-path: inset(100% 0 0 0);
        transition: clip-path 0.9s cubic-bezier(0.16, 1, 0.3, 1);
        transition-delay: calc(var(--theme-reveal-delay, 0ms) + 80ms);
    }
    [data-theme-anim="clip"].is-in-view .theme-clip-img { clip-path: inset(0); }

    /* Contact: kolommen van weerszijden */
    [data-theme-anim="split"] .theme-split-left {
        opacity: 0;
        transform: translateX(-40px);
        transition: opacity 0.75s ease, transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }
    [data-theme-anim="split"] .theme-split-right {
        opacity: 0;
        transform: translateX(40px);
        transition: opacity 0.75s ease, transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        transition-delay: 120ms;
    }
    [data-theme-anim="split"].is-in-view .theme-split-left,
    [data-theme-anim="split"].is-in-view .theme-split-right {
        opacity: 1;
        transform: none;
    }
    [data-theme-anim="split"] .theme-field {
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 0.45s ease, transform 0.45s ease;
        transition-delay: var(--theme-reveal-delay, 0ms);
    }
    [data-theme-anim="split"].is-in-view .theme-field {
        opacity: 1;
        transform: none;
    }

    /* Material: kaarten vallen van boven, chip veert */
    [data-theme-anim="drop"] .theme-reveal-item {
        opacity: 0;
        transform: translateY(-44px);
        transition: opacity 0.55s ease, transform 0.75s cubic-bezier(0.2, 0.8, 0.2, 1);
        transition-delay: var(--theme-reveal-delay, 0ms);
    }
    [data-theme-anim="drop"].is-in-view .theme-reveal-item {
        opacity: 1;
        transform: none;
    }
    [data-theme-anim="drop"] .theme-chip {
        transform: scale(0) translateY(8px);
        transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        transition-delay: calc(var(--theme-reveal-delay, 0ms) + 180ms);
    }
    [data-theme-anim="drop"].is-in-view .theme-chip { transform: scale(1) translateY(0); }

    /* Quotes: aanhalingsteken draait in */
    [data-theme-anim="quote"] .theme-reveal-item {
        opacity: 0;
        transform: rotate(-2.5deg) translateY(12px);
        transition: opacity 0.65s ease, transform 0.75s cubic-bezier(0.16, 1, 0.3, 1);
        transition-delay: var(--theme-reveal-delay, 0ms);
    }
    [data-theme-anim="quote"].is-in-view .theme-reveal-item {
        opacity: 1;
        transform: none;
    }
    [data-theme-anim="quote"] .theme-quote-mark {
        display: inline-block;
        transform: scale(0.15) rotate(-28deg);
        opacity: 0;
        transition: transform 0.7s cubic-bezier(0.34, 1.45, 0.64, 1), opacity 0.4s ease;
        transition-delay: calc(var(--theme-reveal-delay, 0ms) + 80ms);
    }
    [data-theme-anim="quote"].is-in-view .theme-quote-mark {
        transform: none;
        opacity: 1;
    }

    /* Material stats: blur → scherp + ring */
    [data-theme-anim="count"] .theme-reveal-item {
        opacity: 0;
        transform: scale(0.9);
        transition: opacity 0.5s ease, transform 0.65s cubic-bezier(0.16, 1, 0.3, 1);
        transition-delay: var(--theme-reveal-delay, 0ms);
    }
    [data-theme-anim="count"].is-in-view .theme-reveal-item {
        opacity: 1;
        transform: none;
    }
    [data-theme-anim="count"] .theme-count-blur {
        filter: blur(12px);
        letter-spacing: 0.12em;
        transition: filter 0.8s ease, letter-spacing 0.8s ease;
        transition-delay: var(--theme-reveal-delay, 0ms);
    }
    [data-theme-anim="count"].is-in-view .theme-count-blur {
        filter: blur(0);
        letter-spacing: 0;
    }

    /* Pills: schuiven langs, paneel fade */
    [data-theme-anim="pills"] .theme-pill {
        opacity: 0;
        transform: translateY(-16px);
        transition: opacity 0.4s ease, transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.25s ease, color 0.25s ease;
        transition-delay: var(--theme-reveal-delay, 0ms);
    }
    [data-theme-anim="pills"].is-in-view .theme-pill {
        opacity: 1;
        transform: none;
    }
    [data-theme-anim="pills"] .theme-pill-panel {
        display: none;
        opacity: 0;
        transform: translateY(12px);
    }
    [data-theme-anim="pills"] .theme-pill-panel.is-active {
        display: block;
        opacity: 1;
        transform: none;
        animation: theme-pill-in 0.35s ease;
    }
    @keyframes theme-pill-in {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: none; }
    }

    /* Auteur: cirkel-clip op foto */
    [data-theme-anim="portrait"] .theme-portrait {
        clip-path: circle(0% at 50% 50%);
        transition: clip-path 0.9s cubic-bezier(0.16, 1, 0.3, 1);
    }
    [data-theme-anim="portrait"].is-in-view .theme-portrait {
        clip-path: circle(75% at 50% 50%);
    }
    [data-theme-anim="portrait"] .theme-portrait-text {
        opacity: 0;
        transform: translateY(18px);
        transition: opacity 0.6s ease, transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        transition-delay: 180ms;
    }
    [data-theme-anim="portrait"].is-in-view .theme-portrait-text {
        opacity: 1;
        transform: none;
    }
    [data-theme-anim="portrait"] .theme-social {
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 0.4s ease, transform 0.45s ease;
        transition-delay: var(--theme-reveal-delay, 0ms);
    }
    [data-theme-anim="portrait"].is-in-view .theme-social {
        opacity: 1;
        transform: none;
    }

    [data-theme-component].theme-scroll-reveal.theme-anim-reset,
    [data-theme-component].theme-scroll-reveal.theme-anim-reset * {
        transition: none !important;
        animation: none !important;
    }

    @media (prefers-reduced-motion: reduce) {
        [data-theme-component].theme-scroll-reveal .theme-fade,
        [data-theme-anim="wipe"] .theme-reveal-item,
        [data-theme-anim="check"] .theme-reveal-left,
        [data-theme-anim="check"] .theme-reveal-right,
        [data-theme-anim="pop"] .theme-stat-value,
        [data-theme-anim="pop"] .theme-stat-rule,
        [data-theme-anim="flip"] .theme-reveal-item,
        [data-theme-anim="flip"] .theme-avatar,
        [data-theme-anim="video"] .theme-video-frame,
        [data-theme-anim="overlap"] .theme-overlap-a,
        [data-theme-anim="overlap"] .theme-overlap-b,
        [data-theme-anim="clip"] .theme-reveal-item,
        [data-theme-anim="clip"] .theme-clip-img,
        [data-theme-anim="split"] .theme-split-left,
        [data-theme-anim="split"] .theme-split-right,
        [data-theme-anim="split"] .theme-field,
        [data-theme-anim="drop"] .theme-reveal-item,
        [data-theme-anim="drop"] .theme-chip,
        [data-theme-anim="quote"] .theme-reveal-item,
        [data-theme-anim="quote"] .theme-quote-mark,
        [data-theme-anim="count"] .theme-reveal-item,
        [data-theme-anim="count"] .theme-count-blur,
        [data-theme-anim="pills"] .theme-pill,
        [data-theme-anim="portrait"] .theme-portrait,
        [data-theme-anim="portrait"] .theme-portrait-text,
        [data-theme-anim="portrait"] .theme-social,
        [data-theme-anim="marquee"] .theme-brand-item {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
            clip-path: none !important;
            letter-spacing: 0 !important;
            transition: none !important;
            animation: none !important;
        }
        [data-theme-anim="marquee"].is-in-view .theme-marquee__track,
        [data-theme-anim="video"].is-in-view .theme-play-ring {
            animation: none !important;
        }
    }
</style>
<script>
(function () {
    function animateCount(node) {
        if (node.getAttribute('data-theme-count-done') === '1') return;
        node.setAttribute('data-theme-count-done', '1');
        var target = parseFloat(String(node.getAttribute('data-theme-count') || '0').replace(',', '.'));
        if (isNaN(target)) return;
        var prefix = node.getAttribute('data-theme-count-prefix') || '';
        var suffix = node.getAttribute('data-theme-count-suffix') || '';
        var decimals = parseInt(node.getAttribute('data-theme-count-decimals') || '0', 10);
        if (isNaN(decimals) || decimals < 0) decimals = 0;
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        function format(val) {
            var n = decimals > 0 ? val.toFixed(decimals) : String(Math.round(val));
            return prefix + n + suffix;
        }
        if (reduce) {
            node.textContent = format(target);
            return;
        }
        var start = null;
        var dur = 1100;
        function frame(ts) {
            if (!start) start = ts;
            var t = Math.min(1, (ts - start) / dur);
            var eased = 1 - Math.pow(1 - t, 3);
            node.textContent = format(target * eased);
            if (t < 1) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    }
    function bindPills(section) {
        var pills = section.querySelectorAll('[data-theme-pill]');
        var panels = section.querySelectorAll('[data-theme-pill-panel]');
        if (!pills.length || !panels.length) return;
        pills.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-theme-pill');
                var color = btn.getAttribute('data-theme-pill-color') || '#e91e63';
                pills.forEach(function (p) {
                    var on = p === btn;
                    p.classList.toggle('is-active', on);
                    p.setAttribute('aria-selected', on ? 'true' : 'false');
                    if (on) {
                        p.style.background = color;
                        p.style.color = '#fff';
                        p.style.borderColor = 'transparent';
                    } else {
                        p.style.background = 'transparent';
                        p.style.color = '';
                        p.style.borderColor = '';
                    }
                });
                panels.forEach(function (panel) {
                    panel.classList.toggle('is-active', panel.getAttribute('data-theme-pill-panel') === id);
                });
            });
        });
    }
    function mark(el) {
        el.classList.add('is-in-view');
        el.querySelectorAll('[data-theme-count]').forEach(animateCount);
    }
    window.nexaReplayThemeAnimations = function (root) {
        root = root || document;
        var sections = root.querySelectorAll('[data-theme-component].theme-scroll-reveal');
        if (!sections.length) {
            sections = root.querySelectorAll('[data-scroll-reveal], .scroll-reveal-section, .theme-scroll-reveal');
        }
        if (!sections.length) return;
        sections.forEach(function (el) {
            el.classList.remove('is-in-view', 'is-visible');
            el.classList.add('theme-anim-reset');
            el.querySelectorAll('[data-theme-count]').forEach(function (n) {
                n.removeAttribute('data-theme-count-done');
                var suffix = n.getAttribute('data-theme-count-suffix') || '';
                var prefix = n.getAttribute('data-theme-count-prefix') || '';
                var decimals = parseInt(n.getAttribute('data-theme-count-decimals') || '0', 10);
                n.textContent = prefix + (decimals > 0 ? Number(0).toFixed(decimals) : '0') + suffix;
            });
            el.querySelectorAll('.theme-check-icon path, .theme-marquee__track, .theme-play-ring').forEach(function (node) {
                node.style.animation = 'none';
            });
        });
        void root.offsetHeight;
        window.setTimeout(function () {
            sections.forEach(function (el) {
                el.classList.remove('theme-anim-reset');
                el.querySelectorAll('.theme-check-icon path, .theme-marquee__track, .theme-play-ring').forEach(function (node) {
                    node.style.animation = '';
                });
                void el.offsetWidth;
                mark(el);
            });
        }, 50);
    };
    function inViewport(el) {
        var r = el.getBoundingClientRect();
        return r.width > 0 && r.height > 0 && r.bottom > 0 && r.top < (window.innerHeight || document.documentElement.clientHeight);
    }
    function bind() {
        var sections = document.querySelectorAll('[data-theme-component].theme-scroll-reveal');
        if (!sections.length) return;
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        sections.forEach(function (el) {
            if (el.getAttribute('data-theme-pills-bound') !== '1') {
                el.setAttribute('data-theme-pills-bound', '1');
                bindPills(el);
            }
        });
        if (reduce || !('IntersectionObserver' in window)) {
            sections.forEach(mark);
            return;
        }
        if (!window.__nexaThemeScrollRevealIo) {
            window.__nexaThemeScrollRevealIo = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    mark(entry.target);
                    window.__nexaThemeScrollRevealIo.unobserve(entry.target);
                });
            }, { rootMargin: '0px 0px 18% 0px', threshold: 0.01 });
        }
        sections.forEach(function (el) {
            if (el.getAttribute('data-theme-reveal-bound') === '1') return;
            el.setAttribute('data-theme-reveal-bound', '1');
            if (inViewport(el)) {
                mark(el);
                return;
            }
            window.__nexaThemeScrollRevealIo.observe(el);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind);
    bind();
    setTimeout(bind, 80);
})();
</script>
@endonce
