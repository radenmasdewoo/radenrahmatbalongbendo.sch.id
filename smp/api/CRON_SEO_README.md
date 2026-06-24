# Cron: Auto-Optimize SEO & Meta Tags (1x per hari)

## Apakah setelan SEO benar-benar dipakai di situs live?

**Ya.** Alurnya:

1. **Sumber data:** `../data/site_config.json` (bagian `pages.smp.seo_title`, `seo_desc`, `seo_keywords`, `seo_url`, `seo_og_image`).
2. **Situs live:** https://smp.radenrahmatbalongbendo.sch.id/ memuat halaman dari `/mnt/webdata/htdocs/smp/index.html`.
3. **Penerapan:** Di `index.html` ada script `js/dynamic_loader.js`. Saat halaman dibuka, script itu memuat `data/site_config.json` lalu mengisi:
   - `document.title` ← `seo_title`
   - `<meta name="description">` ← `seo_desc`
   - `<meta name="keywords">` ← `seo_keywords`
   - `<meta property="og:title">`, `og:description`, `og:image`, `og:url` ← nilai SEO dari config

Jadi **setiap kali isi `site_config.json` di-update** (lewat Super Admin atau lewat cron ini), **pengunjung berikutnya** yang buka https://smp.radenrahmatbalongbendo.sch.id/ akan dapat judul dan meta terbaru.

## Menjalankan cron 1x per hari (tanpa buka halaman)

### 1. Ubah kode rahasia (opsional tapi disarankan)

Edit `api/cron_auto_seo.php`, baris:

```php
$CRON_SECRET = 'smp_auto_seo_2026'; // Ganti dengan kode rahasia Anda
```

Ganti `smp_auto_seo_2026` dengan string rahasia Anda, lalu pakai nilai yang sama di URL cron di bawah.

### 2. Pasang cron di server

Jalankan **sekali sehari** (contoh jam 03:00):

```bash
0 3 * * * curl -s "https://smp.radenrahmatbalongbendo.sch.id/api/cron_auto_seo.php?key=GANTI_DENGAN_KODE_RAHASIA"
```

- Ganti `GANTI_DENGAN_KODE_RAHASIA` dengan nilai `$CRON_SECRET` yang Anda pakai di `cron_auto_seo.php`.
- Jika Anda tidak mengubah `$CRON_SECRET`, pakai: `?key=smp_auto_seo_2026`.

### 3. Cek hasil

- Buka di browser (pakai `key` yang benar):  
  `https://smp.radenrahmatbalongbendo.sch.id/api/cron_auto_seo.php?key=smp_auto_seo_2026`  
  Respons sukses contoh: `{"success":true,"message":"SEO & Meta Tags auto-optimized and saved ...","updated":"...","seo_title":"..."}`.
- Setelah cron jalan, buka https://smp.radenrahmatbalongbendo.sch.id/ lalu lihat sumber halaman (View Source) atau tab “Elements” di DevTools: `<title>` dan `<meta name="description">` harus sesuai isi terbaru di `data/site_config.json`.

## Ringkasan

| Yang di-update cron | Diterapkan ke |
|---------------------|----------------|
| `data/site_config.json` → `pages.smp.seo_*` | Situs live https://smp.radenrahmatbalongbendo.sch.id/ via `index.html` + `js/dynamic_loader.js` |

Dengan cron di atas, **Auto-Optimize SEO & Simpan** benar-benar berjalan 1x per hari tanpa perlu membuka halaman Super Admin, dan setelan SEO & Meta Tags itu yang dipakai di index.html (situs live).
