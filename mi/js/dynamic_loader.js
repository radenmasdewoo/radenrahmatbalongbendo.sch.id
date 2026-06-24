/**
 * SUPER ADMIN DYNAMIC LOADER
 * Fetches site_config.json and updates the DOM elements.
 */

document.addEventListener("DOMContentLoaded", async () => {
    // Determine relative path to data folder
    // If we are in subdirectory (mi/, smp/) on the same domain, path is ../
    // If we are on a subdomain (mi.xxx), the path is current root.
    const isSubdomain = window.location.hostname.startsWith('mi.') || window.location.hostname.startsWith('smp.');
    const inSubdir = window.location.pathname.includes('/mi/') || window.location.pathname.includes('/smp/');
    
    const pathPrefix = (inSubdir && !isSubdomain) ? '../' : '';
    const configFile = `${pathPrefix}data/site_config.json`;

    try {
        let res = await fetch(configFile);
        if (!res.ok && pathPrefix === '../') {
            // Fallback to local data if ../ failed (e.g. if subdomain is serving from subdir but proxying)
            res = await fetch('data/site_config.json');
        }
        const config = await res.json();

        // NOTE:
        // Navbar, hero, dan Instagram feed sekarang dikendalikan langsung oleh
        // masing-masing halaman (index.html, smp/index.html, mi/index.html).
        // Dynamic loader hanya dipakai untuk:
        //  - menerapkan overrides dari Visual Editor
        //  - memuat Visual Editor saat edit_mode aktif

        // APPLY CUSTOM OVERRIDES (Visual Editor Data)
        applyOverrides(config.overrides || {});

        // CHECK FOR EDIT MODE (aktifkan Visual Editor)
        if (new URLSearchParams(window.location.search).get('edit_mode') === 'true') {
            loadVisualEditor(pathPrefix);
        }

    } catch (e) {
        console.error("Failed to load site config:", e);
    }
});

function renderNavbar(items, prefix, pageKey = 'main') {
    const navContainer = document.querySelector('.nav-menu') || document.querySelector('.navbar-nav');
    if (!navContainer) return;

    // Different HTML structure for Main (ul.nav-menu) vs Subsites (ul.navbar-nav with Bootstrap)
    const isBootstrap = navContainer.classList.contains('navbar-nav');

    function normalizeLink(link, prefix) {
        if (!link || link.startsWith('http') || link.startsWith('#')) return link;
        // Remove existing ../ prefixes to get "root relative" path
        const clean = link.replace(/^(\.\.\/)+/, '');
        return prefix + clean;
    }

    let html = '';

    items.forEach(item => {
        const link = normalizeLink(item.link, prefix);
        const icon = item.icon ? `<i class="${item.icon} me-2"></i>` : '';
        const iconOnly = item.iconOnly && item.icon ? `<i class="${item.icon}"></i>` : '';
        const customClass = item.class || '';
        const activeClass = item.active ? 'active' : '';

        if (item.dropdown) {
            // Dropdown Logic (only for main site)
            if (isBootstrap) {
                html += `
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">${item.text}</a>
                    <ul class="dropdown-menu">
                        ${item.dropdown.map(sub => `<li><a class="dropdown-item" href="${normalizeLink(sub.link, prefix)}" target="${sub.target || '_self'}">${sub.text}</a></li>`).join('')}
                    </ul>
                </li>`;
            } else {
                // Main Site Custom Dropdown
                html += `
                <li class="has-dropdown">
                    <a href="${link}">${item.text} <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 5px;"></i></a>
                    <div class="dropdown">
                        ${item.dropdown.map(sub => `
                            <a href="${normalizeLink(sub.link, prefix)}" target="${sub.target || '_self'}">
                                ${sub.img ? `<img src="${normalizeLink(sub.img, prefix)}" class="menu-logo">` : ''} 
                                ${sub.text}
                            </a>
                        `).join('')}
                    </div>
                </li>`;
            }
        } else {
            // Single Link
            if (isBootstrap) {
                // For MI/SMP sites with Bootstrap navbar
                if (customClass.includes('btn')) {
                    // Special button styling (like PPDB button or back button)
                    html += `<li class="nav-item ${customClass.includes('ms-') ? customClass.match(/ms-\w+/)?.[0] || '' : ''}">
                        <a class="nav-link ${customClass}" href="${link}">${iconOnly || (icon + item.text)}</a>
                    </li>`;
                } else {
                    html += `<li class="nav-item"><a class="nav-link ${activeClass}" href="${link}">${icon}${item.text}</a></li>`;
                }
            } else {
                // Main site
                html += `<li><a href="${link}" class="${activeClass}">${item.text}</a></li>`;
            }
        }
    });

    // Replace content
    navContainer.innerHTML = html;
}

function updatePageContent(config, prefix) {
    // Identify current page by some marker or URL
    let pageKey = 'main';
    if (window.location.pathname.includes('/mi/')) pageKey = 'mi';
    if (window.location.pathname.includes('/smp/')) pageKey = 'smp';

    const pageData = config.pages[pageKey];
    if (!pageData) return;

    // Update Hero Title
    // Selectors might vary.
    const titleEl = document.querySelector('h1.display-3') || document.querySelector('.hero-content h1') || document.querySelector('.hero-content h2');
    if (titleEl && pageData.hero_title) titleEl.innerText = pageData.hero_title;

    // Update Hero Description
    const descEl = document.querySelector('.lead') || document.querySelector('.hero-content p');
    if (descEl && pageData.hero_desc) descEl.innerText = pageData.hero_desc;

    // Update Hero Background
    const heroSection = document.querySelector('.hero') || document.querySelector('.hero-section');
    if (heroSection && pageData.hero_bg) {
        let bgUrl = pageData.hero_bg;
        // Handle relative uploads vs absolute URLs
        if (!bgUrl.startsWith('http')) {
            // It's a file path. From 'smp/', uploads is '../uploads/'.
            // From root, uploads is 'uploads/'.
            // If the config value is simple filename 'foo.jpg', we add path.
            // If it is 'uploads/foo.jpg', we handle prefix.
            // Admin API saves as '../uploads/foo.jpg' usually ? No, let's check Admin API.
            // Admin API returns '../uploads/timestamp_name'.
            // If config has '../uploads/...', and we are in root, we need 'uploads/...'.
            // If we are in smp/, we need '../uploads/...'.

            // Simplest normalization:
            // Remove all '../' from start and prepend correct prefix for current location.
            const cleanPath = bgUrl.replace(/^(\.\.\/)+/, ''); // remove leading ../
            // If in root: 'uploads/foo.jpg'
            // If in subdir: '../uploads/foo.jpg'

            bgUrl = (prefix ? '../' : '') + cleanPath;
        }

        // Apply
        // Some sites use <img>, some use background-image
        const heroImg = heroSection.querySelector('img.hero-img');
        if (heroImg) {
            heroImg.src = bgUrl;
        } else {
            heroSection.style.backgroundImage = `linear-gradient(rgba(0,50,0,0.7), rgba(0,50,0,0.7)), url('${bgUrl}')`;
        }
    }
}

async function initInstagramFeed(prefix) {
    const containers = document.querySelectorAll('.ig-feed-container');
    if (containers.length === 0) return;

    try {
        const res = await fetch(`${prefix}api/news.php`);
        const allNews = await res.json();

        // Filter news that have Instagram links or are meant for the feed
        // For a school site, let's just take the latest 6 items
        const feeds = allNews.slice(0, 6);

        containers.forEach(container => {
            if (feeds.length === 0) {
                container.innerHTML = '<p class="text-muted">Belum ada postingan terbaru.</p>';
                return;
            }

            container.innerHTML = `
                <div class="row g-3">
                    ${feeds.map(item => `
                        <div class="col-md-4 col-sm-6">
                            <div class="insta-card" style="
                                background: white;
                                border-radius: 12px;
                                overflow: hidden;
                                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                                transition: transform 0.3s;
                                height: 100%;
                                cursor: pointer;
                            " onclick="window.open('${item.link}', '_blank')">
                                <div style="height: 200px; overflow: hidden; position: relative;">
                                    ${isVideoMedia(item.img) ?
                    `<video src="${normalizeMediaUrl(item.img, prefix)}" style="width:100%; height:100%; object-fit:cover;" autoplay muted loop playsinline class="autoplay-video"></video>` :
                    `<img src="${normalizeMediaUrl(item.img, prefix)}" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='https://picsum.photos/seed/news/400/250'; this.onerror=null;">`
                }
                                    <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.5); color: white; padding: 5px 8px; border-radius: 5px; font-size: 10px;">
                                        <i class="fab fa-instagram"></i>
                                    </div>
                                </div>
                                <div style="padding: 15px; font-size: 13px; line-height: 1.5;">
                                    <p style="margin-bottom: 5px; color: #333; font-weight: 500; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                        ${item.excerpt}
                                    </p>
                                    <small style="color: #999;">${item.date}</small>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        });
    } catch (e) {
        console.error("Failed to load Instagram feed:", e);
    }
}

/**
 * Applies generic CSS-based overrides (text, href, src)
 */
function applyOverrides(overrides) {
    Object.keys(overrides).forEach(selector => {
        const data = overrides[selector];
        const elements = document.querySelectorAll(selector);

        elements.forEach(el => {
            // TAMBAHKAN INI — Jangan timpa elemen chatbot
            if (
                el.closest('#rr-chat-widget-container') ||
                el.closest('#rr-chat-window') ||
                el.id === 'rr-chat-fab'
            ) return;
            // AKHIR TAMBAHAN
            if (data.text !== undefined) {
                // Preserving icon if exists (simple check)
                const icon = el.querySelector('i');
                if (icon) {
                    const iconHtml = icon.outerHTML;
                    el.innerHTML = iconHtml + ' ' + data.text;
                } else {
                    el.innerText = data.text;
                }
            }
            if (data.href !== undefined && el.tagName === 'A') el.href = data.href;
            if (data.src !== undefined && el.tagName === 'IMG') el.src = data.src;
            if (data.bg !== undefined) el.style.backgroundImage = `url('${data.bg}')`;
        });
    });
}

/**
 * Loads the Visual Editor script if superadmin is logged in
 */
async function loadVisualEditor(prefix) {
    try {
        const res = await fetch(`${prefix}api/auth.php?action=check`);
        const auth = await res.json();
        if (auth.logged_in) {
            console.log("Edit Mode Active: Loading Visual Editor...");
            const script = document.createElement('script');
            script.src = `${prefix}js/visual_editor.js`;
            document.head.appendChild(script);
        }
    } catch (e) {
        console.error("Auth check failed for edit mode", e);
    }
}

/**
 * Robust check for video files or data
 */
function isVideoMedia(url) {
    if (!url) return false;
    // Common extensions
    if (url.match(/\.(mp4|webm|mov|ogg|m4v)/i)) return true;
    // Instagram reels are videos
    if (url.includes('instagram.com/reel/')) return true;
    // CDN / Social media markers - check for actual video files
    if ((url.includes('instagram') || url.includes('fbcdn')) && url.match(/\.(mp4|webm|mov)/i)) return true;
    // Data URIs
    if (url.startsWith('data:video/')) return true;
    // Check for media_xxx.mp4 style from local uploads
    if (url.includes('uploads/') && url.endsWith('.mp4')) return true;
    return false;
}

/**
 * Normalizes media URLs (removes localhost/absolute if possible)
 */
function normalizeMediaUrl(url, prefix) {
    if (!url) return '';
    if (url.startsWith('http')) {
        // If it's localhost, make it relative
        if (url.includes('localhost') || url.includes('127.0.0.1')) {
            const parts = url.split('/radenrahmat/');
            return (parts[1] ? prefix + parts[1] : url);
        }
        return url;
    }
    // If it's already relative or data URI
    if (url.startsWith('data:') || url.startsWith('uploads/')) {
        return prefix + url;
    }
    return url;
}

/**
 * Aggressive Autoplay Enforcer
 */
function enforceAutoplay() {
    const playVideos = () => {
        document.querySelectorAll('video').forEach(video => {
            // Target videos that are supposed to autoplay
            if (video.classList.contains('autoplay-video') || video.hasAttribute('autoplay')) {
                video.muted = true; // Ensure muted for autoplay compliance
                video.play().catch(e => {
                    console.log("Autoplay blocked, user interaction might be needed:", e);
                });
            }
        });
    };

    // Try immediately
    playVideos();

    // Also try on first interaction
    document.addEventListener('click', playVideos, { once: true });
    document.addEventListener('touchstart', playVideos, { once: true });

    // Polling for dynamic content (backup)
    setInterval(playVideos, 3000);
}

// Intercept initInstagramFeed to run enforcer
const originalInitIG = initInstagramFeed;
initInstagramFeed = async function (prefix) {
    await originalInitIG(prefix);
    enforceAutoplay();
};
