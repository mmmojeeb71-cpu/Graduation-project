<?php
session_start();
require_once '../Shared/config.php';

if (!isset($_SESSION['user_id'])) {
    die("انتهت الجلسة، يرجى تسجيل الدخول.");
}

// 1. استلام البيانات
$from_acc_hex = $_POST['from_account_hex'] ?? '';
$target_input = trim($_POST['target_account_number'] ?? ''); 
$amount = floatval($_POST['amount'] ?? 0);
$user_id_session = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // 2. جلب حساب المرسل
    $stmt_sender = $pdo->prepare("
        SELECT account_id, balance, currency 
        FROM accounts 
        WHERE account_id = UNHEX(?) 
        AND user_id = UNHEX(REPLACE(?, '-', ''))
    ");
    $stmt_sender->execute([$from_acc_hex, $user_id_session]);
    $sender = $stmt_sender->fetch();

    if (!$sender) {
        throw new Exception("sender_not_found");
    }

    if ($sender['balance'] < $amount || $amount <= 0) {
        throw new Exception("insufficient_balance");
    }

    // 3. البحث عن المستلم
    $stmt_receiver = $pdo->prepare("
        SELECT account_id, currency, account_number 
        FROM accounts 
        WHERE account_number = :exact 
        OR account_number LIKE :suffix
    ");
    $stmt_receiver->execute([
        'exact' => $target_input,
        'suffix' => '%' . $target_input
    ]);
    $receiver = $stmt_receiver->fetch();

    if (!$receiver) {
        throw new Exception("receiver_not_found");
    }

    // ⛔ الشرط الحاسم: التحقق من تطابق العملات
    if ($sender['currency'] !== $receiver['currency']) {
        // إعادة المستخدم مع معامل خطأ في الرابط بدلاً من القتل
        header("Location: transfer_view.php?error=currency_mismatch");
        exit();
    }

    // منع التحويل لنفس الحساب
    if ($sender['account_id'] === $receiver['account_id']) {
        throw new Exception("same_account");
    }

    // 4. تنفيذ العمليات
    $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE account_id = ?")
        ->execute([$amount, $sender['account_id']]);

    $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_id = ?")
        ->execute([$amount, $receiver['account_id']]);

    // 5. تسجيل المعاملة
    $log_sql = "INSERT INTO virtual_bank_transactions (account_id, amount, transaction_type, status, created_at) VALUES (?, ?, ?, 'COMPLETED', NOW())";
    
    $pdo->prepare($log_sql)->execute([$sender['account_id'], $amount, 'TRANSFER_OUT']);
    $pdo->prepare($log_sql)->execute([$receiver['account_id'], $amount, 'TRANSFER_IN']);

    $pdo->commit();
    
    header("Location: dashboard.php?transfer=success");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // إعادة المستخدم مع نوع الخطأ العام
    $errorType = $e->getMessage();
    header("Location: transfer_view.php?error=" . $errorType);
    exit();
}