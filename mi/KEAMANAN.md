# Cek Keamanan Website MI — Sebelum Upload ke Server

## Yang sudah diterapkan

- **Auth (api/auth.php):** Validasi username/password, tidak mengembalikan hash ke client, session untuk login.
- **CMS:** Tidak menyimpan password di localStorage; meta `noindex` di halaman admin.
- **Upload gambar berita:** Hanya untuk user yang sudah login; validasi MIME; file disimpan di `api/uploads/`.
- **API berita (POST):** Hanya dengan session admin; override gambar per shortcode (tidak menimpa seluruh data Instagram).
- **image_proxy.php:** Cek path traversal (no `..`), realpath agar hanya file di bawah folder api yang disajikan.
- **api/.htaccess:** Blok akses langsung ke `insta_config_mi.json`, `insta_config.json`, `sync_log*.txt`, dan file `test_*.php` / `test_*.html` (disarankan dipakai di production).

## Yang harus Anda lakukan sebelum/setelah upload

### 1. Sync Instagram (sync_key)

- File **`api/insta_config_mi.json`** berisi **`sync_key`** (rahasia untuk menjalankan sync).
- **Ganti** `sync_key` untuk production (string acak panjang, jangan pakai `radenrahmat_sync_2026`).
- Jangan panggil URL sync dari browser sembarang; lebih aman hanya dari **cron/task di server** (lihat `SINKRONISASI_INSTAGRAM.md`).

### 2. Data sensitif

- **`data/users.json`** (jika ada): berisi hash password. Pastikan tidak bisa diakses langsung dari web (folder `data/` di luar document root atau dilindungi .htaccess).
- **`api/insta_config_mi.json`** dan **`api/insta_config.json`**: sudah diblok akses langsung lewat **api/.htaccess** (Apache). Jika pakai Nginx, tambah rule untuk melarang akses ke file tersebut.

### 3. HTTPS

- Aktifkan **HTTPS** di server. Set session cookie hanya via HTTPS (bisa diatur di `php.ini` atau di script: `session_set_cookie_params(..., true)`).

### 4. File test / debug

- **api/.htaccess** sudah memblok akses ke file `test_*.php` dan `test_*.html`. Sebelum production, pastikan tidak ada URL yang mengarah ke script test.
- Jika tidak pakai Apache, hapus atau pindahkan file `api/test_*.php` dan `api/test_*.html` agar tidak bisa diakses.

### 5. Session

- Pastikan **session** dipakai untuk semua aksi admin (login, upload, set override berita). Jangan simpan kredensial di front-end.

### 6. Upload folder

- Folder **`api/uploads/`** harus writable oleh PHP; jangan izinkan eksekusi script (mis. tidak ada file `.php` di dalamnya, atau set konfigurasi server agar tidak menjalankan PHP di folder uploads).

## Checklist singkat sebelum go-live

- [ ] Ganti `sync_key` di `api/insta_config_mi.json` (dan `insta_config.json` jika dipakai).
- [ ] Pastikan `data/users.json` tidak bisa diakses langsung dari web.
- [ ] HTTPS aktif; session cookie aman.
- [ ] File test tidak bisa diakses (atau sudah dihapus/dipindah).
- [ ] Jadwal sync Instagram (cron/task) sudah diatur jika ingin posting baru ikut muncul (lihat `SINKRONISASI_INSTAGRAM.md`).
