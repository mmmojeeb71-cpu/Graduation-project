<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول - بوابة اليمن</title>
    <style>
        body { font-family: Arial; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 350px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .register-btn { display: block; margin-top: 15px; padding: 10px; background: #6c757d; color: white; text-align: center; border-radius: 5px; text-decoration: none; font-size: 14px; }
        .register-btn:hover { background: #5a6268; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="text-align: center;">تسجيل الدخول</h2>
        <form action="login_logic.php" method="POST">
            <input type="email" name="email" placeholder="البريد الإلكتروني" required>
            <input type="password" name="password" placeholder="كلمة المرور" required>
            <button type="submit">دخول</button>
        </form>
        <a href="http://localhost/Yemen_Gate/User_Registration&Login/register_view.php" class="register-btn">ليس لديك حساب؟ انتقل إلى صفحة التسجيل</a>
    </div>
</body>
</html>
