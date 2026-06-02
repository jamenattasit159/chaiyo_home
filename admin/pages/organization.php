<?php
// admin/pages/organization.php

// ตรวจสอบ Session
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// จัดการการบันทึกข้อมูล
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'update_organization') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $vision = $_POST['vision'] ?? '';
        $mission = $_POST['mission'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $address = $_POST['address'] ?? '';

        // [เพิ่ม] รับค่าแผนที่ Google Map
        $google_map = $_POST['google_map'] ?? '';

        // ค่าเริ่มต้นเอามาจาก Input hidden (ซึ่งจะเปลี่ยนเมื่อเลือก emoji หรืออัปโหลด)
        $icon = $_POST['icon'] ?? '🏥';

        // --- ส่วนจัดการอัปโหลดโลโก้ (เพิ่มรองรับ .ico) ---
        if (isset($_FILES['custom_logo']) && $_FILES['custom_logo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'];
            $filename = $_FILES['custom_logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $uploadDir = '../uploads/logos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newFilename = 'logo_' . time() . '.' . $ext;
                $uploadPath = $uploadDir . $newFilename;

                if (move_uploaded_file($_FILES['custom_logo']['tmp_name'], $uploadPath)) {
                    $icon = 'uploads/logos/' . $newFilename;
                }
            } else {
                $message = '<div class="alert alert-error gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none"><i class="fa-solid fa-circle-exclamation"></i> <span>ไฟล์รองรับเฉพาะรูปภาพ (JPG, PNG, GIF, WEBP, ICO) เท่านั้น</span></div>';
            }
        }
        // ------------------------------

        if (empty($message)) {
            try {
                // [แก้ไข] เพิ่ม google_map ในคำสั่ง UPDATE
                $stmt = $pdo->prepare(
                    "UPDATE organization_info SET name=?, description=?, vision=?, mission=?, phone=?, email=?, address=?, google_map=?, logo=?, updated_at=NOW() WHERE id=1"
                );
                $stmt->execute([$name, $description, $vision, $mission, $phone, $email, $address, $google_map, $icon]);
                $message = '<div class="alert alert-success gap-2 py-3 px-5 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none"><i class="fa-solid fa-circle-check"></i> <span>บันทึกข้อมูลหน่วยงานเรียบร้อยแล้ว</span></div>';
            } catch (Exception $e) {
                $message = '<div class="alert alert-error gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none"><i class="fa-solid fa-circle-exclamation"></i> <span>เกิดข้อผิดพลาด: ' . $e->getMessage() . '</span></div>';
            }
        }
    }
}

// ดึงข้อมูลหน่วยงานปัจจุบัน
$orgInfo = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();

if (!$orgInfo) {
    $pdo->query("INSERT INTO organization_info (id, name, logo) VALUES (1, 'สถาบันอุตสาหกรรมสุขภาพ', '🏥')");
    $orgInfo = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();
}

$currentLogo = $orgInfo['logo'] ?? '🏥';
$isCustomLogo = strpos($currentLogo, 'uploads/') !== false;
?>

<h2 class="text-3xl font-black text-base-content flex items-center gap-2 mb-6">🏢 จัดการข้อมูลหน่วยงาน</h2>

<?php if ($message): ?>
    <div class="mb-6">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card bg-base-100 shadow-xl border border-base-200">
    <div class="card-body p-8">
        <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-8">
            <input type="hidden" name="action" value="update_organization">

            <!-- Section 1: Name and Logo -->
            <div>
                <h3 class="text-xl font-bold text-primary flex items-center gap-2 border-b border-base-200 pb-3 mb-6">
                    <i class="fa-solid fa-circle-info"></i> ชื่อและสัญลักษณ์หน่วยงาน
                </h3>
                
                <div class="grid grid-cols-1 gap-6">
                    <div class="form-control w-full">
                        <label class="label"><span class="label-text font-bold text-xs uppercase text-base-content/60">ชื่อหน่วยงาน *</span></label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($orgInfo['name'] ?? ''); ?>" placeholder="เช่น โรงพยาบาลไชโย" class="input input-bordered w-full rounded-xl focus:input-primary" required />
                    </div>

                    <div class="form-control w-full">
                        <label class="label"><span class="label-text font-bold text-xs uppercase text-base-content/60">เลือกสัญลักษณ์ (Emoji หรือ อัปโหลดรูปภาพ)</span></label>
                        <div class="flex flex-wrap gap-4 items-center">
                            <!-- Custom Upload Box -->
                            <div class="relative w-16 h-16 rounded-2xl border-2 border-dashed border-base-300 hover:border-primary flex items-center justify-center cursor-pointer overflow-hidden transition-all bg-base-200/50 hover:bg-base-200 group <?php echo $isCustomLogo ? 'border-solid border-primary' : ''; ?>" id="logoUploadBox">
                                <?php if ($isCustomLogo): ?>
                                    <img src="../<?php echo htmlspecialchars($currentLogo); ?>" class="w-full h-full object-contain p-2" id="logoPreview">
                                <?php else: ?>
                                    <span class="text-xl text-base-content/40 group-hover:text-primary"><i class="fa-solid fa-plus"></i></span>
                                <?php endif; ?>
                                <input type="file" name="custom_logo" accept=".jpg,.jpeg,.png,.gif,.webp,.ico" class="absolute inset-0 opacity-0 cursor-pointer z-10" onchange="previewUpload(this)" />
                            </div>

                            <!-- Emojis Grid -->
                            <div class="flex flex-wrap gap-2">
                                <?php
                                $emojis = ['🏥', '🏢', '🏛️', '🎓', '⚕️', '🔬', '🏆', '⭐', '🌟', '💼', '🎯', '🚀'];
                                foreach ($emojis as $emo) {
                                    $active = (!$isCustomLogo && $currentLogo === $emo) ? 'btn-active bg-primary text-white border-primary' : 'btn-ghost hover:bg-base-200';
                                    echo "<button type='button' class='btn btn-square rounded-xl text-2xl font-normal $active' onclick=\"selectIcon('$emo', this)\">$emo</button>";
                                }
                                ?>
                            </div>
                        </div>
                        <p class="text-xs text-base-content/40 mt-2 flex items-center gap-1"><i class="fa-solid fa-circle-info"></i> กดที่กล่อง + เพื่ออัปโหลดไฟล์รูปภาพของหน่วยงาน (.png, .jpg, .ico)</p>
                        <input type="hidden" id="iconInput" name="icon" value="<?php echo htmlspecialchars($currentLogo); ?>">
                    </div>
                </div>
            </div>

            <!-- Section 2: General Info -->
            <div>
                <h3 class="text-xl font-bold text-primary flex items-center gap-2 border-b border-base-200 pb-3 mb-6">
                    <i class="fa-solid fa-file-lines"></i> ข้อมูลแนะนำและนโยบาย
                </h3>
                
                <div class="grid grid-cols-1 gap-6">
                    <div class="form-control w-full">
                        <label class="label"><span class="label-text font-bold text-xs uppercase text-base-content/60">คำอธิบายหน่วยงาน (Description)</span></label>
                        <textarea name="description" rows="3" class="textarea textarea-bordered w-full rounded-xl focus:textarea-primary" placeholder="พิมพ์คำอธิบายหรือประวัติย่อของหน่วยงาน..."><?php echo htmlspecialchars($orgInfo['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control w-full">
                            <label class="label"><span class="label-text font-bold text-xs uppercase text-base-content/60">วิสัยทัศน์ (Vision)</span></label>
                            <textarea name="vision" rows="3" class="textarea textarea-bordered w-full rounded-xl focus:textarea-primary" placeholder="พิมพ์วิสัยทัศน์ของหน่วยงาน..."><?php echo htmlspecialchars($orgInfo['vision'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-control w-full">
                            <label class="label"><span class="label-text font-bold text-xs uppercase text-base-content/60">พันธกิจ (Mission)</span></label>
                            <textarea name="mission" rows="3" class="textarea textarea-bordered w-full rounded-xl focus:textarea-primary" placeholder="พิมพ์พันธกิจของหน่วยงาน..."><?php echo htmlspecialchars($orgInfo['mission'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Contact & Location -->
            <div>
                <h3 class="text-xl font-bold text-primary flex items-center gap-2 border-b border-base-200 pb-3 mb-6">
                    <i class="fa-solid fa-map-location-dot"></i> ข้อมูลติดต่อและพิกัดที่ตั้ง
                </h3>
                
                <div class="grid grid-cols-1 gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control w-full">
                            <label class="label"><span class="label-text font-bold text-xs uppercase text-base-content/60">เบอร์โทรศัพท์ติดต่อ</span></label>
                            <div class="relative w-full">
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($orgInfo['phone'] ?? ''); ?>" class="input input-bordered w-full pl-12 rounded-xl focus:input-primary" placeholder="เช่น 035-xxx-xxx" />
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base-content/40"><i class="fa-solid fa-phone"></i></span>
                            </div>
                        </div>
                        <div class="form-control w-full">
                            <label class="label"><span class="label-text font-bold text-xs uppercase text-base-content/60">อีเมลหน่วยงาน</span></label>
                            <div class="relative w-full">
                                <input type="email" name="email" value="<?php echo htmlspecialchars($orgInfo['email'] ?? ''); ?>" class="input input-bordered w-full pl-12 rounded-xl focus:input-primary" placeholder="เช่น contact@hospital.com" />
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base-content/40"><i class="fa-solid fa-envelope"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-control w-full">
                        <label class="label"><span class="label-text font-bold text-xs uppercase text-base-content/60">ที่อยู่หน่วยงาน (Address)</span></label>
                        <textarea name="address" rows="3" class="textarea textarea-bordered w-full rounded-xl focus:textarea-primary" placeholder="พิมพ์ที่อยู่เต็มของหน่วยงาน..."><?php echo htmlspecialchars($orgInfo['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-control w-full">
                        <label class="label"><span class="label-text font-bold text-xs uppercase text-base-content/60">แผนที่ Google Maps (วางโค้ด iframe สำหรับฝัง)</span></label>
                        <textarea name="google_map" rows="4" class="textarea textarea-bordered w-full rounded-xl font-mono text-sm focus:textarea-primary" placeholder='<iframe src="https://www.google.com/maps/embed?..." ...></iframe>'><?php echo htmlspecialchars($orgInfo['google_map'] ?? ''); ?></textarea>
                        <p class="text-xs text-base-content/40 mt-2 flex items-center gap-1"><i class="fa-solid fa-circle-info"></i> ไปที่ Google Maps > ค้นหาสถานที่ > กดแชร์ (Share) > ฝังแผนที่ (Embed a map) > คัดลอกโค้ด HTML มาวางที่นี่</p>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="border-t border-base-200 pt-6 flex justify-end">
                <button type="submit" class="btn btn-primary px-8 rounded-xl font-bold gap-2">
                    <i class="fa-solid fa-save"></i> บันทึกข้อมูลทั้งหมด
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function selectIcon(icon, btnElement) {
        // เคลียร์ความแอคทีฟของปุ่มและกล่องอัปโหลด
        document.querySelectorAll('.btn-square').forEach(el => el.classList.remove('btn-active', 'bg-primary', 'text-white', 'border-primary'));
        document.querySelectorAll('.btn-square').forEach(el => el.classList.add('btn-ghost'));
        document.getElementById('logoUploadBox').classList.remove('border-solid', 'border-primary');
        
        // เซ็ตให้ปุ่มนี้แอคทีฟ
        btnElement.classList.remove('btn-ghost');
        btnElement.classList.add('btn-active', 'bg-primary', 'text-white', 'border-primary');
        
        // เซ็ตค่าใน Input
        document.getElementById('iconInput').value = icon;
        
        // ล้างค่าอัปโหลดรูปภาพ
        document.querySelector('input[name="custom_logo"]').value = '';
        
        // เปลี่ยนพรีวิวในกล่องอัปโหลดกลับเป็นเครื่องหมายบวก
        document.getElementById('logoUploadBox').innerHTML = `
            <span class="text-xl text-base-content/40 group-hover:text-primary"><i class="fa-solid fa-plus"></i></span>
            <input type="file" name="custom_logo" accept=".jpg,.jpeg,.png,.gif,.webp,.ico" class="absolute inset-0 opacity-0 cursor-pointer z-10" onchange="previewUpload(this)" />
        `;
    }

    function previewUpload(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                // เคลียร์ความแอคทีฟของปุ่มอิโมจิทั้งหมด
                document.querySelectorAll('.btn-square').forEach(el => el.classList.remove('btn-active', 'bg-primary', 'text-white', 'border-primary'));
                document.querySelectorAll('.btn-square').forEach(el => el.classList.add('btn-ghost'));
                
                // เซ็ตให้กล่องอัปโหลดแอคทีฟ
                var box = document.getElementById('logoUploadBox');
                box.classList.add('border-solid', 'border-primary');
                
                // ทำการอัปเดตและแสดงพรีวิวภาพด้านใน
                var fileInputHtml = `<input type="file" name="custom_logo" accept=".jpg,.jpeg,.png,.gif,.webp,.ico" class="absolute inset-0 opacity-0 cursor-pointer z-10" onchange="previewUpload(this)" />`;
                box.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-contain p-2" id="logoPreview" />
                    ${fileInputHtml}
                `;
                
                // เก็บค่าว่างไว้เพื่อรอตรวจรับไฟล์หลังโพสต์
                document.getElementById('iconInput').value = '';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>