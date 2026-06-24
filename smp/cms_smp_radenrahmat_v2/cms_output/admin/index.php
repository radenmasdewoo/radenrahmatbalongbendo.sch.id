<?php
session_start();
$isLoggedIn = isset($_SESSION['is_admin_logged_in']) && $_SESSION['is_admin_logged_in'] === true;
$user = $_SESSION['current_user'] ?? '';
$role = $_SESSION['user_role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMS Admin — SMP R Rahmat</title>
<link rel="icon" href="../logo-smp.jpg" type="image/jpeg">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --green: #006837;
  --green-dark: #004d29;
  --green-light: #e8f5e9;
  --gold: #f39c12;
  --red: #e74c3c;
  --navy: #1a2332;
  --sidebar-w: 260px;
  --radius: 14px;
  --shadow: 0 4px 24px rgba(0,0,0,0.09);
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f0f4f8;color:#1a2332;min-height:100vh}

/* LOGIN */
#login-screen{display:flex;align-items:center;justify-content:center;min-height:100vh;
  background:linear-gradient(135deg,#004d29 0%,#006837 50%,#0a8a4b 100%);position:relative;overflow:hidden}
#login-screen::before{content:'';position:absolute;inset:0;
  background:radial-gradient(circle at 20% 80%,rgba(243,156,18,.15) 0%,transparent 50%),
             radial-gradient(circle at 80% 20%,rgba(255,255,255,.05) 0%,transparent 50%)}
.login-card{background:white;border-radius:24px;padding:48px 40px;width:420px;max-width:95vw;
  box-shadow:0 30px 80px rgba(0,0,0,0.3);position:relative;z-index:1}
.login-logo{text-align:center;margin-bottom:32px}
.login-logo img{width:70px;height:70px;object-fit:cover;border-radius:50%;border:3px solid var(--green);margin-bottom:12px}
.login-logo h2{font-family:'Outfit',sans-serif;font-size:1.3rem;font-weight:800;color:var(--green);margin-bottom:4px}
.login-logo p{font-size:.8rem;color:#666}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:.8rem;font-weight:700;color:#444;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px}
.form-group .input-wrap{position:relative}
.form-group .input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#aaa;font-size:.9rem}
.form-group input{width:100%;padding:12px 14px 12px 40px;border:2px solid #e8ecf0;border-radius:10px;font-size:1rem;
  font-family:'Plus Jakarta Sans',sans-serif;transition:.2s;background:#f8fafc}
.form-group input:focus{outline:none;border-color:var(--green);background:white}
.btn-login{width:100%;padding:14px;background:var(--green);color:white;border:none;border-radius:10px;
  font-size:1rem;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;transition:.3s;margin-top:8px}
.btn-login:hover{background:var(--green-dark);transform:translateY(-1px);box-shadow:0 8px 20px rgba(0,104,55,.4)}
.login-error{background:#fff5f5;border:1px solid #fca5a5;color:#dc2626;padding:10px 14px;border-radius:8px;
  font-size:.85rem;margin-top:12px;display:none}

/* LAYOUT */
#app{display:none}
.sidebar{position:fixed;left:0;top:0;bottom:0;width:var(--sidebar-w);background:var(--navy);
  display:flex;flex-direction:column;z-index:100;transition:.3s;overflow-y:auto;overflow-x:hidden}
.sidebar::-webkit-scrollbar{width:4px}
.sidebar::-webkit-scrollbar-track{background:transparent}
.sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15);border-radius:4px}
.sidebar-header{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.08)}
.sidebar-header .logo-row{display:flex;align-items:center;gap:12px}
.sidebar-header img{width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid var(--green)}
.sidebar-header .brand h3{font-family:'Outfit',sans-serif;font-size:.95rem;font-weight:800;color:white;line-height:1.2}
.sidebar-header .brand p{font-size:.7rem;color:rgba(255,255,255,.4);margin-top:2px}

.nav-section{padding:16px 12px 8px;font-size:.65rem;font-weight:700;text-transform:uppercase;
  letter-spacing:1px;color:rgba(255,255,255,.3)}
.nav-item{display:flex;align-items:center;gap:12px;padding:11px 16px;border-radius:10px;margin:2px 8px;
  cursor:pointer;transition:.2s;color:rgba(255,255,255,.6);font-size:.88rem;font-weight:500}
.nav-item:hover{background:rgba(255,255,255,.07);color:white}
.nav-item.active{background:var(--green);color:white;font-weight:600}
.nav-item i{width:18px;text-align:center;font-size:.9rem}

.sidebar-footer{margin-top:auto;padding:16px;border-top:1px solid rgba(255,255,255,.08)}
.user-badge{display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;
  background:rgba(255,255,255,.06)}
.user-badge .avatar{width:34px;height:34px;background:var(--green);border-radius:50%;
  display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:.9rem}
.user-badge .info h4{font-size:.82rem;font-weight:700;color:white}
.user-badge .info p{font-size:.7rem;color:rgba(255,255,255,.4)}
.btn-logout{display:flex;align-items:center;gap:8px;padding:9px 14px;margin-top:8px;border-radius:8px;
  background:rgba(231,76,60,.15);color:#ff6b5b;cursor:pointer;font-size:.82rem;font-weight:600;
  border:1px solid rgba(231,76,60,.2);transition:.2s;width:100%}
.btn-logout:hover{background:rgba(231,76,60,.25)}

.main{margin-left:var(--sidebar-w);min-height:100vh;display:flex;flex-direction:column}
.topbar{background:white;padding:16px 28px;display:flex;align-items:center;justify-content:between;
  box-shadow:0 1px 0 #e8ecf0;position:sticky;top:0;z-index:50}
.topbar h1{font-family:'Outfit',sans-serif;font-size:1.25rem;font-weight:800;color:var(--navy);flex:1}
.topbar-actions{display:flex;align-items:center;gap:12px}
.btn-preview{display:flex;align-items:center;gap:7px;padding:9px 16px;background:var(--green-light);
  color:var(--green);border-radius:8px;font-size:.85rem;font-weight:700;cursor:pointer;
  border:none;text-decoration:none;transition:.2s}
.btn-preview:hover{background:var(--green);color:white}

.content{padding:28px;flex:1}

/* CARDS */
.card{background:white;border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
.card-header{padding:18px 24px;border-bottom:1px solid #f0f4f8;display:flex;align-items:center;gap:10px}
.card-header .icon{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.9rem}
.card-header h3{font-family:'Outfit',sans-serif;font-size:1rem;font-weight:700}
.card-header p{font-size:.78rem;color:#888;margin-top:1px}
.card-body{padding:24px}

/* GRID */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px}

/* FORM ELEMENTS */
.field-group{margin-bottom:20px}
.field-label{font-size:.78rem;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:7px;display:block}
.field-input{width:100%;padding:10px 14px;border:2px solid #e8ecf0;border-radius:9px;font-size:.92rem;
  font-family:'Plus Jakarta Sans',sans-serif;transition:.2s;background:#fafbfc;resize:vertical}
.field-input:focus{outline:none;border-color:var(--green);background:white}
textarea.field-input{min-height:90px}
.field-hint{font-size:.73rem;color:#aaa;margin-top:4px}
.char-count{font-size:.73rem;color:#aaa;text-align:right;margin-top:3px}

/* BADGE */
.badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:700}
.badge-green{background:#d1fae5;color:#065f46}
.badge-gold{background:#fef3c7;color:#92400e}
.badge-blue{background:#dbeafe;color:#1e40af}
.badge-red{background:#fee2e2;color:#991b1b}

/* SAVE BTN */
.btn-save{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:var(--green);
  color:white;border:none;border-radius:9px;font-size:.9rem;font-weight:700;cursor:pointer;
  font-family:'Plus Jakarta Sans',sans-serif;transition:.3s}
.btn-save:hover{background:var(--green-dark);transform:translateY(-1px);box-shadow:0 6px 18px rgba(0,104,55,.35)}
.btn-save:disabled{opacity:.6;cursor:not-allowed;transform:none}
.btn-danger{background:#fee2e2;color:#dc2626;border:1px solid #fca5a5}
.btn-danger:hover{background:#dc2626;color:white}
.btn-add{background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe}
.btn-add:hover{background:#2563eb;color:white}

/* TOAST */
.toast-container{position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px}
.toast{display:flex;align-items:center;gap:12px;padding:14px 18px;border-radius:12px;
  background:white;box-shadow:0 8px 30px rgba(0,0,0,.15);min-width:280px;
  animation:slideIn .3s ease;font-size:.88rem;font-weight:500}
.toast.success{border-left:4px solid #22c55e}
.toast.error{border-left:4px solid #ef4444}
.toast-icon{font-size:1.1rem}
.toast.success .toast-icon{color:#22c55e}
.toast.error .toast-icon{color:#ef4444}
@keyframes slideIn{from{transform:translateX(120px);opacity:0}to{transform:translateX(0);opacity:1}}

/* FEATURE CARDS */
.feature-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
.feature-item{border:2px solid #e8ecf0;border-radius:12px;padding:18px;transition:.2s;position:relative}
.feature-item:hover{border-color:var(--green);background:#fafffe}
.feature-item .item-number{position:absolute;top:-10px;left:14px;background:var(--green);color:white;
  width:24px;height:24px;border-radius:50%;font-size:.72rem;font-weight:700;
  display:flex;align-items:center;justify-content:center}
.feature-item .btn-remove{position:absolute;top:10px;right:10px;width:26px;height:26px;
  background:#fee2e2;color:#dc2626;border:none;border-radius:6px;cursor:pointer;
  font-size:.75rem;display:flex;align-items:center;justify-content:center;transition:.2s}
.feature-item .btn-remove:hover{background:#dc2626;color:white}

/* NAV EDITOR */
.nav-row{display:flex;align-items:center;gap:10px;padding:10px 14px;border:2px solid #e8ecf0;
  border-radius:10px;margin-bottom:8px;background:#fafbfc;transition:.2s}
.nav-row:hover{border-color:var(--green)}
.nav-row .drag-handle{color:#ccc;cursor:grab;font-size:1rem}
.nav-row .nav-inputs{flex:1;display:flex;gap:10px}
.nav-row .nav-inputs input{flex:1;padding:7px 10px;border:1px solid #e8ecf0;border-radius:7px;
  font-size:.85rem;font-family:'Plus Jakarta Sans',sans-serif}
.nav-row .nav-inputs input:focus{outline:none;border-color:var(--green)}

/* CONTACT ITEMS */
.contact-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}

/* STATS ROW */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.stat-card{background:white;border-radius:var(--radius);padding:20px;box-shadow:var(--shadow);
  display:flex;align-items:center;gap:14px}
.stat-icon{width:46px;height:46px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.stat-info .num{font-family:'Outfit',sans-serif;font-size:1.6rem;font-weight:800;line-height:1}
.stat-info .lbl{font-size:.75rem;color:#888;margin-top:3px}

/* COLOR PICKER */
.color-row{display:flex;align-items:center;gap:10px}
.color-row input[type=color]{width:42px;height:36px;border:2px solid #e8ecf0;border-radius:8px;cursor:pointer;padding:2px}
.color-row span{font-size:.85rem;color:#666;font-family:monospace}

/* SECTION TABS */
.section-tabs{display:flex;gap:4px;margin-bottom:24px;background:white;padding:6px;
  border-radius:12px;box-shadow:var(--shadow);width:fit-content}
.tab-btn{padding:9px 20px;border:none;border-radius:9px;font-size:.85rem;font-weight:600;
  cursor:pointer;transition:.2s;font-family:'Plus Jakarta Sans',sans-serif;color:#666;background:transparent}
.tab-btn.active{background:var(--green);color:white;box-shadow:0 3px 10px rgba(0,104,55,.3)}
.tab-btn:hover:not(.active){background:#f0f4f8}
.tab-pane{display:none}
.tab-pane.active{display:block}

/* IMAGE UPLOAD */
.img-upload-area{border:2px dashed #d1d5db;border-radius:12px;padding:30px;text-align:center;
  cursor:pointer;transition:.2s;background:#fafbfc;position:relative}
.img-upload-area:hover{border-color:var(--green);background:#f0fdf4}
.img-upload-area .preview{width:100%;max-height:200px;object-fit:cover;border-radius:8px;margin-bottom:10px;display:none}
.img-upload-area input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer}
.img-upload-area .placeholder i{font-size:2rem;color:#d1d5db;display:block;margin-bottom:8px}
.img-upload-area .placeholder p{font-size:.85rem;color:#888}
.img-upload-area .placeholder small{font-size:.75rem;color:#bbb}

/* RESPONSIVE */
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .main{margin-left:0}
  .grid-2,.grid-3,.stats-row,.contact-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- LOGIN SCREEN -->
<div id="login-screen">
  <div class="login-card">
    <div class="login-logo">
      <img src="../logo-smp.jpg" alt="Logo SMP">
      <h2>CMS ADMIN PANEL</h2>
      <p>SMP R Rahmat Balongbendo</p>
    </div>
    <div class="form-group">
      <label>Username</label>
      <div class="input-wrap">
        <i class="fas fa-user"></i>
        <input type="text" id="inp-user" placeholder="Masukkan username" autocomplete="username">
      </div>
    </div>
    <div class="form-group">
      <label>Password</label>
      <div class="input-wrap">
        <i class="fas fa-lock"></i>
        <input type="password" id="inp-pass" placeholder="Masukkan password" autocomplete="current-password">
      </div>
    </div>
    <button class="btn-login" onclick="doLogin()"><i class="fas fa-sign-in-alt me-2"></i> MASUK</button>
    <div class="login-error" id="login-error"></div>
  </div>
</div>

<!-- MAIN APP -->
<div id="app">
  <div class="toast-container" id="toast-container"></div>

  <!-- SIDEBAR -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="logo-row">
        <img src="../logo-smp.jpg" alt="Logo">
        <div class="brand">
          <h3>SMP R Rahmat</h3>
          <p>Admin Panel v2.0</p>
        </div>
      </div>
    </div>

    <div class="nav-section">Menu Utama</div>
    <div class="nav-item active" onclick="showPage('dashboard')"><i class="fas fa-gauge-high"></i> Dashboard</div>
    <div class="nav-section">Konten Halaman</div>
    <div class="nav-item" onclick="showPage('hero')"><i class="fas fa-image"></i> Hero / Banner</div>
    <div class="nav-item" onclick="showPage('navbar')"><i class="fas fa-bars"></i> Navigasi</div>
    <div class="nav-item" onclick="showPage('features')"><i class="fas fa-star"></i> Keunggulan Sekolah</div>
    <div class="nav-item" onclick="showPage('ppdb')"><i class="fas fa-file-pen"></i> PPDB & Formulir</div>
    <div class="nav-item" onclick="showPage('contact')"><i class="fas fa-address-card"></i> Kontak & Footer</div>
    <div class="nav-item" onclick="showPage('social')"><i class="fas fa-share-nodes"></i> Media Sosial</div>
    <div class="nav-section">Pengaturan</div>
    <div class="nav-item" onclick="showPage('seo')"><i class="fas fa-magnifying-glass-chart"></i> SEO & Meta</div>
    <div class="nav-item" onclick="showPage('appearance')"><i class="fas fa-palette"></i> Tampilan & Warna</div>

    <div class="sidebar-footer">
      <div class="user-badge">
        <div class="avatar" id="user-avatar">A</div>
        <div class="info">
          <h4 id="user-name">Admin</h4>
          <p id="user-role">Administrator</p>
        </div>
      </div>
      <button class="btn-logout" onclick="doLogout()"><i class="fas fa-right-from-bracket"></i> Keluar</button>
    </div>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main">
    <div class="topbar">
      <h1 id="page-title">Dashboard</h1>
      <div class="topbar-actions">
        <a href="../index.html" target="_blank" class="btn-preview"><i class="fas fa-eye"></i> Lihat Website</a>
        <button onclick="doLogout()" style="display:flex;align-items:center;gap:7px;padding:9px 16px;background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;border-radius:8px;font-size:.85rem;font-weight:700;cursor:pointer;font-family:inherit;transition:.2s" onmouseover="this.style.background='#dc2626';this.style.color='white'" onmouseout="this.style.background='#fee2e2';this.style.color='#dc2626'"><i class="fas fa-right-from-bracket"></i> Keluar</button>
      </div>
    </div>

    <div class="content" id="page-content">
      <!-- Pages will be injected here -->
    </div>
  </div>
</div>

<script>
// ===== STATE =====
let cfg = {};
let currentPage = 'dashboard';

// ===== AUTH =====
async function doLogin() {
  const user = document.getElementById('inp-user').value;
  const pass = document.getElementById('inp-pass').value;
  const btn = document.querySelector('.btn-login');
  const errEl = document.getElementById('login-error');
  
  if (!user || !pass) { showLoginError('Isi username dan password!'); return; }
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memeriksa...';
  btn.disabled = true;

  try {
    const res = await fetch('../api/auth.php?action=login', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({user, pass})
    });
    const data = await res.json();
    if (data.success) {
      document.getElementById('login-screen').style.display = 'none';
      document.getElementById('app').style.display = 'flex';
      document.getElementById('user-name').textContent = user;
      document.getElementById('user-avatar').textContent = user[0].toUpperCase();
      document.getElementById('user-role').textContent = data.role === 'superadmin' ? 'Super Admin' : 'Admin';
      await loadConfig();
      showPage('dashboard');
    } else {
      showLoginError(data.message || 'Username atau password salah!');
    }
  } catch(e) { showLoginError('Gagal terhubung ke server.'); }
  btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> MASUK';
  btn.disabled = false;
}

function showLoginError(msg) {
  const el = document.getElementById('login-error');
  el.textContent = msg; el.style.display = 'block';
  setTimeout(() => el.style.display = 'none', 4000);
}

async function doLogout() {
  await fetch('../api/auth.php?action=logout');
  location.reload();
}

// ===== CONFIG =====
async function loadConfig() {
  const res = await fetch('../api/admin_api.php');
  cfg = await res.json();
  if (!cfg.pages) cfg.pages = {};
  if (!cfg.pages.smp) cfg.pages.smp = {};
  if (!cfg.global) cfg.global = {};
}

async function saveConfig() {
  const res = await fetch('../api/admin_api.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify(cfg)
  });
  const data = await res.json();
  if (data.success) showToast('✅ Perubahan berhasil disimpan!', 'success');
  else showToast('❌ Gagal menyimpan: ' + (data.message || 'Error'), 'error');
}

// ===== TOAST =====
function showToast(msg, type='success') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<span class="toast-icon">${type==='success'?'<i class="fas fa-circle-check"></i>':'<i class="fas fa-circle-xmark"></i>'}</span>${msg}`;
  c.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

// ===== NAVIGATION =====
const pageTitles = {
  dashboard:'Dashboard', hero:'Hero / Banner', navbar:'Navigasi & Menu',
  features:'Keunggulan Sekolah', ppdb:'PPDB & Formulir', contact:'Kontak & Footer',
  social:'Media Sosial', seo:'SEO & Meta Tags', appearance:'Tampilan & Warna'
};

function showPage(name) {
  currentPage = name;
  document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(el => {
    if (el.getAttribute('onclick') === `showPage('${name}')`) el.classList.add('active');
  });
  document.getElementById('page-title').textContent = pageTitles[name] || name;
  const content = document.getElementById('page-content');
  content.innerHTML = '';
  const fn = pages[name];
  if (fn) content.innerHTML = fn();
  bindPageEvents(name);
}

// ===== PAGE RENDERERS =====
const pages = {};

// DASHBOARD
pages.dashboard = () => {
  const smp = cfg.pages?.smp || {};
  return `
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon" style="background:#d1fae5;color:#065f46"><i class="fas fa-heading"></i></div>
      <div class="stat-info"><div class="num">1</div><div class="lbl">Hero Section</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#dbeafe;color:#1e40af"><i class="fas fa-star"></i></div>
      <div class="stat-info"><div class="num">${(smp.features||[]).length||3}</div><div class="lbl">Keunggulan</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#fef3c7;color:#92400e"><i class="fas fa-bars"></i></div>
      <div class="stat-info"><div class="num">${(smp.navbar||cfg.pages?.smp?.navbar||[]).length||5}</div><div class="lbl">Menu Navigasi</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#fce7f3;color:#9d174d"><i class="fas fa-file-pen"></i></div>
      <div class="stat-info"><div class="num">1</div><div class="lbl">Form PPDB</div></div>
    </div>
  </div>

  <div class="grid-2">
    <div class="card">
      <div class="card-header">
        <div class="icon" style="background:#d1fae5;color:#065f46"><i class="fas fa-gauge-high"></i></div>
        <div><h3>Selamat Datang!</h3><p>CMS Admin Panel SMP R Rahmat</p></div>
      </div>
      <div class="card-body">
        <p style="color:#555;line-height:1.7;font-size:.9rem">Anda dapat mengedit <strong>semua konten website</strong> menggunakan panel ini tanpa perlu menyentuh kode. Pilih menu di sidebar untuk mulai mengedit.</p>
        <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px">
          ${[
            ['hero','fas fa-image','Edit judul & subtitle hero banner'],
            ['features','fas fa-star','Kelola kartu keunggulan sekolah'],
            ['ppdb','fas fa-file-pen','Edit form & info PPDB'],
            ['contact','fas fa-address-card','Update alamat & kontak sekolah']
          ].map(([p,ic,lb]) => `<div onclick="showPage('${p}')" style="display:flex;align-items:center;gap:12px;padding:11px 14px;
            background:#f8fafc;border-radius:9px;cursor:pointer;border:1px solid #e8ecf0;transition:.2s"
            onmouseover="this.style.background='#e8f5e9';this.style.borderColor='var(--green)'"
            onmouseout="this.style.background='#f8fafc';this.style.borderColor='#e8ecf0'">
            <i class="${ic}" style="color:var(--green);width:18px"></i>
            <span style="font-size:.87rem;font-weight:500">${lb}</span>
            <i class="fas fa-chevron-right" style="margin-left:auto;color:#ccc;font-size:.75rem"></i>
          </div>`).join('')}
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="icon" style="background:#dbeafe;color:#1e40af"><i class="fas fa-circle-info"></i></div>
        <div><h3>Info Website</h3><p>Data terkini dari konfigurasi</p></div>
      </div>
      <div class="card-body">
        <table style="width:100%;font-size:.85rem;border-collapse:collapse">
          ${[
            ['Nama Sekolah','SMP R Rahmat Balongbendo'],
            ['NPSN','20501766'],
            ['Akreditasi','A'],
            ['Hero Title', smp.hero_title || '(belum diset)'],
            ['Status','<span class="badge badge-green"><i class="fas fa-circle" style="font-size:.5rem"></i> Aktif</span>'],
            ['URL Lokal','<code style="font-size:.75rem;background:#f0f4f8;padding:2px 6px;border-radius:4px">localhost:8080/web_rr/smp</code>'],
          ].map(([k,v]) => `<tr style="border-bottom:1px solid #f0f4f8">
            <td style="padding:9px 6px;color:#888;white-space:nowrap">${k}</td>
            <td style="padding:9px 6px;font-weight:500">${v}</td>
          </tr>`).join('')}
        </table>
      </div>
    </div>
  </div>`;
};

// HERO
pages.hero = () => {
  const smp = cfg.pages?.smp || {};
  const badge = smp.hero_badge || 'PENERIMAAN SISWA BARU 2026/2027 DIBUKA!';
  const title = smp.hero_title || 'Generasi Cerdas,Berkarakter & Berprestasi';
  const desc = smp.hero_desc || '';
  const btn1 = smp.hero_btn1 || 'DAFTAR SEKARANG';
  const btn2 = smp.hero_btn2 || 'Profil Sekolah';

  return `
  <div class="card">
    <div class="card-header">
      <div class="icon" style="background:#d1fae5;color:#065f46"><i class="fas fa-image"></i></div>
      <div><h3>Hero / Banner Utama</h3><p>Bagian pertama yang dilihat pengunjung</p></div>
    </div>
    <div class="card-body">
      <div class="grid-2">
        <div>
          <div class="field-group">
            <label class="field-label">Teks Badge (Label kecil di atas judul)</label>
            <input class="field-input" id="hero-badge" value="${badge}" placeholder="Contoh: PPDB 2026/2027 DIBUKA!">
            <div class="field-hint">Badge berwarna kuning di atas judul utama</div>
          </div>
          <div class="field-group">
            <label class="field-label">Judul Utama (H1)</label>
            <textarea class="field-input" id="hero-title" rows="3" placeholder="Judul hero...">${title}</textarea>
            <div class="field-hint">Gunakan enter untuk baris baru. Tampil besar di tengah halaman.</div>
          </div>
          <div class="field-group">
            <label class="field-label">Deskripsi / Subtitle</label>
            <textarea class="field-input" id="hero-desc" rows="4" placeholder="Deskripsi singkat sekolah...">${desc}</textarea>
          </div>
          <div class="grid-2">
            <div class="field-group">
              <label class="field-label">Tombol Utama (Kuning)</label>
              <input class="field-input" id="hero-btn1" value="${btn1}">
            </div>
            <div class="field-group">
              <label class="field-label">Tombol Kedua (Outline)</label>
              <input class="field-input" id="hero-btn2" value="${btn2}">
            </div>
          </div>
        </div>
        <div>
          <div class="field-group">
            <label class="field-label">Background Image Hero</label>
            <div class="img-upload-area" onclick="document.getElementById('hero-img-file').click()">
              <img id="hero-img-preview" src="${smp.hero_bg || '../bgfull_smp.jpeg'}" class="preview" style="display:block">
              <input type="file" id="hero-img-file" accept="image/*" onchange="previewAndUpload(this,'hero-img-preview','hero-bg-val')">
              <div class="placeholder" id="hero-img-placeholder" style="display:none">
                <i class="fas fa-cloud-arrow-up"></i>
                <p>Klik untuk upload gambar</p>
                <small>JPG, PNG, WEBP — Max 5MB</small>
              </div>
            </div>
            <input type="hidden" id="hero-bg-val" value="${smp.hero_bg || ''}">
            <div class="field-hint">Gambar latar belakang seluruh halaman</div>
          </div>
          <div class="field-group">
            <label class="field-label">Preview Tampilan</label>
            <div style="background:linear-gradient(rgba(0,50,0,.65),rgba(0,50,0,.55)),url('${smp.hero_bg || '../bgfull_smp.jpeg'}') center/cover;
              border-radius:12px;padding:30px 20px;text-align:center;color:white" id="hero-preview-box">
              <span style="background:rgba(243,156,18,.9);color:#111;padding:4px 14px;border-radius:20px;font-size:.72rem;font-weight:700">${badge}</span>
              <h2 style="font-size:1.3rem;font-weight:800;margin:10px 0 6px;line-height:1.3">${title.replace(/,/g,'<br>')}</h2>
              <p style="font-size:.78rem;opacity:.9">${desc.substring(0,80)}...</p>
            </div>
          </div>
        </div>
      </div>
      <hr style="border:none;border-top:1px solid #f0f4f8;margin:20px 0">
      <button class="btn-save" onclick="saveHero()"><i class="fas fa-floppy-disk"></i> Simpan Hero</button>
    </div>
  </div>`;
};

// NAVBAR
pages.navbar = () => {
  const navItems = cfg.pages?.smp?.navbar || [];
  const navHTML = navItems.map((item, i) => `
    <div class="nav-row" id="nav-row-${i}">
      <i class="fas fa-grip-vertical drag-handle"></i>
      <div class="nav-inputs">
        <input placeholder="Teks Menu" value="${item.text || ''}" onchange="updateNav(${i},'text',this.value)">
        <input placeholder="Link (#about, URL)" value="${item.link || ''}" onchange="updateNav(${i},'link',this.value)">
        <input placeholder="Icon (fas fa-home)" value="${item.icon || ''}" style="max-width:160px" onchange="updateNav(${i},'icon',this.value)">
      </div>
      <label style="display:flex;align-items:center;gap:5px;font-size:.78rem;white-space:nowrap;cursor:pointer">
        <input type="checkbox" ${item.class && item.class.includes('btn-ppdb') ? 'checked' : ''} onchange="updateNav(${i},'isPpdb',this.checked)"> PPDB Style
      </label>
      <button class="btn-remove" onclick="removeNav(${i})"><i class="fas fa-trash"></i></button>
    </div>`).join('');

  return `
  <div class="card">
    <div class="card-header">
      <div class="icon" style="background:#fef3c7;color:#92400e"><i class="fas fa-bars"></i></div>
      <div><h3>Menu Navigasi</h3><p>Kelola item menu di navbar</p></div>
    </div>
    <div class="card-body">
      <p style="font-size:.85rem;color:#666;margin-bottom:16px">
        <i class="fas fa-info-circle text-primary me-1"></i>
        Isi <strong>Link</strong> dengan <code>#section</code> untuk anchor, atau URL lengkap untuk halaman lain.
      </p>
      <div id="nav-list">${navHTML}</div>
      <div style="display:flex;gap:10px;margin-top:16px">
        <button class="btn-save btn-add" onclick="addNav()"><i class="fas fa-plus"></i> Tambah Menu</button>
        <button class="btn-save" onclick="saveNavbar()"><i class="fas fa-floppy-disk"></i> Simpan Navigasi</button>
      </div>
    </div>
  </div>`;
};

// FEATURES
pages.features = () => {
  const features = cfg.pages?.smp?.features || [
    {icon:'fas fa-quran', title:'Tahfidz & Diniyah', desc:'Program unggulan tahfidz Juz 30 dan materi keagamaan mendalam untuk membentuk karakter Islami.'},
    {icon:'fas fa-laptop-code', title:'Berbasis Teknologi', desc:'Pembelajaran modern didukung laboratorium komputer, multimedia, dan akses internet.'},
    {icon:'fas fa-trophy', title:'Ekstrakurikuler Juara', desc:'Beragam kegiatan ekstrakurikuler dari Pramuka, Pagar Nusa, Banjari, hingga Olahraga.'}
  ];

  const itemsHTML = features.map((f, i) => `
    <div class="feature-item">
      <div class="item-number">${i+1}</div>
      <button class="btn-remove" onclick="removeFeature(${i})"><i class="fas fa-trash"></i></button>
      <div class="field-group" style="margin-top:8px">
        <label class="field-label">Icon (Font Awesome class)</label>
        <div style="display:flex;gap:8px;align-items:center">
          <input class="field-input" value="${f.icon || ''}" placeholder="fas fa-quran" style="flex:1"
            onchange="updateFeature(${i},'icon',this.value)" id="feat-icon-${i}">
          <div style="width:38px;height:38px;background:var(--green-light);border-radius:9px;
            display:flex;align-items:center;justify-content:center;color:var(--green)">
            <i class="${f.icon || 'fas fa-star'}" id="feat-icon-prev-${i}"></i>
          </div>
        </div>
        <div class="field-hint">Cari icon di <a href="https://fontawesome.com/icons" target="_blank" style="color:var(--green)">fontawesome.com/icons</a></div>
      </div>
      <div class="field-group">
        <label class="field-label">Judul Kartu</label>
        <input class="field-input" value="${f.title || ''}" placeholder="Nama keunggulan..." onchange="updateFeature(${i},'title',this.value)">
      </div>
      <div class="field-group" style="margin-bottom:0">
        <label class="field-label">Deskripsi</label>
        <textarea class="field-input" rows="3" placeholder="Penjelasan singkat..." onchange="updateFeature(${i},'desc',this.value)">${f.desc || ''}</textarea>
      </div>
    </div>`).join('');

  return `
  <div class="card">
    <div class="card-header">
      <div class="icon" style="background:#d1fae5;color:#065f46"><i class="fas fa-star"></i></div>
      <div><h3>Keunggulan Sekolah</h3><p>Kartu-kartu keunggulan di section "Kenapa Memilih Kami"</p></div>
    </div>
    <div class="card-body">
      <div class="field-group">
        <label class="field-label">Judul Section</label>
        <input class="field-input" id="feat-section-title" value="${cfg.pages?.smp?.features_title || 'Keunggulan SMP R Rahmat'}" placeholder="Judul section keunggulan">
      </div>
      <div class="field-group">
        <label class="field-label">Sub-judul Section</label>
        <input class="field-input" id="feat-section-sub" value="${cfg.pages?.smp?.features_sub || 'Kenapa Memilih Kami?'}" placeholder="Label kecil di atas judul">
      </div>
      <hr style="border:none;border-top:1px solid #f0f4f8;margin:16px 0">
      <div class="feature-row" id="features-list">${itemsHTML}</div>
      <div style="display:flex;gap:10px;margin-top:20px">
        <button class="btn-save btn-add" onclick="addFeature()"><i class="fas fa-plus"></i> Tambah Kartu</button>
        <button class="btn-save" onclick="saveFeatures()"><i class="fas fa-floppy-disk"></i> Simpan Keunggulan</button>
      </div>
    </div>
  </div>`;
};

// PPDB
pages.ppdb = () => {
  const smp = cfg.pages?.smp || {};
  return `
  <div class="card">
    <div class="card-header">
      <div class="icon" style="background:#fce7f3;color:#9d174d"><i class="fas fa-file-pen"></i></div>
      <div><h3>PPDB & Formulir Pendaftaran</h3><p>Pengaturan halaman PPDB dan tombol WhatsApp</p></div>
    </div>
    <div class="card-body">
      <div class="grid-2">
        <div>
          <div class="field-group">
            <label class="field-label">Judul Form PPDB</label>
            <input class="field-input" id="ppdb-title" value="${smp.ppdb_title || 'Formulir Pendaftaran Siswa Baru'}" placeholder="Judul form">
          </div>
          <div class="field-group">
            <label class="field-label">Tahun Ajaran</label>
            <input class="field-input" id="ppdb-tahun" value="${smp.ppdb_tahun || '2026/2027'}" placeholder="2026/2027">
          </div>
          <div class="field-group">
            <label class="field-label">Teks Penjelasan Form</label>
            <textarea class="field-input" id="ppdb-desc" rows="3" placeholder="Instruksi pengisian...">${smp.ppdb_desc || 'Silakan isi data awal berikut untuk terhubung langsung dengan Panitia PPDB kami via WhatsApp.'}</textarea>
          </div>
          <div class="field-group">
            <label class="field-label">Nomor WhatsApp Admin PPDB</label>
            <div style="display:flex;align-items:center;gap:8px">
              <span style="padding:10px 12px;background:#d1fae5;border-radius:8px;color:var(--green)"><i class="fab fa-whatsapp"></i></span>
              <input class="field-input" id="ppdb-wa" value="${smp.ppdb_wa || '6285706673560'}" placeholder="628...">
            </div>
            <div class="field-hint">Format internasional tanpa + (misal: 6285706673560)</div>
          </div>
          <div class="field-group">
            <label class="field-label">Label Tombol Kirim</label>
            <input class="field-input" id="ppdb-btn-label" value="${smp.ppdb_btn_label || 'KIRIM DATA PENDAFTARAN'}" placeholder="KIRIM DATA PENDAFTARAN">
          </div>
        </div>
        <div>
          <div class="field-group">
            <label class="field-label">Badge Status PPDB (di Hero)</label>
            <input class="field-input" id="ppdb-badge" value="${smp.hero_badge || 'PENERIMAAN SISWA BARU 2026/2027 DIBUKA!'}" placeholder="Status PPDB...">
            <div class="field-hint">Muncul sebagai label kuning di hero section</div>
          </div>
          <div class="field-group">
            <label class="field-label">Status Pembukaan PPDB</label>
            <select class="field-input" id="ppdb-status">
              <option value="open" ${(smp.ppdb_status||'open')==='open'?'selected':''}>🟢 PPDB Dibuka</option>
              <option value="soon" ${smp.ppdb_status==='soon'?'selected':''}>🟡 Segera Dibuka</option>
              <option value="closed" ${smp.ppdb_status==='closed'?'selected':''}>🔴 PPDB Ditutup</option>
            </select>
          </div>
          <div style="background:#f0fdf4;border-radius:10px;padding:16px;border:1px solid #bbf7d0;margin-top:16px">
            <h4 style="font-size:.88rem;font-weight:700;color:var(--green);margin-bottom:10px"><i class="fab fa-whatsapp me-2"></i>Preview Pesan WA</h4>
            <div style="font-family:monospace;font-size:.78rem;color:#444;line-height:1.8">
              Halo Admin PPDB SMP R Rahmat,<br>
              Saya ingin mendaftarkan calon siswa...<br>
              <strong>Nama Siswa:</strong> [diisi user]<br>
              <strong>Asal Sekolah:</strong> [diisi user]<br>
              <strong>Nama Ortu:</strong> [diisi user]<br>
              <strong>Nomor HP:</strong> [diisi user]
            </div>
          </div>
        </div>
      </div>
      <hr style="border:none;border-top:1px solid #f0f4f8;margin:20px 0">
      <button class="btn-save" onclick="savePPDB()"><i class="fas fa-floppy-disk"></i> Simpan Pengaturan PPDB</button>
    </div>
  </div>`;
};

// CONTACT
pages.contact = () => {
  const smp = cfg.pages?.smp || {};
  const year = new Date().getFullYear();
  return `
  <div class="card">
    <div class="card-header">
      <div class="icon" style="background:#ede9fe;color:#6d28d9"><i class="fas fa-address-card"></i></div>
      <div><h3>Kontak & Footer</h3><p>Informasi kontak dan teks footer</p></div>
    </div>
    <div class="card-body">
      <div class="contact-grid">
        <div class="field-group">
          <label class="field-label">Nama Sekolah (Footer)</label>
          <input class="field-input" id="contact-name" value="${smp.school_name || 'SMP R RAHMAT'}" placeholder="Nama sekolah">
        </div>
        <div class="field-group">
          <label class="field-label">Deskripsi Footer</label>
          <textarea class="field-input" id="contact-desc" rows="2" placeholder="Deskripsi singkat...">${smp.footer_desc || 'Mencetak generasi berprestasi, berakhlak mulia, dan siap menghadapi tantangan global.'}</textarea>
        </div>
        <div class="field-group">
          <label class="field-label"><i class="fas fa-map-marker-alt" style="color:var(--gold)"></i> Alamat</label>
          <input class="field-input" id="contact-address" value="${smp.address || 'Jl. Raya Balongbendo, Sidoarjo'}" placeholder="Alamat lengkap">
        </div>
        <div class="field-group">
          <label class="field-label"><i class="fas fa-phone" style="color:var(--gold)"></i> Telepon</label>
          <input class="field-input" id="contact-phone" value="${smp.phone || '(031) 8970323'}" placeholder="No. telepon">
        </div>
        <div class="field-group">
          <label class="field-label"><i class="fab fa-whatsapp" style="color:#25d366"></i> WhatsApp</label>
          <input class="field-input" id="contact-wa" value="${smp.whatsapp || '0857-0667-3560'}" placeholder="No. WhatsApp tampilan">
        </div>
        <div class="field-group">
          <label class="field-label"><i class="fas fa-envelope" style="color:var(--gold)"></i> Email</label>
          <input class="field-input" id="contact-email" value="${smp.email || 'adminsmp@radenrahmatbalongbendo.sch.id'}" placeholder="Alamat email">
        </div>
        <div class="field-group">
          <label class="field-label">Nomor WhatsApp (Link, format internasional)</label>
          <input class="field-input" id="contact-wa-link" value="${smp.whatsapp_link || '6285706673560'}" placeholder="628...">
          <div class="field-hint">Digunakan untuk link wa.me/... (tanpa +)</div>
        </div>
        <div class="field-group">
          <label class="field-label">Teks Copyright Footer</label>
          <input class="field-input" id="contact-copyright" value="${smp.copyright || `© ${year} SMP R RAHMAT BALONGBENDO. All Rights Reserved.`}" placeholder="Teks copyright">
        </div>
      </div>
      <hr style="border:none;border-top:1px solid #f0f4f8;margin:20px 0">
      <button class="btn-save" onclick="saveContact()"><i class="fas fa-floppy-disk"></i> Simpan Kontak & Footer</button>
    </div>
  </div>`;
};

// SOCIAL
pages.social = () => {
  const smp = cfg.pages?.smp || {};
  return `
  <div class="card">
    <div class="card-header">
      <div class="icon" style="background:#fce7f3;color:#be185d"><i class="fas fa-share-nodes"></i></div>
      <div><h3>Media Sosial</h3><p>Link akun media sosial sekolah</p></div>
    </div>
    <div class="card-body">
      <div class="contact-grid">
        <div class="field-group">
          <label class="field-label"><i class="fab fa-instagram" style="color:#e4405f"></i> Instagram Handle</label>
          <div style="display:flex;align-items:center;gap:8px">
            <span style="padding:10px 12px;background:#fce7f3;border-radius:8px;color:#e4405f">@</span>
            <input class="field-input" id="social-ig" value="${smp.social_ig || 'smprrahmatbalongbendo'}" placeholder="username tanpa @">
          </div>
          <div class="field-hint">Tampil di section Kegiatan dan footer</div>
        </div>
        <div class="field-group">
          <label class="field-label"><i class="fab fa-facebook" style="color:#1877f2"></i> Facebook URL</label>
          <input class="field-input" id="social-fb" value="${smp.social_fb || 'https://facebook.com/'}" placeholder="https://facebook.com/...">
        </div>
        <div class="field-group">
          <label class="field-label"><i class="fab fa-youtube" style="color:#ff0000"></i> YouTube URL</label>
          <input class="field-input" id="social-yt" value="${smp.social_yt || 'https://youtube.com/'}" placeholder="https://youtube.com/...">
        </div>
        <div class="field-group">
          <label class="field-label"><i class="fab fa-tiktok"></i> TikTok URL (opsional)</label>
          <input class="field-input" id="social-tt" value="${smp.social_tt || ''}" placeholder="https://tiktok.com/@...">
        </div>
        <div class="field-group">
          <label class="field-label">Teks Deskripsi Section Instagram</label>
          <textarea class="field-input" id="social-ig-desc" rows="3">${smp.ig_desc || 'Ikuti keseruan dan update terbaru dari kegiatan siswa-siswi SMP R Rahmat melalui Instagram resmi kami.'}</textarea>
        </div>
        <div class="field-group">
          <label class="field-label">Judul Section Gallery/Instagram</label>
          <input class="field-input" id="social-ig-title" value="${smp.ig_title || 'Dokumentasi & Kegiatan'}" placeholder="Judul section">
        </div>
      </div>
      <hr style="border:none;border-top:1px solid #f0f4f8;margin:20px 0">
      <button class="btn-save" onclick="saveSocial()"><i class="fas fa-floppy-disk"></i> Simpan Media Sosial</button>
    </div>
  </div>`;
};

// SEO
pages.seo = () => {
  const smp = cfg.pages?.smp || {};
  return `
  <div class="card">
    <div class="card-header">
      <div class="icon" style="background:#dbeafe;color:#1e40af"><i class="fas fa-magnifying-glass-chart"></i></div>
      <div><h3>SEO & Meta Tags</h3><p>Pengaturan untuk mesin pencari (Google, dll)</p></div>
    </div>
    <div class="card-body">
      <div class="field-group">
        <label class="field-label">Judul Halaman (Title Tag)</label>
        <input class="field-input" id="seo-title" value="${smp.seo_title || 'SMP R RAHMAT BALONGBENDO | Official Website'}" placeholder="Judul untuk browser & Google">
        <div class="field-hint" id="seo-title-count">Max 60 karakter untuk SEO optimal. Sekarang: ${(smp.seo_title||'SMP R RAHMAT BALONGBENDO | Official Website').length} karakter</div>
      </div>
      <div class="field-group">
        <label class="field-label">Meta Description</label>
        <textarea class="field-input" id="seo-desc" rows="3" placeholder="Deskripsi halaman untuk Google...">${smp.seo_desc || 'Website Resmi SMP R Rahmat Balongbendo (NPSN 20501766). Generasi Cerdas, Berkarakter & Berprestasi berlandaskan Ahlussunnah Wal Jamaah.'}</textarea>
        <div class="field-hint">Max 160 karakter. Muncul di hasil pencarian Google.</div>
      </div>
      <div class="field-group">
        <label class="field-label">Keywords (Kata Kunci)</label>
        <textarea class="field-input" id="seo-keywords" rows="3" placeholder="kata kunci, dipisah koma...">${smp.seo_keywords || 'SMP R Rahmat, NPSN 20501766, SMP Balongbendo, SMP Sidoarjo'}</textarea>
        <div class="field-hint">Pisahkan dengan koma. Tidak terlalu berpengaruh untuk Google modern, tapi baik untuk mesin lain.</div>
      </div>
      <div class="grid-2">
        <div class="field-group">
          <label class="field-label">URL Canonical</label>
          <input class="field-input" id="seo-url" value="${smp.seo_url || 'https://smp.radenrahmatbalongbendo.sch.id/'}" placeholder="https://...">
        </div>
        <div class="field-group">
          <label class="field-label">Gambar OG (Open Graph / Share)</label>
          <input class="field-input" id="seo-og-image" value="${smp.seo_og_image || 'https://radenrahmatbalongbendo.sch.id/smp/logo-smp.jpg'}" placeholder="URL gambar untuk share medsos">
          <div class="field-hint">Gambar muncul saat link dibagikan di WA/medsos</div>
        </div>
      </div>
      <hr style="border:none;border-top:1px solid #f0f4f8;margin:20px 0">
      <button class="btn-save" onclick="saveSEO()"><i class="fas fa-floppy-disk"></i> Simpan Pengaturan SEO</button>
    </div>
  </div>`;
};

// APPEARANCE
pages.appearance = () => {
  const smp = cfg.pages?.smp || {};
  return `
  <div class="card">
    <div class="card-header">
      <div class="icon" style="background:#fef3c7;color:#b45309"><i class="fas fa-palette"></i></div>
      <div><h3>Tampilan & Warna</h3><p>Warna tema dan tampilan website</p></div>
    </div>
    <div class="card-body">
      <div class="grid-2">
        <div class="field-group">
          <label class="field-label">Warna Primer (Hijau Utama)</label>
          <div class="color-row">
            <input type="color" id="color-primary" value="${smp.color_primary || '#006837'}">
            <input class="field-input" id="color-primary-hex" value="${smp.color_primary || '#006837'}" style="width:120px">
            <span style="padding:8px 14px;border-radius:8px;color:white;font-size:.8rem;font-weight:700"
              id="color-primary-preview" style="background:${smp.color_primary || '#006837'}">Primer</span>
          </div>
          <div class="field-hint">Warna utama navbar, tombol, dan aksen</div>
        </div>
        <div class="field-group">
          <label class="field-label">Warna Aksen (Emas/Gold)</label>
          <div class="color-row">
            <input type="color" id="color-accent" value="${smp.color_accent || '#f39c12'}">
            <input class="field-input" id="color-accent-hex" value="${smp.color_accent || '#f39c12'}" style="width:120px">
            <span style="padding:8px 14px;border-radius:8px;color:white;font-size:.8rem;font-weight:700"
              id="color-accent-preview">Aksen</span>
          </div>
          <div class="field-hint">Warna badge, ikon kontak, dan elemen aksen</div>
        </div>
        <div class="field-group">
          <label class="field-label">Warna PPDB Button</label>
          <div class="color-row">
            <input type="color" id="color-ppdb" value="${smp.color_ppdb || '#ff5e00'}">
            <input class="field-input" id="color-ppdb-hex" value="${smp.color_ppdb || '#ff5e00'}" style="width:120px">
            <span style="padding:8px 14px;border-radius:8px;color:white;font-size:.8rem;font-weight:700"
              id="color-ppdb-preview">PPDB</span>
          </div>
        </div>
        <div class="field-group">
          <label class="field-label">Logo Sekolah</label>
          <div class="img-upload-area" onclick="document.getElementById('logo-file').click()">
            <img id="logo-preview" src="../logo-smp.jpg" class="preview" style="display:block;max-height:100px;width:auto;margin:0 auto">
            <input type="file" id="logo-file" accept="image/*" onchange="previewAndUpload(this,'logo-preview','logo-val')">
            <div id="logo-placeholder" style="display:none" class="placeholder">
              <i class="fas fa-cloud-arrow-up"></i><p>Upload logo baru</p>
            </div>
          </div>
          <input type="hidden" id="logo-val" value="${smp.logo || '../logo-smp.jpg'}">
        </div>
      </div>
      <hr style="border:none;border-top:1px solid #f0f4f8;margin:20px 0">
      <button class="btn-save" onclick="saveAppearance()"><i class="fas fa-floppy-disk"></i> Simpan Tampilan</button>
    </div>
  </div>`;
};

// ===== BIND EVENTS =====
function bindPageEvents(name) {
  if (name === 'seo') {
    const inp = document.getElementById('seo-title');
    if (inp) inp.addEventListener('input', function() {
      document.getElementById('seo-title-count').textContent = `Max 60 karakter untuk SEO optimal. Sekarang: ${this.value.length} karakter`;
    });
  }
  if (name === 'appearance') {
    ['primary','accent','ppdb'].forEach(key => {
      const picker = document.getElementById(`color-${key}`);
      const hex = document.getElementById(`color-${key}-hex`);
      const prev = document.getElementById(`color-${key}-preview`);
      if (picker && hex && prev) {
        const sync = (val) => {
          hex.value = val; prev.style.background = val;
        };
        picker.addEventListener('input', e => { hex.value = e.target.value; prev.style.background = e.target.value; });
        hex.addEventListener('input', e => { if (/^#[0-9a-fA-F]{6}$/.test(e.target.value)) picker.value = e.target.value; prev.style.background = e.target.value; });
        sync(picker.value);
      }
    });
  }
  if (name === 'features') {
    // Live icon preview
    document.querySelectorAll('[id^="feat-icon-"]').forEach(inp => {
      const idx = inp.id.split('-').pop();
      inp.addEventListener('input', e => {
        const prev = document.getElementById(`feat-icon-prev-${idx}`);
        if (prev) prev.className = e.target.value;
      });
    });
  }
}

// ===== SAVE FUNCTIONS =====
function ensureSmp() { if (!cfg.pages) cfg.pages = {}; if (!cfg.pages.smp) cfg.pages.smp = {}; return cfg.pages.smp; }

async function saveHero() {
  const smp = ensureSmp();
  smp.hero_badge = document.getElementById('hero-badge').value;
  smp.hero_title = document.getElementById('hero-title').value;
  smp.hero_desc = document.getElementById('hero-desc').value;
  smp.hero_btn1 = document.getElementById('hero-btn1').value;
  smp.hero_btn2 = document.getElementById('hero-btn2').value;
  const bg = document.getElementById('hero-bg-val').value;
  if (bg) smp.hero_bg = bg;
  await saveConfig();
}

function updateNav(i, key, val) {
  const smp = ensureSmp();
  if (!smp.navbar) smp.navbar = JSON.parse(JSON.stringify(cfg.pages?.smp?.navbar || []));
  if (!smp.navbar[i]) smp.navbar[i] = {};
  if (key === 'isPpdb') {
    smp.navbar[i].class = val ? 'btn-ppdb-nav' : '';
  } else { smp.navbar[i][key] = val; }
}

function addNav() {
  const smp = ensureSmp();
  if (!smp.navbar) smp.navbar = JSON.parse(JSON.stringify(cfg.pages?.smp?.navbar || []));
  smp.navbar.push({text:'Menu Baru', link:'#'});
  showPage('navbar');
}

function removeNav(i) {
  const smp = ensureSmp();
  if (!smp.navbar) smp.navbar = JSON.parse(JSON.stringify(cfg.pages?.smp?.navbar || []));
  smp.navbar.splice(i, 1);
  showPage('navbar');
}

async function saveNavbar() {
  const smp = ensureSmp();
  if (!smp.navbar) smp.navbar = JSON.parse(JSON.stringify(cfg.pages?.smp?.navbar || []));
  await saveConfig();
}

function updateFeature(i, key, val) {
  const smp = ensureSmp();
  if (!smp.features) smp.features = JSON.parse(JSON.stringify(cfg.pages?.smp?.features || []));
  if (!smp.features[i]) smp.features[i] = {};
  smp.features[i][key] = val;
}

function addFeature() {
  const smp = ensureSmp();
  if (!smp.features) smp.features = JSON.parse(JSON.stringify(cfg.pages?.smp?.features || []));
  smp.features.push({icon:'fas fa-star', title:'Keunggulan Baru', desc:'Deskripsi keunggulan...'});
  showPage('features');
}

function removeFeature(i) {
  const smp = ensureSmp();
  if (!smp.features) smp.features = JSON.parse(JSON.stringify(cfg.pages?.smp?.features || []));
  smp.features.splice(i, 1);
  showPage('features');
}

async function saveFeatures() {
  const smp = ensureSmp();
  smp.features_title = document.getElementById('feat-section-title').value;
  smp.features_sub = document.getElementById('feat-section-sub').value;
  if (!smp.features) smp.features = JSON.parse(JSON.stringify(cfg.pages?.smp?.features || []));
  await saveConfig();
}

async function savePPDB() {
  const smp = ensureSmp();
  smp.ppdb_title = document.getElementById('ppdb-title').value;
  smp.ppdb_tahun = document.getElementById('ppdb-tahun').value;
  smp.ppdb_desc = document.getElementById('ppdb-desc').value;
  smp.ppdb_wa = document.getElementById('ppdb-wa').value;
  smp.ppdb_btn_label = document.getElementById('ppdb-btn-label').value;
  smp.ppdb_status = document.getElementById('ppdb-status').value;
  smp.hero_badge = document.getElementById('ppdb-badge').value;
  await saveConfig();
}

async function saveContact() {
  const smp = ensureSmp();
  smp.school_name = document.getElementById('contact-name').value;
  smp.footer_desc = document.getElementById('contact-desc').value;
  smp.address = document.getElementById('contact-address').value;
  smp.phone = document.getElementById('contact-phone').value;
  smp.whatsapp = document.getElementById('contact-wa').value;
  smp.email = document.getElementById('contact-email').value;
  smp.whatsapp_link = document.getElementById('contact-wa-link').value;
  smp.copyright = document.getElementById('contact-copyright').value;
  await saveConfig();
}

async function saveSocial() {
  const smp = ensureSmp();
  smp.social_ig = document.getElementById('social-ig').value;
  smp.social_fb = document.getElementById('social-fb').value;
  smp.social_yt = document.getElementById('social-yt').value;
  smp.social_tt = document.getElementById('social-tt').value;
  smp.ig_desc = document.getElementById('social-ig-desc').value;
  smp.ig_title = document.getElementById('social-ig-title').value;
  await saveConfig();
}

async function saveSEO() {
  const smp = ensureSmp();
  smp.seo_title = document.getElementById('seo-title').value;
  smp.seo_desc = document.getElementById('seo-desc').value;
  smp.seo_keywords = document.getElementById('seo-keywords').value;
  smp.seo_url = document.getElementById('seo-url').value;
  smp.seo_og_image = document.getElementById('seo-og-image').value;
  await saveConfig();
}

async function saveAppearance() {
  const smp = ensureSmp();
  smp.color_primary = document.getElementById('color-primary').value;
  smp.color_accent = document.getElementById('color-accent').value;
  smp.color_ppdb = document.getElementById('color-ppdb').value;
  const logoVal = document.getElementById('logo-val').value;
  if (logoVal) smp.logo = logoVal;
  await saveConfig();
}

// ===== FILE UPLOAD =====
async function previewAndUpload(input, previewId, hiddenId) {
  const file = input.files[0]; if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const prev = document.getElementById(previewId);
    if (prev) { prev.src = e.target.result; prev.style.display = 'block'; }
  };
  reader.readAsDataURL(file);
  
  const form = new FormData();
  form.append('file', file);
  try {
    const res = await fetch('../api/admin_api.php', { method:'POST', body: form });
    const data = await res.json();
    if (data.success && data.url) {
      const hidden = document.getElementById(hiddenId);
      if (hidden) hidden.value = '../' + data.url;
      showToast('✅ Gambar berhasil diupload!', 'success');
    } else { showToast('❌ Upload gagal: ' + (data.message || 'Error'), 'error'); }
  } catch(e) { showToast('❌ Gagal upload: ' + e.message, 'error'); }
}

// ===== INIT =====
document.getElementById('inp-pass').addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });
document.getElementById('inp-user').addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });

// Check if already logged in
(async () => {
  try {
    const res = await fetch('../api/auth.php?action=check');
    const data = await res.json();
    if (data.logged_in) {
      document.getElementById('login-screen').style.display = 'none';
      document.getElementById('app').style.display = 'flex';
      document.getElementById('user-name').textContent = data.user;
      document.getElementById('user-avatar').textContent = (data.user||'A')[0].toUpperCase();
      document.getElementById('user-role').textContent = data.role === 'superadmin' ? 'Super Admin' : 'Admin';
      await loadConfig();
      showPage('dashboard');
    }
  } catch(e) {}
})();
</script>
</body>
</html>
