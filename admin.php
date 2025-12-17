<?php
session_start();
require_once '../config/database-config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// جلب إحصائيات
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    
    // إحصائيات عامة
    $stats = [
        'total' => $conn->query("SELECT COUNT(*) FROM conference_registrations")->fetchColumn(),
        'pending' => $conn->query("SELECT COUNT(*) FROM conference_registrations WHERE status = 'pending'")->fetchColumn(),
        'confirmed' => $conn->query("SELECT COUNT(*) FROM conference_registrations WHERE status = 'confirmed'")->fetchColumn(),
        'oral' => $conn->query("SELECT COUNT(*) FROM conference_registrations WHERE participation_type = 'بحث كامل'")->fetchColumn(),
        'poster' => $conn->query("SELECT COUNT(*) FROM conference_registrations WHERE participation_type = 'بوستر'")->fetchColumn(),
    ];
    
    // التسجيلات الحديثة
    $recent_stmt = $conn->prepare("
        SELECT registration_id, full_name_ar, email, institution, registration_date, status 
        FROM conference_registrations 
        ORDER BY registration_date DESC 
        LIMIT 10
    ");
    $recent_stmt->execute();
    $recent_registrations = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - مؤتمر الفيزياء</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- شريط التنقل العلوي -->
        <nav class="admin-navbar">
            <div class="nav-brand">
                <h1><i class="fas fa-cogs"></i> لوحة التحكم</h1>
                <span class="nav-subtitle">مؤتمر الفيزياء الدولي 2026</span>
            </div>
            <div class="nav-user">
                <span>مرحباً، <?php echo $_SESSION['admin_name']; ?></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> خروج</a>
            </div>
        </nav>
        
        <!-- المحتوى الرئيسي -->
        <div class="admin-content">
            <div class="stats-grid">
                <div class="stat-card bg-blue">
                    <h3>إجمالي التسجيلات</h3>
                    <p class="stat-number"><?php echo $stats['total']; ?></p>
                    <i class="fas fa-users stat-icon"></i>
                </div>
                <div class="stat-card bg-orange">
                    <h3>قيد المراجعة</h3>
                    <p class="stat-number"><?php echo $stats['pending']; ?></p>
                    <i class="fas fa-clock stat-icon"></i>
                </div>
                <div class="stat-card bg-green">
                    <h3>مؤكدين</h3>
                    <p class="stat-number"><?php echo $stats['confirmed']; ?></p>
                    <i class="fas fa-check-circle stat-icon"></i>
                </div>
                <div class="stat-card bg-purple">
                    <h3>عروض شفوية</h3>
                    <p class="stat-number"><?php echo $stats['oral']; ?></p>
                    <i class="fas fa-chalkboard-teacher stat-icon"></i>
                </div>
            </div>
            
            <!-- قائمة التسجيلات الحديثة -->
            <div class="recent-registrations">
                <h2><i class="fas fa-history"></i> آخر التسجيلات</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>رقم التسجيل</th>
                            <th>الاسم</th>
                            <th>المؤسسة</th>
                            <th>البريد</th>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_registrations as $registration): ?>
                        <tr>
                            <td><?php echo $registration['registration_id']; ?></td>
                            <td><?php echo htmlspecialchars($registration['full_name_ar']); ?></td>
                            <td><?php echo htmlspecialchars($registration['institution']); ?></td>
                            <td><?php echo htmlspecialchars($registration['email']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($registration['registration_date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $registration['status']; ?>">
                                    <?php echo $registration['status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="view-registration.php?id=<?php echo $registration['registration_id']; ?>" 
                                   class="btn-action btn-view">
                                    <i class="fas fa-eye"></i> عرض
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- أدوات سريعة -->
            <div class="quick-actions">
                <h2><i class="fas fa-bolt"></i> أدوات سريعة</h2>
                <div class="action-buttons">
                    <a href="registrations.php" class="action-btn">
                        <i class="fas fa-list"></i>
                        <span>عرض جميع التسجيلات</span>
                    </a>
                    <a href="export.php" class="action-btn">
                        <i class="fas fa-file-export"></i>
                        <span>تصدير البيانات</span>
                    </a>
                    <a href="send-email.php" class="action-btn">
                        <i class="fas fa-envelope"></i>
                        <span>إرسال بريد جماعي</span>
                    </a>
                    <a href="settings.php" class="action-btn">
                        <i class="fas fa-cog"></i>
                        <span>الإعدادات</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>