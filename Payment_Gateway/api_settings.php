<?php
session_start();
require_once '../Shared/config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: ../User_Registration&Login/login_view.php");
    exit();
}

$user_id_raw = $_SESSION['user_id'];

// جلب بيانات التاجر
$stmt = $pdo->prepare("SELECT * FROM merchants WHERE user_id = UNHEX(REPLACE(?, '-', ''))");
$stmt->execute([$user_id_raw]);
$merchant = $stmt->fetch();

// إذا لم يكن تاجراً بعد، نوجهه لصفحة التسجيل كتاجر
if (!$merchant) {
    header("Location: become_merchant.php");
    exit();
}

// معالجة تحديث طرق الدفع
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $methods = $_POST['methods'] ?? [];
    $methods_json = json_encode($methods);
    
    $update = $pdo->prepare("UPDATE merchants SET allowed_payment_methods = ? WHERE merchant_id = ?");
    $update->execute([$methods_json, $merchant['merchant_id']]);
    
    // تحديث البيانات في المتغير الحالي للعرض
    $merchant['allowed_payment_methods'] = $methods_json;
    $success_msg = "تم تحديث إعدادات الربط بنجاح!";
}

$current_methods = json_decode($merchant['allowed_payment_methods'] ?? '[]', true);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إعدادات الربط البرمجي | Yemen Gate</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0ea5e9;
            --bg-dark: #020617;
            --card-bg: #0f172a;
            --text-main: #f8fafc;
            --border: #1e293b;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            margin: 0;
            padding: 40px;
        }

        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid var(--border);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .api-key-box {
            background: #010409;
            padding: 15px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border: 1px solid #30363d;
        }

        .api-key-box code { color: #79c0ff; font-family: monospace; font-size: 1.1em; }

        .method-item {
            display: flex;
            align-items: center;
            gap: 15 inline-block;
            background: rgba(255,255,255,0.02);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: 0.3s;
        }

        .method-item:hover { background: rgba(255,255,255,0.05); }

        .btn-save {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }

        .btn-generate {
            display: block;
            text-align: center;
            text-decoration: none;
            background: #6366f1;
            color: white;
            padding: 12px;
            border-radius: 10px;
            margin-top: 15px;
            font-weight: bold;
        }

        .alert { padding: 15px; border-radius: 10px; background: #064e3b; color: #34d399; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="settings-container">
    <div class="header">
        <h2><i class="fas fa-plug"></i> إعدادات الربط (API)</h2>
        <a href="../User_Registration&Login/dashboard.php" style="color: var(--primary); text-decoration: none;">العودة</a>
    </div>

    <?php if(isset($success_msg)): ?>
        <div class="alert"><?= $success_msg ?></div>
    <?php endif; ?>

    <label>مفتاح التاجر (API Key):</label>
    <div class="api-key-box">
        <code><?= $merchant['api_key'] ?></code>
        <i class="fas fa-copy" style="cursor:pointer;" onclick="alert('تم النسخ')"></i>
    </div>

    <form method="POST">
        <h3>طرق الدفع المسموح بها في متجرك:</h3>
        
        <label class="method-item">
            <input type="checkbox" name="methods[]" value="yemen_gate_card" <?= in_array('yemen_gate_card', $current_methods) ? 'checked' : '' ?>>
            <div style="margin-right: 15px;">
                <strong>بطاقة يمن جت الذكية</strong><br>
                <small>السماح للزبائن بالدفع عبر بطاقة البنك الافتراضية</small>
            </div>
        </label>

        <label class="method-item">
            <input type="checkbox" name="methods[]" value="yer_acc" <?= in_array('yer_acc', $current_methods) ? 'checked' : '' ?>>
            <div style="margin-right: 15px;">
                <strong>حساب ريال يمني (YER)</strong><br>
                <small>استقبال المدفوعات بالعملة المحلية</small>
            </div>
        </label>

        <label class="method-item">
            <input type="checkbox" name="methods[]" value="usd_acc" <?= in_array('usd_acc', $current_methods) ? 'checked' : '' ?>>
            <div style="margin-right: 15px;">
                <strong>حساب دولار (USD)</strong><br>
                <small>استقبال المدفوعات العالمية</small>
            </div>
        </label>

        <button type="submit" name="update_settings" class="btn-save">حفظ الإعدادات</button>
    </form>

    <a href="generate_integration.php" class="btn-generate">
        <i class="fas fa-code"></i> الحصول على كود الربط البرمجي
    </a>
</div>

</body>
</html>