<?php
// admin/pages/banners.php - จัดการแบนเนอร์สไลด์ (Modern & Official 2025)

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// === การจัดการแบนเนอร์ ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    // 1. อัปโหลดแบนเนอร์ใหม่
    if ($_POST['action'] == 'upload' && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            $message = '<div class="alert alert-error gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none"><i class="fa-solid fa-circle-exclamation"></i> <span>รองรับเฉพาะไฟล์รูปภาพเท่านั้น (JPG, PNG, GIF, WebP)</span></div>';
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB
            $message = '<div class="alert alert-error gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none"><i class="fa-solid fa-circle-exclamation"></i> <span>ไฟล์ต้องไม่เกิน 5MB</span></div>';
        } else {
            $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $filepath = '../uploads/banners/' . $filename;

            if (!is_dir('../uploads/banners')) {
                mkdir('../uploads/banners', 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $stmt = $pdo->prepare("INSERT INTO banners (image, status, created_at) VALUES (?, 'active', NOW())");
                $stmt->execute([$filename]);
                $message = '<div class="alert alert-success gap-2 py-3 px-5 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none"><i class="fa-solid fa-circle-check"></i> <span>อัปโหลดแบนเนอร์ใหม่และเปิดใช้งานทันทีสำเร็จแล้ว</span></div>';
            } else {
                $message = '<div class="alert alert-error gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none"><i class="fa-solid fa-circle-exclamation"></i> <span>ไม่สามารถบันทึกไฟล์ได้</span></div>';
            }
        }
    }

    // 2. สลับสถานะ เปิด/ปิด
    elseif ($_POST['action'] == 'toggle_status') {
        $id = (int) $_POST['id'];
        $current = $_POST['current_status'];
        $new_status = ($current == 'active') ? 'inactive' : 'active';

        $stmt = $pdo->prepare("UPDATE banners SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);

        $status_th = $new_status == 'active' ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
        $message = '<div class="alert alert-success gap-2 py-3 px-5 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none"><i class="fa-solid fa-circle-check"></i> <span>เปลี่ยนสถานะเป็น <strong>' . $status_th . '</strong> เรียบร้อย</span></div>';
    }

    // 3. ลบแบนเนอร์
    elseif ($_POST['action'] == 'delete') {
        $id = (int) $_POST['id'];

        $banner = $pdo->query("SELECT image FROM banners WHERE id = $id")->fetchColumn();
        if ($banner && file_exists('../uploads/banners/' . $banner)) {
            @unlink('../uploads/banners/' . $banner);
        }

        $pdo->prepare("DELETE FROM banners WHERE id = ?")->execute([$id]);
        $message = '<div class="alert alert-success gap-2 py-3 px-5 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none"><i class="fa-solid fa-circle-check"></i> <span>ลบแบนเนอร์ออกจากระบบเรียบร้อยแล้ว</span></div>';
    }
}

// ดึงข้อมูลแบนเนอร์ทั้งหมด
$banners = $pdo->query("SELECT * FROM banners ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- หัวข้อของหน้า -->
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-extrabold text-base-content flex items-center gap-2.5">
            <i class="fas fa-images text-primary"></i> จัดการแบนเนอร์สไลด์
        </h2>
        <p class="text-sm text-base-content/60 mt-1">บริหารจัดการภาพแบนเนอร์สไลด์หน้าแรก อัปโหลดรูปภาพใหม่ และควบคุมการแสดงผลของหน้าเว็บไซต์หลัก</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="mb-6">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 gap-8">
    <!-- ฟอร์มอัปโหลด -->
    <div class="card bg-base-100 shadow-xl border border-base-200">
        <div class="card-body p-8">
            <h3 class="text-xl font-bold text-primary flex items-center gap-2 border-b border-base-200 pb-3 mb-6">
                <i class="fa-solid fa-cloud-arrow-up"></i> เพิ่มแบนเนอร์ประชาสัมพันธ์ใหม่
            </h3>
            
            <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                <input type="hidden" name="action" value="upload">

                <div class="form-control w-full">
                    <div class="flex flex-col md:flex-row gap-4 items-center">
                        <input type="file" id="bannerInput" name="image" accept="image/*" class="file-input file-input-bordered file-input-primary w-full max-w-md rounded-xl" required onchange="previewFile(this)" />
                        <button type="submit" class="btn btn-primary rounded-xl font-bold px-8 shadow-lg shadow-primary/20 w-full md:w-auto">
                            <i class="fa-solid fa-arrow-up-from-bracket"></i> อัปโหลดและเปิดใช้งานทันที
                        </button>
                    </div>
                    <label class="label mt-2">
                        <span class="label-text-alt text-base-content/40 flex items-center gap-1"><i class="fa-solid fa-circle-info"></i> ขนาดไฟล์แนะนำไม่เกิน 5MB (รองรับ JPG, PNG, GIF, WebP)</span>
                    </label>
                </div>
                
                <!-- พรีวิวก่อนอัปโหลด -->
                <div class="hidden w-full max-w-xl rounded-2xl overflow-hidden shadow-md border border-base-200 relative aspect-[21/9]" id="previewWrapper">
                    <img id="uploadPreview" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black/40 flex items-end p-4">
                        <span class="text-white font-bold text-sm bg-black/60 px-3 py-1.5 rounded-lg flex items-center gap-1"><i class="fa-solid fa-eye"></i> ตัวอย่างภาพที่จะอัปโหลด</span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- รายการแบนเนอร์ -->
    <div class="card bg-base-100 shadow-xl border border-base-200 overflow-hidden">
        <div class="p-8 border-b border-base-200">
            <h3 class="text-xl font-bold text-primary flex items-center gap-2 m-0">
                <i class="fa-solid fa-images"></i> แบนเนอร์ทั้งหมดในระบบ (<?php echo count($banners); ?> รายการ)
            </h3>
        </div>

        <?php if (empty($banners)): ?>
            <div class="py-16 text-center flex flex-col items-center gap-4">
                <div class="text-6xl text-base-content/30"><i class="fa-solid fa-image-portrait"></i></div>
                <p class="text-base-content/50 font-medium">ยังไม่มีข้อมูลแบนเนอร์ประชาสัมพันธ์ในระบบ<br>เริ่มต้นการอัปโหลดไฟล์แบนเนอร์แรกของคุณด้านบน</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto w-full">
                <table class="table table-zebra w-full select-none">
                    <thead>
                        <tr class="bg-base-200 text-base-content/70">
                            <th class="font-bold py-4">ตัวอย่างภาพแบนเนอร์</th>
                            <th class="font-bold py-4">ชื่อไฟล์แบนเนอร์ / วันที่ลงระบบ</th>
                            <th class="font-bold py-4 text-center">สถานะการแสดงผล</th>
                            <th class="font-bold py-4 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-200">
                        <?php foreach ($banners as $b): ?>
                            <tr class="hover:bg-base-200/30 transition-colors">
                                <td class="py-4">
                                    <div class="w-48 aspect-[21/9] rounded-xl overflow-hidden shadow-sm border border-base-200">
                                        <img src="../uploads/banners/<?php echo htmlspecialchars($b['image']); ?>" alt="Banner" class="w-full h-full object-cover">
                                    </div>
                                </td>
                                <td class="py-4 font-medium text-sm">
                                    <div class="font-bold text-base-content/90 mb-1 truncate max-w-xs" title="<?php echo htmlspecialchars($b['image']); ?>">
                                        <?php echo htmlspecialchars($b['image']); ?>
                                    </div>
                                    <span class="text-xs text-base-content/50 flex items-center gap-1">
                                        <i class="fa-regular fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($b['created_at'])); ?>
                                    </span>
                                </td>
                                <td class="py-4 text-center">
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $b['status']; ?>">
                                        <?php if ($b['status'] == 'active'): ?>
                                            <button type="submit" class="btn btn-success btn-xs gap-1 py-2 px-3 rounded-lg text-white font-bold h-auto min-h-0 shadow-sm shadow-success/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> Active แสดงอยู่
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-neutral btn-xs gap-1 py-2 px-3 rounded-lg text-white font-bold h-auto min-h-0">
                                                <i class="fa-solid fa-eye-slash"></i> Inactive ปิดไว้
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <td class="py-4 text-center">
                                    <form method="POST" onsubmit="return confirm('⚠️ ยืนยันการลบรูปภาพแบนเนอร์นี้อย่างถาวร?');" class="inline-block">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                        <button type="submit" class="btn btn-error btn-outline btn-sm rounded-lg gap-1 font-bold">
                                            <i class="fa-solid fa-trash-can"></i> ลบภาพ
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function previewFile(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('uploadPreview').src = e.target.result;
                document.getElementById('previewWrapper').classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
