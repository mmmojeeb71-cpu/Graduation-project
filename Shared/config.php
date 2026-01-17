<?php
// إعدادات الاتصال بقاعدة البيانات
$host = 'localhost';
$db   = 'yemen_gate_db';
$user = 'root'; 
$pass = ''; 

try {
    // إنشاء اتصال PDO
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    // توقف في حال فشل الاتصال
    die("❌ خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}
?>
<?php
// 1. إعدادات الاتصال الأساسية (بناءً على الكود الخاص بك)
$host = 'localhost';
$db   = 'yemen_gate_db';
$user = 'root'; 
$pass = ''; 
$charset = 'utf8mb4';

try {
    // إنشاء اتصال PDO
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 2. إنشاء قاعدة البيانات إذا لم تكن موجودة واستخدامها
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db` "); 

    // 3. تحديث وهيكلة الجداول (الإنتاجية والربط العالمي) [cite: 2026-01-13]
    $tables = [
        "users" => "CREATE TABLE IF NOT EXISTS users (
            user_id BINARY(16) PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        "accounts" => "CREATE TABLE IF NOT EXISTS accounts (
            account_id BINARY(16) PRIMARY KEY,
            user_id BINARY(16),
            currency VARCHAR(3) NOT NULL, -- YER, SAR, USD
            balance DECIMAL(18, 2) DEFAULT 0.00,
            account_number VARCHAR(25) UNIQUE NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "issued_cards" => "CREATE TABLE IF NOT EXISTS issued_cards (
            card_id BINARY(16) PRIMARY KEY,
            user_id BINARY(16),
            account_id BINARY(16), -- الربط الأساسي مع الحساب البنكي
            card_number VARCHAR(20),
            card_holder VARCHAR(100),
            card_balance DECIMAL(18, 2) DEFAULT 0.00,
            -- إضافة محافظ العملات الثلاث للبطاقة [cite: 2026-01-13]
            balance_yer DECIMAL(18, 2) DEFAULT 0.00,
            balance_sar DECIMAL(18, 2) DEFAULT 0.00,
            balance_usd DECIMAL(18, 2) DEFAULT 0.00,
            expiry_month INT(2),
            expiry_year INT(4),
            cvv INT(3),
            card_type VARCHAR(20) DEFAULT 'VISA',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        ) ENGINE=InnoDB",

        "virtual_bank_transactions" => "CREATE TABLE IF NOT EXISTS virtual_bank_transactions (
            transaction_id INT AUTO_INCREMENT PRIMARY KEY, 
            account_id BINARY(16),
            amount DECIMAL(18, 2) NOT NULL,
            transaction_type VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT 'completed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (account_id) REFERENCES accounts(account_id)
        ) ENGINE=InnoDB",

        "merchants" => "CREATE TABLE IF NOT EXISTS merchants (
            merchant_id BINARY(16) PRIMARY KEY,
            user_id BINARY(16) UNIQUE,
            business_name VARCHAR(150),
            merchant_type VARCHAR(50) DEFAULT 'E-commerce',
            settlement_currency VARCHAR(3) DEFAULT 'USD',
            api_key VARCHAR(100) UNIQUE,
            webhook_url TEXT,
            allowed_payment_methods TEXT, -- العمود الجديد لتخزين طرق الدفع المحددة [cite: 2026-01-13]
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    ];

    // تنفيذ إنشاء الجداول
    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
    }

    // 4. إضافة الأعمدة المفقودة في حال كانت الجداول موجودة مسبقاً (Migration) [cite: 2026-01-13]
    $alterations = [
        "ALTER TABLE issued_cards ADD COLUMN IF NOT EXISTS balance_yer DECIMAL(18, 2) DEFAULT 0.00",
        "ALTER TABLE issued_cards ADD COLUMN IF NOT EXISTS balance_sar DECIMAL(18, 2) DEFAULT 0.00",
        "ALTER TABLE issued_cards ADD COLUMN IF NOT EXISTS balance_usd DECIMAL(18, 2) DEFAULT 0.00",
        "ALTER TABLE accounts MODIFY account_number VARCHAR(25) UNIQUE NOT NULL",
        "ALTER TABLE merchants ADD COLUMN IF NOT EXISTS api_key VARCHAR(100) UNIQUE",
        "ALTER TABLE merchants ADD COLUMN IF NOT EXISTS settlement_currency VARCHAR(3) DEFAULT 'USD'",
        "ALTER TABLE merchants ADD COLUMN IF NOT EXISTS merchant_type VARCHAR(50) DEFAULT 'E-commerce'",
        // إضافة العمود المطلوب لحل مشكلة الخطأ الذي ظهر في الصورة [cite: 2026-01-13]
        "ALTER TABLE merchants ADD COLUMN IF NOT EXISTS allowed_payment_methods TEXT"
    ];

    foreach ($alterations as $query) {
        try { $pdo->exec($query); } catch (Exception $e) { /* استمرار في حال وجود العمود */ }
    }

} catch (PDOException $e) {
    die("❌ فشل إعداد النظام المالي: " . $e->getMessage());
}
?>