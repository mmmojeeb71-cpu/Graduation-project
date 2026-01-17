<?php
require_once 'db.php';
require_once 'auth.php';
require_admin();
check_csrf();

$csrf = ensure_csrf();

$filter_user = trim($_GET['user_id'] ?? ''); // UUID
$filter_action = trim($_GET['action_type'] ?? '');

$sql = "SELECT log_id, user_id, action_type, action_details, ip_address, created_at
        FROM audit_logs";
$where = [];
$params = [];

if ($filter_user !== '') {
    $where[] = "user_id = :uid";
    $params[':uid'] = uuid_to_bin($filter_user);
}
if ($filter_action !== '') {
    $where[] = "action_type = :act";
    $params[':act'] = $filter_action;
}
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY created_at DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

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
.btn-primary {
  background: linear-gradient(135deg, #4f46e5, #06b6d4);
  border: none;
}
.btn-primary:hover {
  background: linear-gradient(135deg, #4338ca, #0e7490);
}
.badge {
  font-size: 0.85rem;
}
</style>

<div class="card mb-3">
  <div class="card-body">
    <h5 class="mb-3">🔍 فلترة سجل التدقيق</h5>
    <form method="get" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">معرف المستخدم (UUID)</label>
        <input name="user_id" class="form-control" value="<?= htmlspecialchars($filter_user) ?>" placeholder="UUID">
      </div>
      <div class="col-md-4">
        <label class="form-label">نوع العملية</label>
        <input name="action_type" class="form-control" value="<?= htmlspecialchars($filter_action) ?>" placeholder="LOGIN, UPDATE_ACCOUNT, ISSUE_CARD ...">
      </div>
      <div class="col-md-4 align-self-end">
        <button class="btn btn-primary">تطبيق الفلترة</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="mb-3">📋 سجل التدقيق الأمني</h5>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>المعرف</th>
            <th>المستخدم</th>
            <th>العملية</th>
            <th>تفاصيل</th>
            <th>IP</th>
            <th>التاريخ</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($stmt as $row):
          $lid = $row['log_id'] ? bin_to_uuid($row['log_id']) : '';
          $uid = $row['user_id'] ? bin_to_uuid($row['user_id']) : '';
          $statusBadge = match($row['action_type']) {
            'LOGIN' => 'bg-success',
            'LOGOUT' => 'bg-secondary',
            'UPDATE_ACCOUNT' => 'bg-info',
            'ISSUE_CARD' => 'bg-primary',
            default => 'bg-dark'
          };
        ?>
          <tr>
            <td><small><?= htmlspecialchars($lid) ?></small></td>
            <td><small><?= htmlspecialchars($uid) ?></small></td>
            <td><span class="badge <?= $statusBadge ?>"><?= htmlspecialchars($row['action_type']) ?></span></td>
            <td><?= htmlspecialchars($row['action_details']) ?></td>
            <td><?= htmlspecialchars($row['ip_address']) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
