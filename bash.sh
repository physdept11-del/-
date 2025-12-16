# في المجلد الرئيسي
mkdir uploads
mkdir logs

# إنشاء ملف حماية في مجلد uploads
echo "<?php header('HTTP/1.0 403 Forbidden'); echo 'Access Forbidden'; ?>" > uploads/index.php

# إنشاء ملف حماية في مجلد logs
echo "<?php header('HTTP/1.0 403 Forbidden'); echo 'Access Forbidden'; ?>" > logs/index.php

# إنشاء ملف htaccess في uploads
echo "Order Deny,Allow
Deny from all
<FilesMatch '\.(pdf|doc|docx)$'>
    Allow from all
</FilesMatch>" > uploads/.htaccess