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
    echo json_encode([
        'StatusCode' => 405,
        'Message' => 'طريقة الطلب غير مسموحة. يرجى استخدام POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

// تحديد الشركة تلقائياً بناءً على اسم الحقل المرسل
$isQuickLine = isset($inputData['phoneNumber']) || (isset($inputData['provider']) && $inputData['provider'] === 'quickline');
$mobNumber = '';

if ($isQuickLine) {
    $mobNumber = isset($inputData['phoneNumber']) ? trim($inputData['phoneNumber']) : '';
} else {
    $mobNumber = isset($inputData['MobNumber']) ? trim($inputData['MobNumber']) : '';
}

// التحقق من صحة الرقم (10 أرقام يبدأ بـ 05)
if (empty($mobNumber) || !preg_match('/^05\d{8}$/', $mobNumber)) {
    http_response_code(400);
    echo json_encode([
        'StatusCode' => 400,
        'Message' => 'يرجى إدخال رقم هاتف صحيح مكون من 10 أرقام ويبدأ بـ 05'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// إعداد بيانات الاتصال حسب الشركة
if ($isQuickLine) {
    $targetUrl = 'https://rn.quicklinecrm.com/Subscriptions/CustomersRefreshNumber/New';
    $payload   = json_encode(['phoneNumber' => $mobNumber]);
    $origin    = 'https://rn.quicklinecrm.com';
    $referer   = 'https://rn.quicklinecrm.com/';
} else {
    $targetUrl = 'https://api.areen.net/api/common/RefreshMobileNumber';
    $payload   = json_encode(['MobNumber' => $mobNumber]);
    $origin    = 'https://areen.net';
    $referer   = 'https://areen.net/';
}

$ch = curl_init($targetUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_ENCODING       => '',
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json, text/plain, */*',
        'Content-Length: ' . strlen($payload),
        'Origin: ' . $origin,
        'Referer: ' . $referer,
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ],
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'StatusCode' => 502,
        'Message' => 'تعذر الاتصال بخادم التحديث: ' . $curlError
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// إذا كانت الاستجابة JSON صالحة (مثل أرين أو كويك لاين)
$decoded = json_decode($response, true);
if ($decoded !== null || json_last_error() === JSON_ERROR_NONE) {
    http_response_code($httpCode ?: 200);
    echo $response;
    exit;
}

// في حال أعاد كويك لاين نصاً عادياً (Plain Text) بدلاً من JSON
http_response_code($httpCode ?: 200);
echo json_encode([
    'StatusCode' => $httpCode ?: 200,
    'Message'    => trim(strip_tags($response)) ?: ($httpCode == 200 ? 'تم تحديث الرقم بنجاح' : 'فشل تحديث الرقم')
], JSON_UNESCAPED_UNICODE);
?>
