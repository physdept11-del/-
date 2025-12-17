<?php
// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'physics_conference_2026');
define('DB_USER', 'conference_user');
define('DB_PASS', 'secure_password_here');
define('DB_CHARSET', 'utf8mb4');

// إنشاء الجدول إذا لم يكن موجوداً (لأول مرة)
function createDatabaseTables($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS conference_registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        registration_id VARCHAR(50) UNIQUE NOT NULL,
        full_name_ar VARCHAR(255) NOT NULL,
        full_name_en VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        nationality VARCHAR(100) NOT NULL,
        passport VARCHAR(100),
        academic_title VARCHAR(100) NOT NULL,
        institution VARCHAR(255) NOT NULL,
        department VARCHAR(255) NOT NULL,
        city_country VARCHAR(255) NOT NULL,
        participation_type VARCHAR(50) NOT NULL,
        research_specialization VARCHAR(255) NOT NULL,
        research_title_ar TEXT NOT NULL,
        research_title_en TEXT NOT NULL,
        abstract_ar TEXT NOT NULL,
        abstract_en TEXT NOT NULL,
        registration_date DATETIME NOT NULL,
        ip_address VARCHAR(45),
        status ENUM('pending', 'confirmed', 'rejected', 'cancelled') DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'free') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_status (status),
        INDEX idx_registration_date (registration_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    
    CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        role ENUM('super_admin', 'admin', 'viewer') DEFAULT 'admin',
        last_login DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS email_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        registration_id VARCHAR(50),
        email_type VARCHAR(50),
        recipient_email VARCHAR(255),
        subject VARCHAR(255),
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('sent', 'failed') DEFAULT 'sent',
        error_message TEXT,
        FOREIGN KEY (registration_id) REFERENCES conference_registrations(registration_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    INSERT IGNORE INTO admin_users (username, password_hash, full_name, email, role) 
    VALUES ('admin', SHA2('admin123', 256), 'مدير النظام', 'admin@physics-conference.edu', 'super_admin');";
    
    try {
        $conn->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Create table error: " . $e->getMessage());
        return false;
    }
}
?>