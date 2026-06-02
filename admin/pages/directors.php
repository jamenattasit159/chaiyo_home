<?php
// admin/pages/directors.php - จัดการผู้บริหารและบุคลากร

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// === การจัดการข้อมูล ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. เพิ่มผู้บริหาร/บุคลากร
    if ($action == 'add') {
        $name = trim($_POST['name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $category = $_POST['category'] ?? 'personnel';
        $image = '';

        if (empty($name) || empty($position)) {
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> กรุณากรอกชื่อและตำแหน่งให้ครบถ้วน</div>';
        } else {
            // อัปโหลดรูปภาพ
            if (!empty($_FILES['image']['name'])) {
                $file = $_FILES['image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($ext, $allowed)) {
                    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $path = '../uploads/directors/' . $filename;

                    if (!is_dir('../uploads/directors')) {
                        mkdir('../uploads/directors', 0755, true);
                    }

                    if (move_uploaded_file($file['tmp_name'], $path)) {
                        $image = $filename;
                    }
                } else {
                    $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> รองรับเฉพาะไฟล์รูปภาพเท่านั้น</div>';
                }
            }

            if (empty($message)) {
                $stmt = $pdo->prepare("INSERT INTO directors (name, position, image, category, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
                $stmt->execute([$name, $position, $image, $category]);
                $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-check-circle text-lg"></i> เพิ่มข้อมูลบุคลากรเรียบร้อยแล้ว</div>';
            }
        }
    }

    // 2. แก้ไขข้อมูล
    elseif ($action == 'update') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $position = trim($_POST['position']);
        $category = $_POST['category'];

        if (empty($name) || empty($position)) {
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> กรุณากรอกข้อมูลให้ครบถ้วน</div>';
        } else {
            if (!empty($_FILES['image']['name'])) {
                $file = $_FILES['image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($ext, $allowed)) {
                    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $path = '../uploads/directors/' . $filename;

                    if (move_uploaded_file($file['tmp_name'], $path)) {
                        // ลบรูปเก่า
                        $old = $pdo->query("SELECT image FROM directors WHERE id = $id")->fetchColumn();
                        if ($old && file_exists('../uploads/directors/' . $old)) {
                            @unlink('../uploads/directors/' . $old);
                        }

                        $stmt = $pdo->prepare("UPDATE directors SET name=?, position=?, image=?, category=?, updated_at=NOW() WHERE id=?");
                        $stmt->execute([$name, $position, $filename, $category, $id]);
                    }
                }
            } else {
                $stmt = $pdo->prepare("UPDATE directors SET name=?, position=?, category=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$name, $position, $category, $id]);
            }

            $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-check-circle text-lg"></i> อัปเดตข้อมูลเรียบร้อยแล้ว</div>';
            unset($_GET['edit']);
        }
    }

    // 3. ลบข้อมูล
    elseif ($action == 'delete') {
        $id = (int)$_POST['id'];

        $img = $pdo->query("SELECT image FROM directors WHERE id = $id")->fetchColumn();
        if ($img && file_exists('../uploads/directors/' . $img)) {
            @unlink('../uploads/directors/' . $img);
        }

        $pdo->prepare("DELETE FROM directors WHERE id = ?")->execute([$id]);
        $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-check-circle text-lg"></i> ลบข้อมูลเรียบร้อยแล้ว</div>';
    }
}

// ดึงข้อมูลทั้งหมด
$directors = $pdo->query("SELECT * FROM directors ORDER BY 
    FIELD(category, 'director', 'personnel'), created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$editDirector = null;
if (!empty($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editDirector = $pdo->query("SELECT * FROM directors WHERE id = $editId")->fetch(PDO::FETCH_ASSOC);
}
?>

<!-- หัวข้อของหน้า -->
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-extrabold text-base-content flex items-center gap-2.5">
            <i class="fas fa-user-tie text-primary"></i> จัดการผู้บริหารและบุคลากร
        </h2>
        <p class="text-sm text-base-content/60 mt-1">บริหารจัดการโครงสร้างและทำเนียบผู้บริหาร ข้อมูลตำแหน่ง และรูปภาพบุคลากรของหน่วยงาน</p>
    </div>
</div>

<?php echo $message; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
    <!-- ฟอร์มเพิ่ม/แก้ไขบุคลากร (1 ส่วน) -->
    <div class="card bg-base-100 shadow-xl border border-base-200">
        <div class="card-body">
            <h3 class="card-title text-lg font-bold text-base-content flex items-center gap-2 border-b border-base-200 pb-3 mb-2">
                <i class="fas <?php echo $editDirector ? 'fa-user-edit text-warning' : 'fa-user-plus text-primary'; ?>"></i>
                <?php echo $editDirector ? 'แก้ไขข้อมูลบุคลากร' : 'เพิ่มบุคลากรใหม่'; ?>
            </h3>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="<?php echo $editDirector ? 'update' : 'add'; ?>">
                <?php if ($editDirector): ?>
                    <input type="hidden" name="id" value="<?php echo $editDirector['id']; ?>">
                <?php endif; ?>

                <!-- ชื่อ - นามสกุล -->
                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/80">ชื่อ - นามสกุล <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($editDirector['name'] ?? ''); ?>" required
                           class="input input-bordered w-full" placeholder="ระบุคำนำหน้าชื่อ และชื่อ-นามสกุล">
                </div>

                <!-- ตำแหน่ง -->
                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/80">ตำแหน่ง <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="position" value="<?php echo htmlspecialchars($editDirector['position'] ?? ''); ?>" required
                           class="input input-bordered w-full" placeholder="ระบุตำแหน่งบริหาร หรือตำแหน่งหน้าที่งาน">
                </div>

                <!-- หมวดหมู่การแสดงผล -->
                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/85">หมวดหมู่การแสดงผล</span>
                    </label>
                    <select name="category" required class="select select-bordered w-full">
                        <option value="director" <?php echo ($editDirector['category'] ?? '') == 'director' ? 'selected' : ''; ?>>
                            ผู้บริหารสูงสุด / ผู้อำนวยการ (แสดงหน้าแรก)
                        </option>
                        <option value="personnel" <?php echo ($editDirector['category'] ?? 'personnel') == 'personnel' ? 'selected' : ''; ?>>
                            บุคลากรทั่วไป (แสดงหน้าบุคลากร)
                        </option>
                    </select>
                </div>

                <!-- รูปถ่าย -->
                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/80">รูปถ่าย <span class="text-xs text-base-content/50 font-normal">(แนะนำขนาด W: 318px H: 278px)</span></span>
                    </label>
                    <input type="file" name="image" accept="image/*" class="file-input file-input-bordered w-full">
                    
                    <?php if ($editDirector && $editDirector['image']): ?>
                        <div class="mt-4 p-3 bg-base-200 rounded-xl flex items-center gap-3.5 border border-base-300">
                            <img src="../uploads/directors/<?php echo htmlspecialchars($editDirector['image']); ?>" 
                                 class="w-14 h-14 object-cover rounded-lg border border-base-300 shadow-sm" alt="Current image">
                            <div>
                                <span class="text-xs font-bold text-base-content/80 block">รูปภาพปัจจุบัน</span>
                                <span class="text-[10px] text-base-content/50 block font-mono"><?php echo htmlspecialchars($editDirector['image']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-actions justify-end pt-2 gap-2">
                    <?php if ($editDirector): ?>
                        <a href="?page=directors" class="btn btn-ghost">ยกเลิก</a>
                        <button type="submit" class="btn btn-success text-white">
                            <i class="fas fa-check-circle"></i> บันทึกการแก้ไข
                        </button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary w-full gap-2">
                            <i class="fas fa-save"></i> บันทึกเพิ่มบุคลากร
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- รายชื่อผู้ดูแลระบบทั้งหมด (2 ส่วน) -->
    <div class="card bg-base-100 shadow-xl border border-base-200 xl:col-span-2 overflow-hidden">
        <div class="p-6 pb-0 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="card-title text-lg font-bold text-base-content flex items-center gap-2">
                    <i class="fas fa-users text-primary"></i> รายชื่อบุคลากรทั้งหมด
                </h3>
                <p class="text-xs text-base-content/55 mt-0.5 font-sans">คุณสามารถคลิกปุ่มแก้ไขเพื่อปรับปรุงข้อมูลหรือรูปถ่ายได้ตลอดเวลา</p>
            </div>
            <div class="badge badge-neutral font-bold py-3 px-3.5 select-none shrink-0 self-start sm:self-center">
                ทั้งหมด <?php echo count($directors); ?> คน
            </div>
        </div>

        <div class="card-body p-2 mt-4">
            <div class="overflow-x-auto w-full">
                <table class="table w-full table-zebra">
                    <thead>
                        <tr class="bg-base-200/50">
                            <th class="w-20 text-center">รูปถ่าย</th>
                            <th>ชื่อ - ตำแหน่ง</th>
                            <th>หมวดหมู่</th>
                            <th class="text-right w-36">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($directors)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-12 text-base-content/50 font-medium">
                                    <i class="fas fa-user-slash text-4xl block mb-2 opacity-30"></i>
                                    ยังไม่มีข้อมูลบุคลากรในระบบ
                                </td>
                            </tr>
                        <?php else: foreach ($directors as $d): ?>
                            <tr class="hover">
                                <td class="text-center">
                                    <?php if ($d['image']): ?>
                                        <div class="avatar shadow-sm border border-base-300 rounded-xl overflow-hidden inline-block">
                                            <div class="w-12 h-12">
                                                <img src="../uploads/directors/<?php echo htmlspecialchars($d['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($d['name']); ?>" class="object-cover">
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="avatar placeholder inline-block">
                                            <div class="bg-neutral text-neutral-content rounded-xl w-12 h-12 font-bold text-sm">
                                                <span><?php echo mb_substr(htmlspecialchars($d['name']), 0, 1, 'UTF-8'); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="font-bold text-base-content text-[15px]"><?php echo htmlspecialchars($d['name']); ?></div>
                                    <div class="text-xs text-base-content/60 mt-1 font-semibold"><?php echo htmlspecialchars($d['position']); ?></div>
                                </td>
                                <td>
                                    <?php if ($d['category'] == 'director'): ?>
                                        <span class="badge badge-warning gap-1 py-2.5 px-3 font-semibold text-xs">
                                            <i class="fas fa-crown"></i> ผู้บริหารสูงสุด
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-info gap-1 py-2.5 px-3 font-semibold text-xs">
                                            <i class="fas fa-user-circle"></i> บุคลากรทั่วไป
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <a href="?page=directors&edit=<?php echo $d['id']; ?>" 
                                           class="btn btn-square btn-sm btn-outline btn-neutral">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <form method="POST" class="inline" onsubmit="return confirm('ยืนยันลบข้อมูลผู้ใช้งาน <?php echo htmlspecialchars($d['name']); ?> หรือไม่?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                            <button type="submit" class="btn btn-square btn-sm btn-error btn-outline">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
