<?php
session_start();
require_once '../Shared/config.php';

// 1. استقبال البيانات من صفحة الـ Checkout
$merchant_id = $_POST['merchant_id'] ?? '';
$amount      = floatval($_POST['amount'] ?? 0);
$currency    = $_POST['currency'] ?? 'USD';
$card_number = $_POST['card_number'] ?? '';
$cvv         = $_POST['cvv'] ?? '';

try {
    $pdo->beginTransaction();

    // 2. التحقق من البطاقة وصاحبها ورصيده
    // سنبحث عن البطاقة في جدول "virtual_cards" ونربطها بحساب المستخدم
    $stmt = $pdo->prepare("SELECT vc.*, a.balance, a.account_id 
                           FROM virtual_cards vc 
                           JOIN accounts a ON vc.user_id = a.user_id 
                           WHERE vc.card_number = ? AND vc.cvv = ? AND a.currency = ?");
    $stmt->execute([$card_number, $cvv, $currency]);
    $card_info = $stmt->fetch();

    if (!$card_info) {
        throw new Exception("بيانات البطاقة غير صحيحة أو العملة غير متوافقة.");
    }

    if ($card_info['balance'] < $amount) {
        throw new Exception("عذراً، الرصيد في البطاقة غير كافٍ لإتمام العملية.");
    }

    // 3. خصم المبلغ من المشتري
    $update_buyer = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE account_id = ?");
    $update_buyer->execute([$amount, $card_info['account_id']]);

    // 4. إيداع المبلغ للتاجر
    // جلب حساب التاجر (نفترض أن التاجر لديه حساب بنفس العملة المطلوبة)
    $stmt_merchant = $pdo->prepare("SELECT account_id FROM accounts WHERE user_id = (SELECT user_id FROM merchants WHERE merchant_id = ?) AND currency = ?");
    $stmt_merchant->execute([$merchant_id, $currency]);
    $merchant_account = $stmt_merchant->fetch();

    if (!$merchant_account) {
        throw new Exception("لا يوجد حساب مفعل للتاجر لاستلام هذه العملة.");
    }

    $update_merchant = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_id = ?");
    $update_merchant->execute([$amount, $merchant_account['account_id']]);

    // 5. تسجيل العملية في سجل التحويلات (History)
    $stmt_log = $pdo->prepare("INSERT INTO transactions (sender_account_id, receiver_account_id, amount, currency, status, transaction_type) 
                               VALUES (?, ?, ?, ?, 'completed', 'gateway_payment')");
    $stmt_log->execute([$card_info['account_id'], $merchant_account['account_id'], $amount, $currency]);

    $pdo->commit();

    // 6. عرض صفحة النجاح بتنسيق عالمي
    echo "
    <div style='text-align:center; padding:100px; font-family:sans-serif; background:#020617; color:white; height:100vh;'>
        <div style='background:#0f172a; padding:40px; border-radius:24px; display:inline-block; border:1px solid #22c55e;'>
            <i class='fas fa-check-circle' style='color:#22c55e; font-size:4rem;'></i>
            <h1 style='margin-top:20px;'>تم الدفع بنجاح!</h1>
            <p style='color:#94a3b8;'>تم تحويل مبلغ $amount $currency إلى المتجر.</p>
            <p style='font-size:0.8rem;'>رقم العملية: " . bin2hex(random_bytes(8)) . "</p>
            <br>
            <a href='#' onclick='window.close()' style='color:#0ea5e9; text-decoration:none;'>العودة إلى الموقع</a>
        </div>
    </div>";

} catch (Exception $e) {
    $pdo->rollBack();
    die("<div style='text-align:center; padding:100px; background:#020617; color:white; height:100vh;'>
            <h2 style='color:#ef4444;'>❌ فشلت العملية</h2>
            <p>" . $e->getMessage() . "</p>
            <a href='javascript:history.back()' style='color:#0ea5e9;'>حاول مرة أخرى</a>
         </div>");
}