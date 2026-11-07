/**
 * AKSARA — Separated Learning Workspace Tabs Controller
 * Separates Modul Bacaan, Berkas Materi, Tugas & Evaluasi, Presensi, and Siswa
 * into clean, distinct panels with URL hash deep-link synchronization.
 */
(function () {
    'use strict';

    const HASH_MAP = {
        '#lessons': 'lessons',
        '#learning': 'lessons',
        '#materials': 'materials',
        '#assignments': 'assignments',
        '#quizzes': 'quizzes',
        '#monitoring': 'monitoring',
        '#attendance': 'monitoring',
        '#take-attendance': 'monitoring',
        '#students': 'students',
    };

    function activatePane(root, targetName, updateHash) {
        if (!root || !targetName) return;
        const targetPane = root.querySelector('[data-lw-pane="' + targetName + '"]');
        if (!targetPane) return;

        root.querySelectorAll('[data-lw-tab]').forEach(btn => {
            const active = btn.getAttribute('data-lw-tab') === targetName;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        root.querySelectorAll('[data-lw-pane]').forEach(pane => {
            const active = pane.getAttribute('data-lw-pane') === targetName;
            pane.classList.toggle('is-active', active);
        });

        document.querySelectorAll('.lw-hub-kpi[data-lw-jump]').forEach(card => {
            card.classList.toggle('is-selected', card.getAttribute('data-lw-jump') === targetName);
        });

        const tabInput = document.getElementById('lwActiveTabInput');
        if (tabInput && ['lessons', 'materials', 'assignments'].includes(targetName)) {
            tabInput.value = targetName;
        }

        if (updateHash && window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '#' + targetName);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-lw-root]');
        if (!root) return;

        root.setAttribute('data-lw-ready', 'true');

        const hash = window.location.hash;
        let initialTab = HASH_MAP[hash] || root.getAttribute('data-default-tab') || '';

        if (!initialTab && root.getAttribute('data-has-search') === '1') {
            for (const candidate of ['lessons', 'materials', 'assignments', 'quizzes', 'students']) {
                const btn = root.querySelector('[data-lw-tab="' + candidate + '"] .lw-tab-count');
                if (btn && parseInt(btn.textContent || '0', 10) > 0) {
                    initialTab = candidate;
                    break;
                }
            }
        }

        if (!initialTab) {
            const currentActive = root.querySelector('[data-lw-tab].is-active');
            initialTab = currentActive ? currentActive.getAttribute('data-lw-tab') : 'lessons';
        }

        activatePane(root, initialTab, false);

        if (hash === '#take-attendance') {
            const formEl = document.getElementById('take-attendance');
            if (formEl) {
                requestAnimationFrame(() => formEl.scrollIntoView({ behavior: 'smooth', block: 'center' }));
            }
        }

        root.addEventListener('click', event => {
            const tabBtn = event.target.closest('[data-lw-tab]');
            if (!tabBtn) return;
            event.preventDefault();
            activatePane(root, tabBtn.getAttribute('data-lw-tab'), true);
        });

        document.addEventListener('click', event => {
            const jumpBtn = event.target.closest('[data-lw-jump]');
            if (!jumpBtn) return;
            const target = jumpBtn.getAttribute('data-lw-jump');
            if (!target) return;
            const href = jumpBtn.getAttribute('href') || '';
            event.preventDefault();
            activatePane(root, target, true);
            if (href === '#take-attendance') {
                const formEl = document.getElementById('take-attendance');
                if (formEl) {
                    formEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const firstInput = formEl.querySelector('input:not([type="hidden"])');
                    if (firstInput) firstInput.focus();
                }
            }
        });

        window.addEventListener('hashchange', () => {
            const mapped = HASH_MAP[window.location.hash];
            if (mapped) activatePane(root, mapped, false);
        });
    });
})();

