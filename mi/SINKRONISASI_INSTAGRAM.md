# Sinkronisasi Instagram MI — Apakah Posting Baru Muncul Otomatis?

## Jawaban singkat

**Tidak otomatis dengan sendirinya.** Posting baru dari akun Instagram **akan masuk ke website hanya setelah script sinkronisasi dijalankan**. Jadi agar terasa "otomatis", Anda harus **menjadwalkan** jalannya script (cron atau Task Scheduler).

## Cara kerjanya

1. Script **`api/insta_sync_mi.php`** mengambil posting terbaru dari Instagram (via RSS-Bridge atau Imginn).
2. Hasil disimpan ke **`api/news_mi.json`** (maksimal sesuai `posts_limit` di config, default 5–10).
3. Halaman utama memakai **`api/news_mi.php`** (GET) untuk menampilkan berita; isinya dari `news_mi.json` (+ override gambar admin jika ada).

Jadi: **setiap kali script sync dijalankan**, daftar berita di website diperbarui. Jika akun Instagram baru memposting, posting itu **baru muncul di website setelah sync berikutnya dijalankan**.

## Agar "otomatis" (posting baru ikut muncul)

Anda perlu **menjalankan sync secara terjadwal**:

### Di server Linux (cron)

Contoh setiap 6 jam:

```cron
0 */6 * * * curl -s "https://mi.radenrahmatbalongbendo.sch.id/api/insta_sync_mi.php?key=radenrahmat_sync_2026"
```

Ganti `mi.radenrahmatbalongbendo.sch.id` dengan domain aktif Anda jika berbeda, dan pastikan **`key`** sesuai dengan `api/insta_config_mi.json`.

### Di Windows (Task Scheduler)

1. Buat task terjadwal yang memanggil script sync (mis. lewat `.bat` atau PHP CLI).
2. Contoh isi `sync_mi_daily.bat`: panggil URL  
   `https://domain-anda.com/mi/api/insta_sync_mi.php?key=SYNC_KEY_ANDA`  
   (pakai `curl` atau `php -r "file_get_contents('...')"`).
3. Atur jadwal (mis. setiap 6 jam atau sekali sehari).

### Manual

Jalankan di browser atau dengan curl:

```bash
https://domain-anda.com/mi/api/insta_sync_mi.php?key=SYNC_KEY_ANDA
```

**Penting:** Jangan bagikan URL ini (berisi `sync_key`). Lebih aman jika sync **hanya** dipanggil dari cron/task di server (localhost atau internal), bukan dari browser sembarang.

### Manual (Bypass Cache)

Untuk memaksa data terbaru (mengabaikan cache), tambahkan `&t=123` di akhir URL:

```bash
https://mi.radenrahmatbalongbendo.sch.id/api/insta_sync_mi.php?key=radenrahmat_sync_2026&t=1
```

### Otomatis (Cron Setup Helper)

Gunakan fitur "Auto-Setup" dengan mengunjungi URL ini di browser Anda:
`https://mi.radenrahmatbalongbendo.sch.id/api/insta_sync_mi.php?key=radenrahmat_sync_2026&setup_cron=1`

**Penting:** Perintah `0 */6 * * * ...` adalah untuk konfigurasi **crontab**, bukan untuk dijalankan langsung di terminal (Cmd/PowerShell). Gunakan `crontab -e` untuk mengedit jadwal di Linux.

## Ringkasan

| Pertanyaan | Jawaban |
| :--------- | :------ |
| Apakah posting baru Instagram langsung muncul di website? | **Tidak.** Hanya setelah sync dijalankan. |
| Apakah bisa terasa otomatis? | **Ya**, jika sync dijadwalkan (cron / Task Scheduler) misalnya tiap 6 jam atau tiap hari. |
| Config | `api/insta_config_mi.json` (handle, posts_limit, sync_key). |
| Log | `api/sync_log_mi.txt` (untuk cek apakah sync jalan). |
