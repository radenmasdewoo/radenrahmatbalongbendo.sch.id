# Sinkronisasi Instagram ke news.json

`news.json` dapat diperbarui otomatis dari 10 postingan Instagram terbaru menggunakan RSS feed.

## Cara Setup

### 1. Buat RSS Feed Instagram (gratis)

1. Buka [RSS.app - Instagram RSS Generator](https://rss.app/rss-feed/create-instagram-rss-feed)
2. Paste URL profil: `https://www.instagram.com/osis_smprrahmat/`
3. Klik **Generate**
4. Salin **URL RSS** yang dihasilkan (format: `https://...`)

### 2. Konfigurasi

Edit `api/insta_config.json`:

```json
{
    "rss_url": "https://rss.app/feeds/XXXXX.xml",
    "insta_handle": "osis_smprrahmat",
    "posts_limit": 10,
    "sync_key": "radenrahmat_sync_2026"
}
```

- `rss_url`: URL RSS dari langkah 1
- `posts_limit`: jumlah postingan terbaru yang diambil (default: 10)
- `sync_key`: rahasia untuk trigger sync (ubah di production)

### 3. Jalankan Sync

**Manual (browser/curl):**
```
https://smp.radenrahmatbalongbendo.sch.id/api/insta_sync.php?key=radenrahmat_sync_2026
```

**Otomatis via Cron (setiap 6 jam):**
```cron
0 */6 * * * curl -s "https://radenrahmat.ct.ws/api/insta_sync.php?key=radenrahmat_sync_2026"
```

Atau dengan PHP CLI:
```cron
0 */6 * * * cd /path/to/radenrahmat/api && php -r "file_get_contents('https://radenrahmat.ct.ws/api/insta_sync.php?key=radenrahmat_sync_2026');"
```

## Sinkronisasi MI (@mir.rahmatbalongbendo)

Halaman MI menggunakan feed terpisah:
- Config: `api/insta_config_mi.json`
- Data: `api/news_mi.json`
- Sync: `api/insta_sync_mi.php?key=radenrahmat_sync_2026`

RSS: https://rss.app/feeds/OL0KbiS9lvlpDQB6.xml

Cron (tambahkan baris):
```cron
0 */6 * * * curl -s "https://radenrahmat.ct.ws/api/insta_sync_mi.php?key=radenrahmat_sync_2026"
```

## Catatan

- Akun Instagram harus **publik**
- RSS.app free tier biasanya cukup untuk website sekolah
- Alternatif: [Instatom](https://instatom.com), [QueryFeed](https://queryfeed.net) juga menyediakan Instagram RSS
