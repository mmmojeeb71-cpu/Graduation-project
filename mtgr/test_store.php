<?php
// إعدادات افتراضية للتاجر
$merchant_business_name = "متجر اليمن الرقمي التجريبي";

// التحقق مما إذا كان المستخدم قد أدخل بيانات سلعة جديدة
$p_name = isset($_POST['p_name']) ? $_POST['p_name'] : "ساعة ذكية Ultra";
$p_price = isset($_POST['p_price']) ? floatval($_POST['p_price']) : 25.00;
$p_currency = isset($_POST['p_currency']) ? $_POST['p_currency'] : "USD";
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة منتج وتجربة الدفع | Yemen Gate</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --main-bg: #f8fafc; --card-bg: #ffffff; --primary: #06b6d4; --dark: #0f172a; }
        body { font-family: 'Tajawal', sans-serif; background: var(--main-bg); color: var(--dark); margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        .container { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; max-width: 900px; width: 100%; margin-top: 40px; }
        .setup-section, .preview-section { background: var(--card-bg); padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        h2 { font-size: 20px; margin-bottom: 20px; color: var(--primary); text-align: center; }
        label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: bold; }
        input, select { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #cbd5e1; border-radius: 10px; box-sizing: border-box; }
        .update-btn { background: var(--dark); color: white; border: none; padding: 12px; width: 100%; border-radius: 10px; cursor: pointer; font-weight: bold; }
        .update-btn:hover { background: #1e293b; }
        .product-preview { text-align: center; }
        .product-img { width: 120px; height: 120px; background: #f1f5f9; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 50px; }
        .price-tag { font-size: 32px; font-weight: 800; color: var(--primary); margin: 15px 0; }
        .pay-btn { background: var(--primary); color: white; padding: 18px; border-radius: 12px; text-decoration: none; display: block; font-weight: bold; font-size: 18px; transition: 0.3s; }
        .pay-btn:hover { background: #0891b2; transform: translateY(-3px); box-shadow: 0 10px 15px rgba(6, 182, 212, 0.3); }
        .badge { background: #dcfce7; color: #166534; padding: 5px 10px; border-radius: 5px; font-size: 12px; margin-bottom: 10px; display: inline-block; }
    </style>
</head>
<body>

    <h1 style="margin-bottom: 0;">🧪 بيئة تجربة المتجر (Sandbox)</h1>
    <p style="color: #64748b;">هنا يمكنك محاكاة عملية شراء حقيقية من متجرك إلى بوابتك</p>

    <div class="container">
        <div class="setup-section">
            <h2>📦 إضافة سلعة للتجربة</h2>
            <form method="POST">
                <label>اسم المنتج:</label>
                <input type="text" name="p_name" value="<?= htmlspecialchars($p_name) ?>" placeholder="مثلاً: اشتراك VIP" required>
                
                <label>السعر:</label>
                <input type="number" name="p_price" step="0.01" value="<?= $p_price ?>" required>
                
                <label>العملة:</label>
                <select name="p_currency">
                    <option value="USD" <?= $p_currency == 'USD' ? 'selected' : '' ?>>الدولار الأمريكي (USD)</option>
                    <option value="SAR" <?= $p_currency == 'SAR' ? 'selected' : '' ?>>الريال السعودي (SAR)</option>
                    <option value="YER" <?= $p_currency == 'YER' ? 'selected' : '' ?>>الريال اليمني (YER)</option>
                </select>
                
                <button type="submit" class="update-btn">تحديث بيانات السلعة 🔄</button>
            </form>
        </div>

        <div class="preview-section">
            <div class="product-preview">
                <div class="badge">جاهز للدفع</div>
                <div class="product-img">🎁</div>
                <h3 style="margin: 0;"><?= htmlspecialchars($p_name) ?></h3>
                <p style="color: #64748b; font-size: 14px;">بواسطة: <?= $merchant_business_name ?></p>
                
                <div class="price-tag"><?= number_format($p_price, 2) ?> <span style="font-size: 18px;"><?= $p_currency ?></span></div>
                
                <a href="../Payment_Gateway/pay.php?amount=<?= $p_price ?>&currency=<?= $p_currency ?>&business=<?= urlencode($merchant_business_name) ?>&item=<?= urlencode($p_name) ?>" class="pay-btn">
                    💳 اتمام الشراء الآن
                </a>
                
                <p style="font-size: 12px; color: #94a3b8; margin-top: 15px;">
                    سيتم توجيهك إلى صفحة الدفع الآمنة في <br> <strong>Yemen Gate Payment Gateway</strong>
                </p>
            </div>
        </div>
    </div>

</body>
</html>