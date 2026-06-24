<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_start();

$ROOT = '/mnt/webdata/htdocs';

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

function call_ai_provider(array $apiKeyInfo, string $prompt): string {
    $provider = $apiKeyInfo['provider'];
    $key = $apiKeyInfo['key'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    echo "  -> Trying $provider...\n";
    
    if ($provider === 'gemini') {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=" . $key;
        $data = [
            "contents" => [["parts" => [["text" => $prompt]]]],
            "generationConfig" => ["temperature" => 0.7, "maxOutputTokens" => 1024]
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        $json = json_decode($res, true);
        $txt = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
        echo "     Result length: " . strlen($txt) . "\n";
        if (!$txt) {
            // Fallback to gemini-pro if 1.5-pro fails
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $key;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $res = curl_exec($ch);
            curl_close($ch);
            $json = json_decode($res, true);
            $txt = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (!$txt) echo "     Error: $err | Resp: " . substr($res, 0, 150) . "...\n";
        }
        return $txt;
        
    } elseif ($provider === 'groq') {
        $url = "https://api.groq.com/openai/v1/chat/completions";
        $data = [
            "model" => "llama-3.1-8b-instant",
            "messages" => [["role" => "user", "content" => $prompt]],
            "temperature" => 0.7,
            "max_tokens" => 1024
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        $json = json_decode($res, true);
        $txt = $json['choices'][0]['message']['content'] ?? '';
        echo "     Result length: " . strlen($txt) . "\n";
        if (!$txt) echo "     Error: $err | Resp: " . substr($res, 0, 150) . "...\n";
        return $txt;
        
    } elseif ($provider === 'openrouter') {
        $url = "https://openrouter.ai/api/v1/chat/completions";
        // Lower max_tokens because user only has 2697 credits left and deepseek-r1 thinks a lot!
        // We'll use a cheaper or less token-heavy model, but let's stick to deepseek-r1 with max_tokens 500
        $data = [
            "model" => "deepseek/deepseek-r1",
            "messages" => [["role" => "user", "content" => $prompt]],
            "temperature" => 0.7,
            "max_tokens" => 600
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        $json = json_decode($res, true);
        $txt = $json['choices'][0]['message']['content'] ?? '';
        echo "     Result length: " . strlen($txt) . "\n";
        if (!$txt) echo "     Error: $err | Resp: " . substr($res, 0, 150) . "...\n";
        return $txt;
    }
    
    curl_close($ch);
    return '';
}

function expand_article_with_ai(string $text, string $shortcode): string {
    $apiKeys = get_all_api_keys();
    if (empty($apiKeys)) return "ERROR: No keys found.";

    $prompt = "Buatlah sebuah artikel berita atau liputan kegiatan berbahasa Indonesia yang profesional, informatif, dan menginspirasi berdasarkan informasi berikut. Artikel HARUS terdiri dari 300 sampai 500 kata. Gunakan format paragraf HTML murni (gunakan tag <p> dan <br> saja, JANGAN gunakan blok kode markdown seperti ```html, jangan gunakan tag <html> atau <body>). Informasi dasar: \"" . strip_tags($text) . "\"";

    $generatedText = '';
    foreach ($apiKeys as $apiKeyInfo) {
        $result = call_ai_provider($apiKeyInfo, $prompt);
        if ($result && strpos($result, '<think>') !== false) {
            $result = preg_replace('/<think>.*?<\/think>/s', '', $result);
        }
        
        if ($result && str_word_count(strip_tags($result)) >= 100) {
            $generatedText = $result;
            break;
        }
    }
    
    return $generatedText ?: $text;
}

echo "<pre>\n";
echo "Testing Extract and API Fallback...\n";

$keys = get_all_api_keys();
echo "Found API Keys: " . count($keys) . "\n";

$text = "Bulan suci Ramadhan telah tiba, menebarkan haruman keberkahan. BP3NU Raden Rahmat menyambut dengan berbagai program: kajian kitab salaf, pesantren kilat, dan bakti sosial pembagian takjil di sekitar Balongbendo.";
echo "\nWord count of original: " . str_word_count(strip_tags($text)) . "\n\n";

$start = microtime(true);
$res = expand_article_with_ai($text, "debug_" . time());
$end = microtime(true);

echo "\nGeneration time: " . ($end - $start) . " seconds\n";
echo "Word count of result: " . str_word_count(strip_tags($res)) . "\n";
echo "Result:\n" . substr($res, 0, 500) . "...\n";
echo "</pre>";

$out = ob_get_clean();
file_put_contents('/mnt/webdata/htdocs/api/ig_cache/debug_api.log', $out);
echo $out;
