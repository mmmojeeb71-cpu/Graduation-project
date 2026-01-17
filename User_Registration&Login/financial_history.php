<?php
session_start();
require_once '../Shared/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login_view.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // جلب جميع المعاملات المرتبطة بحسابات المستخدم
    $stmt = $pdo->prepare("
        SELECT 
            t.transaction_id, 
            t.amount, 
            t.transaction_type, 
            t.status, 
            t.created_at, 
            a.account_number, 
            a.currency
        FROM virtual_bank_transactions t
        JOIN accounts a ON t.account_id = a.account_id
        WHERE a.user_id = UNHEX(REPLACE(?, '-', ''))
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}

// دالة لتنسيق نوع العملية
function formatType($type) {
    switch($type) {
        case 'TOP_UP': return 'إيداع (قسيمة بنكية)';
        case 'WITHDRAW': return 'سحب نقدي';
        case 'TRANSFER_IN': return 'حوالة واردة';
        case 'TRANSFER_OUT': return 'حوالة صادرة';
        default: return $type;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>السجل المالي الكامل | Yemen Gate</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #0b1220; color: #e5e7eb; font-family: sans-serif; margin: 0; padding: 20px; }
        .history-container { max-width: 1000px; margin: 0 auto; background: #1e293b; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .header-box { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 20px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #0f172a; color: #94a3b8; text-align: right; padding: 15px; font-size: 13px; }
        td { padding: 15px; border-bottom: 1px solid #334155; font-size: 14px; }
        .type-badge { padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; }
        .plus { color: #10b981; }
        .minus { color: #ef4444; }
        .btn-back { color: #06b6d4; text-decoration: none; font-size: 14px; }
        .status-done { color: #10b981; font-size: 12px; }
        @media print { .btn-back { display: none; } body { background: white; color: black; } .history-container { box-shadow: none; } }
    </style>
</head>
<body>

<div class="history-container">
    <div class="header-box">
        <div>
            <h2 style="margin:0;">كشف الحساب الموحد</h2>
            <small style="color: #94a3b8;">عرض كافة العمليات المالية والتحويلات</small>
        </div>
        <a href="dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-right"></i> العودة للوحة التحكم</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>رقم الحساب</th>
                <th>نوع العملية</th>
                <th>المبلغ</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            <?php if($transactions): ?>
                <?php foreach($transactions as $tx): 
                    $is_positive = in_array($tx['transaction_type'], ['TOP_UP', 'TRANSFER_IN']);
                ?>
                <tr>
                    <td><?= date('Y/m/d H:i', strtotime($tx['created_at'])) ?></td>
                    <td style="font-family: monospace;"><?= $tx['account_number'] ?></td>
                    <td><?= formatType($tx['transaction_type']) ?></td>
                    <td class="<?= $is_positive ? 'plus' : 'minus' ?>" style="font-weight:bold;">
                        <?= ($is_positive ? '+' : '-') . number_format($tx['amount'], 2) ?> 
                        <small><?= $tx['currency'] ?></small>
                    </td>
                    <td class="status-done"><i class="fa-solid fa-check-circle"></i> مكتملة</td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding: 40px; color: #94a3b8;">لا توجد سجلات مالية حتى الآن.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="background: #334155; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">
            <i class="fa-solid fa-print"></i> طباعة كشف الحساب
        </button>
    </div>
</div>

</body>
</html>