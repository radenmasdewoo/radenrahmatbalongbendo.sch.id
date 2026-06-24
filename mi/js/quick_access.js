// Quick Access System for MI CMS
// Ctrl+Shift+L to access dashboard from any page

(function () {
    'use strict';

    // Keyboard shortcut listener
    document.addEventListener('keydown', function (e) {
        // Ctrl+Shift+L
        if (e.ctrlKey && e.shiftKey && (e.key === 'L' || e.key === 'l')) {
            e.preventDefault();

            // Redirect specifically for MI
            window.location.href = 'cms-admin.html';
        }
    });

})();
