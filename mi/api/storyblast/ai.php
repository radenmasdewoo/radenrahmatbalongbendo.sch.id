<?php
// StoryBlast AI Integration - Vision (Gambar & Frame Video)
require_once 'config_v2.php';

function handleAiRequest()
{
    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true);
    $prompt = $input['prompt'] ?? null;
    $imageBase64 = $input['image_base64'] ?? null;
    $imageMime = $input['image_mime'] ?? 'image/jpeg';

    $apiKey = 'AIzaSyBx_TWMv0MP51FBbCppRaj1MLCgWDkzaPs';
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;

    // Prompt standar untuk semua media
    $captionPrompt = "Kamu adalah social media manager MI Raden Rahmat Balongbendo. "
        . "Analisis gambar/frame ini dan buatkan:\n"
        . "1. Caption menarik dan kekinian untuk sosmed (FB, IG, WA) - max 150 kata, bahasa santai namun sopan dan Islami\n"
        . "2. Minimal 12 hashtag relevan terkait isi gambar, sekolah Islami, dan madrasah\n\n"
        . "Format output HARUS persis:\n"
        . "[CAPTION]\n(tulis caption di sini)\n\n"
        . "[HASHTAG]\n(tulis hashtag di sini)";

    // Bersihkan data-URL prefix jika ada
    if ($imageBase64 && strpos($imageBase64, ',') !== false) {
        $imageBase64 = explode(',', $imageBase64)[1];
    }

    // Susun request body
    if ($imageBase64) {
        // Multimodal: analisis gambar/frame
        $data = [
            "contents" => [[
                    "parts" => [
                        ["inline_data" => ["mime_type" => $imageMime, "data" => $imageBase64]],
                        ["text" => $captionPrompt]
                    ]
                ]]
        ];
    }
    else {
        // Text-only: tidak ada media diupload
        $textPrompt = $prompt ?? "Buat text caption menarik dan kekinian untuk sosmed (FB, IG, WA) "
            . "tentang sekolah MI Raden Rahmat Balongbendo.\n"
            . "Bahasanya santai namun sopan dan Islami, max 150 kata. Sertakan minimal 12 hashtag.\n\n"
            . "Format:\n[CAPTION]\n...\n\n[HASHTAG]\n...";

        $data = [
            "contents" => [[
                    "parts" => [["text" => $textPrompt]]
                ]]
        ];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'cURL Error: ' . $curlErr]);
        exit;
    }

    if ($httpcode === 200) {
        $resData = json_decode($response, true);
        $text = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Parse caption & hashtag
        $caption = trim($text);
        $hashtag = '';
        if (preg_match('/\[CAPTION\](.*?)(\[HASHTAG\]|$)/s', $text, $m)) {
            $caption = trim($m[1]);
        }
        if (preg_match('/\[HASHTAG\](.*?)$/s', $text, $m)) {
            $hashtag = trim($m[1]);
        }

        $fullCaption = $hashtag ? $caption . "\n\n" . $hashtag : $caption;

        echo json_encode([
            'success' => true,
            'caption' => $fullCaption,
            'caption_only' => $caption,
            'hashtag' => $hashtag
        ]);
    }
    else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Gagal generate caption. HTTP: ' . $httpcode,
            'error' => $response
        ]);
    }
    exit;
}