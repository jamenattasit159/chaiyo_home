<?php 
require_once '../db_connect.php'; 

// ลบข่าว
if(isset($_GET['del'])) {
    $id = $_GET['del'];
    $conn->query("DELETE FROM news WHERE id=$id");
    header("Location: manage_news.php");
}

// เพิ่มข่าว
if(isset($_POST['add_news'])) {
    $title = $_POST['title'];
    $desc = $_POST['description']; // เก็บไว้เผื่อใช้ แต่หน้าเว็บย่ออาจไม่ได้แสดงทั้งหมด
    $color = $_POST['color'];
    $date = $_POST['date'];
    $file_link = "";

    // อัปโหลดไฟล์ PDF/Doc
    if(!empty($_FILES['doc_file']['name'])) {
        $target = "../uploads/docs_" . time() . "_" . $_FILES['doc_file']['name'];
        if(move_uploaded_file($_FILES['doc_file']['tmp_name'], $target)) {
            $file_link = "uploads/" . basename($target);
        }
    }

    $sql = "INSERT INTO news (title, description, category_color, publish_date, file_link) VALUES ('$title', '$desc', '$color', '$date', '$file_link')";
    $conn->query($sql);
    header("Location: manage_news.php");
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <title>จัดการข่าวประชาสัมพันธ์</title>
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">📰 จัดการข่าวและประกาศ</h2>
            <a href="index.php" class="btn btn-sm">กลับหน้าหลัก</a>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h3 class="font-bold text-lg mb-4">เพิ่มประกาศใหม่</h3>
            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control md:col-span-2">
                    <label class="label-text">หัวข้อข่าว</label>
                    <input type="text" name="title" class="input input-bordered w-full" required placeholder="เช่น ประกาศรับสมัครงาน..." />
                </div>
                
                <div class="form-control">
                    <label class="label-text">วันที่ประกาศ</label>
                    <input type="date" name="date" class="input input-bordered w-full" value="<?= date('Y-m-d') ?>" required />
                </div>

                <div class="form-control">
                    <label class="label-text">สีธีม (ขอบซ้าย)</label>
                    <select name="color" class="select select-bordered w-full">
                        <option value="blue">สีฟ้า (ทั่วไป)</option>
                        <option value="green">สีเขียว (อบรม/กิจกรรม)</option>
                        <option value="orange">สีส้ม (ด่วน/สำคัญ)</option>
                        <option value="red">สีแดง (แจ้งเตือน)</option>
                    </select>
                </div>

                <div class="form-control">
                    <label class="label-text">ไฟล์แนบ (PDF/รูปภาพ)</label>
                    <input type="file" name="doc_file" class="file-input file-input-bordered w-full" />
                </div>

                <div class="form-control hidden">
                    <textarea name="description" class="textarea textarea-bordered">รายละเอียดเพิ่มเติม</textarea>
                </div>

                <div class="md:col-span-2 mt-2">
                    <button type="submit" name="add_news" class="btn btn-success text-white w-full">ประกาศข่าว</button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="table table-zebra">
                <thead>
                    <tr class="bg-gray-200">
                        <th>วันที่</th>
                        <th>หัวข้อ</th>
                        <th>สี</th>
                        <th>ไฟล์แนบ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $res = $conn->query("SELECT * FROM news ORDER BY publish_date DESC");
                    while($row = $res->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($row['publish_date'])) ?></td>
                        <td><?= $row['title'] ?></td>
                        <td><div class="w-4 h-4 rounded-full bg-<?= $row['category_color'] ?>-500"></div></td>
                        <td>
                            <?php if($row['file_link']): ?>
                                <a href="../<?= $row['file_link'] ?>" target="_blank" class="badge badge-outline">ดูไฟล์</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?del=<?= $row['id'] ?>" onclick="return confirm('ยืนยันลบ?')" class="btn btn-xs btn-error text-white">ลบ</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>