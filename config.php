<?php
// إعدادات البريد الإلكتروني
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'phys.dept11@gmail.com');
define('SMTP_PASS', 'your-secure-password');
define('SMTP_SECURE', 'tls');

// عناوين البريد
define('FROM_EMAIL', 'phys.dept11@gmail.com');
define('FROM_NAME', 'مؤتمر الفيزياء الدولي');
define('ADMIN_EMAIL', 'admin@physics-conference.edu');
define('REPLY_TO', 'noreply@physics-conference.edu');

// إعدادات الموقع
define('SITE_URL', 'https://physics-conference.mans.edu.eg');
define('CONFERENCE_NAME', 'المؤتمر الدولي الأول للفيزياء وتطبيقاتها في التنمية المستدامة');
define('CONFERENCE_DATES', '20-24 أبريل 2026');

// مسارات القوالب
define('TEMPLATE_PATH', dirname(__DIR__) . '/responses/');
?>
