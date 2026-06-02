<?php
// admin/pages/buttons.php - จัดการเมนู/ปุ่ม Sidebar

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// === การจัดการปุ่ม ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // เพิ่มปุ่มใหม่
    if ($action == 'add') {
        $name = trim($_POST['name'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $order = (int) ($_POST['sort_order'] ?? 0);

        if ($name && $link) {
            $stmt = $pdo->prepare("INSERT INTO sidebar_buttons (name, link, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$name, $link, $order]);
            $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-check-circle text-lg"></i> เพิ่มปุ่มใหม่เรียบร้อยแล้ว</div>';
        } else {
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> กรุณากรอกชื่อปุ่มและลิงก์ให้ครบถ้วน</div>';
        }
    }

    // ลบปุ่ม
    elseif ($action == 'delete') {
        $id = (int) $_POST['id'];
        $pdo->prepare("DELETE FROM sidebar_buttons WHERE id = ?")->execute([$id]);
        $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-check-circle text-lg"></i> ลบปุ่มเรียบร้อยแล้ว</div>';
    }

    // บันทึกลำดับใหม่ (Drag & Drop)
    elseif ($action == 'reorder') {
        $order = json_decode($_POST['order'], true);
        if (is_array($order)) {
            $pdo->beginTransaction();
            foreach ($order as $index => $id) {
                $pdo->prepare("UPDATE sidebar_buttons SET sort_order = ? WHERE id = ?")
                    ->execute([$index + 1, $id]);
            }
            $pdo->commit();
            exit(json_encode(['success' => true]));
        }
    }
}

// ดึงข้อมูลปุ่มทั้งหมด
$buttons = $pdo->query("SELECT * FROM sidebar_buttons ORDER BY sort_order ASC, created_at DESC")
    ->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- หัวข้อของหน้า -->
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-extrabold text-base-content flex items-center gap-2.5">
            <i class="fas fa-link text-primary"></i> จัดการเมนูและปุ่มทางลัด (Sidebar)
        </h2>
        <p class="text-sm text-base-content/60 mt-1">บริหารจัดการ ลิงก์ปุ่มด่วนด้านข้างของเว็บไซต์หลัก และลากเพื่อจัดเรียงลำดับได้อย่างง่ายดาย</p>
    </div>
</div>

<?php echo $message; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
    <!-- ฟอร์มเพิ่มปุ่มใหม่ (1 ส่วน) -->
    <div class="card bg-base-100 shadow-xl border border-base-200">
        <div class="card-body">
            <h3 class="card-title text-lg font-bold text-base-content flex items-center gap-2 border-b border-base-200 pb-3 mb-2">
                <i class="fas fa-plus-circle text-primary"></i> เพิ่มปุ่มทางลัดใหม่
            </h3>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add">
                
                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/85">ข้อความบนปุ่ม <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" required placeholder="เช่น วิสัยทัศน์หน่วยงาน, ITA 2568"
                           class="input input-bordered w-full">
                </div>

                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/85">ลิงก์ปลายทาง <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="link" required placeholder="เช่น pages.php?id=1 หรือ https://..."
                           class="input input-bordered w-full">
                </div>

                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/85">ลำดับการจัดเรียง <span class="text-xs text-base-content/50 font-normal">(ตัวเลขน้อยจะขึ้นก่อน)</span></span>
                    </label>
                    <input type="number" name="sort_order" value="0" min="0"
                           class="input input-bordered w-full">
                </div>

                <div class="card-actions justify-end pt-2">
                    <button type="submit" class="btn btn-primary w-full gap-2">
                        <i class="fas fa-save"></i> บันทึกเพิ่มปุ่มทางลัด
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- รายการปุ่มทั้งหมด (2 ส่วน) -->
    <div class="card bg-base-100 shadow-xl border border-base-200 xl:col-span-2 overflow-hidden">
        <div class="p-6 pb-0 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="card-title text-lg font-bold text-base-content flex items-center gap-2">
                    <i class="fas fa-bars text-primary"></i> ลำดับปุ่มทั้งหมดในระบบ
                </h3>
                <p class="text-xs text-base-content/55 mt-0.5 font-sans">↕ ลากแถบปุ่มค้างไว้เพื่อเปลี่ยนลำดับ แล้วปล่อยเพื่อบันทึกโดยอัตโนมัติ</p>
            </div>
            <div class="badge badge-neutral font-bold py-3 px-3.5 select-none shrink-0 self-start sm:self-center">
                ทั้งหมด <?php echo count($buttons); ?> รายการ
            </div>
        </div>

        <div class="card-body p-2 mt-4">
            <?php if (empty($buttons)): ?>
                <div class="flex flex-col items-center justify-center text-center p-12 bg-base-200/40 rounded-2xl border border-base-200 m-4">
                    <i class="fas fa-link text-5xl text-base-content/20 mb-3 animate-pulse"></i>
                    <h4 class="font-bold text-base text-base-content">ยังไม่มีปุ่มทางลัดในระบบ</h4>
                    <p class="text-xs text-base-content/55 mt-1 max-w-[280px]">เริ่มต้นโดยสร้างปุ่มและกำหนดหน้าเนื้อหาด้วยแบบฟอร์มด้านซ้าย</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto w-full">
                    <table class="table w-full">
                        <thead>
                            <tr class="bg-base-200/50">
                                <th class="w-12 text-center">ย้าย</th>
                                <th>ชื่อปุ่มทางลัด</th>
                                <th>ลิงก์ปลายทาง</th>
                                <th class="text-center w-36">ตัวอย่างปุ่ม</th>
                                <th class="text-right w-24">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="sortable">
                            <?php foreach ($buttons as $btn): ?>
                                <tr data-id="<?php echo $btn['id']; ?>" class="hover transition-all duration-200">
                                    <td class="text-center">
                                        <span class="handle cursor-grab active:cursor-grabbing text-base-content/30 hover:text-primary transition duration-150 inline-block px-2 py-1">
                                            <i class="fas fa-grip-lines text-lg"></i>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="font-bold text-base-content text-[15px]"><?php echo htmlspecialchars($btn['name']); ?></strong>
                                    </td>
                                    <td>
                                        <code class="text-xs bg-base-200 px-2.5 py-1.5 rounded-lg text-base-content/75 font-mono select-all block max-w-xs truncate" title="<?php echo htmlspecialchars($btn['link']); ?>">
                                            <?php echo htmlspecialchars($btn['link']); ?>
                                        </code>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo htmlspecialchars($btn['link']); ?>" target="_blank" 
                                           class="btn btn-xs btn-outline btn-primary rounded-lg font-bold gap-1 px-3">
                                            <i class="fas fa-external-link-alt text-[9px]"></i> <?php echo htmlspecialchars($btn['name']); ?>
                                        </a>
                                    </td>
                                    <td class="text-right">
                                        <form method="POST" class="inline" onsubmit="return confirm('⚠️ ยืนยันการลบปุ่มทางลัดนี้หรือไม่?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $btn['id']; ?>">
                                            <button type="submit" class="btn btn-square btn-sm btn-error btn-outline">
                                                <i class="fas fa-trash"></i>
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
</div>

<!-- Sortable.js สำหรับ Drag & Drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    // เปิดใช้งาน Drag & Drop
    const el = document.getElementById('sortable');
    if (el) {
        new Sortable(el, {
            animation: 180,
            ghostClass: 'bg-primary/10',
            handle: '.handle',
            onEnd: function () {
                const order = [];
                document.querySelectorAll('#sortable tr').forEach(row => {
                    order.push(row.dataset.id);
                });

                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=reorder&order=' + JSON.stringify(order)
                });
            }
        });
    }
</script>
