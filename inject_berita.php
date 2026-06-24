<?php
/**
 * inject_berita.php
 * Reads api/news.json + api/news_mi.json, expands articles via AI,
 * then injects static HTML cards into berita.html.
 * Run via: php inject_berita.php
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (function_exists('set_time_limit')) {
    set_time_limit(300); // Maks 5 menit
}

// ─── Konfigurasi Terpusat (Fitur F2) ──────────────────────────────────────────
define('MAX_PER_AUTHOR',    7);     // Maks artikel per penulis Instagram
define('MAX_TOTAL',         21);    // Maks total artikel yang dirender
define('AI_WORD_THRESHOLD', 200);   // Kata minimum sebelum AI di-skip

$ROOT      = '/mnt/webdata/htdocs';
$NEWS_FILE = $ROOT . '/api/news.json';
$MI_FILE   = $ROOT . '/api/news_mi.json';
$HTML_FILE = $ROOT . '/berita.html';

// ─── Fungsi Utilitas ────────────────────────────────────────────────────────────

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

/**
 * FIX Bug #5 — Hitung kata yang akurat untuk Bahasa Indonesia.
 * str_word_count() dirancang untuk bahasa Inggris dan tidak akurat
 * untuk teks Indonesia yang mengandung emoji, karakter Unicode, dll.
 */
function count_words_id(string $text): int {
    $clean = strip_tags($text);
    // Hapus emoji dan simbol, pertahankan huruf, angka, spasi, tanda hubung
    $clean = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $clean);
    $words = preg_split('/\s+/u', trim($clean), -1, PREG_SPLIT_NO_EMPTY);
    return count($words);
}

/**
 * FIX Bug #7 — Deteksi dan normalisasi URL gambar.
 * Jika gambar adalah URL CDN Instagram (bisa kadaluarsa), coba
 * fallback ke cache lokal. Jika tidak ada, pakai placeholder.
 */
function get_safe_img(array $item): string {
    global $ROOT;
    $img = $item['img'] ?? '';
    if (empty($img)) return '';

    // Sudah path lokal — langsung return
    if (!str_starts_with($img, 'http')) return $img;

    // URL eksternal: coba temukan cache lokal berdasarkan shortcode
    $sc = extract_shortcode($item['link'] ?? '');
    if ($sc) {
        $localPath = "api/ig_cache/{$sc}.jpg";
        if (file_exists("{$ROOT}/{$localPath}")) return $localPath;
        // Coba juga .webp
        $localWebp = "api/ig_cache/{$sc}.webp";
        if (file_exists("{$ROOT}/{$localWebp}")) return $localWebp;
    }

    // Fallback: placeholder bergradien hijau dengan nama author
    $author = urlencode($item['author'] ?? 'Berita');
    return "https://via.placeholder.com/400x250/006837/ffffff?text={$author}";
}

/**
 * Fitur F1 — Badge warna berdasarkan akun Instagram (unit sekolah).
 * Mengembalikan ['label' => '...', 'color' => '...', 'bg' => '...']
 */
function get_school_badge(string $author): array {
    $a = strtolower($author);
    if (str_contains($a, 'mi') || str_contains($a, 'madrasah') || str_contains($a, 'miradenrahmat')) {
        return ['label' => 'MI',    'color' => '#7C3AED', 'bg' => '#EDE9FE'];
    }
    if (str_contains($a, 'smp') || str_contains($a, 'smprrahmat')) {
        return ['label' => 'SMP',   'color' => '#1D4ED8', 'bg' => '#DBEAFE'];
    }
    if (str_contains($a, 'smartba') || str_contains($a, 'sma') || str_contains($a, 'smar')) {
        return ['label' => 'SMA',   'color' => '#DC2626', 'bg' => '#FEE2E2'];
    }
    return ['label' => 'BP3NU',     'color' => '#006837', 'bg' => '#D1FAE5'];
}

/**
 * FIX Bug #1 — Render media dengan container yang benar.
 * Video + play overlay sekarang dibungkus dalam div relative
 * agar overlay bisa diposisikan dengan position:absolute.
 */
function render_media(array $item): string {
    $img     = htmlspecialchars(get_safe_img($item), ENT_QUOTES); // FIX Bug #7
    $isVideo = !empty($item['is_video']) || !empty($item['video']);

    if ($isVideo) {
        if (!empty($item['video'])) {
            $vSrc   = htmlspecialchars($item['video'], ENT_QUOTES);
            $poster = $img;
            // FIX Bug #1: wrapper <div> memastikan overlay tertimpa di atas video
            return "<div style=\"position:relative;width:100%;height:100%;\">
                <video src=\"{$vSrc}\" poster=\"{$poster}\" muted preload=\"metadata\"
                    class=\"autoplay-video\" style=\"width:100%;height:100%;object-fit:cover;\"
                    playsinline controls></video>
                <div class=\"play-overlay\" style=\"position:absolute;top:50%;left:50%;
                    transform:translate(-50%,-50%);width:60px;height:60px;
                    background:rgba(0,0,0,0.6);border-radius:50%;display:flex;
                    align-items:center;justify-content:center;pointer-events:none;
                    transition:all 0.3s ease;\">
                    <i class=\"fas fa-play\" style=\"color:#fff;font-size:24px;margin-left:3px;\"></i>
                </div>
            </div>";
        }
        // Tidak ada URL video — tampilkan thumbnail dengan play icon
        if ($img) {
            return "<div style=\"position:relative;width:100%;height:100%;\">
                <img src=\"{$img}\" alt=\"News Media\" loading=\"lazy\"
                    style=\"width:100%;height:100%;object-fit:cover;\">
                <div class=\"play-overlay\" style=\"position:absolute;top:50%;left:50%;
                    transform:translate(-50%,-50%);width:60px;height:60px;
                    background:rgba(0,0,0,0.6);border-radius:50%;display:flex;
                    align-items:center;justify-content:center;pointer-events:none;\">
                    <i class=\"fas fa-play\" style=\"color:#fff;font-size:24px;margin-left:3px;\"></i>
                </div>
            </div>";
        }
    }

    if ($img) {
        return "<img src=\"{$img}\" alt=\"News Media\" loading=\"lazy\"
            style=\"width:100%;height:100%;object-fit:cover;\"
            onerror=\"this.src='https://via.placeholder.com/400x250/006837/ffffff?text=Berita';this.onerror=null;\">";
    }

    return '<div style="width:100%;height:100%;background:#eee;display:flex;align-items:center;'
         . 'justify-content:center;color:#ccc;"><i class="fas fa-image fa-2x"></i></div>';
}

/**
 * Baca semua API key dari file token.
 * Mendukung Gemini (AIzaSy...), Groq (gsk_...), OpenRouter (sk-or-...).
 */
function get_all_api_keys(): array {
    global $ROOT;
    $tokenFile = $ROOT . '/API TOKEN.txt';
    if (!file_exists($tokenFile)) return [];

    $keys  = [];
    $lines = file($tokenFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (str_starts_with($line, 'AIzaSy')) {
            $keys[] = ['provider' => 'gemini',      'key' => $line];
        } elseif (str_starts_with($line, 'gsk_')) {
            $keys[] = ['provider' => 'groq',         'key' => $line];
        } elseif (str_starts_with($line, 'sk-or-')) {
            $keys[] = ['provider' => 'openrouter',   'key' => $line];
        }
    }
    return $keys;
}

/**
 * FIX Bug #6 — Panggil AI provider dan kembalikan hasil beserta info provider & model.
 * Sebelumnya fungsi hanya return string; sekarang return array terstruktur.
 */
function call_ai_provider(array $apiKeyInfo, string $prompt): array {
    $provider = $apiKeyInfo['provider'];
    $key      = $apiKeyInfo['key'];
    $model    = '';
    $text     = '';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if ($provider === 'gemini') {
        $model = 'gemini-1.5-flash';
        $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
        $data  = [
            "contents"         => [["parts" => [["text" => $prompt]]]],
            "generationConfig" => ["temperature" => 0.7, "maxOutputTokens" => 1024],
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $res  = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($res, true);
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';

    } elseif ($provider === 'groq') {
        $model = 'llama-3.1-8b-instant';
        $url   = "https://api.groq.com/openai/v1/chat/completions";
        $data  = [
            "model"       => $model,
            "messages"    => [["role" => "user", "content" => $prompt]],
            "temperature" => 0.7,
            "max_tokens"  => 1024,
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $res  = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($res, true);
        $text = $json['choices'][0]['message']['content'] ?? '';

    } elseif ($provider === 'openrouter') {
        $model = 'deepseek/deepseek-r1';
        $url   = "https://openrouter.ai/api/v1/chat/completions";
        $data  = [
            "model"       => $model,
            "messages"    => [["role" => "user", "content" => $prompt]],
            "temperature" => 0.7,
            "max_tokens"  => 600,
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $res  = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($res, true);
        $text = $json['choices'][0]['message']['content'] ?? '';

    } else {
        curl_close($ch);
    }

    return ['text' => $text, 'provider' => $provider, 'model' => $model];
}

/**
 * Perluas teks artikel menggunakan AI jika belum cukup panjang.
 *
 * FIX Bug #3 — Bersihkan markdown (**bold**, *italic*, # Heading) dari respons AI.
 * FIX Bug #4 — Gunakan field `caption` (teks lengkap Instagram) sebagai input AI.
 * FIX Bug #5 — Gunakan count_words_id() sebagai pengganti str_word_count().
 * FIX Bug #6 — Return array ['html', 'provider', 'model'] untuk label dinamis.
 *
 * @param string $excerpt   Teks singkat (150 char dari JSON)
 * @param string $shortcode Shortcode Instagram untuk cache key
 * @param string $caption   Teks caption lengkap Instagram (prioritas)
 */
function expand_article_with_ai(string $excerpt, string $shortcode, string $caption = ''): array {
    // FIX Bug #4: Prioritaskan caption (teks penuh) sebagai input AI
    $inputText = !empty(trim($caption)) ? $caption : $excerpt;

    // FIX Bug #5: Gunakan count_words_id() yang akurat untuk Bahasa Indonesia
    if (count_words_id(strip_tags($inputText)) >= AI_WORD_THRESHOLD) {
        return ['html' => $inputText, 'provider' => '', 'model' => ''];
    }
    if (empty(trim($inputText))) {
        return ['html' => $inputText, 'provider' => '', 'model' => ''];
    }

    global $ROOT;
    $cacheFile = $ROOT . '/api/ig_cache/ai_cache.json';
    $cache     = [];
    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true) ?: [];
    }

    $cacheKey = $shortcode ?: md5($inputText);
    if (isset($cache[$cacheKey]) && count_words_id(strip_tags($cache[$cacheKey])) >= AI_WORD_THRESHOLD) {
        return ['html' => $cache[$cacheKey], 'provider' => 'cache', 'model' => ''];
    }

    $apiKeys = get_all_api_keys();
    if (empty($apiKeys)) {
        return ['html' => $inputText, 'provider' => '', 'model' => ''];
    }

    $prompt = "Buatlah sebuah artikel berita atau liputan kegiatan berbahasa Indonesia yang profesional, "
            . "informatif, dan menginspirasi berdasarkan informasi berikut. "
            . "Artikel HARUS terdiri dari 300 sampai 500 kata. "
            . "Gunakan format paragraf HTML murni (gunakan tag <p> dan <br> saja, "
            . "JANGAN gunakan blok kode markdown, jangan gunakan tag <html> atau <body>). "
            . "Informasi dasar: \"" . strip_tags($inputText) . "\"";

    $usedProvider  = '';
    $usedModel     = '';
    $generatedText = '';

    foreach ($apiKeys as $apiKeyInfo) {
        $result = call_ai_provider($apiKeyInfo, $prompt);
        $text   = $result['text'];

        // Bersihkan tag <think>...</think> dari DeepSeek R1
        if ($text && str_contains($text, '<think>')) {
            $text = preg_replace('/<think>.*?<\/think>/s', '', $text);
        }

        // FIX Bug #3: Bersihkan semua markdown formatting dari respons AI
        $text = preg_replace('/```(?:html)?\s*([\s\S]*?)```/s', '$1', $text); // ``` code blocks ```
        $text = preg_replace('/\*\*(.*?)\*\*/s', '$1', $text);                 // **bold**
        $text = preg_replace('/\*(.*?)\*/s', '$1', $text);                     // *italic*
        $text = preg_replace('/^#{1,6}\s+(.+)$/mu', '<strong>$1</strong>', $text); // # Heading
        $text = preg_replace('/\n{3,}/', "\n\n", $text);                       // Newline berlebihan
        $text = trim($text);

        if ($text && count_words_id(strip_tags($text)) >= AI_WORD_THRESHOLD) {
            $generatedText = $text;
            $usedProvider  = $result['provider'];
            $usedModel     = $result['model'];
            break;
        }
    }

    if ($generatedText) {
        $cache[$cacheKey] = $generatedText;
        file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return ['html' => $generatedText, 'provider' => $usedProvider, 'model' => $usedModel];
    }

    return ['html' => $inputText, 'provider' => '', 'model' => ''];
}

/**
 * Render satu card berita.
 *
 * FIX Bug #2 — URL mentah (non-HTML-encoded) dipakai di dalam JS onclick
 * agar tidak terjadi double-encoding (`&amp;`, `&#039;`, dsb).
 * FIX Bug #8 — Progress logging ditangani di loop utama, bukan di sini.
 */
function render_card(array $item, int $index): string {
    // FIX Bug #2: Simpan nilai mentah untuk JS, HTML-encode hanya untuk atribut HTML
    $link_raw  = $item['link']   ?? '#';
    $title_raw = $item['title']  ?? 'Tanpa Judul';

    $title  = htmlspecialchars($title_raw,         ENT_QUOTES, 'UTF-8');
    $date   = htmlspecialchars($item['date']    ?? '', ENT_QUOTES, 'UTF-8');
    $author = htmlspecialchars($item['author']  ?? '', ENT_QUOTES, 'UTF-8');
    $link   = htmlspecialchars($link_raw,             ENT_QUOTES, 'UTF-8');

    $media  = render_media($item);
    $sc     = extract_shortcode($link_raw);
    $artId  = $sc ?: "art_{$index}";

    $excerpt = $item['excerpt'] ?? '';
    $caption = $item['caption'] ?? ''; // FIX Bug #4: ambil caption lengkap

    // Teks excerpt pendek untuk preview
    $excerpt_short = mb_substr(strip_tags($excerpt), 0, 150, 'UTF-8');
    if (mb_strlen(strip_tags($excerpt), 'UTF-8') > 150) {
        $excerpt_short .= '...';
    }
    $excerpt_short = htmlspecialchars($excerpt_short, ENT_QUOTES, 'UTF-8');

    // FIX Bug #4: Pass caption ke expand_article_with_ai
    $aiResult      = expand_article_with_ai($excerpt, $sc, $caption);
    $expanded_html = $aiResult['html'];
    $ai_provider   = $aiResult['provider'];

    // Fallback jika hasil AI bukan HTML (tidak ada tag <p>)
    if (strpos($expanded_html, '<p>') === false && strpos($expanded_html, '<P>') === false) {
        $expanded_html = "<p>" . nl2br(htmlspecialchars($expanded_html, ENT_QUOTES, 'UTF-8')) . "</p>";
    }

    // Highlight teks Arab dengan style khusus
    $expanded_html = preg_replace(
        '/(<p>)((?:[^\x{0600}-\x{06FF}]*[\x{0600}-\x{06FF}][^\x{0600}-\x{06FF}]*)+)(<\/p>)/u',
        '<p style="font-family:\'Amiri\',serif;font-size:1.8rem;line-height:2.2;text-align:right;'
        . 'direction:rtl;color:#1a5f3a;background:linear-gradient(135deg,rgba(230,244,234,0.5),'
        . 'rgba(255,255,255,0.3));border-right:4px solid #f39c12;border-radius:8px;'
        . 'padding:15px;margin:20px 0;">$2</p>',
        $expanded_html
    );

    // FIX Bug #5: Hitung kata dengan count_words_id()
    $word_count = count_words_id(strip_tags($expanded_html));

    // Fitur F1: Badge unit sekolah
    $badge      = get_school_badge($item['author'] ?? '');
    $badge_html = "<span style=\"display:inline-block;background:{$badge['bg']};color:{$badge['color']};"
                . "padding:3px 10px;border-radius:12px;font-size:0.75rem;font-weight:700;"
                . "letter-spacing:0.5px;margin-right:8px;\">{$badge['label']}</span>";

    // FIX Bug #6: Label provider AI yang akurat dan dinamis
    $ai_label = match($ai_provider) {
        'gemini'     => 'Gemini 1.5 Flash',
        'groq'       => 'Groq (Llama 3.1)',
        'openrouter' => 'DeepSeek R1',
        'cache'      => 'AI (Cache)',
        default      => 'AI',
    };

    // FIX Bug #2: Escape khusus untuk konteks JavaScript (addslashes, bukan htmlspecialchars)
    $link_js  = addslashes($link_raw);
    $title_js = addslashes($title_raw);

    // WhatsApp share URL — dibuat di PHP, bukan di JS, agar encoding benar
    $wa_text  = rawurlencode("*{$title_raw}*\n\nBaca: {$link_raw}");
    $wa_url   = "https://wa.me/?text={$wa_text}";
    $wa_url_h = htmlspecialchars($wa_url, ENT_QUOTES, 'UTF-8');

    // Facebook share — encode URL di PHP
    $fb_url   = 'https://facebook.com/sharer/sharer.php?u=' . rawurlencode($link_raw);
    $fb_url_h = htmlspecialchars($fb_url, ENT_QUOTES, 'UTF-8');

    return "
                    <div class=\"news-card\" data-article-id=\"{$artId}\">
                        <div class=\"news-img-box\" style=\"position:relative;\">
                            {$media}
                        </div>
                        <div class=\"news-details\" style=\"flex: 1;\">
                            <div class=\"news-meta\" style=\"display:flex;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:10px;\">
                                {$badge_html}
                                <span><i class=\"far fa-calendar-alt\"></i> {$date}</span>
                                <span><i class=\"far fa-user\"></i> {$author}</span>
                            </div>
                            <a href=\"{$link}\" target=\"_blank\" rel=\"noopener\" class=\"news-title\" id=\"title_{$artId}\"
                                style=\"font-size: 1.3rem; margin-bottom: 15px; display: block;\">{$title}</a>

                            <!-- Excerpt singkat -->
                            <p class=\"news-excerpt\" id=\"excerpt_{$artId}\"
                                style=\"font-size:0.95rem;color:#444;margin-bottom:15px;\">{$excerpt_short}</p>

                            <button class=\"read-more-btn\"
                                onclick=\"(function(btn){var el=document.getElementById('full_{$artId}');if(el.style.display==='none'){el.style.display='block';btn.innerHTML='Tutup <i class=&quot;fas fa-chevron-up&quot;></i>';btn.classList.add('expanded');}else{el.style.display='none';btn.innerHTML='Baca Selengkapnya <i class=&quot;fas fa-chevron-down&quot;></i>';btn.classList.remove('expanded');}})(this)\"
                                style=\"background:linear-gradient(135deg,#006837,#004d29);color:#fff;border:none;
                                    padding:10px 25px;border-radius:25px;font-weight:600;font-size:0.9rem;
                                    cursor:pointer;transition:all 0.3s ease;display:inline-flex;align-items:center;
                                    gap:8px;margin-top:10px;box-shadow:0 4px 15px rgba(0,104,55,0.2);\">
                                Baca Selengkapnya <i class=\"fas fa-chevron-down\"></i>
                            </button>

                            <div id=\"full_{$artId}\" class=\"news-full-article\"
                                style=\"display:none;margin-top:20px;text-align:left;color:#444;line-height:1.7;font-size:1rem;\">
                                {$expanded_html}

                                <!-- Share Buttons — FIX Bug #2: URL sudah di-encode di PHP, aman untuk atribut HTML -->
                                <div class=\"share-buttons\" style=\"display:flex;gap:10px;margin-top:20px;
                                    padding-top:15px;border-top:1px solid #eee;flex-wrap:wrap;\">
                                    <button onclick=\"window.open('{$wa_url_h}','_blank')\"
                                        style=\"background:#25D366;color:#fff;border:none;padding:8px 16px;
                                            border-radius:20px;font-size:0.85rem;font-weight:600;cursor:pointer;
                                            display:inline-flex;align-items:center;gap:6px;\">
                                        <i class=\"fab fa-whatsapp\"></i> WhatsApp
                                    </button>
                                    <button onclick=\"window.open('{$fb_url_h}','_blank','width=600,height=400')\"
                                        style=\"background:#1877F2;color:#fff;border:none;padding:8px 16px;
                                            border-radius:20px;font-size:0.85rem;font-weight:600;cursor:pointer;
                                            display:inline-flex;align-items:center;gap:6px;\">
                                        <i class=\"fab fa-facebook\"></i> Facebook
                                    </button>
                                    <button onclick=\"navigator.clipboard.writeText('{$link_js}').then(function(){alert('Link berhasil disalin!')})\"
                                        style=\"background:#6c757d;color:#fff;border:none;padding:8px 16px;
                                            border-radius:20px;font-size:0.85rem;font-weight:600;cursor:pointer;
                                            display:inline-flex;align-items:center;gap:6px;\">
                                        <i class=\"fas fa-link\"></i> Salin Link
                                    </button>
                                </div>

                                <!-- Info footer artikel -->
                                <div style=\"margin-top:20px;padding:15px;background:linear-gradient(135deg,
                                    rgba(0,104,55,0.05),rgba(243,156,18,0.05));border-radius:12px;
                                    border-left:4px solid #006837;font-size:0.85rem;line-height:1.5;\">
                                    <div style=\"display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;\">
                                        <div>
                                            <strong style=\"color:#006837;font-size:0.9rem;\">
                                                <i class=\"fab fa-instagram\"></i> @{$author}
                                            </strong><br>
                                            <span style=\"color:#666;\">
                                                <i class=\"far fa-calendar-alt\"></i> {$date}
                                            </span>
                                        </div>
                                        <div style=\"display:flex;align-items:center;gap:8px;flex-wrap:wrap;\">
                                            {$badge_html}
                                            <span style=\"background:linear-gradient(135deg,rgba(243,156,18,0.2),rgba(243,156,18,0.1));
                                                color:#d35400;padding:6px 12px;border-radius:15px;font-weight:600;font-size:0.8rem;\">
                                                <i class=\"fas fa-file-alt\"></i> {$word_count} kata
                                            </span>
                                        </div>
                                    </div>
                                    <!-- FIX Bug #6: Label AI yang akurat dan dinamis -->
                                    <div style=\"margin-top:12px;font-style:italic;color:#888;text-align:right;
                                        border-top:1px dashed #ddd;padding-top:10px;font-size:0.75rem;\">
                                        <i class=\"fas fa-robot\"></i> Ditulis otomatis oleh {$ai_label}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>";
}

// ─── Load dan merge data ────────────────────────────────────────────────────────
$main = read_json($NEWS_FILE);
$mi   = read_json($MI_FILE);

// Deduplikasi berdasarkan shortcode atau hash link+title
$seen   = [];
$merged = [];
foreach ($main as $item) {
    $sc  = extract_shortcode($item['link'] ?? '');
    $key = $sc ?: md5(($item['link'] ?? '') . ($item['title'] ?? ''));
    if (!isset($seen[$key])) { $seen[$key] = true; $merged[] = $item; }
}
foreach ($mi as $item) {
    $sc  = extract_shortcode($item['link'] ?? '');
    $key = $sc ?: md5(($item['link'] ?? '') . ($item['title'] ?? ''));
    if (!isset($seen[$key])) { $seen[$key] = true; $merged[] = $item; }
}

// Distribusi adil: MAX_PER_AUTHOR per penulis, urutkan timestamp terbaru, ambil MAX_TOTAL
$byAuthor = [];
foreach ($merged as $item) {
    $a            = $item['author'] ?? 'unknown';
    $byAuthor[$a][] = $item;
}
$balanced = [];
foreach ($byAuthor as $posts) {
    usort($posts, fn($a, $b) => intval($b['timestamp'] ?? 0) - intval($a['timestamp'] ?? 0));
    $balanced = array_merge($balanced, array_slice($posts, 0, MAX_PER_AUTHOR));
}
usort($balanced, fn($a, $b) => intval($b['timestamp'] ?? 0) - intval($a['timestamp'] ?? 0));
$articles = array_slice($balanced, 0, MAX_TOTAL);

if (empty($articles)) {
    echo "ERROR: Tidak ada artikel ditemukan di news.json / news_mi.json.\n";
    exit(1);
}

$total = count($articles);
echo "📰 Memproses {$total} artikel (maks " . MAX_PER_AUTHOR . "/penulis, total " . MAX_TOTAL . ")...\n\n";
flush();

// ─── Generate HTML cards ────────────────────────────────────────────────────────
$cards_html = "\n";
foreach ($articles as $i => $item) {
    $num     = $i + 1;
    $cards_html .= render_card($item, $i) . "\n";
}

// ─── Inject ke berita.html ──────────────────────────────────────────────────────
$html = file_get_contents($HTML_FILE);
if ($html === false) {
    echo "ERROR: Gagal membaca {$HTML_FILE}\n";
    exit(1);
}

$new_html = preg_replace(
    '/(<div[^>]+id="news-container"[^>]*>).*?(<\/div>\s*<\/main>)/s',
    "$1\n{$cards_html}\n        $2",
    $html,
    1
);

// Fallback manual jika regex tidak cocok (struktur HTML berbeda)
if ($new_html === null || $new_html === $html) {
    $pos = strpos($html, 'id="news-container"');
    if ($pos !== false) {
        $tagEnd   = strpos($html, '>', $pos);
        $mainEnd  = strpos($html, '</main>', $tagEnd);
        if ($mainEnd !== false) {
            $closePos = strrpos(substr($html, 0, $mainEnd), '</div>');
            if ($closePos !== false && $closePos > $tagEnd) {
                $new_html = substr($html, 0, $tagEnd + 1)
                          . "\n" . $cards_html . "\n        "
                          . substr($html, $closePos);
            }
        }
    }
}

if (empty($new_html)) {
    echo "ERROR: Gagal meng-inject HTML. Elemen #news-container tidak ditemukan atau format tidak sesuai.\n";
    exit(1);
}

if ($new_html === $html) {
    echo "✅ berita.html sudah up-to-date (tidak ada perubahan).\n";
    exit(0);
}

file_put_contents($HTML_FILE, $new_html);
echo "✅ Injeksi ke berita.html berhasil! Total: {$total} artikel.\n";
exit(0);
