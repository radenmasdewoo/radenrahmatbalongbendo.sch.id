# SKILL: MI-RaRa-StoryBlast

## IDENTITAS PROYEK
- Nama: MI-RaRa-StoryBlast
- Domain: mi.radenrahmatbalongbendo.sch.id
- Stack: React.js + Node.js + Express + MySQL
- Warna Brand: #157a3c (Hijau LP Ma'arif NU)
- Bahasa: Indonesia

## META API CREDENTIALS
META_APP_ID=2018343639040281
META_APP_SECRET=957cac3555021533c473f18bd29ad054
META_ACCESS_TOKEN=EAAcrrDZBjwRkBQ3ml1Pnr4YStPUUrazdrlZBYCf51LWCLKyr9nBMYZCyRmixZBySRZCeOZAnR86tgvzJw8EwGLg93SeeiBcqfwXj9F88glMkxWhenpl7frbGUHz5gTk8tD3ndmsIXBkRmgz67ryYo0BMVwXpuSyCrZBqJ5ZCkWJm4A3C4qlxP7gLbh7sJn5tZAKhdp2FPKDD6H7w5kWC5xQdqCtSOJeFHQRWV847vMY5HLVZBTgxZBtZBzx2ZCpoqzQ4hwMmX4tgfqu4Uo6zTKr7AQCPkagZDZD
FB_PAGE_NAME=MI Raden Rahmat Balongbendo
IG_APP_ID=1247116110925821
IG_APP_SECRET=653d65362cbb39a71dcc514c12457161

## KEAMANAN WAJIB
- Password: bcrypt rounds 12
- SQL: prepared statements SELALU
- Token DB: enkripsi AES-256
- Rate limit: 10 req/menit
- Helmet.js wajib dipasang
- JWT expiry: 8 jam
- CORS: hanya mi.radenrahmatbalongbendo.sch.id
- robots.txt: Disallow /api/ dan /admin/

## DATABASE SCHEMA
### Tabel users
id, username, password_hash, role(superadmin/admin/operator), is_active, created_at, last_login

### Tabel platforms
id, platform(facebook/instagram/whatsapp), account_name, access_token_encrypted, page_id, is_active, token_expires_at

### Tabel post_logs
id, user_id, platform, caption, media_url, status(success/failed/pending), meta_post_id, error_message, created_at

## FITUR APLIKASI
1. Upload media drag&drop max 500MB
2. Auto caption via Gemini Flash API
3. Kategori: Umum, Kegiatan, PPDB, Prestasi
4. Post ke Facebook Page + Instagram + WhatsApp
5. User management 3 role
6. Login aman + ubah password
7. Status monitor real-time
8. Responsive mobile & desktop

## STRUKTUR FOLDER
/mnt/webdata/htdocs/mi/storyblast/
├── frontend/ (React.js)
├── backend/ (Node.js+Express)
├── database/ (schema.sql)
└── .env (credentials)
