<?php
require_once '../db_connect.php';

// บันทึกข้อมูล
if (isset($_POST['save_exec'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $pos_title = $_POST['position_title'];

    // อัปเดตข้อความ
    $sql = "UPDATE executives SET name='$name', position_title='$pos_title' WHERE id=$id";
    $conn->query($sql);

    // ถ้ามีการอัปโหลดรูปใหม่
    if (!empty($_FILES['image']['name'])) {
        $target = "../uploads/" . time() . "_" . $_FILES['image']['name'];
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $db_path = "uploads/" . basename($target);
            $conn->query("UPDATE executives SET image_path='$db_path' WHERE id=$id");
        }
    }
    echo "<script>alert('บันทึกเรียบร้อย'); window.location='manage_exec.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <title>จัดการผู้บริหาร</title>
</head>

<body class="bg-gray-50 p-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">👤 จัดการข้อมูลผู้บริหาร</h2>
            <a href="index.php" class="btn btn-sm">กลับหน้าหลัก</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php
            $res = $conn->query("SELECT * FROM executives ORDER BY id ASC");
            while ($row = $res->fetch_assoc()):
                $zoneName = ($row['zone'] == 'left') ? "ฝั่งซ้าย (ผอ.)" : "ฝั่งขวา (รอง ผอ.)";
                ?>
                <div class="card bg-white shadow-xl">
                    <div class="card-body">
                        <h3 class="card-title text-blue-600"><?= $zoneName ?></h3>
                        <div class="flex justify-center my-4">
                            <img src="../<?= $row['image_path'] ?>"
                                class="w-32 h-32 object-cover rounded-full border-4 border-gray-200">
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="space-y-3">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">

                            <div class="form-control">
                                <label class="label-text">ชื่อ-นามสกุล</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>"
                                    class="input input-bordered w-full" required />
                            </div>

                            <div class="form-control">
                                <label class="label-text">ตำแหน่ง</label>
                                <input type="text" name="position_title"
                                    value="<?= htmlspecialchars($row['position_title']) ?>"
                                    class="input input-bordered w-full" required />
                            </div>

                            <div class="form-control">
                                <label class="label-text">เปลี่ยนรูปโปรไฟล์ (ถ้ามี)</label>
                                <input type="file" name="image" class="file-input file-input-bordered w-full file-input-sm"
                                    accept="image/*" />
                            </div>

                            <button type="submit" name="save_exec"
                                class="btn btn-primary w-full mt-2">บันทึกการเปลี่ยนแปลง</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>

</html>