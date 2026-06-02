<?php
// admin/pages/files.php - จัดการรูปทั่วไปและประชาสัมพันธ์

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// === การจัดการไฟล์ ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. อัปโหลดรูปภาพ
    if ($action == 'upload' && isset($_FILES['file']['error']) === 0) {
        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> รองรับเฉพาะไฟล์รูปภาพเท่านั้น (JPG, PNG, GIF, WebP)</div>';
        } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> ขนาดไฟล์ต้องไม่เกิน 10MB</div>';
        } else {
            $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $path = '../uploads/files/' . $filename;

            if (!is_dir('../uploads/files')) {
                mkdir('../uploads/files', 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $path)) {
                $originalName = pathinfo($file['name'], PATHINFO_FILENAME);

                $stmt = $pdo->prepare("INSERT INTO files (filename, filepath, file_type, category, status, created_at) VALUES (?, ?, ?, 'pr_image', 'active', NOW())");
                $stmt->execute([$originalName, $filename, $ext]);

                $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-check-circle text-lg"></i> อัปโหลดรูปภาพเรียบร้อยแล้ว</div>';
            } else {
                $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> ไม่สามารถบันทึกไฟล์ได้</div>';
            }
        }
    }

    // 2. ลบรูปภาพ
    elseif ($action == 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];

        $file = $pdo->query("SELECT filepath FROM files WHERE id = $id")->fetchColumn();
        if ($file && file_exists('../uploads/files/' . $file)) {
            @unlink('../uploads/files/' . $file);
        }

        $pdo->prepare("DELETE FROM files WHERE id = ?")->execute([$id]);
        $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-check-circle text-lg"></i> ลบรูปภาพเรียบร้อยแล้ว</div>';
    }
}

// ดึงรูปทั้งหมด (เฉพาะรูปภาพ)
$files = $pdo->query("SELECT * FROM files WHERE file_type IN ('jpg','jpeg','png','gif','webp') ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- หัวข้อของหน้า -->
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-extrabold text-base-content flex items-center gap-2.5">
            <i class="fas fa-folder-open text-primary"></i> คลังรูปภาพและไฟล์สื่อประชาสัมพันธ์
        </h2>
        <p class="text-sm text-base-content/60 mt-1">คลังจัดเก็บไฟล์รูปภาพสำหรับข่าวสาร กิจกรรม หรือนำลิงก์รูปไปใช้ประกอบในหน้าเนื้อหาหลัก</p>
    </div>
</div>

<?php echo $message; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
    <!-- ฟอร์มอัปโหลดรูป (1 ส่วน) -->
    <div class="card bg-base-100 shadow-xl border border-base-200">
        <div class="card-body">
            <h3 class="card-title text-lg font-bold text-base-content flex items-center gap-2 border-b border-base-200 pb-3 mb-2">
                <i class="fas fa-cloud-upload-alt text-primary"></i> อัปโหลดไฟล์สื่อใหม่
            </h3>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="upload">

                <!-- อัปโหลดดีไซน์พรีเมียมแบบลากวางไฟล์ -->
                <div class="form-control">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/85">เลือกไฟล์รูปภาพ</span>
                    </label>
                    
                    <div class="border-2 border-dashed border-base-300 rounded-2xl p-8 text-center cursor-pointer hover:border-primary hover:bg-primary/5 transition duration-200 flex flex-col items-center justify-center min-h-[220px] relative overflow-hidden group select-none" id="uploadArea">
                        <div id="uploadPlaceholder" class="flex flex-col items-center justify-center gap-2">
                            <i class="fas fa-images text-4xl text-base-content/30 group-hover:text-primary group-hover:scale-110 transition duration-200"></i>
                            <span class="text-xs font-bold text-base-content/85 group-hover:text-primary">คลิกหรือลากรูปภาพมาวางที่นี่</span>
                            <span class="text-[10px] text-base-content/50">JPG, PNG, GIF, WebP (สูงสุด 10MB)</span>
                        </div>
                        <!-- พรีวิวก่อนบันทึก -->
                        <img id="imagePreview" src="#" alt="Preview" class="hidden absolute inset-0 w-full h-full object-contain bg-base-200/80 p-2">
                    </div>

                    <input type="file" id="fileInput" name="file" accept="image/*" class="hidden">
                </div>

                <div class="card-actions justify-end pt-2">
                    <button type="submit" class="btn btn-primary w-full gap-2">
                        <i class="fas fa-save"></i> Upload บันทึกรูปภาพ
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- คลังรูปภาพทั้งหมด (2 ส่วน) -->
    <div class="card bg-base-100 shadow-xl border border-base-200 xl:col-span-2 overflow-hidden">
        <div class="p-6 pb-0 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="card-title text-lg font-bold text-base-content flex items-center gap-2">
                    <i class="fas fa-photo-video text-primary"></i> คลังรูปภาพทั้งหมดในระบบ
                </h3>
                <p class="text-xs text-base-content/55 mt-0.5 font-sans">คลังภาพรวมที่อัปโหลดทั้งหมดเพื่อนำไปเขียนเนื้อหาหรือแนบประกอบเพิ่มเติม</p>
            </div>
            <div class="badge badge-neutral font-bold py-3 px-3.5 select-none shrink-0 self-start sm:self-center">
                ทั้งหมด <?php echo count($files); ?> รูป
            </div>
        </div>

        <div class="card-body mt-4">
            <?php if (empty($files)): ?>
                <div class="flex flex-col items-center justify-center text-center p-12 bg-base-200/40 rounded-2xl border border-base-200">
                    <i class="fas fa-file-image text-5xl text-base-content/20 mb-3 animate-pulse"></i>
                    <h4 class="font-bold text-base text-base-content">ยังไม่มีรูปภาพในคลัง</h4>
                    <p class="text-xs text-base-content/50 mt-1 max-w-[280px]">เริ่มต้นโดยการอัปโหลดไฟล์แรกของหน่วยงานด้วยเครื่องมือทางด้านซ้าย</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php foreach ($files as $f): ?>
                        <div class="card bg-base-100 border border-base-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-200 overflow-hidden group">
                            <!-- พรีวิวรูปภาพ -->
                            <figure class="aspect-[4/3] bg-base-300 relative overflow-hidden">
                                <img src="../uploads/files/<?php echo htmlspecialchars($f['filepath']); ?>"
                                     alt="<?php echo htmlspecialchars($f['filename']); ?>"
                                     loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <span class="absolute top-2 left-2 badge badge-neutral bg-black/60 text-[9px] border-none text-white py-2 px-2.5 font-bold">
                                    <?php echo strtoupper(htmlspecialchars($f['file_type'])); ?>
                                </span>
                            </figure>
                            
                            <div class="p-3.5 flex flex-col justify-between flex-1 gap-2">
                                <div class="min-w-0">
                                    <div class="font-bold text-xs text-base-content line-clamp-2 min-h-[32px] break-all leading-relaxed" 
                                         title="<?php echo htmlspecialchars($f['filename']); ?>">
                                        <?php echo htmlspecialchars($f['filename']); ?>
                                    </div>
                                    <div class="text-[10px] text-base-content/50 flex items-center gap-1 mt-1 font-semibold">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($f['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <form method="POST" onsubmit="return confirm('ยืนยันลบรูปภาพนี้หรือไม่?');" class="mt-1">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
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
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const preview = document.getElementById('imagePreview');
    const placeholder = document.getElementById('uploadPlaceholder');

    // คลิกเพื่อเปิดเลือกไฟล์
    uploadArea.addEventListener('click', () => fileInput.click());

    // Preview เมื่อเลือกไฟล์
    fileInput.addEventListener('change', function() {
        if (this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Drag & Drop
    ['dragenter', 'dragover'].forEach(event => {
        uploadArea.addEventListener(event, e => {
            e.preventDefault();
            uploadArea.classList.add('border-primary', 'bg-primary/5');
        });
    });

    ['dragleave', 'drop'].forEach(event => {
        uploadArea.addEventListener(event, e => {
            e.preventDefault();
            uploadArea.classList.remove('border-primary', 'bg-primary/5');
        });
    });

    uploadArea.addEventListener('drop', e => {
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });
</script>
