<?php require_once '../db_connect.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <title>จัดการสไลด์</title>
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">จัดการรูปภาพสไลด์</h2>
            <a href="index.php" class="btn btn-sm btn-ghost">กลับหน้าหลัก</a>
        </div>

        <div class="bg-green-50 p-6 rounded-lg mb-8 border border-green-200">
            <form action="" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="form-control w-full md:w-1/3">
                    <label class="label"><span class="label-text">เลือกตำแหน่งรูป</span></label>
                    <select name="position" class="select select-bordered w-full">
                        <option value="hero">สไลด์บนสุด (Hero)</option>
                        <option value="center">สไลด์กิจกรรม (Center)</option>
                        <option value="pride">สไลด์ความภูมิใจ (Pride)</option>
                    </select>
                </div>
                <div class="form-control w-full md:w-1/3">
                    <label class="label"><span class="label-text">เลือกไฟล์รูปภาพ</span></label>
                    <input type="file" name="slide_image" class="file-input file-input-bordered w-full" required accept="image/*" />
                </div>
                <button type="submit" name="upload_slide" class="btn btn-success w-full md:w-auto text-white">อัปโหลด</button>
            </form>
        </div>

        <?php
        if (isset($_POST['upload_slide'])) {
            $position = $_POST['position'];
            $target_dir = "../uploads/";
            
            // สร้างชื่อไฟล์ใหม่กันซ้ำ (เช่น time_filename.jpg)
            $filename = time() . "_" . basename($_FILES["slide_image"]["name"]);
            $target_file = $target_dir . $filename;
            
            // อัปโหลดไฟล์จริง
            if (move_uploaded_file($_FILES["slide_image"]["tmp_name"], $target_file)) {
                // บันทึก path ลง Database (เก็บ path ที่จะเรียกใช้จาก index.php)
                $db_path = "uploads/" . $filename;
                $conn->query("INSERT INTO slideshows (position, image_path) VALUES ('$position', '$db_path')");
                echo "<div class='alert alert-success mb-4'>อัปโหลดสำเร็จ!</div>";
            } else {
                echo "<div class='alert alert-error mb-4'>อัปโหลดล้มเหลว ตรวจสอบ permissions โฟลเดอร์</div>";
            }
        }

        // ลบรูป
        if (isset($_GET['del'])) {
            $id = $_GET['del'];
            $img = $_GET['img']; // ชื่อไฟล์เก่าเพื่อตามไปลบทิ้ง
            $conn->query("DELETE FROM slideshows WHERE id=$id");
            if(file_exists("../".$img)) unlink("../".$img); // ลบไฟล์ออกจากเครื่องด้วย
            echo "<script>window.location='manage_slide.php';</script>";
        }
        ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php 
            $sql = "SELECT * FROM slideshows ORDER BY position, id DESC";
            $result = $conn->query($sql);
            while($row = $result->fetch_assoc()): 
            ?>
            <div class="card bg-base-100 shadow border relative group">
                <figure class="h-40 overflow-hidden">
                    <img src="../<?= $row['image_path'] ?>" alt="Slide" class="w-full h-full object-cover" />
                </figure>
                <div class="card-body p-4">
                    <div class="badge badge-outline mb-2"><?= strtoupper($row['position']) ?></div>
                    <a href="?del=<?= $row['id'] ?>&img=<?= $row['image_path'] ?>" 
                       onclick="return confirm('ต้องการลบรูปนี้?')"
                       class="btn btn-sm btn-error w-full">ลบทิ้ง</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>