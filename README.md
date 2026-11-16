🗄️ Backup Website Raden Rahmat Balongbendo

Repositori ini berisi arsip/backup dari website resmi lembaga pendidikan Raden Rahmat Balongbendo.

🌐 Daftar Website
Unit	URL	Folder Backup
Website Utama	https://radenrahmatbalongbendo.sch.id/	main/
MI	https://mi.radenrahmatbalongbendo.sch.id/	mi/
SMP	https://smp.radenrahmatbalongbendo.sch.id/	smp/
📁 Struktur Repositori
.
├── main/
│   ├── files/          # File website (public_html / wp-content)
│   └── database/       # Dump database (.sql)
├── mi/
│   ├── files/
│   └── database/
├── smp/
│   ├── files/
│   └── database/
├── scripts/
│   └── backup.sh       # Script backup otomatis
├── .gitignore
└── README.md
🔧 Cara Melakukan Backup
1. Backup File Website (via SSH/rsync)
bash
rsync -avz --delete user@server:/path/to/public_html/ ./main/files/
rsync -avz --delete user@server:/path/to/mi/public_html/ ./mi/files/
rsync -avz --delete user@server:/path/to/smp/public_html/ ./smp/files/
2. Backup Database (MySQL/MariaDB)
bash
mysqldump -u USER -p DB_UTAMA > main/database/main_$(date +%F).sql
mysqldump -u USER -p DB_MI    > mi/database/mi_$(date +%F).sql
mysqldump -u USER -p DB_SMP   > smp/database/smp_$(date +%F).sql
3. Backup Tampilan Statis (tanpa akses server)

Jika hanya ingin menyimpan salinan tampilan publik:

bash
wget --mirror --convert-links --adjust-extension --page-requisites \
     --no-parent -P main/files https://radenrahmatbalongbendo.sch.id/
wget --mirror --convert-links --adjust-extension --page-requisites \
     --no-parent -P mi/files https://mi.radenrahmatbalongbendo.sch.id/
wget --mirror --convert-links --adjust-extension --page-requisites \
     --no-parent -P smp/files https://smp.radenrahmatbalongbendo.sch.id/

⚠️ Cara ini hanya menyimpan HTML/CSS/JS/gambar publik, bukan database atau panel admin.

♻️ Cara Restore
Upload isi folder files/ ke direktori web server (public_html).
Impor database:
bash
mysql -u USER -p NAMA_DB < database/nama_file.sql
Sesuaikan wp-config.php (atau file konfigurasi lain) dengan kredensial database baru.
Jika domain berubah, lakukan search-replace URL pada database.
Atur ulang permission: folder 755, file 644.
🔒 Keamanan
Jangan commit file sensitif seperti wp-config.php, .env, password, atau kunci API.
Gunakan repositori private bila backup berisi database atau data siswa.
Ukuran file di GitHub dibatasi 100 MB per file; gunakan Git LFS atau simpan arsip besar di tempat lain.

Contoh .gitignore:

wp-config.php
.env
*.log
*.zip
*.tar.gz
node_modules/
🕒 Jadwal Backup
Frekuensi	Keterangan
Mingguan	Backup file + database
Sebelum update	Backup manual sebelum update tema/plugin/core

Contoh cron (setiap Minggu pukul 02:00):

cron
0 2 * * 0 /bin/bash /path/to/scripts/backup.sh
📝 Riwayat Backup
Tanggal	Website	Keterangan
YYYY-MM-DD	Utama / MI / SMP	Backup awal
📄 Lisensi & Catatan

Seluruh konten website adalah milik Raden Rahmat Balongbendo. Repositori ini hanya digunakan untuk keperluan arsip dan pemulihan data internal.

<p align="center">Dibuat untuk arsip website Raden Rahmat Balongbendo 🏫</p>
