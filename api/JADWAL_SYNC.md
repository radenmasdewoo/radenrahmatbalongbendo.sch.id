# Jadwal Sinkronisasi Instagram

## Produksi (Linux Server)

**Setiap 6 jam:**
```cron
0 */6 * * * curl -s "https://radenrahmat.ct.ws/api/insta_sync.php?key=radenrahmat_sync_2026"
0 */6 * * * curl -s "https://radenrahmat.ct.ws/api/insta_sync_mi.php?key=radenrahmat_sync_2026"
```

**Atau sekali per hari jam 00:00:**
```cron
0 0 * * * curl -s "https://radenrahmat.ct.ws/api/insta_sync.php?key=radenrahmat_sync_2026"
0 0 * * * curl -s "https://radenrahmat.ct.ws/api/insta_sync_mi.php?key=radenrahmat_sync_2026"
```

## Localhost (Windows)

### Task Scheduler - MI Sync Per Hari Jam 00:00

1. Buka **Task Scheduler** (taskschd.msc)
2. Klik **Create Basic Task**
3. Name: `Instagram MI Sync`
4. Trigger: **Daily**, Start: `00:00:00`
5. Action: **Start a program**
6. Program: `C:\xampp\htdocs\radenrahmat\api\sync_mi_daily.bat`
7. Selesai

**Atau** jalankan di PowerShell (Admin):
```powershell
$action = New-ScheduledTaskAction -Execute "C:\xampp\htdocs\radenrahmat\api\sync_mi_daily.bat"
$trigger = New-ScheduledTaskTrigger -Daily -At "00:00"
Register-ScheduledTask -TaskName "Instagram MI Sync" -Action $action -Trigger $trigger
```

**Penting:** Pastikan XAMPP Apache berjalan saat jam 00:00, atau gunakan PHP CLI:
```bat
cd C:\xampp\htdocs\radenrahmat
php -r "file_get_contents('http://localhost/radenrahmat/api/insta_sync_mi.php?key=radenrahmat_sync_2026');"
```
