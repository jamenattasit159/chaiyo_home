<?php
session_start();
require '../config.php';
require '../vendor/autoload.php';

// เช็คว่าผ่านการล็อกอินขั้นแรกมาหรือยัง (ต้องมี temp_admin_id)
if (!isset($_SESSION['temp_admin_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = trim($_POST['code'] ?? '');

    if (empty($code)) {
        $error = 'กรุณากรอกรหัส 6 หลัก';
    } else {
        try {
            // ดึงข้อมูล User และ Secret จาก Database
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
            $stmt->execute([$_SESSION['temp_admin_id']]);
            $user = $stmt->fetch();

            if ($user && !empty($user['google_2fa_secret'])) {
                $g = new \Google\Authenticator\GoogleAuthenticator();

                // ตรวจสอบรหัส (อนุญาตให้เวลาคลาดเคลื่อนได้เล็กน้อย)
                if ($g->checkCode($user['google_2fa_secret'], $code)) {

                    // --- ผ่าน! เลื่อนขั้นจาก Temp เป็น Admin เต็มตัว ---
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role']; // ถ้ามี role

                    // ล้าง Session ชั่วคราว
                    unset($_SESSION['temp_admin_id']);

                    // เข้าสู่ระบบสำเร็จ -> ไปหน้าหลัก
                    header('Location: index.php');
                    exit;

                } else {
                    $error = 'รหัสไม่ถูกต้อง หรือหมดอายุแล้ว';
                }
            } else {
                // กรณี User นี้ไม่มี Secret (ผิดปกติ ถ้าเข้ามาหน้านี้ได้)
                header('Location: login.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="winter">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันตัวตน 2FA - SSO Admin</title>
    <!-- โหลดฟอนต์ภาษาไทยและอังกฤษพรีเมียม -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Sarabun:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
    <!-- FontAwesome สำหรับไอคอนสวยๆ -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- daisyUI 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5.5.20/daisyui.min.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5.5.20/themes.css" rel="stylesheet" type="text/css" />
    
    <!-- Tailwind CSS 4 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <style type="text/tailwindcss">
      @theme {
        --font-sans: 'Sarabun', 'Outfit', sans-serif;
      }
    </style>
</head>

<body class="bg-gradient-to-tr from-primary/80 to-secondary/90 min-h-screen flex items-center justify-center p-4 font-sans relative overflow-hidden">
    <!-- หน้าจอโหลดดิ้งพรีเมียม (Page Loading Overlay) -->
    <div id="page-loader" class="fixed inset-0 z-[9999] bg-base-100 flex flex-col items-center justify-center gap-4 transition-all duration-500 ease-out opacity-100 visible">
        <div class="flex flex-col items-center gap-3">
            <span class="loading loading-ring loading-lg text-primary scale-125"></span>
            <div class="flex items-center gap-2">
                <span class="text-3xl">🛡️</span>
                <span class="font-black text-xl text-primary tracking-tight">กำลังโหลดระบบ...</span>
            </div>
            <p class="text-xs text-base-content/40 font-bold uppercase tracking-widest">Admin Control Panel</p>
        </div>
    </div>

    <!-- วงกลมตกแต่งฉากหลัง -->
    <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>

    <div class="card w-full max-w-md bg-base-100 shadow-2xl rounded-2xl overflow-hidden border border-base-200 relative z-10 transition-all hover:shadow-primary/10">
        <div class="card-body p-8 flex flex-col items-center">
            
            <!-- Logo / Header -->
            <div class="flex flex-col items-center gap-2 mb-6 text-center">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center text-primary text-4xl mb-2 animate-pulse">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h2 class="card-title text-2xl font-black text-base-content tracking-tight">ยืนยันตัวตน 2FA</h2>
                <p class="text-xs text-base-content/50 max-w-[280px] leading-relaxed mt-1">กรุณากรอกรหัสความปลอดภัย 6 หลักจากแอป <strong class="text-primary font-bold">Google Authenticator</strong> เพื่อดำเนินการต่อ</p>
            </div>

            <!-- Error Alert -->
            <?php if ($error): ?>
                <div class="alert alert-error gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none flex items-center justify-center w-full">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" class="w-full flex flex-col gap-4">
                
                <!-- Code Input -->
                <div class="form-control w-full">
                    <label class="label pb-1.5">
                        <span class="label-text font-bold text-xs uppercase text-base-content/60">รหัสยืนยันตัวตน 6 หลัก</span>
                    </label>
                    <input type="text" name="code" class="input input-bordered w-full rounded-xl text-2xl tracking-[8px] font-mono text-center focus:input-primary h-14 bg-base-200/50" 
                           placeholder="000000" maxlength="6" inputmode="numeric" required autofocus autocomplete="one-time-code">
                </div>

                <!-- Submit Button -->
                <div class="mt-2">
                    <button type="submit" class="btn btn-primary w-full rounded-xl gap-2 font-bold text-base shadow-lg shadow-primary/25">
                        ยืนยันและลงชื่อเข้าใช้งาน <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </form>

            <a href="login.php" class="back-link link link-primary link-hover text-xs font-bold gap-1 flex items-center mt-6 text-base-content/50 hover:text-primary">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> กลับไปหน้าลงชื่อเข้าใช้
            </a>

            <!-- System Badge -->
            <div class="w-full border-t border-base-200 mt-6 pt-4 text-center text-xs text-base-content/40 font-semibold tracking-wide">
                <i class="fa-solid fa-building-columns mr-1"></i> ระบบความปลอดภัย SSO Angthong
            </div>
        </div>
    </div>
    <script>
        window.addEventListener('load', function() {
            const loader = document.getElementById('page-loader');
            if (loader) {
                loader.classList.add('opacity-0', 'invisible');
                setTimeout(() => {
                    loader.remove();
                }, 500);
            }
        });
    </script>
</body>

</html>