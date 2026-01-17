<?php
// تعديل المسار للوصول إلى config.php في مجلد Shared
require_once '../Shared/config.php';

$generated_code = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    
    // توليد كود فريد مكون من 12 رمزاً
    $generated_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 12));
    
    try {
        $stmt = $pdo->prepare("INSERT INTO bank_vouchers (voucher_code, amount, currency) VALUES (?, ?, ?)");
        $stmt->execute([$generated_code, $amount, $currency]);
    } catch (Exception $e) {
        die("خطأ في النظام: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة البنك - إصدار قسائم</title>
    <style>
        body { background: #0f172a; color: white; font-family: sans-serif; display: flex; justify-content: center; padding: 50px; }
        .gen-box { background: #1e293b; padding: 40px; border-radius: 20px; border: 1px solid #06b6d4; text-align: center; width: 400px; }
        input, select { width: 100%; padding: 12px; margin: 10px 0; border-radius: 8px; border: none; }
        .code-display { background: #06b6d4; color: #0f172a; padding: 15px; font-size: 22px; font-weight: bold; margin: 20px 0; border-radius: 8px; }
        button { background: #06b6d4; color: #0f172a; padding: 15px; width: 100%; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="gen-box">
        <h2>إصدار كود شحن جديد</h2>
        <form method="POST">
            <input type="number" name="amount" placeholder="حدد المبلغ المراد شحنه" required>
            <select name="currency">
                <option value="USD">دولار أمريكي (USD)</option>
                <option value="YER">ريال يمني (YER)</option>
                <option value="SAR">ريال سعودي (SAR)</option>
            </select>
            <button type="submit">توليد الكود الآمن</button>
        </form>

        <?php if($generated_code): ?>
            <div style="margin-top: 30px; border-top: 1px solid #334155; padding-top: 20px;">
                <p>تم توليد الكود بنجاح:</p>
                <div class="code-display"><?= $generated_code ?></div>
                <p style="color: #94a3b8; font-size: 14px;">أرسل الكود للعميل لشحن حسابه بمبلغ <?= number_format($amount, 2) ?> <?= $currency ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>