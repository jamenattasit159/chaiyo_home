<?php
// admin/pages/pr_images.php - จัดการรูปประชาสัมพันธ์

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// === จัดการอัปโหลด & ลบรูป ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. อัปโหลดรูปใหม่
    if ($action == 'upload' && isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> รองรับเฉพาะไฟล์รูปภาพเท่านั้น (JPG, PNG, GIF, WebP)</div>';
        } elseif ($file['size'] > 8 * 1024 * 1024) { // 8MB
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> ขนาดไฟล์ต้องไม่เกิน 8MB</div>';
        } else {
            $description = trim($_POST['description'] ?? '');
            if (empty($description))
                $description = $file['name'];

            $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $filepath = '../uploads/files/' . $filename;

            if (!is_dir('../uploads/files')) {
                mkdir('../uploads/files', 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $stmt = $pdo->prepare("INSERT INTO files (filename, filepath, file_type, category, status, created_at) VALUES (?, ?, ?, 'pr_activity', 'active', NOW())");
                $stmt->execute([$description, $filename, $ext]);

                $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-check-circle text-lg"></i> อัปโหลดรูปภาพประชาสัมพันธ์เรียบร้อยแล้ว</div>';
            } else {
                $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> ไม่สามารถบันทึกไฟล์ได้</div>';
            }
        }
    }

    // 2. ลบรูป
    elseif ($action == 'delete' && !empty($_POST['id'])) {
        $id = (int) $_POST['id'];

        $stmt = $pdo->prepare("SELECT filepath FROM files WHERE id = ? AND category = 'pr_activity'");
        $stmt->execute([$id]);
        $file = $stmt->fetchColumn();

        if ($file && file_exists('../uploads/files/' . $file)) {
            @unlink('../uploads/files/' . $file);
        }

        $pdo->prepare("DELETE FROM files WHERE id = ?")->execute([$id]);
        $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-check-circle text-lg"></i> ลบรูปภาพเรียบร้อยแล้ว</div>';
    }
}

// ดึงรูปทั้งหมดในหมวด pr_activity
$pr_images = $pdo->query("SELECT * FROM files WHERE category = 'pr_activity' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- หัวข้อของหน้า -->
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-extrabold text-base-content flex items-center gap-2.5">
            <i class="fas fa-photo-film text-primary"></i> จัดการรูปประชาสัมพันธ์ / กิจกรรม
        </h2>
        <p class="text-sm text-base-content/60 mt-1">อัปเดตและแสดงแกลเลอรีรูปภาพข่าวสาร กิจกรรมหน่วยงาน เพื่อนำไปแสดงในหน้าเว็บหลัก</p>
    </div>
</div>

<?php echo $message; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
    <!-- ฟอร์มอัปโหลดรูป (1 ส่วน) -->
    <div class="card bg-base-100 shadow-xl border border-base-200">
        <div class="card-body">
            <h3 class="card-title text-lg font-bold text-base-content flex items-center gap-2 border-b border-base-200 pb-3 mb-2">
                <i class="fas fa-cloud-upload-alt text-primary"></i> อัปโหลดรูปกิจกรรมใหม่
            </h3>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="upload">

                <!-- อัปโหลดดีไซน์พรีเมียม -->
                <div class="form-control">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/85">ไฟล์รูปภาพ</span>
                    </label>
                    <div class="border-2 border-dashed border-base-300 rounded-2xl p-6 text-center cursor-pointer hover:border-primary hover:bg-primary/5 transition duration-200 group flex flex-col items-center justify-center gap-2" 
                         onclick="document.getElementById('imgInput').click()">
                        <i class="fas fa-images text-3xl text-base-content/30 group-hover:text-primary group-hover:scale-110 transition duration-200"></i>
                        <span class="text-xs font-bold text-base-content/80 group-hover:text-primary">คลิกเพื่อเลือกไฟล์ภาพ</span>
                        <span class="text-[10px] text-base-content/50">JPG, PNG, GIF, WebP (ไม่เกิน 8MB)</span>
                    </div>
                    <input type="file" id="imgInput" name="image" accept="image/*" class="hidden" required>
                    <!-- แถบแสดงสถานะเลือกไฟล์ -->
                    <div id="fileInfo" class="text-xs font-semibold text-primary mt-2 min-h-[16px] text-center"></div>
                </div>

                <!-- คำอธิบายรูปภาพ -->
                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/80">คำอธิบายรูปภาพ (ไม่บังคับ)</span>
                    </label>
                    <input type="text" name="description" class="input input-bordered w-full"
                           placeholder="เช่น พิธีทำบุญตักบาตรประจำปี 2568...">
                </div>

                <div class="card-actions justify-end pt-2">
                    <button type="submit" class="btn btn-primary w-full gap-2">
                        <i class="fas fa-arrow-circle-up text-lg"></i> อัปโหลดรูปภาพ
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- แกลเลอรีรูปภาพทั้งหมด (2 ส่วน) -->
    <div class="card bg-base-100 shadow-xl border border-base-200 xl:col-span-2 overflow-hidden">
        <div class="p-6 pb-0 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="card-title text-lg font-bold text-base-content flex items-center gap-2">
                    <i class="fas fa-images text-primary"></i> รายการรูปภาพกิจกรรมทั้งหมด
                </h3>
                <p class="text-xs text-base-content/55 mt-0.5">ภาพกิจกรรมที่เปิดใช้งานจะเรียงลำดับจากล่าสุดขึ้นก่อน</p>
            </div>
            <div class="badge badge-neutral font-bold py-3 px-3.5 select-none shrink-0 self-start sm:self-center">
                ทั้งหมด <?php echo count($pr_images); ?> รูป
            </div>
        </div>

        <div class="card-body mt-4">
            <?php if (empty($pr_images)): ?>
                <div class="flex flex-col items-center justify-center text-center p-12 bg-base-200/40 rounded-2xl border border-base-200">
                    <i class="fas fa-photo-video text-5xl text-base-content/20 mb-3 animate-pulse"></i>
                    <h4 class="font-bold text-base text-base-content">ยังไม่มีรูปภาพประชาสัมพันธ์</h4>
                    <p class="text-xs text-base-content/50 mt-1 max-w-[280px]">เริ่มต้นโดยอัปโหลดภาพกิจกรรมภาพแรกด้วยแบบฟอร์มด้านซ้าย</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php foreach ($pr_images as $img): ?>
                        <div class="card bg-base-100 border border-base-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-200 overflow-hidden group">
                            <!-- พรีวิวรูปภาพ -->
                            <figure class="aspect-[4/3] bg-base-300 relative overflow-hidden">
                                <img src="../uploads/files/<?php echo htmlspecialchars($img['filepath']); ?>"
                                     alt="<?php echo htmlspecialchars($img['filename']); ?>"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <span class="absolute top-2 left-2 badge badge-neutral bg-black/60 text-[9px] border-none text-white py-2 px-2.5 font-bold">
                                    <?php echo strtoupper(htmlspecialchars($img['file_type'])); ?>
                                </span>
                            </figure>
                            
                            <div class="p-3.5 flex flex-col justify-between flex-1 gap-2">
                                <div class="min-w-0">
                                    <div class="font-bold text-xs text-base-content line-clamp-2 min-h-[32px] break-all leading-relaxed" 
                                         title="<?php echo htmlspecialchars($img['filename']); ?>">
                                        <?php echo htmlspecialchars($img['filename']); ?>
                                    </div>
                                    <div class="text-[10px] text-base-content/50 flex items-center gap-1 mt-1 font-semibold">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($img['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <form method="POST" onsubmit="return confirm('ยืนยันลบรูปนี้หรือไม่?');" class="mt-1">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $img['id']; ?>">
                                    <button type="submit" class="btn btn-error btn-outline btn-xs w-full gap-1 py-2 h-auto text-[10px]">
                                        <i class="fa-solid fa-trash-can"></i> ลบรูปภาพ
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // แสดงชื่อไฟล์ที่เลือก
    document.getElementById('imgInput').addEventListener('change', function () {
        if (this.files[0]) {
            document.getElementById('fileInfo').innerHTML = '<i class="fas fa-file-image"></i> เลือกไฟล์แล้ว: <span class="text-base-content font-bold">' + this.files[0].name + '</span>';
        } else {
            document.getElementById('fileInfo').textContent = '';
        }
    });
</script>
