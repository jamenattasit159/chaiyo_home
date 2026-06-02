<?php
// config.php - ตั้งค่าฐานข้อมูล

// ตั้งค่า Timezone
date_default_timezone_set('Asia/Bangkok');

// ตั้งค่าฐานข้อมูล
$host = 'localhost';
$db = 'hospital_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// DSN
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
}

// ฟังก์ชันช่วยเหลือ
function sanitize($data)
{
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

function base_url($path = '')
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base = "$protocol://$host" . dirname($_SERVER['PHP_SELF']);
    return $base . '/' . ltrim($path, '/');
}
// --- เพิ่มต่อท้ายไฟล์ config.php ---

// ฟังก์ชันแปลง ID เป็นรหัส (เช่น 1 -> MjE=)
function encode_id($id)
{
    // ใช้ base64 ผสมกับตัวเลขสุ่มเล็กน้อยเพื่อไม่ให้เดาง่ายเกินไป
    return rtrim(base64_encode(($id * 123456789)), '=');
}

// ฟังก์ชันแปลงรหัสกลับเป็น ID
function decode_id($encoded)
{
    $id = base64_decode($encoded);
    return ($id / 123456789);
}

// ฟังก์ชันดึงค่าสีและธีมมาใช้แสดงผล
function get_theme_style_block($pdo)
{
    $theme_primary = '#059669';
    $theme_secondary = '#047857';
    $theme_accent = '#10b981';
    
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'theme_%'");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (isset($settings['theme_primary'])) {
            $theme_primary = $settings['theme_primary'];
        }
        if (isset($settings['theme_secondary'])) {
            $theme_secondary = $settings['theme_secondary'];
        }
        if (isset($settings['theme_accent'])) {
            $theme_accent = $settings['theme_accent'];
        }
    } catch (Exception $e) {
        // กรณีตารางยังไม่มีข้อมูล หรือไม่มีตาราง
    }

    return "
    <style>
        :root {
            --primary: {$theme_primary};
            --primary-hover: {$theme_secondary};
            --secondary: {$theme_secondary};
            --accent: {$theme_accent};
            --light-green: color-mix(in srgb, var(--primary) 15%, white);
            --lighter-green: color-mix(in srgb, var(--primary) 5%, white);
            --light: color-mix(in srgb, var(--primary) 5%, white);
        }
    </style>
    ";
}
?>

