<?php
// header.php
require_once 'auth.php';
$csrf = ensure_csrf();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>لوحة التحكم - Yemen Gate</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body {
  background: linear-gradient(135deg, #f0f4f8, #e2e8f0);
  font-family: 'Tajawal', sans-serif;
}
.sidebar {
  min-height:100vh;
  background: linear-gradient(180deg, #1f2937, #111827);
  color:#fff;
  box-shadow: 4px 0 15px rgba(0,0,0,0.3);
}
.sidebar h5 {
  font-weight:700;
  color:#06b6d4;
  text-align:center;
}
.sidebar a {
  color:#fff;
  text-decoration:none;
  display:flex;
  align-items:center;
  gap:.5rem;
  padding:.75rem 1rem;
  border-radius:8px;
  transition: all .3s ease;
}
.sidebar a.active, .sidebar a:hover {
  background: linear-gradient(90deg, #06b6d4, #4f46e5);
  color:#fff;
  transform: translateX(5px);
}
.sidebar a i {
  width:20px;
}
.content {
  padding:2rem;
  animation: fadeIn .8s ease-in-out;
}
.table td, .table th {
  vertical-align: middle;
}
@keyframes fadeIn {
  from {opacity:0; transform: translateY(20px);}
  to {opacity:1; transform: translateY(0);}
}
.btn-danger {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  border:none;
}
.btn-danger:hover {
  background: linear-gradient(135deg, #b91c1c, #7f1d1d);
}
</style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <nav class="col-12 col-md-3 col-lg-2 sidebar">
      <div class="p-3">
        <h5 class="mb-4"><i class="fa-solid fa-gem"></i> لوحة Yemen Gate</h5>
        <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF'])==='dashboard.php'?'active':'' ?>"><i class="fa-solid fa-house"></i> الصفحة الرئيسية</a>
        <a href="users.php" class="<?= basename($_SERVER['PHP_SELF'])==='users.php'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستخدمون</a>
        <a href="accounts.php" class="<?= basename($_SERVER['PHP_SELF'])==='accounts.php'?'active':'' ?>"><i class="fa-solid fa-wallet"></i> الحسابات</a>
        <a href="cards.php" class="<?= basename($_SERVER['PHP_SELF'])==='cards.php'?'active':'' ?>"><i class="fa-solid fa-credit-card"></i> البطاقات</a>
        <a href="merchants.php" class="<?= basename($_SERVER['PHP_SELF'])==='merchants.php'?'active':'' ?>"><i class="fa-solid fa-store"></i> التجار</a>
        <a href="payments.php" class="<?= basename($_SERVER['PHP_SELF'])==='payments.php'?'active':'' ?>"><i class="fa-solid fa-money-bill-wave"></i> المدفوعات</a>
        <a href="audit_logs.php" class="<?= basename($_SERVER['PHP_SELF'])==='audit_logs.php'?'active':'' ?>"><i class="fa-solid fa-shield-halved"></i> سجل التدقيق</a>
        <a href="create_admin_form.php" class="<?= basename($_SERVER['PHP_SELF'])==='create_admin_form.php'?'active':'' ?>"><i class="fa-solid fa-user-shield"></i> إنشاء أدمن جديد</a>
        <a href="admins.php" class="<?= basename($_SERVER['PHP_SELF'])==='admins.php'?'active':'' ?>"><i class="fa-solid fa-users-gear"></i> إدارة الأدمن</a>
        <a href="delete_users.php" class="<?= basename($_SERVER['PHP_SELF'])==='delete_users.php'?'active':'' ?>"><i class="fa-solid fa-user-slash"></i> حذف المستخدمين المتسلسل</a>
        <hr>
        <form method="post" action="logout.php">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          <button class="btn btn-sm btn-danger w-100"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</button>
        </form>
      </div>
    </nav>
    <main class="col-12 col-md-9 col-lg-10 content">
