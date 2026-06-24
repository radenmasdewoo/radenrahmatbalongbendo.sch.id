/**
 * DYNAMIC LOADER v2.0 — SMP R Rahmat
 * Membaca konfigurasi dari data/site_config.json
 * dan menerapkan perubahan ke halaman secara otomatis.
 */
(function () {
    const prefix = (window.location.pathname.includes('/mi/') || window.location.pathname.includes('/smp/')) ? '../' : '';
    const CFG_URL = prefix + 'data/site_config.json';

    async function loadConfig() {
        try {
            const res = await fetch(CFG_URL + '?cb=' + Date.now());
            if (!res.ok) return null;
            return await res.json();
        } catch (e) {
            console.warn('[DynamicLoader] Gagal memuat config:', e);
            return null;
        }
    }

    function getPageKey() {
        if (window.PAGE_KEY) return window.PAGE_KEY;
        const path = window.location.pathname;
        const title = document.title;
        if (path.includes('/smp/') || title.includes('SMP')) return 'smp';
        if (path.includes('/mi/') || title.includes('MI')) return 'mi';
        return 'main';
    }

    function applyConfig(cfg) {
        if (!cfg) return;
        const pageKey = getPageKey();
        const pageCfg = (cfg.pages && cfg.pages[pageKey]) ? cfg.pages[pageKey] : {};

        // ---- SEO & META (Modern Trend 2026) ----
        if (pageCfg.seo_title) document.title = pageCfg.seo_title;

        const updateMeta = (name, content) => {
            if (!content) return;
            let meta = document.querySelector(`meta[name="${name}"]`) || document.querySelector(`meta[property="${name}"]`);
            if (!meta) {
                meta = document.createElement('meta');
                if (name.startsWith('og:')) meta.setAttribute('property', name);
                else meta.setAttribute('name', name);
                document.head.appendChild(meta);
            }
            meta.setAttribute('content', content);
        };

        updateMeta('description', pageCfg.seo_desc);
        updateMeta('keywords', pageCfg.seo_keywords);
        updateMeta('og:title', pageCfg.seo_title);
        updateMeta('og:description', pageCfg.seo_desc);
        updateMeta('og:image', pageCfg.seo_og_image);
        updateMeta('og:url', pageCfg.seo_url || window.location.href);

        // ---- SCHEMA.ORG (JSON-LD) - Secret Sauce for Google ----
        if (pageKey === 'smp') {
            const schemaId = 'smp-schema-jsonld';
            let script = document.getElementById(schemaId);
            if (!script) {
                script = document.createElement('script');
                script.id = schemaId;
                script.type = 'application/ld+json';
                document.head.appendChild(script);
            }
            const schemaData = {
                "@context": "https://schema.org",
                "@type": "School",
                "name": "SMP R Rahmat Balongbendo",
                "alternateName": "SMP RR",
                "url": pageCfg.seo_url || "https://smp.radenrahmatbalongbendo.sch.id/",
                "logo": pageCfg.logo || "https://radenrahmatbalongbendo.sch.id/smp/logo-smp.webp",
                "image": pageCfg.seo_og_image || "https://radenrahmatbalongbendo.sch.id/smp/logo-smp.webp",
                "description": pageCfg.seo_desc || "SMP R Rahmat Balongbendo (NPSN 20501766) - Generasi Cerdas & Berkarakter.",
                "address": {
                    "@type": "PostalAddress",
                    "streetAddress": "Jl. Raya Balongbendo",
                    "addressLocality": "Sidoarjo",
                    "addressRegion": "Jawa Timur",
                    "addressCountry": "ID"
                },
                "geo": {
                    "@type": "GeoCoordinates",
                    "latitude": -7.348427,
                    "longitude": 112.511394
                },
                "telephone": pageCfg.phone || "+6285706673560",
                "openingHoursSpecification": {
                    "@type": "OpeningHoursSpecification",
                    "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
                    "opens": "07:00",
                    "closes": "14:00"
                }
            };
            script.text = JSON.stringify(schemaData);
        }

        // ---- HERO ----
        if (pageCfg.hero_badge) {
            const badge = document.querySelector('.hero-section .badge, .hero-section [class*="badge"]');
            if (badge) badge.textContent = pageCfg.hero_badge;
        }

        if (pageCfg.hero_title) {
            const h1 = document.querySelector('.hero-section h1, .hero-content h1');
            if (h1) h1.innerHTML = pageCfg.hero_title.replace(/,/g, ',<br>');
        }

        if (pageCfg.hero_desc) {
            const desc = document.querySelector('.hero-section p, .hero-content p');
            if (desc) desc.textContent = pageCfg.hero_desc;
        }

        if (pageCfg.hero_btn1) {
            const btn = document.querySelector('.hero-section a.btn-warning, .hero-section .btn-warning');
            if (btn) btn.textContent = pageCfg.hero_btn1;
        }

        if (pageCfg.hero_btn2) {
            const btn2 = document.querySelector('.hero-section a.btn-outline-light');
            if (btn2) btn2.textContent = pageCfg.hero_btn2;
        }

        // ---- BACKGROUND ----
        if (pageCfg.body_bg) {
            document.body.style.backgroundImage = `url('${pageCfg.body_bg}')`;
            document.body.style.backgroundSize = 'cover';
            document.body.style.backgroundPosition = 'center';
            document.body.style.backgroundAttachment = 'fixed';
        } else if (pageCfg.hero_bg) {
            document.body.style.backgroundImage = `url('${pageCfg.hero_bg}')`;
        }
        
        if (pageCfg.hero_bg) {
            const navbar = document.querySelector('.navbar');
            if (navbar) navbar.style.backgroundImage = `linear-gradient(rgba(255,255,255,0.7),rgba(255,255,255,0.7)), url('${pageCfg.hero_bg}')`;

            const hero = document.querySelector('.hero-section');
            if (hero) hero.style.backgroundImage = `linear-gradient(rgba(0, 70, 30, 0.75), rgba(0, 50, 0, 0.65)), url('${pageCfg.hero_bg}')`;
        }

        // ---- LOGO ----
        if (pageCfg.logo) {
            document.querySelectorAll('.navbar-brand img, footer img[alt="Logo"]').forEach(img => {
                img.src = pageCfg.logo;
            });
        }

        // ---- NAVBAR ----
        const navList = document.querySelector('.navbar-nav');
        if (navList && pageCfg.navbar && pageCfg.navbar.length > 0) {
            navList.innerHTML = pageCfg.navbar.map(item => {
                const icon = item.icon ? `<i class="${item.icon} me-1"></i>` : '';
                const activeClass = item.active ? ' active' : '';
                const extraClass = item.class || '';
                const target = item.target ? ` target="${item.target}"` : '';

                if (item.iconOnly) {
                    return `<li class="nav-item ms-3">
                        <a class="btn btn-outline-dark btn-sm rounded-circle ${extraClass}" href="${item.link}"${target} title="${item.text}">
                            <i class="${item.icon}"></i>
                        </a>
                    </li>`;
                }

                const isPpdb = extraClass.includes('btn-ppdb');
                return `<li class="nav-item ${isPpdb ? 'ms-lg-2' : ''}">
                    <a class="nav-link ${activeClass} ${extraClass}" href="${item.link}"${target}>
                        ${icon}${item.text}
                    </a>
                </li>`;
            }).join('');
        }

        // ---- FEATURES ----
        if (pageCfg.features && pageCfg.features.length > 0) {
            const featSection = document.querySelector('#about .row.g-4');
            if (featSection) {
                featSection.innerHTML = pageCfg.features.map(f => `
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="${f.icon || 'fas fa-star'}"></i></div>
                            <h4>${f.title || ''}</h4>
                            <p class="text-muted">${f.desc || ''}</p>
                        </div>
                    </div>`).join('');
            }

            if (pageCfg.features_title) {
                const ftitle = document.querySelector('#about h2.display-6, #about h2');
                if (ftitle) ftitle.textContent = pageCfg.features_title;
            }
            if (pageCfg.features_sub) {
                const fsub = document.querySelector('#about h6.text-primary');
                if (fsub) fsub.textContent = pageCfg.features_sub;
            }
        }

        // ---- PPDB ----
        if (pageCfg.ppdb_title) {
            const ppdbH = document.querySelector('.card-header h2');
            if (ppdbH) ppdbH.textContent = pageCfg.ppdb_title;
        }
        if (pageCfg.ppdb_tahun) {
            const ppdbSub = document.querySelector('.card-header p');
            if (ppdbSub) ppdbSub.textContent = 'Tahun Ajaran ' + pageCfg.ppdb_tahun;
        }
        if (pageCfg.ppdb_desc) {
            const ppdbDesc = document.querySelector('#ppdb .card-body > p');
            if (ppdbDesc) ppdbDesc.textContent = pageCfg.ppdb_desc;
        }
        if (pageCfg.ppdb_wa) {
            window._ppdbWa = pageCfg.ppdb_wa;
        }
        if (pageCfg.ppdb_btn_label) {
            const ppdbBtn = document.querySelector('#form-ppdb button[type="button"]');
            if (ppdbBtn) ppdbBtn.innerHTML = `<i class="fab fa-whatsapp me-2"></i> ${pageCfg.ppdb_btn_label}`;
        }
        if (pageCfg.ppdb_wa_msg) {
            window._ppdbWaMsg = pageCfg.ppdb_wa_msg;
        }

        // ---- FOOTER CONTACT ----
        if (pageCfg.school_name) {
            const fn = document.querySelector('footer h5');
            if (fn) fn.textContent = pageCfg.school_name;
        }
        if (pageCfg.footer_desc) {
            const fd = document.querySelector('footer p.small');
            if (fd) fd.textContent = pageCfg.footer_desc;
        }
        if (pageCfg.address) {
            const addr = document.querySelector('footer .fa-map-marker-alt')?.parentElement;
            if (addr) addr.innerHTML = `<i class="fas fa-map-marker-alt me-2 text-warning"></i> ${pageCfg.address}`;
        }
        if (pageCfg.phone) {
            const ph = document.querySelector('footer .fa-phone')?.parentElement;
            if (ph) ph.innerHTML = `<i class="fas fa-phone me-2 text-warning"></i> ${pageCfg.phone}`;
        }
        if (pageCfg.email) {
            const em = document.querySelector('footer .fa-envelope')?.parentElement?.parentElement;
            if (em) em.innerHTML = `<a href="mailto:${pageCfg.email}" class="d-flex align-items-start text-decoration-none text-reset">
                <i class="fas fa-envelope me-2 text-warning mt-1"></i><span>${pageCfg.email}</span></a>`;
        }
        if (pageCfg.whatsapp && pageCfg.whatsapp_link) {
            const wa = document.querySelector('footer .fa-whatsapp')?.parentElement;
            if (wa) wa.innerHTML = `<a href="https://wa.me/${pageCfg.whatsapp_link}" target="_blank" class="text-decoration-none text-reset">
                <i class="fab fa-whatsapp me-2 text-warning"></i> ${pageCfg.whatsapp}</a>`;
        }
        if (pageCfg.copyright) {
            const cp = document.querySelector('footer .border-top small');
            if (cp) cp.textContent = pageCfg.copyright;
        }

        // ---- SOCIAL ----
        if (pageCfg.social_ig) {
            const igLink = document.querySelector('a[href*="instagram.com"]');
            if (igLink) igLink.href = `https://www.instagram.com/${pageCfg.social_ig}/`;
            const igHandle = document.querySelector('a.btn-outline-danger[href*="instagram"]');
            if (igHandle) igHandle.innerHTML = `<i class="fab fa-instagram me-2"></i> @${pageCfg.social_ig}`;
        }
        if (pageCfg.social_fb) {
            const fbLink = document.querySelector('footer a .fa-facebook')?.parentElement;
            if (fbLink) fbLink.href = pageCfg.social_fb;
        }
        if (pageCfg.social_yt) {
            const ytLink = document.querySelector('footer a .fa-youtube')?.parentElement;
            if (ytLink) ytLink.href = pageCfg.social_yt;
        }
        if (pageCfg.ig_title) {
            const igTitle = document.querySelector('#gallery h2');
            if (igTitle) igTitle.textContent = pageCfg.ig_title;
        }
        if (pageCfg.ig_desc) {
            const igDesc = document.querySelector('#gallery p.text-muted');
            if (igDesc) igDesc.textContent = pageCfg.ig_desc;
        }

        // ---- SEO ----
        if (pageCfg.seo_title) document.title = pageCfg.seo_title;
        if (pageCfg.seo_desc) {
            const metaDesc = document.querySelector('meta[name="description"]');
            if (metaDesc) metaDesc.content = pageCfg.seo_desc;
        }
        if (pageCfg.seo_keywords) {
            const metaKw = document.querySelector('meta[name="keywords"]');
            if (metaKw) metaKw.content = pageCfg.seo_keywords;
        }

        // ---- COLORS ----
        if (pageCfg.color_primary) {
            document.documentElement.style.setProperty('--primary', pageCfg.color_primary);
        }
        if (pageCfg.color_accent) {
            document.documentElement.style.setProperty('--accent', pageCfg.color_accent);
        }
        if (pageCfg.color_ppdb) {
            const ppdbBtn = document.querySelector('.btn-ppdb-nav');
            if (ppdbBtn) ppdbBtn.style.backgroundColor = pageCfg.color_ppdb;
        }

        console.log(`[DynamicLoader v2] Konfigurasi "${pageKey}" berhasil diterapkan.`);
    }

    // ---- PATCH kirimFormulir agar gunakan nomor dari config ----
    function patchKirimFormulir() {
        const orig = window.kirimFormulir;
        window.kirimFormulir = function () {
            if (window._ppdbWa) {
                // Override nomor admin dari config
                const nomorAdmin = window._ppdbWa;
                const nama = document.getElementById('nama')?.value || '';
                const sekolah = document.getElementById('sekolah')?.value || '';
                const ortu = document.getElementById('ortu')?.value || '';
                const hp = document.getElementById('hp')?.value || '';
                if (nama && sekolah && ortu && hp) {
                    let pesan = "";
                    if (window._ppdbWaMsg) {
                        pesan = window._ppdbWaMsg
                            .replace(/{nama}/g, nama)
                            .replace(/{sekolah}/g, sekolah)
                            .replace(/{ortu}/g, ortu)
                            .replace(/{hp}/g, hp);
                    } else {
                        pesan = `Halo Admin PPDB SMP R Rahmat,\n\nSaya ingin mendaftarkan calon siswa baru dengan data berikut:\n*Nama Siswa:* ${nama}\n*Asal Sekolah:* ${sekolah}\n*Nama Ortu:* ${ortu}\n*Nomor HP:* ${hp}\n\nMohon informasinya lebih lanjut. Terima kasih.`;
                    }
                    window.open(`https://wa.me/${nomorAdmin}?text=${encodeURIComponent(pesan)}`, '_blank');
                } else {
                    alert('Mohon lengkapi semua data formulir terlebih dahulu.');
                }
            } else if (orig) {
                orig();
            }
        };
    }

    // ---- INIT ----
    loadConfig().then(cfg => {
        applyConfig(cfg);
        patchKirimFormulir();
    });

})();
