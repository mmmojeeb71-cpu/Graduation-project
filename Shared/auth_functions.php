<?php
/**
 * نظام بوابة اليمن الإلكترونية - ملف الدوال المساعدة
 * هذا الملف يحتوي على الوظائف الحيوية لتوليد المعرفات الفريدة وتأمين المدخلات
 */

// 1. دالة توليد UUID (متطلب أساسي للأمان والربط بين الجداول)
if (!function_exists('generate_uuid')) {
    function generate_uuid() {
        // توليد 16 بايت عشوائي وتحويلها إلى نص سداسي عشري
        // نستخدم هذا الأسلوب لأنه يتوافق تماماً مع نوع BINARY(16) في قاعدة البيانات
        return bin2hex(random_bytes(16));
    }
}

// 2. دالة تنظيف البيانات (لحماية النظام من هجمات XSS و SQL Injection)
if (!function_exists('clean_input')) {
    function clean_input($data) {
        $data = trim($data);            // إزالة المسافات الزائدة
        $data = stripslashes($data);    // إزالة المائلات الخلفية
        $data = htmlspecialchars($data);// تحويل الرموز الخاصة إلى نصوص آمنة
        return $data;
    }
}

/**
 * ملاحظة: هذا الملف يتم استدعاؤه في register_logic.php و login_logic.php
 * لضمان أن جميع المعرفات يتم توليدها بنفس الطريقة القياسية.
 */
?>