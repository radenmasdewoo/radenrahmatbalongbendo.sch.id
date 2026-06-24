<?php
// StoryBlast Gemini AI Integration

function handleGeminiRequest()
{
    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true);
    $prompt = $input['prompt'] ?? "Buat text caption menarik dan kekinian untuk upload sosmed fb IG dan WA tentang kegiatan sekolah madrasah ibtidaiyah raden rahmat balongbendo. Sertakan hashtag relevan.";

    $apiKey = 'AIzaSyBx_TWMv0MP51FBbCppRaj1MLCgWDkzaPs';
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode === 200) {
        $resData = json_decode($response, true);
        $text = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
        echo json_encode(['success' => true, 'caption' => trim($text)]);
    }
    else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to generate content', 'error' => $response]);
    }
    exit;
}