<?php
session_start();
// المسار الصحيح للوصول إلى الإعدادات بناءً على مجلد Bank
require_once '../Shared/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../User_Registration&Login/login_view.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";

// جلب حسابات المستخدم - استخدام HEX() و UNHEX() للتعامل مع BINARY(16)
$stmt_acc = $pdo->prepare("SELECT HEX(account_id) as account_id_hex, currency, balance FROM accounts WHERE user_id = UNHEX(REPLACE(?, '-', ''))");
$stmt_acc->execute([$user_id]);
$accounts = $stmt_acc->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['voucher_code'])) {
    $input_code = strtoupper(trim($_POST['voucher_code']));
    $target_account_hex = $_POST['account_id']; // القيمة القادمة هي HEX

    // 1. التحقق من الكود وجلب بياناته مع بيانات الحساب المختار للتأكد من العملة
    // قمنا بربط الجدولين في الاستعلام لضمان الدقة البرمجية
    $stmt = $pdo->prepare("
        SELECT v.*, a.currency as acc_currency 
        FROM bank_vouchers v, accounts a 
        WHERE v.voucher_code = ? 
        AND a.account_id = UNHEX(?) 
        AND v.is_used = 0
    ");
    $stmt->execute([$input_code, $target_account_hex]);
    $voucher_data = $stmt->fetch();

    if ($voucher_data) {
        // 2. صمام الأمان: منع شحن الكود إذا كانت عملته تختلف عن عملة الحساب
        if ($voucher_data['currency'] !== $voucher_data['acc_currency']) {
            $error = "عذراً! الكود مخصص لعملة (" . $voucher_data['currency'] . ") بينما الحساب المختار عملته (" . $voucher_data['acc_currency'] . ").";
        } else {
            try {
                $pdo->beginTransaction();

                // 3. تحديث الرصيد (الآن نحن متأكدون أن العملات متطابقة)
                $upd = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_id = UNHEX(?)");
                $upd->execute([$voucher_data['amount'], $target_account_hex]);

                // 4. إتلاف الكود لضمان عدم استخدامه مرة أخرى
                $mark = $pdo->prepare("UPDATE bank_vouchers SET is_used = 1 WHERE voucher_id = ?");
                $mark->execute([$voucher_data['voucher_id']]);

                // 5. تسجيل العملية في سجلات البنك الافتراضي
                $transaction_id = bin2hex(random_bytes(16));
                $log = $pdo->prepare("INSERT INTO virtual_bank_transactions (transaction_id, account_id, amount, transaction_type, status) VALUES (UNHEX(?), UNHEX(?), ?, 'TOP_UP', 'completed')");
                $log->execute([$transaction_id, $target_account_hex, $voucher_data['amount']]);

                $pdo->commit();
                header("Location: ../User_Registration&Login/dashboard.php?status=success");
                exit();

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "خطأ قاعدة بيانات: " . $e->getMessage();
            }
        }
    } else {
        $error = "عذراً، الكود غير صحيح أو تم استخدامه مسبقاً.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بوابة الشحن - Yemen Gate</title>
    <style>
        body { background: #0b1220; color: white; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .payment-card { background: #1e293b; padding: 40px; border-radius: 20px; width: 420px; text-align: center; border: 1px solid rgba(6,182,212,0.3); }
        input, select { width: 100%; padding: 15px; margin: 10px 0; border-radius: 10px; border: 1px solid #334155; background: #0f172a; color: white; text-align: center; }
        .btn-pay { background: #06b6d4; color: #0b1220; border: none; padding: 15px; width: 100%; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 16px; }
        .error-msg { background: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 15px; border-radius: 8px; font-size: 13px; margin-bottom: 15px; border: 1px solid #ef4444; }
        .btn-back { background: #334155; color: #94a3b8; border: none; padding: 12px; width: 100%; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 14px; margin-top: 15px; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
    <div class="payment-card">
        <h3>شحن الحساب الآمن</h3>
        <p style="color: #94a3b8; font-size: 13px; margin-bottom: 20px;">يرجى التأكد من اختيار الحساب المطابق لعملة الكود</p>
        
        <?php if($error): ?> <div class="error-msg"><?= $error ?></div> <?php endif; ?>

        <form method="POST">
            <label style="font-size: 12px; color: #94a3b8; display: block; text-align: right;">اشحن إلى:</label>
            <select name="account_id" required>
                <?php foreach($accounts as $acc): ?>
                    <option value="<?= $acc['account_id_hex'] ?>">حساب (<?= $acc['currency'] ?>) - رصيد: <?= $acc['balance'] ?></option>
                <?php endforeach; ?>
            </select>
            
            <label style="font-size: 12px; color: #94a3b8; display: block; text-align: right; margin-top: 10px;">كود التفعيل:</label>
            <input type="text" name="voucher_code" placeholder="أدخل كود الشحن (12 رمزاً)" maxlength="12" required style="letter-spacing: 2px; font-family: monospace;">
            
            <button type="submit" class="btn-pay">تأكيد وتفعيل الرصيد</button>
        </form>

        <a href="../User_Registration&Login/dashboard.php" class="btn-back">العودة إلى الصفحة الرئيسية</a>
    </div>
</body>
</html>
