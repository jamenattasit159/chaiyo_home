<?php
// admin/pages/announcements.php - จัดการประกาศข่าวสาร

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// === การจัดการประกาศ (เพิ่ม / แก้ไข / ลบ) ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // ลบไฟล์แนบ
    if ($action == 'delete_attachment') {
        $att_id = $_POST['att_id'] ?? 0;
        try {
            $att = $pdo->query("SELECT * FROM announcement_attachments WHERE id = " . (int)$att_id)->fetch(PDO::FETCH_ASSOC);
            if ($att) {
                if (file_exists('../uploads/files/' . $att['file_path'])) {
                    @unlink('../uploads/files/' . $att['file_path']);
                }
                $pdo->prepare("DELETE FROM announcement_attachments WHERE id = ?")->execute([$att_id]);
                // Redirect กลับมาที่หน้าแก้ไขเดิม
                $announce_id = $att['announcement_id'];
                echo "<script>window.location.href='?page=announcements&edit=$announce_id';</script>";
                exit;
            }
        } catch (Exception $e) {
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> ไม่สามารถลบไฟล์แนบได้</div>';
        }
    }

    // เพิ่มประกาศ
    elseif ($action == 'add_announce') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category = $_POST['category'] ?? 'general';
        $status = $_POST['status'] ?? 'active';
        $announce_date = $_POST['announce_date'] ?? date('Y-m-d');
        $created_at = $announce_date . ' ' . date('H:i:s');
        
        if (empty($title) || empty($content)) {
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> กรุณากรอกหัวข้อและเนื้อหาให้ครบถ้วน</div>';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO announcements (title, content, image, category, status, created_at) VALUES (?, ?, '', ?, ?, ?)");
                $stmt->execute([$title, $content, $category, $status, $created_at]);
                $last_id = $pdo->lastInsertId();

                // จัดการไฟล์แนบ (Multiple)
                if (!empty($_FILES['attachments']['name'][0])) {
                    $total_files = count($_FILES['attachments']['name']);
                    
                    for ($i = 0; $i < $total_files; $i++) {
                        $f_name = $_FILES['attachments']['name'][$i];
                        $f_tmp = $_FILES['attachments']['tmp_name'][$i];
                        $f_error = $_FILES['attachments']['error'][$i];

                        if ($f_error === 0) {
                            $ext = strtolower(pathinfo($f_name, PATHINFO_EXTENSION));
                            $allowed = ['pdf','doc','docx','xls','xlsx','ppt','pptx','zip','jpg','jpeg','png','gif'];

                            if (in_array($ext, $allowed)) {
                                $new_filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $i . '.' . $ext;
                                $path = '../uploads/files/' . $new_filename;

                                if (!is_dir('../uploads/files')) mkdir('../uploads/files', 0755, true);

                                if (move_uploaded_file($f_tmp, $path)) {
                                    $stmt_att = $pdo->prepare("INSERT INTO announcement_attachments (announcement_id, file_path, file_name) VALUES (?, ?, ?)");
                                    $stmt_att->execute([$last_id, $new_filename, $f_name]);
                                }
                            }
                        }
                    }
                }

                $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-check-circle text-lg"></i> เพิ่มประกาศเรียบร้อยแล้ว</div>';
                
            } catch (Exception $e) {
                $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }

    // แก้ไขประกาศ
    elseif ($action == 'update_announce') {
        $id = $_POST['id'] ?? 0;
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category = $_POST['category'] ?? 'general';
        $status = $_POST['status'] ?? 'active';
        $announce_date = $_POST['announce_date'] ?? date('Y-m-d');
        $created_at = $announce_date . ' ' . date('H:i:s');

        if (!$id || empty($title) || empty($content)) {
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> ข้อมูลไม่ครบถ้วน</div>';
        } else {
            try {
                // อัปเดตข้อมูลหลัก
                $stmt = $pdo->prepare("UPDATE announcements SET title=?, content=?, category=?, status=?, created_at=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$title, $content, $category, $status, $created_at, $id]);

                // เพิ่มไฟล์แนบใหม่ (Multiple)
                if (!empty($_FILES['attachments']['name'][0])) {
                    $total_files = count($_FILES['attachments']['name']);
                    
                    for ($i = 0; $i < $total_files; $i++) {
                        $f_name = $_FILES['attachments']['name'][$i];
                        $f_tmp = $_FILES['attachments']['tmp_name'][$i];
                        $f_error = $_FILES['attachments']['error'][$i];

                        if ($f_error === 0) {
                            $ext = strtolower(pathinfo($f_name, PATHINFO_EXTENSION));
                            $allowed = ['pdf','doc','docx','xls','xlsx','ppt','pptx','zip','jpg','jpeg','png','gif'];

                            if (in_array($ext, $allowed)) {
                                $new_filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $i . '.' . $ext;
                                $path = '../uploads/files/' . $new_filename;

                                if (!is_dir('../uploads/files')) mkdir('../uploads/files', 0755, true);

                                if (move_uploaded_file($f_tmp, $path)) {
                                    $stmt_att = $pdo->prepare("INSERT INTO announcement_attachments (announcement_id, file_path, file_name) VALUES (?, ?, ?)");
                                    $stmt_att->execute([$id, $new_filename, $f_name]);
                                }
                            }
                        }
                    }
                }

                $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-check-circle text-lg"></i> อัปเดตประกาศเรียบร้อยแล้ว</div>';
                
                // Refresh data if editing
                if (isset($_GET['edit']) && $_GET['edit'] == $id) {
                    $editAnnounce = $pdo->query("SELECT * FROM announcements WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
                }

            } catch (Exception $e) {
                $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> เกิดข้อผิดพลาดในการอัปเดต: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }

    // ลบประกาศ
    elseif ($action == 'delete_announce') {
        $id = $_POST['id'] ?? 0;
        try {
            // ลบไฟล์แนบทั้งหมดก่อน
            $atts = $pdo->query("SELECT * FROM announcement_attachments WHERE announcement_id = " . (int)$id)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($atts as $att) {
                if (file_exists('../uploads/files/' . $att['file_path'])) {
                    @unlink('../uploads/files/' . $att['file_path']);
                }
            }
            $pdo->prepare("DELETE FROM announcement_attachments WHERE announcement_id = ?")->execute([$id]);

            // ลบ announcement
            $img = $pdo->query("SELECT image FROM announcements WHERE id = " . (int)$id)->fetchColumn();
            if ($img && file_exists('../uploads/files/' . $img)) @unlink('../uploads/files/' . $img);

            $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$id]);
            $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-check-circle text-lg"></i> ลบประกาศเรียบร้อยแล้ว</div>';
        } catch (Exception $e) {
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> ไม่สามารถลบได้</div>';
        }
    }
}

// ดึงข้อมูลทั้งหมด
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// โหมดแก้ไข
$editAnnounce = null;
$editAttachments = [];
if (!empty($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editAnnounce = $pdo->query("SELECT * FROM announcements WHERE id = $editId")->fetch(PDO::FETCH_ASSOC);
    // ดึงไฟล์แนบ
    if ($editAnnounce) {
        $editAttachments = $pdo->query("SELECT * FROM announcement_attachments WHERE announcement_id = $editId")->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!-- หัวข้อของหน้า -->
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-extrabold text-base-content flex items-center gap-2.5">
            <i class="fas fa-bullhorn text-primary"></i> จัดการประกาศข่าวสาร
        </h2>
        <p class="text-sm text-base-content/60 mt-1">สร้างข่าวสาร ประชาสัมพันธ์ประกาศผล จัดซื้อจัดจ้าง และเอกสารดาวน์โหลดทั่วไปลงหน้าแรก</p>
    </div>
</div>

<?php echo $message; ?>

<!-- ฟอร์มเพิ่ม/แก้ไขประกาศ -->
<div class="card bg-base-100 shadow-xl border border-base-200 mb-8">
    <div class="card-body">
        <h3 class="card-title text-lg font-bold text-base-content flex items-center gap-2 border-b border-base-200 pb-3 mb-2">
            <i class="fas <?php echo $editAnnounce ? 'fa-edit text-warning' : 'fa-plus text-primary'; ?>"></i>
            <?php echo $editAnnounce ? 'แก้ไขประกาศข่าวสาร' : 'เพิ่มประกาศใหม่'; ?>
        </h3>

        <form method="POST" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="action" value="<?php echo $editAnnounce ? 'update_announce' : 'add_announce'; ?>">
            <?php if ($editAnnounce): ?>
                <input type="hidden" name="id" value="<?php echo $editAnnounce['id']; ?>">
            <?php endif; ?>

            <!-- หัวข้อประกาศ -->
            <div class="form-control w-full">
                <label class="label py-1.5">
                    <span class="label-text font-bold text-base-content/85">หัวข้อประกาศ <span class="text-error">*</span></span>
                </label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($editAnnounce['title'] ?? ''); ?>" required 
                       class="input input-bordered w-full" placeholder="เช่น ประกาศจัดซื้อเวชภัณฑ์ยาปีงบประมาณ 2568, รับสมัครบุคลากรสอบแข่งขัน...">
            </div>

            <!-- แถวกริดตั้งค่าหมวดหมู่ วันที่ และสถานะ -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/80">หมวดหมู่</span>
                    </label>
                    <select name="category" required class="select select-bordered w-full">
                        <option value="general" <?php echo ($editAnnounce['category'] ?? 'general') == 'general' ? 'selected' : ''; ?>>ประกาศทั่วไป / ข่าวประชาสัมพันธ์</option>
                        <option value="download" <?php echo ($editAnnounce['category'] ?? '') == 'download' ? 'selected' : ''; ?>>เอกสารดาวน์โหลด</option>
                        <option value="procurement" <?php echo ($editAnnounce['category'] ?? '') == 'procurement' ? 'selected' : ''; ?>>ข่าวจัดซื้อจัดจ้าง</option>
                        <option value="ita" <?php echo ($editAnnounce['category'] ?? '') == 'ita' ? 'selected' : ''; ?>>ข้อมูลการประเมิน ITA</option>
                    </select>
                </div>

                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/80">วันที่ประกาศ</span>
                    </label>
                    <input type="date" name="announce_date" class="input input-bordered w-full"
                           value="<?php echo $editAnnounce ? date('Y-m-d', strtotime($editAnnounce['created_at'])) : date('Y-m-d'); ?>">
                </div>

                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/80">สถานะ</span>
                    </label>
                    <select name="status" class="select select-bordered w-full">
                        <option value="active" <?php echo ($editAnnounce['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>เปิดใช้งาน (แสดงผลหน้าเว็บ)</option>
                        <option value="inactive" <?php echo ($editAnnounce['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>ปิดใช้งาน (ซ่อนชั่วคราว)</option>
                    </select>
                </div>
            </div>

            <!-- รายละเอียด/เนื้อหาประกาศ -->
            <div class="form-control w-full">
                <label class="label py-1.5">
                    <span class="label-text font-bold text-base-content/85">เนื้อหาประกาศ / รายละเอียดโดยย่อ <span class="text-error">*</span></span>
                </label>
                <textarea name="content" required class="textarea textarea-bordered w-full h-32 leading-relaxed" 
                          placeholder="พิมพ์รายละเอียดประกาศแบบย่อเพื่อให้ผู้อ่านเข้าใจเบื้องต้น..."><?php echo htmlspecialchars($editAnnounce['content'] ?? ''); ?></textarea>
            </div>

            <!-- ระบบการเพิ่มและจัดการไฟล์แนบ -->
            <div class="form-control w-full">
                <label class="label py-1.5">
                    <span class="label-text font-bold text-base-content/80">ไฟล์แนบเพิ่มเติม (สามารถอัปโหลดได้หลายไฟล์พร้อมกัน)</span>
                </label>
                
                <div class="border-2 border-dashed border-base-300 rounded-2xl p-6 text-center cursor-pointer hover:border-primary hover:bg-primary/5 transition duration-200 group flex flex-col items-center justify-center gap-2" 
                     onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-file-pdf text-3xl text-base-content/30 group-hover:text-primary group-hover:scale-110 transition duration-200"></i>
                    <span class="text-xs font-bold text-base-content/80 group-hover:text-primary">คลิกเลือกไฟล์แนบจากคอมพิวเตอร์ของคุณ</span>
                    <span class="text-[10px] text-base-content/50">PDF, Word, Excel, PowerPoint, ZIP หรือรูปภาพทั่วไป</span>
                </div>
                
                <input type="file" id="fileInput" name="attachments[]" multiple class="hidden" 
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.jpg,.jpeg,.png,.gif">
                
                <!-- ไฟล์แนบที่เลือกใหม่ -->
                <div id="fileList" class="space-y-1.5 mt-3"></div>

                <!-- รายการไฟล์แนบปัจจุบัน (โหมดแก้ไข) -->
                <?php if (!empty($editAttachments)): ?>
                    <div class="mt-5 p-4 bg-base-200/50 rounded-2xl border border-base-200 space-y-2">
                        <label class="font-bold text-xs text-base-content/75 block border-b border-base-300 pb-2 mb-1">
                            <i class="fas fa-paperclip"></i> รายการไฟล์แนบปัจจุบันในระบบ (คลิกเพื่อเปิดดู / กดกากบาทเพื่อลบออก)
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <?php foreach ($editAttachments as $att): ?>
                                <div class="bg-base-100 rounded-xl px-3 py-2 flex items-center justify-between border border-base-300 shadow-sm gap-2">
                                    <div class="min-w-0 flex items-center gap-2">
                                        <i class="fas fa-file-alt text-base-content/40 shrink-0 text-sm"></i>
                                        <a href="../uploads/files/<?php echo htmlspecialchars($att['file_path']); ?>" target="_blank"
                                           class="text-xs font-semibold link link-hover link-primary truncate select-all block leading-tight">
                                            <?php echo htmlspecialchars($att['file_name'] ?: $att['file_path']); ?>
                                        </a>
                                    </div>
                                    <button type="submit" form="del_att_<?php echo $att['id']; ?>" 
                                            class="btn btn-square btn-error btn-ghost btn-xs shrink-0 rounded-lg" title="ลบไฟล์แนบนี้">
                                        <i class="fas fa-times text-sm"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ปุ่มบันทึกการส่ง -->
            <div class="card-actions justify-end pt-3 gap-2">
                <?php if ($editAnnounce): ?>
                    <a href="?page=announcements" class="btn btn-ghost">ยกเลิก</a>
                    <button type="submit" class="btn btn-success text-white px-6">
                        <i class="fas fa-check-circle"></i> บันทึกการแก้ไขประกาศ
                    </button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary px-8">
                        <i class="fas fa-plus-circle"></i> บันทึกข้อมูลและประกาศข่าวสาร
                    </button>
                <?php endif; ?>
            </div>
        </form>
        
        <!-- ฟอร์มเสริมสำหรับลบไฟล์แนบด้วย AJAX/Submit เพื่อไม่ให้ปนกับฟอร์มหลัก -->
        <?php if (!empty($editAttachments)): ?>
            <?php foreach ($editAttachments as $att): ?>
                <form id="del_att_<?php echo $att['id']; ?>" method="POST" class="hidden" 
                      onsubmit="return confirm('⚠️ ยืนยันการลบไฟล์แนบ <?php echo htmlspecialchars($att['file_name']); ?> ?');">
                    <input type="hidden" name="action" value="delete_attachment">
                    <input type="hidden" name="att_id" value="<?php echo $att['id']; ?>">
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ตารางรายการประกาศทั้งหมด -->
<div class="card bg-base-100 shadow-xl border border-base-200 overflow-hidden">
    <div class="p-6 pb-0 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h3 class="card-title text-lg font-bold text-base-content flex items-center gap-2">
                <i class="fas fa-list-ul text-primary"></i> รายการประกาศทั้งหมดในระบบ
            </h3>
            <p class="text-xs text-base-content/55 mt-0.5 font-sans">แสดงรายการข่าวสารและประกาศทั้งหมดในระบบตามลำดับเวลาลงประกาศล่าสุด</p>
        </div>
        <div class="badge badge-neutral font-bold py-3 px-3.5 select-none shrink-0 self-start sm:self-center">
            ทั้งหมด <?php echo count($announcements); ?> รายการ
        </div>
    </div>

    <div class="card-body p-2 mt-4">
        <div class="overflow-x-auto w-full">
            <table class="table w-full table-zebra">
                <thead>
                    <tr class="bg-base-200/50">
                        <th class="w-[45%]">หัวข้อประกาศ</th>
                        <th>หมวดหมู่</th>
                        <th>สถานะการใช้งาน</th>
                        <th>ไฟล์แนบ</th>
                        <th>วันที่เผยแพร่</th>
                        <th class="text-right w-36">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($announcements)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-12 text-base-content/50 font-medium">
                                <i class="fas fa-folder-open text-4xl block mb-2 opacity-30"></i>
                                ยังไม่มีข้อมูลข่าวสารประกาศในระบบ
                            </td>
                        </tr>
                    <?php else: foreach ($announcements as $a): ?>
                        <tr class="hover">
                            <td>
                                <strong class="font-bold text-base-content text-[14px] line-clamp-1 block" title="<?php echo htmlspecialchars($a['title']); ?>">
                                    <?php echo htmlspecialchars($a['title']); ?>
                                </strong>
                            </td>
                            <td>
                                <?php
                                $cats = [
                                    'general' => ['label' => 'ประกาศทั่วไป', 'class' => 'bg-slate-500 text-white'],
                                    'download' => ['label' => 'เอกสารดาวน์โหลด', 'class' => 'badge-info text-white'],
                                    'procurement' => ['label' => 'จัดซื้อจัดจ้าง', 'class' => 'badge-warning'],
                                    'ita' => ['label' => 'ITA', 'class' => 'badge-primary']
                                ];
                                $c = $cats[$a['category']] ?? $cats['general'];
                                ?>
                                <span class="badge border-none gap-1 py-2.5 px-3 font-semibold text-xs <?php echo $c['class']; ?>">
                                    <?php echo $c['label']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($a['status'] == 'active'): ?>
                                    <span class="badge badge-success gap-1 py-2.5 px-3 text-white font-semibold text-xs">
                                        <i class="fas fa-check-circle"></i> เปิดใช้งาน
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-ghost gap-1 py-2.5 px-3 font-semibold text-xs opacity-60">
                                        <i class="fas fa-ban text-[10px]"></i> ปิดใช้งาน
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM announcement_attachments WHERE announcement_id = ?");
                                    $stmt_count->execute([$a['id']]);
                                    $count = $stmt_count->fetchColumn();
                                    if ($count > 0): ?>
                                        <span class="badge badge-neutral badge-outline gap-1 font-semibold text-xs py-2 px-2.5">
                                            <i class="fas fa-paperclip"></i> <?php echo $count; ?> ไฟล์
                                        </span>
                                    <?php else: ?>
                                        <span class="text-base-content/30 text-xs font-mono">-</span>
                                    <?php endif; ?>
                            </td>
                            <td class="text-base-content/75 text-xs font-semibold">
                                <?php echo date('d/m/Y', strtotime($a['created_at'])); ?>
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1.5">
                                    <a href="?page=announcements&edit=<?php echo $a['id']; ?>" 
                                       class="btn btn-square btn-sm btn-outline btn-neutral" title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <form method="POST" class="inline" onsubmit="return confirm('⚠️ ยืนยันการลบประกาศหัวข้อนี้หรือไม่? หากลบแล้วไฟล์แนบทั้งหมดที่แนบมากับประกาศนี้จะถูกลบไปด้วย');">
                                        <input type="hidden" name="action" value="delete_announce">
                                        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                        <button type="submit" class="btn btn-square btn-sm btn-error btn-outline" title="ลบ">
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

<script>
    // แสดงไฟล์ที่เลือกใหม่
    document.getElementById('fileInput').addEventListener('change', function(e) {
        const fileList = document.getElementById('fileList');
        fileList.innerHTML = '';
        if (this.files.length > 0) {
            for (let i = 0; i < this.files.length; i++) {
                const item = document.createElement('div');
                item.className = 'flex items-center gap-2 p-2 bg-base-200 border border-base-300 rounded-xl max-w-full text-xs font-semibold text-primary';
                item.innerHTML = '<i class="fas fa-file-upload text-sm"></i> <span>เลือกไฟล์สำเร็จ: <strong class="text-base-content">' + this.files[i].name + '</strong></span>';
                fileList.appendChild(item);
            }
        }
    });
</script>
