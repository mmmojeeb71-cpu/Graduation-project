<?php
// db.php
$host = 'localhost';
$db   = 'yemen_gate_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage()));
}

// Helpers for BINARY(16) UUID
function uuid_to_bin($uuid) {
    return pack("H*", str_replace('-', '', $uuid));
}

function bin_to_uuid($bin) {
    // إذا كانت القيمة فارغة أو NULL نرجع نص فارغ لتفادي الخطأ
    if ($bin === null || $bin === '') {
        return '';
    }
    $hex = unpack("H*", $bin)[1];
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
}

function gen_uuid() {
    // Generate UUID v4
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return bin_to_uuid($data);
}
