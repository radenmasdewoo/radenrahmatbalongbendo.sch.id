# 📱 Instagram Sync - Setup & Penggunaan

## ⚠️ PENTING: Token Instagram Graph API Expired

**Status:** Token Instagram Graph API kadaluarsa pada **14 Maret 2026**

**Solusi:**
1. Generate token baru dari Facebook Developer Dashboard
2. Update token di file `insta_config_mi.json`
3. Jalankan ulang sinkronisasi

---

## 🔑 Cara Mendapatkan Instagram Graph API Token Baru

### Langkah 1: Buka Facebook Developer Dashboard
1. Kunjungi: https://developers.facebook.com/apps/
2. Login dengan akun Facebook yang terhubung ke Instagram MI Raden Rahmat
3. Pilih app **MI-RaRa-StoryBlast-IG** (ID: 1247116110925821)

### Langkah 2: Generate Access Token
1. Buka **Tools & Support** → **Graph API Explorer**
2. Pilih app: **MI-RaRa-StoryBlast-IG**
3. Klik **Generate Access Token** → Pilih **Get Page Access Token**
4. Pilih page: **MI Raden Rahmat Balongbendo**
5. Izinkan permissions:
   - `instagram_basic`
   - `instagram_content_publish`
   - `pages_show_list`
   - `pages_read_engagement`

### Langkah 3: Copy Token
Token akan terlihat seperti:
```
EAAcrrDZBjwRkBQ3ml1Pnr4YStPUUrazdrlZBYCf51LWCLKyr9nBMYZCyRmixZBySRZCeOZAnR86tgvzJw8EwGLg93SeeiBcqfwXj9F88glMkxWhenpl7frbGUHz5gTk8tD3ndmsIXBkRmgz67ryYo0BMVwXpuSyCrZBqJ5ZCkWJm4A3C4qlxP7gLbh7sJn5tZAKhdp2FPKDD6H7w5kWC5xQdqCtSOJeFHQRWV847vMY5HLVZBTgxZBtZBzx2ZCpoqzQ4hwMmX4tgfqu4Uo6zTKr7AQCPkagZDZD
```

### Langkah 4: Update Config
Edit file `/mnt/webdata/htdocs/mi/api/insta_config_mi.json`:

```json
{
    "insta_handle": "miradenrahmatbalongbendo",
    "rss_url": "",
    "posts_limit": 12,
    "sync_key": "radenrahmat_sync_2026",
    "graph_api": {
        "app_id": "1247116110925821",
        "app_secret": "653d65362cbb39a71dcc514c12457161",
        "page_access_token": "TOKEN_BARU_DISINI",
        "instagram_business_account_id": ""
    }
}
```

---

## 🚀 Cara Menjalankan Sinkronisasi

### Metode 1: Via Browser/HTTP (Recommended)

```
https://radenrahmatbalongbendo.sch.id/mi/api/insta_sync_mi.php?key=radenrahmat_sync_2026
```

### Metode 2: Via Command Line (SSH)

```bash
# Login SSH
ssh admin@radenrahmatbalongbendo.sch.id

# Jalankan sync
php /mnt/webdata/htdocs/mi/api/insta_sync_mi.php key=radenrahmat_sync_2026

# Atau dengan cleanup
php /mnt/webdata/htdocs/mi/api/insta_sync_mi.php key=radenrahmat_sync_2026 cleanup=1
```

### Metode 3: Via Cron (Otomatis Setiap 2 Jam)

Sudah terkonfigurasi di crontab:
```bash
0 */2 * * * curl -s "https://radenrahmatbalongbendo.sch.id/mi/api/insta_sync_mi.php?key=radenrahmat_sync_2026" > /dev/null 2>&1
```

---

## 📊 Memeriksa Hasil Sinkronisasi

### Cek Data Berita
```bash
curl -s "https://radenrahmatbalongbendo.sch.id/mi/api/news_mi.php" | python3 -m json.tool
```

### Cek Log Sync
```bash
tail -50 /mnt/webdata/htdocs/mi/api/sync_log_mi.txt
```

### Cek File Cache
```bash
ls -lh /mnt/webdata/htdocs/mi/api/ig_cache/
```

---

## 🔧 Troubleshooting

### 1. Error: "Unauthorized"
**Solusi:** Pastikan parameter `key=radenrahmat_sync_2026` ada di URL

### 2. Error: "Failed to connect to Instagram Graph API"
**Penyebab:** Token expired atau network issue
**Solusi:**
- Generate token baru (lihat langkah di atas)
- Atau biarkan fallback ke RSS-Bridge (otomatis)

### 3. Error: "504 Gateway Timeout"
**Penyebab:** Server sedang maintenance
**Solusi:** Tunggu beberapa menit dan coba lagi

### 4. Postingan Tidak Muncul
**Penyebab:** 
- RSS-Bridge rate limited
- Instagram membatasi scraping

**Solusi:**
- Tunggu sync berikutnya (2 jam)
- Atau tambahkan manual via `add_manual_post.php`

---

## 📝 Menambah Postingan Manual

Jika ada postingan yang tidak terambil:

```bash
curl -X POST "https://radenrahmatbalongbendo.sch.id/mi/api/add_manual_post.php?key=radenrahmat_sync_2026" \
  -H "Content-Type: application/json" \
  -d '{
    "shortcode": "DV8XYZ123abc",
    "title": "Judul Postingan Terbaru",
    "img": "https://link-ke-gambar.jpg",
    "date": "22 Mar 2026",
    "is_video": false
  }'
```

---

## 📅 Jadwal Sinkronisasi

| Waktu (WIB) | Aktivitas |
|-------------|-----------|
| 00:00       | Auto Sync |
| 02:00       | Auto Sync |
| 04:00       | Auto Sync |
| 06:00       | Auto Sync |
| 08:00       | Auto Sync |
| 10:00       | Auto Sync |
| 12:00       | Auto Sync |
| 14:00       | Auto Sync |
| 16:00       | Auto Sync |
| 18:00       | Auto Sync |
| 20:00       | Auto Sync |
| 22:00       | Auto Sync |

**Total:** 12x sinkronisasi per hari

---

## 📂 Struktur File

```
/mnt/webdata/htdocs/mi/api/
├── insta_sync_mi.php           # Main sync script (RSS-Bridge)
├── insta_sync_graph_api.php    # Graph API sync (perlu token baru)
├── insta_config_mi.json        # Configuration file
├── news_mi.json                # Output data berita
├── news_mi_overrides.json      # Manual overrides
├── add_manual_post.php         # Manual post adder
├── sync_log_mi.txt             # Sync log
└── ig_cache/                   # Cached images
    ├── DV5EZmrAI2m.jpg
    ├── DV451EKAQWM.jpg
    └── ...
```

---

## 📞 Kontak Support

Untuk bantuan lebih lanjut, hubungi:
- Email: miradenrahmatbalongbendo@gmail.com
- Admin: Administrator Sistem

---

**Last Updated:** 22 Maret 2026
