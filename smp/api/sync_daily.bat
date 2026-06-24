@echo off
REM Instagram News Sync - SMP - Automation Script
REM This script runs the Instagram sync to update smp/api/news.json

echo ========================================
echo Instagram News Sync SMP Started
echo %date% %time%
echo ========================================

REM Change to SMP API directory
cd /d "C:\xampp\htdocs\radenrahmat\smp\api"

REM Ensure log file exists
if not exist "sync_log_smp.txt" echo. > sync_log_smp.txt

REM Run the sync using PHP CLI with full XAMPP path
echo Running SMP sync...
C:\xampp\php\php.exe -r "echo file_get_contents('http://localhost:8080/radenrahmat/smp/api/insta_sync.php?key=radenrahmat_sync_2026');" >> sync_log_smp.txt 2>&1

REM Log completion
echo. >> sync_log_smp.txt
echo [%date% %time%] SMP sync completed >> sync_log_smp.txt
echo ---------------------------------------- >> sync_log_smp.txt

echo.
echo SMP sync completed. Check sync_log_smp.txt for details.
echo ========================================
