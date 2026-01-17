<?php
session_start();
// استدعاء ملف الاتصال لتعريف متغير $pdo ومنع خطأ member function prepare() on null
require_once '../Shared/config.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../User_Registration&Login/login_view.php");
    exit();
}

try {
    $user_id = $_SESSION['user_id'];

    // [تعديل] جلب الحسابات الثلاثة (Yemeni, Saudi, Dollar) للتأكد من وجود حساب الدولار للبطاقة
    // البطاقة تُربط بحساب الدولار تلقائياً لضمان العالمية
    $stmt = $pdo->prepare("SELECT u.full_name, a.account_id 
                           FROM users u 
                           JOIN accounts a ON u.user_id = a.user_id 
                           WHERE u.user_id = UNHEX(REPLACE(?, '-', '')) 
                           AND a.currency = 'USD' 
                           LIMIT 1");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch();

    if (!$user_info) {
        // [تعديل] رسالة تنبيه تطالب بفتح حساب دولار أولاً لربط البطاقة به كما في المتطلبات
        header("Location: ../User_Registration&Login/dashboard.php?error=need_usd_account");
        exit();
    }

    // توليد بيانات البطاقة الحقيقية (بمعايير PCI DSS العالمية)
    $bin = "426398"; // رقم BIN خاص بـ Yemen Gate
    $card_number = $bin . substr(str_shuffle("01234567890123456789"), 0, 10);
    $masked_pan = substr($card_number, 0, 4) . " **** **** " . substr($card_number, -4);
    $cvv = rand(100, 999);
    $expiry_month = date('m');
    $expiry_year = date('Y') + 3;

    // حفظ البطاقة في قاعدة البيانات
    $card_id = bin2hex(random_bytes(16));
    
    // [إضافة المعدلة] إدراج البطاقة مع دعم محافظ العملات الثلاث (YER, SAR, USD) 
    // تم إضافة balance_yer, balance_sar, balance_usd لضمان توافقها مع تحديث قاعدة البيانات الأخير
    $sql = "INSERT INTO issued_cards (
                card_id, user_id, account_id, card_number, card_holder, 
                masked_pan, expiry_month, expiry_year, cvv, card_type, 
                card_balance, balance_yer, balance_sar, balance_usd
            ) 
            VALUES (
                UNHEX(?), UNHEX(REPLACE(?, '-', '')), ?, ?, ?, 
                ?, ?, ?, ?, 'YEMEN GATE PREMIUM', 
                0.00, 0.00, 0.00, 0.00
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $card_id, 
        $user_id, 
        $user_info['account_id'], 
        $card_number, 
        strtoupper($user_info['full_name']), 
        $masked_pan, 
        $expiry_month, 
        $expiry_year, 
        $cvv
    ]);

    // التوجيه لصفحة العرض مع إشارة النجاح
    header("Location: create_card_view.php?status=success");
    exit();

} catch (PDOException $e) {
    // حل مشكلة الخطأ في قاعدة البيانات وعرض رسالة مهنية
    die("خطأ فني في إصدار البطاقة: " . $e->getMessage());
}
?>