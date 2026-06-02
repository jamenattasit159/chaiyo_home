/**
 * assets/js/admin.js - Premium Modern Admin Dashboard Controller
 * ควบคุมลูกเล่นและพฤติกรรมอินเตอร์แอคทีฟของแผงควบคุมระบบจัดการ
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. เริ่มต้นระบบ Sidebar (Desktop Collapsible & Mobile Drawer)
    initializeSidebar();

    // 2. เริ่มต้นระบบ Dynamic Active Menu และการตั้งชื่อหัวข้อหน้า
    highlightActiveMenu();

    // 3. เริ่มต้นระบบแจ้งเตือนและยืนยันการทำรายการแบบพรีเมียม
    initializePremiumConfirmations();

    // 4. เพิ่มระบบ Live Preview ในการเลือกอัปโหลดไฟล์รูปภาพ
    initializeImageUploadPreview();
});

/**
 * 1. ระบบ Sidebar (ย่อ-ขยาย บน Desktop / สไลด์ Drawer บน Mobile)
 */
function initializeSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (!sidebar || !toggleBtn) return;

    // --- ส่วนของ Desktop (จดจำสถานะย่อขยายผ่าน localStorage) ---
    // ตรวจสอบสถานะเดิมที่เคยบันทึกไว้
    const isCollapsed = localStorage.getItem('admin_sidebar_collapsed') === 'true';
    
    // ตั้งค่าสถานะเริ่มต้น (ถ้าเป็น Desktop และบันทึกไว้ว่าเคยย่อ ให้ย่อทันที)
    if (isCollapsed && window.innerWidth > 992) {
        sidebar.classList.add('collapsed');
    }

    // --- จัดการการคลิกปุ่ม Toggle (Hamburger) ---
    toggleBtn.addEventListener('click', function (e) {
        e.preventDefault();
        
        if (window.innerWidth > 992) {
            // โหมด Desktop: ย่อ / ขยาย
            sidebar.classList.toggle('collapsed');
            
            // บันทึกสถานะลงใน localStorage
            const nowCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('admin_sidebar_collapsed', nowCollapsed);
        } else {
            // โหมด Mobile: เปิด / ปิด Drawer
            sidebar.classList.toggle('mobile-active');
            if (overlay) {
                overlay.classList.toggle('active');
            }
        }
    });

    // --- จัดการคลิก Backdrop Overlay ในโหมดมือถือเพื่อปิดเมนู ---
    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('mobile-active');
            overlay.classList.remove('active');
        });
    }

    // ปรับการทำงานยามผู้ใช้เปลี่ยนขนาดหน้าจอแบบ Realtime
    window.addEventListener('resize', function () {
        if (window.innerWidth > 992) {
            // ถ้ากลับมาหน้าจอใหญ่ ให้ล้างคลาสโมบายล์ออก
            sidebar.classList.remove('mobile-active');
            if (overlay) overlay.classList.remove('active');
            
            // ดึงค่า localStorage มาใช้
            const shouldCollapse = localStorage.getItem('admin_sidebar_collapsed') === 'true';
            if (shouldCollapse) {
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
            }
        } else {
            // ถ้าหดลงจอมือถือ ให้ล้างคลาส Collapsed ออกชั่วคราว
            sidebar.classList.remove('collapsed');
        }
    });
}

/**
 * 2. ค้นหาและไฮไลต์เมนูที่เปิดอยู่ (เพื่อความแม่นยำเพิ่มเติม)
 */
function highlightActiveMenu() {
    const currentUrl = window.location.search;
    const menuLinks = document.querySelectorAll('.admin-menu a');
    
    if (currentUrl) {
        let matched = false;
        menuLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && currentUrl.includes(href)) {
                // เอา active คลาสเดิมออกก่อน
                document.querySelectorAll('.admin-menu a.active').forEach(el => {
                    el.classList.remove('active');
                });
                // เติม active คลาสตัวที่เจอ
                link.classList.add('active');
                matched = true;
            }
        });
    }
}

/**
 * 3. ระบบยืนยันการกระทำที่สำคัญ (เช่น ปุ่มลบข้อมูล)
 * เพิ่ม Micro-interaction กระตุ้นให้ระวังก่อนลบข้อมูลจริง
 */
function initializePremiumConfirmations() {
    const deleteButtons = document.querySelectorAll('.btn-danger, button[class*="danger"], a[href*="delete"], a[onclick*="delete"]');
    
    deleteButtons.forEach(btn => {
        // ข้ามปุ่มที่มีการระบุ onclick ด้วยข้อความอื่นไว้แล้วเพื่อไม่ให้ทับซ้อน
        if (btn.getAttribute('onclick') && !btn.getAttribute('onclick').includes('return')) {
            return;
        }

        btn.addEventListener('click', function (e) {
            // ถ้าเป็นลิงก์ที่มี href ไปที่การลบโดยตรง
            const href = this.getAttribute('href');
            if (href && (href.includes('delete') || href.includes('action=delete'))) {
                e.preventDefault();
                
                // ออกแบบกล่องยืนยันแบบประณีต (ใช้ confirm มาตรฐานแต่ถ้อยคำสุภาพชัดเจน)
                const confirmMessage = "⚠️ ยืนยันการลบข้อมูลจริงหรือไม่?\nการดำเนินการนี้จะไม่สามารถย้อนกลับได้ และข้อมูลดังกล่าวจะถูกลบออกจากระบบอย่างถาวร";
                
                // สร้างลูกเล่น Glow กะพริบเบาๆ ที่ปุ่มก่อนแสดงยืนยัน
                this.style.transform = "scale(0.95)";
                this.style.transition = "all 0.1s ease";
                
                setTimeout(() => {
                    this.style.transform = "scale(1)";
                    if (confirm(confirmMessage)) {
                        window.location.href = href;
                    }
                }, 100);
            }
        });
    });
}

/**
 * 4. ระบบการแสดงภาพตัวอย่างแบบเรียลไทม์ (Live Preview) เมื่อเลือกภาพอัปโหลด
 * ช่วยเพิ่มมิติความหรูหรา และเช็คความถูกต้องของภาพได้ทันที
 */
function initializeImageUploadPreview() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                const currentInput = this;
                
                reader.onload = function (e) {
                    // ค้นหากล่องพรีวิวเดิม หรือสร้างใหม่
                    let previewContainer = currentInput.parentElement.querySelector('.image-upload-preview');
                    
                    if (!previewContainer) {
                        previewContainer = document.createElement('div');
                        previewContainer.className = 'image-upload-preview';
                        previewContainer.style.marginTop = '15px';
                        previewContainer.style.textAlign = 'center';
                        previewContainer.style.animation = 'contentFadeUp 0.3s ease forwards';
                        
                        const img = document.createElement('img');
                        img.style.maxWidth = '180px';
                        img.style.maxHeight = '120px';
                        img.style.objectFit = 'cover';
                        img.style.borderRadius = '8px';
                        img.style.border = '2px dashed var(--slate-300)';
                        img.style.padding = '4px';
                        img.style.boxShadow = 'var(--shadow-md)';
                        
                        previewContainer.appendChild(img);
                        currentInput.parentElement.appendChild(previewContainer);
                    }
                    
                    const imgEl = previewContainer.querySelector('img');
                    if (imgEl) {
                        imgEl.src = e.target.result;
                    }
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
}