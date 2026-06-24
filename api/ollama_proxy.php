<?php
/**
 * Unified AI Proxy API
 * Supports Gemini, Groq, and DeepSeek with automatic fallback.
 * BP3NU Raden Rahmat Balongbendo
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$messages = $data['messages'] ?? [];
$system_instruction = $data['system_instruction'] ?? '';

// KNOWLEDGE ENRICHMENT
$school_db = include 'school_database.php';
$knowledge_context = "INFORMASI TAMBAHAN DATABASE SEKOLAH:\n";
$knowledge_context .= "- Yayasan: " . $school_db['BP3NU_RADEN_RAHMAT']['full_name'] . "\n";
$knowledge_context .= "- Didirikan: " . $school_db['BP3NU_RADEN_RAHMAT']['founded_date'] . "\n";
$knowledge_context .= "- Legal: NIB " . $school_db['BP3NU_RADEN_RAHMAT']['legal_status']['NIB'] . ", SK Menkumham " . $school_db['BP3NU_RADEN_RAHMAT']['legal_status']['SK_MENKUMHAM'] . "\n";
$knowledge_context .= "- Unit: MI (NPSN " . $school_db['MI_RADEN_RAHMAT']['npsn'] . "), SMP (NPSN " . $school_db['SMP_R_RAHMAT']['npsn'] . "), SMA (NPSN 20501869)\n";
$knowledge_context .= "- Sejarah MI: " . $school_db['MI_RADEN_RAHMAT']['history'] . "\n";
$knowledge_context .= "- Slogan SMP: " . $school_db['SMP_R_RAHMAT']['slogan'] . "\n\n";

$system_instruction = $knowledge_context . $system_instruction;

// API TOKENS (Exhaustive from API TOKEN.txt)
$tokens = [
    'gemini' => [
        'AIzaSyDkA45gu4Jl4L4maNqRmhVoB2zBMojKUIs',
        'AIzaSyBQ-UMRsqvWol4Cy3q0Nu4R7D4ck4Nmsrs',
        'AIzaSyCjfhEFnV9GavvFhwIqYf5zmdCBuq_UkCU'
    ],
    'groq' => [
        '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',
        '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'
    ],
    'openrouter' => [
        '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'
    ]
];

/**
 * Call Gemini API with Fallback between keys
 */
function callGemini($messages, $system, $keys)
{
    $contents = [];
    foreach ($messages as $m) {
        $contents[] = [
            'role' => ($m['role'] === 'assistant' ? 'model' : 'user'),
            'parts' => [['text' => $m['content']]]
        ];
    }
    $payload = ['contents' => $contents];
    if ($system)
        $payload['system_instruction'] = ['parts' => [['text' => $system]]];
    $jsonData = json_encode($payload);

    foreach ($keys as $key) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $key;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            $json = json_decode($res, true);
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($text)
                return ['reply' => $text, 'provider' => 'Gemini API'];
        }

        // Fallback to file_get_contents if curl fails
        if (!$res && ini_get('allow_url_fopen')) {
            $options = [
                'http' => [
                    'header' => "Content-type: application/json\r\n",
                    'method' => 'POST',
                    'content' => $jsonData,
                    'ignore_errors' => true,
                    'timeout' => 10
                ],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
            ];
            $context = stream_context_create($options);
            $res = @file_get_contents($url, false, $context);
            if ($res) {
                $json = json_decode($res, true);
                $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($text)
                    return ['reply' => $text, 'provider' => 'Gemini (Stream)'];
            }
        }
        $last_err = "Key: " . substr($key, 0, 8) . "... Err: " . ($err ?: "HTTP $code");
    }
    return ['error' => $last_err];
}

/**
 * Call Groq API
 */
function callGroq($messages, $system, $keys)
{
    $fullMessages = [];
    if ($system)
        $fullMessages[] = ['role' => 'system', 'content' => $system];
    foreach ($messages as $m)
        $fullMessages[] = ['role' => $m['role'], 'content' => $m['content']];
    $payload = ['model' => 'llama-3.3-70b-versatile', 'messages' => $fullMessages, 'temperature' => 0.7];
    $jsonData = json_encode($payload);

    foreach ($keys as $key) {
        $url = "https://api.groq.com/openai/v1/chat/completions";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $key]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            $json = json_decode($res, true);
            $text = $json['choices'][0]['message']['content'] ?? null;
            if ($text)
                return ['reply' => $text, 'provider' => 'Groq API'];
        }
    }
    return ['error' => "Groq failed"];
}

/**
 * Call DeepSeek via OpenRouter
 */
function callOpenRouter($messages, $system, $keys)
{
    $fullMessages = [];
    if ($system)
        $fullMessages[] = ['role' => 'system', 'content' => $system];
    foreach ($messages as $m)
        $fullMessages[] = ['role' => $m['role'], 'content' => $m['content']];
    $payload = ['model' => 'deepseek/deepseek-r1:free', 'messages' => $fullMessages];
    $jsonData = json_encode($payload);

    foreach ($keys as $key) {
        $url = "https://openrouter.ai/api/v1/chat/completions";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $key,
            'HTTP-Referer: https://radenrahmatbalongbendo.sch.id',
            'X-Title: BP3NU Chatbot'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            $json = json_decode($res, true);
            $text = $json['choices'][0]['message']['content'] ?? null;
            if ($text)
                return ['reply' => $text, 'provider' => 'OpenRouter'];
        }
    }
    return ['error' => "OpenRouter failed"];
}

// EXECUTION WITH FALLBACK
$debug = [];

// Try Gemini
$resGemini = callGemini($messages, $system_instruction, $tokens['gemini']);
if (isset($resGemini['reply'])) {
    $result = $resGemini;
}
else {
    $debug['gemini'] = $resGemini;

    // Try Groq
    $resGroq = callGroq($messages, $system_instruction, $tokens['groq']);
    if (isset($resGroq['reply'])) {
        $result = $resGroq;
    }
    else {
        $debug['groq'] = $resGroq;

        // Try OpenRouter
        $resOR = callOpenRouter($messages, $system_instruction, $tokens['openrouter']);
        if (isset($resOR['reply'])) {
            $result = $resOR;
        }
        else {
            $debug['openrouter'] = $resOR;
            $result = null;
        }
    }
}

if ($result) {
    echo json_encode([
        'reply' => $result['reply'],
        'model' => $result['provider'] . ' (Cloud)'
    ]);
}
else {
    echo json_encode([
        'error' => 'Semua layanan AI sedang sibuk. Silakan coba lagi nanti.',
        'debug' => $debug
    ]);
}
?>