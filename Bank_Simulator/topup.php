<?php
session_start();
require_once '../Shared/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../User_Registration&Login/login_view.php");
    exit();
}

// جلب حسابات المستخدم لربط عملية الشحن بحساب محدد
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT account_id, currency, account_number FROM accounts WHERE user_id = UNHEX(?)");
$stmt->execute([$user_id]);
$accounts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>شحن الحساب - Yemen Gate</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #0b1220; --card: rgba(255,255,255,0.06); --border: rgba(255,255,255,0.1); --primary: #06b6d4; }
        body { margin: 0; font-family: 'Tajawal', sans-serif; background: var(--bg); color: #e5e7eb; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .method-card {
            background: var(--card); border: 1px solid var(--border); border-radius: 16px;
            padding: 20px; margin-bottom: 15px; cursor: pointer; transition: 0.3s;
            display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: inherit;
        }
        .method-card:hover { border-color: var(--primary); transform: translateY(-3px); background: rgba(6,182,212,0.05); }
        .icon-box { width: 50px; height: 50px; border-radius: 12px; background: rgba(6,182,212,0.1); display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--primary); }
        .method-info { flex: 1; margin-right: 15px; }
        .method-title { font-weight: 700; font-size: 16px; margin-bottom: 4px; }
        .method-desc { font-size: 13px; color: #94a3b8; }
        .badge-recommended { background: var(--primary); color: #fff; font-size: 10px; padding: 2px 8px; border-radius: 10px; margin-right: 8px; }
    </style>
</head>
<body>

<div class="container">
    <h2 style="text-align: center; margin-bottom: 30px;">اختر طريقة الشحن</h2>

    <a href="virtual_bank_gateway.php" class="method-card">
        <div class="icon-box"><i class="fa-solid fa-building-columns"></i></div>
        <div class="method-info">
            <div class="method-title">البنك الافتراضي (رابط دفع) <span class="badge-recommended">آلي وفوري</span></div>
            <div class="method-desc">اشحن رصيدك عبر محاكي البنك الخاص بنا لتجربة النظام.</div>
        </div>
        <i class="fa-solid fa-chevron-left"></i>
    </a>

    <div class="method-card" onclick="alert('يرجى التواصل مع الدعم الفني لإرسال الحوالة عبر الكريمي أو النجم')">
        <div class="icon-box" style="color: #fbbf24; background: rgba(251,191,36,0.1);"><i class="fa-solid fa-money-bill-transfer"></i></div>
        <div class="method-info">
            <div class="method-title">الكريمي / النجم / الامتياز</div>
            <div class="method-desc">إيداع يدوي عبر شبكات الصرافة اليمنية (يستغرق 15 دقيقة).</div>
        </div>
        <i class="fa-solid fa-chevron-left"></i>
    </div>

    <div class="method-card" onclick="alert('سيتم تفعيل الدفع بالبطاقات الدولية قريباً')">
        <div class="icon-box" style="color: #f43f5e; background: rgba(244,63,94,0.1);"><i class="fa-solid fa-credit-card"></i></div>
        <div class="method-info">
            <div class="method-title">بطاقة فيزا / ماستركارد</div>
            <div class="method-desc">شحن الرصيد مباشرة باستخدام بطاقتك البنكية الدولية.</div>
        </div>
        <i class="fa-solid fa-lock"></i>
    </div>

    <p style="text-align: center; color: #94a3b8; font-size: 13px; margin-top: 20px;">
        <i class="fa-solid fa-shield-halved"></i> جميع المعاملات مشفرة وآمنة تماماً.
    </p>
</div>

</body>
</html>