// Quick Access System for CMS Pro
// Ctrl+Shift+L to access dashboard from any page

(function () {
    'use strict';

    // Keyboard shortcut listener
    document.addEventListener('keydown', function (e) {
        // Ctrl+Shift+L
        if (e.ctrlKey && e.shiftKey && (e.key === 'L' || e.key === 'l')) {
            e.preventDefault();

            // Find where this script is located to determine root
            var scripts = document.getElementsByTagName('script');
            var pathPrefix = './';
            for (var i = 0; i < scripts.length; i++) {
                var src = scripts[i].getAttribute('src');
                if (src && (src.includes('js/quick_access.js') || src.includes('js/quick_access.min.js'))) {
                    // If src is "../js/quick_access.js", we are in a subfolder
                    var match = src.match(/^(\.\.\/)+/);
                    if (match) pathPrefix = match[0];
                    // Also works for /js/quick_access.js (relative to root)
                    if (src.startsWith('/')) {
                        pathPrefix = '/';
                    }
                    break;
                }
            }

            // Redirect to the new CMS
            if (pathPrefix === '/') {
                window.location.href = '/cms-pro-radenrahmat.html';
            } else {
                window.location.href = pathPrefix + 'cms-pro-radenrahmat.html';
            }
        }
    });

})();
