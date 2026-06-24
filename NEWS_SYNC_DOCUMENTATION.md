# 📰 Dokumentasi Lengkap: Sinkronisasi Berita
## BP3NU Raden Rahmat Balongbendo - Sistem Manajemen Artikel

**File Utama:** `/mnt/webdata/htdocs/berita.html`  
**Versi Dokumen:** 2.0  
**Terakhir Update:** March 28, 2026

---

## 📋 Daftar Isi

1. [Overview Sistem](#1-overview-sistem)
2. [Arsitektur Sinkronisasi](#2-arsitektur-sinkronisasi)
3. [Flow Diagram](#3-flow-diagram)
4. [API Endpoints](#4-api-endpoints)
5. [Struktur Data](#5-struktur-data)
6. [Mekanisme Sinkronisasi](#6-mekanisme-sinkronisasi)
7. [Admin Panel Features](#7-admin-panel-features)
8. [AI Article Generation](#8-ai-article-generation)
9. [Instagram Integration](#9-instagram-integration)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Overview Sistem

### 1.1 Deskripsi Umum

Sistem berita di `berita.html` adalah platform manajemen konten berita sekolah yang terintegrasi dengan:
- **Instagram** sebagai sumber konten utama
- **AI Generator** untuk pembuatan artikel otomatis
- **JSON File Storage** untuk penyimpanan data
- **Session-based Authentication** untuk keamanan admin

### 1.2 Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| 📥 **Auto-Sync** | Sinkronisasi otomatis dengan Instagram feed |
| ✍️ **AI Writer** | Generate artikel 300-500 kata otomatis |
| 🔐 **Admin Panel** | CRUD operasi dengan autentikasi |
| 📸 **Media Handler** | Upload & proxy gambar/video Instagram |
| 🔄 **Multi-Source** | Gabung feed dari berbagai akun Instagram |
| 💾 **Local Cache** | localStorage untuk performa |

---

## 2. Arsitektur Sinkronisasi

### 2.1 Komponen Sistem

```
┌─────────────────────────────────────────────────────────────────┐
│                    FRONTEND (berita.html)                       │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────────────┐   │
│  │ Admin Panel │  │ News Renderer│  │ AI Article Generator │   │
│  └─────────────┘  └──────────────┘  └──────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────────┐
│                      API LAYER (/api/)                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌────────────────┐  │
│  │ news.php │  │ auth.php │  │upload.php│  │anthropic_proxy │  │
│  └──────────┘  └──────────┘  └──────────┘  └────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────────┐
│                    DATA STORAGE LAYER                           │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌────────────────┐  │
│  │news.json │  │users.json│  │uploads/  │  │ Instagram API  │  │
│  └──────────┘  └──────────┘  └──────────┘  └────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 File Structure

```
/mnt/webdata/htdocs/
├── berita.html                 # Main application
├── api/
│   ├── news.php               # CRUD news API
│   ├── news_mi.php            # MI unit news feed
│   ├── auth.php               # Authentication API
│   ├── upload.php             # File upload API
│   ├── anthropic_proxy.php    # AI article generator
│   └── image_proxy.php        # Instagram image proxy
├── data/
│   ├── news.json              # News database
│   └── users.json             # User credentials
└── uploads/                   # Uploaded media files
```

---

## 3. Flow Diagram

### 3.1 Page Load Sequence

```
┌────────────────────────────────────────────────────────────────┐
│                    PAGE LOAD (berita.html)                     │
└────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────┐
│  STEP 1: Static HTML Render                                │
│  - Pre-rendered news cards from PHP cron injection         │
│  - AI articles already embedded in HTML                    │
│  - User sees content immediately (no loading spinner)      │
└────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────┐
│  STEP 2: Silent Data Fetch                                 │
│  - Fetch api/news.php (cache-busting: ?cb=timestamp)       │
│  - Load data to Admin.newsData (state only)                │
│  - DO NOT call renderNews() - preserve static HTML         │
└────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────┐
│  STEP 3: MI Feed Merge (Background)                        │
│  - Fetch api/news_mi.php                                   │
│  - Deduplicate by Instagram shortcode                      │
│  - Merge to newsData array                                 │
│  - DO NOT render - keep existing HTML                      │
└────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────┐
│  STEP 4: Session Check                                     │
│  - Fetch api/auth.php?action=check                         │
│  - If logged in: show admin banner & edit buttons          │
│  - Call renderNews() ONLY if admin is logged in            │
└────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────┐
│  STEP 5: Initialize Intersection Observer                  │
│  - Observe .ai-observable-card elements                    │
│  - Trigger AI generation when 15% visible                  │
│  - Cache generated articles                                │
└────────────────────────────────────────────────────────────┘
```

### 3.2 Admin Save Flow

```
┌────────────────────────────────────────────────────────────┐
│  ADMIN CLICKS "SIMPAN"                                     │
└────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────┐
│  1. Validate Form Data                                     │
│     - Check title, excerpt, link                           │
│     - Handle file upload if local mode                     │
└────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────┐
│  2. Upload Media (if local mode)                           │
│     - POST to api/upload.php                               │
│     - Convert to WebP (if image)                           │
│     - Get URL: uploads/media_xxx.webp                      │
└────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────┐
│  3. Update newsData Array                                  │
│     - If new: unshift() to beginning                       │
│     - If edit: replace at index                            │
│     - Add metadata: date, author, timestamp                │
└────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────┐
│  4. Optimistic UI Render                                   │
│     - Call renderNews() immediately                        │
│     - Close modal                                          │
│     - User sees changes instantly                          │
└────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────┐
│  5. Save to Server                                         │
│     - POST to api/news.php                                 │
│     - Send ENTIRE newsData array                           │
│     - Server deduplicates by shortcode                     │
│     - Server writes to news.json                           │
└────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────┐
│  6. Sync LocalStorage                                      │
│     - localStorage.setItem('rr_news_data', ...)            │
│     - Backup for offline access                            │
└────────────────────────────────────────────────────────────┘
```

---

## 4. API Endpoints

### 4.1 News API (`api/news.php`)

**GET - Fetch All News**
```http
GET /api/news.php?cb={timestamp}
```

**Response:**
```json
[
  {
    "title": "Judul Artikel",
    "img": "uploads/image.webp",
    "excerpt": "Ringkasan artikel...",
    "link": "https://instagram.com/p/ABC123/",
    "date": "28 Mar 2026",
    "author": "admin",
    "timestamp": 1711612800,
    "is_video": false
  }
]
```

**POST - Save All News**
```http
POST /api/news.php
Content-Type: application/json
Cookie: PHPSESSID=xxx

{
  "all_news": [...]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Data berhasil disimpan",
  "bytes": 1234
}
```

**Security:**
- Requires active session (`$_SESSION['is_admin_logged_in']`)
- Returns 403 if unauthorized
- Deduplicates by Instagram shortcode before saving

---

### 4.2 Auth API (`api/auth.php`)

**POST - Login**
```http
POST /api/auth.php?action=login
Content-Type: application/json

{
  "user": "admin",
  "pass": "password"
}
```

**Response:**
```json
{ "success": true }
```

**GET - Check Session**
```http
GET /api/auth.php?action=check
```

**Response:**
```json
{
  "logged_in": true,
  "user": "admin",
  "role": "admin"
}
```

**POST - Update Password**
```http
POST /api/auth.php?action=update_pass
Content-Type: application/json

{
  "username": "admin",
  "new_pass": "newpassword"
}
```

---

### 4.3 Upload API (`api/upload.php`)

**POST - Upload File**
```http
POST /api/upload.php
Content-Type: multipart/form-data

file: [binary]
```

**Response:**
```json
{
  "success": true,
  "url": "uploads/media_6a5b4c3d2e1f.webp"
}
```

**Supported Formats:**
- Images: JPG, JPEG, PNG, GIF, WEBP
- Videos: MP4, WEBM

**Auto-conversion:**
- Images automatically converted to WebP
- Original file deleted after conversion

---

### 4.4 AI Proxy API (`api/anthropic_proxy.php`)

**POST - Generate Article**
```http
POST /api/anthropic_proxy.php
Content-Type: application/json

{
  "prompt": "Kamu adalah jurnalis sekolah..."
}
```

**Response:**
```json
{
  "content": [
    {
      "text": "<h2>Judul Artikel</h2><p>Isi artikel...</p>"
    }
  ],
  "provider": "gemini",
  "timestamp": "2026-03-28T16:00:00+07:00"
}
```

**Providers (Auto-fallback):**
1. Google Gemini 2.5 Flash (Primary)
2. Google Gemini Backup Keys (1-3)
3. Groq LLaMA 3.3 70B
4. DeepSeek R1 (OpenRouter)

---

## 5. Struktur Data

### 5.1 News Item Schema

```typescript
interface NewsItem {
  // Required fields
  title: string;           // Judul artikel
  img: string;             // URL gambar/thumbnail
  excerpt: string;         // Ringkasan konten
  link: string;            // URL Instagram post
  date: string;            // Format: "DD MMM YYYY"
  author: string;          // Username admin/Instagram handle
  
  // Optional fields
  timestamp?: number;      // Unix timestamp (auto-generated)
  is_video?: boolean;      // True jika konten video
  video?: string;          // URL video (jika berbeda dari img)
  media_type?: string;     // 'image' | 'video' | 'carousel'
}
```

### 5.2 Example Data

```json
{
  "title": "Juara 1 Lomba Karya Ilmiah Remaja",
  "img": "uploads/media_6a5b4c3d2e1f.webp",
  "excerpt": "Siswa SMP Raden Rahmat berhasil meraih juara 1 dalam lomba karya ilmiah tingkat provinsi.",
  "link": "https://www.instagram.com/p/C5AbCdEfGhI/",
  "date": "28 Mar 2026",
  "author": "smp_r_rahmat",
  "timestamp": 1711612800,
  "is_video": false
}
```

### 5.3 Deduplication Logic

```javascript
// Extract Instagram shortcode from URL
const shortcode = link.match(/instagram\.com\/(?:p|reel|tv)\/([^\/\?\#]+)/i)?.[1];

// Fallback for non-Instagram links
const fallbackId = md5(link + title);

// Unique identifier: shortcode || fallbackId
```

---

## 6. Mekanisme Sinkronisasi

### 6.1 Multi-Layer Sync Strategy

```
┌─────────────────────────────────────────────────────────────┐
│  LAYER 1: Static HTML (Pre-injected)                        │
│  - PHP cron job injects news cards ke HTML                  │
│  - AI articles sudah ter-generate                           │
│  - Zero JavaScript dependency untuk initial render          │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  LAYER 2: localStorage Cache                                │
│  - Backup data dari sesi sebelumnya                         │
│  - Fallback jika server unavailable                         │
│  - Key: 'rr_news_data'                                      │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  LAYER 3: Server Fetch (Silent)                             │
│  - Fetch api/news.php dengan cache-busting                  │
│  - Update Admin.newsData state                              │
│  - TIDAK re-render jika bukan admin                         │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  LAYER 4: MI Feed Merge                                     │
│  - Fetch api/news_mi.php                                    │
│  - Deduplicate by shortcode                                 │
│  - Merge ke newsData array                                  │
└─────────────────────────────────────────────────────────────┘
```

### 6.2 Sync Timing

| Event | Trigger | Action |
|-------|---------|--------|
| **Page Load** | DOMContentLoaded | Fetch news + MI feed |
| **Admin Login** | Session check passes | Re-render with edit buttons |
| **Save News** | Admin clicks Simpan | POST to server + localStorage |
| **Scroll** | Intersection Observer | Generate AI article |
| **Background** | Every 2 minutes (optional) | Refresh visitor stats |

### 6.3 Cache Strategy

```javascript
// localStorage caching
localStorage.setItem('rr_news_data', JSON.stringify(newsData));

// Cache-busting untuk fresh data
const response = await fetch(this.apiPath + `?cb=${Date.now()}`);

// AI article cache (in-memory Map)
this._articleCache = new Map();
this._articleCache.set(articleId, renderedHTML);
```

---

## 7. Admin Panel Features

### 7.1 Authentication Flow

```javascript
// Login
async login() {
    const res = await fetch(`${this.authPath}?action=login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user, pass })
    });
    const data = await res.json();
    
    if (data.success) {
        this.isLoggedIn = true;
        this.currentUser = user;
        // Fetch role
        const checkRes = await fetch(`${this.authPath}?action=check`);
        const checkData = await checkRes.json();
        this.userRole = checkData.role || 'admin';
    }
}

// Check session
async checkSession() {
    const res = await fetch(`${this.authPath}?action=check`);
    const data = await res.json();
    return data.logged_in;
}
```

### 7.2 CRUD Operations

**CREATE (Tambah Berita)**
```javascript
openAdd() {
    document.getElementById('edit-index').value = 'new';
    document.getElementById('edit-title').value = '';
    document.getElementById('edit-img').value = '';
    document.getElementById('edit-excerpt').value = '';
    document.getElementById('edit-link').value = '';
    this.switchUpload('local'); // Default to file upload
}
```

**READ (Load Data)**
```javascript
async init() {
    const response = await fetch(this.apiPath + `?cb=${Date.now()}`);
    const data = await response.json();
    this.newsData = data;
    // DO NOT call renderNews() unless admin
}
```

**UPDATE (Edit Berita)**
```javascript
openEdit(index) {
    const item = this.newsData[index];
    document.getElementById('edit-index').value = index;
    document.getElementById('edit-title').value = item.title;
    document.getElementById('edit-img').value = item.img;
    document.getElementById('edit-excerpt').value = item.excerpt;
    document.getElementById('edit-link').value = item.link;
}
```

**DELETE (Hapus Berita)**
```javascript
deleteNews(index) {
    if (confirm(`Hapus berita: "${this.newsData[index].title}"?`)) {
        this.newsData.splice(index, 1);
        // Save to server
        fetch(this.apiPath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ all_news: this.newsData })
        });
    }
}
```

### 7.3 Upload Modes

**Mode 1: Local Upload**
```javascript
async uploadFile(file) {
    const formData = new FormData();
    formData.append('file', file);
    
    const res = await fetch(this.uploadPath, {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    return data.url; // uploads/media_xxx.webp
}
```

**Mode 2: URL Input**
```javascript
// Direct URL input (no upload)
const imgUrl = document.getElementById('edit-img').value;
// Use as-is: https://example.com/image.jpg
```

---

## 8. AI Article Generation

### 8.1 Trigger Mechanism

```javascript
initAIObserver() {
    const options = {
        root: null,
        rootMargin: '0px',
        threshold: 0.15 // Trigger when 15% visible
    };
    
    this._aiObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const card = entry.target;
                const articleId = card.getAttribute('data-article-id');
                const index = card.getAttribute('data-news-index');
                
                if (!this._articleCache.has(articleId)) {
                    card.classList.add('ai-loading');
                    this.generateAIArticle(articleId, this.newsData[index]);
                }
                observer.unobserve(card); // Only trigger once
            }
        });
    }, options);
}
```

### 8.2 Prompt Template

```javascript
const prompt = `Kamu adalah jurnalis sekolah resmi BP3NU Raden Rahmat Balongbendo, Sidoarjo.
Tulis artikel berita berdasarkan data postingan Instagram berikut.

DATA: 
  Akun: ${newsItem.author}, 
  Judul: ${newsItem.title}, 
  Ringkasan: ${newsItem.excerpt}, 
  Tanggal: ${newsItem.date}, 
  Link: ${newsItem.link}

KETENTUAN: 
  - Panjang 300-500 kata
  - Bahasa Indonesia baku
  - Struktur: lead → isi (2-3 paragraf) → penutup
  - Gunakan <h2> untuk judul bagian, <p> untuk paragraf, <strong> untuk kata kunci
  - Sertakan konteks BP3NU Raden Rahmat sebagai lembaga pendidikan Islami berakreditasi A
  - Output HANYA HTML murni tanpa backtick atau markdown`;
```

### 8.3 Response Processing

```javascript
async generateAIArticle(articleId, newsItem) {
    // 1. Show skeleton loading
    container.innerHTML = '<div class="shimmer-wrapper">...</div>';
    
    // 2. Call AI proxy
    const response = await fetch('api/anthropic_proxy.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ prompt })
    });
    const data = await response.json();
    
    // 3. Extract text
    let aiHtml = data.content[0].text;
    
    // 4. Clean markdown
    aiHtml = aiHtml.replace(/```html/g, '').replace(/```/g, '');
    
    // 5. Word count
    const wordCount = aiHtml.replace(/<[^>]*>?/gm, '').split(/\s+/).length;
    
    // 6. Render with metadata
    container.innerHTML = `
        <div class="ai-content">${aiHtml}</div>
        <div class="ai-footer-box">
            <span>${wordCount} kata</span>
            <span>Generated by Gemini AI 2.5</span>
        </div>
    `;
    
    // 7. Cache result
    this._articleCache.set(articleId, container.innerHTML);
}
```

### 8.4 Fallback Strategy

```javascript
try {
    // Try AI generation
    const article = await generateAIArticle();
    render(article);
} catch (e) {
    // Fallback to excerpt
    render(`<p>${newsItem.excerpt}</p>
            <a href="${newsItem.link}">Lihat sumber</a>`);
}
```

---

## 9. Instagram Integration

### 9.1 Shortcode Extraction

```javascript
extractInstagramShortcode(link) {
    if (!link || typeof link !== 'string') return '';
    
    // Match patterns: /p/XXX, /reel/XXX, /tv/XXX
    const match = link.match(/instagram\.com\/(?:p|reel|tv)\/([^\/\?\#]+)/i);
    return (match && match[1]) ? match[1] : '';
}
```

**Examples:**
- `https://instagram.com/p/ABC123/` → `ABC123`
- `https://instagram.com/reel/XYZ789/` → `XYZ789`
- `https://example.com/page` → `''` (fallback to MD5)

### 9.2 Media Rendering

```javascript
renderMedia(item) {
    const shortcode = this.extractInstagramShortcode(item.link);
    const isVideo = item.is_video || this.isVideoMedia(item.link);
    
    // Video with thumbnail overlay
    if (isVideo && !item.video) {
        return `
            <a href="${item.link}" target="_blank">
                <img src="api/image_proxy.php?shortcode=${shortcode}&kind=image">
                <span class="play-icon"><i class="fas fa-play"></i></span>
            </a>
        `;
    }
    
    // Actual video element
    if (isVideo && item.video) {
        return `
            <video src="${item.video}" autoplay loop muted playsinline>
                <img src="${item.img}" alt="Thumbnail">
            </video>
        `;
    }
    
    // Static image
    return `<img src="api/image_proxy.php?shortcode=${shortcode}&kind=image">`;
}
```

### 9.3 Image Proxy (`api/image_proxy.php`)

**Purpose:**
- Bypass CORS restrictions
- Cache Instagram CDN images
- Handle expired URLs
- Convert to WebP on-the-fly

**Usage:**
```html
<img src="api/image_proxy.php?shortcode=ABC123&kind=image">
<img src="api/image_proxy.php?url=https://scontent.cdninstagram.com/...">
```

---

## 10. Troubleshooting

### 10.1 Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| **Artikel AI tidak muncul** | API key invalid / timeout | Check `.env` file, test API connection |
| **Edit buttons tidak muncul** | Session expired | Re-login via admin panel |
| **Upload gagal** | Folder permissions | `chmod 777 /mnt/webdata/htdocs/uploads/` |
| **Instagram gambar broken** | CDN URL expired | Clear cache, proxy will regenerate |
| **Save ke server gagal** | news.json write protected | `chmod 666 /mnt/webdata/htdocs/api/news.json` |

### 10.2 Debug Mode

**Enable in `.env`:**
```ini
DEBUG_MODE=1
```

**Console commands:**
```javascript
// Check current state
console.log(Admin.newsData);
console.log(Admin.isLoggedIn);
console.log(Admin._articleCache);

// Force sync
Admin.syncWithIndexData();

// Clear cache
Admin._articleCache.clear();
Admin.syncWithIndexData();
```

### 10.3 Manual Data Recovery

**If news.json corrupted:**
```bash
# Backup current file
cp /mnt/webdata/htdocs/api/news.json /mnt/webdata/htdocs/api/news.json.bak

# Restore from localStorage (browser console)
console.log(JSON.stringify(localStorage.getItem('rr_news_data')));

# Manually paste to news.json
nano /mnt/webdata/htdocs/api/news.json
```

**If users.json locked:**
```bash
# Reset admin password
php -r "echo password_hash('newpassword', PASSWORD_BCRYPT);"
# Edit users.json with new hash
```

---

## 📎 Appendix

### A. File Permissions

```bash
# Required permissions
chmod 644 /mnt/webdata/htdocs/berita.html
chmod 644 /mnt/webdata/htdocs/api/*.php
chmod 666 /mnt/webdata/htdocs/api/news.json
chmod 777 /mnt/webdata/htdocs/uploads/
chmod 600 /mnt/webdata/.env
chmod 644 /mnt/webdata/.htaccess
```

### B. Environment Variables

```ini
# /mnt/webdata/.env
GEMINI_API_KEY=AIzaSyDkA45gu4Jl4L4maNqRmhVoB2zBMojKUIs
GEMINI_API_KEY_BACKUP1=AIzaSyBQ-UMRsqvWol4Cy3q0Nu4R7D4ck4Nmsrs
GROQ_API_KEY=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
AI_PROVIDER_PRIORITY=gemini,groq
API_TIMEOUT=60
DEBUG_MODE=0
```

### C. Related Documentation

- [AI Security Guide](/mnt/webdata/AI_SECURITY_GUIDE.md)
- [API Token Reference](/mnt/webdata/htdocs/API%20TOKEN.txt)
- [Visitor Stats API](/mnt/webdata/htdocs/api/visitor_count.php)

---

**Dokumentasi ini dibuat untuk internal BP3NU Raden Rahmat Balongbendo.**

**Kontak IT Support:** [your-it-team@radenrahmatbalongbendo.sch.id](mailto:your-it-team@radenrahmatbalongbendo.sch.id)
