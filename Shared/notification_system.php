<?php
// Shared/notification_system.php

/**
 * دالة إرسال الإشعارات العالمية
 *
 */
function sendSystemNotification($user_email, $subject, $message_body) {
    // في بيئة التطوير المحلية، سنقوم بتسجيل الإشعارات في ملف نصي
    // وفي البيئة العالمية، يتم ربطها بـ SMTP أو خدمات مثل SendGrid/Amazon SES
    
    $log_entry = "[" . date('Y-m-d H:i:s') . "] TO: $user_email | SUBJECT: $subject | BODY: $message_body" . PHP_EOL;
    file_put_contents(__DIR__ . '/../../logs/email_notifications.log', $log_entry, FILE_APPEND);

    // كود الإرسال الحقيقي (اختياري عند الرفع للسيرفر)
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Yemen Gate Support <noreply@yemengate.com>" . "\r\n";

    // mail($user_email, $subject, $message_body, $headers);
    return true;
}
?>