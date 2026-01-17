<?php
session_start();
require_once '../Shared/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../User_Registration&Login/login_view.php");
    exit();
}

$user_id_raw = $_SESSION['user_id'];
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $business_name = $_POST['business_name'];
    $currency = $_POST['settlement_currency'];
    $webhook = $_POST['webhook_url'];
    $merchant_type = $_POST['merchant_type'] ?? 'E-commerce';
    
    $allowed_methods = isset($_POST['payment_methods']) ? json_encode($_POST['payment_methods']) : json_encode(['yemen_gate_card']);

    try {
        $stmt_check = $pdo->prepare("SELECT api_key FROM merchants WHERE user_id = UNHEX(REPLACE(?, '-', ''))");
        $stmt_check->execute([$user_id_raw]);
        $existing_merchant = $stmt_check->fetch();

        if ($existing_merchant) {
            $sql = "UPDATE merchants SET business_name = ?, settlement_currency = ?, webhook_url = ?, merchant_type = ?, allowed_payment_methods = ? 
                    WHERE user_id = UNHEX(REPLACE(?, '-', ''))";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$business_name, $currency, $webhook, $merchant_type, $allowed_methods, $user_id_raw]);
            header("Location: generate_integration.php?status=updated");
            exit();
        } else {
            $merchant_id = bin2hex(random_bytes(16)); 
            $api_key = "YG_LIVE_" . strtoupper(bin2hex(random_bytes(12)));
            $sql = "INSERT INTO merchants (merchant_id, user_id, business_name, settlement_currency, webhook_url, merchant_type, api_key, allowed_payment_methods) 
                    VALUES (UNHEX(?), UNHEX(REPLACE(?, '-', '')), ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$merchant_id, $user_id_raw, $business_name, $currency, $webhook, $merchant_type, $api_key, $allowed_methods]);
            header("Location: generate_integration.php?welcome=true");
            exit();
        }
    } catch (Exception $e) { $error_msg = "❌ خطأ: " . $e->getMessage(); }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM merchants WHERE user_id = UNHEX(REPLACE(?, '-', ''))");
    $stmt->execute([$user_id_raw]);
    $merchant = $stmt->fetch();
    $current_methods = ($merchant && !empty($merchant['allowed_payment_methods'])) ? json_decode($merchant['allowed_payment_methods'], true) : ['yemen_gate_card', 'yer_acc', 'sar_acc', 'usd_acc'];
} catch (Exception $e) { $merchant = null; $current_methods = ['yemen_gate_card']; }
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات التاجر | Yemen Gate Global</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0ea5e9;
            --secondary: #6366f1;
            --accent: #f43f5e;
            --bg-dark: #020617;
            --card-bg: #0f172a;
            --input-bg: #1e293b;
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --border: #334155;
        }

        body {
            font-family: 'Tajawal', 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 0% 0%, rgba(14, 165, 233, 0.15) 0%, transparent 35%),
                radial-gradient(circle at 100% 100%, rgba(99, 102, 241, 0.15) 0%, transparent 35%);
            color: var(--text-main);
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .merchant-wrapper {
            width: 100%;
            max-width: 950px;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .main-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            display: grid;
            grid-template-columns: 350px 1fr;
        }

        /* الجزء الجانبي (المعلومات) */
        .sidebar-info {
            background: linear-gradient(180deg, var(--secondary), var(--primary));
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sidebar-info h2 { font-size: 32px; font-weight: 800; line-height: 1.2; margin: 0; }
        .sidebar-info p { opacity: 0.9; margin-top: 20px; font-size: 16px; line-height: 1.6; }

        .feature-list { list-style: none; padding: 0; margin-top: 40px; }
        .feature-list li { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; font-weight: 500; font-size: 14px; }
        .feature-list li i { background: rgba(255,255,255,0.2); padding: 8px; border-radius: 50%; width: 15px; height: 15px; display: flex; align-items:center; justify-content: center; }

        /* الجزء الرئيسي (النموذج) */
        .form-section { padding: 60px; background: var(--card-bg); }

        .section-label { font-size: 13px; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 25px; display: block; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }

        .input-group { margin-bottom: 25px; position: relative; }
        .input-group label { display: block; font-size: 14px; font-weight: 600; color: var(--text-dim); margin-bottom: 10px; }
        
        .form-input {
            width: 100%;
            padding: 14px 18px;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            color: white;
            font-size: 15px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: #2d3a4f;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
        }

        /* طرق الدفع الشبكية */
        .methods-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 30px;
        }

        .method-tile {
            background: var(--input-bg);
            border: 2px solid var(--border);
            padding: 16px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .method-tile input { display: none; }
        .method-tile i { font-size: 18px; color: var(--text-dim); }
        .method-tile span { font-size: 14px; font-weight: 600; color: var(--text-dim); }

        .method-tile.active {
            border-color: var(--primary);
            background: rgba(14, 165, 233, 0.05);
            transform: scale(1.02);
        }
        .method-tile.active i, .method-tile.active span { color: var(--primary); }
        .method-tile.active::after {
            content: "\f058";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            left: 15px;
            color: var(--primary);
        }

        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(90deg, var(--secondary), var(--primary));
            border: none;
            border-radius: 16px;
            color: white;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.4s;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 30px -10px rgba(14, 165, 233, 0.5);
            filter: brightness(1.1);
        }

        @media (max-width: 900px) {
            .main-card { grid-template-columns: 1fr; }
            .sidebar-info { padding: 40px; }
            .form-section { padding: 30px; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="merchant-wrapper">
    <div class="main-card">
        <div class="sidebar-info">
            <div>
                <h2>Yemen Gate Business</h2>
                <p>انضم إلى أقوى شبكة دفع عالمية في اليمن. ابدأ باستقبال المدفوعات من عملائك في دقائق معدودة.</p>
                
                <ul class="feature-list">
                    <li><i class="fas fa-check"></i> ربط عالمي عبر API</li>
                    <li><i class="fas fa-check"></i> دعم الحسابات الثلاثة (USD, SAR, YER)</li>
                    <li><i class="fas fa-check"></i> تسوية مالية فورية</li>
                    <li><i class="fas fa-check"></i> حماية ضد الاحتيال</li>
                </ul>
            </div>
            
            <div style="font-size: 12px; opacity: 0.7;">
                © 2026 Yemen Gate Global Financial Systems.
            </div>
        </div>

        <form action="" method="POST" class="form-section">
            <span class="section-label">إعدادات المتجر الأساسية</span>
            
            <div class="form-grid">
                <div class="input-group">
                    <label>اسم النشاط التجاري</label>
                    <input type="text" name="business_name" required class="form-input" 
                           placeholder="مثال: يمن ماركت" value="<?= $merchant ? htmlspecialchars($merchant['business_name']) : '' ?>">
                </div>
                <div class="input-group">
                    <label>تصنيف المتجر</label>
                    <select name="merchant_type" class="form-input">
                        <option value="E-commerce" <?= ($merchant && $merchant['merchant_type'] == 'E-commerce') ? 'selected' : '' ?>>تجارة إلكترونية</option>
                        <option value="SaaS" <?= ($merchant && $merchant['merchant_type'] == 'SaaS') ? 'selected' : '' ?>>منصة برمجية</option>
                        <option value="Services" <?= ($merchant && $merchant['merchant_type'] == 'Services') ? 'selected' : '' ?>>خدمات عامة</option>
                    </select>
                </div>
            </div>

            <div class="input-group">
                <label>عملة تسوية الأرباح</label>
                <select name="settlement_currency" class="form-input" style="border-right: 4px solid var(--primary);">
                    <option value="USD" <?= ($merchant && $merchant['settlement_currency'] == 'USD') ? 'selected' : '' ?>>USD - دولار أمريكي</option>
                    <option value="SAR" <?= ($merchant && $merchant['settlement_currency'] == 'SAR') ? 'selected' : '' ?>>SAR - ريال سعودي</option>
                    <option value="YER" <?= ($merchant && $merchant['settlement_currency'] == 'YER') ? 'selected' : '' ?>>YER - ريال يمني</option>
                </select>
            </div>

            <span class="section-label">طرق الدفع النشطة</span>
            <div class="methods-container">
                <label class="method-tile <?= in_array('yemen_gate_card', $current_methods) ? 'active' : '' ?>">
                    <input type="checkbox" name="payment_methods[]" value="yemen_gate_card" <?= in_array('yemen_gate_card', $current_methods) ? 'checked' : '' ?> onchange="this.parentElement.classList.toggle('active')">
                    <i class="fa-solid fa-credit-card"></i>
                    <span>بطاقة يمن جت</span>
                </label>

                <label class="method-tile <?= in_array('yer_acc', $current_methods) ? 'active' : '' ?>">
                    <input type="checkbox" name="payment_methods[]" value="yer_acc" <?= in_array('yer_acc', $current_methods) ? 'checked' : '' ?> onchange="this.parentElement.classList.toggle('active')">
                    <i class="fa-solid fa-money-bill-1"></i>
                    <span>حساب ريال يمني</span>
                </label>

                <label class="method-tile <?= in_array('sar_acc', $current_methods) ? 'active' : '' ?>">
                    <input type="checkbox" name="payment_methods[]" value="sar_acc" <?= in_array('sar_acc', $current_methods) ? 'checked' : '' ?> onchange="this.parentElement.classList.toggle('active')">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                    <span>حساب ريال سعودي</span>
                </label>

                <label class="method-tile <?= in_array('usd_acc', $current_methods) ? 'active' : '' ?>">
                    <input type="checkbox" name="payment_methods[]" value="usd_acc" <?= in_array('usd_acc', $current_methods) ? 'checked' : '' ?> onchange="this.parentElement.classList.toggle('active')">
                    <i class="fa-solid fa-dollar-sign"></i>
                    <span>حساب دولار أمريكي</span>
                </label>
            </div>

            <div class="input-group">
                <label>رابط الإشعارات (Webhook URL)</label>
                <input type="url" name="webhook_url" class="form-input" 
                       placeholder="https://api.site.com/payment-callback" value="<?= $merchant ? htmlspecialchars($merchant['webhook_url']) : '' ?>">
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-bolt" style="margin-left: 10px;"></i>
                <?= $merchant ? 'تحديث الإعدادات العالمية' : 'إطلاق بوابة المتجر الآن' ?>
            </button>
        </form>
    </div>
</div>

</body>
</html>