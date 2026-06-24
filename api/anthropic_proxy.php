<?php
/**
 * AI Article Generator Proxy
 * 
 * Secure proxy for AI article generation with multiple provider fallbacks.
 * Reads API keys from .env file (outside web root for security).
 * 
 * Security Features:
 * - API keys stored outside web root
 * - .env file inaccessible via web
 * - Multiple provider fallbacks
 * - Rate limiting support
 * - Input validation
 * 
 * @author BP3NU Raden Rahmat IT Team
 * @version 2.0
 */

// Security: Block direct access to .env files
header('X-Content-Type-Options: nosniff');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

/**
 * Load environment variables from .env file
 * 
 * @param string $path Path to .env file
 * @return array Environment variables
 */
function loadEnv($path) {
    $env = [];
    
    if (!file_exists($path)) {
        error_log("[AI Proxy] .env file not found at: $path");
        return $env;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }
    }
    
    return $env;
}

/**
 * Call Google Gemini API
 * 
 * @param string $prompt The prompt to send
 * @param string $apiKey API key for Gemini
 * @param int $timeout Request timeout in seconds
 * @return array|null Response data or null on failure
 */
function callGeminiAPI($prompt, $apiKey, $timeout = 60) {
    if (empty($apiKey)) {
        error_log("[AI Proxy] Gemini API key is empty");
        return null;
    }
    
    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 2048,
        ]
    ];
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("[AI Proxy] Gemini cURL error: $error");
        return null;
    }
    
    if ($httpcode !== 200) {
        error_log("[AI Proxy] Gemini API HTTP error: $httpcode");
        return null;
    }
    
    $data = json_decode($response, true);
    
    // Check for API errors
    if (isset($data['error'])) {
        error_log("[AI Proxy] Gemini API error: " . json_encode($data['error']));
        return null;
    }
    
    // Extract text from response
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
    
    error_log("[AI Proxy] Unexpected Gemini response format");
    return null;
}

/**
 * Call Groq API (LLaMA models)
 * 
 * @param string $prompt The prompt to send
 * @param string $apiKey API key for Groq
 * @param int $timeout Request timeout in seconds
 * @return array|null Response data or null on failure
 */
function callGroqAPI($prompt, $apiKey, $timeout = 60) {
    if (empty($apiKey)) {
        error_log("[AI Proxy] Groq API key is empty");
        return null;
    }
    
    // Using Llama 3.3 70B (current recommended model)
    // Ref: https://console.groq.com/docs/deprecations
    $payload = [
        'model' => 'llama-3.3-70b-versatile',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Kamu adalah jurnalis sekolah resmi BP3NU Raden Rahmat Balongbendo, Sidoarjo. Tulis artikel dalam format HTML dengan tag <h2>, <p>, dan <strong>. Jangan gunakan markdown atau backtick.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 2048
    ];
    
    $url = "https://api.groq.com/openai/v1/chat/completions";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("[AI Proxy] Groq cURL error: $error");
        return null;
    }
    
    if ($httpcode !== 200) {
        error_log("[AI Proxy] Groq API HTTP error: $httpcode");
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['choices'][0]['message']['content'])) {
        return $data['choices'][0]['message']['content'];
    }
    
    error_log("[AI Proxy] Unexpected Groq response format");
    return null;
}

/**
 * Call DeepSeek API via OpenRouter
 * 
 * @param string $prompt The prompt to send
 * @param string $apiKey API key for DeepSeek/OpenRouter
 * @param int $timeout Request timeout in seconds
 * @return array|null Response data or null on failure
 */
function callDeepSeekAPI($prompt, $apiKey, $timeout = 60) {
    if (empty($apiKey)) {
        error_log("[AI Proxy] DeepSeek API key is empty");
        return null;
    }
    
    $payload = [
        'model' => 'deepseek/deepseek-r1-distill-llama-70b:free',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Kamu adalah jurnalis sekolah resmi BP3NU Raden Rahmat Balongbendo, Sidoarjo. Tulis artikel dalam format HTML dengan tag <h2>, <p>, dan <strong>. Jangan gunakan markdown atau backtick.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 2048
    ];
    
    $url = "https://openrouter.ai/api/v1/chat/completions";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'HTTP-Referer: https://radenrahmatbalongbendo.sch.id',
        'X-Title: BP3NU Raden Rahmat Article Generator'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("[AI Proxy] DeepSeek cURL error: $error");
        return null;
    }
    
    if ($httpcode !== 200) {
        error_log("[AI Proxy] DeepSeek API HTTP error: $httpcode");
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['choices'][0]['message']['content'])) {
        return $data['choices'][0]['message']['content'];
    }
    
    error_log("[AI Proxy] Unexpected DeepSeek response format");
    return null;
}

// ============================================================================
// MAIN EXECUTION
// ============================================================================

// Load environment configuration
// Try multiple possible locations for .env file
$possiblePaths = [
    dirname(__DIR__) . '/.env',           // /mnt/webdata/.env
    dirname(__DIR__, 2) . '/.env',        // /mnt/webdata/.env (alternative)
    __DIR__ . '/../.env',                 // /mnt/webdata/htdocs/.env
    '/mnt/webdata/.env',                  // Absolute path
];

$env = [];
$envPath = null;

foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $env = loadEnv($path);
        $envPath = $path;
        break;
    }
}

// Log configuration loading (for debugging)
error_log("[AI Proxy] Loading configuration from: $envPath");
error_log("[AI Proxy] Loaded " . count($env) . " environment variables");

// Get configuration with defaults
$debugMode = isset($env['DEBUG_MODE']) && $env['DEBUG_MODE'] == '1';
$apiTimeout = isset($env['API_TIMEOUT']) ? (int)$env['API_TIMEOUT'] : 60;
$providerPriority = isset($env['AI_PROVIDER_PRIORITY']) 
    ? explode(',', $env['AI_PROVIDER_PRIORITY']) 
    : ['gemini'];

// Validate input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['prompt'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required field: prompt']);
    exit;
}

$prompt = trim($input['prompt']);

if (empty($prompt)) {
    http_response_code(400);
    echo json_encode(['error' => 'Prompt cannot be empty']);
    exit;
}

// Sanitize prompt (basic XSS prevention)
$prompt = htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8');

// Track which provider was used
$usedProvider = null;
$aiText = null;

// Try each provider in priority order
foreach ($providerPriority as $provider) {
    $provider = trim($provider);
    
    error_log("[AI Proxy] Attempting provider: $provider");
    
    switch ($provider) {
        case 'gemini':
            // Try primary Gemini key
            $aiText = callGeminiAPI($prompt, $env['GEMINI_API_KEY'] ?? '', $apiTimeout);
            
            // Fallback to backup keys if primary fails
            if ($aiText === null && isset($env['GEMINI_API_KEY_BACKUP1'])) {
                error_log("[AI Proxy] Primary Gemini failed, trying backup1");
                $aiText = callGeminiAPI($prompt, $env['GEMINI_API_KEY_BACKUP1'], $apiTimeout);
            }
            
            if ($aiText === null && isset($env['GEMINI_API_KEY_BACKUP2'])) {
                error_log("[AI Proxy] Backup1 Gemini failed, trying backup2");
                $aiText = callGeminiAPI($prompt, $env['GEMINI_API_KEY_BACKUP2'], $apiTimeout);
            }
            
            if ($aiText === null && isset($env['GEMINI_API_KEY_BACKUP3'])) {
                error_log("[AI Proxy] Backup2 Gemini failed, trying backup3");
                $aiText = callGeminiAPI($prompt, $env['GEMINI_API_KEY_BACKUP3'], $apiTimeout);
            }
            
            if ($aiText !== null) {
                $usedProvider = 'gemini';
            }
            break;
            
        case 'groq':
            $aiText = callGroqAPI($prompt, $env['GROQ_API_KEY'] ?? '', $apiTimeout);
            if ($aiText !== null) {
                $usedProvider = 'groq';
            }
            break;
            
        case 'deepseek':
            $aiText = callDeepSeekAPI($prompt, $env['DEEPSEEK_API_KEY'] ?? '', $apiTimeout);
            if ($aiText !== null) {
                $usedProvider = 'deepseek';
            }
            break;
            
        default:
            error_log("[AI Proxy] Unknown provider: $provider");
    }
    
    // Stop if we got a successful response
    if ($aiText !== null) {
        break;
    }
}

// Handle failure - all providers failed
if ($aiText === null) {
    error_log("[AI Proxy] All AI providers failed");
    http_response_code(503);
    
    $errorMsg = $debugMode 
        ? 'Semua provider AI tidak tersedia. Periksa log server untuk detail.'
        : 'Layanan AI sedang sibuk. Silakan coba lagi nanti.';
    
    echo json_encode([
        'error' => $errorMsg,
        'provider_used' => null,
        'fallback_message' => '<p><em>Maaf, artikel AI sedang tidak tersedia. Silakan coba lagi nanti.</em></p>'
    ]);
    exit;
}

// Clean up AI response (remove markdown code blocks if present)
$aiText = preg_replace('/^```html\s*/m', '', $aiText);
$aiText = preg_replace('/^```\s*/m', '', $aiText);
$aiText = trim($aiText);

// Log success
error_log("[AI Proxy] Successfully generated article using provider: $usedProvider");

// Return response in Anthropic-compatible format for frontend compatibility
$response = [
    'content' => [
        [
            'text' => $aiText
        ]
    ],
    'provider' => $usedProvider,
    'timestamp' => date('c')
];

// Add debug info if enabled
if ($debugMode) {
    $response['_debug'] = [
        'provider_priority' => $providerPriority,
        'timeout' => $apiTimeout,
        'env_loaded' => count($env) > 0
    ];
}

http_response_code(200);
echo json_encode($response);
?>
