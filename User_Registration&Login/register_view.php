<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فتح حساب جديد - بوابة اليمن</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #1a73e8; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background-color: #1a73e8; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { background-color: #1557b0; }
        .login-link { text-align: center; margin-top: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>بوابة اليمن الإلكترونية</h2>
        <p style="text-align: center; color: #666;">أنشئ حسابك البنكي في لحظات</p>
        
        <form action="register_logic.php" method="POST">
            <input type="text" name="full_name" placeholder="الاسم الكامل" required>
            <input type="email" name="email" placeholder="البريد الإلكتروني" required>
            <input type="password" name="password" placeholder="كلمة المرور" required>
            <button type="submit">فتح الحساب</button>
        </form>

        <div class="login-link">
            لديك حساب بالفعل؟ <a href="login_view.php">سجل دخولك هنا</a>
        </div>
    </div>
</body>
</html>