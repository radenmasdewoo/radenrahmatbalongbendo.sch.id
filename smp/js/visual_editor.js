/**
 * VISUAL EDITOR FOR SUPER ADMIN
 * Allows real-time editing of buttons and images.
 */

(function () {
    const EDITOR_CSS = `
        .ve-editable { outline: 2px dashed #f39c12 !important; cursor: pointer !important; position: relative; }
        .ve-editable:hover { outline-style: solid !important; background: rgba(243, 156, 18, 0.1) !important; }
        .ve-badge { 
            position: absolute; top: -10px; right: -10px; background: #f39c12; color: white; 
            font-size: 10px; padding: 2px 6px; border-radius: 4px; z-index: 99999; font-family: sans-serif;
            pointer-events: none; text-transform: uppercase; font-weight: bold;
        }
        .ve-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.8); z-index: 100000; display: flex; justify-content: center; align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .ve-modal-content {
            background: white; padding: 25px; border-radius: 12px; width: 400px; max-width: 90%;
            box-shadow: 0 15px 50px rgba(0,0,0,0.5);
        }
        .ve-btn { 
            padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; 
            margin-top: 10px; transition: 0.2s;
        }
        .ve-btn-save { background: #006837; color: white; width: 100%; }
        .ve-btn-save:hover { background: #004d29; }
        .ve-btn-cancel { background: #eee; color: #333; margin-left: 10px; }
        .ve-input { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .ve-label { display: block; font-size: 12px; font-weight: bold; color: #666; margin-top: 10px; }
    `;

    const style = document.createElement('style');
    style.innerHTML = EDITOR_CSS;
    document.head.appendChild(style);

    let currentConfig = {};
    const prefix = window.location.pathname.includes('/mi/') || window.location.pathname.includes('/smp/') ? '../' : '';

    async function init() {
        const res = await fetch(`${prefix}api/admin_api.php`);
        currentConfig = await res.json();
        if (!currentConfig.overrides) currentConfig.overrides = {};

        scanPage();
        createControlBar();
    }

    function createControlBar() {
        const bar = document.createElement('div');
        bar.style = "position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: #111; color: white; padding: 10px 25px; border-radius: 50px; z-index: 100001; display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); font-family: sans-serif;";
        bar.innerHTML = `
            <div style="font-weight: bold; color: #f39c12;"><i class="fas fa-edit me-2"></i> MODE EDIT VISUAL</div>
            <div style="font-size: 12px; color: #aaa;">Klik pada tombol atau gambar untuk merubah.</div>
            <button id="ve-save-all" style="background: #006837; color: white; border: none; padding: 6px 15px; border-radius: 20px; cursor: pointer; font-weight: bold;">SIMPAN SEMUA</button>
            <button onclick="window.location.href=window.location.pathname" style="background: #e74c3c; color: white; border: none; padding: 6px 15px; border-radius: 20px; cursor: pointer; font-weight: bold;">KELUAR</button>
        `;
        document.body.appendChild(bar);
        document.getElementById('ve-save-all').onclick = saveConfig;
    }

    function scanPage() {
        // Target almost everything for text or image editing
        const selectors = 'a, button, img, h1, h2, h3, h4, h5, h6, p, span, small, .feature-card, .hero-section';
        const editables = document.querySelectorAll(selectors);

        editables.forEach(el => {
            // Exceptions - don't edit the editor itself or scripts
            if (el.closest('.ve-modal') || el.closest('#ve-control-bar') || el.tagName === 'SCRIPT' || el.tagName === 'STYLE') return;
            if (el.classList.contains('navbar-toggler') || el.classList.contains('spinner-border')) return;

            // EXCEPTION: Watermark / Branding (RMD_emas.png)
            if (el.tagName === 'IMG' && (el.src.includes('RMD_emas.png') || el.src.includes('RMD_emas.webp'))) return;

            el.classList.add('ve-editable');
            el.title = "Klik untuk edit " + el.tagName;

            el.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openEditor(el);
            });
        });

        // SPECIAL: Features List
        const featureContainer = document.getElementById('smp-features-container');
        if (featureContainer && !document.getElementById('ve-add-feature')) {
            const addBtn = document.createElement('div');
            addBtn.id = 've-add-feature';
            addBtn.innerHTML = `<button style="background:#006837; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:bold; margin:20px 0; width:100%"><i class="fas fa-plus"></i> TAMBAH KEUNGGULAN BARU</button>`;
            featureContainer.after(addBtn);
            addBtn.onclick = () => {
                const pageKey = window.location.pathname.includes('/smp/') ? 'smp' : 'mi';
                if (!currentConfig.pages[pageKey].features) currentConfig.pages[pageKey].features = [];
                currentConfig.pages[pageKey].features.push({
                    title: "Keunggulan Baru",
                    icon: "fas fa-star",
                    desc: "Deskripsi keunggulan sekolah..."
                });
                if (typeof renderFeatures === 'function') renderFeatures(currentConfig, prefix);
                scanPage();
            };
        }
    }

    function generateSelector(el) {
        if (el.id) return `#${el.id}`;
        if (el === document.body) return 'body';

        // Try to find a unique class first
        if (el.className && typeof el.className === 'string') {
            const classes = Array.from(el.classList).filter(c => !c.startsWith('ve-') && !['active', 'show', 'animate__animated'].includes(c));
            if (classes.length > 0) {
                const selector = `.${classes[0]}`;
                if (document.querySelectorAll(selector).length === 1) return selector;
            }
        }

        const tag = el.tagName.toLowerCase();
        const parent = el.parentNode;
        if (!parent || parent === document) return tag;

        const siblings = Array.from(parent.children).filter(s => s.tagName === el.tagName);
        const index = siblings.indexOf(el) + 1;

        return generateSelector(parent) + ' > ' + `${tag}:nth-of-type(${index})`;
    }

    function openEditor(el) {
        const selector = generateSelector(el);
        const modal = document.createElement('div');
        modal.className = 've-modal';

        let fields = '';
        const tagName = el.tagName;

        if (tagName === 'IMG') {
            fields = `
                <label class="ve-label">URL Gambar</label>
                <input type="text" id="ve-field-src" class="ve-input" value="${el.src}">
                <label class="ve-label">Upload File Baru</label>
                <input type="file" id="ve-field-file" class="ve-input" accept="image/*">
            `;
        } else if (el.classList.contains('hero-section')) {
            fields = `
                <label class="ve-label">ID / Nama Bagian</label>
                <input type="text" class="ve-input" value="${el.id || 'Hero Section'}" readonly>
                <label class="ve-label">Upload Background Baru</label>
                <input type="file" id="ve-field-file" class="ve-input" accept="image/*">
            `;
        } else {
            // Text Editing
            const currentText = el.innerText.trim();
            const isLink = tagName === 'A';

            fields = `
                <label class="ve-label">Konten Teks</label>
                <textarea id="ve-field-text" class="ve-input" rows="4">${currentText}</textarea>
                ${isLink ? `
                    <label class="ve-label">Link (HREF)</label>
                    <input type="text" id="ve-field-href" class="ve-input" value="${el.getAttribute('href') || '#'}">
                ` : ''}
            `;
        }

        modal.innerHTML = `
            <div class="ve-modal-content">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 style="margin:0;">Edit ${tagName}</h4>
                </div>
                <div style="font-size: 10px; color: #aaa; font-family:monospace;">Target: ${selector}</div>
                ${fields}
                <div class="d-flex gap-2 mt-4">
                    <button class="ve-btn ve-btn-save" style="flex:2;">TERAPKAN</button>
                    ${el.closest('.feature-card') ? `<button class="ve-btn ve-btn-delete" style="flex:1; background:#e74c3c; color:white; margin-left:0;">HAPUS</button>` : ''}
                    <button class="ve-btn ve-btn-cancel" style="flex:1; margin-left:10px;">BATAL</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const closeModal = () => modal.remove();
        modal.querySelector('.ve-btn-cancel').onclick = closeModal;

        const deleteBtn = modal.querySelector('.ve-btn-delete');
        if (deleteBtn) {
            deleteBtn.onclick = () => {
                if (confirm("Hapus keunggulan ini?")) {
                    const card = el.closest('.feature-card');
                    const container = document.getElementById('smp-features-container');
                    const cards = Array.from(container.querySelectorAll('.feature-card'));
                    const index = cards.indexOf(card);

                    const pageKey = window.location.pathname.includes('/smp/') ? 'smp' : 'mi';
                    currentConfig.pages[pageKey].features.splice(index, 1);

                    renderFeatures(currentConfig, prefix);
                    scanPage();
                    closeModal();
                }
            };
        }
        modal.querySelector('.ve-btn-save').onclick = async () => {
            const update = {};
            const textInput = document.getElementById('ve-field-text');
            const hrefInput = document.getElementById('ve-field-href');
            const srcInput = document.getElementById('ve-field-src');
            const fileInput = document.getElementById('ve-field-file');

            if (textInput) update.text = textInput.value;
            if (hrefInput) update.href = hrefInput.value;
            if (srcInput) update.src = srcInput.value;

            if (fileInput && fileInput.files[0]) {
                const formData = new FormData();
                formData.append('file', fileInput.files[0]);
                try {
                    const uploadRes = await fetch(`${prefix}api/admin_api.php`, { method: 'POST', body: formData });
                    const uploadData = await uploadRes.json();
                    if (uploadData.success) {
                        if (tagName === 'IMG') {
                            update.src = uploadData.url;
                            el.src = uploadData.url;
                        } else {
                            update.bg = uploadData.url;
                            el.style.backgroundImage = `linear-gradient(rgba(0,50,0,0.6), rgba(0,50,0,0.5)), url('${uploadData.url}')`;
                        }
                    } else {
                        alert("Upload gagal: " + uploadData.message);
                    }
                } catch (e) { console.error("Upload error", e); }
            }

            // Apply text changes immediately
            if (update.text !== undefined) {
                // Keep icon if it was there
                const icon = el.querySelector('i');
                if (icon) {
                    const iconHtml = icon.outerHTML;
                    el.innerHTML = iconHtml + ' ' + update.text;
                } else {
                    el.innerText = update.text;
                }
            }
            if (update.href) el.setAttribute('href', update.href);
            if (update.src && tagName === 'IMG') el.src = update.src;

            // Save to internal config
            currentConfig.overrides[selector] = update;

            // SPECIAL: If inside feature-card, update the features array too for structural consistency
            const featureCard = el.closest('.feature-card');
            if (featureCard) {
                const container = document.getElementById('smp-features-container');
                const cards = Array.from(container.querySelectorAll('.feature-card'));
                const index = cards.indexOf(featureCard);
                const pageKey = window.location.pathname.includes('/smp/') ? 'smp' : 'mi';
                const feat = currentConfig.pages[pageKey].features[index];
                if (feat) {
                    if (el.tagName === 'H4') feat.title = update.text;
                    if (el.tagName === 'P') feat.desc = update.text;
                    if (el.tagName === 'I') feat.icon = update.text; // Assuming text edit for icon class
                }
            }

            modal.remove();
        };
    }

    async function saveConfig() {
        try {
            const btn = document.getElementById('ve-save-all');
            btn.innerText = 'MENYIMPAN...';
            const res = await fetch(`${prefix}api/admin_api.php`, {
                method: 'POST',
                body: JSON.stringify(currentConfig)
            });
            const data = await res.json();
            if (data.success) {
                alert("Berhasil disimpan!");
                btn.innerText = 'SIMPAN SEMUA';
            } else {
                alert("Gagal menyimpan: " + data.message);
                btn.innerText = 'SIMPAN SEMUA';
            }
        } catch (e) {
            alert("Error saving config");
            console.error(e);
        }
    }

    init();
})();
