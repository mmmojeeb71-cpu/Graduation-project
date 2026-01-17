<?php
require_once 'db.php';
require_once 'auth.php';
require_admin();
check_csrf();

$csrf = ensure_csrf();
$notice = '';

$filter_status = $_GET['status'] ?? '';
$filter_currency = strtoupper(trim($_GET['currency'] ?? ''));

$sql = "SELECT p.payment_id, p.merchant_id, p.amount, p.currency, p.status, p.created_at, m.business_name
        FROM payments p
        LEFT JOIN merchants m ON m.merchant_id = p.merchant_id";
$where = [];
$params = [];

if ($filter_status !== '') {
    $where[] = "p.status = :status";
    $params[':status'] = $filter_status;
}
if ($filter_currency !== '') {
    $where[] = "p.currency = :currency";
    $params[':currency'] = $filter_currency;
}
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY p.created_at DESC";

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
</style>

<div class="card mb-3">
  <div class="card-body">
    <h5 class="mb-3">🔍 فلترة المدفوعات</h5>
    <form method="get" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select">
          <option value="">الكل</option>
          <option value="PENDING" <?= $filter_status==='PENDING'?'selected':'' ?>>⏳ قيد الانتظار</option>
          <option value="AUTHORIZED" <?= $filter_status==='AUTHORIZED'?'selected':'' ?>>✅ مصرح</option>
          <option value="CAPTURED" <?= $filter_status==='CAPTURED'?'selected':'' ?>>📌 محجوز</option>
          <option value="REFUNDED" <?= $filter_status==='REFUNDED'?'selected':'' ?>>💸 مسترد</option>
          <option value="FAILED" <?= $filter_status==='FAILED'?'selected':'' ?>>❌ فشل</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">العملة</label>
        <input name="currency" class="form-control" value="<?= htmlspecialchars($filter_currency) ?>" placeholder="USD">
      </div>
      <div class="col-md-4 align-self-end">
        <button class="btn btn-primary">تطبيق الفلترة</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="mb-3">📋 سجل المدفوعات</h5>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>المعرف</th>
            <th>التاجر</th>
            <th>المبلغ</th>
            <th>العملة</th>
            <th>الحالة</th>
            <th>التاريخ</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($stmt as $row):
          $pid = $row['payment_id'] ? bin_to_uuid($row['payment_id']) : '';
          $mid = $row['merchant_id'] ? bin_to_uuid($row['merchant_id']) : '';
        ?>
          <tr>
            <td><small><?= htmlspecialchars($pid) ?></small></td>
            <td><?= htmlspecialchars($row['business_name'] ?: $mid) ?></td>
            <td><?= htmlspecialchars(number_format((float)$row['amount'], 2)) ?></td>
            <td><?= htmlspecialchars($row['currency']) ?></td>
            <td>
              <?php
              $status = $row['status'];
              $badgeClass = match($status) {
                'PENDING' => 'bg-secondary',
                'AUTHORIZED' => 'bg-info',
                'CAPTURED' => 'bg-primary',
                'REFUNDED' => 'bg-success',
                'FAILED' => 'bg-danger',
                default => 'bg-dark'
              };
              ?>
              <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
            </td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
