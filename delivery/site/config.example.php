<?php
/**
 * إعدادات الموقع — انسخ الملف ده باسم config.php وعدّل القيم.
 * 🔴 config.php مالوش رفع على Git ولا حد يشوفه.
 */
return [
    // بيانات قاعدة البيانات من hPanel ← MySQL Databases
    'db_host' => 'localhost',
    'db_name' => 'اسم_قاعدة_البيانات',
    'db_user' => 'اسم_المستخدم',
    'db_pass' => 'كلمة_السر',

    // كلمة سر لوحة الإدارة — غيّرها لحاجة قوية
    'admin_password' => 'change-me-now',

    // سر توقيع كوكي الدخول — أي نص طويل عشوائي (٣٢ حرف أو أكتر)
    'session_secret' => 'change-this-to-a-long-random-string-32-chars-min',

    // رقم الدعم اللي بيظهر في التذييل
    'support_phone' => '01000000000',

    // اسم البلد اللي بيظهر في الهيدر
    'main_zone' => 'القيصرية',
];
