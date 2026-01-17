<?php
session_start();
require_once '../Shared/config.php';

if (!isset($_SESSION['user_id'])) { 
    die("<div style='color:white; background:#0f172a; height:100vh; display:flex; align-items:center; justify-content:center; font-family:sans-serif;'>❌ غير مصرح لك بالدخول</div>"); 
}

$user_id_raw = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM merchants WHERE user_id = UNHEX(REPLACE(?, '-', ''))");
$stmt->execute([$user_id_raw]);
$merchant = $stmt->fetch();

if (!$merchant) { die("❌ يرجى إعداد حساب التاجر أولاً."); }

// حل العلامة الصفراء: التحقق من وجود المفتاح قبل استخدامه
$allowed_methods = [];
if (!empty($merchant['allowed_payment_methods'])) {
    $allowed_methods = json_decode($merchant['allowed_payment_methods'], true) ?? ['yemen_gate_card'];
} else {
    $allowed_methods = ['yemen_gate_card']; // قيمة افتراضية
}

$gateway_url = "http://localhost/YEMEN_GATE/Payment_Gateway/checkout.php";
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مركز المطورين | Yemen Gate Integration</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&family=Tajawal:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0ea5e9;
            --secondary: #6366f1;
            --bg-dark: #020617;
            --card-bg: #0f172a;
            --code-bg: #1e293b;
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --border: #334155;
            --success: #22c55e;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg-dark);
            background-image: radial-gradient(circle at 50% -20%, rgba(99, 102, 241, 0.15), transparent);
            color: var(--text-main);
            margin: 0;
            padding: 40px 20px;
            line-height: 1.6;
        }

        .container { max-width: 1000px; margin: 0 auto; position: relative; }

        /* زر العودة العالمي */
        .back-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .btn-back {
            text-decoration: none;
            color: var(--text-dim);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            padding: 8px 16px;
            border-radius: 12px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
        }
        .btn-back:hover {
            color: var(--text-main);
            background: rgba(255,255,255,0.08);
            transform: translateX(5px);
        }

        .dev-header { text-align: center; margin-bottom: 50px; }
        .dev-header h1 { font-size: 36px; font-weight: 800; margin-bottom: 10px; background: linear-gradient(to left, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .dev-header p { color: var(--text-dim); font-size: 18px; }

        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .status-card { background: var(--card-bg); border: 1px solid var(--border); padding: 20px; border-radius: 20px; display: flex; align-items: center; gap: 15px; }
        .status-card i { font-size: 24px; color: var(--primary); }
        .status-card div span { display: block; font-size: 12px; color: var(--text-dim); }
        .status-card div strong { font-size: 14px; color: var(--text-main); }

        .integration-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 24px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
        .card-header { padding: 20px 30px; background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { margin: 0; font-size: 18px; display: flex; align-items: center; gap: 10px; }
        
        .copy-btn { background: var(--primary); color: var(--bg-dark); border: none; padding: 8px 16px; border-radius: 10px; font-weight: 800; cursor: pointer; font-size: 13px; transition: 0.3s; }
        .copy-btn:hover { transform: scale(1.05); filter: brightness(1.2); }

        .code-area { position: relative; background: #010409; padding: 30px; direction: ltr; text-align: left; font-family: 'Fira Code', monospace; font-size: 14px; color: #d1d5db; overflow-x: auto; }
        .code-area .comment { color: #6a737d; }
        .code-area .tag { color: #7ee787; }
        .code-area .attr { color: #79c0ff; }
        .code-area .val { color: #a5d6ff; }

        .instructions { margin-top: 40px; padding: 30px; background: rgba(14, 165, 233, 0.05); border-radius: 20px; border: 1px dashed var(--primary); }
        .instructions h4 { margin-top: 0; color: var(--primary); }
        .instructions ul { padding-right: 20px; }
        .instructions li { margin-bottom: 10px; font-size: 14px; color: var(--text-dim); }

        .badge-method { background: rgba(99, 102, 241, 0.2); color: var(--secondary); border: 1px solid var(--secondary); padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: bold; margin-right: 5px; }
    </style>
</head>
<body>

<div class="container">
    <div class="back-nav">
        <a href="../User_Registration&Login/dashboard.php" class="btn-back">
            <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
        </a>
        <div class="live-indicator">
            <span style="color: var(--success);">● متصل</span>
        </div>
    </div>

    <div class="dev-header">
        <h1>📦 مركز الدمج البرمجي</h1>
        <p>انسخ الكود المحدث الذي يتضمن خيارات الدفع لعملائك</p>
    </div>

    <div class="status-grid">
        <div class="status-card">
            <i class="fas fa-key"></i>
            <div>
                <span>مفتاح الـ API الحالي</span>
                <strong><?= substr($merchant['api_key'], 0, 12) ?>****</strong>
            </div>
        </div>
        <div class="status-card">
            <i class="fas fa-shield-halved"></i>
            <div>
                <span>نمط العمل</span>
                <strong style="color: var(--success);">الإنتاج (Live Mode)</strong>
            </div>
        </div>
        <div class="status-card">
            <i class="fas fa-wallet"></i>
            <div>
                <span>طرق الدفع المفعلة</span>
                <div style="margin-top:5px;">
                    <?php foreach($allowed_methods as $m): ?>
                        <span class="badge-method"><?= strtoupper(str_replace('_acc', '', $m)) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="integration-card">
        <div class="card-header">
            <h3><i class="fas fa-code"></i> HTML Smart Form</h3>
            <button class="copy-btn" onclick="copyCode()"><i class="fas fa-copy"></i> نسخ الكود</button>
        </div>
        <div class="code-area" id="integrationCode">
<pre>
<span class="comment">&lt;!-- Yemen Gate Global Integration Form --&gt;</span>
<span class="tag">&lt;form</span> <span class="attr">action</span>=<span class="val">"<?= $gateway_url ?>"</span> <span class="attr">method</span>=<span class="val">"POST"</span><span class="tag">&gt;</span>
    <span class="tag">&lt;input</span> <span class="attr">type</span>=<span class="val">"hidden"</span> <span class="attr">name</span>=<span class="val">"api_key"</span> <span class="attr">value</span>=<span class="val">"<?= $merchant['api_key'] ?>"</span><span class="tag">&gt;</span>
    
    <span class="tag">&lt;div</span> <span class="attr">class</span>=<span class="val">"payment-fields"</span><span class="tag">&gt;</span>
        <span class="tag">&lt;label&gt;</span>المبلغ والعملة:<span class="tag">&lt;/label&gt;</span>
        <span class="tag">&lt;input</span> <span class="attr">type</span>=<span class="val">"number"</span> <span class="attr">name</span>=<span class="val">"amount"</span> <span class="attr">step</span>=<span class="val">"0.01"</span> <span class="attr">required</span><span class="tag">&gt;</span>
        <span class="tag">&lt;select</span> <span class="attr">name</span>=<span class="val">"currency"</span><span class="tag">&gt;</span>
            <?php if(in_array('usd_acc', $allowed_methods)): ?><span class="tag">&lt;option</span> <span class="attr">value</span>=<span class="val">"USD"</span><span class="tag">&gt;</span>USD<span class="tag">&lt;/option&gt;</span><?php endif; ?>
            <?php if(in_array('sar_acc', $allowed_methods)): ?><span class="tag">&lt;option</span> <span class="attr">value</span>=<span class="val">"SAR"</span><span class="tag">&gt;</span>SAR<span class="tag">&lt;/option&gt;</span><?php endif; ?>
            <?php if(in_array('yer_acc', $allowed_methods)): ?><span class="tag">&lt;option</span> <span class="attr">value</span>=<span class="val">"YER"</span><span class="tag">&gt;</span>YER<span class="tag">&lt;/option&gt;</span><?php endif; ?>
        <span class="tag">&lt;/select&gt;</span>

        <span class="tag">&lt;div</span> <span class="attr">class</span>=<span class="val">"methods-selector"</span> <span class="attr">style</span>=<span class="val">"margin-top:20px;"</span><span class="tag">&gt;</span>
            <span class="tag">&lt;p&gt;</span>اختر وسيلة الدفع:<span class="tag">&lt;/p&gt;</span>
            <?php if(in_array('yemen_gate_card', $allowed_methods)): ?>
            <span class="tag">&lt;label&gt;</span>
                <span class="tag">&lt;input</span> <span class="attr">type</span>=<span class="val">"radio"</span> <span class="attr">name</span>=<span class="val">"payment_option"</span> <span class="attr">value</span>=<span class="val">"card"</span> <span class="attr">checked</span><span class="tag">&gt;</span> 
                💳 بطاقة يمن جت الإلكترونية
            <span class="tag">&lt;/label&gt;</span>
            <?php endif; ?>
            
            <span class="tag">&lt;label</span> <span class="attr">style</span>=<span class="val">"margin-right:15px;"</span><span class="tag">&gt;</span>
                <span class="tag">&lt;input</span> <span class="attr">type</span>=<span class="val">"radio"</span> <span class="attr">name</span>=<span class="val">"payment_option"</span> <span class="attr">value</span>=<span class="val">"bank"</span><span class="tag">&gt;</span> 
                🏦 تحويل مباشر للحساب
            <span class="tag">&lt;/label&gt;</span>
        <span class="tag">&lt;/div&gt;</span>
    <span class="tag">&lt;/div&gt;</span>

    <span class="tag">&lt;button</span> <span class="attr">type</span>=<span class="val">"submit"</span> <span class="attr">style</span>=<span class="val">"margin-top:20px;"</span><span class="tag">&gt;</span>دفع آمن عبر اليمن جت<span class="tag">&lt;/button&gt;</span>
<span class="tag">&lt;/form&gt;</span>
</pre>
        </div>
    </div>

    <div class="instructions">
        <h4><i class="fas fa-lightbulb"></i> إرشادات الدمج:</h4>
        <ul>
            <li>تأكد من وضع الكود في صفحة مؤمنة بـ <strong>SSL</strong> في متجرك.</li>
            <li>متغير <strong>payment_option</strong> يحدد وجهة العميل فور وصوله لبوابة الدفع.</li>
            <li>يمكنك دائماً تعديل طرق الدفع المسموحة من صفحة "إعدادات التاجر".</li>
        </ul>
    </div>
</div>

<script>
function copyCode() {
    const code = document.getElementById('integrationCode').innerText;
    navigator.clipboard.writeText(code).then(() => {
        const btn = document.querySelector('.copy-btn');
        btn.innerHTML = '<i class="fas fa-check"></i> تم النسخ!';
        btn.style.background = '#22c55e';
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-copy"></i> نسخ الكود';
            btn.style.background = '#0ea5e9';
        }, 2000);
    });
}
</script>

</body>
</html>