<?php
// api/send-email.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// قراءة البيانات
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$action = $data['action'] ?? '';

switch ($action) {
    case 'send_registration':
        sendRegistrationEmail($data);
        break;
    case 'send_file_notification':
        sendFileNotification($data);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

function sendRegistrationEmail($data) {
    $to = 'phys.dept11@gmail.com';
    $subject = '📋 تسجيل جديد في مؤتمر الفيزياء الدولي';
    
    $message = createRegistrationEmail($data);
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: مؤتمر الفيزياء <noreply@physics-conference.mans.edu.eg>\r\n";
    $headers .= "Reply-To: " . ($data['email'] ?? 'phys.dept11@gmail.com') . "\r\n";
    $headers .= "X-Priority: 1\r\n";
    
    if (mail($to, $subject, $message, $headers)) {
        // إرسال تأكيد للمشارك
        sendConfirmationEmail($data);
        
        echo json_encode(['success' => true, 'message' => 'تم إرسال البريد بنجاح']);
    } else {
        // المحاولة باستخدام SMTP بديل
        sendViaSMTP($to, $subject, $message);
    }
}

function sendFileNotification($data) {
    $to = 'phys.dept11@gmail.com';
    $subject = '📎 ملف ملخص مرفق - مؤتمر الفيزياء';
    
    $message = "
    <html>
    <body dir='rtl'>
        <h2>📎 إشعار برفع ملف ملخص</h2>
        <p><strong>الباحث:</strong> {$data['participant_name']}</p>
        <p><strong>البريد الإلكتروني:</strong> {$data['participant_email']}</p>
        <p><strong>اسم الملف:</strong> {$data['file_name']}</p>
        <p><strong>نوع الملف:</strong> {$data['file_type']}</p>
        <p><strong>حجم الملف:</strong> " . formatBytes($data['file_size']) . "</p>
        <p><strong>التاريخ:</strong> " . date('Y-m-d H:i:s') . "</p>
        <hr>
        <p><em>ملاحظة: تم تسجيل الباحث في Google Sheets. يرجى طلب الملف منه إذا لزم الأمر.</em></p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: ملفات المؤتمر <files@physics-conference.mans.edu.eg>\r\n";
    
    mail($to, $subject, $message, $headers);
}

function createRegistrationEmail($data) {
    return "
    <!DOCTYPE html>
    <html dir='rtl' lang='ar'>
    <head>
        <meta charset='UTF-8'>
        <title>تسجيل جديد</title>
        <style>
            body { font-family: 'Cairo', Arial, sans-serif; line-height: 1.8; color: #333; max-width: 800px; margin: 0 auto; }
            .header { background: linear-gradient(135deg, #1e3a8a, #0369a1); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .section { margin: 20px 0; padding: 20px; background: #f8fafc; border-radius: 8px; border-right: 4px solid #3b82f6; }
            .label { font-weight: bold; color: #1e40af; display: inline-block; width: 180px; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 2px solid #e5e7eb; text-align: center; color: #6b7280; }
            .alert { background: #d1fae5; padding: 15px; border-radius: 8px; border-right: 4px solid #10b981; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🎉 تسجيل جديد في مؤتمر الفيزياء</h1>
            <p>تاريخ الاستلام: " . date('Y-m-d H:i:s') . "</p>
        </div>
        
        <div class='content'>
            <div class='alert'>
                <h3>✅ تم تسجيل مشارك جديد</h3>
                <p>رقم التسجيل: REG-" . time() . "</p>
            </div>
            
            <div class='section'>
                <h3>👤 المعلومات الشخصية</h3>
                <p><span class='label'>الاسم:</span> " . htmlspecialchars($data['full_name_ar'] ?? '') . "</p>
                <p><span class='label'>البريد:</span> " . htmlspecialchars($data['email'] ?? '') . "</p>
                <p><span class='label'>الهاتف:</span> " . htmlspecialchars($data['phone'] ?? '') . "</p>
                <p><span class='label'>الجنسية:</span> " . htmlspecialchars($data['nationality'] ?? '') . "</p>
            </div>
            
            <div class='section'>
                <h3>🎓 المعلومات الأكاديمية</h3>
                <p><span class='label'>الجامعة:</span> " . htmlspecialchars($data['institution'] ?? '') . "</p>
                <p><span class='label'>القسم:</span> " . htmlspecialchars($data['department'] ?? '') . "</p>
                <p><span class='label'>المسمى:</span> " . htmlspecialchars($data['academic_title'] ?? '') . "</p>
            </div>
            
            <div class='section'>
                <h3>📝 نوع المشاركة</h3>
                <p><span class='label'>النوع:</span> " . htmlspecialchars($data['participation_type'] ?? '') . "</p>
                <p><span class='label'>عنوان البحث:</span> " . htmlspecialchars($data['research_title_ar'] ?? 'غير محدد') . "</p>
            </div>
            
            <div class='footer'>
                <p>📍 مؤتمر الفيزياء الدولي - جامعة المنصورة</p>
                <p>📧 sciphyc@mans.edu.eg | 📞 للاستفسار</p>
                <p>⏰ تم الاستلام تلقائياً عبر النظام الإلكتروني</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function sendConfirmationEmail($data) {
    $to = $data['email'];
    $subject = '✅ تأكيد تسجيلك في مؤتمر الفيزياء الدولي 2026';
    
    $message = "
    <!DOCTYPE html>
    <html dir='rtl' lang='ar'>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Cairo, Arial; line-height: 1.8;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #1e40af, #3b82f6); color: white; padding: 30px; text-align: center; border-radius: 10px;'>
                <h1>شكراً لتسجيلك! 🎉</h1>
                <p>المؤتمر الدولي الأول للفيزياء وتطبيقاتها</p>
            </div>
            
            <div style='background: white; padding: 30px; margin-top: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h2>عزيزي/عزيزتي " . htmlspecialchars($data['full_name_ar'] ?? '') . "</h2>
                
                <div style='background: #d1fae5; padding: 15px; border-radius: 8px; margin: 20px 0; border-right: 4px solid #10b981;'>
                    <h3>✅ تم تأكيد تسجيلك بنجاح</h3>
                    <p><strong>رقم التسجيل:</strong> REG-" . time() . "</p>
                    <p><strong>تاريخ التسجيل:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>
                
                <p>نشكرك على تسجيلك في مؤتمر الفيزياء الدولي 2026. سنتواصل معك قريباً بشأن تفاصيل مشاركتك.</p>
                
                <div style='background: #fee2e2; padding: 15px; border-radius: 8px; margin: 20px 0; border-right: 4px solid #ef4444;'>
                    <h3>⏰ المواعيد النهائية:</h3>
                    <p>• تقديم الملخصات: 30 يناير 2026</p>
                    <p>• تقديم الأبحاث الكاملة: 28 فبراير 2026</p>
                    <p>• تاريخ المؤتمر: 20-24 أبريل 2026</p>
                </div>
                
                <p>لأي استفسار، يرجى التواصل معنا على: <strong>sciphyc@mans.edu.eg</strong></p>
                
                <hr style='margin: 30px 0;'>
                
                <div style='text-align: center; color: #6b7280; font-size: 14px;'>
                    <p>📍 كلية العلوم - جامعة المنصورة</p>
                    <p>📅 20-24 أبريل 2026 | شرم الشيخ، مصر</p>
                    <p><em>هذا البريد آلي، يرجى عدم الرد عليه</em></p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: مؤتمر الفيزياء <phys.dept11@gmail.com>\r\n";
    
    mail($to, $subject, $message, $headers);
}

function sendViaSMTP($to, $subject, $message) {
    // بديل باستخدام SMTP إذا فشل mail()
    require_once 'PHPMailer/PHPMailer.php';
    require_once 'PHPMailer/SMTP.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    
    try {
        // إعدادات SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        $mail->setFrom('noreply@physics-conference.mans.edu.eg', 'مؤتمر الفيزياء');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'تم الإرسال عبر SMTP']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $mail->ErrorInfo]);
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
