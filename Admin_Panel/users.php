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
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($full_name && $email && $password) {
            // تحقق إذا البريد موجود مسبقًا
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $check->bindParam(':email', $email);
            $check->execute();
            if ($check->fetchColumn() > 0) {
                $notice = '⚠️ البريد الإلكتروني موجود بالفعل.';
            } else {
                $uuid = gen_uuid();
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $uid_bin = uuid_to_bin($uuid);
                $stmt = $pdo->prepare("INSERT INTO users (user_id, full_name, email, password_hash, status) 
                                       VALUES (:id, :name, :email, :hash, 'active')");
                $stmt->bindParam(':id', $uid_bin, PDO::PARAM_LOB);
                $stmt->bindParam(':name', $full_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':hash', $hash);
                $stmt->execute();
                $notice = '✅ تم إنشاء المستخدم بنجاح.';
            }
        }
    } elseif ($action === 'update') {
        $user_id = $_POST['user_id'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        if ($user_id && $full_name) {
            $uid_bin = uuid_to_bin($user_id);
            $stmt = $pdo->prepare("UPDATE users SET full_name=:name, status=:status WHERE user_id=:id");
            $stmt->bindParam(':name', $full_name);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $uid_bin, PDO::PARAM_LOB);
            $stmt->execute();
            $notice = '✅ تم تحديث المستخدم.';
        }
    } elseif ($action === 'delete') {
        $user_id = $_POST['user_id'] ?? '';
        if ($user_id) {
            $uid_bin = uuid_to_bin($user_id);
            try {
                // احذف البطاقات المرتبطة
                $stmt = $pdo->prepare("DELETE FROM issued_cards WHERE user_id=:id");
                $stmt->bindParam(':id', $uid_bin, PDO::PARAM_LOB);
                $stmt->execute();

                // احذف الحسابات المرتبطة
                $stmt = $pdo->prepare("DELETE FROM accounts WHERE user_id=:id");
                $stmt->bindParam(':id', $uid_bin, PDO::PARAM_LOB);
                $stmt->execute();

                // أخيرًا احذف المستخدم
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id=:id");
                $stmt->bindParam(':id', $uid_bin, PDO::PARAM_LOB);
                $stmt->execute();

                $notice = '✅ تم حذف المستخدم وكل بياناته المرتبطة.';
            } catch (PDOException $e) {
                $notice = '❌ خطأ أثناء الحذف: ' . htmlspecialchars($e->getMessage());
            }
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
.btn-primary {
  background: linear-gradient(135deg, #4f46e5, #06b6d4);
  border: none;
}
.btn-primary:hover {
  background: linear-gradient(135deg, #4338ca, #0e7490);
}
.table-striped thead {
  background: #1f2937;
  color: #fff;
}
.table-striped tbody tr:hover {
  background: #f1f5f9;
}
.alert-info {
  background: linear-gradient(135deg, #06b6d4, #4f46e5);
  color: #fff;
  border: none;
}
</style>

<div class="card mb-3">
  <div class="card-body">
    <h5 class="mb-3">➕ إضافة مستخدم</h5>
    <form method="post" class="row g-3">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="create">
      <div class="col-md-4">
        <label class="form-label">الاسم الكامل</label>
        <input name="full_name" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">البريد الإلكتروني</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">كلمة المرور</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="col-12">
        <button class="btn btn-primary">إضافة</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="mb-3">👥 قائمة المستخدمين</h5>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead><tr><th>المعرف</th><th>الاسم</th><th>البريد</th><th>الحالة</th><th>أنشئ في</th><th>إجراءات</th></tr></thead>
        <tbody>
        <?php
        $stmt = $pdo->query("SELECT user_id, full_name, email, status, created_at FROM users ORDER BY created_at DESC");
        foreach ($stmt as $row):
          $uid = bin_to_uuid($row['user_id']);
        ?>
          <tr>
            <td><small><?= htmlspecialchars($uid) ?></small></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
            <td>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($uid) ?>">
                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('تأكيد الحذف؟')">🗑️ حذف</button>
              </form>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-<?= htmlspecialchars($uid) ?>">✏️ تعديل</button>
            </td>
          </tr>
          <tr class="collapse" id="edit-<?= htmlspecialchars($uid) ?>">
            <td colspan="6">
              <form method="post" class="row g-2">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($uid) ?>">
                <div class="col-md-4">
                  <input name="full_name" class="form-control" value="<?= htmlspecialchars($row['full_name']) ?>" required>
                </div>
                <div class="col-md-3">
                  <select name="status" class="form-select">
                    <option value="active" <?= $row['status']==='active'?'selected':'' ?>>نشط</option>
                    <option value="disabled" <?= $row['status']==='disabled'?'selected':'' ?>>معطل</option>
                  </select>
                </div>
                <div class="col-md-3">
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
