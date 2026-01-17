<?php
session_start();
require_once '../Shared/config.php';
require_once '../Shared/auth_functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];

    // البحث عن المستخدم بالبريد
    $stmt = $pdo->prepare("SELECT HEX(user_id) as user_id, full_name, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // إذا كانت البيانات صحيحة، نخزن بياناته في الجلسة
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];

        // توجيهه إلى لوحة التحكم (سننشئها في الخطوة القادمة)
        header("Location: dashboard.php");
        exit();
    } else {
        echo "❌ البريد الإلكتروني أو كلمة المرور غير صحيحة.";
    }
}