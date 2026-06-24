&lt;?php
/**
 * Instagram Sync Test & Manual Trigger
 * Halaman ini untuk testing dan monitoring sinkronisasi Instagram
 */

header('Content-Type: text/html; charset=utf-8');

$newsFile = __DIR__ . '/news.json';
$configFile = __DIR__ . '/insta_config.json';
$syncKey = 'radenrahmat_sync_2026';

// Load config
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$rssUrl = $config['rss_url'] ?? 'Not configured';

// Manual sync trigger
$syncResult = null;
if (isset($_GET['action']) &amp;&amp; $_GET['action'] === 'sync') {
    $syncUrl = "http://localhost:8080/radenrahmat/api/insta_sync.php?key=$syncKey";
    $syncResult = @file_get_contents($syncUrl);
}

// Load current news
$newsData = [];
if (file_exists($newsFile)) {
    $newsData = json_decode(file_get_contents($newsFile), true) ?: [];
}

$newsCount = count($newsData);
$lastModified = file_exists($newsFile) ? date('Y-m-d H:i:s', filemtime($newsFile)) : 'Never';

?&gt;
&lt;!DOCTYPE html&gt;
&lt;html lang="id"&gt;
&lt;head&gt;
    &lt;meta charset="UTF-8"&gt;
    &lt;meta name="viewport" content="width=device-width, initial-scale=1.0"&gt;
    &lt;title&gt;Instagram Sync Test - Raden Rahmat&lt;/title&gt;
    &lt;style&gt;
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #006837;
            margin-bottom: 10px;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #006837;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        .btn:hover {
            background: #004d29;
        }
        .btn-refresh {
            background: #f39c12;
        }
        .btn-refresh:hover {
            background: #d35400;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #006837;
            color: white;
        }
        .news-item {
            margin: 10px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #006837;
            border-radius: 5px;
        }
        .news-item img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 5px;
            margin: 10px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #272822;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 10px 0;
        }
    &lt;/style&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;div class="container"&gt;
        &lt;h1&gt;📰 Instagram News Sync - Test Panel&lt;/h1&gt;
        &lt;p&gt;Halaman ini digunakan untuk testing dan monitoring sinkronisasi berita dari Instagram.&lt;/p&gt;

        &lt;?php if ($syncResult): ?&gt;
            &lt;div class="status success"&gt;
                &lt;strong&gt;✅ Sync Triggered!&lt;/strong&gt;&lt;br&gt;
                Result: &lt;code&gt;&lt;?php echo htmlspecialchars($syncResult); ?&gt;&lt;/code&gt;
            &lt;/div&gt;
        &lt;?php endif; ?&gt;

        &lt;h2&gt;📊 Status&lt;/h2&gt;
        &lt;table&gt;
            &lt;tr&gt;
                &lt;th&gt;Parameter&lt;/th&gt;
                &lt;th&gt;Value&lt;/th&gt;
            &lt;/tr&gt;
            &lt;tr&gt;
                &lt;td&gt;RSS Feed URL&lt;/td&gt;
                &lt;td&gt;&lt;code&gt;&lt;?php echo htmlspecialchars($rssUrl); ?&gt;&lt;/code&gt;&lt;/td&gt;
            &lt;/tr&gt;
            &lt;tr&gt;
                &lt;td&gt;News Count&lt;/td&gt;
                &lt;td&gt;&lt;strong&gt;&lt;?php echo $newsCount; ?&gt;&lt;/strong&gt; items&lt;/td&gt;
            &lt;/tr&gt;
            &lt;tr&gt;
                &lt;td&gt;Last Updated&lt;/td&gt;
                &lt;td&gt;&lt;?php echo $lastModified; ?&gt;&lt;/td&gt;
            &lt;/tr&gt;
            &lt;tr&gt;
                &lt;td&gt;News File&lt;/td&gt;
                &lt;td&gt;&lt;code&gt;&lt;?php echo $newsFile; ?&gt;&lt;/code&gt;&lt;/td&gt;
            &lt;/tr&gt;
        &lt;/table&gt;

        &lt;div style="margin: 20px 0;"&gt;
            &lt;a href="?action=sync" class="btn"&gt;🔄 Run Sync Now&lt;/a&gt;
            &lt;a href="?" class="btn btn-refresh"&gt;♻️ Refresh Status&lt;/a&gt;
            &lt;a href="../index.html" class="btn" style="background: #95a5a6;"&gt;🏠 Back to Website&lt;/a&gt;
        &lt;/div&gt;

        &lt;h2&gt;📋 Current News Items&lt;/h2&gt;
        &lt;?php if (empty($newsData)): ?&gt;
            &lt;div class="status warning"&gt;
                &lt;strong&gt;⚠️ No news data found&lt;/strong&gt;&lt;br&gt;
                Click "Run Sync Now" to fetch from Instagram RSS feed.
            &lt;/div&gt;
        &lt;?php else: ?&gt;
            &lt;?php foreach ($newsData as $index =&gt; $item): ?&gt;
                &lt;div class="news-item"&gt;
                    &lt;strong&gt;#&lt;?php echo $index + 1; ?&gt;: &lt;?php echo htmlspecialchars($item['title'] ?? 'No title'); ?&gt;&lt;/strong&gt;&lt;br&gt;
                    &lt;small&gt;📅 &lt;?php echo htmlspecialchars($item['date'] ?? 'No date'); ?&gt; | 👤 &lt;?php echo htmlspecialchars($item['author'] ?? 'unknown'); ?&gt;&lt;/small&gt;&lt;br&gt;
                    &lt;?php if (!empty($item['img'])): ?&gt;
                        &lt;img src="&lt;?php echo htmlspecialchars($item['img']); ?&gt;" alt="News image" onerror="this.style.display='none'"&gt;&lt;br&gt;
                    &lt;?php endif; ?&gt;
                    &lt;p&gt;&lt;?php echo htmlspecialchars($item['excerpt'] ?? 'No excerpt'); ?&gt;&lt;/p&gt;
                    &lt;a href="&lt;?php echo htmlspecialchars($item['link'] ?? '#'); ?&gt;" target="_blank"&gt;🔗 View on Instagram&lt;/a&gt;
                &lt;/div&gt;
            &lt;?php endforeach; ?&gt;
        &lt;?php endif; ?&gt;

        &lt;h2&gt;🔧 Manual Sync via Command Line&lt;/h2&gt;
        &lt;div class="status info"&gt;
            &lt;p&gt;You can also trigger sync manually using command line:&lt;/p&gt;
            &lt;pre&gt;cd C:\xampp\htdocs\radenrahmat\api
C:\xampp\php\php.exe -r "echo file_get_contents('http://localhost:8080/radenrahmat/api/insta_sync.php?key=radenrahmat_sync_2026');"&lt;/pre&gt;
        &lt;/div&gt;

        &lt;h2&gt;⏰ Automatic Sync Setup&lt;/h2&gt;
        &lt;div class="status info"&gt;
            &lt;p&gt;&lt;strong&gt;To setup automatic daily sync at 00:00:&lt;/strong&gt;&lt;/p&gt;
            &lt;ol&gt;
                &lt;li&gt;Open PowerShell as Administrator&lt;/li&gt;
                &lt;li&gt;Navigate to: &lt;code&gt;cd C:\xampp\htdocs\radenrahmat\api&lt;/code&gt;&lt;/li&gt;
                &lt;li&gt;Run: &lt;code&gt;.\setup_scheduled_task.ps1&lt;/code&gt;&lt;/li&gt;
            &lt;/ol&gt;
        &lt;/div&gt;
    &lt;/div&gt;
&lt;/body&gt;
&lt;/html&gt;
