<?php
// admin/pages/users.php

// ตรวจสอบสิทธิ์: ต้องเป็น Super Admin เท่านั้น
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    echo '
    <div class="alert alert-error shadow-sm mb-6 flex items-start gap-3">
        <i class="fas fa-exclamation-circle text-lg mt-0.5"></i> 
        <div>
            <strong>Access Denied:</strong> คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (เฉพาะ Super Admin)
        </div>
    </div>';
    return;
}

$message = '';

// จัดการ Form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. เพิ่มผู้ใช้ใหม่
    if ($action == 'add') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $email = trim($_POST['email']);
        $role = $_POST['role'];

        // เช็คว่า username ซ้ำไหม
        $check = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
        $check->execute([$username]);

        if ($check->rowCount() > 0) {
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle"></i> ชื่อผู้ใช้นี้มีอยู่แล้ว</div>';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, email, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");

            if ($stmt->execute([$username, $hash, $email, $role])) {
                $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-check-circle"></i> เพิ่มผู้ใช้เรียบร้อย</div>';
            } else {
                $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle"></i> เกิดข้อผิดพลาด</div>';
            }
        }
    }

    // 2. ลบผู้ใช้
    elseif ($action == 'delete') {
        $id = $_POST['id'];
        if ($id == $_SESSION['admin_id']) {
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-ban"></i> ไม่สามารถลบบัญชีตัวเองได้</div>';
        } else {
            $pdo->prepare("DELETE FROM admin_users WHERE id = ?")->execute([$id]);
            $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-trash-alt"></i> ลบผู้ใช้เรียบร้อย</div>';
        }
    }

    // 3. แก้ไขข้อมูล / เปลี่ยนรหัสผ่าน (Reset Password)
    elseif ($action == 'edit') {
        $id = $_POST['id'];
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $status = $_POST['status']; // รับค่าสถานะ
        $new_pass = $_POST['password']; // รับค่ารหัสผ่านใหม่

        // กรณีเปลี่ยนรหัสผ่านด้วย
        if (!empty($new_pass)) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $sql = "UPDATE admin_users SET email = ?, role = ?, status = ?, password = ? WHERE id = ?";
            $params = [$email, $role, $status, $hash, $id];
            $msg_text = "อัปเดตข้อมูลและเปลี่ยนรหัสผ่านเรียบร้อย";
        }
        // กรณีไม่เปลี่ยนรหัสผ่าน (อัปเดตแค่ข้อมูล)
        else {
            $sql = "UPDATE admin_users SET email = ?, role = ?, status = ? WHERE id = ?";
            $params = [$email, $role, $status, $id];
            $msg_text = "อัปเดตข้อมูลเรียบร้อย";
        }

        if ($pdo->prepare($sql)->execute($params)) {
            $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-save"></i> ' . $msg_text . '</div>';
        } else {
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle"></i> เกิดข้อผิดพลาดในการบันทึก</div>';
        }
    }

    // 4. Toggle Status (เปิด/ปิดการใช้งาน)
    elseif ($action == 'toggle_status') {
        $id = $_POST['id'];
        $current_status = $_POST['current_status'];
        $new_status = ($current_status == 'active') ? 'inactive' : 'active';
        
        if ($id == $_SESSION['admin_id']) {
            $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-ban"></i> ไม่สามารถระงับบัญชีตัวเองได้</div>';
        } else {
            $pdo->prepare("UPDATE admin_users SET status = ? WHERE id = ?")->execute([$new_status, $id]);
            $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-sync"></i> อัปเดตสถานะเรียบร้อย</div>';
        }
    }
}

// ดึงข้อมูลผู้ใช้ทั้งหมด
$users = $pdo->query("SELECT * FROM admin_users ORDER BY created_at DESC")->fetchAll();
?>

<!-- หัวข้อของหน้า -->
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-extrabold text-base-content flex items-center gap-2.5">
            <i class="fas fa-users-cog text-primary"></i> จัดการผู้ใช้งาน (Super Admin)
        </h2>
        <p class="text-sm text-base-content/60 mt-1">บริหารจัดการบัญชีผู้ดูแลระบบ กำหนดบทบาท และเปิด/ปิดสิทธิ์การใช้งาน</p>
    </div>
</div>

<?php echo $message; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
    <!-- ฟอร์มเพิ่มผู้ใช้ใหม่ (1 ส่วนใน Desktop) -->
    <div class="card bg-base-100 shadow-xl border border-base-200">
        <div class="card-body">
            <h3 class="card-title text-lg font-bold text-base-content flex items-center gap-2 border-b border-base-200 pb-3 mb-2">
                <i class="fas fa-user-plus text-primary"></i> เพิ่มผู้ดูแลระบบใหม่
            </h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add">
                
                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/80">Username <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="username" class="input input-bordered w-full" required 
                           placeholder="ชื่อเข้าระบบ (ภาษาอังกฤษ)" pattern="[a-zA-Z0-9_]+" title="ภาษาอังกฤษและตัวเลขเท่านั้น">
                </div>
                
                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/80">Password <span class="text-error">*</span></span>
                    </label>
                    <input type="password" name="password" class="input input-bordered w-full" required 
                           placeholder="รหัสผ่านสำหรับเข้าสู่ระบบ">
                </div>
                
                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/80">Email <span class="text-error">*</span></span>
                    </label>
                    <input type="email" name="email" class="input input-bordered w-full" required 
                           placeholder="admin@example.com">
                </div>
                
                <div class="form-control w-full">
                    <label class="label py-1.5">
                        <span class="label-text font-bold text-base-content/80">สิทธิ์การใช้งาน (Role)</span>
                    </label>
                    <select name="role" class="select select-bordered w-full">
                        <option value="admin">Admin ทั่วไป (จัดการเนื้อหา)</option>
                        <option value="super_admin">Super Admin (จัดการทุกอย่าง)</option>
                    </select>
                </div>
                
                <div class="card-actions justify-end pt-2">
                    <button type="submit" class="btn btn-primary w-full gap-2">
                        <i class="fas fa-save"></i> บันทึกผู้ใช้ใหม่
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ตารางผู้ดูแลระบบทั้งหมด (2 ส่วนใน Desktop) -->
    <div class="card bg-base-100 shadow-xl border border-base-200 xl:col-span-2 overflow-hidden">
        <div class="p-6 pb-0">
            <h3 class="card-title text-lg font-bold text-base-content flex items-center gap-2">
                <i class="fas fa-list-ul text-primary"></i> รายชื่อผู้ดูแลระบบทั้งหมด
            </h3>
            <p class="text-xs text-base-content/55 mt-0.5">คุณสามารถเปิด/ปิดสถานะ หรือคลิกแก้ไขเพื่อรีเซ็ตรหัสผ่านได้</p>
        </div>
        
        <div class="card-body p-2 mt-4">
            <div class="overflow-x-auto w-full">
                <table class="table w-full table-zebra">
                    <thead>
                        <tr class="bg-base-200/50">
                            <th>Username</th>
                            <th>Email</th>
                            <th>สิทธิ์ (Role)</th>
                            <th>สถานะ (Status)</th>
                            <th class="text-right">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr class="hover">
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-base-content">
                                            <?php echo htmlspecialchars($u['username']); ?>
                                        </span>
                                        <?php if ($u['id'] == $_SESSION['admin_id']): ?>
                                            <span class="badge badge-primary badge-sm font-bold text-white">คุณ</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-base-content/75"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <?php if ($u['role'] == 'super_admin'): ?>
                                        <span class="badge badge-warning gap-1 py-2.5 px-3 font-semibold text-xs">
                                            <i class="fas fa-crown"></i> Super Admin
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-info gap-1 py-2.5 px-3 font-semibold text-xs">
                                            <i class="fas fa-user"></i> Admin
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $u['status']; ?>">
                                        <button type="submit" class="tooltip tooltip-right" data-tip="คลิกเพื่อเปลี่ยนสถานะ">
                                            <?php if ($u['status'] == 'active'): ?>
                                                <span class="badge badge-success gap-1 py-2.5 px-3 text-white font-semibold text-xs">
                                                    <i class="fas fa-toggle-on text-sm"></i> ใช้งานปกติ
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-error gap-1 py-2.5 px-3 text-white font-semibold text-xs">
                                                    <i class="fas fa-toggle-off text-sm"></i> ถูกระงับ
                                                </span>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <button type="button" class="btn btn-square btn-sm btn-outline btn-neutral"
                                                onclick="toggleEdit('<?php echo $u['id']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <?php if ($u['id'] != $_SESSION['admin_id']): ?>
                                            <form method="POST" class="inline"
                                                  onsubmit="return confirm('⚠️ ยืนยันการลบผู้ใช้ <?php echo htmlspecialchars($u['username']); ?> ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn btn-square btn-sm btn-error btn-outline">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <!-- แถวแก้ไขแบบ Inline (ขยายออกเมื่อกดแก้ไข) -->
                            <tr id="edit-row-<?php echo $u['id']; ?>" class="hidden transition-all duration-300 bg-base-200/40">
                                <td colspan="6" class="p-4">
                                    <div class="card bg-base-100 shadow-inner border border-base-300 p-4">
                                        <form method="POST" class="space-y-4">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">

                                            <div class="text-sm font-bold text-warning flex items-center gap-1.5 mb-2">
                                                <i class="fas fa-pen-square"></i> แก้ไขข้อมูลผู้ดูแลระบบ:
                                                <span class="text-base-content"><?php echo htmlspecialchars($u['username']); ?></span>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="form-control">
                                                    <label class="label py-1">
                                                        <span class="label-text font-semibold text-xs">อีเมล</span>
                                                    </label>
                                                    <input type="email" name="email" value="<?php echo htmlspecialchars($u['email']); ?>" 
                                                           class="input input-bordered input-sm w-full" required>
                                                </div>
                                                <div class="form-control">
                                                    <label class="label py-1">
                                                        <span class="label-text font-semibold text-xs text-warning-content">🔑 รหัสผ่านใหม่ (เว้นว่างหากไม่ต้องการเปลี่ยน)</span>
                                                    </label>
                                                    <input type="password" name="password" placeholder="ระบุรหัสผ่านใหม่..." 
                                                           class="input input-bordered input-sm w-full border-warning/50 focus:border-warning">
                                                </div>
                                                <div class="form-control">
                                                    <label class="label py-1">
                                                        <span class="label-text font-semibold text-xs">สิทธิ์การใช้งาน</span>
                                                    </label>
                                                    <select name="role" class="select select-bordered select-sm w-full">
                                                        <option value="admin" <?php echo $u['role'] == 'admin' ? 'selected' : ''; ?>>Admin ทั่วไป</option>
                                                        <option value="super_admin" <?php echo $u['role'] == 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                                    </select>
                                                </div>
                                                <div class="form-control">
                                                    <label class="label py-1">
                                                        <span class="label-text font-semibold text-xs">สถานะ</span>
                                                    </label>
                                                    <select name="status" class="select select-bordered select-sm w-full">
                                                        <option value="active" <?php echo ($u['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>ใช้งานปกติ</option>
                                                        <option value="inactive" <?php echo ($u['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>ถูกระงับ</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="flex justify-end gap-2 pt-2 border-t border-base-200 mt-2">
                                                <button type="button" class="btn btn-sm btn-ghost" onclick="toggleEdit('<?php echo $u['id']; ?>')">
                                                    ยกเลิก
                                                </button>
                                                <button type="submit" class="btn btn-sm btn-success text-white">
                                                    บันทึกการเปลี่ยนแปลง
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleEdit(id) {
        var row = document.getElementById('edit-row-' + id);
        if (row.classList.contains('hidden')) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    }
</script>
