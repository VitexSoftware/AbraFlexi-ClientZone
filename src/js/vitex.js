/*
 * Vitex Software – UI behaviour: light/dark toggle, sticky header, fade-in on scroll,
 * tabs, the rotating MultiFlexi feed and the hero parallax.
 */
(function () {
    'use strict';

    var root = document.documentElement;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function setTheme(theme) {
        root.setAttribute('data-theme', theme);
        root.setAttribute('data-bs-theme', theme);
        try { localStorage.setItem('vsTheme', theme); } catch (e) { /* private mode */ }
    }

    function ready() {
        document.querySelectorAll('.theme-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                setTheme(root.getAttribute('data-theme') === 'light' ? 'dark' : 'light');
            });
        });

        var header = document.querySelector('.site-header');
        if (header) {
            var onScroll = function () { header.classList.toggle('scrolled', window.scrollY > 8); };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        }

        // .reveal elements fade in when they scroll into view (stagger via --d).
        var reveals = document.querySelectorAll('.reveal');
        if (reduce || !('IntersectionObserver' in window)) {
            reveals.forEach(function (el) { el.classList.add('in'); });
        } else {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
            reveals.forEach(function (el) { io.observe(el); });
        }

        // Tabs with cross-fading panels: [data-tabs] > [data-tab="x"] + [data-panel="x"]
        document.querySelectorAll('[data-tabs]').forEach(function (tabs) {
            tabs.querySelectorAll('[data-tab]').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    var key = tab.getAttribute('data-tab');
                    tabs.querySelectorAll('[data-tab]').forEach(function (t) {
                        t.classList.toggle('active', t === tab);
                        t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
                    });
                    tabs.querySelectorAll('[data-panel]').forEach(function (panel) {
                        panel.classList.toggle('active', panel.getAttribute('data-panel') === key);
                    });
                });
            });
        });

        // Rotating "live" feed: [data-cycle="N"] shows N items and fades in the next one.
        document.querySelectorAll('[data-cycle]').forEach(function (list) {
            var items = Array.prototype.slice.call(list.querySelectorAll('.cycle-item'));
            var visible = parseInt(list.getAttribute('data-cycle'), 10) || 3;
            var next = visible;
            items.forEach(function (item, i) { item.classList.toggle('shown', i < visible); });
            if (reduce || items.length <= visible) {
                return;
            }
            setInterval(function () {
                var shown = items.filter(function (item) { return item.classList.contains('shown'); });
                shown[0].classList.remove('shown');
                var item = items[next % items.length];
                list.appendChild(item);
                window.requestAnimationFrame(function () { item.classList.add('shown'); });
                next++;
            }, 2600);
        });

        var scene = document.querySelector('.landscape svg');
        if (scene && reduce && scene.pauseAnimations) {
            scene.pauseAnimations();
        }

        // Gentle parallax of the planet horizon.
        var ridges = document.querySelectorAll('.landscape .ridge');
        if (ridges.length && !reduce) {
            window.addEventListener('scroll', function () {
                var y = Math.min(window.scrollY, 900);
                ridges.forEach(function (ridge) {
                    ridge.style.transform = 'translateY(' + (-y * ridge.getAttribute('data-depth') / 300) + 'px)';
                });
            }, { passive: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ready);
    } else {
        ready();
    }
})();
