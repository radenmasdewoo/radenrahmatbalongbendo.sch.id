&lt;?php
$newsJson =file_get_contents(__DIR__ . '/api/news.json');
$news = json_decode($newsJson, true);

header('Content-Type: text/html; charset=utf-8');
?&gt;
&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;title&gt;News API Test&lt;/title&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;h1&gt;News API Test&lt;/h1&gt;
    &lt;p&gt;Total news items: &lt;?php echo count($news); ?&gt;&lt;/p&gt;
    
    &lt;?php if (!empty($news)): ?&gt;
        &lt;h2&gt;First News Item:&lt;/h2&gt;
        &lt;p&gt;&lt;strong&gt;Title:&lt;/strong&gt; &lt;?php echo htmlspecialchars($news[0]['title']); ?&gt;&lt;/p&gt;
        &lt;p&gt;&lt;strong&gt;Image URL:&lt;/strong&gt; &lt;?php echo htmlspecialchars($news[0]['img']); ?&gt;&lt;/p&gt;
        &lt;img src="&lt;?php echo htmlspecialchars($news[0]['img']); ?&gt;" alt="Test" style="max-width: 300px;"&gt;
    &lt;?php endif; ?&gt;
    
    &lt;hr&gt;
    &lt;h2&gt;Full JSON:&lt;/h2&gt;
    &lt;pre&gt;&lt;?php echo htmlspecialchars($newsJson); ?&gt;&lt;/pre&gt;
&lt;/body&gt;
&lt;/html&gt;