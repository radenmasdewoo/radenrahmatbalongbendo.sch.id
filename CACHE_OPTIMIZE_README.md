# Caching & Optimasi – Situs Statis (htdocs, mi, smp)

Konfigurasi ini **hanya** berlaku untuk:
- `/mnt/webdata/htdocs/` (portal BP3NU)
- `/mnt/webdata/htdocs/mi/` (MI)
- `/mnt/webdata/htdocs/smp/` (SMP)

**Folder `/mnt/webdata/htdocs/rdmmi/` tidak disentuh** (tidak ada caching/optimasi khusus untuk rdmmi).

---

## 1. Caching (Nginx)

- **Snippet:** `/etc/nginx/snippets/static-cache-htdocs.conf`
  - Hanya di-include di vhost **semua-domain** (mi, smp, radenrahmatbalongbendo.sch.id).
  - **rdmmi** memakai konfigurasi terpisah (`rdmmi.conf`) dan **tidak** memakai snippet ini.

- **Aturan cache:**
  - Aset statis (js, css, webp, jpg, png, gif, font, mp4, webm): `Cache-Control: public, max-age=31536000`
  - HTML: `Cache-Control: public, max-age=0, must-revalidate`
  - JSON/XML: `Cache-Control: public, max-age=300`

---

## 2. Optimasi (Nginx)

- **Gzip** diaktifkan per server block (mi, smp, portal) di **semua-domain**:
  - `gzip on`, `gzip_vary on`, `gzip_min_length 256`
  - Tipe: `text/plain`, `text/css`, `application/json`, `application/javascript`, `text/xml`, `application/xml`, `text/javascript`

- **Cache statis:** Aset (logo, font, script, gambar) dilayani dengan `Cache-Control: public, max-age=31536000` (1 tahun) agar kunjungan ulang cepat.
- **Kompresi:** Semua respons teks (HTML, CSS, JS) dikompres Gzip oleh Nginx sebelum dikirim ke browser.

---

## 3. Minifikasi JS/CSS (opsional)

Skrip **hanya** memproses file di tiga root di atas dan **tidak** memproses `rdmmi`:

```bash
php /mnt/webdata/htdocs/scripts/minify-static.php
```

- Memproses: `htdocs`, `htdocs/mi`, `htdocs/smp` (rekursif terbatas).
- Menghasilkan file `*.min.js` dan `*.min.css` di folder yang sama; file asli tidak diubah.
- Agar situs memakai versi minified, ubah referensi di HTML dari `file.js` ke `file.min.js` (atau gunakan build/deploy yang mengganti otomatis).

---

## 4. File yang dipasang

| File | Keterangan |
|------|------------|
| `/etc/nginx/snippets/static-cache-htdocs.conf` | Snippet cache (hanya dipakai oleh semua-domain) |
| `/etc/nginx/sites-enabled/semua-domain` | Sudah berisi gzip + include snippet (hanya untuk mi, smp, portal) |
| `/mnt/webdata/htdocs/nginx-semua-domain-with-cache.conf` | Salinan config untuk referensi/rollback |
| `/mnt/webdata/htdocs/scripts/minify-static.php` | Skrip minifikasi (opsional), hanya htdocs/mi/smp |

---

## 5. Reload Nginx setelah ubah config

```bash
sudo nginx -t && sudo systemctl reload nginx
```
