<?php
/**
 * Yemen Gate - International Transfer Engine
 * نظام التحويل عبر رقم الحساب مع تدقيق العملة
 */

require_once '../Shared/config.php';
require_once '../Shared/auth_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_user_id = $_SESSION['user_id']; // معرف المرسل من الجلسة
    $receiver_acc_num = $_POST['receiver_account_number']; // رقم حساب المستلم
    $amount = floatval($_POST['amount']);
    $currency = $_POST['currency']; // USD, SAR, or YER

    try {
        $pdo->beginTransaction();

        // 1. التحقق من حساب المرسل وصحة العملة والرصيد
        $stmt_sender = $pdo->prepare("SELECT account_id, balance FROM accounts WHERE user_id = ? AND currency = ?");
        $stmt_sender->execute([$sender_user_id, $currency]);
        $sender_acc = $stmt_sender->fetch();

        if (!$sender_acc || $sender_acc['balance'] < $amount) {
            throw new Exception("رصيدك غير كافٍ في حساب $currency أو الحساب غير موجود.");
        }

        // 2. التحقق من وجود حساب المستلم (يجب أن يطابق نفس العملة)
        $stmt_receiver = $pdo->prepare("SELECT account_id FROM accounts WHERE account_number = ? AND currency = ?");
        $stmt_receiver->execute([$receiver_acc_num, $currency]);
        $receiver_acc = $stmt_receiver->fetch();

        if (!$receiver_acc) {
            throw new Exception("رقم الحساب المستلم غير صحيح أو لا يدعم عملة $currency.");
        }

        if ($sender_acc['account_id'] === $receiver_acc['account_id']) {
            throw new Exception("لا يمكنك التحويل لنفس الحساب.");
        }

        // 3. تنفيذ عملية الخصم والإضافة (Atomic Updates)
        $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE account_id = ?")->execute([$amount, $sender_acc['account_id']]);
        $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_id = ?")->execute([$amount, $receiver_acc['account_id']]);

        // 4. تسجيل العملية في جدول التحويلات (Reference Number)
        $ref_num = "TRX-" . strtoupper(bin2hex(random_bytes(6)));
        $stmt_trans = $pdo->prepare("INSERT INTO transactions (transaction_id, sender_account_id, receiver_account_id, amount, currency, transaction_type, reference_number) VALUES (UUID_TO_BIN(UUID()), ?, ?, ?, ?, 'TRANSFER', ?)");
        $stmt_trans->execute([$sender_acc['account_id'], $receiver_acc['account_id'], $amount, $currency, $ref_num]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'تم التحويل بنجاح. رقم المرجع: ' . $ref_num]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}