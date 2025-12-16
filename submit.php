<?php
// api/submit.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// إنشاء مجلد للمرفقات إذا لم يكن موجوداً
$uploadsDir = '../uploads/';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

// إنشاء مجلد السجلات
$logsDir = '../logs/';
if (!file_exists($logsDir)) {
    mkdir($logsDir, 0777, true);
}

// التحقق مما إذا كان الطلب يحتوي على ملف
if (isset($_FILES['abstract_file']) && $_FILES['abstract_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['abstract_file'];
    
    // التحقق من حجم الملف (5MB كحد أقصى)
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxFileSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'حجم الملف يتجاوز الحد المسموح (5MB)']);
        exit;
    }
    
    // التحقق من نوع الملف
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'نوع الملف غير مدعوم. يرجى رفع ملف PDF أو Word']);
        exit;
    }
    
    // إنشاء اسم فريد للملف
    $fileName = 'abstract_' . time() . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filePath = $uploadsDir . $fileName;
    
    // حفظ الملف
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        $fileUploaded = true;
        $fileInfo = [
            'original_name' => $file['name'],
            'saved_name' => $fileName,
            'size' => $file['size'],
            'type' => $file['type'],
            'path' => $filePath
        ];
    } else {
        $fileUploaded = false;
        $fileInfo = null;
        logError('فشل في حفظ الملف: ' . $file['name']);
    }
} else {
    $fileUploaded = false;
    $fileInfo = null;
    $uploadError = isset($_FILES['abstract_file']) ? $_FILES['abstract_file']['error'] : 'No file uploaded';
}

// قراءة البيانات النصية
$data = $_POST;

// إضافة معلومات الملف إلى البيانات
if ($fileUploaded && $fileInfo) {
    $data['file_info'] = $fileInfo;
    $data['has_abstract_file'] = 'نعم';
} else {
    $data['has_abstract_file'] = 'لا';
    $data['upload_error'] = $uploadError ?? 'لم يتم رفع أي ملف';
}

// تسجيل الطلب
logSubmission($data);

// إعداد بيانات البريد الإلكتروني
$to = 'sciphyc@mans.edu.eg'; // الإيميل الرئيسي للمؤتمر
$subject = 'تسجيل جديد في مؤتمر الفيزياء 2026 - مع ملف ملخص';

// إنشاء محتوى البريد
$message = "
<!DOCTYPE html>
<html dir='rtl' lang='ar'>
<head>
    <meta charset='UTF-8'>
    <title>تسجيل جديد في مؤتمر الفيزياء</title>
    <style>
        body { font-family: 'Cairo', Arial, sans-serif; line-height: 1.8; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; background: #f9fafb; }
        .header { background: linear-gradient(135deg, #1e3a8a 0%, #0369a1 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: white; padding: 30px; border-radius: 0 0 10px 10px; }
        .section { margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid #e5e7eb; }
        .label { font-weight: bold; color: #1e40af; width: 200px; display: inline-block; }
        .value { color: #374151; }
        .file-info { background: #d1fae5; padding: 15px; border-radius: 8px; border-right: 4px solid #10b981; margin: 20px 0; }
        .no-file { background: #fef3c7; padding: 15px; border-radius: 8px; border-right: 4px solid #f59e0b; margin: 20px 0; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 2px solid #3b82f6; text-align: center; color: #6b7280; font-size: 14px; }
        .highlight { background: #dbeafe; padding: 15px; border-radius: 8px; border-right: 4px solid #3b82f6; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>📄 تسجيل جديد مع ملف ملخص</h1>
            <p>📅 " . date('Y-m-d H:i:s') . "</p>
        </div>
        
        <div class='content'>
            <div class='highlight'>
                <h3>🆕 تسجيل جديد رقم: REG-" . time() . "</h3>
                <p>تم استلام طلب تسجيل جديد مع " . ($fileUploaded ? 'ملف ملخص' : 'بدون ملف') . "</p>
            </div>
            
            <div class='section'>
                <h3>👤 البيانات الشخصية</h3>
                <p><span class='label'>الاسم الكامل (عربي):</span> <span class='value'>" . htmlspecialchars($data['full_name_ar'] ?? '') . "</span></p>
                <p><span class='label'>الاسم الكامل (إنجليزي):</span> <span class='value'>" . htmlspecialchars($data['full_name_en'] ?? '') . "</span></p>
                <p><span class='label'>البريد الإلكتروني:</span> <span class='value'>" . htmlspecialchars($data['email'] ?? '') . "</span></p>
                <p><span class='label'>رقم الهاتف:</span> <span class='value'>" . htmlspecialchars($data['phone'] ?? '') . "</span></p>
                <p><span class='label'>الجنسية:</span> <span class='value'>" . htmlspecialchars($data['nationality'] ?? '') . "</span></p>
            </div>
            
            <div class='section'>
                <h3>🎓 المعلومات الأكاديمية</h3>
                <p><span class='label'>المسمى الوظيفي:</span> <span class='value'>" . htmlspecialchars($data['academic_title'] ?? '') . "</span></p>
                <p><span class='label'>الجامعة / المؤسسة:</span> <span class='value'>" . htmlspecialchars($data['institution'] ?? '') . "</span></p>
                <p><span class='label'>القسم / التخصص:</span> <span class='value'>" . htmlspecialchars($data['department'] ?? '') . "</span></p>
            </div>
            
            <div class='section'>
                <h3>📝 معلومات البحث</h3>
                <p><span class='label'>نوع المشاركة:</span> <span class='value'>" . htmlspecialchars($data['participation_type'] ?? '') . "</span></p>
                <p><span class='label'>عنوان البحث (عربي):</span> <span class='value'>" . htmlspecialchars($data['research_title_ar'] ?? 'غير محدد') . "</span></p>
                <p><span class='label'>عنوان البحث (إنجليزي):</span> <span class='value'>" . htmlspecialchars($data['research_title_en'] ?? 'غير محدد') . "</span></p>
            </div>
            
            <div class='section'>
                <h3>📎 معلومات الملف المرفق</h3>
                " . ($fileUploaded ? "
                <div class='file-info'>
                    <h4>✅ تم استلام ملف الملخص</h4>
                    <p><strong>اسم الملف:</strong> " . htmlspecialchars($fileInfo['original_name'] ?? '') . "</p>
                    <p><strong>حجم الملف:</strong> " . formatFileSize($fileInfo['size'] ?? 0) . "</p>
                    <p><strong>نوع الملف:</strong> " . htmlspecialchars($fileInfo['type'] ?? '') . "</p>
                    <p><strong>اسم الملف على الخادم:</strong> " . htmlspecialchars($fileInfo['saved_name'] ?? '') . "</p>
                    <p><strong>مسار الملف:</strong> " . htmlspecialchars($fileInfo['path'] ?? '') . "</p>
                </div>
                " : "
                <div class='no-file'>
                    <h4>⚠️ لم يتم رفع ملف ملخص</h4>
                    <p>المشارك لم يرفع ملف ملخص للبحث.</p>
                </div>
                ") . "
            </div>
            
            <div class='section'>
                <h3>🏨 متطلبات إضافية</h3>
                <p><span class='label'>حجز إقامة فندقية:</span> <span class='value'>" . ($data['hotel_accommodation'] ?? 'لا') . "</span></p>
            </div>
            
            <div class='footer'>
                <h4>🔗 روابط مهمة للوصول للملف:</h4>
                " . ($fileUploaded ? "
                <p>يمكنك الوصول للملف من خلال:</p>
                <ul>
                    <li>المسار المباشر على الخادم: " . htmlspecialchars($fileInfo['path'] ?? '') . "</li>
                    <li>اسم الملف: " . htmlspecialchars($fileInfo['saved_name'] ?? '') . "</li>
                </ul>
                " : "") . "
                <p>📍 مؤتمر الفيزياء الدولي - جامعة المنصورة</p>
                <p>📧 sciphyc@mans.edu.eg | 📅 20-24 أبريل 2026</p>
                <p>⏰ تم الاستلام في: " . date('Y-m-d H:i:s') . "</p>
            </div>
        </div>
    </div>
</body>
</html>
";

// إعداد رؤوس البريد
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Physics Conference <noreply@physics-conference.mans.edu.eg>\r\n";
$headers .= "Reply-To: " . ($data['email'] ?? 'sciphyc@mans.edu.eg') . "\r\n";
$headers .= "X-Priority: 1\r\n"; // عالي الأولوية
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// إرسال البريد
if (mail($to, $subject, $message, $headers)) {
    // إرسال بريد تأكيد للباحث
    sendConfirmationEmail($data, $fileUploaded, $fileInfo);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'تم إرسال الطلب والملف بنجاح',
        'registration_id' => 'REG-' . time(),
        'file_uploaded' => $fileUploaded,
        'file_name' => $fileInfo['original_name'] ?? null
    ]);
} else {
    // تسجيل الخطأ
    logError('فشل في إرسال البريد الإلكتروني');
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'تم استلام الطلب ولكن حدث خطأ في إرسال البريد. سيتم التواصل معك.',
        'registration_id' => 'REG-' . time()
    ]);
}

// ============================================
// الدوال المساعدة
// ============================================

function sendConfirmationEmail($data, $fileUploaded, $fileInfo) {
    $to = $data['email'] ?? '';
    if (empty($to)) return;
    
    $subject = 'تأكيد تسجيلك في مؤتمر الفيزياء الدولي 2026';
    
    $fileMessage = $fileUploaded ? 
        "<div style='background:#d1fae5; padding:15px; border-radius:8px; border-right:4px solid #10b981; margin:15px 0;'>
            <h4>✅ تم استلام ملف الملخص بنجاح</h4>
            <p>اسم الملف: " . htmlspecialchars($fileInfo['original_name'] ?? '') . "</p>
            <p>سيتم مراجعته من قبل اللجنة العلمية.</p>
        </div>" : 
        "<div style='background:#fef3c7; padding:15px; border-radius:8px; border-right:4px solid #f59e0b; margin:15px 0;'>
            <h4>⚠️ ملاحظة: لم ترسل ملف الملخص</h4>
            <p>يمكنك إرساله لاحقاً على sciphyc@mans.edu.eg مع ذكر رقم التسجيل.</p>
        </div>";
    
    $message = "
    <!DOCTYPE html>
    <html dir='rtl' lang='ar'>
    <head>
        <meta charset='UTF-8'>
        <title>تأكيد التسجيل</title>
        <style>
            body { font-family: 'Cairo', Arial, sans-serif; line-height: 1.8; color: #333; }
            .container { max-width: 700px; margin: 0 auto; padding: 20px; background: #f9fafb; }
            .header { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white; padding: 30px; text-align: center; border-radius: 10px; }
            .content { background: white; padding: 30px; margin-top: 20px; border-radius: 10px; }
            .highlight { background: #dbeafe; padding: 15px; border-radius: 8px; border-right: 4px solid #3b82f6; margin: 20px 0; }
            .deadline { background: #fee2e2; padding: 15px; border-radius: 8px; border-right: 4px solid #ef4444; margin: 20px 0; }
            .info-box { background: #e0f2fe; padding: 15px; border-radius: 8px; border-right: 4px solid #0ea5e9; margin: 20px 0; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 تم تأكيد تسجيلك بنجاح!</h1>
                <p>المؤتمر الدولي الأول للفيزياء وتطبيقاتها في التنمية المستدامة</p>
            </div>
            
            <div class='content'>
                <h2>عزيزي/عزيزتي " . htmlspecialchars($data['full_name_ar'] ?? '') . "</h2>
                
                <div class='highlight'>
                    <h3>✅ تم تسجيل طلبك بنجاح</h3>
                    <p><strong>رقم التسجيل:</strong> REG-" . time() . "</p>
                    <p><strong>تاريخ التسجيل:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>
                
                " . $fileMessage . "
                
                <div class='info-box'>
                    <h3>📋 معلومات مهمة:</h3>
                    <ul>
                        <li>احفظ رقم التسجيل للمتابعة</li>
                        <li>ستتلقى تحديثات على بريدك الإلكتروني</li>
                        <li>تحقق من مجلد Spam إذا لم تتلقى رسائلنا</li>
                        <li>لأي استفسار: sciphyc@mans.edu.eg</li>
                    </ul>
                </div>
                
                <div class='deadline'>
                    <h3>⏰ المواعيد النهائية المهمة:</h3>
                    <p><strong>✅ تقديم الملخصات:</strong> 30 يناير 2026</p>
                    <p><strong>✅ تقديم البحث الكامل:</strong> 28 فبراير 2026</p>
                    <p><strong>📅 تاريخ المؤتمر:</strong> 20-24 أبريل 2026</p>
                </div>
                
                <p>شكراً لتسجيلك في مؤتمر الفيزياء الدولي. سنتواصل معك قريباً.</p>
                
                <div class='footer'>
                    <p>📍 كلية العلوم - جامعة المنصورة</p>
                    <p>📅 20-24 أبريل 2026 | 📍 المنصورة - شرم الشيخ</p>
                    <p>🔗 هذا البريد آلي، يرجى عدم الرد عليه</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: مؤتمر الفيزياء <sciphyc@mans.edu.eg>\r\n";
    $headers .= "Reply-To: sciphyc@mans.edu.eg\r\n";
    
    mail($to, $subject, $message, $headers);
}

function logSubmission($data) {
    global $logsDir;
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'data' => $data
    ];
    
    $logFile = $logsDir . 'submissions_' . date('Y-m-d') . '.log';
    file_put_contents($logFile, json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
}

function logError($message) {
    global $logsDir;
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => 'ERROR',
        'message' => $message,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ];
    
    $logFile = $logsDir . 'errors_' . date('Y-m-d') . '.log';
    file_put_contents($logFile, json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>