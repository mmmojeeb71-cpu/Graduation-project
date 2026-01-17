<?php
// index.php داخل مجلد Admin_Panel

require_once 'db.php';
session_start();

// ✅ تحقق إذا لا يوجد أي أدمن في قاعدة البيانات
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'");
$countAdmins = $stmt->fetchColumn();

if ($countAdmins == 0) {
    // إذا لا يوجد أي أدمن → حوّل مباشرة إلى صفحة إنشاء أدمن
    header("Location: create_admin_form.php");
    exit;
}

// إذا كان المستخدم مسجل دخول بالفعل، حوّله للوحة التحكم
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// إذا لم يكن مسجل دخول، حوّله لصفحة تسجيل الدخول
header("Location: login.php");
exit;
