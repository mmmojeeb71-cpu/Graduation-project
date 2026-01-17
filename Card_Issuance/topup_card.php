<?php
session_start();
// استدعاء ملف الاتصال الموحد
require_once '../Shared/config.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../User_Registration&Login/login_view.php");
    exit();
}

// تنظيف وتجهيز الـ User ID للتعامل مع BINARY(16)
$user_id_raw = $_SESSION['user_id'];
$error = "";

try {
    // 1. جلب بيانات البطاقة النشطة للمستخدم (مع جلب الحالة Status)
    $stmt_card = $pdo->prepare("SELECT * FROM issued_cards WHERE user_id = UNHEX(REPLACE(?, '-', '')) LIMIT 1");
    $stmt_card->execute([$user_id_raw]);
    $card = $stmt_card->fetch();

    if (!$card) {
        die("<div style='background:#0b1220; color:white; height:100vh; display:flex; align-items:center; justify-content:center; font-family:Tajawal; direction:rtl;'>❌ لم يتم العثور على بطاقة نشطة. يرجى إصدار بطاقة أولاً.</div>");
    }

    // --- الإضافة المطلوبة: التحقق إذا كانت البطاقة مجمدة ---
    if (isset($card['status']) && $card['status'] === 'Frozen') {
        $error = "🚫 لا يمكن إتمام عملية الشحن لأن البطاقة (مجمدة حالياً). يرجى تفعيلها من إعدادات البطاقة أولاً.";
    }

    // 2. جلب الحسابات البنكية المتاحة (يمني، سعودي، دولار) مع تحويل الـ ID لنظام HEX للعرض
    $stmt_acc = $pdo->prepare("SELECT HEX(account_id) as acc_id_hex, currency, balance FROM accounts WHERE user_id = UNHEX(REPLACE(?, '-', ''))");
    $stmt_acc->execute([$user_id_raw]);
    $accounts = $stmt_acc->fetchAll();

    // 3. معالجة طلب تحويل الرصيد للبطاقة
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['amount'], $_POST['from_account'])) {
        
        // منع المعالجة إذا كانت البطاقة مجمدة
        if (isset($card['status']) && $card['status'] === 'Frozen') {
            throw new Exception("البطاقة مجمدة، لا يمكن تنفيذ عمليات مالية.");
        }

        $amount_to_add = floatval($_POST['amount']);
        $from_acc_hex = $_POST['from_account'];

        // البحث الدقيق عن الحساب المصدر باستخدام UNHEX لمطابقة نوع البيانات في قاعدة البيانات
        $stmt_check = $pdo->prepare("SELECT balance, currency FROM accounts WHERE account_id = UNHEX(?)");
        $stmt_check->execute([$from_acc_hex]);
        $source_acc = $stmt_check->fetch();

        if ($source_acc) {
            $current_balance = floatval($source_acc['balance']);
            
            if ($current_balance >= $amount_to_add && $amount_to_add > 0) {
                $pdo->beginTransaction();

                // أ- خصم الرصيد من حساب البنك
                $stmt_deduct = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE account_id = UNHEX(?)");
                $stmt_deduct->execute([$amount_to_add, $from_acc_hex]);

                // ب- توجيه المبلغ للمحفظة الصحيحة في البطاقة (Multi-Currency Wallet)
                $currency_key = trim(strtoupper($source_acc['currency']));
                $target_wallet = "";
                
                if ($currency_key === 'YER') $target_wallet = "balance_yer";
                elseif ($currency_key === 'SAR') $target_wallet = "balance_sar";
                elseif ($currency_key === 'USD') $target_wallet = "balance_usd";

                if (!empty($target_wallet)) {
                    // ج- إيداع الرصيد في البطاقة
                    $stmt_add = $pdo->prepare("UPDATE issued_cards SET $target_wallet = $target_wallet + ? WHERE card_id = ?");
                    $stmt_add->execute([$amount_to_add, $card['card_id']]);

                    // د- تسجيل العملية في سجل البنك الافتراضي
                    $stmt_log = $pdo->prepare("INSERT INTO virtual_bank_transactions (account_id, amount, transaction_type, status) VALUES (UNHEX(?), ?, 'CARD_TOPUP', 'completed')");
                    $stmt_log->execute([$from_acc_hex, $amount_to_add]);

                    $pdo->commit();
                    header("Location: create_card_view.php?status=success");
                    exit();
                } else {
                    throw new Exception("عملة الحساب المختار غير مدعومة في نظام البطاقات الحالي.");
                }
            } else {
                $error = "عذراً، الرصيد المتاح هو (" . number_format($current_balance, 2) . ")، وهو غير كافٍ لإتمام عملية الشحن.";
            }
        } else {
            $error = "فشل في التعرف على بيانات الحساب المختار. يرجى إعادة المحاولة.";
        }
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    if (empty($error)) $error = "⚠️ خطأ في النظام: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شحن البطاقة | Yemen Gate</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; background: #0b1220; color: white; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card-container { background: #0f172a; padding: 40px; border-radius: 24px; width: 100%; max-width: 450px; border: 1px solid #1e293b; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        .header-title { text-align: center; margin-bottom: 30px; }
        .header-title h2 { margin: 0; font-size: 24px; color: #f8fafc; }
        .header-title p { color: #94a3b8; font-size: 14px; margin-top: 8px; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #fca5a5; padding: 15px; border-radius: 12px; margin-bottom: 25px; text-align: center; font-size: 14px; line-height: 1.6; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #94a3b8; font-size: 13px; font-weight: 700; }
        select, input { width: 100%; padding: 16px; border-radius: 12px; background: #1e293b; border: 1px solid #334155; color: white; font-size: 16px; transition: all 0.3s ease; box-sizing: border-box; }
        select:focus, input:focus { border-color: #06b6d4; outline: none; box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1); }
        .submit-btn { width: 100%; padding: 18px; background: #06b6d4; border: none; border-radius: 12px; color: #0b1220; font-weight: 700; font-size: 16px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .submit-btn:hover { background: #22d3ee; transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(6, 182, 212, 0.4); }
        .submit-btn:disabled { background: #334155; color: #94a3b8; cursor: not-allowed; transform: none; box-shadow: none; }
        .footer-link { text-align: center; margin-top: 25px; }
        .footer-link a { color: #94a3b8; text-decoration: none; font-size: 14px; transition: 0.2s; }
        .footer-link a:hover { color: #f8fafc; }
        .btn-back { display:inline-block; margin-top:10px; padding:12px 18px; background:#334155; color:#f8fafc; border-radius:8px; text-decoration:none; font-size:14px; font-weight:bold; }
        .btn-back:hover { background:#475569; }
    </style>
</head>
<body>
    <div class="card-container">
                <div class="header-title">
            <h2>تعبئة محفظة البطاقة</h2>
            <p>قم بتحويل الرصيد من حسابك البنكي إلى محفظة البطاقة فوراً</p>
        </div>

        <?php if($error): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>اختر الحساب المصدر للشحن</label>
                <select name="from_account" required <?= ($card['status'] === 'Frozen') ? 'disabled' : '' ?>>
                    <option value="" disabled selected>-- اختر الحساب --</option>
                    <?php foreach($accounts as $acc): ?>
                        <option value="<?= $acc['acc_id_hex'] ?>">
                            <?= htmlspecialchars($acc['currency']) ?> - الرصيد: <?= number_format($acc['balance'], 2) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>المبلغ المراد تحويله</label>
                <input type="number" name="amount" step="0.01" min="0.10" placeholder="0.00" required <?= ($card['status'] === 'Frozen') ? 'disabled' : '' ?>>
            </div>

            <button type="submit" class="submit-btn" <?= ($card['status'] === 'Frozen') ? 'disabled' : '' ?>>
                <?= ($card['status'] === 'Frozen') ? 'البطاقة مجمدة' : 'تأكيد شحن البطاقة' ?>
            </button>
            
            <div class="footer-link">
                <a href="create_card_view.php">⬅️ إلغاء والعودة للوحة التحكم</a>
                <br>
                <a href="../User_Registration&Login/dashboard.php" class="btn-back">🏠 العودة إلى الصفحة الرئيسية</a>
            </div>
        </form>
    </div>
</body>
</html>
