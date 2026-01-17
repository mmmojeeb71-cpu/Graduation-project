<?php
require_once 'db.php';
require_once 'auth.php';
require_admin();

$counts = [];
$counts['المستخدمون 👤'] = $pdo->query("SELECT COUNT(*) AS c FROM users")->fetch()['c'];
$counts['الحسابات 💳'] = $pdo->query("SELECT COUNT(*) AS c FROM accounts")->fetch()['c'];
$counts['البطاقات 🪪'] = $pdo->query("SELECT COUNT(*) AS c FROM issued_cards")->fetch()['c'];
$counts['التجار 🏪'] = $pdo->query("SELECT COUNT(*) AS c FROM merchants")->fetch()['c'];
$counts['المدفوعات 💰'] = $pdo->query("SELECT COUNT(*) AS c FROM payments")->fetch()['c'];
$counts['سجل التدقيق 📜'] = $pdo->query("SELECT COUNT(*) AS c FROM audit_logs")->fetch()['c'];

include 'header.php';
?>

<style>
.dashboard-card {
  background: linear-gradient(135deg, #4f46e5, #06b6d4);
  color: #fff;
  border-radius: 12px;
  box-shadow: 0 6px 15px rgba(0,0,0,0.15);
  transition: transform .3s ease, box-shadow .3s ease;
}
.dashboard-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 25px rgba(0,0,0,0.25);
}
.dashboard-card h6 {
  font-weight: 600;
  opacity: .9;
}
.dashboard-card h3 {
  font-size: 2rem;
  margin-top: .5rem;
}
.audit-table th {
  background: #1f2937;
  color: #fff;
}
.audit-table tr:hover {
  background: #f1f5f9;
}
</style>

<div class="row g-3">
  <?php foreach ($counts as $label => $c): ?>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card text-center dashboard-card">
        <div class="card-body">
          <h6><?= htmlspecialchars($label) ?></h6>
          <h3><?= (int)$c ?></h3>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="mt-4 card">
  <div class="card-body">
    <h5 class="mb-3">✨ آخر 10 عمليات تدقيق</h5>
    <div class="table-responsive">
      <table class="table table-striped audit-table">
        <thead>
          <tr>
            <th>المستخدم</th>
            <th>العملية</th>
            <th>تفاصيل</th>
            <th>IP</th>
            <th>التاريخ</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->query("SELECT a.user_id, a.action_type, a.action_details, a.ip_address, a.created_at 
                             FROM audit_logs a ORDER BY a.created_at DESC LIMIT 10");
        foreach ($stmt as $row):
        ?>
          <tr>
            <td><?= htmlspecialchars(bin_to_uuid($row['user_id'])) ?></td>
            <td><?= htmlspecialchars($row['action_type']) ?></td>
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
