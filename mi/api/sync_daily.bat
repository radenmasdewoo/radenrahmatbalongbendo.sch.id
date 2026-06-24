@echo off
REM Instagram News Sync - Daily Automation Script
REM This script runs the Instagram sync to update news.json

echo ========================================
echo Instagram News Sync Started
echo %date% %time%
echo ========================================

REM Change to API directory
cd /d "C:\xampp\htdocs\radenrahmat\api"

REM Ensure log file exists
if not exist "sync_log.txt" echo. > sync_log.txt

REM Run the sync using PHP CLI with full XAMPP path
echo Running sync...
C:\xampp\php\php.exe -r "echo file_get_contents('http://localhost:8080/api/insta_sync.php?key=radenrahmat_sync_2026');" >> sync_log.txt 2>&1
C:\xampp\php\php.exe -r "echo file_get_contents('http://localhost:8080/api/insta_sync_mi.php?key=radenrahmat_sync_2026');" >> sync_log.txt 2>&1

REM Log completion
echo. >> sync_log.txt
echo [%date% %time%] Sync completed >> sync_log.txt
echo ---------------------------------------- >> sync_log.txt

echo.
echo Sync completed. Check sync_log.txt for details.
echo ========================================
