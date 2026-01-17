<?php
session_start();
require_once '../Shared/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../User_Registration&Login/login_view.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success_msg = "";

// جلب الحسابات المتاحة للسحب
$stmt_acc = $pdo->prepare("SELECT HEX(account_id) as acc_hex, currency, balance FROM accounts WHERE user_id = UNHEX(REPLACE(?, '-', ''))");
$stmt_acc->execute([$user_id]);
$accounts = $stmt_acc->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['amount'])) {
    $amount = (float)$_POST['amount'];
    $target_acc_hex = $_POST['account_id'];

    // 1. التحقق من وجود رصيد كافٍ في الحساب المختار
    $stmt_check = $pdo->prepare("SELECT balance, currency FROM accounts WHERE account_id = UNHEX(?) AND user_id = UNHEX(REPLACE(?, '-', ''))");
    $stmt_check->execute([$target_acc_hex, $user_id]);
    $account = $stmt_check->fetch();

    if ($account && $account['balance'] >= $amount) {
        if ($amount <= 0) {
            $error = "يرجى إدخال مبلغ صحيح للسحب.";
        } else {
            try {
                $pdo->beginTransaction();

                // 2. خصم المبلغ من حساب المستخدم
                $deduct = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE account_id = UNHEX(?)");
                $deduct->execute([$amount, $target_acc_hex]);

                // 3. توليد كود سحب (Voucher) ليتمكن البنك من صرفه للعميل
                $withdraw_code = "WD-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
                
                $ins_voucher = $pdo->prepare("INSERT INTO bank_vouchers (voucher_code, amount, currency, is_used) VALUES (?, ?, ?, 0)");
                $ins_voucher->execute([$withdraw_code, $amount, $account['currency']]);

                // 4. تسجيل العملية في سجل الحساب
                $trans_id = bin2hex(random_bytes(16));
                $log = $pdo->prepare("INSERT INTO virtual_bank_transactions (transaction_id, account_id, amount, transaction_type, status) VALUES (UNHEX(?), UNHEX(?), ?, 'WITHDRAW', 'completed')");
                $log->execute([$trans_id, $target_acc_hex, $amount]);

                $pdo->commit();
                $success_msg = "تمت عملية السحب بنجاح! كود الصرف الخاص بك هو: <b style='font-size:20px; color:#06b6d4;'>$withdraw_code</b>. قدم هذا الكود للبنك لاستلام مبلغك.";

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "حدث خطأ أثناء المعالجة: " . $e->getMessage();
            }
        }
    } else {
        $error = "عذراً، رصيدك غير كافٍ لإتمام عملية السحب.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سحب الأموال - Yemen Gate</title>
    <style>
        body { background: #0b1220; color: white; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .withdraw-card { background: #1e293b; padding: 40px; border-radius: 20px; width: 420px; text-align: center; border: 1px solid rgba(239, 68, 68, 0.3); }
        input, select { width: 100%; padding: 15px; margin: 10px 0; border-radius: 10px; border: 1px solid #334155; background: #0f172a; color: white; text-align: center; }
        .btn-withdraw { background: #ef4444; color: white; border: none; padding: 15px; width: 100%; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 16px; transition: 0.3s; }
        .btn-withdraw:hover { background: #dc2626; }
        .error-msg { background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #ef4444; }
        .success-msg { background: rgba(34, 197, 94, 0.1); color: #22c55e; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #22c55e; }
    </style>
</head>
<body>
    <div class="withdraw-card">
        <h3>سحب الأموال إلى البنك</h3>
        <p style="color: #94a3b8; font-size: 13px; margin-bottom: 20px;">سيتم خصم المبلغ من حسابك وتوليد كود صرف بنكي</p>

        <?php if($error): ?> <div class="error-msg"><?= $error ?></div> <?php endif; ?>
        <?php if($success_msg): ?> <div class="success-msg"><?= $success_msg ?></div> <?php endif; ?>

        <form method="POST">
            <label style="font-size: 12px; color: #94a3b8; display: block; text-align: right;">اسحب من حساب:</label>
            <select name="account_id" required>
                <?php foreach($accounts as $acc): ?>
                    <option value="<?= $acc['acc_hex'] ?>">حساب (<?= $acc['currency'] ?>) - الرصيد: <?= number_format($acc['balance'], 2) ?></option>
                <?php endforeach; ?>
            </select>

            <label style="font-size: 12px; color: #94a3b8; display: block; text-align: right; margin-top: 10px;">المبلغ المراد سحبه:</label>
            <input type="number" name="amount" step="0.01" min="1" placeholder="0.00" required>

            <button type="submit" class="btn-withdraw">تأكيد عملية السحب</button>
        </form>
        
        <div style="margin-top: 20px;">
            <a href="../User_Registration&Login/dashboard.php" style="color: #94a3b8; text-decoration: none; font-size: 13px;">← العودة للوحة التحكم</a>
        </div>
    </div>
</body>
</html>