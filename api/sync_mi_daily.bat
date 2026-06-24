@echo off
REM ==============================================
REM  Sinkronisasi Instagram MI - Setiap 1 Jam
REM  Source: https://www.instagram.com/mir.rahmatbalongbendo/
REM  Target: C:\xampp\htdocs\radenrahmat\api\news_mi.json
REM ==============================================

set LOGFILE=C:\xampp\htdocs\radenrahmat\api\sync_log_mi.txt
set SYNC_URL=http://localhost:8080/api/insta_sync_mi.php?key=radenrahmat_sync_2026

REM Ambil timestamp
for /f "tokens=1-3 delims=/ " %%a in ('date /t') do set TANGGAL=%%a/%%b/%%c
for /f "tokens=1-2 delims=: " %%a in ('time /t') do set WAKTU=%%a:%%b

REM Jalankan sync dan simpan response
set TMPFILE=%TEMP%\mi_sync_result_%RANDOM%.tmp
curl -s "%SYNC_URL%" -o "%TMPFILE%" 2>>"%LOGFILE%"

REM Cek apakah response mengandung "success":true
findstr /C:"\"success\":true" "%TMPFILE%" >nul 2>&1
if %ERRORLEVEL%==0 (
    echo [%TANGGAL% %WAKTU%] [SUCCESS] Sync berhasil >> "%LOGFILE%"
) else (
    echo [%TANGGAL% %WAKTU%] [FAIL] Sync gagal >> "%LOGFILE%"
)

REM Tulis response ke log
if exist "%TMPFILE%" (
    echo   Response: >> "%LOGFILE%"
    type "%TMPFILE%" >> "%LOGFILE%"
    echo. >> "%LOGFILE%"
    del "%TMPFILE%" >nul 2>&1
)
echo ---------------------------------------- >> "%LOGFILE%"
