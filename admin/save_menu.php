<?php
require_once '../db_connect.php';

// ลบข้อมูล
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM menus WHERE id = $id");
    header("Location: manage_menu.php");
    exit();
}

// เพิ่มหรือแก้ไขข้อมูล
if (isset($_POST['save_menu'])) {
    $title = $_POST['title'];
    $url = $_POST['url'];
    $category = $_POST['category'];
    $order_index = $_POST['order_index'];
    $id = $_POST['id'];

    if ($id) {
        // แก้ไข (Update)
        $sql = "UPDATE menus SET title='$title', url='$url', category='$category', order_index='$order_index' WHERE id=$id";
    } else {
        // เพิ่มใหม่ (Insert)
        $sql = "INSERT INTO menus (title, url, category, order_index) VALUES ('$title', '$url', '$category', '$order_index')";
    }

    if ($conn->query($sql) === TRUE) {
        header("Location: manage_menu.php");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>