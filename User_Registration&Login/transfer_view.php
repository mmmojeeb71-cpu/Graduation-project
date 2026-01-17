<?php
session_start();
require_once '../Shared/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login_view.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // جلب الحسابات مع تحويل المعرف BINARY إلى HEX لعرضه واستخدامه في النموذج
    $stmt = $pdo->prepare("SELECT HEX(account_id) as acc_id_hex, balance, currency, account_number FROM accounts WHERE user_id = UNHEX(REPLACE(?, '-', ''))");
    $stmt->execute([$user_id]);
    $my_accounts = $stmt->fetchAll();
} catch (Exception $e) {
    die("خطأ في النظام: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إرسال أموال - بوابة اليمن</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #0b1220; --primary: #06b6d4; --border: rgba(255,255,255,0.1); }
        body { margin: 0; font-family: 'Tajawal', sans-serif; background: var(--bg); color: #e5e7eb; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .transfer-card { width: 100%; max-width: 450px; background: rgba(255,255,255,0.05); padding: 30px; border-radius: 24px; border: 1px solid var(--border); backdrop-filter: blur(20px); }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 14px; color: #94a3b8; }
        .input-group select, .input-group input { width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--border); background: rgba(0,0,0,0.3); color: white; outline: none; box-sizing: border-box; }
        .btn-send { width: 100%; padding: 16px; border-radius: 12px; border: none; background: linear-gradient(135deg, #06b6d4, #4f46e5); color: white; font-weight: 700; cursor: pointer; font-size: 16px; transition: 0.3s; }
        .btn-send:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(6,182,212,0.3); }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #94a3b8; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

<div class="transfer-card">
    <div style="text-align: center; margin-bottom: 25px;">
        <i class="fa-solid fa-paper-plane" style="font-size: 40px; color: var(--primary);"></i>
        <h2 style="margin: 10px 0;">تحويل مالي</h2>
    </div>

    <form action="process_transfer.php" method="POST">
        <div class="input-group">
            <label>من حسابك</label>
            <select name="from_account_hex" required>
                <?php foreach ($my_accounts as $acc): ?>
                    <option value="<?= $acc['acc_id_hex']; ?>">
                        حساب <?= $acc['currency']; ?> (رقم: <?= $acc['account_number']; ?>) - رصيد: <?= number_format($acc['balance'], 2); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="input-group">
            <label>إلى رقم حساب المستلم (Account Number)</label>
            <input type="text" name="target_account_number" placeholder="مثلاً: YG-123456 أو كود الحساب" required>
            <small style="color: #64748b; font-size: 11px; margin-top: 5px; display: block;">أدخل رقم الحساب الذي يظهر في لوحة تحكم المستلم.</small>
        </div>

        <div class="input-group">
            <label>المبلغ</label>
            <input type="number" name="amount" step="0.01" min="0.1" placeholder="0.00" required>
        </div>

        <button type="submit" class="btn-send">تأكيد الإرسال</button>
    </form>
    <a href="dashboard.php" class="back-link">إلغاء والعودة</a>
</div>
<div id="errorToast" class="toast">
    <i class="fa-solid fa-circle-exclamation"></i>
    <span>خطأ: لا يمكن التحويل لعملة مختلفة!</span>
</div>

<style>
/* تصميم الرسالة المنبثقة */
.toast {
    visibility: hidden;
    min-width: 300px;
    background-color: #ef4444; /* لون أحمر */
    color: #fff;
    text-align: center;
    border-radius: 12px;
    padding: 16px;
    position: fixed;
    z-index: 1000;
    left: 50%;
    bottom: 30px;
    transform: translateX(-50%);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: bold;
    opacity: 0;
    transition: opacity 0.5s, bottom 0.5s;
}

.toast.show {
    visibility: visible;
    opacity: 1;
    bottom: 50px;
}
</style>

<script>
// التحقق من وجود خطأ في الرابط
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('error') === 'currency_mismatch') {
    const toast = document.getElementById("errorToast");
    
    // إظهار الرسالة
    toast.className = "toast show";
    
    // إخفاء الرسالة بعد 5 ثوانٍ (5000 ميلي ثانية)
    setTimeout(function() {
        toast.className = toast.className.replace("show", "");
        
        // تنظيف الرابط من كلمة error لكي لا تظهر الرسالة عند تحديث الصفحة
        window.history.replaceState({}, document.title, window.location.pathname);
    }, 5000);
}
</script>
</body>
</html>