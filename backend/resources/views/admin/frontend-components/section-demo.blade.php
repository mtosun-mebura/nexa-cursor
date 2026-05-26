@php
    $type = trim((string) ($sectionType ?? ''));
    $name = trim((string) ($sectionName ?? 'Sectie demo'));
@endphp

<div class="p-6 space-y-4 bg-background">
    <div class="rounded-lg border border-border bg-muted/30 p-4">
        <h3 class="text-lg font-semibold text-foreground">{{ $name }}</h3>
        <p class="text-sm text-muted-foreground mt-1">Voorbeeldweergave van ingebouwde sectie <code>section.{{ $type }}</code>.</p>
    </div>

    @if($type === 'hero')
        <section class="rounded-xl border border-border p-8 bg-gradient-to-r from-blue-700 to-indigo-700 text-white section-demo-reveal">
            <h2 class="text-3xl font-bold mb-3">Vind je droombaan met AI</h2>
            <p class="opacity-90 mb-5">Volledige hero-demo met CTA's zoals op de website-homepage.</p>
            <div class="flex gap-3">
                <span class="px-4 py-2 rounded-md bg-white text-blue-700 font-medium">Primair</span>
                <span class="px-4 py-2 rounded-md border border-white/70">Secundair</span>
            </div>
        </section>
    @elseif($type === 'stats')
        <section class="grid grid-cols-2 md:grid-cols-4 gap-3" data-section-stats-demo>
            @foreach([
                ['display' => '10000+', 'value' => 10000, 'suffix' => '+', 'label' => 'Vacatures'],
                ['display' => '95%', 'value' => 95, 'suffix' => '%', 'label' => 'Matchscore'],
                ['display' => '500+', 'value' => 500, 'suffix' => '+', 'label' => 'Bedrijven'],
                ['display' => '24/7', 'value' => null, 'suffix' => '', 'label' => 'Beschikbaar'],
            ] as $stat)
                <div class="rounded-lg border border-border bg-background p-4 text-center section-demo-reveal">
                    <div class="text-2xl font-bold text-foreground" @if($stat['value'] !== null) data-countup-target="{{ $stat['value'] }}" data-countup-suffix="{{ $stat['suffix'] }}" @endif>
                        {{ $stat['display'] }}
                    </div>
                    <div class="text-xs text-muted-foreground mt-1">{{ $stat['label'] }}</div>
                </div>
            @endforeach
        </section>
    @elseif($type === 'why_nexa')
        <section class="rounded-xl border border-border bg-background p-8 section-demo-reveal">
            <h2 class="text-3xl font-bold text-foreground mb-3">Waarom NEXA?</h2>
            <p class="text-muted-foreground mb-6">Wij combineren slimme matching, snelle onboarding en heldere rapportages voor groeiende teams.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach([
                    ['title' => 'AI Matching', 'text' => 'Automatische matchscore op basis van profieldata en vacaturecontext.'],
                    ['title' => 'Snelle Setup', 'text' => 'Binnen een dag live met je eerste flow en formulieren.'],
                    ['title' => 'Inzicht & Groei', 'text' => 'Realtime inzicht in funnel, conversie en candidate quality.'],
                ] as $item)
                    <article class="rounded-lg border border-border bg-muted/20 p-4 section-demo-reveal">
                        <h3 class="font-semibold text-foreground mb-1">{{ $item['title'] }}</h3>
                        <p class="text-sm text-muted-foreground">{{ $item['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @elseif($type === 'features')
        <section class="rounded-xl border border-border bg-background p-8 space-y-4">
            <h2 class="text-3xl font-bold text-foreground section-demo-reveal">Kenmerken</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach([
                    ['icon' => 'ki-rocket', 'title' => 'Snelle publicatie', 'text' => 'Plaats vacatures en landingspagina’s in minuten.'],
                    ['icon' => 'ki-security-check', 'title' => 'Veilig & schaalbaar', 'text' => 'Multi-tenant architectuur met rolgebaseerde toegang.'],
                    ['icon' => 'ki-chart-line-up', 'title' => 'Datagedreven', 'text' => 'Duidelijke KPI widgets voor team en management.'],
                    ['icon' => 'ki-abstract-26', 'title' => 'Flexibele modules', 'text' => 'Kies per bedrijf de juiste feature set en thema’s.'],
                ] as $feature)
                    <article class="rounded-lg border border-border p-4 section-demo-reveal">
                        <div class="flex items-start gap-3">
                            <i class="ki-filled {{ $feature['icon'] }} text-primary text-xl mt-0.5"></i>
                            <div>
                                <h3 class="font-semibold text-foreground">{{ $feature['title'] }}</h3>
                                <p class="text-sm text-muted-foreground mt-1">{{ $feature['text'] }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @elseif($type === 'cta')
        <section class="rounded-xl border border-border bg-background p-8 text-center section-demo-reveal">
            <h2 class="text-2xl font-bold text-foreground">Klaar om te starten?</h2>
            <p class="text-muted-foreground mt-2 mb-5">Demo van een call-to-action sectie.</p>
            <span class="inline-flex px-5 py-2 rounded-md bg-primary text-primary-foreground">Bekijk mogelijkheden</span>
        </section>
    @elseif($type === 'carousel')
        <section class="space-y-4">
            <div class="rounded-lg border border-border bg-muted/30 p-4">
                <h3 class="text-lg font-semibold text-foreground">Natuur carousel demo</h3>
                <p class="text-sm text-muted-foreground mt-1">Voorbeeld met dummy natuurfoto's.</p>
            </div>
            <div class="relative rounded-xl border border-border bg-background shadow-sm overflow-hidden section-demo-reveal" data-carousel-demo>
                <div class="relative h-64 md:h-80">
                    @foreach([
                        'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1400&q=80',
                        'https://images.unsplash.com/photo-1448375240586-882707db888b?auto=format&fit=crop&w=1400&q=80',
                        'https://images.unsplash.com/photo-1470770841072-f978cf4d019e?auto=format&fit=crop&w=1400&q=80',
                        'https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=1400&q=80',
                    ] as $idx => $img)
                        <div class="absolute inset-0 transition-opacity duration-700 {{ $idx === 0 ? 'opacity-100' : 'opacity-0 pointer-events-none' }}" data-carousel-slide>
                            <img src="{{ $img }}" alt="Natuur demo afbeelding {{ $idx + 1 }}" class="w-full h-full object-cover" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                            <div class="absolute left-4 bottom-4 text-white text-sm md:text-base font-medium drop-shadow">Natuur afbeelding {{ $idx + 1 }}</div>
                        </div>
                    @endforeach
                </div>

                <button type="button" class="absolute left-3 top-1/2 -translate-y-1/2 z-10 inline-flex items-center justify-center h-9 w-9 rounded-full bg-black/45 hover:bg-black/65 text-white transition-colors" data-carousel-prev aria-label="Vorige afbeelding">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 z-10 inline-flex items-center justify-center h-9 w-9 rounded-full bg-black/45 hover:bg-black/65 text-white transition-colors" data-carousel-next aria-label="Volgende afbeelding">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>

                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-10 flex items-center gap-2" data-carousel-dots></div>
            </div>
        </section>
    @elseif($type === 'cards_ronde_hoeken')
        <section class="rounded-xl border border-border bg-background p-8">
            <h2 class="text-2xl font-bold text-foreground mb-4 section-demo-reveal">Cards met ronde hoeken</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach([
                    ['title' => 'Recruitment', 'text' => 'Slimme kandidaatmatching en snelle opvolging.'],
                    ['title' => 'Planning', 'text' => 'Overzichtelijke agenda en taken per team.'],
                    ['title' => 'Rapportage', 'text' => 'Direct inzicht in prestaties en groeikansen.'],
                ] as $card)
                    <article class="rounded-2xl border border-border bg-muted/20 p-5 shadow-sm section-demo-reveal">
                        <h3 class="font-semibold text-foreground">{{ $card['title'] }}</h3>
                        <p class="text-sm text-muted-foreground mt-2">{{ $card['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @elseif($type === 'featured_services')
        <section class="rounded-xl border border-border bg-background p-8">
            <h2 class="text-2xl font-bold text-foreground mb-4 section-demo-reveal">Uitgelichte diensten</h2>
            <div class="space-y-3">
                @foreach([
                    ['title' => 'Implementatie', 'text' => 'Volledige onboarding inclusief data-import en setup.'],
                    ['title' => 'Consultancy', 'text' => 'Strategisch advies voor procesoptimalisatie.'],
                    ['title' => 'Support', 'text' => 'Snelle ondersteuning en doorlopende verbeteringen.'],
                ] as $service)
                    <article class="rounded-lg border border-border bg-muted/20 p-4 section-demo-reveal">
                        <div class="font-semibold text-foreground">{{ $service['title'] }}</div>
                        <p class="text-sm text-muted-foreground mt-1">{{ $service['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @elseif($type === 'email_template')
        <section class="rounded-xl border border-border bg-background p-8 section-demo-reveal">
            <h2 class="text-2xl font-bold text-foreground mb-2">Informatie aanvragen</h2>
            <p class="text-sm text-muted-foreground mb-6">Dummy formulier zoals een e-mailtemplate-sectie op de website.</p>
            <form class="grid grid-cols-1 md:grid-cols-2 gap-4" data-email-template-demo-form>
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Naam</label>
                    <input type="text" class="kt-input w-full" value="Jan Demo">
                </div>
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">E-mail</label>
                    <input type="email" class="kt-input w-full" value="jan.demo@nexa.test">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-foreground mb-1">Onderwerp</label>
                    <input type="text" class="kt-input w-full" value="Aanvraag productdemo">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-foreground mb-1">Bericht</label>
                    <textarea class="kt-input w-full" rows="4">Graag ontvang ik meer informatie over de mogelijkheden voor ons team.</textarea>
                </div>
                <div class="md:col-span-2 flex items-center gap-3">
                    <button type="submit" class="kt-btn kt-btn-primary">Verzenden (demo)</button>
                    <span class="text-sm text-green-600 hidden" data-email-template-demo-success>Demo verzonden: formulier werkt in preview.</span>
                </div>
            </form>
        </section>
    @elseif($type === 'text_block')
        <section class="rounded-xl border border-border bg-background p-8">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">
                <article class="lg:col-span-3 prose max-w-none section-demo-reveal text-foreground">
                    <h2>Tekstblok met rijke content</h2>
                    <p>Dit is een voorbeeld van een <strong>rich text</strong> blok met opmaak, links en CTA’s zoals het op de website gebruikt wordt.</p>
                    <ul>
                        <li>Duidelijke structuur voor contentblokken</li>
                        <li>Combinatie met componenten of media</li>
                        <li>Geschikt voor landingspagina’s</li>
                    </ul>
                    <p><a href="#" class="text-primary">Lees meer over deze module</a></p>
                </article>
                <aside class="lg:col-span-2 rounded-xl border border-border overflow-hidden section-demo-reveal">
                    <img src="https://images.unsplash.com/photo-1519337265831-281ec6cc8514?auto=format&fit=crop&w=1100&q=80" alt="Dummy afbeelding naast tekstblok" class="w-full h-56 object-cover">
                </aside>
            </div>
        </section>
    @else
        <section class="rounded-xl border border-border bg-background p-8">
            <div class="text-foreground font-medium">Demo beschikbaar</div>
            <p class="text-sm text-muted-foreground mt-2">Deze sectie heeft een basisvoorbeeld zodat je het type kunt testen op de demo-pagina.</p>
        </section>
    @endif
</div>

@once
    @push('styles')
        <style>
            .section-demo-reveal {
                opacity: 0;
                transform: translateY(18px);
                transition: opacity .5s ease, transform .55s ease;
            }
            .section-demo-reveal.is-visible {
                opacity: 1;
                transform: translateY(0);
            }
        </style>
    @endpush
    @push('scripts')
        <script>
            (function () {
                function animateCount(el) {
                    var target = parseInt(el.getAttribute('data-countup-target') || '0', 10);
                    var suffix = el.getAttribute('data-countup-suffix') || '';
                    if (!isFinite(target) || target <= 0) return;

                    var duration = 1400;
                    var start = 0;
                    var startedAt = null;

                    function step(ts) {
                        if (startedAt === null) startedAt = ts;
                        var progress = Math.min((ts - startedAt) / duration, 1);
                        var eased = 1 - Math.pow(1 - progress, 3);
                        var value = Math.floor(start + (target - start) * eased);
                        el.textContent = value.toLocaleString('nl-NL') + suffix;
                        if (progress < 1) {
                            window.requestAnimationFrame(step);
                        }
                    }

                    window.requestAnimationFrame(step);
                }

                function initStatsDemo() {
                    var blocks = document.querySelectorAll('[data-section-stats-demo]');
                    blocks.forEach(function (block) {
                        if (block.getAttribute('data-countup-init') === '1') return;
                        block.setAttribute('data-countup-init', '1');

                        var counters = Array.prototype.slice.call(block.querySelectorAll('[data-countup-target]'));
                        if (!counters.length) return;
                        counters.forEach(function (el) {
                            var suffix = el.getAttribute('data-countup-suffix') || '';
                            el.textContent = '0' + suffix;
                        });

                        if (!('IntersectionObserver' in window)) {
                            window.setTimeout(function () {
                                counters.forEach(animateCount);
                            }, 180);
                            return;
                        }

                        var fired = false;
                        var observer = new IntersectionObserver(function (entries) {
                            entries.forEach(function (entry) {
                                if (fired || !entry.isIntersecting) return;
                                fired = true;
                                window.setTimeout(function () {
                                    counters.forEach(animateCount);
                                }, 180);
                                observer.disconnect();
                            });
                        }, { threshold: 0.35 });

                        observer.observe(block);
                    });
                }

                function initCarouselDemo() {
                    var carousels = document.querySelectorAll('[data-carousel-demo]');
                    carousels.forEach(function (root) {
                        if (root.getAttribute('data-carousel-init') === '1') return;
                        root.setAttribute('data-carousel-init', '1');

                        var slides = Array.prototype.slice.call(root.querySelectorAll('[data-carousel-slide]'));
                        var prevBtn = root.querySelector('[data-carousel-prev]');
                        var nextBtn = root.querySelector('[data-carousel-next]');
                        var dotsWrap = root.querySelector('[data-carousel-dots]');
                        if (!slides.length) return;

                        var index = 0;
                        var autoplayId = null;
                        var autoplayMs = 3500;
                        var dots = [];

                        function render() {
                            slides.forEach(function (slide, i) {
                                var active = i === index;
                                slide.classList.toggle('opacity-100', active);
                                slide.classList.toggle('opacity-0', !active);
                                slide.classList.toggle('pointer-events-none', !active);
                            });
                            dots.forEach(function (dot, i) {
                                var active = i === index;
                                dot.classList.toggle('bg-white', active);
                                dot.classList.toggle('w-6', active);
                                dot.classList.toggle('bg-white/45', !active);
                                dot.classList.toggle('w-2.5', !active);
                                dot.setAttribute('aria-selected', active ? 'true' : 'false');
                            });
                        }

                        function goTo(nextIndex) {
                            index = (nextIndex + slides.length) % slides.length;
                            render();
                        }

                        function startAutoplay() {
                            stopAutoplay();
                            autoplayId = window.setInterval(function () {
                                goTo(index + 1);
                            }, autoplayMs);
                        }

                        function stopAutoplay() {
                            if (autoplayId) {
                                window.clearInterval(autoplayId);
                                autoplayId = null;
                            }
                        }

                        if (dotsWrap) {
                            slides.forEach(function (_, i) {
                                var dot = document.createElement('button');
                                dot.type = 'button';
                                dot.className = 'h-2.5 rounded-full transition-all duration-200 bg-white/45 w-2.5';
                                dot.setAttribute('aria-label', 'Ga naar afbeelding ' + (i + 1));
                                dot.addEventListener('click', function () {
                                    goTo(i);
                                    startAutoplay();
                                });
                                dotsWrap.appendChild(dot);
                                dots.push(dot);
                            });
                        }

                        if (prevBtn) prevBtn.addEventListener('click', function () { goTo(index - 1); startAutoplay(); });
                        if (nextBtn) nextBtn.addEventListener('click', function () { goTo(index + 1); startAutoplay(); });

                        root.addEventListener('mouseenter', stopAutoplay);
                        root.addEventListener('mouseleave', startAutoplay);

                        render();
                        startAutoplay();
                    });
                }

                function initRevealDemo() {
                    var items = Array.prototype.slice.call(document.querySelectorAll('.section-demo-reveal'));
                    if (!items.length) return;

                    if (!('IntersectionObserver' in window)) {
                        items.forEach(function (el) { el.classList.add('is-visible'); });
                        return;
                    }

                    var observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (!entry.isIntersecting) return;
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        });
                    }, { threshold: 0.2 });

                    items.forEach(function (el) { observer.observe(el); });
                }

                function initEmailTemplateDemoForm() {
                    var forms = document.querySelectorAll('[data-email-template-demo-form]');
                    forms.forEach(function (form) {
                        if (form.getAttribute('data-demo-init') === '1') return;
                        form.setAttribute('data-demo-init', '1');
                        form.addEventListener('submit', function (e) {
                            e.preventDefault();
                            var success = form.querySelector('[data-email-template-demo-success]');
                            if (!success) return;
                            success.classList.remove('hidden');
                            window.setTimeout(function () {
                                success.classList.add('hidden');
                            }, 2600);
                        });
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function () {
                        initStatsDemo();
                        initCarouselDemo();
                        initRevealDemo();
                        initEmailTemplateDemoForm();
                    }, { once: true });
                } else {
                    initStatsDemo();
                    initCarouselDemo();
                    initRevealDemo();
                    initEmailTemplateDemoForm();
                }
            })();
        </script>
    @endpush
@endonce
