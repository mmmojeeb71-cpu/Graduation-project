<?php
require_once 'db.php';
require_once 'auth.php';
check_csrf();

// ✅ تحقق إذا لا يوجد أي أدمن في قاعدة البيانات
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'");
if ($stmt->fetchColumn() == 0) {
    header("Location: create_admin_form.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        // نفترض أن المشرفين مخزّنون في جدول users مع status='active'
        $stmt = $pdo->prepare("SELECT user_id, email, password_hash, status FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_id'] = $user['user_id']; // BINARY(16) سيعود كـ string binary
            $_SESSION['admin_email'] = $user['email'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'بيانات الدخول غير صحيحة أو الحساب غير نشط.';
        }
    } else {
        $error = 'يرجى إدخال البريد الإلكتروني وكلمة المرور.';
    }
}
$csrf = ensure_csrf();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>تسجيل الدخول - لوحة Yemen Gate</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body {
  background: linear-gradient(135deg, #f0f4f8, #e2e8f0);
  font-family: 'Tajawal', sans-serif;
}
.card {
  border-radius: 12px;
  box-shadow: 0 6px 15px rgba(0,0,0,0.1);
  animation: fadeIn .8s ease-in-out;
}
.card-body h4 {
  font-weight: 700;
  color: #1f2937;
}
.btn-primary {
  background: linear-gradient(135deg, #4f46e5, #06b6d4);
  border: none;
}
.btn-primary:hover {
  background: linear-gradient(135deg, #4338ca, #0e7490);
}
.alert-danger {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  color: #fff;
  border: none;
}
@keyframes fadeIn {
  from {opacity:0; transform: translateY(20px);}
  to {opacity:1; transform: translateY(0);}
}
</style>
</head>
<body>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-6">
      <div class="card">
        <div class="card-body">
          <h4 class="mb-3"><i class="fa-solid fa-lock"></i> تسجيل الدخول</h4>
          <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <div class="mb-3">
              <label class="form-label">البريد الإلكتروني</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">كلمة المرور</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100"><i class="fa-solid fa-right-to-bracket"></i> دخول</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
