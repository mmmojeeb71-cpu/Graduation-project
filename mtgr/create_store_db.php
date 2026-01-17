<?php
// إعدادات السيرفر المحلي
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // 1. الاتصال بالسيرفر
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. إنشاء قاعدة بيانات المتجر (mtgr_db)
    $pdo->exec("CREATE DATABASE IF NOT EXISTS mtgr_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE mtgr_db");

    // 3. إنشاء جدول لتخزين الطلبات التي تأتي من "اليمن جيت"
    // هذا الجدول سيسجل نجاح الدفع بعد استقبال إشارة الـ Webhook
    $createTable = "CREATE TABLE IF NOT EXISTS store_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_id VARCHAR(100) NOT NULL, -- رقم العملية من بوابتك
        product_name VARCHAR(255),
        amount DECIMAL(18, 2),
        currency VARCHAR(10),
        status VARCHAR(20) DEFAULT 'pending', -- pending, completed
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($createTable);

    echo "<div style='direction:rtl; text-align:center; font-family:Tahoma; padding:20px; background:#dcfce7; color:#166534; border-radius:10px;'>
            ✅ تم إنشاء قاعدة بيانات المتجر (mtgr_db) وجدول الطلبات بنجاح!<br>
            يمكنك الآن حذف هذا الملف والبدء بتجربة الربط.
          </div>";

} catch (PDOException $e) {
    die("❌ فشل إنشاء قاعدة البيانات: " . $e->getMessage());
}
?>