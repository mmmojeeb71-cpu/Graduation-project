<?php
session_start();
require_once '../Shared/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id_raw = $_SESSION['user_id'];
    $business_name = $_POST['business_name'];
    $currency = $_POST['settlement_currency'];
    $webhook = $_POST['webhook_url'];
    $merchant_type = $_POST['merchant_type'] ?? 'E-commerce';

    try {
        // 1. التحقق إذا كان المستخدم تاجر بالفعل [cite: 2026-01-13]
        $stmt_check = $pdo->prepare("SELECT user_id FROM merchants WHERE user_id = UNHEX(REPLACE(?, '-', ''))");
        $stmt_check->execute([$user_id_raw]);
        $exists = $stmt_check->fetch();

        if ($exists) {
            // تحديث البيانات الحالية للتاجر [cite: 2026-01-13]
            $sql = "UPDATE merchants SET business_name = ?, settlement_currency = ?, webhook_url = ?, merchant_type = ? 
                    WHERE user_id = UNHEX(REPLACE(?, '-', ''))";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$business_name, $currency, $webhook, $merchant_type, $user_id_raw]);
            
            // التوجيه لصفحة الإعدادات مع إشعار التحديث
            header("Location: become_merchant.php?status=updated");
            exit();
        } else {
            // إنشاء حساب تاجر جديد كلياً وتوليد مفاتيح الوصول [cite: 2026-01-13]
            $merchant_id = bin2hex(random_bytes(16)); // معرف فريد (UUID)
            $api_key = "YG_LIVE_" . strtoupper(bin2hex(random_bytes(12))); // مفتاح API حقيقي للمتجر
            
            $sql = "INSERT INTO merchants (merchant_id, user_id, business_name, settlement_currency, webhook_url, merchant_type, api_key) 
                    VALUES (UNHEX(?), UNHEX(REPLACE(?, '-', '')), ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$merchant_id, $user_id_raw, $business_name, $currency, $webhook, $merchant_type, $api_key]);

            // التوجيه مباشرة لصفحة "أدوات الربط" لعرض كود الـ HTML والمفتاح الجديد [cite: 2026-01-13]
            header("Location: generate_integration.php?welcome=true");
            exit();
        }

    } catch (Exception $e) {
        // التعامل مع الأخطاء بشكل احترافي
        error_log("Merchant Error: " . $e->getMessage());
        die("❌ حدث خطأ تقني أثناء معالجة الطلب. يرجى المحاولة لاحقاً.");
    }
} else {
    header("Location: become_merchant.php");
    exit();
}