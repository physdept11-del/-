<?php
// ملف إرسال البريد الإلكتروني عبر الخادم
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// استقبال البيانات
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'لم يتم استلام البيانات']);
    exit;
}

// إعدادات البريد
$to = "phys.dept11@gmail.com";
$subject = "تسجيل جديد في مؤتمر الفيزياء الدولي - " . ($data['registration_id'] ?? 'غير محدد');

// بناء محتوى البريد
$message = "
<!DOCTYPE html>
<html dir='rtl' lang='ar'>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #1e3a8a, #0369a1); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8fafc; padding: 30px; border: 1px solid #e2e8f0; }
        .section { margin-bottom: 20px; padding: 15px; background: white; border-radius: 5px; border-right: 4px solid #3b82f6; }
        .label { font-weight: bold; color: #1e3a8a; }
        .footer { background: #f1f5f9; padding: 20px; text-align: center; color: #64748b; font-size: 14px; border-radius: 0 0 10px 10px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>تسجيل جديد في مؤتمر الفيزياء الدولي 2026</h1>
            <p>رقم التسجيل: " . ($data['registration_id'] ?? '') . "</p>
        </div>
        
        <div class='content'>
            <div class='section'>
                <h2>👤 البيانات الشخصية</h2>
                <table>
                    <tr><td class='label'>الاسم الكامل (عربي):</td><td>" . ($data['full_name_ar'] ?? '') . "</td></tr>
                    <tr><td class='label'>الاسم الكامل (إنجليزي):</td><td>" . ($data['full_name_en'] ?? '') . "</td></tr>
                    <tr><td class='label'>البريد الإلكتروني:</td><td>" . ($data['email'] ?? '') . "</td></tr>
                    <tr><td class='label'>رقم الهاتف:</td><td>" . ($data['phone'] ?? '') . "</td></tr>
                    <tr><td class='label'>الجنسية:</td><td>" . ($data['nationality'] ?? '') . "</td></tr>
                </table>
            </div>
            
            <div class='section'>
                <h2>🎓 المعلومات الأكاديمية</h2>
                <table>
                    <tr><td class='label'>المسمى الوظيفي:</td><td>" . ($data['academic_title'] ?? '') . "</td></tr>
                    <tr><td class='label'>الجامعة / المؤسسة:</td><td>" . ($data['institution'] ?? '') . "</td></tr>
                    <tr><td class='label'>القسم / التخصص:</td><td>" . ($data['department'] ?? '') . "</td></tr>
                    <tr><td class='label'>المدينة / الدولة:</td><td>" . ($data['city_country'] ?? '') . "</td></tr>
                </table>
            </div>
            
            <div class='section'>
                <h2>🔬 معلومات المشاركة</h2>
                <table>
                    <tr><td class='label'>نوع المشاركة:</td><td>" . ($data['participation_type'] ?? '') . "</td></tr>
                    <tr><td class='label'>تخصص البحث:</td><td>" . ($data['research_specialization'] ?? '') . "</td></tr>
                    <tr><td class='label'>عنوان البحث (عربي):</td><td>" . ($data['research_title_ar'] ?? '') . "</td></tr>
                    <tr><td class='label'>عنوان البحث (إنجليزي):</td><td>" . ($data['research_title_en'] ?? '') . "</td></tr>
                </table>
            </div>
        </div>
        
        <div class='footer'>
            <p>تم استلام التسجيل في: " . ($data['timestamp'] ?? '') . "</p>
            <p>© 2026 - المؤتمر الدولي الأول للفيزياء وتطبيقاتها في التنمية المستدامة</p>
        </div>
    </div>
</body>
</html>
";

// إعدادات البريد
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type: text/html; charset=utf-8" . "\r\n";
$headers .= "From: مؤتمر الفيزياء الدولي <noreply@physics-conference.edu>" . "\r\n";
$headers .= "Reply-To: " . ($data['email'] ?? '') . "\r\n";

// إرسال البريد
if (mail($to, $subject, $message, $headers)) {
    echo json_encode([
        'success' => true,
        'message' => 'تم إرسال البيانات بنجاح إلى بريد المؤتمر'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في إرسال البريد'
    ]);
}
?>
