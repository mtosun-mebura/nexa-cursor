{{-- Gedeelde carousel-init (Flowbite-style): prev/next, indicators, autoplay, captions. --}}
<script>
(function () {
    function initCarousels(rootScope) {
        var scope = rootScope && rootScope.querySelectorAll ? rootScope : document;
        var carousels = scope.querySelectorAll('[data-carousel="slide"], [data-carousel="static"]');
        carousels.forEach(function (root) {
            if (root.getAttribute('data-carousel-ready') === '1') {
                return;
            }
            var items = root.querySelectorAll('[data-carousel-item]');
            if (!items.length) {
                return;
            }
            root.setAttribute('data-carousel-ready', '1');
            var indicators = root.querySelectorAll('[data-carousel-slide-to]');
            var prevBtn = root.querySelector('[data-carousel-prev]');
            var nextBtn = root.querySelector('[data-carousel-next]');
            var current = 0;
            var isSlide = root.getAttribute('data-carousel') === 'slide';
            var interval = null;
            var intervalMs = null;
            var intervalSecAttr = root.getAttribute('data-carousel-interval');
            if (intervalSecAttr !== null && intervalSecAttr !== '') {
                var intervalSec = parseInt(intervalSecAttr, 10);
                if (!isNaN(intervalSec) && intervalSec > 0) {
                    intervalMs = intervalSec * 1000;
                }
            } else if (isSlide) {
                intervalMs = 5000;
            }

            function restartAutoplay() {
                if (interval) {
                    clearInterval(interval);
                }
                interval = null;
                if (isSlide && intervalMs) {
                    interval = setInterval(next, intervalMs);
                }
            }

            function playCarouselCaption(slideEl) {
                root.querySelectorAll('[data-carousel-caption]').forEach(function (c) {
                    c.classList.remove('is-visible');
                });
                if (!slideEl) {
                    return;
                }
                var cap = slideEl.querySelector('[data-carousel-caption]');
                if (!cap) {
                    return;
                }
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        cap.classList.add('is-visible');
                    });
                });
            }

            function show(pos) {
                var n = items.length;
                var nextIndex = ((pos % n) + n) % n;
                var prevIndex = current;
                if (nextIndex === prevIndex && items[prevIndex] && items[prevIndex].classList.contains('opacity-100')) {
                    indicators.forEach(function (btn, i) {
                        btn.setAttribute('aria-current', i === current ? 'true' : 'false');
                        btn.style.background = i === current ? '#ffffff' : '#9ca3af';
                    });
                    playCarouselCaption(items[nextIndex]);
                    return;
                }
                current = nextIndex;
                items.forEach(function (el, i) {
                    if (i === current) {
                        el.classList.remove('opacity-0', 'pointer-events-none', 'z-0');
                        el.classList.add('opacity-100', 'z-20');
                        el.setAttribute('data-carousel-item', 'active');
                    } else if (i === prevIndex) {
                        el.classList.remove('opacity-100', 'z-20', 'z-0');
                        el.classList.add('opacity-0', 'z-10', 'pointer-events-none');
                        el.setAttribute('data-carousel-item', '');
                        (function (outgoing) {
                            function onFadeEnd(e) {
                                if (e.propertyName !== 'opacity') {
                                    return;
                                }
                                outgoing.classList.remove('z-10');
                                outgoing.classList.add('z-0');
                                outgoing.removeEventListener('transitionend', onFadeEnd);
                            }
                            outgoing.addEventListener('transitionend', onFadeEnd);
                        })(el);
                    } else {
                        el.classList.remove('opacity-100', 'z-20', 'z-10');
                        el.classList.add('opacity-0', 'z-0', 'pointer-events-none');
                        el.setAttribute('data-carousel-item', '');
                    }
                });
                indicators.forEach(function (btn, i) {
                    btn.setAttribute('aria-current', i === current ? 'true' : 'false');
                    btn.style.background = i === current ? '#ffffff' : '#9ca3af';
                });
                playCarouselCaption(items[current]);
            }

            function next() {
                show(current + 1);
            }
            function prev() {
                show(current - 1);
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    prev();
                    restartAutoplay();
                });
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    next();
                    restartAutoplay();
                });
            }
            indicators.forEach(function (btn, i) {
                btn.addEventListener('click', function () {
                    show(i);
                    restartAutoplay();
                });
            });

            root._nexaCarouselShow = show;
            root._nexaCarouselRestart = function () {
                show(0);
                restartAutoplay();
            };

            show(0);
            restartAutoplay();
        });
    }

    function restartCarousels() {
        document.querySelectorAll('[data-carousel="slide"], [data-carousel="static"]').forEach(function (root) {
            if (typeof root._nexaCarouselRestart === 'function') {
                root._nexaCarouselRestart();
            }
        });
    }

    window.nexaInitCarousels = initCarousels;
    window.nexaRestartCarousels = restartCarousels;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initCarousels(document);
        });
    } else {
        initCarousels(document);
    }
})();
</script>
