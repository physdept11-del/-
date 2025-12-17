<?php
require_once '../vendor/autoload.php'; // إذا استخدمت PHPMailer

class ConferenceMailer {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        $this->setupMailer();
    }
    
    private function setupMailer() {
        // إعدادات SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = SMTP_HOST;
        $this->mailer->Port = SMTP_PORT;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = SMTP_USER;
        $this->mailer->Password = SMTP_PASS;
        $this->mailer->SMTPSecure = SMTP_SECURE;
        $this->mailer->CharSet = 'UTF-8';
        
        // إعدادات المرسل
        $this->mailer->setFrom(FROM_EMAIL, FROM_NAME);
        $this->mailer->addReplyTo(REPLY_TO, FROM_NAME);
    }
    
    public function sendRegistrationConfirmation($data) {
        try {
            // إرسال للمشارك
            $this->mailer->addAddress($data['email'], $data['full_name_ar']);
            $this->mailer->Subject = 'تأكيد التسجيل - ' . CONFERENCE_NAME;
            $this->mailer->isHTML(true);
            
            // تحميل قالب البريد
            $template = file_get_contents(TEMPLATE_PATH . 'confirmation.html');
            $template = $this->replacePlaceholders($template, $data);
            
            $this->mailer->Body = $template;
            $this->mailer->AltBody = $this->generatePlainText($data);
            
            $this->mailer->send();
            
            // إرسال نسخة للإدارة
            $this->sendAdminNotification($data);
            
            return true;
            
        } catch (Exception $e) {
            error_log('Mail Error: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    private function replacePlaceholders($template, $data) {
        $placeholders = [
            '{{conference_name}}' => CONFERENCE_NAME,
            '{{conference_dates}}' => CONFERENCE_DATES,
            '{{registration_id}}' => $data['registration_id'],
            '{{full_name_ar}}' => $data['full_name_ar'],
            '{{full_name_en}}' => $data['full_name_en'],
            '{{email}}' => $data['email'],
            '{{phone}}' => $data['phone'],
            '{{academic_title}}' => $data['academic_title'],
            '{{institution}}' => $data['institution'],
            '{{participation_type}}' => $data['participation_type'],
            '{{research_title_ar}}' => $data['research_title_ar'],
            '{{site_url}}' => SITE_URL,
            '{{current_year}}' => date('Y')
        ];
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }
    
    private function generatePlainText($data) {
        return "شكراً لتسجيلك في " . CONFERENCE_NAME . "\n\n" .
               "رقم التسجيل: " . $data['registration_id'] . "\n" .
               "الاسم: " . $data['full_name_ar'] . "\n" .
               "نوع المشاركة: " . $data['participation_type'] . "\n\n" .
               "سنتواصل معك قريباً للمزيد من التفاصيل.\n\n" .
               "مع تحيات لجنة المؤتمر";
    }
    
    private function sendAdminNotification($data) {
        try {
            $adminMailer = new PHPMailer\PHPMailer\PHPMailer(true);
            $adminMailer->isSMTP();
            // نفس إعدادات البريد...
            
            $adminMailer->addAddress(ADMIN_EMAIL, 'مدير المؤتمر');
            $adminMailer->Subject = 'تسجيل جديد - ' . $data['full_name_ar'];
            
            $adminContent = "تم تسجيل مشارك جديد:\n\n";
            foreach ($data as $key => $value) {
                $adminContent .= "$key: $value\n";
            }
            
            $adminMailer->Body = nl2br($adminContent);
            $adminMailer->send();
            
        } catch (Exception $e) {
            error_log('Admin notification error: ' . $e->getMessage());
        }
    }
}
?>