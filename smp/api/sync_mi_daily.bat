@echo off
REM Sinkronisasi Instagram MI - Jalankan setiap hari jam 00:00
REM Jadwalkan via Task Scheduler Windows
REM Pastikan XAMPP Apache berjalan

curl -s "http://localhost:8080/api/insta_sync_mi.php?key=radenrahmat_sync_2026" >nul 2>&1
