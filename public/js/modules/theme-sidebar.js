(function () {
    const STORAGE_KEY = 'lms_theme';

    function getPreferredTheme() {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'dark' || stored === 'light') return stored;
        return 'light';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);

        document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
            const isDark = theme === 'dark';
            btn.setAttribute('aria-label', isDark ? 'Beralih ke mode terang' : 'Beralih ke mode gelap');
            btn.setAttribute('title', isDark ? 'Mode Terang' : 'Mode Gelap');
            if (isDark) {
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`;
            } else {
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
            }
        });
    }

    applyTheme(getPreferredTheme());

    document.addEventListener('DOMContentLoaded', () => {
        applyTheme(getPreferredTheme());

        document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                applyTheme(next);
            });
        });

        const menuBtn = document.getElementById('mobileMenuBtn');
        const closeBtn = document.getElementById('menuClose');
        const collapseBtn = document.getElementById('sidebarCollapseBtn');
        const panel = document.getElementById('mobileNavPanel');
        const backdrop = document.getElementById('mobileNavBackdrop');
        const sidebarMenu = document.getElementById('sidebarMenu');
        const indicator = document.getElementById('sidebarIndicator');

        function moveIndicator(target) {
            if (!sidebarMenu || !indicator) return;
            const links = sidebarMenu.querySelectorAll('.sidebar-link');
            links.forEach(l => l.classList.remove('is-highlighted'));
            if (!target || target.offsetHeight === 0) {
                sidebarMenu.classList.remove('has-indicator');
                return;
            }
            sidebarMenu.classList.add('has-indicator');
            target.classList.add('is-highlighted');
            indicator.style.setProperty('--ind-top', target.offsetTop + 'px');
            indicator.style.setProperty('--ind-height', target.offsetHeight + 'px');
        }

        if (sidebarMenu && indicator) {
            const links = Array.from(sidebarMenu.querySelectorAll('.sidebar-link'));
            const getActiveLink = () => sidebarMenu.querySelector('.sidebar-link.active') || links[0];

            requestAnimationFrame(() => moveIndicator(getActiveLink()));

            links.forEach(link => {
                link.addEventListener('mouseenter', () => moveIndicator(link));
                link.addEventListener('focus', () => moveIndicator(link));
            });

            sidebarMenu.addEventListener('mouseleave', () => moveIndicator(getActiveLink()));
            window.addEventListener('resize', () => moveIndicator(getActiveLink()));
        }

        if (collapseBtn) {
            collapseBtn.addEventListener('click', () => {
                const isCollapsed = document.documentElement.getAttribute('data-sidebar') === 'collapsed';
                if (isCollapsed) {
                    document.documentElement.removeAttribute('data-sidebar');
                    localStorage.setItem('aksara_sidebar', 'expanded');
                } else {
                    document.documentElement.setAttribute('data-sidebar', 'collapsed');
                    localStorage.setItem('aksara_sidebar', 'collapsed');
                }
                setTimeout(() => {
                    if (sidebarMenu) {
                        const active = sidebarMenu.querySelector('.sidebar-link.active');
                        moveIndicator(active);
                    }
                }, 60);
            });
        }

        function toggleMobileMenu(open) {
            if (!panel) return;
            panel.classList.toggle('open', open);
            if (backdrop) backdrop.classList.toggle('open', open);
            if (menuBtn) menuBtn.setAttribute('aria-expanded', String(open));
            if (open && sidebarMenu) {
                requestAnimationFrame(() => {
                    const active = sidebarMenu.querySelector('.sidebar-link.active') || sidebarMenu.querySelector('.sidebar-link');
                    moveIndicator(active);
                });
            }
        }

        if (menuBtn) menuBtn.addEventListener('click', () => {
            const isOpen = panel && panel.classList.contains('open');
            toggleMobileMenu(!isOpen);
        });
        if (closeBtn) closeBtn.addEventListener('click', () => toggleMobileMenu(false));
        if (backdrop) backdrop.addEventListener('click', () => toggleMobileMenu(false));

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && panel?.classList.contains('open')) toggleMobileMenu(false);
        });

        panel?.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => toggleMobileMenu(false));
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 992 && panel?.classList.contains('open')) toggleMobileMenu(false);
        });

        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.querySelectorAll('[data-count-up]').forEach(el => {
                const raw = String(el.getAttribute('data-count-up') || '').trim();
                const match = raw.match(/^(\d+(?:\.\d+)?)(%?)$/);
                if (!match) return;
                const target = parseFloat(match[1]);
                const suffix = match[2] || '';
                const hasDecimal = match[1].includes('.');
                if (!Number.isFinite(target) || target === 0) return;

                const duration = 750;
                const start = performance.now();
                function step(now) {
                    const progress = Math.min(1, (now - start) / duration);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const current = target * eased;
                    el.textContent = (hasDecimal ? current.toFixed(1) : Math.round(current).toLocaleString('id-ID')) + suffix;
                    if (progress < 1) {
                        requestAnimationFrame(step);
                    } else {
                        el.textContent = raw;
                    }
                }
                requestAnimationFrame(step);
            });
        }
    });
})();
