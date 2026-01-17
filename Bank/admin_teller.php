<?php
session_start();
require_once '../Shared/config.php';

// ملاحظة: يفضل إضافة نظام حماية هنا ليدخل الموظفون فقط
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_code'])) {
    $code = strtoupper(trim($_POST['voucher_code']));

    // البحث عن الكود في نظام القسائم
    $stmt = $pdo->prepare("SELECT * FROM bank_vouchers WHERE voucher_code = ?");
    $stmt->execute([$code]);
    $voucher = $stmt->fetch();

    if ($voucher) {
        if ($voucher['is_used'] == 1) {
            $error = "هذا الكود تم استخدامه مسبقاً في تاريخ: " . $voucher['created_at'];
        } else {
            // تحديث الكود ليصبح "مستخدماً" (محاكاة لتسليم النقد للعميل أو شحن حسابه)
            $update = $pdo->prepare("UPDATE bank_vouchers SET is_used = 1 WHERE voucher_id = ?");
            $update->execute([$voucher['voucher_id']]);
            
            $type = (strpos($code, 'WD-') === 0) ? "عملية سحب نقدي" : "عملية إيداع/شحن";
            $success = "تمت العملية بنجاح! <br> النوع: $type <br> المبلغ: " . number_format($voucher['amount'], 2) . " " . $voucher['currency'];
        }
    } else {
        $error = "عذراً، هذا الكود غير موجود في سجلات البنك.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نظام الصراف الآلي - البنك الافتراضي</title>
    <style>
        body { background: #f1f5f9; color: #1e293b; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; }
        .navbar { background: #0f172a; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 900px; margin: 50px auto; padding: 20px; }
        .teller-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-top: 5px solid #06b6d4; }
        .input-group { margin: 20px 0; }
        input { width: 100%; padding: 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 18px; text-align: center; font-weight: bold; letter-spacing: 2px; }
        .btn-action { background: #06b6d4; color: white; border: none; padding: 15px 30px; border-radius: 10px; cursor: pointer; font-size: 16px; width: 100%; font-weight: bold; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: bold; }
        .alert-danger { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .stats { display: flex; gap: 20px; margin-top: 30px; }
        .stat-box { background: white; padding: 20px; border-radius: 12px; flex: 1; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    </style>
</head>
<body>

<div class="navbar">
    <h2>🏦 Yemen Gate - فرع البنك المركزي</h2>
    <span>موظف الصرافة رقم: #001</span>
</div>

<div class="container">
    <div class="teller-card">
        <h3>نظام التحقق من القسائم والأكواد</h3>
        <p>قم بإدخال الكود الذي قدمه العميل لتأكيد عملية الصرف أو الإيداع.</p>

        <?php if($error): ?> <div class="alert alert-danger"><?= $error ?></div> <?php endif; ?>
        <?php if($success): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <input type="text" name="voucher_code" placeholder="أدخل الكود هنا (مثلاً: WD-XXXXX أو كود الشحن)" required>
            </div>
            <button type="submit" name="process_code" class="btn-action">تأكيد العملية وصرف المبلغ</button>
        </form>
    </div>

    <div class="stats">
        <div class="stat-box">
            <small>حالة النظام</small>
            <div style="color: #22c55e;">● متصل بالشبكة</div>
        </div>
        <div class="stat-box">
            <small>توقيت البنك</small>
            <div><?= date('H:i:s A') ?></div>
        </div>
    </div>
</div>

</body>
</html>