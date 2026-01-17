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
    // 1. جلب بيانات التاجر والمتجر
    $stmt_merchant = $pdo->prepare("SELECT * FROM merchants WHERE user_id = UNHEX(REPLACE(?, '-', '')) LIMIT 1");
    $stmt_merchant->execute([$user_id]);
    $merchant = $stmt_merchant->fetch();

    if (!$merchant) {
        header("Location: become_merchant.php");
        exit();
    }

    // 2. جلب إحصائيات المبيعات (إجمالي المبالغ حسب الحالة)
    $stmt_stats = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_sales,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as sales_count,
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount
        FROM merchant_transactions 
        WHERE merchant_id = ?
    ");
    $stmt_stats->execute([$merchant['merchant_id']]);
    $stats = $stmt_stats->fetch();

    // 3. جلب آخر 10 عمليات دفع
    $stmt_trans = $pdo->prepare("
        SELECT * FROM merchant_transactions 
        WHERE merchant_id = ? 
        ORDER BY created_at DESC LIMIT 10
    ");
    $stmt_trans->execute([$merchant['merchant_id']]);
    $transactions = $stmt_trans->fetchAll();

} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المبيعات | Yemen Gate</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #06b6d4; --bg: #0b1220; --card-bg: #1e293b; --text: #f8fafc; }
        body { background: var(--bg); color: var(--text); font-family: 'Tajawal', sans-serif; margin: 0; padding: 20px; }
        .dashboard-wrapper { max-width: 1100px; margin: 0 auto; }
        
        /* Header */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .shop-badge { background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 8px 15px; border-radius: 20px; font-size: 14px; border: 1px solid #10b981; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: var(--card-bg); padding: 25px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); text-align: center; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); border-color: var(--primary); }
        .stat-card i { font-size: 30px; color: var(--primary); margin-bottom: 15px; }
        .stat-card h3 { margin: 0; font-size: 24px; }
        .stat-card p { color: #94a3b8; margin: 5px 0 0; font-size: 14px; }

        /* Table Section */
        .table-container { background: var(--card-bg); border-radius: 20px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); }
        .table-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; }
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(0,0,0,0.2); text-align: right; padding: 15px; color: #94a3b8; font-size: 13px; }
        td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
        
        .status { padding: 5px 10px; border-radius: 8px; font-size: 12px; font-weight: bold; }
        .status-completed { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

        .btn-action { color: var(--primary); text-decoration: none; font-size: 18px; }
        .back-link { color: #94a3b8; text-decoration: none; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <div class="header">
        <div>
            <h1 style="margin:0;">لوحة المبيعات 🚀</h1>
            <p style="color:#94a3b8;">مرحباً بك، متجر: <strong><?= htmlspecialchars($merchant['business_name']) ?></strong></p>
        </div>
        <div class="shop-badge"><i class="fa-solid fa-circle-check"></i> حساب تاجر موثق</div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <i class="fa-solid fa-wallet"></i>
            <h3>$<?= number_format($stats['total_sales'] ?? 0, 2) ?></h3>
            <p>إجمالي المبيعات المكتملة</p>
        </div>
        <div class="stat-card">
            <i class="fa-solid fa-cart-shopping"></i>
            <h3><?= $stats['sales_count'] ?? 0 ?></h3>
            <p>عدد الطلبات الناجحة</p>
        </div>
        <div class="stat-card">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <h3>$<?= number_format($stats['pending_amount'] ?? 0, 2) ?></h3>
            <p>مبالغ قيد الانتظار</p>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <h3 style="margin:0;">آخر عمليات الدفع</h3>
            <a href="#" class="btn-action" title="تصدير البيانات"><i class="fa-solid fa-file-export"></i></a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>رقم العملية</th>
                    <th>العميل</th>
                    <th>المبلغ</th>
                    <th>العملة</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($transactions) > 0): ?>
                    <?php foreach($transactions as $tx): ?>
                        <tr>
                            <td>#<?= substr($tx['transaction_id'], 0, 8) ?>...</td>
                            <td><?= htmlspecialchars($tx['customer_email'] ?? 'عميل خارجي') ?></td>
                            <td style="font-weight: bold;">$<?= number_format($tx['amount'], 2) ?></td>
                            <td><?= $tx['currency'] ?></td>
                            <td>
                                <span class="status status-<?= $tx['status'] ?>">
                                    <?= $tx['status'] === 'completed' ? 'مكتملة' : 'معلقة' ?>
                                </span>
                            </td>
                            <td style="color:#94a3b8;"><?= date('Y-m-d H:i', strtotime($tx['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 40px; color:#94a3b8;">لا توجد عمليات بيع حتى الآن.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="merchant_hub.php" class="back-link"><i class="fa-solid fa-arrow-right"></i> العودة لمركز التجار</a>
</div>

</body>
</html>