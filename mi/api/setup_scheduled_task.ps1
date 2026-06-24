# Instagram News Sync - Windows Task Scheduler Setup
# This PowerShell script creates a scheduled task to run news sync every 15 minutes

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Setting up Instagram News Sync Task" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    pause
    exit 1
}

# Define task parameters
$taskName = "Instagram News Sync - Raden Rahmat"
$batchFile = "C:\xampp\htdocs\radenrahmat\api\sync_daily.bat"

# Check if batch file exists
if (-not (Test-Path $batchFile)) {
    Write-Host "ERROR: sync_daily.bat not found at: $batchFile" -ForegroundColor Red
    pause
    exit 1
}

# Remove existing task if it exists
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Write-Host "Removing existing task..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
}

# Create new scheduled task
Write-Host "Creating scheduled task (every 15 minutes)..." -ForegroundColor Green

$action = New-ScheduledTaskAction -Execute $batchFile

# Trigger: start once (segera) and repeat every 15 minutes
$startTime = (Get-Date).AddMinutes(1)
$trigger = New-ScheduledTaskTrigger `
    -Once `
    -At $startTime `
    -RepetitionInterval (New-TimeSpan -Minutes 15) `
    -RepetitionDuration (New-TimeSpan -Days 3650)
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName $taskName `
    -Action $action `
    -Trigger $trigger `
    -Principal $principal `
    -Settings $settings `
    -Description "Sinkronisasi berita dari Instagram setiap 15 menit"

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Task created successfully!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Task Name: $taskName" -ForegroundColor White
Write-Host "Schedule: Every 15 minutes (starting at 00:00)" -ForegroundColor White
Write-Host "Script: $batchFile" -ForegroundColor White
Write-Host ""
Write-Host "To view the task:" -ForegroundColor Yellow
Write-Host "  1. Open Task Scheduler (taskschd.msc)" -ForegroundColor White
Write-Host "  2. Look for '$taskName'" -ForegroundColor White
Write-Host ""
Write-Host "To test the task now:" -ForegroundColor Yellow
Write-Host "  Run the following command in PowerShell (as Admin):" -ForegroundColor White
Write-Host "  Start-ScheduledTask -TaskName '$taskName'" -ForegroundColor Cyan
Write-Host ""
Write-Host "Press any key to exit..." -ForegroundColor Gray
pause
