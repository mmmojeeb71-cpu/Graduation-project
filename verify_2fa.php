<?php
session_start();
if (!isset($_SESSION['temp_user_id'])) { header("Location: login_view.php"); exit(); }
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>التحقق من الهوية - بوابة اليمن</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .verify-box { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; width: 350px; }
        input { width: 100%; padding: 15px; margin: 20px 0; border: 2px solid #ddd; border-radius: 8px; font-size: 24px; text-align: center; letter-spacing: 10px; }
        .btn-verify { width: 100%; padding: 15px; background: #1a73e8; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="verify-box">
        <h2>🛡️ تحقق إضافي</h2>
        <p>تم إرسال رمز التحقق إلى بريدك الإلكتروني (افتراضياً: استخدم 123456 للتجربة الآن).</p>
        <form action="verify_logic.php" method="POST">
            <input type="text" name="otp_code" maxlength="6" placeholder="000000" required>
            <button type="submit" class="btn-verify">تأكيد الدخول</button>
        </form>
    </div>
</body>
</html>