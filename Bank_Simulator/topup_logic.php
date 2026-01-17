<?php
session_start();
require_once '../Shared/config.php';

// 1. التحقق من تسجيل الدخول وصحة البيانات المرسلة
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: topup.php");
    exit();
}

$user_id    = $_SESSION['user_id'];
$account_id = $_POST['account_id']; // الحساب الذي اختاره المستخدم (يمني، سعودي، أو دولار)
$amount     = floatval($_POST['amount']);

if ($amount <= 0) {
    die("❌ خطأ: يجب إدخال مبلغ أكبر من صفر.");
}

try {
    // بدء عملية TRANSACTION لضمان سلامة البيانات ماليًا
    $pdo->beginTransaction();

    // 2. التحقق من ملكية الحساب (لضمان أن المستخدم يشحن حسابه الخاص فقط)
    $stmt_check = $pdo->prepare("SELECT account_id, currency FROM accounts WHERE account_id = ? AND user_id = UNHEX(?)");
    $stmt_check->execute([$account_id, $user_id]);
    $account_data = $stmt_check->fetch();

    if (!$account_data) {
        throw new Exception("عذراً، الحساب المستهدف غير موجود أو لا يخصك.");
    }

    // 3. تحديث الرصيد (إضافة المبلغ المشحون)
    $stmt_update = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_id = ?");
    $stmt_update->execute([$amount, $account_id]);

    // 4. تسجيل العملية في جدول المعاملات (للأرشفة والشفافية)
    // نستخدم نوع 'deposit' للإشارة إلى عملية إيداع/شحن
    $stmt_log = $pdo->prepare("
        INSERT INTO virtual_bank_transactions (account_id, amount, status, type, created_at) 
        VALUES (?, ?, 'completed', 'deposit', NOW())
    ");
    $stmt_log->execute([$account_id, $amount]);

    // تثبيت العملية
    $pdo->commit();

    // 5. التوجيه لصفحة النجاح (لوحة التحكم) مع إشعار بالنجاح
    header("Location: ../User_Registration&Login/dashboard.php?topup=success&amount=" . $amount . "&cur=" . $account_data['currency']);
    exit();

} catch (Exception $e) {
    // في حال حدوث أي خطأ، يتم التراجع عن كل شيء في قاعدة البيانات
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // إظهار رسالة خطأ احترافية
    echo "<div style='background:#fff1f2; color:#be123c; padding:30px; font-family:Tajawal, sans-serif; border-radius:15px; text-align:center; margin:50px auto; max-width:450px; border:1px solid #fda4af; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);'>
            <i class='fa-solid fa-circle-xmark' style='font-size:40px; margin-bottom:15px;'></i>
            <h2 style='margin:0;'>فشلت عملية الشحن</h2>
            <p style='font-size:16px;'>" . $e->getMessage() . "</p>
            <a href='virtual_bank_gateway.php' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#be123c; color:white; text-decoration:none; border-radius:8px;'>العودة ومحاولة مرة أخرى</a>
          </div>";
}