# Cronjob Sinkronisasi Instagram - MI Raden Rahmat Balongbendo

## Ringkasan Konfigurasi

Cronjob telah dikonfigurasi untuk **berjalan setiap 4 jam** guna mengambil berita terbaru dari Instagram @miradenrahmatbalongbendo dan menampilkannya di website.

## Jadwal Cronjob

```
0 */4 * * * curl -s "https://mi.radenrahmatbalongbendo.sch.id/api/insta_sync_mi.php?key=radenrahmat_sync_2026" > /dev/null 2>&1
```

**Arti jadwal:**
- `0` - Menit ke-0
- `*/4` - Setiap 4 jam (00:00, 04:00, 08:00, 12:00, 16:00, 20:00)
- `* * *` - Setiap hari, setiap bulan, setiap hari dalam seminggu

## Cara Kerja Sinkronisasi

### 1. Script yang Digunakan
- **File:** `/mnt/webdata/htdocs/mi/api/insta_sync_mi.php`
- **Config:** `/mnt/webdata/htdocs/mi/api/insta_config_mi.json`
- **Output Data:** `/mnt/webdata/htdocs/mi/api/news_mi.json`
- **Cache Gambar:** `/mnt/webdata/htdocs/mi/api/ig_cache/`
- **Log:** `/mnt/webdata/htdocs/mi/api/sync_log_mi.txt`

### 2. Proses Sinkronisasi
1. Script mengambil posting terbaru dari Instagram melalui:
   - **RSS-Bridge** (primary)
   - **Imginn** (fallback)
   - **Picuki** (fallback)
   - **Instagram Embed API** (pelengkap)

2. Gambar dan video diunduh ke folder lokal `ig_cache/`

3. Data disimpan ke `news_mi.json` (maksimal 12 posting terbaru)

4. Posting lama (>30 hari) otomatis dihapus

5. Website menampilkan data dari `news_mi.json` via `api/news_mi.php`

### 3. Fitur Otomatis
- ✅ **Auto-cleanup:** Menghapus berita & cache >30 hari
- ✅ **Exponential backoff:** Retry otomatis jika gagal
- ✅ **Multi-source fallback:** Jika satu sumber gagal, coba sumber lain
- ✅ **Cache gambar:** Gambar disimpan lokal untuk performa

## Verifikasi Cronjob

### Cek cronjob yang terpasang
```bash
crontab -l | grep insta_sync_mi
```

### Cek log sinkronisasi
```bash
tail -50 /mnt/webdata/htdocs/mi/api/sync_log_mi.txt
```

### Jalankan manual (testing)
```bash
curl -s "https://mi.radenrahmatbalongbendo.sch.id/api/insta_sync_mi.php?key=radenrahmat_sync_2026"
```

### Force refresh (bypass cache)
```bash
curl -s "https://mi.radenrahmatbalongbendo.sch.id/api/insta_sync_mi.php?key=radenrahmat_sync_2026&t=1"
```

### Cleanup manual (hapus data lama)
```bash
curl -s "https://mi.radenrahmatbalongbendo.sch.id/api/insta_sync_mi.php?key=radenrahmat_sync_2026&cleanup=1"
```

## Konfigurasi

File: `/mnt/webdata/htdocs/mi/api/insta_config_mi.json`

```json
{
    "insta_handle": "miradenrahmatbalongbendo",
    "posts_limit": 12,
    "sync_key": "radenrahmat_sync_2026"
}
```

**Parameter:**
- `insta_handle` - Username Instagram (tanpa @)
- `posts_limit` - Jumlah posting maksimal yang ditampilkan (default: 12)
- `sync_key` - Kunci keamanan untuk menjalankan sync

## Troubleshooting

### Cronjob tidak berjalan
1. Pastikan cron daemon aktif:
   ```bash
   systemctl status cron
   ```

2. Cek log cron:
   ```bash
   grep CRON /var/log/syslog | tail -20
   ```

### Sinkronisasi gagal
1. Cek log sinkronisasi:
   ```bash
   tail -100 /mnt/webdata/htdocs/mi/api/sync_log_mi.txt
   ```

2. Test koneksi ke Instagram:
   ```bash
   curl -I https://www.instagram.com/miradenrahmatbalongbendo/
   ```

3. Jalankan manual dengan verbose:
   ```bash
   curl -v "https://mi.radenrahmatbalongbendo.sch.id/api/insta_sync_mi.php?key=radenrahmat_sync_2026"
   ```

### Gambar tidak muncul
1. Cek folder cache:
   ```bash
   ls -la /mnt/webdata/htdocs/mi/api/ig_cache/
   ```

2. Cek permission:
   ```bash
   chmod -R 755 /mnt/webdata/htdocs/mi/api/ig_cache/
   ```

## Update Sinkronisasi

### Ubah jadwal (misal setiap 6 jam)
```bash
crontab -e
# Ubah baris:
# Dari: 0 */4 * * * ...
# Menjadi: 0 */6 * * * ...
```

### Ubah jumlah posting
Edit `/mnt/webdata/htdocs/mi/api/insta_config_mi.json`:
```json
{
    "posts_limit": 20
}
```

### Ganti sync key (keamanan)
1. Edit config:
   ```json
   {
       "sync_key": "new_secure_key_2027"
   }
   ```

2. Update cronjob:
   ```bash
   crontab -e
   # Update URL dengan key baru
   ```

## Monitoring

### Script monitoring sederhana
Buat file `/mnt/webdata/htdocs/mi/api/check_sync.sh`:
```bash
#!/bin/bash
LOG_FILE="/mnt/webdata/htdocs/mi/api/sync_log_mi.txt"
LAST_SYNC=$(tail -1 "$LOG_FILE" | grep -oP '\[\K[0-9-]+ [0-9:]+')
echo "Last sync: $LAST_SYNC"
```

### Cek status via web
Akses: `https://mi.radenrahmatbalongbendo.sch.id/api/news_mi.php`

## Keamanan

⚠️ **PENTING:**
- Jangan bagikan `sync_key` ke publik
- Sync key saat ini: `radenrahmat_sync_2026`
- Cronjob dijalankan sebagai root/system user
- URL sync hanya bisa diakses dengan key yang benar

## Kontak & Support

Jika ada masalah dengan sinkronisasi:
1. Cek log di `/mnt/webdata/htdocs/mi/api/sync_log_mi.txt`
2. Test manual dengan curl
3. Pastikan server memiliki koneksi internet
4. Verifikasi permission folder cache

---

**Terakhir diupdate:** 29 Maret 2026
**Oleh:** System Administrator
