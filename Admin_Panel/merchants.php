<?php
require_once 'db.php';
require_once 'auth.php';
require_admin();
check_csrf();

$csrf = ensure_csrf();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $user_id = $_POST['user_id'] ?? '';
        $business_name = trim($_POST['business_name'] ?? '');
        $webhook_url = trim($_POST['webhook_url'] ?? '');
        if ($user_id && $business_name) {
            $merchant_id = gen_uuid();
            $api_key_id = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("INSERT INTO merchants 
                (merchant_id, user_id, business_name, api_key_id, webhook_url) 
                VALUES (:mid, :uid, :name, :api, :wh)");
            $stmt->bindParam(':mid', uuid_to_bin($merchant_id), PDO::PARAM_LOB);
            $stmt->bindParam(':uid', uuid_to_bin($user_id), PDO::PARAM_LOB);
            $stmt->bindParam(':name', $business_name);
            $stmt->bindParam(':api', $api_key_id);
            $stmt->bindParam(':wh', $webhook_url);
            $stmt->execute();
            $notice = '✅ تم إضافة التاجر بنجاح.';
        }
    } elseif ($action === 'update') {
        $merchant_id = $_POST['merchant_id'] ?? '';
        $business_name = trim($_POST['business_name'] ?? '');
        $webhook_url = trim($_POST['webhook_url'] ?? '');
        if ($merchant_id && $business_name) {
            $stmt = $pdo->prepare("UPDATE merchants 
                SET business_name = :name, webhook_url = :wh 
                WHERE merchant_id = :mid");
            $stmt->bindParam(':name', $business_name);
            $stmt->bindParam(':wh', $webhook_url);
            $stmt->bindParam(':mid', uuid_to_bin($merchant_id), PDO::PARAM_LOB);
            $stmt->execute();
            $notice = '✏️ تم تحديث بيانات التاجر.';
        }
    } elseif ($action === 'delete') {
        $merchant_id = $_POST['merchant_id'] ?? '';
        if ($merchant_id) {
            $stmt = $pdo->prepare("DELETE FROM merchants WHERE merchant_id = :mid");
            $stmt->bindParam(':mid', uuid_to_bin($merchant_id), PDO::PARAM_LOB);
            $stmt->execute();
            $notice = '🗑️ تم حذف التاجر.';
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
.btn-outline-danger {
  border-color: #dc2626;
  color: #dc2626;
}
.btn-outline-danger:hover {
  background: #dc2626;
  color: #fff;
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

<div class="card mb-3">
  <div class="card-body">
    <h5 class="mb-3">➕ إضافة تاجر جديد</h5>
    <form method="post" class="row g-3">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="create">
      <div class="col-md-4">
        <label class="form-label">معرف المستخدم (UUID)</label>
        <input name="user_id" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">اسم النشاط التجاري</label>
        <input name="business_name" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">رابط Webhook (اختياري)</label>
        <input name="webhook_url" class="form-control">
      </div>
      <div class="col-12">
        <button class="btn btn-primary">🚀 إضافة</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="mb-3">📋 قائمة التجار</h5>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>المعرف</th>
            <th>المستخدم</th>
            <th>النشاط</th>
            <th>API Key</th>
            <th>Webhook</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->query("SELECT merchant_id, user_id, business_name, api_key_id, webhook_url 
                             FROM merchants ORDER BY created_at DESC");
        foreach ($stmt as $row):
          $mid = bin_to_uuid($row['merchant_id']);
          $uid = bin_to_uuid($row['user_id']);
        ?>
          <tr>
            <td><small><?= htmlspecialchars($mid) ?></small></td>
            <td><small><?= htmlspecialchars($uid) ?></small></td>
            <td><?= htmlspecialchars($row['business_name']) ?></td>
            <td><small><?= htmlspecialchars($row['api_key_id']) ?></small></td>
            <td><?= htmlspecialchars($row['webhook_url']) ?></td>
            <td>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="merchant_id" value="<?= htmlspecialchars($mid) ?>">
                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('تأكيد الحذف؟')">🗑️ حذف</button>
              </form>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-<?= htmlspecialchars($mid) ?>">✏️ تعديل</button>
            </td>
          </tr>
          <tr class="collapse" id="edit-<?= htmlspecialchars($mid) ?>">
            <td colspan="6">
              <form method="post" class="row g-2">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="merchant_id" value="<?= htmlspecialchars($mid) ?>">
                <div class="col-md-4">
                  <input name="business_name" class="form-control" value="<?= htmlspecialchars($row['business_name']) ?>" required>
                </div>
                <div class="col-md-4">
                  <input name="webhook_url" class="form-control" value="<?= htmlspecialchars($row['webhook_url']) ?>">
                </div>
                <div class="col-md-4">
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
