<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['action']) || $_POST['action'] !== 'refresh') {
    echo json_encode(["status" => "error", "message" => "طلب غير مصرح به."]);
    exit;
}

$phone = trim($_POST['phone'] ?? '');

if (empty($phone) || !preg_match('/^05[0-9]{8}$/', $phone)) {
    echo json_encode([
        "status" => "error",
        "message" => "يرجى إدخال رقم هاتف صحيح يبدأ بـ 05 ويتكون من 10 أرقام."
    ]);
    exit;
}

$target_url = "https://rn.layan-t.net/Subscribtions/CustomersRefreshNumber";
$payload = json_encode(["number" => $phone]);

$ch = curl_init($target_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json, text/javascript, */*; q=0.01',
    'Origin: https://rn.layan-t.net',
    'Referer: https://rn.layan-t.net/',
    'User-Agent: Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code >= 200 && $http_code < 300) {
    echo json_encode([
        "status" => "success",
        "message" => "تم إرسال طلب تحديث حزمة التصفح للرقم بنجاح!"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => !empty($response) ? strip_tags($response) : "تعذر تحديث الرقم، تأكد من صحة الرقم أو حاول لاحقاً."
    ]);
}
exit;
