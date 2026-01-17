<?php
require_once 'db.php';
require_once 'auth.php';
require_admin();
check_csrf();

$csrf = ensure_csrf();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    if ($user_id) {
        $uid_bin = uuid_to_bin($user_id);
        try {
            // جلب الحسابات المرتبطة بالمستخدم
            $stmt = $pdo->prepare("SELECT account_id FROM accounts WHERE user_id=:uid");
            $stmt->bindParam(':uid', $uid_bin, PDO::PARAM_LOB);
            $stmt->execute();
            $accounts = $stmt->fetchAll();

            // حذف المعاملات المرتبطة بكل حساب
            foreach ($accounts as $acc) {
                $acc_id = $acc['account_id'];
                $stmtDelTx = $pdo->prepare("DELETE FROM virtual_bank_transactions WHERE account_id=:acc");
                $stmtDelTx->bindParam(':acc', $acc_id, PDO::PARAM_LOB);
                $stmtDelTx->execute();
            }

            // حذف البطاقات المرتبطة بالمستخدم
            $stmt = $pdo->prepare("DELETE FROM issued_cards WHERE user_id=:uid");
            $stmt->bindParam(':uid', $uid_bin, PDO::PARAM_LOB);
            $stmt->execute();

            // حذف الحسابات المرتبطة بالمستخدم
            $stmt = $pdo->prepare("DELETE FROM accounts WHERE user_id=:uid");
            $stmt->bindParam(':uid', $uid_bin, PDO::PARAM_LOB);
            $stmt->execute();

            // حذف التجار المرتبطين بالمستخدم
            $stmt = $pdo->prepare("DELETE FROM merchants WHERE user_id=:uid");
            $stmt->bindParam(':uid', $uid_bin, PDO::PARAM_LOB);
            $stmt->execute();

            // حذف المدفوعات المرتبطة بالتجار
            $stmt = $pdo->prepare("DELETE FROM payments WHERE merchant_id IN (SELECT merchant_id FROM merchants WHERE user_id=:uid)");
            $stmt->bindParam(':uid', $uid_bin, PDO::PARAM_LOB);
            $stmt->execute();

            // حذف سجلات التدقيق المرتبطة بالمستخدم
            $stmt = $pdo->prepare("DELETE FROM audit_logs WHERE user_id=:uid");
            $stmt->bindParam(':uid', $uid_bin, PDO::PARAM_LOB);
            $stmt->execute();

            // أخيراً حذف المستخدم نفسه
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id=:uid");
            $stmt->bindParam(':uid', $uid_bin, PDO::PARAM_LOB);
            $stmt->execute();

            $notice = "✅ تم حذف المستخدم وكل بياناته المرتبطة (معاملات، بطاقات، حسابات، تجار، مدفوعات، سجلات).";
        } catch (PDOException $e) {
            $notice = "❌ خطأ أثناء الحذف: " . htmlspecialchars($e->getMessage());
        }
    }
}

include 'header.php';
?>

<style>
.card {
  border-radius: 12px;
  box-shadow: 0 6px 15px rgba(0,0,0,0.1);
}
.card-body h5 {
  font-weight: 600;
  color: #1f2937;
}
.table-striped thead {
  background: #1f2937;
  color: #fff;
}
.table-striped tbody tr:hover {
  background: #f1f5f9;
}
.btn-danger {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  border: none;
}
.btn-danger:hover {
  background: linear-gradient(135deg, #b91c1c, #7f1d1d);
}
.alert-info {
  background: linear-gradient(135deg, #06b6d4, #4f46e5);
  color: #fff;
  border: none;
}
</style>

<div class="card">
  <div class="card-body">
    <h5 class="mb-3">🗑️ الحذف المتسلسل للمستخدمين</h5>
    <?php if ($notice): ?>
      <div class="alert alert-info"><?= htmlspecialchars($notice) ?></div>
    <?php endif; ?>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>المعرف</th>
            <th>الاسم</th>
            <th>البريد</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->query("SELECT user_id, full_name, email FROM users ORDER BY created_at DESC");
        foreach ($stmt as $row):
          $uid = bin_to_uuid($row['user_id']);
        ?>
          <tr>
            <td><small><?= htmlspecialchars($uid) ?></small></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($uid) ?>">
                <button class="btn btn-sm btn-danger" onclick="return confirm('تأكيد الحذف المتسلسل؟')">🚨 حذف متسلسل</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
