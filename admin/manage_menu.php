<?php require_once '../db_connect.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <title>จัดการเมนู</title>
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-5xl mx-auto bg-white p-6 rounded-lg shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">จัดการเมนูและปุ่มกด</h2>
            <a href="index.php" class="btn btn-sm btn-ghost">กลับหน้าหลัก</a>
        </div>

        <div class="bg-blue-50 p-4 rounded-lg mb-8 border border-blue-200">
            <form action="save_menu.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="id" id="edit_id" value="">
                
                <div class="form-control">
                    <label class="label"><span class="label-text">ชื่อปุ่ม/เมนู</span></label>
                    <input type="text" name="title" id="edit_title" placeholder="เช่น ตารางแพทย์" class="input input-bordered w-full" required />
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">ลิงก์ (URL)</span></label>
                    <input type="text" name="url" id="edit_url" placeholder="เช่น https://..." class="input input-bordered w-full" />
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">หมวดหมู่ (ตำแหน่ง)</span></label>
                    <select name="category" id="edit_category" class="select select-bordered w-full">
                        <option value="basic_info">ข้อมูลพื้นฐาน (เมนูฝั่งซ้าย)</option>
                        <option value="services">บริการ (เมนูฝั่งขวา)</option>
                    </select>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">ลำดับการแสดงผล (ตัวเลข)</span></label>
                    <input type="number" name="order_index" id="edit_order" value="0" class="input input-bordered w-full" />
                    <span class="text-xs text-gray-500 mt-1">เลขน้อยจะขึ้นก่อน</span>
                </div>

                <div class="col-span-1 md:col-span-2 mt-2">
                    <button type="submit" name="save_menu" class="btn btn-primary w-full">บันทึกข้อมูล</button>
                    <button type="button" onclick="resetForm()" class="btn btn-ghost w-full mt-2 hidden" id="cancel_btn">ยกเลิกแก้ไข</button>
                </div>
            </form>
        </div>

        <h3 class="text-xl font-bold mb-4">รายการเมนูปัจจุบัน</h3>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full border">
                <thead>
                    <tr class="bg-gray-200">
                        <th>ลำดับ</th>
                        <th>ชื่อเมนู</th>
                        <th>หมวดหมู่</th>
                        <th>ลิงก์</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sql = "SELECT * FROM menus ORDER BY category, order_index ASC";
                    $result = $conn->query($sql);
                    while($row = $result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td class="font-bold text-center"><?= $row['order_index'] ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td>
                            <?php if($row['category'] == 'basic_info'): ?>
                                <span class="badge badge-neutral">ฝั่งซ้าย (ข้อมูล)</span>
                            <?php else: ?>
                                <span class="badge badge-primary">ฝั่งขวา (บริการ)</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-xs truncate max-w-[150px]"><?= $row['url'] ?></td>
                        <td>
                            <button onclick='editItem(<?= json_encode($row) ?>)' class="btn btn-sm btn-warning">แก้ไข</button>
                            <a href="save_menu.php?delete=<?= $row['id'] ?>" onclick="return confirm('ยืนยันการลบ?')" class="btn btn-sm btn-error">ลบ</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function editItem(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_url').value = data.url;
            document.getElementById('edit_category').value = data.category;
            document.getElementById('edit_order').value = data.order_index;
            document.getElementById('cancel_btn').classList.remove('hidden');
            window.scrollTo(0, 0);
        }
        function resetForm() {
            document.getElementById('edit_id').value = '';
            document.forms[0].reset();
            document.getElementById('cancel_btn').classList.add('hidden');
        }
    </script>
</body>
</html>