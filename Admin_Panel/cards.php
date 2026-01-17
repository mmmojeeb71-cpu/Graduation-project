<?php
require_once 'db.php';
require_once 'auth.php';
require_admin();
check_csrf();

$csrf = ensure_csrf();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'issue') {
        $user_id = $_POST['user_id'] ?? '';
        $account_id = $_POST['account_id'] ?? '';
        $masked_pan = $_POST['masked_pan'] ?? '';
        $expiry_month = (int)($_POST['expiry_month'] ?? 0);
        $expiry_year = (int)($_POST['expiry_year'] ?? 0);
        if ($user_id && $account_id && $masked_pan && $expiry_month && $expiry_year) {
            $card_id = gen_uuid();
            $card_token = bin2hex(random_bytes(32)); // رمز آمن
            $stmt = $pdo->prepare("INSERT INTO issued_cards 
                (card_id, user_id, account_id, card_token, masked_pan, expiry_month, expiry_year, is_active) 
                VALUES (:cid, :uid, :aid, :token, :pan, :mm, :yy, 1)");
            $stmt->bindParam(':cid', uuid_to_bin($card_id), PDO::PARAM_LOB);
            $stmt->bindParam(':uid', uuid_to_bin($user_id), PDO::PARAM_LOB);
            $stmt->bindParam(':aid', uuid_to_bin($account_id), PDO::PARAM_LOB);
            $stmt->bindParam(':token', $card_token);
            $stmt->bindParam(':pan', $masked_pan);
            $stmt->bindParam(':mm', $expiry_month);
            $stmt->bindParam(':yy', $expiry_year);
            $stmt->execute();
            $notice = '✅ تم إصدار البطاقة بنجاح.';
        }
    } elseif ($action === 'toggle') {
        $card_id = $_POST['card_id'] ?? '';
        $is_active = (int)($_POST['is_active'] ?? 1);
        if ($card_id) {
            $stmt = $pdo->prepare("UPDATE issued_cards SET is_active=:active WHERE card_id=:id");
            $stmt->bindParam(':active', $is_active);
            $stmt->bindParam(':id', uuid_to_bin($card_id), PDO::PARAM_LOB);
            $stmt->execute();
            $notice = '🔄 تم تحديث حالة البطاقة.';
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
.btn-primary {
  background: linear-gradient(135deg, #4f46e5, #06b6d4);
  border: none;
}
.btn-primary:hover {
  background: linear-gradient(135deg, #4338ca, #0e7490);
}
.btn-outline-warning {
  border-color: #f59e0b;
  color: #f59e0b;
}
.btn-outline-warning:hover {
  background: #f59e0b;
  color: #fff;
}
.alert-info {
  background: linear-gradient(135deg, #06b6d4, #4f46e5);
  color: #fff;
  border: none;
}
</style>

<div class="card mb-3">
  <div class="card-body">
    <h5 class="mb-3">💳 إصدار بطاقة جديدة</h5>
    <form method="post" class="row g-3">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="issue">
      <div class="col-md-3">
        <label class="form-label">معرف المستخدم (UUID)</label>
        <input name="user_id" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">معرف الحساب (UUID)</label>
        <input name="account_id" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">PAN المخفي</label>
        <input name="masked_pan" class="form-control" placeholder="**** **** **** 1234" required>
      </div>
      <div class="col-md-1">
        <label class="form-label">شهر الانتهاء</label>
        <input type="number" name="expiry_month" class="form-control" min="1" max="12" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">سنة الانتهاء</label>
        <input type="number" name="expiry_year" class="form-control" min="2025" required>
      </div>
      <div class="col-12">
        <button class="btn btn-primary">🚀 إصدار</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="mb-3">📋 البطاقات المصدرة</h5>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>المعرف</th>
            <th>المستخدم</th>
            <th>الحساب</th>
            <th>PAN</th>
            <th>الانتهاء</th>
            <th>نشطة</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->query("SELECT card_id, user_id, account_id, masked_pan, expiry_month, expiry_year, is_active 
                             FROM issued_cards ORDER BY created_at DESC");
        foreach ($stmt as $row):
          $cid = bin_to_uuid($row['card_id']);
          $uid = bin_to_uuid($row['user_id']);
          $aid = bin_to_uuid($row['account_id']);
        ?>
          <tr>
            <td><small><?= htmlspecialchars($cid) ?></small></td>
            <td><small><?= htmlspecialchars($uid) ?></small></td>
            <td><small><?= htmlspecialchars($aid) ?></small></td>
            <td><?= htmlspecialchars($row['masked_pan']) ?></td>
            <td><?= htmlspecialchars(sprintf('%02d/%d', $row['expiry_month'], $row['expiry_year'])) ?></td>
            <td><?= $row['is_active'] ? '✅ نعم' : '❌ لا' ?></td>
            <td>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="card_id" value="<?= htmlspecialchars($cid) ?>">
                <input type="hidden" name="is_active" value="<?= $row['is_active'] ? 0 : 1 ?>">
                <button class="btn btn-sm btn-outline-warning">
                  <?= $row['is_active'] ? '🔒 تعطيل' : '🔓 تفعيل' ?>
                </button>
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
