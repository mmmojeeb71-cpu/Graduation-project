<?php
session_start();
require_once '../Shared/config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: ../User_Registration&Login/login_view.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // جلب بيانات التاجر بناءً على UID المستخدم
    // ملاحظة: نستخدم BINARY(16) كما في إعدادات قاعدة البيانات الخاصة بك
    $stmt = $pdo->prepare("SELECT * FROM merchants WHERE user_id = UNHEX(REPLACE(?, '-', '')) LIMIT 1");
    $stmt->execute([$user_id]);
    $merchant = $stmt->fetch();

    // إذا لم يكن مسجلاً كتاجر، يتم توجيهه لصفحة التسجيل كتاجر
    if (!$merchant) {
        header("Location: become_merchant.php");
        exit();
    }
} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مركز التجار | Yemen Gate</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f172a;
            --card-bg: #1e293b;
            --primary-cyan: #06b6d4;
            --text-gray: #94a3b8;
            --success-green: #10b981;
        }

        body {
            background-color: var(--bg-dark);
            color: white;
            font-family: 'Tajawal', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .hub-container {
            width: 90%;
            max-width: 500px;
            background: var(--card-bg);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            text-align: center;
            border: 1px solid #334155;
        }

        .hub-header {
            margin-bottom: 25px;
        }

        .hub-header h1 {
            font-size: 24px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .status-box {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--success-green);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .status-box .status-text {
            color: var(--success-green);
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .status-box .merchant-name {
            color: var(--text-gray);
            font-size: 14px;
        }

        .menu-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .menu-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            background: #2d3748;
            border-radius: 12px;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .menu-btn:hover {
            background: #334155;
            border-color: var(--primary-cyan);
            transform: translateY(-2px);
        }

        .menu-btn.active-btn {
            background: var(--primary-cyan);
            color: var(--bg-dark);
            font-weight: bold;
        }

        .menu-btn.active-btn i {
            color: var(--bg-dark);
        }

        .menu-btn i {
            font-size: 18px;
            color: var(--primary-cyan);
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: var(--text-gray);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: white;
        }
    </style>
</head>
<body>

<div class="hub-container">
    <div class="hub-header">
        <h1><i class="fa-solid fa-rocket" style="color: var(--primary-cyan);"></i> مركز التجار (Merchant Hub)</h1>
    </div>

    <div class="status-box">
        <span class="status-text"><i class="fa-solid fa-check-square"></i> متجر معتمد نشط</span>
        <span class="merchant-name">اسم المتجر: <?= htmlspecialchars($merchant['business_name']) ?></span>
    </div>

    <div class="menu-options">
        <a href="merchant_dashboard.php" class="menu-btn active-btn">
            <span>لوحة تحكم المبيعات</span>
            <i class="fa-solid fa-chart-line"></i>
        </a>

        <a href="api_settings.php" class="menu-btn">
            <span>مفاتيح الربط API Keys</span>
            <i class="fa-solid fa-key"></i>
        </a>

        <a href="documentation.php" class="menu-btn">
            <span>دليل المطورين</span>
            <i class="fa-solid fa-book"></i>
        </a>
    </div>

    <a href="../User_Registration&Login/dashboard.php" class="back-link">
        <i class="fa-solid fa-arrow-right"></i> العودة إلى لوحة التحكم الرئيسية
    </a>
</div>

</body>
</html>