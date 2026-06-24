# 🛠️ PANDUAN INSTALASI CMS ADMIN PANEL
## SMP R Rahmat Balongbendo

---

## 📁 File yang Disediakan

```
cms_output/
├── admin/
│   └── index.php          ← Panel CMS Admin (LETAKKAN di folder smp/admin/)
└── js/
    └── dynamic_loader.js  ← Loader yang diperbarui (GANTI yang lama)
```

---

## 🚀 Cara Install (3 Langkah)

### Langkah 1: Copy file admin
Buat folder baru: `C:\wamp64\www\web_RR\smp\admin\`
Salin file `admin/index.php` ke dalam folder tersebut.

### Langkah 2: Ganti dynamic_loader.js
Salin `js/dynamic_loader.js` ke:
`C:\wamp64\www\web_RR\smp\js\dynamic_loader.js`
(timpa file yang lama)

### Langkah 3: Akses Panel Admin
Buka browser, masuk ke:
```
http://localhost:8080/web_rr/smp/admin/
```

---

## 🔐 Login

| Username    | Password     | Role        |
|-------------|--------------|-------------|
| superadmin  | .Kmzwa88saa  | Super Admin |
| adminsmp    | (password lama) | Admin    |

> ⚠️ **Segera ganti password** setelah pertama login!

---

## ✏️ Fitur yang Bisa Diedit

| Menu               | Konten yang Bisa Diubah |
|--------------------|--------------------------|
| 🖼️ Hero / Banner   | Judul, subtitle, badge PPDB, tombol, background |
| 📋 Navigasi        | Tambah/hapus/edit menu navbar |
| ⭐ Keunggulan      | Kartu keunggulan (icon, judul, deskripsi) — tambah/hapus |
| 📝 PPDB            | Judul form, nomor WA admin, tahun ajaran, status buka/tutup |
| 📞 Kontak & Footer | Alamat, telepon, WA, email, copyright |
| 📱 Media Sosial    | Instagram, Facebook, YouTube, TikTok |
| 🔍 SEO & Meta      | Title, description, keywords, OG image |
| 🎨 Tampilan        | Warna primer, aksen, PPDB button, logo |

---

## ⚙️ Cara Kerja

1. Admin mengubah konten di panel → tersimpan ke `data/site_config.json`
2. Halaman `index.html` memuat `js/dynamic_loader.js` saat dibuka
3. `dynamic_loader.js` membaca `site_config.json` dan menerapkan perubahan otomatis ke halaman
4. Pengunjung melihat tampilan terbaru **tanpa perlu edit kode HTML**

---

## 🆘 Troubleshooting

**Panel tidak bisa diakses?**
→ Pastikan WAMP aktif dan folder `admin/` ada di path yang benar

**Login gagal?**
→ Cek file `data/users.json` masih ada
→ Gunakan superadmin dengan password `.Kmzwa88saa`

**Perubahan tidak muncul di website?**
→ Cek browser console (F12) untuk error
→ Pastikan `api/admin_api.php` bisa diakses
→ Cek folder `data/` punya permission write

**Upload gambar gagal?**
→ Buat folder `uploads/` di root web_RR
→ Pastikan folder `uploads/` bisa ditulis (permission 755)

---

## 📌 Catatan Penting

- Semua perubahan tersimpan di `data/site_config.json`
- Backup file tersebut secara rutin
- `dynamic_loader.js` menerapkan perubahan **setiap halaman dimuat**
- Untuk perubahan yang belum didukung loader, edit langsung di `index.html`
