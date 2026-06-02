<?php
session_start();
require '../config.php';

// ถ้าล็อกอินอยู่แล้ว ดีดไปหน้าแรก
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        try {
            // 1. ดึงข้อมูล User จาก Username (ไม่ต้องเช็ค status ใน query)
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // 2. ตรวจสอบรหัสผ่าน
            if ($user && password_verify($password, $user['password'])) {

                // 3. ตรวจสอบสถานะบัญชี
                if (($user['status'] ?? 'active') !== 'active') {
                    $error = 'บัญชีของท่านถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ';
                } else {
                    // ล็อกอินเข้าสู่ระบบสำเร็จ (ข้ามขั้นตอน 2FA)
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'] ?? 'admin';
                    
                    header('Location: index.php');
                    exit;
                }

            } else {
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
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
    <title>เข้าสู่ระบบแอดมิน - SSO Angthong</title>
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
                <span class="text-3xl">🔑</span>
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
            <div class="flex flex-col items-center gap-2 mb-6">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center text-primary text-4xl mb-2">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h2 class="card-title text-2xl font-black text-base-content tracking-tight">เข้าสู่ระบบ</h2>
                <p class="text-xs text-base-content/40 uppercase tracking-widest font-bold">Admin Control Panel</p>
            </div>
            
            <!-- Error Alert -->
            <?php if ($error): ?>
                <div class="alert alert-error gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Form -->
            <form method="POST" class="w-full flex flex-col gap-4">
                
                <!-- Username Input -->
                <div class="form-control w-full">
                    <label class="label pb-1.5"><span class="label-text font-bold text-xs uppercase text-base-content/60">ชื่อผู้ใช้งาน (Username)</span></label>
                    <div class="relative w-full">
                        <input type="text" name="username" class="input input-bordered w-full pl-12 rounded-xl focus:input-primary" placeholder="กรอกชื่อผู้ใช้งาน" required autofocus />
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base-content/40"><i class="fa-solid fa-user"></i></span>
                    </div>
                </div>
                
                <!-- Password Input -->
                <div class="form-control w-full">
                    <label class="label pb-1.5"><span class="label-text font-bold text-xs uppercase text-base-content/60">รหัสผ่าน (Password)</span></label>
                    <div class="relative w-full">
                        <input type="password" name="password" class="input input-bordered w-full pl-12 rounded-xl focus:input-primary" placeholder="กรอกรหัสผ่าน" required />
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base-content/40"><i class="fa-solid fa-lock"></i></span>
                    </div>
                </div>
                
                <!-- Footer Links -->
                <div class="flex justify-between items-center text-xs font-semibold text-base-content/60 mt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" class="checkbox checkbox-xs checkbox-primary rounded" />
                        <span>จำฉันไว้ในระบบ</span>
                    </label>
                    <a href="forgot_password.php" class="link link-primary link-hover">ลืมรหัสผ่าน?</a>
                </div>
                
                <!-- Submit Button -->
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary w-full rounded-xl gap-2 font-bold text-base shadow-lg shadow-primary/25">
                        เข้าสู่ระบบ <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </form>
            
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