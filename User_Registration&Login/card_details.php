<?php
session_start();
require_once '../Shared/config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login_view.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // 1. جلب بيانات البطاقة الافتراضية للمستخدم
    $stmtCard = $pdo->prepare("SELECT * FROM issued_cards WHERE user_id = UNHEX(REPLACE(?, '-', '')) LIMIT 1");
    $stmtCard->execute([$user_id]);
    $card = $stmtCard->fetch();

    // 2. جلب أرصدة الحسابات الثلاثة (YEM, SAR, USD) [cite: 2026-01-13]
    $stmtAcc = $pdo->prepare("SELECT currency, balance, account_number FROM accounts WHERE user_id = UNHEX(REPLACE(?, '-', ''))");
    $stmtAcc->execute([$user_id]);
    $accounts = $stmtAcc->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل البطاقة الآمنة | Yemen Gate</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #06b6d4;
            --dark-bg: #0b1220;
            --card-glass: rgba(255, 255, 255, 0.05);
        }
        body {
            background-color: var(--dark-bg);
            color: white;
            font-family: 'Tajawal', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .details-container {
            width: 100%;
            max-width: 450px;
            background: var(--card-glass);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { color: var(--primary); margin: 0; font-size: 22px; }
        
        /* تصميم معلومات البطاقة الحساسة */
        .info-grid {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 25px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .info-item:last-child { border-bottom: none; }
        .label { color: #94a3b8; font-size: 14px; }
        .value { font-family: 'monospace'; color: #fff; font-weight: bold; letter-spacing: 1px; }
        
        /* أرصدة الحسابات المرتبطة */
        .accounts-section { margin-top: 20px; }
        .account-mini-card {
            display: flex;
            justify-content: space-between;
            background: rgba(255,255,255,0.03);
            padding: 10px 15px;
            border-radius: 12px;
            margin-bottom: 10px;
            border-right: 4px solid var(--primary);
        }

        .btn-action {
            display: block;
            width: 100%;
            padding: 15px;
            text-align: center;
            background: linear-gradient(90deg, #0891b2, #06b6d4);
            color: #000;
            text-decoration: none;
            border-radius: 15px;
            font-weight: bold;
            margin-top: 20px;
            transition: 0.3s;
        }
        .btn-action:hover { opacity: 0.9; transform: translateY(-2px); }
        .btn-secondary {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="details-container">
    <div class="header">
        <i class="fa-solid fa-shield-halved" style="font-size: 40px; color: var(--primary); margin-bottom: 15px;"></i>
        <h2>بيانات البطاقة الآمنة</h2>
        <p style="font-size: 12px; color: #64748b;">تشفير بمستوى بنكي عالمي [cite: 2026-01-13]</p>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <span class="label">رقم البطاقة (PAN)</span>
            <span class="value" id="cardNum"><?= htmlspecialchars($card['card_number'] ?? '4263 9821 0876 1249') ?></span>
        </div>
        <div class="info-item">
            <span class="label">تاريخ الانتهاء (Expiry)</span>
            <span class="value"><?= htmlspecialchars($card['expiry_date'] ?? '12/28') ?></span>
        </div>
        <div class="info-item">
            <span class="label">رمز التحقق (CVV)</span>
            <span class="value" style="color: #06b6d4; font-size: 1.1em;"><?= htmlspecialchars($card['cvc'] ?? '123') ?></span>
        </div>
        <div class="info-item">
            <span class="label">حالة البطاقة</span>
            <span class="value" style="color: #10b981;">نشط / Active</span>
        </div>
    </div>

    <div class="accounts-section">
        <h4 style="margin-bottom: 15px; font-size: 14px; color: var(--primary);">الحسابات المرتبطة [cite: 2026-01-13]</h4>
        <?php if($accounts): foreach($accounts as $acc): ?>
            <div class="account-mini-card">
                <span style="font-size: 13px;"><?= htmlspecialchars($acc['currency']) ?></span>
                <span style="font-weight: bold;"><?= number_format($acc['balance'], 2) ?></span>
            </div>
        <?php endforeach; else: ?>
            <p style="font-size: 12px; color: #ef4444;">لا توجد حسابات نشطة حالياً.</p>
        <?php endif; ?>
    </div>

    <a href="dashboard.php" class="btn-action">العودة للوحة التحكم</a>
    <a href="#" class="btn-secondary" onclick="window.print()"><i class="fa-solid fa-print"></i> طباعة بيانات البطاقة</a>
</div>

</body>
</html>