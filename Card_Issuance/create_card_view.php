<?php
session_start();
// حل مشكلة Fatal error: Call to a member function prepare() on null
require_once '../Shared/config.php'; 

if (!isset($_SESSION['user_id'])) { 
    header("Location: ../User_Registration&Login/login_view.php");
    exit(); 
}

// جلب أحدث بيانات البطاقة مع معلومات حساب الدولار المرتبط بها
// [تعديل] جلب أعمدة العملات الجديدة (balance_yer, balance_sar, balance_usd)
$stmt = $pdo->prepare("
    SELECT ic.*, a.currency, a.account_number 
    FROM issued_cards ic 
    JOIN accounts a ON ic.account_id = a.account_id 
    WHERE ic.user_id = UNHEX(REPLACE(?, '-', '')) 
    ORDER BY ic.created_at DESC LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$card = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بطاقة يمن جت - Yemen Gate Card</title>
    <style>
        /* التنسيق العالمي للبطاقة */
        .yg-card {
            width: 370px; height: 215px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 18px; padding: 25px; color: white;
            position: relative; border: 1px solid rgba(255,255,255,0.1);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 50px auto 20px auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            overflow: hidden;
        }
        .yg-card::after {
            content: ""; position: absolute; top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
            pointer-events: none;
        }
        .yg-logo { color: #fbbf24; font-weight: 800; letter-spacing: 2px; font-size: 1.2rem; }
        .yg-type { float: left; font-size: 0.7rem; color: #94a3b8; letter-spacing: 1px; }
        .yg-chip { width: 48px; height: 36px; background: linear-gradient(135deg, #94a3b8, #cbd5e1); border-radius: 6px; margin: 20px 0; }
        .yg-number { font-size: 1.4rem; letter-spacing: 3px; font-family: 'Courier New', monospace; margin-bottom: 25px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .yg-footer { display: flex; justify-content: space-between; font-size: 0.8rem; text-transform: uppercase; }
        
        /* [إضافة] تنسيق محافظ العملات الثلاث */
        .card-wallets {
            display: flex; justify-content: center; gap: 15px; margin-bottom: 30px;
        }
        .wallet-pill {
            background: #1e293b; padding: 10px 15px; border-radius: 12px;
            border: 1px solid #334155; text-align: center; min-width: 90px;
        }
        .wallet-pill small { display: block; color: #fbbf24; font-size: 0.65rem; margin-bottom: 4px; font-weight: bold; }
        .wallet-pill span { font-size: 0.9rem; font-weight: 600; color: #f8fafc; }

        /* إشعار النجاح المنبثق (Toast) */
        .toast {
            position: fixed; top: 30px; left: 50%; transform: translateX(-50%);
            background: #10b981; color: white; padding: 15px 35px;
            border-radius: 12px; z-index: 1000; font-weight: bold;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            display: flex; align-items: center; gap: 10px;
            animation: fadeInOut 5s forwards;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; top: 0; }
            10% { opacity: 1; top: 30px; }
            90% { opacity: 1; top: 30px; }
            100% { opacity: 0; top: 0; }
        }
    </style>
</head>
<body>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div id="toast" class="toast">
        <span>✅ تم إنشاء وحفظ بطاقة "يمن جت" بنجاح!</span>
    </div>
    <script>
        // التوجيه التلقائي بعد 6 ثوانٍ ليتسنى للمستخدم رؤية بيانات البطاقة
        setTimeout(() => { 
            window.location.href = '../User_Registration&Login/dashboard.php'; 
        }, 6000); 
    </script>
<?php endif; ?>

<?php if ($card): ?>
    <div class="yg-card">
        <span class="yg-type">PREMIUM DEBIT</span>
        <div class="yg-logo">YEMEN GATE</div>
        <div class="yg-chip"></div>
        <div class="yg-number"><?php echo htmlspecialchars($card['masked_pan']); ?></div>
        <div class="yg-footer">
            <div>
                <small style="display:block; opacity:0.6; font-size: 0.6rem; margin-bottom: 3px;">CARD HOLDER</small>
                <?php echo htmlspecialchars($card['card_holder']); ?>
            </div>
            <div>
                <small style="display:block; opacity:0.6; font-size: 0.6rem; margin-bottom: 3px;">EXPIRES</small>
                <?php echo $card['expiry_month']."/".substr($card['expiry_year'], -2); ?>
            </div>
        </div>
        <div style="position:absolute; bottom:12px; right:20px; font-size:0.5rem; opacity:0.4;">
            LINKED TO: <?php echo $card['currency']; ?> (****<?php echo substr($card['account_number'], -4); ?>)
        </div>
    </div>

    <div class="card-wallets">
        <div class="wallet-pill">
            <small>YER WALLET</small>
            <span><?php echo number_format($card['balance_yer'], 0); ?></span>
        </div>
        <div class="wallet-pill">
            <small>SAR WALLET</small>
            <span><?php echo number_format($card['balance_sar'], 2); ?></span>
        </div>
        <div class="wallet-pill">
            <small>USD WALLET</small>
            <span><?php echo number_format($card['balance_usd'], 2); ?></span>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <p style="color: #64748b; font-size: 0.9rem;">سيتم توجيهك إلى لوحة التحكم تلقائياً...</p>
        <div style="display: flex; justify-content: center; gap: 20px;">
            <a href="topup_card.php" style="color: #fbbf24; text-decoration: none; font-weight: bold;">+ شحن البطاقة</a>
            <a href="../User_Registration&Login/dashboard.php" style="color: #3b82f6; text-decoration: none; font-weight: bold;">العودة الآن</a>
        </div>
    </div>

<?php else: ?>
    <div style="text-align:center; margin-top:100px; font-family: sans-serif;">
        <h3>لا توجد بطاقة نشطة حالياً</h3>
        <p>لبدء استخدام خدماتنا العالمية، يرجى إصدار بطاقتك الأولى.</p>
        <a href="create_card_logic.php" style="display:inline-block; padding:10px 25px; background:#1e293b; color:white; border-radius:8px; text-decoration:none;">إصدار بطاقة يمن جت</a>
    </div>
<?php endif; ?>

</body>
</html>