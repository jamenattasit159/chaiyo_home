<?php
// admin/pages/manage_pages.php

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$popupScript = ''; // ตัวแปรสำหรับเก็บ script popup

// จัดการ Form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. บันทึกหน้าเพจ
    if ($action == 'save') {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $id = $_POST['id'] ?? '';

        $savedId = 0;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE custom_pages SET title=?, content=? WHERE id=?");
            $stmt->execute([$title, $content, $id]);
            $savedId = $id;
            $message = '<div class="alert alert-success gap-2 py-3 px-5 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none"><i class="fa-solid fa-circle-check"></i> <span>บันทึกการแก้ไขเรียบร้อยแล้ว</span></div>';
        } else {
            $stmt = $pdo->prepare("INSERT INTO custom_pages (title, content) VALUES (?, ?)");
            $stmt->execute([$title, $content]);
            $savedId = $pdo->lastInsertId();

            // *** จุดสำคัญ: สร้าง Script Popup ถาม user ***
            $encodedId = encode_id($savedId); // เข้ารหัส ID ก่อนส่ง
            $safeTitle = htmlspecialchars($title, ENT_QUOTES);

            $popupScript = "
                <script>
                    setTimeout(function() {
                        if(confirm('✅ บันทึกหน้าเพจ \"$safeTitle\" เรียบร้อย!\\n\\nต้องการสร้าง \"ปุ่มเมนู\" ทางซ้ายเพื่อลิ้งค์มาหน้านี้เลยไหม?')) {
                            window.location.href = '?page=manage_pages&action=auto_create_btn&page_id=$savedId&title=$safeTitle';
                        }
                    }, 500);
                </script>
            ";

            $message = '<div class="alert alert-success gap-2 py-3 px-5 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none"><i class="fa-solid fa-circle-check"></i> <span>สร้างหน้าเพจใหม่เรียบร้อยแล้ว</span></div>';
        }
    }
    // 2. ลบหน้าเพจ
    elseif ($action == 'delete') {
        $pdo->prepare("DELETE FROM custom_pages WHERE id=?")->execute([$_POST['id']]);
        $message = '<div class="alert alert-success gap-2 py-3 px-5 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none"><i class="fa-solid fa-circle-check"></i> <span>ลบหน้าเพจเรียบร้อยแล้ว</span></div>';
    }
}

// 3. (GET) ฟังก์ชันสร้างปุ่มอัตโนมัติ (ทำงานเมื่อ User กด OK ที่ Popup)
if (isset($_GET['action']) && $_GET['action'] == 'auto_create_btn') {
    $pageId = $_GET['page_id'];
    $btnTitle = $_GET['title'];
    $link = 'page.php?ref=' . encode_id($pageId); // สร้างลิ้งค์แบบเข้ารหัส

    // หา max order เดิม
    $maxOrder = $pdo->query("SELECT MAX(sort_order) FROM sidebar_buttons")->fetchColumn();
    $newOrder = $maxOrder + 1;

    $stmt = $pdo->prepare("INSERT INTO sidebar_buttons (name, link, sort_order) VALUES (?, ?, ?)");
    $stmt->execute([$btnTitle, $link, $newOrder]);

    $message = '<div class="alert alert-success gap-2 py-3 px-5 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none"><i class="fa-solid fa-circle-check"></i> <span>สร้างปุ่มเมนูและผูกลิงก์เรียบร้อยแล้ว!</span></div>';
}

// ดึงข้อมูลแก้ไข
$editData = null;
if (isset($_GET['edit'])) {
    $editData = $pdo->query("SELECT * FROM custom_pages WHERE id=" . intval($_GET['edit']))->fetch();
}

// ดึงรายการทั้งหมด
$pages = $pdo->query("SELECT * FROM custom_pages ORDER BY updated_at DESC")->fetchAll();
?>

<h2 class="text-3xl font-black text-base-content flex items-center gap-2 mb-6">📝 จัดการหน้าเนื้อหา (Custom Pages)</h2>

<?php if ($message): ?>
    <div class="mb-6">
        <?php echo $message; ?>
    </div>
<?php endif; ?>
<?php echo $popupScript; // แสดง Popup ถ้ามีการบันทึกใหม่ ?>

<!-- ฟอร์มสร้าง/แก้ไขหน้าเพจ -->
<div class="card bg-base-100 shadow-xl border border-base-200 mb-8">
    <div class="card-body p-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 border-b border-base-200 pb-4">
            <h3 class="text-xl font-bold text-primary flex items-center gap-2 m-0">
                <i class="fa-solid fa-file-pen"></i> <?php echo $editData ? 'แก้ไขหน้าเพจเนื้อหา' : 'สร้างหน้าเพจเนื้อหาใหม่'; ?>
            </h3>
            <button type="button" onclick="loadTemplate()" class="btn btn-neutral btn-sm rounded-xl gap-2 font-bold bg-base-300 border-none text-base-content hover:bg-base-200">
                <i class="fa-solid fa-wand-magic-sparkles text-primary"></i> โหลดตัวอย่างแม่แบบ (วิสัยทัศน์)
            </button>
        </div>

        <form method="POST" class="flex flex-col gap-6">
            <input type="hidden" name="action" value="save">
            <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>

            <div class="form-control w-full">
                <label class="label"><span class="label-text font-bold text-xs uppercase text-base-content/60">หัวข้อหน้าเพจ *</span></label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($editData['title'] ?? ''); ?>" required placeholder="เช่น ประวัติความเป็นมา หรือ นโยบายหน่วยงาน" class="input input-bordered w-full rounded-xl focus:input-primary" />
            </div>

            <div class="form-control w-full">
                <label class="label"><span class="label-text font-bold text-xs uppercase text-base-content/60">เนื้อหาหน้าเว็บ (HTML / รูปแบบพิเศษ)</span></label>
                <textarea id="contentArea" name="content" rows="16" placeholder="พิมพ์หรือแก้ไขเนื้อหาที่นี่..." class="textarea textarea-bordered w-full rounded-xl focus:textarea-primary min-h-[300px]"><?php echo htmlspecialchars($editData['content'] ?? ''); ?></textarea>
            </div>

            <div class="flex justify-end gap-3 border-t border-base-200 pt-6">
                <?php if ($editData): ?>
                    <a href="?page=manage_pages" class="btn btn-ghost rounded-xl font-bold">ยกเลิก</a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary rounded-xl font-bold px-8 shadow-lg shadow-primary/20 gap-2">
                    <i class="fa-solid fa-save"></i> บันทึกข้อมูลและจัดเก็บหน้าเพจ
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ตารางรายการหน้าเพจ -->
<div class="card bg-base-100 shadow-xl border border-base-200 overflow-hidden">
    <div class="p-8 border-b border-base-200">
        <h3 class="text-xl font-bold text-primary flex items-center gap-2 m-0">
            <i class="fa-solid fa-list-check"></i> รายการหน้าเพจที่สร้างไว้ทั้งหมด (<?php echo count($pages); ?> หน้า)
        </h3>
    </div>

    <?php if (empty($pages)): ?>
        <div class="py-16 text-center flex flex-col items-center gap-4">
            <div class="text-6xl text-base-content/30"><i class="fa-solid fa-file-invoice"></i></div>
            <p class="text-base-content/50 font-medium">ยังไม่มีข้อมูลหน้าเพจเนื้อหาในระบบ<br>เริ่มต้นสร้างเพจแรกของคุณในแบบฟอร์มด้านบน</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto w-full">
            <table class="table table-zebra w-full select-none">
                <thead>
                    <tr class="bg-base-200 text-base-content/70">
                        <th class="font-bold py-4">ID</th>
                        <th class="font-bold py-4">หัวข้อหน้าเพจ</th>
                        <th class="font-bold py-4">ลิงก์อ้างอิงสำหรับใช้งาน</th>
                        <th class="font-bold py-4 text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    <?php foreach ($pages as $p): ?>
                        <?php $encodedLink = 'page.php?ref=' . encode_id($p['id']); ?>
                        <tr class="hover:bg-base-200/30 transition-colors">
                            <td class="py-4 font-bold text-xs"><?php echo $p['id']; ?></td>
                            <td class="py-4 font-bold text-sm text-base-content/90"><?php echo htmlspecialchars($p['title']); ?></td>
                            <td class="py-4">
                                <div class="join w-full max-w-sm shadow-sm">
                                    <input type="text" value="<?php echo $encodedLink; ?>" readonly class="input input-bordered input-xs join-item w-full bg-base-200/50 font-mono text-[11px] text-primary select-all" />
                                    <button type="button" class="btn btn-neutral btn-xs join-item font-bold py-2 px-3 h-auto min-h-0" onclick="navigator.clipboard.writeText('<?php echo $encodedLink; ?>'); alert('คัดลอกลิงก์สำเร็จแล้ว!');">Copy</button>
                                </div>
                            </td>
                            <td class="py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="?page=manage_pages&edit=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm rounded-lg font-bold gap-1">
                                        <i class="fa-solid fa-edit"></i> แก้ไข
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('⚠️ ยืนยันการลบหน้าเพจนี้อย่างถาวร?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="btn btn-error btn-outline btn-sm rounded-lg gap-1 font-bold">
                                            <i class="fa-solid fa-trash-can"></i> ลบ
                                        </button>
                                    </form>
                                    <a href="../<?php echo $encodedLink; ?>" target="_blank" class="btn btn-neutral btn-outline btn-sm rounded-lg gap-1 font-bold">
                                        <i class="fa-solid fa-eye"></i> ดูเพจ
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- TinyMCE 6 Community CDN (100% Free & Open Source) -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
    // เริ่มระบบ TinyMCE
    tinymce.init({
        selector: '#contentArea', // ID ของ Textarea
        height: 520,
        
        // Plugins (เฉพาะชุดที่เป็น Free Open-Source Community ทั้งหมด)
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview', 'anchor',
            'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount', 'emoticons', 'codesample'
        ],

        // Toolbar (เฉพาะเครื่องมือที่เป็นของฟรี ใช้งานได้ 100% ไม่มีโฆษณา/แจ้งเตือนคีย์)
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table codesample | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | emoticons charmap | removeformat preview fullscreen code',

        // ตั้งค่าฟอนต์สารบัญ (Sarabun) ให้แสดงใน Editor
        content_style: "@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap'); body { font-family: 'Sarabun', sans-serif; font-size: 16px; color: #333; }"
    });

    function loadTemplate() {
        const template = `<div style="max-width: 800px; margin: 0 auto; font-family: 'Sarabun', sans-serif;">
    <div style="text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%); border-radius: 15px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <div style="width: 80px; height: 80px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 4px 10px rgba(249, 115, 22, 0.2);">
            <i class="fas fa-eye" style="font-size: 36px; color: #f97316;"></i>
        </div>
        <h2 style="color: #c2410c; margin-bottom: 15px; font-size: 24px;">วิสัยทัศน์ (Vision)</h2>
        <p style="font-size: 20px; font-weight: 500; color: #4b5563; line-height: 1.6; max-width: 600px; margin: 0 auto;">
            "เป็นองค์กรชั้นนำด้านสุขภาพ ที่มุ่งมั่นยกระดับคุณภาพชีวิตประชาชน ด้วยนวัตกรรมและการบริการที่เป็นเลิศ ภายในปี 2570"
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #e5e7eb; border-top: 4px solid #f97316;">
            <h3 style="color: #334155; margin-top: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-bullseye" style="color: #f97316;"></i> พันธกิจ (Mission)
            </h3>
            <ul style="padding-left: 20px; color: #64748b; line-height: 1.8; margin-bottom: 0;">
                <li>พัฒนาระบบบริการสุขภาพให้ได้มาตรฐานสากล</li>
                <li>ส่งเสริมการมีส่วนร่วมของชุมชนในการดูแลสุขภาพ</li>
                <li>บริหารจัดการองค์กรด้วยหลักธรรมาภิบาล</li>
                <li>พัฒนาศักยภาพบุคลากรสู่ความเป็นมืออาชีพ</li>
            </ul>
        </div>
        <div style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #e5e7eb; border-top: 4px solid #3b82f6;">
            <h3 style="color: #334155; margin-top: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-heart" style="color: #3b82f6;"></i> ค่านิยม (Core Values)
            </h3>
            <ul style="list-style: none; padding: 0; color: #64748b; line-height: 1.8; margin-bottom: 0;">
                <li style="margin-bottom: 8px;"><strong style="color: #3b82f6;">S</strong> - Service Mind</li>
                <li style="margin-bottom: 8px;"><strong style="color: #3b82f6;">M</strong> - Mastery</li>
                <li style="margin-bottom: 8px;"><strong style="color: #3b82f6;">A</strong> - Agility</li>
                <li style="margin-bottom: 8px;"><strong style="color: #3b82f6;">R</strong> - Responsibility</li>
                <li><strong style="color: #3b82f6;">T</strong> - Teamwork</li>
            </ul>
        </div>
    </div>
</div>`;

        if (confirm('ต้องการแทนที่เนื้อหาเดิมด้วยตัวอย่างใช่หรือไม่?')) {
            if (typeof tinymce !== 'undefined' && tinymce.get('contentArea')) {
                tinymce.get('contentArea').setContent(template);
            } else {
                document.getElementById('contentArea').value = template;
            }
        }
    }
</script>