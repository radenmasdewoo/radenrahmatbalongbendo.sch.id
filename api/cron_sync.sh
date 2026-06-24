#!/bin/bash
# ============================================================
# Central Cron Sync - Raden Rahmat Balongbendo
# Sinkronisasi Instagram feed setiap 1 jam via cron
# Mencakup: Portal (Root), MI, dan SMP
# Menggunakan PHP CLI langsung (bukan curl HTTP) untuk menghindari
# masalah PHP-FPM versi lama (7.2) yang tidak support arrow functions
# ============================================================

SYNC_KEY="88d455b94ed30553bdb349fe0e57c9de"
LOG_FILE="/mnt/webdata/htdocs/api/sync_log.txt"
MAX_LOG_LINES=1000
PHP_BIN="/usr/bin/php"

# Buat log file jika belum ada
touch "$LOG_FILE"
chmod 664 "$LOG_FILE"

# Fungsi log sederhana
log_msg() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Batasi ukuran log
if [ $(wc -l < "$LOG_FILE") -gt $MAX_LOG_LINES ]; then
    tail -n $MAX_LOG_LINES "$LOG_FILE" > "${LOG_FILE}.tmp" && mv "${LOG_FILE}.tmp" "$LOG_FILE"
fi

echo "============================================" >> "$LOG_FILE"
log_msg "MEMULAI SINKRONISASI GLOBAL"

# 1. PORTAL - SMP Account
log_msg "PORTAL (SMP Feed):"
$PHP_BIN -f /mnt/webdata/htdocs/api/insta_sync.php >> "$LOG_FILE" 2>&1
echo "" >> "$LOG_FILE"

# 2. PORTAL - MI Account
log_msg "PORTAL (MI Feed):"
$PHP_BIN -f /mnt/webdata/htdocs/api/insta_sync_mi.php >> "$LOG_FILE" 2>&1
echo "" >> "$LOG_FILE"

# 3. MI WEBSITE
log_msg "MI (miradenrahmatbalongbendo):"
$PHP_BIN -f /mnt/webdata/htdocs/mi/api/insta_sync_mi.php "key=${SYNC_KEY}" >> "$LOG_FILE" 2>&1
echo "" >> "$LOG_FILE"

# 4. SMP WEBSITE
log_msg "SMP (smprrahmatbalongbendo):"
$PHP_BIN -f /mnt/webdata/htdocs/smp/api/insta_sync.php "key=${SYNC_KEY}" >> "$LOG_FILE" 2>&1
echo "" >> "$LOG_FILE"

# 5. GENERATE BERITA.HTML CONTENT VIA GROQ API
log_msg "BERITA.HTML (Groq AI Auto-Generation):"
python3 /mnt/webdata/htdocs/sync_news.py >> "$LOG_FILE" 2>&1
echo "" >> "$LOG_FILE"

# 6. INJECT AI ARTICLES STATICALLY INTO BERITA.HTML
log_msg "BERITA.HTML (Gemini AI Static HTML Injection):"
$PHP_BIN /mnt/webdata/htdocs/inject_berita.php >> "$LOG_FILE" 2>&1
echo "" >> "$LOG_FILE"

log_msg "SEMUA SINKRONISASI SELESAI"
echo "============================================" >> "$LOG_FILE"
