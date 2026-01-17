<?php
// webhook_handler.php المحدث
$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, true);

if ($data && $data['status'] === 'completed') {
    // الاتصال بقاعدة بيانات المتجر
    $conn = new PDO("mysql:host=localhost;dbname=mtgr_db", "root", "");
    
    // تسجيل الطلب كمكتمل
    $stmt = $conn->prepare("INSERT INTO store_orders (transaction_id, amount, currency, status) VALUES (?, ?, ?, 'completed')");
    $stmt->execute([$data['transaction_id'], $data['amount'], $data['currency']]);
    
    http_response_code(200);
}
?>