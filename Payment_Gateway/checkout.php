<?php
session_start();
require_once '../Shared/config.php';

// 1. استقبال البيانات وتأمينها
$api_key = $_POST['api_key'] ?? '';
$amount  = $_POST['amount'] ?? 0;
$currency = $_POST['currency'] ?? 'USD';
$pay_option = $_POST['payment_option'] ?? 'card'; // الخيار الذي أضفناه سابقاً

// 2. التحقق من هوية التاجر
$stmt = $pdo->prepare("SELECT * FROM merchants WHERE api_key = ?");
$stmt->execute([$api_key]);
$merchant = $stmt->fetch();

if (!$merchant) {
    die("<div style='text-align:center; padding:50px; background:#020617; color:white; height:100vh;'>
            <h2>❌ خطأ في عملية الربط</h2>
            <p>مفتاح التاجر غير صالح أو تم إيقافه.</p>
         </div>");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة الدفع الآمنة | Yemen Gate</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0ea5e9;
            --bg: #020617;
            --card: #0f172a;
            --text: #f8fafc;
            --border: #1e293b;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .checkout-container {
            width: 100%;
            max-width: 450px;
            background: var(--card);
            border-radius: 24px;
            padding: 30px;
            border: 1px solid var(--border);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .merchant-info { text-align: center; margin-bottom: 25px; }
        .merchant-info h3 { margin: 5px 0; font-size: 1.2rem; }
        .amount-badge {
            background: rgba(14, 165, 233, 0.1);
            color: var(--primary);
            padding: 15px;
            border-radius: 15px;
            font-size: 1.5rem;
            font-weight: 800;
            margin: 20px 0;
            border: 1px dashed var(--primary);
        }

        /* تصميم البطاقة الافتراضية */
        .smart-card-input {
            background: #1e293b;
            padding: 20px;
            border-radius: 16px;
            margin-top: 20px;
        }
        
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 0.85rem; color: #94a3b8; }
        .input-group input {
            width: 100%;
            background: #0f172a;
            border: 1px solid var(--border);
            padding: 12px;
            border-radius: 8px;
            color: white;
            box-sizing: border-box;
        }

        .btn-pay {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: 0.3s;
        }
        .btn-pay:hover { filter: brightness(1.2); transform: translateY(-2px); }

        .secure-footer {
            text-align: center;
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="checkout-container">
    <div class="merchant-info">
        <i class="fas fa-shield-check" style="color: #22c55e; font-size: 2rem;"></i>
        <h3>الدفع لـ: <?= htmlspecialchars($merchant['business_name']) ?></h3>
        <p style="color: #94a3b8; font-size: 0.9rem;">معاملة مشفرة وآمنة 100%</p>
    </div>

    <div class="amount-badge">
        <?= number_format($amount, 2) ?> <small><?= $currency ?></small>
    </div>

    <form action="process_payment.php" method="POST">
        <input type="hidden" name="merchant_id" value="<?= $merchant['merchant_id'] ?>">
        <input type="hidden" name="amount" value="<?= $amount ?>">
        <input type="hidden" name="currency" value="<?= $currency ?>">

        <?php if($pay_option == 'card'): ?>
            <div class="smart-card-input">
                <div class="input-group">
                    <label>رقم بطاقة يمن جت (16 رقم)</label>
                    <input type="text" name="card_number" placeholder="0000 0000 0000 0000" required>
                </div>
                <div style="display: flex; gap: 10px;">
                    <div class="input-group" style="flex: 2;">
                        <label>تاريخ الانتهاء</label>
                        <input type="text" name="expiry" placeholder="MM/YY" required>
                    </div>
                    <div class="input-group" style="flex: 1;">
                        <label>CVV</label>
                        <input type="password" name="cvv" placeholder="***" required>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 16px; text-align: center;">
                <i class="fas fa-university" style="font-size: 2rem; margin-bottom: 15px;"></i>
                <p>سيتم تزويدك بأرقام الحسابات (دولار/سعودي/يمني) في الصفحة التالية لإتمام التحويل.</p>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn-pay">
            <i class="fas fa-lock"></i> تأكيد دفع مبلغ <?= number_format($amount, 2) ?>
        </button>
    </form>

    <div class="secure-footer">
        <i class="fas fa-shield-halved"></i> مدعوم بواسطة بوابة اليمن جت العالمية
    </div>
</div>

</body>
</html>