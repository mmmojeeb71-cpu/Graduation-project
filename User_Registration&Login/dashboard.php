<?php
session_start();
// التأكد من استدعاء ملف الإعدادات لتعريف $pdo
require_once '../Shared/config.php'; 

// --- إضافة منطق إنهاء الجلسة التلقائي (15 دقيقة) ---
$timeout_duration = 900; // 15 دقيقة بالثواني

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: login_view.php?reason=timeout");
    exit();
}
$_SESSION['last_activity'] = time(); // تحديث وقت النشاط
// ------------------------------------------------

// 1. التحقق من تسجيل الدخول الصارم
if (!isset($_SESSION['user_id'])) {
    header("Location: login_view.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // 2. جلب بيانات المستخدم الأساسية
    $stmt_user = $pdo->prepare("SELECT full_name, role FROM users WHERE user_id = UNHEX(REPLACE(?, '-', ''))");
    $stmt_user->execute([$user_id]);
    $user_info = $stmt_user->fetch();

    if (!$user_info) { session_destroy(); header("Location: login_view.php"); exit(); }

    $accounts = [];
    $card = null;
    $is_merchant = false;
    $merchant_data = null;

    // 3. جلب بيانات الحسابات والبطاقة (للمستخدم العادي والتاجر)
    if ($user_info['role'] !== 'admin') {
        // جلب الحسابات الثلاثة (YEMENI, SAUDI, DOLLAR)
        $stmt = $pdo->prepare("SELECT HEX(account_id) as acc_id_hex, balance, currency, account_number FROM accounts WHERE user_id = UNHEX(REPLACE(?, '-', '')) ORDER BY currency DESC");
        $stmt->execute([$user_id]);
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // جلب أحدث بطاقة نشطة
        $stmt_card = $pdo->prepare("SELECT * FROM issued_cards WHERE user_id = UNHEX(REPLACE(?, '-', '')) ORDER BY created_at DESC LIMIT 1");
        $stmt_card->execute([$user_id]);
        $card = $stmt_card->fetch();

        // جلب بيانات التاجر إذا وجدت
        $stmt_m = $pdo->prepare("SELECT * FROM merchants WHERE user_id = UNHEX(REPLACE(?, '-', ''))");
        $stmt_m->execute([$user_id]);
        $merchant_data = $stmt_m->fetch();
        $is_merchant = (bool)$merchant_data;
    }

} catch (Exception $e) {
    die("<div style='color:white; background:red; padding:20px;'>❌ خطأ تقني في الاتصال بالنظام العالمي: " . $e->getMessage() . "</div>");
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>Yemen Gate | Dashboard & Merchant Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b1220; --bg-2: #0f172a; --text: #e5e7eb; --muted: #94a3b8;
            --primary-1: #06b6d4; --primary-2: #4f46e5; --success: #10b981;
            --danger: #ef4444; --warning: #f59e0b; --card: rgba(255,255,255,0.04);
            --glass: rgba(255,255,255,0.07); --border: rgba(255,255,255,0.1);
        }
        * { box-sizing: border-box; transition: all 0.2s ease; }
        body {
            margin: 0; font-family: 'Tajawal', sans-serif; color: var(--text);
            background: radial-gradient(circle at top right, rgba(6,182,212,0.1), transparent), var(--bg);
            min-height: 100vh; overflow-x: hidden;
        }

        .header { position: sticky; top: 0; z-index: 100; display: flex; align-items: center; justify-content: space-between; padding: 15px 5%; backdrop-filter: blur(25px); background: rgba(11,18,32,0.7); border-bottom: 1px solid var(--border); }
        .brand h1 { font-size: 20px; letter-spacing: 1px; margin: 0; background: linear-gradient(to left, #fff, var(--primary-1)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .grid { display: grid; gap: 25px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }

        .card { background: var(--card); border: 1px solid var(--border); border-radius: 24px; padding: 25px; position: relative; }
        .card:hover { border-color: var(--primary-1); transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }

        .balance { font-size: 38px; font-weight: 800; margin: 15px 0; color: #fff; display: flex; align-items: baseline; gap: 10px; }
        .currency-symbol { font-size: 18px; color: var(--primary-1); font-weight: 600; }

        .copy-box { 
            background: rgba(255,255,255,0.05); border: 1px solid var(--border); 
            padding: 8px 12px; border-radius: 12px; cursor: pointer; 
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;
        }
        .copy-box:hover { border-color: var(--primary-1); background: rgba(255,255,255,0.1); }

        .actions-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .btn-action { 
            padding: 10px; border-radius: 12px; border: 1px solid var(--border); 
            background: var(--glass); color: var(--text); text-decoration: none; 
            font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px;
        }
        .btn-action:hover { background: #fff; color: #000; border-color: #fff; }
        .btn-primary { background: var(--primary-1); color: #000; border: none; }
        
        /* زر شحن البطاقة المميز */
        .btn-topup-card {
            grid-column: span 2; background: linear-gradient(90deg, #f59e0b, #d97706);
            color: #000 !important; border: none !important; padding: 14px !important;
            font-size: 15px !important; margin-top: 10px; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        .btn-topup-card:hover { transform: scale(1.02); filter: brightness(1.1); }

        .btn-transfer { grid-column: span 2; border-color: rgba(79, 70, 229, 0.4); color: #a5b4fc; }

        .v-card {
            background: linear-gradient(135deg, #1e293b 0%, #020617 100%);
            height: 200px; border-radius: 20px; padding: 25px; position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6); border: 1px solid rgba(255,215,0,0.2);
            overflow: hidden; margin-top: 15px;
        }
        .v-card .chip { width: 45px; height: 32px; background: linear-gradient(45deg, #fbbf24, #d97706); border-radius: 6px; margin-bottom: 25px; }
        .v-card .number { font-size: 20px; letter-spacing: 3px; font-family: 'Courier New', monospace; color: #fff; }

        .card-bal-row { display: flex; gap: 5px; margin-top: 12px; }
        .bal-chip { flex: 1; background: rgba(255,255,255,0.05); padding: 5px; border-radius: 8px; text-align: center; font-size: 11px; border: 1px solid var(--border); }
        .bal-chip small { color: var(--primary-1); display: block; }

        .modal { position: fixed; inset: 0; background: rgba(0,0,0,0.9); display: none; align-items: center; justify-content: center; z-index: 2000; backdrop-filter: blur(15px); }
        .modal.active { display: flex; }
        .modal-content { background: var(--bg-2); border-radius: 30px; padding: 40px; width: 90%; max-width: 420px; text-align: center; border: 1px solid var(--primary-1); }
    </style>
</head>
<body>

    <div id="notifyModal" class="modal">
        <div class="modal-content">
            <div id="modalIcon" style="font-size: 60px; margin-bottom: 20px;"></div>
            <h2 id="modalTitle" style="color:#fff;"></h2>
            <p id="modalMsg" style="color:var(--muted); font-size:16px; margin: 15px 0 30px;"></p>
            <button id="modalBtn" onclick="closeModal()" class="btn-action btn-primary" style="width:100%">تـم</button>
        </div>
    </div>

    <header class="header">
        <div class="brand">
            <h1>YEMEN GATE <span style="font-weight:300; opacity:0.5;">HUB</span></h1>
        </div>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="text-align: left;">
                <small style="color:var(--muted); font-size:10px;">USER SESSION</small>
                <div style="font-weight:700;"><?= htmlspecialchars($user_info['full_name']); ?></div>
            </div>
            <a href="logout.php" class="btn-action" style="padding: 10px; border-radius: 50%;"><i class="fa-solid fa-power-off" style="color:var(--danger)"></i></a>
        </div>
    </header>

    <main class="container">
        
        <div style="margin-bottom: 40px;">
            <h2 style="font-size: 32px; margin:0;">لوحة <span style="color:var(--primary-1)">التحكم</span></h2>
            <p style="color: var(--muted);">قم بإدارة حساباتك المتعددة وخدمات التاجر من مكان واحد.</p>
        </div>

        <section class="grid">
            <?php foreach ($accounts as $acc): ?>
            <div class="card">
                <div style="display:flex; justify-content:space-between;">
                    <span style="font-size:11px; font-weight:800; color:var(--primary-1); background:rgba(6,182,212,0.1); padding:4px 10px; border-radius:10px;">
                        <?= $acc['currency'] ?> GLOBAL
                    </span>
                    <i class="fa-solid fa-circle-check" style="color: var(--success); font-size:12px;"> Active</i>
                </div>
                <div class="balance">
                    <span class="currency-symbol"><?= $acc['currency']; ?></span> 
                    <?= number_format($acc['balance'], 2); ?>
                </div>
                
                <div class="copy-box" onclick="copyValue('<?= $acc['account_number']; ?>', this)">
                    <span style="font-size: 12px; font-family: monospace;"><?= $acc['account_number']; ?></span>
                    <i class="fa-regular fa-copy"></i>
                </div>
                
                <div class="actions-grid">
                    <a href="../Bank_Simulator/virtual_bank_gateway.php?acc=<?= $acc['acc_id_hex']; ?>" class="btn-action"><i class="fa-solid fa-plus"></i> شحن</a>
                    <a href="../Bank_Simulator/withdraw.php?acc=<?= $acc['acc_id_hex']; ?>" class="btn-action"><i class="fa-solid fa-wallet"></i> سحب</a>
                    <a href="transfer_view.php?from=<?= $acc['acc_id_hex']; ?>" class="btn-action btn-transfer"><i class="fa-solid fa-paper-plane"></i> تحويل سريع</a>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="card">
                <h3 style="margin:0; font-size:18px;"><i class="fa-solid fa-credit-card" style="color:var(--warning)"></i> بطاقة يمن جت</h3>
                <?php if ($card): ?>
                    <div class="v-card">
                        <div class="chip"></div>
                        <div class="number"><?= htmlspecialchars($card['card_number'] ?? '**** **** **** ****'); ?></div>
                        <div style="display:flex; justify-content:space-between; margin-top:30px;">
                            <div style="font-size:10px;">
                                <small>HOLDER</small><br>
                                <strong><?= strtoupper(htmlspecialchars($user_info['full_name'])); ?></strong>
                            </div>
                            <div style="font-size:20px; font-weight:900; font-style:italic; opacity:0.6;">VISA</div>
                        </div>
                    </div>

                    <div class="actions-grid" style="margin-top: 15px;">
                        <a href="../Card_Issuance/topup_card.php" class="btn-action btn-topup-card">
                            <i class="fa-solid fa-bolt"></i> شـحن رصـيد البطـاقة
                        </a>
                        <a href="card_details.php" class="btn-action"><i class="fa-solid fa-eye"></i> تفاصيل</a>
                        <a href="card_settings.php" class="btn-action"><i class="fa-solid fa-cog"></i> إعدادات</a>
                    </div>

                    <div class="card-bal-row">
                        <?php foreach($accounts as $ab): ?>
                            <div class="bal-chip">
                                <small><?= $ab['currency'] ?></small>
                                <strong><?= number_format($ab['balance'], 0) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding: 30px; border: 2px dashed var(--border); border-radius: 20px; margin-top:15px;">
                        <p style="color: var(--muted); font-size:14px;">لم يتم إصدار بطاقة.</p>
                        <a href="../Card_Issuance/create_card_logic.php" class="btn-action btn-primary">إصدار بطاقة الآن</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="grid" style="margin-top: 25px;">
            <div class="card">
                <h3><i class="fa-solid fa-rocket" style="color:var(--primary-1)"></i> مركز التجار (Merchant Hub)</h3>
                <?php if ($is_merchant): ?>
                    <div style="background:rgba(16,185,129,0.1); padding:15px; border-radius:15px; margin-bottom:15px; border: 1px solid rgba(16,185,129,0.2);">
                        <div style="font-weight:700; color:var(--success); margin-bottom:5px;">✅ متجر معتمد نشط</div>
                        <small style="color:var(--muted)">اسم المتجر: <?= htmlspecialchars($merchant_data['business_name'] ?? 'غير محدد'); ?></small>
                    </div>
                    <div style="display:grid; gap:10px;">
                        <a href="../Payment_Gateway/merchant_dashboard.php" class="btn-action btn-primary"><i class="fa-solid fa-chart-line"></i> لوحة تحكم المبيعات</a>
                        <a href="../Payment_Gateway/api_settings.php" class="btn-action"><i class="fa-solid fa-key"></i> مفاتيح الربط API Keys</a>
                        <a href="../Payment_Gateway/documentation.php" class="btn-action"><i class="fa-solid fa-book"></i> دليل المطورين</a>
                    </div>
                <?php else: ?>
                    <p style="color:var(--muted); font-size:14px;">ابدأ باستقبال المدفوعات على موقعك الإلكتروني أو تطبيقك عبر بوابتنا العالمية.</p>
                    <a href="../Payment_Gateway/become_merchant.php" class="btn-action btn-primary"><i class="fa-solid fa-store"></i> تفعيل حساب التاجر مجاناً</a>
                <?php endif; ?>
            </div>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin:0;"><i class="fa-solid fa-history"></i> العمليات</h3>
                    <a href="financial_history.php" class="history-link"><i class="fa-solid fa-chevron-left"></i></a>
                </div>
                <div id="logs-container">
                    <?php
                    $stmt_logs = $pdo->prepare("SELECT t.*, a.currency FROM virtual_bank_transactions t JOIN accounts a ON t.account_id = a.account_id WHERE a.user_id = UNHEX(REPLACE(?, '-', '')) ORDER BY t.created_at DESC LIMIT 4");
                    $stmt_logs->execute([$user_id]);
                    $logs = $stmt_logs->fetchAll();
                    if($logs): foreach($logs as $l): $is_plus = in_array($l['transaction_type'], ['TOP_UP', 'TRANSFER_IN']); ?>
                        <div style="display:flex; justify-content:space-between; padding: 10px 0; border-bottom: 1px solid var(--border);">
                            <div>
                                <div style="font-size:13px; font-weight:700;"><?= $l['transaction_type']; ?></div>
                                <small style="color:var(--muted)"><?= date('d M', strtotime($l['created_at'])) ?></small>
                            </div>
                            <div style="text-align: right;">
                                <span style="font-weight:800; color:<?= $is_plus ? 'var(--success)' : 'var(--danger)'; ?>">
                                    <?= ($is_plus ? '+' : '-') . number_format($l['amount'], 2); ?>
                                </span>
                                <div style="font-size: 10px; opacity:0.6;"><?= $l['currency'] ?></div>
                            </div>
                        </div>
                    <?php endforeach; else: echo "<p style='color:var(--muted); text-align:center;'>لا توجد عمليات</p>"; endif; ?>
                </div>
            </div>
        </section>
    </main>

    <script>
        // --- إضافة مراقبة الجلسة عبر JavaScript ---
        let idleTime = 0;
        const maxIdle = 15; // 15 دقيقة

        // إعادة ضبط العداد عند وجود حركة
        window.onmousemove = resetTimer;
        window.onkeypress = resetTimer;
        window.onclick = resetTimer;

        function resetTimer() { idleTime = 0; }

        setInterval(function() {
            idleTime++;
            if (idleTime >= maxIdle) {
                showTimeoutModal();
            }
        }, 60000); // كل دقيقة

        function showTimeoutModal() {
            document.getElementById('modalTitle').innerText = "انتهت الجلسة";
            document.getElementById('modalIcon').innerHTML = "⏳";
            document.getElementById('modalMsg').innerText = "لقد كنت غير نشط لمدة 15 دقيقة. يرجى تسجيل الدخول مرة أخرى لحماية حسابك.";
            document.getElementById('modalBtn').innerText = "تسجيل الدخول";
            document.getElementById('modalBtn').onclick = function() { window.location.href = "logout.php"; };
            document.getElementById('notifyModal').classList.add('active');
        }
        // ------------------------------------------

        function copyValue(text, element) {
            navigator.clipboard.writeText(text).then(() => {
                const original = element.innerHTML;
                element.innerHTML = '<span style="color:var(--success); font-size:12px;">تم النسخ!</span><i class="fa-solid fa-check" style="color:var(--success)"></i>';
                element.style.borderColor = "var(--success)";
                setTimeout(() => {
                    element.innerHTML = original;
                    element.style.borderColor = "var(--border)";
                }, 1500);
            });
        }

        function closeModal() { document.getElementById('notifyModal').classList.remove('active'); }

        window.onload = function() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('status') === 'success') {
                document.getElementById('modalTitle').innerText = "نجحت العملية";
                document.getElementById('modalIcon').innerHTML = "✅";
                document.getElementById('modalMsg').innerText = "تمت معالجة طلبك وتحديث الأرصدة.";
                document.getElementById('notifyModal').classList.add('active');
            }
            if (params.get('reason') === 'timeout') {
                showTimeoutModal();
            }
        }
    </script>
</body>
</html>