if ($provider === 'sky' || $provider === 'سكاي') {
    // رابط الـ API الرسمي لاستعلام وتحديث الرقم في سكاي
    $targetUrl = 'https://rn.sky-5g.net/api/public/refresh';
    
    // البيانات المرسلة (رقم الهاتف مع رمز الكابتشا إن وجد، أو إرسال طلب فارغ للـ token)
    $payload   = json_encode([
        'phone_number' => $mobNumber,
        'captcha'      => isset($inputData['captcha']) ? $inputData['captcha'] : ''
    ]);
    
    // الترويسات (Headers) التي توهم سيرفر سكاي بأن الطلب قادم من موقعه الرسمي
    $headers   = [
        'Content-Type: application/json',
        'Accept: application/json, text/plain, */*',
        'Origin: https://rn.sky-5g.net',
        'Referer: https://rn.sky-5g.net/',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ];
}
