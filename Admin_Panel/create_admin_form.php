<?php
require_once 'db.php';
require_once 'auth.php';
require_admin();
check_csrf();

$csrf = ensure_csrf();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';

    if ($full_name && $email && $password) {
        // تحقق إذا البريد موجود مسبقًا
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $check->bindParam(':email', $email);
        $check->execute();
        $exists = $check->fetchColumn();

        if ($exists > 0) {
            $notice = "⚠️ البريد الإلكتروني موجود بالفعل، يرجى استخدام بريد آخر.";
        } else {
            $uuid = gen_uuid();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $user_id_bin = uuid_to_bin($uuid);

            try {
                // إضافة العمود role بالقيمة admin
                $stmt = $pdo->prepare("INSERT INTO users (user_id, full_name, email, password_hash, status, role)
                                       VALUES (:id, :name, :email, :hash, 'active', 'admin')");
                $stmt->bindParam(':id', $user_id_bin, PDO::PARAM_LOB);
                $stmt->bindParam(':name', $full_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':hash', $hash);
                $stmt->execute();

                $notice = "✅ تم إنشاء حساب الأدمن بنجاح.";
            } catch (PDOException $e) {
                $notice = "❌ حدث خطأ أثناء إنشاء الحساب.";
            }
        }
    } else {
        $notice = "❌ يرجى إدخال جميع البيانات.";
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
.btn-primary {
  background: linear-gradient(135deg, #4f46e5, #06b6d4);
  border: none;
}
.btn-primary:hover {
  background: linear-gradient(135deg, #4338ca, #0e7490);
}
.alert-info {
  background: linear-gradient(135deg, #06b6d4, #4f46e5);
  color: #fff;
  border: none;
}
</style>

<div class="card">
  <div class="card-body">
    <h5 class="mb-3">👑 إنشاء حساب أدمن جديد</h5>
    <?php if ($notice): ?>
      <div class="alert alert-info"><?= htmlspecialchars($notice) ?></div>
    <?php endif; ?>
    <form method="post" class="row g-3">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <div class="col-md-4">
        <label class="form-label">الاسم الكامل</label>
        <input type="text" name="full_name" class="form-control" required>
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
        <button class="btn btn-primary">🚀 إنشاء الأدمن</button>
      </div>
    </form>
  </div>
</div>

<?php include 'footer.php'; ?>
