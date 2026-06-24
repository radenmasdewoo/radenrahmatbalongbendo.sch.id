<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$ROOT      = '/mnt/webdata/htdocs';
$NEWS_FILE = $ROOT . '/api/news.json';
$MI_FILE   = $ROOT . '/api/news_mi.json';

function read_json(string $path): array {
    if (!file_exists($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function extract_shortcode(string $link): string {
    if (preg_match('/instagram\.com\/(?:p|reel|tv)\/([A-Za-z0-9_\-]+)/i', $link, $m)) {
        return $m[1];
    }
    return '';
}

function get_all_api_keys(): array {
    global $ROOT;
    $tokenFile = $ROOT . '/API TOKEN.txt';
    if (!file_exists($tokenFile)) return [];
    
    $keys = [];
    $lines = file($tokenFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, 'AIzaSy') === 0) {
            $keys[] = ['provider' => 'gemini', 'key' => $line];
        } elseif (strpos($line, 'gsk_') === 0) {
            $keys[] = ['provider' => 'groq', 'key' => $line];
        } elseif (strpos($line, 'sk-or-') === 0) {
            $keys[] = ['provider' => 'openrouter', 'key' => $line];
        }
    }
    return $keys;
}

function log_progress(string $msg) {
    global $ROOT;
    file_put_contents($ROOT . '/api/ig_cache/generation_log.txt', date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

function call_ai_provider(array $apiKeyInfo, string $prompt): string {
    $provider = $apiKeyInfo['provider'];
    $key = $apiKeyInfo['key'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Increased timeout
    
    if ($provider === 'gemini') {
        // Use gemini-1.5-flash
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $key;
        $data = [
            "contents" => [["parts" => [["text" => $prompt]]]],
            "generationConfig" => ["temperature" => 0.7, "maxOutputTokens" => 1500] // More tokens
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $res = curl_exec($ch);
        curl_close($ch);
        
        $json = json_decode($res, true);
        return $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
    } elseif ($provider === 'groq') {
        $url = "https://api.groq.com/openai/v1/chat/completions";
        $data = [
            "model" => "llama-3.1-8b-instant",
            "messages" => [["role" => "user", "content" => $prompt]],
            "temperature" => 0.7,
            "max_tokens" => 1500
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $res = curl_exec($ch);
        curl_close($ch);
        
        $json = json_decode($res, true);
        return $json['choices'][0]['message']['content'] ?? '';
        
    } elseif ($provider === 'openrouter') {
        $url = "https://openrouter.ai/api/v1/chat/completions";
        $data = [
            "model" => "deepseek/deepseek-r1",
            "messages" => [["role" => "user", "content" => $prompt]],
            "temperature" => 0.7,
            "max_tokens" => 800
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $res = curl_exec($ch);
        curl_close($ch);
        
        $json = json_decode($res, true);
        return $json['choices'][0]['message']['content'] ?? '';
    }
    
    curl_close($ch);
    return '';
}

function expand_article_with_ai(string $text, string $shortcode): string {
    if (str_word_count(strip_tags($text)) >= 300) return $text;
    if (empty(trim($text))) return $text;

    global $ROOT;
    $cacheFile = $ROOT . '/api/ig_cache/ai_cache.json'; // Moved to writable path
    $cache = [];
    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    
    $cacheKey = $shortcode ?: md5($text);
    // Increased threshold to 300
    if (isset($cache[$cacheKey]) && str_word_count(strip_tags($cache[$cacheKey])) >= 300) {
        return $cache[$cacheKey];
    }
    
    $apiKeys = get_all_api_keys();
    if (empty($apiKeys)) return $text;

    $prompt = "Buatlah sebuah artikel berita atau liputan kegiatan berbahasa Indonesia yang profesional, informatif, dan menginspirasi berdasarkan informasi berikut. Artikel MINIMAL 350 kata dan MAKSIMAL 500 kata. Isi harus sangat mendalam. Gunakan format paragraf HTML murni (gunakan tag <p> dan <br> saja, JANGAN gunakan blok kode markdown, jangan gunakan tag <html> atau <body>). Informasi dasar: \"" . strip_tags($text) . "\"";

    $generatedText = '';
    foreach ($apiKeys as $apiKeyInfo) {
        log_progress("Attempting AI generation for $shortcode via " . $apiKeyInfo['provider']);
        $result = call_ai_provider($apiKeyInfo, $prompt);
        if ($result && strpos($result, '<think>') !== false) {
            $result = preg_replace('/<think>.*?<\/think>/s', '', $result);
        }
        
        $wc = str_word_count(strip_tags($result));
        log_progress("Result WC: $wc");
        
        if ($result && $wc >= 300) {
            $generatedText = $result;
            break;
        }
    }
    
    if ($generatedText) {
        $cleanText = preg_replace('/```html\s*|```\s*/', '', $generatedText);
        $cleanText = trim($cleanText);
        
        $cache[$cacheKey] = $cleanText;
        file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
        return $cleanText;
    }
    
    return $text;
}

function render_media(array $item): string {
    $img  = htmlspecialchars($item['img']  ?? '', ENT_QUOTES);
    $link = htmlspecialchars($item['link'] ?? '#', ENT_QUOTES);
    $isVideo = !empty($item['is_video']) || !empty($item['video']);

    if ($isVideo) {
        if (!empty($item['video'])) {
            $vSrc    = htmlspecialchars($item['video'], ENT_QUOTES);
            $poster  = $img;
            return "<video src=\"{$vSrc}\" poster=\"{$poster}\" width=\"220\" height=\"140\" autoplay loop muted playsinline preload=\"metadata\" class=\"autoplay-video\" style=\"width:100%;height:100%;object-fit:cover;\"></video>";
        }
        if ($img) {
            return "<a href=\"{$link}\" target=\"_blank\" rel=\"noopener\" style=\"display:block;width:100%;height:100%;position:relative;\">
                <img src=\"{$img}\" alt=\"News Media\" width=\"220\" height=\"140\" loading=\"lazy\" style=\"width:100%;height:100%;object-fit:cover;\">
                <span style=\"position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.55);border-radius:50%;width:46px;height:46px;display:flex;align-items:center;justify-content:center;\">
                    <i class=\"fas fa-play\" style=\"color:#fff;font-size:1.2rem;margin-left:3px;\"></i>
                </span>
            </a>";
        }
    }

    if ($img) {
        return "<img src=\"{$img}\" alt=\"News Media\" width=\"220\" height=\"140\" loading=\"lazy\" style=\"width:100%;height:100%;object-fit:cover;\" onerror=\"this.src='https://via.placeholder.com/400x250/006837/ffffff?text=Berita'; this.onerror=null;\">";
    }

    return '<div style="width:100%;height:100%;background:#eee;display:flex;align-items:center;justify-content:center;color:#ccc;"><i class="fas fa-image fa-2x"></i></div>';
}

function render_card(array $item, int $index): string {
    $title   = htmlspecialchars($item['title']   ?? 'Tanpa Judul', ENT_QUOTES);
    $date    = htmlspecialchars($item['date']    ?? '', ENT_QUOTES);
    $author  = htmlspecialchars($item['author']  ?? '', ENT_QUOTES);
    $link    = htmlspecialchars($item['link']    ?? '#', ENT_QUOTES);
    $media   = render_media($item);
    $sc      = extract_shortcode($item['link'] ?? '');
    $artId   = $sc ?: "art_{$index}";

    $excerpt = $item['excerpt'] ?? '';
    // Generate AI HTML if needed
    $expanded_html = expand_article_with_ai($excerpt, $sc);
    
    if (strpos($expanded_html, '<p>') === false) {
        $expanded_html = "<p>" . nl2br(htmlspecialchars($expanded_html, ENT_QUOTES)) . "</p>";
    }

    return "
                    <div class=\"news-card\" data-article-id=\"{$artId}\">
                        <div class=\"news-img-box\">
                            {$media}
                        </div>
                        <div class=\"news-details\" style=\"flex: 1;\">
                            <div class=\"news-meta\">
                                <span><i class=\"far fa-calendar-alt\"></i> {$date}</span>
                                <span><i class=\"far fa-user\"></i> {$author}</span>
                            </div>
                            <a href=\"{$link}\" target=\"_blank\" rel=\"noopener\" class=\"news-title\" id=\"title_{$artId}\" style=\"font-size: 1.3rem; margin-bottom: 15px; display: block;\">{$title}</a>
                            <div class=\"news-full-article\" style=\"color: #444; line-height: 1.7; font-size: 1rem;\">
                                {$expanded_html}
                            </div>
                        </div>
                    </div>";
}

// Data Merging
$main = read_json($NEWS_FILE);
$mi   = read_json($MI_FILE);

$seen = [];
$merged = [];
foreach ($main as $item) {
    $sc = extract_shortcode($item['link'] ?? '');
    $key = $sc ?: md5(($item['link'] ?? '') . ($item['title'] ?? ''));
    if (!isset($seen[$key])) {
        $seen[$key] = true;
        $merged[] = $item;
    }
}
foreach ($mi as $item) {
    $sc = extract_shortcode($item['link'] ?? '');
    $key = $sc ?: md5(($item['link'] ?? '') . ($item['title'] ?? ''));
    if (!isset($seen[$key])) {
        $seen[$key] = true;
        $merged[] = $item;
    }
}

$byAuthor = [];
foreach ($merged as $item) {
    $a = $item['author'] ?? 'unknown';
    $byAuthor[$a][] = $item;
}
$balanced = [];
foreach ($byAuthor as $posts) {
    usort($posts, function($a, $b) {
        return intval($b['timestamp'] ?? 0) - intval($a['timestamp'] ?? 0);
    });
    $balanced = array_merge($balanced, array_slice($posts, 0, 5));
}
usort($balanced, function($a, $b) {
    return intval($b['timestamp'] ?? 0) - intval($a['timestamp'] ?? 0);
});
$articles = array_slice($balanced, 0, 15);

if (empty($articles)) exit(1);

$cards_html = "\n";
foreach ($articles as $i => $item) {
    $cards_html .= render_card($item, $i) . "\n";
}

file_put_contents($ROOT . '/api/ig_cache/cards_only.html', $cards_html);
echo "SUCCESS";
exit(0);
