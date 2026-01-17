<?php
/**
 * Yemen Gate - Real-World Registration Logic
 * معايير عالمية: إنشاء 3 حسابات تلقائية برصيد 0.00
 */

require_once '../Shared/config.php';
require_once '../Shared/auth_functions.php'; // لفنكشنات التشفير والـ UUID

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email     = $_POST['email'];
    $password  = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        // بدء عملية برمجية موحدة (Transaction) لضمان سلامة البيانات المالية
        $pdo->beginTransaction();

        // 1. توليد معرف فريد للمستخدم
        $user_id_raw = bin2hex(random_bytes(16));
        $user_id = hex2bin($user_id_raw);

        // 2. إدخال المستخدم في جدول users
        $stmt = $pdo->prepare("INSERT INTO users (user_id, full_name, email, password_hash, role) VALUES (?, ?, ?, ?, 'user')");
        $stmt->execute([$user_id, $full_name, $email, $password]);

        // 3. إنشاء الحسابات الثلاثة (USD, SAR, YER) برصيد 0.00
        $currencies = ['USD', 'SAR', 'YER'];
        
        foreach ($currencies as $curr) {
            $acc_id = random_bytes(16);
            
            // توليد رقم حساب فريد (مثل: YG-USD-12345678)
            $acc_num = "YG-" . $curr . "-" . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

            $acc_stmt = $pdo->prepare("INSERT INTO accounts (account_id, user_id, currency, balance, account_number) VALUES (?, ?, ?, 0.00, ?)");
            $acc_stmt->execute([$acc_id, $user_id, $curr, $acc_num]);
        }

        // 4. تسجيل العملية في سجل الأمان (Audit Logs)
        $log_id = random_bytes(16);
        $log_stmt = $pdo->prepare("INSERT INTO audit_logs (log_id, user_id, action_type, action_details, ip_address) VALUES (?, ?, 'REGISTER', ?, ?)");
        $log_stmt->execute([$log_id, $user_id, "تم إنشاء مستخدم جديد مع 3 حسابات بنكية", $_SERVER['REMOTE_ADDR']]);

        // تأكيد كافة العمليات بنجاح
        $pdo->commit();

        // التوجيه للوحة التحكم بعد النجاح [cite: 2026-01-13]
        header("Location: login_view.php?success=1");
        exit;

    } catch (Exception $e) {
        // في حال حدوث أي خطأ، يتم التراجع عن كل شيء (Rollback) لمنع الحسابات الناقصة
        $pdo->rollBack();
        die("❌ خطأ فني في التسجيل: " . $e->getMessage());
    }
}