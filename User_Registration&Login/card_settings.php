<?php
session_start();
require_once '../Shared/config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login_view.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = "";

// --- معالجة التحديثات البرمجية فور إرسال النموذج ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. معالجة تجميد/تنشيط البطاقة (تم إصلاح منطق التبديل هنا)
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_freeze') {
        $new_status = ($_POST['current_status'] === 'Active') ? 'Frozen' : 'Active';
        $stmt = $pdo->prepare("UPDATE issued_cards SET status = ? WHERE user_id = UNHEX(REPLACE(?, '-', ''))");
        $stmt->execute([$new_status, $user_id]);
        $success_msg = "تم تحديث حالة البطاقة بنجاح.";
    }

    // 2. معالجة إغلاق البطاقة نهائياً
    if (isset($_POST['terminate_card'])) {
        $stmt = $pdo->prepare("DELETE FROM issued_cards WHERE user_id = UNHEX(REPLACE(?, '-', ''))");
        $stmt->execute([$user_id]);
        header("Location: dashboard.php?msg=card_terminated");
        exit();
    }
}

try {
    // جلب بيانات البطاقة الحالية للتأكد من الحالة المحدثة
    $stmtCard = $pdo->prepare("SELECT * FROM issued_cards WHERE user_id = UNHEX(REPLACE(?, '-', '')) LIMIT 1");
    $stmtCard->execute([$user_id]);
    $card = $stmtCard->fetch();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات البطاقة | Yemen Gate</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #06b6d4; --bg: #0b1220; --glass: rgba(255, 255, 255, 0.05); }
        body { background: var(--bg); color: white; font-family: 'Tajawal', sans-serif; margin: 0; padding: 20px; display: flex; justify-content: center; min-height: 100vh; }
        .settings-container { width: 100%; max-width: 450px; background: var(--glass); padding: 30px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(10px); align-self: center; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { color: var(--primary); margin: 5px 0; }
        
        .setting-row { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 20px; background: rgba(0,0,0,0.2); border-radius: 15px; margin-bottom: 15px;
            transition: 0.3s;
        }
        .setting-row:hover { background: rgba(255,255,255,0.05); }
        .setting-info h4 { margin: 0; font-size: 16px; }
        .setting-info p { margin: 5px 0 0; font-size: 12px; color: #94a3b8; }

        /* Switch Toggle Style */
        .switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #334155; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(24px); }

        .danger-zone { margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; }
        .btn-cancel { width: 100%; padding: 15px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid #ef4444; border-radius: 12px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-cancel:hover { background: #ef4444; color: white; }
        .btn-back { display: block; text-align: center; margin-top: 20px; color: #94a3b8; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

<div class="settings-container">
    <div class="header">
        <i class="fa-solid fa-circle-check" style="display: <?= $success_msg ? 'block' : 'none' ?>; color: #10b981; margin-bottom: 10px;"></i>
        <i class="fa-solid fa-gear" style="font-size: 40px; color: var(--primary); display: <?= $success_msg ? 'none' : 'block' ?>;"></i>
        <h2>إعدادات البطاقة</h2>
        <p style="color: #64748b; font-size: 12px;">تحكم كامل بخصائص بطاقتك الافتراضية [cite: 2026-01-13]</p>
    </div>

    <form id="freezeForm" method="POST">
        <input type="hidden" name="action" value="toggle_freeze">
        <input type="hidden" name="current_status" value="<?= $card['status'] ?? 'Active' ?>">
        <div class="setting-row">
            <div class="setting-info">
                <h4>تجميد البطاقة مؤقتاً</h4>
                <p><?= ($card['status'] ?? 'Active') === 'Frozen' ? 'البطاقة متوقفة حالياً' : 'إيقاف جميع العمليات فوراً' ?></p>
            </div>
            <label class="switch">
                <input type="checkbox" onchange="document.getElementById('freezeForm').submit()" <?= ($card['status'] ?? 'Active') === 'Frozen' ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
        </div>
    </form>

    <div class="setting-row">
        <div class="setting-info">
            <h4>حماية العمليات (2FA)</h4>
            <p>طلب رمز التحقق عند كل عملية [cite: 2026-01-13]</p>
        </div>
        <label class="switch">
            <input type="checkbox" checked disabled>
            <span class="slider" style="opacity: 0.6; cursor: not-allowed;"></span>
        </label>
    </div>

    <div class="setting-row" style="cursor: pointer;" onclick="alert('ميزة تعديل الحد اليومي ستتوفر في التحديث القادم')">
        <div class="setting-info">
            <h4>حد الإنفاق اليومي</h4>
            <p>الحالي: 500.00 USD [cite: 2026-01-13]</p>
        </div>
        <i class="fa-solid fa-chevron-left" style="color: #475569;"></i>
    </div>

    <div class="danger-zone">
        <h4 style="color: #ef4444; margin-bottom: 15px;">المنطقة الخطرة</h4>
        <form method="POST" onsubmit="return confirm('⚠️ تحذير: سيتم حذف بيانات البطاقة نهائياً ولا يمكن التراجع. هل أنت متأكد؟')">
            <button type="submit" name="terminate_card" class="btn-cancel">
                <i class="fa-solid fa-trash-can"></i> إغلاق البطاقة نهائياً
            </button>
        </form>
    </div>

    <a href="dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-right"></i> العودة للوحة التحكم</a>
</div>

</body>
</html>