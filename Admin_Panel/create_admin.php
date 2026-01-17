<?php
// create_admin.php
require_once 'db.php';

// إعداد بيانات الأدمن
$uuid = gen_uuid();
$email = "admin@example.com";      // غيّر البريد لما تريده
$full_name = "Administrator";      // اسم الأدمن
$password = "StrongPassword123";   // غيّر كلمة المرور لقوية

// تشفير كلمة المرور
$hash = password_hash($password, PASSWORD_DEFAULT);

// ملاحظة مهمة: bindParam يحتاج متغيرات بالمرجعية
$user_id_bin = uuid_to_bin($uuid);

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (user_id, full_name, email, password_hash, status)
        VALUES (:id, :name, :email, :hash, 'active')
    ");

    // استخدم bindValue أو مرّر متغيرات إلى bindParam
    $stmt->bindParam(':id', $user_id_bin, PDO::PARAM_LOB);
    $stmt->bindParam(':name', $full_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':hash', $hash);

    $stmt->execute();

    echo "✅ تم إنشاء حساب الأدمن بنجاح. يمكنك تسجيل الدخول باستخدام البريد: " . htmlspecialchars($email);
} catch (PDOException $e) {
    // عرض الخطأ بشكل آمن
    echo "❌ فشل إنشاء الأدمن: " . htmlspecialchars($e->getMessage());
}

require_once 'db.php';
require_once 'auth.php';
require_admin();   // تأكد أن المستخدم الحالي أدمن
check_csrf();

$csrf = ensure_csrf();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';

    if ($full_name && $email && $password) {
        $uuid = gen_uuid();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $user_id_bin = uuid_to_bin($uuid);

        $stmt = $pdo->prepare("INSERT INTO users (user_id, full_name, email, password_hash, status) 
                               VALUES (:id, :name, :email, :hash, 'active')");
        $stmt->bindParam(':id', $user_id_bin, PDO::PARAM_LOB);
        $stmt->bindParam(':name', $full_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':hash', $hash);
        $stmt->execute();

        $notice = "✅ تم إنشاء حساب الأدمن بنجاح.";
    } else {
        $notice = "❌ يرجى إدخال جميع البيانات.";
    }
}

include 'header.php';
?>

<div class="card">
  <div class="card-body">
    <h5 class="mb-3">إنشاء حساب أدمن جديد</h5>
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
        <button class="btn btn-primary">إنشاء الأدمن</button>
      </div>
    </form>
  </div>
</div>

<?php include 'footer.php'; ?>

// يُفضّل حذف هذا الملف بعد التنفيذ لأسباب أمنية
