<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'refresh') {
    header('Content-Type: application/json; charset=utf-8');
    
    $phone = trim($_POST['phone'] ?? '');

    if (empty($phone) || !preg_match('/^05[0-9]{8}$/', $phone)) {
        echo json_encode([
            "status" => "error",
            "message" => "يرجى إدخال رقم هاتف صحيح يبدأ بـ 05 ويتكون من 10 أرقام."
        ]);
        exit;
    }

    $main_url = "https://rn.layan-t.net/";
    $target_url = "https://rn.layan-t.net/Subscribtions/CustomersRefreshNumber";
    $user_agent = "Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36";
    
    // ملف مؤقت لحفظ الكوكيز والجلسة
    $cookie_file = tempnam(sys_get_temp_dir(), 'layan_cookie_');

    // 1. زيارة الصفحة الرئيسية أولاً لفتح جلسة وسحب رمز الحماية إن وجد
    $ch = curl_init($main_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $home_html = curl_exec($ch);
    curl_close($ch);

    // استخراج __RequestVerificationToken إن كان موجوداً في الصفحة
    $post_fields = ['number' => $phone];
    if (preg_match('/name="__RequestVerificationToken"\s+type="hidden"\s+value="([^"]+)"/i', $home_html, $matches)) {
        $post_fields['__RequestVerificationToken'] = $matches[1];
    }

    // 2. إرسال طلب تحديث الرقم مع الكوكيز والترويسات الصحيحة
    $ch = curl_init($target_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        'Accept: application/json, text/javascript, */*; q=0.01',
        'X-Requested-With: XMLHttpRequest',
        'Origin: https://rn.layan-t.net',
        'Referer: https://rn.layan-t.net/',
        'User-Agent: ' . $user_agent
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // حذف ملف الكوكيز المؤقت
    if (file_exists($cookie_file)) {
        @unlink($cookie_file);
    }

    // التحقق من أخطاء الاتصال في السيرفر
    if ($response === false) {
        echo json_encode([
            "status" => "error",
            "message" => "خطأ اتصال من الاستضافة: " . $curl_error
        ]);
        exit;
    }

    // قراءة رد موقع ليان الفعلي
    $json_res = json_decode($response, true);

    if ($http_code >= 200 && $http_code < 300) {
        // إذا كان موقع ليان يرجع حالة خطأ داخل الـ JSON نفسه
        if (is_array($json_res) && isset($json_res['success']) && $json_res['success'] === false) {
            echo json_encode([
                "status" => "error",
                "message" => $json_res['message'] ?? "رفض النظام تحديث هذا الرقم."
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "message" => $json_res['message'] ?? "تم إرسال طلب تحديث حزمة التصفح للرقم بنجاح!"
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "فشل التحديث (كود $http_code): " . (!empty($response) ? mb_substr(strip_tags($response), 0, 150) : "تأكد من صحة الرقم أو حاول لاحقاً.")
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>BRAND SIM - تحديث الرقم</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #f5f6fa;
            font-family: 'Tajawal', system-ui, -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            width: 100%;
            max-width: 380px;
            padding: 50px 28px 45px;
            border-radius: 22px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
            text-align: center;
        }
        .brand-mark {
            width: 28px;
            height: 8px;
            background: linear-gradient(135deg, #a51c74, #c2185b);
            border-radius: 10px 2px 2px 2px;
            margin: 0 auto 40px;
            transform: rotate(-10deg);
        }
        .title { color: #1a2533; font-size: 22px; font-weight: 700; margin-bottom: 12px; }
        .subtitle { color: #9aa5b1; font-size: 14px; font-weight: 500; margin-bottom: 32px; }
        .input-phone {
            width: 100%;
            height: 56px;
            font-size: 19px;
            font-family: inherit;
            text-align: center;
            letter-spacing: 3px;
            color: #2d3748;
            background-color: #ffffff;
            border: 1.5px solid #9c2770;
            border-radius: 12px;
            outline: none;
            margin-bottom: 18px;
            direction: ltr;
        }
        .btn-submit {
            width: 100%;
            height: 54px;
            background-color: #a51c74;
            color: #ffffff;
            font-family: inherit;
            font-size: 17px;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2.5px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .alert-box {
            display: none;
            margin-top: 20px;
            padding: 12px 15px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.5;
        }
        .alert-success { background-color: #edf7ed; color: #1e4620; border: 1px solid #c8e6c9; }
        .alert-error { background-color: #fdeded; color: #5f2120; border: 1px solid #ffcdd2; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand-mark"></div>
        <h1 class="title">BRAND SIM - تحديث الرقم</h1>
        <p class="subtitle">أدخل رقم الهاتف لتحديث حزمة التصفح</p>

        <form id="refreshForm">
            <input type="tel" id="phoneInput" name="phone" class="input-phone" placeholder="05XXXXXXXX" inputmode="numeric" maxlength="10" required autocomplete="off">
            <button type="submit" id="submitBtn" class="btn-submit">
                <span id="btnText">تحديث</span>
                <div class="spinner" id="btnSpinner"></div>
            </button>
        </form>
        <div id="alertBox" class="alert-box"></div>
    </div>

    <script>
        const form = document.getElementById('refreshForm');
        const phoneInput = document.getElementById('phoneInput');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        const alertBox = document.getElementById('alertBox');

        phoneInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            alertBox.style.display = 'none';

            const phone = phoneInput.value.trim();
            if (!/^05[0-9]{8}$/.test(phone)) {
                showAlert('يرجى إدخال رقم هاتف صحيح مكون من 10 أرقام ويبدأ بـ 05', 'error');
                return;
            }

            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'block';

            try {
                const formData = new FormData();
                formData.append('action', 'refresh');
                formData.append('phone', phone);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                showAlert(result.message, result.status);
                
                if (result.status === 'success') {
                    phoneInput.value = '';
                }
            } catch (error) {
                showAlert('حدث خطأ في الاتصال، تأكد من أن امتداد الملف .php وليس .html', 'error');
            } finally {
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                btnSpinner.style.display = 'none';
            }
        });

        function showAlert(message, type) {
            alertBox.textContent = message;
            alertBox.className = 'alert-box ' + (type === 'success' ? 'alert-success' : 'alert-error');
            alertBox.style.display = 'block';
        }
    </script>
</body>
</html>
