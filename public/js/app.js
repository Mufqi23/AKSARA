/**
 * AKSARA — Portal Akademik Terpadu (Main JavaScript Entry Point)
 * Core logic is modularized into:
 *   - /js/modules/theme-sidebar.js (Theme, Sidebar, Curve Indicator, CountUp)
 *   - /js/modules/modals-forms.js  (Pop-up Modals, Password Toggle, Form Guards)
 *   - /js/modules/learning-tabs.js (Separated Modul, Materi & Tugas Tabs)
 */
(function () {
    'use strict';
    ['/js/modules/theme-sidebar.js', '/js/modules/modals-forms.js', '/js/modules/learning-tabs.js'].forEach(function (src) {
        if (!document.querySelector('script[src*="' + src + '"]')) {
            var s = document.createElement('script');
            s.src = src;
            s.defer = true;
            document.head.appendChild(s);
        }
    });
})();
