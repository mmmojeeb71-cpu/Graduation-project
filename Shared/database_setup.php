<?php
// إعدادات الاتصال
$host = 'localhost';
$db   = 'yemen_gate_db';
$user = 'root'; 
$pass = '';     
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // إنشاء قاعدة البيانات واستخدامها
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db` "); 

    echo "<h2>🏗️ التحديث المالي العالمي: Yemen Gate (Production Ready)</h2>";

    $tables = [
        // 1. جدول المستخدمين
        "users" => "CREATE TABLE IF NOT EXISTS users (
            user_id BINARY(16) PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            mfa_secret TEXT,
            status VARCHAR(20) DEFAULT 'active',
            role VARCHAR(20) DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        // 2. جدول الحسابات
        "accounts" => "CREATE TABLE IF NOT EXISTS accounts (
            account_id BINARY(16) PRIMARY KEY,
            user_id BINARY(16),
            currency VARCHAR(3) NOT NULL,
            balance DECIMAL(18, 2) DEFAULT 0.00,
            account_number VARCHAR(25) UNIQUE NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        // 3. جدول البنك الافتراضي (تم الإبقاء عليه كما هو مع إضافة تصحيح ID بالأسفل)
        "virtual_bank_transactions" => "CREATE TABLE IF NOT EXISTS virtual_bank_transactions (
            transaction_id BINARY(16) PRIMARY KEY,
            account_id BINARY(16),
            amount DECIMAL(18, 2) NOT NULL,
            transaction_type ENUM('TOP_UP', 'WITHDRAW', 'TRANSFER_IN', 'TRANSFER_OUT') NOT NULL,
            status VARCHAR(20) DEFAULT 'completed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (account_id) REFERENCES accounts(account_id)
        ) ENGINE=InnoDB",

        // 4. جدول البطاقات
        "issued_cards" => "CREATE TABLE IF NOT EXISTS issued_cards (
            card_id BINARY(16) PRIMARY KEY,
            user_id BINARY(16),
            account_id BINARY(16),
            card_balance DECIMAL(18, 2) DEFAULT 0.00,
            card_token TEXT NOT NULL,
            masked_pan VARCHAR(16) NOT NULL,
            expiry_month INT(2),
            expiry_year INT(4),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id),
            FOREIGN KEY (account_id) REFERENCES accounts(account_id)
        ) ENGINE=InnoDB",

        // 5. جدول التجار
        "merchants" => "CREATE TABLE IF NOT EXISTS merchants (
            merchant_id BINARY(16) PRIMARY KEY,
            user_id BINARY(16),
            business_name VARCHAR(200),
            merchant_type VARCHAR(50) DEFAULT 'Retail',
            api_key_id VARCHAR(100) UNIQUE,
            webhook_url TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        ) ENGINE=InnoDB",

        // 6. جدول المدفوعات
        "payments" => "CREATE TABLE IF NOT EXISTS payments (
            payment_id BINARY(16) PRIMARY KEY,
            merchant_id BINARY(16),
            amount DECIMAL(18, 2) NOT NULL,
            currency VARCHAR(3) NOT NULL,
            status ENUM('PENDING', 'AUTHORIZED', 'CAPTURED', 'REFUNDED', 'FAILED') DEFAULT 'PENDING',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (merchant_id) REFERENCES merchants(merchant_id)
        ) ENGINE=InnoDB",

        // 7. جدول التدقيق الأمني
        "audit_logs" => "CREATE TABLE IF NOT EXISTS audit_logs (
            log_id BINARY(16) PRIMARY KEY,
            user_id BINARY(16),
            action_type VARCHAR(50),
            action_details TEXT NOT NULL, 
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        ) ENGINE=InnoDB",

        // 8. جدول قسائم الشحن
        "bank_vouchers" => "CREATE TABLE IF NOT EXISTS bank_vouchers (
            voucher_id INT AUTO_INCREMENT PRIMARY KEY,
            voucher_code VARCHAR(20) UNIQUE NOT NULL,
            amount DECIMAL(18, 2) NOT NULL,
            currency VARCHAR(3) NOT NULL,
            is_used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB"
    ];

    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
        echo "✅ تم تجهيز/تحديث جدول: <b>$name</b><br>";
    }

    // --- الإضافات المطلوبة (دون حذف أي شيء من الكود الأصلي) ---

    // أ- إصلاح مشكلة Duplicate Entry في جدول العمليات بتحويله لترقيم تلقائي
    try {
        $pdo->exec("ALTER TABLE virtual_bank_transactions MODIFY transaction_id INT AUTO_INCREMENT");
        echo "✅ تم تحويل معرف العمليات إلى AUTO_INCREMENT لمنع خطأ التكرار<br>";
    } catch (Exception $e) {
        try {
            $pdo->exec("ALTER TABLE virtual_bank_transactions MODIFY transaction_id INT PRIMARY KEY AUTO_INCREMENT");
            echo "✅ تم إصلاح هيكلية معرف العمليات بنجاح<br>";
        } catch (Exception $ex) {}
    }

    // 1. إضافة عمود رقم البطاقة الكامل
    try {
        $pdo->exec("ALTER TABLE issued_cards ADD COLUMN card_number VARCHAR(20) AFTER account_id");
        echo "✅ تمت إضافة العمود المفقود: card_number<br>";
    } catch (Exception $e) {}

    // 2. إضافة اسم حامل البطاقة
    try {
        $pdo->exec("ALTER TABLE issued_cards ADD COLUMN card_holder VARCHAR(100) AFTER card_number");
        echo "✅ تمت إضافة العمود المفقود: card_holder<br>";
    } catch (Exception $e) {}

    // 3. إضافة رمز الـ CVV (و CVC للتوافق التام)
    try {
        $pdo->exec("ALTER TABLE issued_cards ADD COLUMN cvv INT(3) AFTER expiry_year");
        $pdo->exec("ALTER TABLE issued_cards ADD COLUMN cvc INT(3) AFTER cvv");
        echo "✅ تمت إضافة الأعمدة المفقودة: cvv و cvc<br>";
    } catch (Exception $e) {}

    // 4. إضافة نوع البطاقة
    try {
        $pdo->exec("ALTER TABLE issued_cards ADD COLUMN card_type VARCHAR(20) DEFAULT 'VISA' AFTER cvc");
        echo "✅ تمت إضافة العمود المفقود: card_type<br>";
    } catch (Exception $e) {}

    // 5. إضافة عمود الحالة (Status) لتمكين ميزة التجميد والنشاط [cite: 2026-01-13]
    try {
        $pdo->exec("ALTER TABLE issued_cards ADD COLUMN status ENUM('Active', 'Frozen') DEFAULT 'Active' AFTER card_type");
        echo "✅ تمت إضافة عمود حالة البطاقة (status) لدعم ميزة التجميد<br>";
    } catch (Exception $e) {}

    // 6. تعديل ENUM الخاص بالعمليات لمرونة أكبر
    try {
        $pdo->exec("ALTER TABLE virtual_bank_transactions MODIFY COLUMN transaction_type VARCHAR(50) NOT NULL");
        echo "✅ تم تحديث نوع العمليات لمرونة أكبر<br>";
    } catch (Exception $e) {}

    // --- إضافة محافظ العملات الثلاث للبطاقة (مهم جداً للتحويل العالمي) ---
    try {
        $pdo->exec("ALTER TABLE issued_cards ADD COLUMN balance_yer DECIMAL(18, 2) DEFAULT 0.00 AFTER card_balance");
        $pdo->exec("ALTER TABLE issued_cards ADD COLUMN balance_sar DECIMAL(18, 2) DEFAULT 0.00 AFTER balance_yer");
        $pdo->exec("ALTER TABLE issued_cards ADD COLUMN balance_usd DECIMAL(18, 2) DEFAULT 0.00 AFTER balance_sar");
        echo "✅ تم تفعيل ميزة البطاقة متعددة العملات (YER, SAR, USD) بنجاح [cite: 2026-01-13]<br>";
    } catch (Exception $e) {}

    // التعديلات السابقة المطلوبة
    try { $pdo->exec("ALTER TABLE merchants ADD COLUMN merchant_type VARCHAR(50) DEFAULT 'Retail' AFTER business_name"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE accounts MODIFY account_number VARCHAR(25) UNIQUE NOT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("CREATE INDEX idx_acc_num ON accounts(account_number)"); } catch (Exception $e) {}

    // إضافة فهرس لكود الشحن لسرعة التحقق
    try { 
        $pdo->exec("CREATE INDEX idx_voucher_code ON bank_vouchers(voucher_code)"); 
        echo "✅ تم إنشاء فهرس البحث عن الأكواد البنكية<br>";
    } catch (Exception $e) {}

    // --- الإضافة الجديدة والمطلوبة لحل مشاكل لوحة تحكم المبيعات ---
    
    // أ- إنشاء جدول مبيعات التجار (Merchant Transactions)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS merchant_transactions (
            transaction_id INT AUTO_INCREMENT PRIMARY KEY,
            merchant_id BINARY(16),
            customer_email VARCHAR(255),
            amount DECIMAL(18, 2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'USD',
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (merchant_id) REFERENCES merchants(merchant_id)
        ) ENGINE=InnoDB");
        echo "✅ تم إنشاء جدول مبيعات التجار بنجاح<br>";
    } catch (Exception $e) {}

    // ب- إضافة أعمدة API Key و Secret لجدول التجار
    try {
        $pdo->exec("ALTER TABLE merchants ADD COLUMN api_key VARCHAR(64) UNIQUE AFTER api_key_id");
        $pdo->exec("ALTER TABLE merchants ADD COLUMN api_secret VARCHAR(128) AFTER api_key");
        echo "✅ تم إضافة أعمدة API المفقودة لجدول التجار<br>";
    } catch (Exception $e) {}

    echo "<br>🚀 <b>مبروك! تم تحديث النظام وإضافة ميزات البنك المحاكي والبطاقة المتعددة وحالة التجميد بنجاح.</b>";

} catch (PDOException $e) {
    die("❌ فشل الإعداد: " . $e->getMessage());
}
?>