<?php
require_once 'db.php';
require_once 'auth.php';
require_admin();
check_csrf();

$csrf = ensure_csrf();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $user_id = $_POST['user_id'] ?? '';
        if ($user_id) {
            // حذف الأدمن فقط
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :uid AND role = 'admin'");
            $uid_bin = uuid_to_bin($user_id);
            $stmt->bindParam(':uid', $uid_bin, PDO::PARAM_LOB);
            $stmt->execute();
            $notice = "✅ تم حذف الأدمن.";
        }
    }
}

// جلب فقط حسابات الأدمن
$stmt = $pdo->query("SELECT user_id, full_name, email FROM users WHERE role = 'admin' ORDER BY created_at DESC");

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
.btn-outline-danger {
  border-color: #dc2626;
  color: #dc2626;
}
.btn-outline-danger:hover {
  background: #dc2626;
  color: #fff;
}
.alert-info {
  background: linear-gradient(135deg, #06b6d4, #4f46e5);
  color: #fff;
  border: none;
}
</style>

<div class="card">
  <div class="card-body">
    <h5 class="mb-3">👑 إدارة حسابات الأدمن</h5>
    <?php if ($notice): ?>
      <div class="alert alert-info"><?= htmlspecialchars($notice) ?></div>
    <?php endif; ?>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>المعرف</th>
            <th>الاسم</th>
            <th>البريد الإلكتروني</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($stmt as $row):
          $uid = $row['user_id'] ? bin_to_uuid($row['user_id']) : '';
        ?>
          <tr>
            <td><small><?= htmlspecialchars($uid) ?></small></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($uid) ?>">
                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('تأكيد الحذف؟')">🗑️ حذف</button>
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
