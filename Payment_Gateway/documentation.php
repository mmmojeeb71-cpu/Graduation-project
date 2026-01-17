<?php
session_start();
// التأكد من تسجيل الدخول كتاجر للوصول للدليل الكامل
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Dashboard/login_view.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>دليل المطورين | Yemen Gate API</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f172a; --sidebar: #1e293b; --accent: #38bdf8;
            --text: #f1f5f9; --code-bg: #011627;
        }
        body {
            margin: 0; font-family: 'Tajawal', sans-serif; background: var(--bg); color: var(--text);
            display: flex; min-height: 100vh;
        }
        /* Sidebar */
        .sidebar {
            width: 280px; background: var(--sidebar); border-left: 1px solid rgba(255,255,255,0.1);
            padding: 30px 20px; position: fixed; height: 100vh;
        }
        .sidebar h2 { color: var(--accent); font-size: 1.2rem; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .nav-link { 
            display: block; color: #94a3b8; text-decoration: none; padding: 12px; 
            border-radius: 8px; margin-bottom: 5px; font-size: 0.9rem;
        }
        .nav-link:hover, .nav-link.active { background: rgba(56, 189, 248, 0.1); color: var(--accent); }

        /* Main Content */
        .content { margin-right: 280px; padding: 50px; width: 100%; max-width: 900px; }
        
        .endpoint { 
            background: #10b981; color: #fff; padding: 4px 10px; border-radius: 6px; 
            font-family: 'Fira Code', monospace; font-size: 0.8rem; margin-left: 10px;
        }
        
        pre { 
            background: var(--code-bg); padding: 20px; border-radius: 12px; 
            direction: ltr; overflow-x: auto; border: 1px solid rgba(255,255,255,0.05);
            font-family: 'Fira Code', monospace; line-height: 1.6; color: #addb67;
        }
        
        .method { color: #c792ea; } /* POST */
        .string { color: #ecc48d; }
        .key { color: #7fdbca; }

        .info-box { 
            background: rgba(56, 189, 248, 0.1); border-right: 4px solid var(--accent); 
            padding: 20px; border-radius: 8px; margin: 25px 0;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h2>YEMEN GATE API</h2>
        <nav>
            <a href="#intro" class="nav-link active">مقدمة</a>
            <a href="#auth" class="nav-link">المصادقة (Authentication)</a>
            <a href="#create-payment" class="nav-link">إنشاء عملية دفع</a>
            <a href="#callback" class="nav-link">استقبال الرد (Webhook)</a>
            <a href="../User_Registration&Login/dashboard.php" class="nav-link" style="margin-top:50px; color: #ef4444;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> العودة للوحة التحكم
            </a>
        </nav>
    </aside>

    <main class="content">
        <section id="intro">
            <h1>دليل الربط البرمجي (API Documentation)</h1>
            <p>مرحباً بك في بوابة اليمن جت. يتيح لك نظامنا ربط متجرك الإلكتروني واستقبال المدفوعات من عملائك في اليمن والسعودية والعالم بكل سهولة عبر طلبات HTTP بسيطة.</p>
            
            <div class="info-box">
                <strong>Base URL:</strong><br>
                <code>https://api.yemengate.com/v1/</code>
            </div>
        </section>

        <hr style="opacity: 0.1; margin: 50px 0;">

        <section id="auth">
            <h2>1. المصادقة (Authentication)</h2>
            <p>يجب إرسال مفتاح الـ API الخاص بك في كل طلب عبر الـ Header:</p>
            <pre>
Authorization: Bearer YOUR_SECRET_KEY
Content-Type: application/json</pre>
        </section>

        <section id="create-payment" style="margin-top: 60px;">
            <h2>2. إنشاء عملية دفع <span class="endpoint">POST /payments/create</span></h2>
            <p>لتحويل العميل إلى صفحة الدفع، قم بإرسال البيانات التالية:</p>
            
            <pre>
{
    <span class="key">"amount"</span>: <span class="string">150.00</span>,
    <span class="key">"currency"</span>: <span class="string">"USD"</span>,
    <span class="key">"order_id"</span>: <span class="string">"INV-9928"</span>,
    <span class="key">"customer_name"</span>: <span class="string">"Ahmed Ali"</span>,
    <span class="key">"success_url"</span>: <span class="string">"https://yourstore.com/success"</span>,
    <span class="key">"cancel_url"</span>: <span class="string">"https://yourstore.com/cancel"</span>
}</pre>

            <h3>الرد المتوقع (Response):</h3>
            <pre>
{
    <span class="key">"status"</span>: <span class="string">"success"</span>,
    <span class="key">"payment_url"</span>: <span class="string">"https://checkout.yemengate.com/pay/xxxx-xxxx"</span>,
    <span class="key">"transaction_id"</span>: <span class="string">"YG_882711"</span>
}</pre>
        </section>

        <section id="callback" style="margin-top: 60px;">
            <h2>3. استقبال الإشعارات (Webhooks)</h2>
            <p>بعد إتمام العميل للدفع، سيقوم نظامنا بإرسال طلب POST إلى الـ Callback URL المسجل في حسابك لتأكيد العملية برمجياً.</p>
            <div class="info-box" style="border-color: #10b981;">
                تأكد من التحقق من توقيع الطلب (Signature) لضمان أمان البيانات.
            </div>
        </section>
        
        <footer style="margin-top: 100px; opacity: 0.5; font-size: 0.8rem; text-align: center;">
            &copy; 2026 Yemen Gate Global Banking - Developer Relations
        </footer>
    </main>

    <script>
        // تغيير الرابط النشط عند التمرير
        const sections = document.querySelectorAll("section");
        const navLinks = document.querySelectorAll(".nav-link");

        window.onscroll = () => {
            let current = "";
            sections.forEach((section) => {
                const sectionTop = section.offsetTop;
                if (pageYOffset >= sectionTop - 100) {
                    current = section.getAttribute("id");
                }
            });

            navLinks.forEach((link) => {
                link.classList.remove("active");
                if (link.getAttribute("href").includes(current)) {
                    link.classList.add("active");
                }
            });
        };
    </script>
</body>
</html>