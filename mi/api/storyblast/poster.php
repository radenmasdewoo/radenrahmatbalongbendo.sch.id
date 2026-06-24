<?php
// StoryBlast Posting Logic

require_once 'config.php';
require_once 'logger.php';

function postToFacebook($caption, $mediaUrl) {
    $endpoint = "https://graph.facebook.com/" . FB_API_VERSION . "/" . FB_PAGE_ID . ($mediaUrl ? "/photos" : "/feed");
    
    $data = [
        'access_token' => FB_PAGE_ACCESS_TOKEN
    ];

    if ($mediaUrl) {
        $data['url'] = $mediaUrl;
        $data['caption'] = $caption;
    } else {
        $data['message'] = $caption;
    }

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $response = curl_exec($ch);
    $resData = json_decode($response, true);
    curl_close($ch);

    if (isset($resData['id'])) {
        return ['platform' => 'facebook', 'status' => 'success', 'post_id' => $resData['id']];
    } else {
        return ['platform' => 'facebook', 'status' => 'error', 'message' => $resData['error']['message'] ?? 'Unknown error'];
    }
}

function postToInstagram($caption, $mediaUrl) {
    if (!$mediaUrl) {
        return ['platform' => 'instagram', 'status' => 'error', 'message' => 'IG requires media URL'];
    }

    // Step 1: Create media container
    $endpoint = "https://graph.facebook.com/" . FB_API_VERSION . "/" . IG_USER_ID . "/media";
    $data = [
        'image_url' => $mediaUrl,
        'caption' => $caption,
        'access_token' => IG_ACCESS_TOKEN
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $response = curl_exec($ch);
    $resData = json_decode($response, true);
    curl_close($ch);

    if (!isset($resData['id'])) {
        return ['platform' => 'instagram', 'status' => 'error', 'message' => 'Container creation failed: ' . ($resData['error']['message'] ?? 'Unknown error')];
    }

    $creationId = $resData['id'];

    // Step 2: Publish media
    $pubEndpoint = "https://graph.facebook.com/" . FB_API_VERSION . "/" . IG_USER_ID . "/media_publish";
    $pubData = [
        'creation_id' => $creationId,
        'access_token' => IG_ACCESS_TOKEN
    ];

    $ch = curl_init($pubEndpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($pubData));
    $pubResponse = curl_exec($ch);
    $pubResData = json_decode($pubResponse, true);
    curl_close($ch);

    if (isset($pubResData['id'])) {
        return ['platform' => 'instagram', 'status' => 'success', 'post_id' => $pubResData['id']];
    } else {
        return ['platform' => 'instagram', 'status' => 'error', 'message' => 'Publish failed: ' . ($pubResData['error']['message'] ?? 'Unknown error')];
    }
}

function postToWhatsApp($caption, $mediaUrl) {
    $numbers = explode(',', WA_TARGET_NUMBERS);
    $successCount = 0;
    $errors = [];

    foreach ($numbers as $number) {
        $number = trim($number);
        $endpoint = "https://graph.facebook.com/" . FB_API_VERSION . "/" . WA_PHONE_NUMBER_ID . "/messages";
        
        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $number,
            'type' => $mediaUrl ? 'image' : 'text'
        ];

        if ($mediaUrl) {
            $body['image'] = ['link' => $mediaUrl, 'caption' => $caption];
        } else {
            $body['text'] = ['body' => $caption];
        }

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . WA_ACCESS_TOKEN]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $response = curl_exec($ch);
        $resData = json_decode($response, true);
        curl_close($ch);

        if (isset($resData['messages'])) {
            $successCount++;
        } else {
            $errors[] = $resData['error']['message'] ?? 'Unknown error';
        }
    }

    if ($successCount > 0) {
        return ['platform' => 'whatsapp', 'status' => 'success', 'message' => "Sent to $successCount numbers", 'errors' => $errors];
    } else {
        return ['platform' => 'whatsapp', 'status' => 'error', 'message' => 'Failed to send to any number', 'errors' => $errors];
    }
}

function handlePostStory() {
    $caption = $_POST['caption'] ?? '';
    $platformsJson = $_POST['platforms'] ?? '[]';
    $platforms = json_decode($platformsJson, true) ?: [];
    
    $mediaUrl = null;
    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . rand(100, 999) . '.' . $ext;
        $targetPath = UPLOAD_DIR . $filename;
        if (move_uploaded_file($_FILES['media']['tmp_name'], $targetPath)) {
            $mediaUrl = "https://" . $_SERVER['HTTP_HOST'] . "/mi/api/storyblast/uploads/" . $filename;
        }
    }

    $results = [];
    foreach ($platforms as $platform) {
        if ($platform === 'facebook') $results[] = postToFacebook($caption, $mediaUrl);
        if ($platform === 'instagram') $results[] = postToInstagram($caption, $mediaUrl);
        if ($platform === 'whatsapp') $results[] = postToWhatsApp($caption, $mediaUrl);
    }

    saveLog($caption, $platforms, $results);
    echo json_encode(['results' => $results]);
    exit;
}
