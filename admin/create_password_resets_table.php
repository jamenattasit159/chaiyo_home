<?php
// create_password_resets_table.php
// รันไฟล์นี้ครั้งเดียวเพื่อสร้างตาราง password_resets ในฐานข้อมูล
// หลังรันแล้วควรลบไฟล์นี้ออกเพื่อความปลอดภัย
require '../config.php';

$sql = "
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $pdo->exec($sql);
    echo '<div style="font-family:Sarabun,sans-serif;max-width:500px;margin:50px auto;padding:30px;background:#f0fdf4;border:2px solid #86efac;border-radius:12px;text-align:center;">';
    echo '<div style="font-size:3rem;">✅</div>';
    echo '<h2 style="color:#166534;">สร้างตารางสำเร็จ!</h2>';
    echo '<p style="color:#15803d;">ตาราง <strong>password_resets</strong> ถูกสร้างในฐานข้อมูลเรียบร้อยแล้ว</p>';
    echo '<p style="color:#dc2626;font-size:0.85rem;margin-top:20px;">⚠️ กรุณาลบไฟล์นี้ออกหลังจากรันแล้ว เพื่อความปลอดภัย</p>';
    echo '</div>';
} catch (PDOException $e) {
    echo '<div style="font-family:Sarabun,sans-serif;max-width:500px;margin:50px auto;padding:30px;background:#fef2f2;border:2px solid #fca5a5;border-radius:12px;text-align:center;">';
    echo '<div style="font-size:3rem;">❌</div>';
    echo '<h2 style="color:#991b1b;">เกิดข้อผิดพลาด!</h2>';
    echo '<p style="color:#b91c1c;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}
?>
