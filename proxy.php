<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['StatusCode' => 405, 'Message' => 'طريقة الطلب غير مسموحة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

$provider = isset($inputData['provider']) ? trim($inputData['provider']) : '';
$mobNumber = isset($inputData['phoneNumber']) ? trim($inputData['phoneNumber']) : (isset($inputData['MobNumber']) ? trim($inputData['MobNumber']) : (isset($inputData['number']) ? trim($inputData['number']) : ''));

if (empty($mobNumber) || !preg_match('/^05\d{8}$/', $mobNumber)) {
    http_response_code(400);
    echo json_encode(['StatusCode' => 400, 'Message' => 'يرجى إدخال رقم هاتف صحيح يبدأ بـ 05 ومكون من 10 أرقام.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($provider === 'sky') {
    // توجيه الطلب إلى سيرفر سكاي مباشرة
    $targetUrl = 'https://rn.sky-5g.net/api/Subscriptions/CustomersRefreshNumber/New';
    $payload   = json_encode(['phoneNumber' => $mobNumber, 'number' => $mobNumber]);
    $headers   = [
        'Content-Type: application/json',
        'Accept: application/json, text/plain, */*',
        'Origin: https://rn.sky-5g.net',
        'Referer: https://rn.sky-5g.net/',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ];
} elseif ($provider === 'quickline') {
    $targetUrl = 'https://api.quicklinecrm.com/api/Subscriptions/CustomersRefreshNumber/New';
    $payload   = json_encode(['phoneNumber' => $mobNumber]);
    $headers   = [
        'Content-Type: application/json',
        'Accept: application/json, text/plain, */*',
        'Language: ar',
        'Origin: https://rn.quicklinecrm.com',
        'Referer: https://rn.quicklinecrm.com/',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ];
} else {
    // أرين
    $targetUrl = 'https://api.areen.net/api/common/RefreshMobileNumber';
    $payload   = json_encode(['MobNumber' => $mobNumber]);
    $headers   = [
        'Content-Type: application/json',
        'Accept: application/json, text/plain, */*',
        'Origin: https://areen.net',
        'Referer: https://areen.net/',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ];
}

$ch = curl_init($targetUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['StatusCode' => 502, 'Message' => 'تعذر الاتصال بخادم التحديث.'], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(200);
$jsonTest = json_decode($response, true);
if ($jsonTest !== null) {
    echo $response;
} else {
    echo json_encode([
        'StatusCode' => ($httpCode >= 200 && $httpCode < 300) ? 200 : $httpCode,
        'Message'    => trim($response, "\" \t\n\r\0\x0B")
    ], JSON_UNESCAPED_UNICODE);
}
?>
