// js/conference-handler.js
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('conferenceForm');
    const submitBtn = document.getElementById('submitBtn');
    const successModal = document.getElementById('successModal');
    const modalContent = document.getElementById('modalContent');
    
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // التحقق من صحة النموذج
            if (!form.checkValidity()) {
                alert('يرجى ملء جميع الحقول المطلوبة بشكل صحيح');
                return;
            }
            
            // التحقق من حجم الملف
            const fileInput = document.getElementById('abstract_file');
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (file.size > maxSize) {
                    alert('حجم الملف يتجاوز الحد المسموح (5MB)');
                    return;
                }
                
                // التحقق من نوع الملف
                const allowedTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];
                
                if (!allowedTypes.includes(file.type)) {
                    alert('نوع الملف غير مدعوم. يرجى رفع ملف PDF أو Word (DOC/DOCX)');
                    return;
                }
            }
            
            // جمع بيانات النموذج (باستخدام FormData للملفات)
            const formData = new FormData(form);
            
            // إضافة معلومات إضافية
            formData.append('submission_time', new Date().toISOString());
            formData.append('user_agent', navigator.userAgent);
            
            // عرض مؤشر التحميل
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> جاري الإرسال...';
            submitBtn.disabled = true;
            
            try {
                // إرسال البيانات إلى الخادم
                const response = await fetch('/api/submit.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    // حفظ في localStorage للعرض المحلي
                    const submissions = JSON.parse(localStorage.getItem('conference_submissions') || '[]');
                    const submissionData = Object.fromEntries(formData);
                    
                    submissions.push({
                        ...submissionData,
                        timestamp: new Date().toISOString(),
                        id: result.registration_id,
                        file_uploaded: result.file_uploaded || false,
                        file_name: result.file_name || null
                    });
                    
                    localStorage.setItem('conference_submissions', JSON.stringify(submissions));
                    
                    // عرض نافذة النجاح
                    showSuccessModal(submissionData, result);
                    
                    // إعادة تعيين النموذج
                    form.reset();
                    
                    // إعادة تعيين خيار الفندق
                    const hotelDetails = document.getElementById('hotelDetails');
                    if (hotelDetails) {
                        hotelDetails.classList.add('hidden');
                    }
                } else {
                    throw new Error(result.message || 'حدث خطأ في الإرسال');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('حدث خطأ في إرسال الطلب: ' + error.message + '\nيرجى المحاولة مرة أخرى أو التواصل معنا.');
            } finally {
                // إعادة تعيين الزر
                submitBtn.innerHTML = '<i class="fas fa-paper-plane ml-2"></i> إرسال طلب المشاركة';
                submitBtn.disabled = false;
            }
        });
    }
    
    function showSuccessModal(data, result) {
        // تعبئة البيانات في النافذة
        document.getElementById('userName').textContent = data.full_name_ar || data.full_name_en;
        document.getElementById('userEmail').textContent = data.email;
        document.getElementById('registrationId').textContent = result.registration_id;
        
        // إضافة معلومات الملف إذا تم رفعه
        const detailsList = document.querySelector('#modalContent ul');
        if (result.file_uploaded && result.file_name) {
            const fileItem = document.createElement('li');
            fileItem.textContent = `تم استلام ملف: ${result.file_name}`;
            fileItem.className = 'text-green-600 font-medium';
            detailsList.appendChild(fileItem);
        }
        
        // عرض النافذة
        successModal.classList.remove('hidden');
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 50);
    }
    
    // إغلاق النافذة عند النقر خارجها
    successModal.addEventListener('click', function(e) {
        if (e.target === successModal) {
            closeModal();
        }
    });
    
    // إضافة تأثير عند التركيز على الحقول
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('ring-2', 'ring-blue-200');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('ring-2', 'ring-blue-200');
        });
    });
    
    // إظهار رسالة عند اختيار ملف
    const fileInput = document.getElementById('abstract_file');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
                
                // عرض معلومات الملف
                const fileInfo = this.parentElement.querySelector('.file-info');
                if (!fileInfo) {
                    const infoDiv = document.createElement('div');
                    infoDiv.className = 'file-info mt-3 p-3 bg-blue-50 rounded-lg border-r-2 border-blue-300';
                    infoDiv.innerHTML = `
                        <p class="text-sm text-blue-700">
                            <i class="fas fa-file-alt ml-2"></i>
                            تم اختيار ملف: <strong>${file.name}</strong> (${fileSize} MB)
                        </p>
                        <p class="text-xs text-blue-600 mt-1">
                            <i class="fas fa-info-circle ml-2"></i>
                            سيتم إرسال هذا الملف مع طلب التسجيل
                        </p>
                    `;
                    this.parentElement.appendChild(infoDiv);
                } else {
                    fileInfo.querySelector('strong').textContent = file.name;
                    fileInfo.querySelector('strong').insertAdjacentText('after', ` (${fileSize} MB)`);
                }
            }
        });
    }
});

// دالة إغلاق النافذة
function closeModal() {
    const modal = document.getElementById('successModal');
    const modalContent = document.getElementById('modalContent');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}