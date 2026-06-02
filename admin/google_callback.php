<?php
session_start();
require '../config.php';
require_once '../vendor/autoload.php'; // เรียกใช้ Library Google Client

// ตั้งค่า Google Client
$client = new Google_Client();
$client->setClientId('xxx');
$client->setClientSecret('xxx');
$client->setRedirectUri('http://localhost/chaiyo_home/admin/google_callback.php'); 
$client->addScope('email');
$client->addScope('profile');

// 1. ถ้ากดปุ่มมาจากหน้า Login (ส่งไปหน้าล็อกอินของ Google)
if (isset($_GET['login'])) {
    $authUrl = $client->createAuthUrl();
    header('Location: ' . $authUrl);
    exit;
}

// 2. พอกลับมาจาก Google (ได้รับ Code)
if (isset($_GET['code'])) {
    try {
        // เอา code ไปแลก Token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (!isset($token['error'])) {
            $client->setAccessToken($token['access_token']);

            // ดึงข้อมูลผู้ใช้จาก Google
            $google_oauth = new Google_Service_Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();

            $email = $google_account_info->email;
            $name = $google_account_info->name;

            // ตรวจสอบในฐานข้อมูล
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // --- กรณี 1: มี User อยู่แล้ว ---
                if ($user['status'] === 'inactive') {
                    die("บัญชีของคุณถูกระงับ กรุณาติดต่อผู้ดูแลระบบ");
                }

                // สร้าง Session ชั่วคราว (ยังไม่ให้เข้าใช้งานจริง จนกว่าจะผ่าน 2FA)
                $_SESSION['temp_admin_id'] = $user['id'];

                // ตรวจสอบว่าตั้งค่า 2FA หรือยัง
                if (!empty($user['google_2fa_secret'])) {
                    // ถ้ามี Secret แล้ว -> ไปหน้ายืนยัน OTP
                    header('Location: verify_2fa.php');
                } else {
                    // ถ้ายังไม่มี Secret -> ไปหน้าตั้งค่า
                    header('Location: setup_2fa.php');
                }
                exit;

            } else {
                // --- กรณี 2: สมาชิกใหม่ (Auto-Register) ---
                try {
                    $newUsername = $email;
                    // สร้างรหัสผ่านสุ่ม (เพราะเข้าผ่าน Google)
                    $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

                    // บันทึกลงฐานข้อมูล
                    $insertStmt = $pdo->prepare("INSERT INTO admin_users (username, password, email, status, created_at) VALUES (?, ?, ?, 'active', NOW())");

                    if ($insertStmt->execute([$newUsername, $randomPassword, $email])) {
                        $newUserId = $pdo->lastInsertId();
                        
                        // สมัครเสร็จ -> สร้าง Session ชั่วคราว
                        $_SESSION['temp_admin_id'] = $newUserId;
                        
                        // สมาชิกใหม่ต้องไปตั้งค่า 2FA ก่อนเสมอ
                        header('Location: setup_2fa.php');
                        exit;
                    } else {
                        echo "เกิดข้อผิดพลาดในการสร้างบัญชีใหม่";
                    }
                } catch (Exception $e) {
                    echo "Database Error: " . $e->getMessage();
                }
            }
        } else {
            echo "Google Login Error: " . htmlspecialchars($token['error']);
        }
    } catch (Exception $e) {
        echo 'Google Login Exception: ' . $e->getMessage();
    }
}
?>