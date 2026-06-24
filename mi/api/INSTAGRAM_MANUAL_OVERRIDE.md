# Instagram Sync - Troubleshooting & Manual Override

## Masalah: 2 Postingan Terbaru Tidak Terambil

### Penyebab
Instagram membatasi scraping data. RSS-Bridge (sumber utama) hanya mengembalikan **maksimal 6 postingan terbaru** karena:
1. Rate limiting dari Instagram
2. Kebijakan privasi Instagram
3. Keterbatasan RSS-Bridge instances

### Solusi yang Sudah Diterapkan

#### 1. Auto-Sync Setiap 2 Jam
Cron job sudah dikonfigurasi untuk sinkronisasi otomatis:
```bash
0 */2 * * * curl -s "https://radenrahmatbalongbendo.sch.id/mi/api/insta_sync_mi.php?key=radenrahmat_sync_2026" > /dev/null 2>&1
```

#### 2. Multi-Source Fallback
Script mencoba berbagai sumber:
- RSS-Bridge Pool (15+ instances)
- Instagram Embed API
- Imginn scraper
- Picuki scraper

#### 3. Auto-Cleanup
Menghapus berita & file cache yang lebih dari 30 hari.

---

## Manual Override - Tambah Postingan Secara Manual

Jika ada postingan yang tidak terambil oleh auto-sync, gunakan API manual:

### Cara 1: Via cURL (Command Line)

```bash
# Tambah postingan baru
curl -X POST "https://radenrahmatbalongbendo.sch.id/mi/api/add_manual_post.php?key=radenrahmat_sync_2026" \
  -H "Content-Type: application/json" \
  -d '{
    "shortcode": "DV8XYZ123abc",
    "title": "Judul Postingan Terbaru",
    "img": "https://scontent.cdninstagram.com/...",
    "date": "22 Mar 2026",
    "is_video": false
  }'
```

### Cara 2: Via Browser (dengan ekstensi REST Client)

**Endpoint:** `POST /mi/api/add_manual_post.php?key=radenrahmat_sync_2026`

**Body (JSON):**
```json
{
  "shortcode": "DV8XYZ123abc",
  "title": "Judul Postingan",
  "img": "https://link-ke-gambar.jpg",
  "date": "22 Mar 2026",
  "is_video": false,
  "link": "https://www.instagram.com/p/DV8XYZ123abc/"
}
```

### Cara 3: Edit Langsung File news_mi.json

1. Buka file: `/mnt/webdata/htdocs/mi/api/news_mi.json`
2. Tambahkan entri baru di awal array
3. Format:
```json
{
  "shortcode": "DV8XYZ123abc",
  "title": "Judul Postingan",
  "excerpt": "Ringkasan...",
  "img": "api/ig_cache/DV8XYZ123abc.jpg",
  "video": "",
  "is_video": false,
  "date": "22 Mar 2026",
  "timestamp": 1774300000,
  "link": "https://www.instagram.com/p/DV8XYZ123abc/",
  "author": "miradenrahmatbalongbendo"
}
```

---

## Cara Mendapatkan Data Postingan Instagram

### 1. Shortcode dari URL
URL: `https://www.instagram.com/p/DV8XYZ123abc/`
Shortcode: `DV8XYZ123abc`

### 2. Gambar URL
- Buka postingan di browser desktop
- Klik kanan pada gambar → "Copy Image Address"
- Atau gunakan: `https://www.instagram.com/p/{shortcode}/media/?size=l`

### 3. Caption/Title
- Copy teks caption dari postingan
- Maksimal 80 karakter untuk title

---

## Monitoring & Debugging

### Cek Status Sync Terakhir
```bash
tail -20 /mnt/webdata/htdocs/mi/api/sync_log_mi.txt
```

### Cek Data Saat Ini
```bash
curl -s "https://radenrahmatbalongbendo.sch.id/mi/api/news_mi.php" | python3 -m json.tool
```

### Force Sync Manual
```bash
curl -s "https://radenrahmatbalongbendo.sch.id/mi/api/insta_sync_mi.php?key=radenrahmat_sync_2026"
```

### Cleanup Manual
```bash
curl -s "https://radenrahmatbalongbendo.sch.id/mi/api/insta_sync_mi.php?key=radenrahmat_sync_2026&cleanup=1"
```

---

## Rekomendasi

1. **Untuk postingan rutin**: Biarkan auto-sync (setiap 2 jam)
2. **Untuk postingan penting yang terlewat**: Gunakan manual override
3. **Monitoring**: Cek website secara berkala, jika ada postingan yang tidak muncul dalam 4 jam, tambahkan manual

---

## Kontak Support

Jika ada masalah teknis, hubungi administrator sistem.
