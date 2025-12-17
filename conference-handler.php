<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// إعدادات البريد
require_once '../config/mail-config.php';
require_once '../config/database-config.php';
require_once 'mailer.php';

// الاستجابة الافتراضية
$response = [
    'success' => false,
    'message' => '',
    'registration_id' => '',
    'email_sent' => false
];

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'طريقة الطلب غير مسموحة';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// الحصول على البيانات
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $data = $_POST; // في حالة إرسال form-data
}

// التحقق من البيانات المطلوبة
$required_fields = [
    'full_name_ar', 'full_name_en', 'email', 'phone',
    'nationality', 'academic_title', 'institution',
    'department', 'city_country', 'participation_type',
    'research_specialization', 'research_title_ar',
    'research_title_en', 'abstract_ar', 'abstract_en'
];

foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        $response['message'] = 'الحقل ' . $field . ' مطلوب';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// تنظيف البيانات
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

foreach ($data as $key => $value) {
    $data[$key] = clean_input($value);
}

// إنشاء رقم تسجيل فريد
$registration_id = 'REG-' . date('Ymd') . '-' . substr(md5(uniqid()), 0, 6);
$data['registration_id'] = $registration_id;
$data['registration_date'] = date('Y-m-d H:i:s');
$data['ip_address'] = $_SERVER['REMOTE_ADDR'];

// حفظ في قاعدة البيانات
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "INSERT INTO conference_registrations (
        registration_id, full_name_ar, full_name_en, email, phone,
        nationality, passport, academic_title, institution, department,
        city_country, participation_type, research_specialization,
        research_title_ar, research_title_en, abstract_ar, abstract_en,
        registration_date, ip_address, status
    ) VALUES (
        :registration_id, :full_name_ar, :full_name_en, :email, :phone,
        :nationality, :passport, :academic_title, :institution, :department,
        :city_country, :participation_type, :research_specialization,
        :research_title_ar, :research_title_en, :abstract_ar, :abstract_en,
        :registration_date, :ip_address, 'pending'
    )";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
    
    // إرسال البريد الإلكتروني
    $mailer = new ConferenceMailer();
    $email_sent = $mailer->sendRegistrationConfirmation($data);
    
    $response['success'] = true;
    $response['message'] = 'تم تسجيل مشاركتك بنجاح';
    $response['registration_id'] = $registration_id;
    $response['email_sent'] = $email_sent;
    
} catch (PDOException $e) {
    $response['message'] = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
    error_log('Database Error: ' . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'حدث خطأ: ' . $e->getMessage();
    error_log('General Error: ' . $e->getMessage());
}

// إرجاع الاستجابة
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>