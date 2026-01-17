<?php
require_once 'db.php';
require_once 'auth.php';
require_admin();
check_csrf();

$csrf = ensure_csrf();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update') {
        $account_id = $_POST['account_id'] ?? '';
        $balance = (float)($_POST['balance'] ?? 0);
        $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));
        if ($account_id && $currency) {
            $stmt = $pdo->prepare("UPDATE accounts SET balance=:balance, currency=:currency WHERE account_id=:id");
            $stmt->bindParam(':balance', $balance);
            $stmt->bindParam(':currency', $currency);
            $stmt->bindParam(':id', uuid_to_bin($account_id), PDO::PARAM_LOB);
            $stmt->execute();
            $notice = '✅ تم تحديث الحساب بنجاح.';
        }
    }
}

include 'header.php';
if ($notice) echo '<div class="alert alert-info">'.$notice.'</div>';
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
.btn-outline-primary {
  border-color: #4f46e5;
  color: #4f46e5;
}
.btn-outline-primary:hover {
  background: #4f46e5;
  color: #fff;
}
.btn-success {
  background: linear-gradient(135deg, #06b6d4, #4f46e5);
  border: none;
}
.btn-success:hover {
  background: linear-gradient(135deg, #0e7490, #4338ca);
}
.alert-info {
  background: linear-gradient(135deg, #06b6d4, #4f46e5);
  color: #fff;
  border: none;
}
</style>

<div class="card">
  <div class="card-body">
    <h5 class="mb-3">🏦 الحسابات البنكية</h5>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>المعرف</th>
            <th>المستخدم</th>
            <th>رقم الحساب</th>
            <th>الرصيد</th>
            <th>العملة</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->query("SELECT a.account_id, a.user_id, a.account_number, a.balance, a.currency 
                             FROM accounts a ORDER BY a.account_number ASC");
        foreach ($stmt as $row):
          $aid = bin_to_uuid($row['account_id']);
          $uid = bin_to_uuid($row['user_id']);
        ?>
          <tr>
            <td><small><?= htmlspecialchars($aid) ?></small></td>
            <td><small><?= htmlspecialchars($uid) ?></small></td>
            <td><?= htmlspecialchars($row['account_number']) ?></td>
            <td><?= htmlspecialchars(number_format((float)$row['balance'], 2)) ?></td>
            <td><?= htmlspecialchars($row['currency']) ?></td>
            <td>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-<?= htmlspecialchars($aid) ?>">✏️ تعديل</button>
            </td>
          </tr>
          <tr class="collapse" id="edit-<?= htmlspecialchars($aid) ?>">
            <td colspan="6">
              <form method="post" class="row g-2">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="account_id" value="<?= htmlspecialchars($aid) ?>">
                <div class="col-md-3">
                  <label class="form-label">الرصيد</label>
                  <input type="number" step="0.01" name="balance" class="form-control" value="<?= htmlspecialchars($row['balance']) ?>" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">العملة</label>
                  <input name="currency" class="form-control" value="<?= htmlspecialchars($row['currency']) ?>" required>
                </div>
                <div class="col-md-3 align-self-end">
                  <button class="btn btn-success">💾 حفظ</button>
                </div>
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
