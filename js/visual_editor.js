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
        const editables = document.querySelectorAll('a, button, img, .hero, .hero-section');
        editables.forEach(el => {
            // Exceptions
            if (el.closest('footer') && !el.tagName.includes('IMG')) return; // skip footer links for now unless they are images
            if (el.id === 've-save-all') return;

            el.classList.add('ve-editable');
            const badge = document.createElement('span');
            badge.className = 've-badge';
            badge.innerText = el.tagName === 'IMG' ? 'IMG' : 'BTN';
            if (el.classList.contains('hero') || el.classList.contains('hero-section')) badge.innerText = 'HERO';

            // Only add badge if positioned/block
            if (getComputedStyle(el).position === 'static') el.style.position = 'relative';
            el.appendChild(badge);

            el.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openEditor(el);
            });
        });
    }

    function generateSelector(el) {
        if (el.id) return `#${el.id}`;
        if (el.className) {
            // Pick the first significant class
            const classes = Array.from(el.classList).filter(c => !c.startsWith('ve-') && !['active', 'show', 'has-dropdown'].includes(c));
            if (classes.length > 0) return `.${classes[0]}`;
        }
        // Fallback to path (not perfect but better than nothing)
        return el.tagName.toLowerCase();
    }

    function openEditor(el) {
        const selector = generateSelector(el);
        const modal = document.createElement('div');
        modal.className = 've-modal';

        let fields = '';
        if (el.tagName === 'IMG') {
            fields = `
                <label class="ve-label">URL Gambar</label>
                <input type="text" id="ve-field-src" class="ve-input" value="${el.src}">
                <label class="ve-label">Upload Baru</label>
                <input type="file" id="ve-field-file" class="ve-input">
            `;
        } else if (el.classList.contains('hero') || el.classList.contains('hero-section')) {
            fields = `
                <label class="ve-label">Gambar Hero (BG)</label>
                <input type="text" id="ve-field-bg" class="ve-input" value="">
                <label class="ve-label">Upload Baru</label>
                <input type="file" id="ve-field-file" class="ve-input">
            `;
        } else {
            const currentText = el.innerText.trim();
            fields = `
                <label class="ve-label">Teks Tombol</label>
                <input type="text" id="ve-field-text" class="ve-input" value="${currentText}">
                <label class="ve-label">Link (HREF)</label>
                <input type="text" id="ve-field-href" class="ve-input" value="${el.href || '#'}">
            `;
        }

        modal.innerHTML = `
            <div class="ve-modal-content">
                <h3 style="margin:0 0 15px 0;">Edit Elemen</h3>
                <div style="font-size: 11px; color: #999; margin-bottom: 15px;">Selector: ${selector}</div>
                ${fields}
                <button class="ve-btn ve-btn-save">TERAPKAN</button>
                <button class="ve-btn ve-btn-cancel">BATAL</button>
            </div>
        `;

        document.body.appendChild(modal);

        modal.querySelector('.ve-btn-cancel').onclick = () => modal.remove();
        modal.querySelector('.ve-btn-save').onclick = async () => {
            const update = {};
            if (document.getElementById('ve-field-text')) update.text = document.getElementById('ve-field-text').value;
            if (document.getElementById('ve-field-href')) update.href = document.getElementById('ve-field-href').value;
            if (document.getElementById('ve-field-src')) update.src = document.getElementById('ve-field-src').value;

            const fileInput = document.getElementById('ve-field-file');
            if (fileInput && fileInput.files[0]) {
                const formData = new FormData();
                formData.append('file', fileInput.files[0]);
                const uploadRes = await fetch(`${prefix}api/admin_api.php`, { method: 'POST', body: formData });
                const uploadData = await uploadRes.json();
                if (uploadData.success) {
                    if (el.tagName === 'IMG') update.src = uploadData.url;
                    else update.bg = uploadData.url;
                }
            }

            // Apply to UI immediately
            if (update.text !== undefined) {
                const icon = el.querySelector('i');
                el.innerText = update.text;
                if (icon) el.prepend(icon);
            }
            if (update.href) el.href = update.href;
            if (update.src) el.src = update.src;

            // Save to config object
            currentConfig.overrides[selector] = update;
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
