<?php
// admin/pages/forgot_password.php

$step = 1; // 1=ตรวจสอบข้อมูล, 2=ตั้งรหัสใหม่
$message = '';
$user_id_reset = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // ขั้นตอนที่ 1: ตรวจสอบข้อมูล
    if ($action == 'verify') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);

        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? AND email = ? AND status = 'active'");
        $stmt->execute([$username, $email]);
        $user = $stmt->fetch();

        if ($user) {
            $step = 2;
            $user_id_reset = $user['id'];
        } else {
            $message = '<div class="alert alert-error gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none flex items-center justify-center w-full"><i class="fa-solid fa-circle-xmark"></i> ไม่พบข้อมูลผู้ใช้ หรืออีเมลไม่ถูกต้อง</div>';
        }
    }
    // ขั้นตอนที่ 2: เปลี่ยนรหัสผ่าน
    elseif ($action == 'reset') {
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        $uid = $_POST['uid'];

        if (strlen($new_pass) < 4) {
            $message = '<div class="alert alert-error gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none flex items-center justify-center w-full"><i class="fa-solid fa-circle-xmark"></i> รหัสผ่านต้องมีความยาวอย่างน้อย 4 ตัวอักษร</div>';
            $step = 2;
            $user_id_reset = $uid;
        } elseif ($new_pass !== $confirm_pass) {
            $message = '<div class="alert alert-error gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none flex items-center justify-center w-full"><i class="fa-solid fa-circle-xmark"></i> รหัสผ่านไม่ตรงกัน</div>';
            $step = 2;
            $user_id_reset = $uid;
        } else {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hash, $uid])) {
                $message = '<div class="alert alert-success gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none flex items-center justify-center w-full"><i class="fa-solid fa-circle-check"></i> เปลี่ยนรหัสผ่านสำเร็จ! <a href="login.php" class="underline hover:text-white">เข้าสู่ระบบ</a></div>';
                $step = 3; // เสร็จสิ้น
            } else {
                $message = '<div class="alert alert-error gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none flex items-center justify-center w-full"><i class="fa-solid fa-circle-xmark"></i> เกิดข้อผิดพลาด</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="winter">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน — SSO Angthong</title>
    <!-- โหลดฟอนต์ภาษาไทยและอังกฤษพรีเมียม -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Sarabun:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
    <!-- FontAwesome สำหรับไอคอนสวยๆ -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS Play CDN -->
    <!-- daisyUI 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5.5.20/daisyui.min.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5.5.20/themes.css" rel="stylesheet" type="text/css" />
    
    <!-- Tailwind CSS 4 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <style type="text/tailwindcss">
      @theme {
        --font-sans: 'Sarabun', 'Outfit', sans-serif;
        --font-display: 'Outfit', 'Sarabun', sans-serif;
      }
      h1, h2, h3, h4, h5, h6, .stat-value, .stat-title, .menu-title, .badge, .font-display {
        font-family: var(--font-display);
        font-weight: 800;
        letter-spacing: -0.02em;
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
            <div class="flex flex-col items-center gap-2 mb-6 text-center">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center text-primary text-4xl mb-2">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h2 class="card-title text-2xl font-black text-base-content tracking-tight">กู้คืนรหัสผ่าน</h2>
                <p class="text-xs text-base-content/50 max-w-[280px] leading-relaxed mt-1">กรอกข้อมูลผู้ใช้งานและอีเมลของท่านเพื่อเปลี่ยนรหัสผ่านใหม่เข้าใช้งานระบบ</p>
            </div>

            <?php echo $message; ?>

            <?php if ($step == 1): ?>
                <form method="POST" class="w-full flex flex-col gap-4">
                    <input type="hidden" name="action" value="verify">
                    
                    <!-- Username Input -->
                    <div class="form-control w-full">
                        <label class="label pb-1.5"><span class="label-text font-bold text-xs uppercase text-base-content/60">ชื่อผู้ใช้งาน (Username)</span></label>
                        <div class="relative w-full">
                            <input type="text" name="username" class="input input-bordered w-full pl-12 rounded-xl focus:input-primary" placeholder="กรอกชื่อผู้ใช้..." required autofocus />
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base-content/40"><i class="fa-solid fa-user"></i></span>
                        </div>
                    </div>

                    <!-- Email Input -->
                    <div class="form-control w-full">
                        <label class="label pb-1.5"><span class="label-text font-bold text-xs uppercase text-base-content/60">อีเมลที่ลงทะเบียน</span></label>
                        <div class="relative w-full">
                            <input type="email" name="email" class="input input-bordered w-full pl-12 rounded-xl focus:input-primary" placeholder="name@example.com" required />
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base-content/40"><i class="fa-solid fa-envelope"></i></span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary w-full rounded-xl gap-2 font-bold text-base shadow-lg shadow-primary/25">
                            ตรวจสอบข้อมูลการลงทะเบียน <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                </form>

            <?php elseif ($step == 2): ?>
                <form method="POST" class="w-full flex flex-col gap-4">
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="uid" value="<?php echo $user_id_reset; ?>">
                    
                    <div class="alert alert-success gap-2 py-3 px-4 rounded-xl text-[11px] font-bold text-white shadow-md border-none flex items-center justify-center w-full">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>ยืนยันข้อมูลเรียบร้อยแล้ว ตั้งรหัสผ่านใหม่ได้ทันที</span>
                    </div>

                    <!-- Password Input -->
                    <div class="form-control w-full">
                        <label class="label pb-1.5"><span class="label-text font-bold text-xs uppercase text-base-content/60">รหัสผ่านใหม่</span></label>
                        <div class="relative w-full">
                            <input type="password" name="new_password" class="input input-bordered w-full pl-12 rounded-xl focus:input-primary" placeholder="กรอกรหัสผ่านใหม่..." required autofocus />
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base-content/40"><i class="fa-solid fa-lock"></i></span>
                        </div>
                    </div>

                    <!-- Confirm Password Input -->
                    <div class="form-control w-full">
                        <label class="label pb-1.5"><span class="label-text font-bold text-xs uppercase text-base-content/60">ยืนยันรหัสผ่านใหม่อีกครั้ง</span></label>
                        <div class="relative w-full">
                            <input type="password" name="confirm_password" class="input input-bordered w-full pl-12 rounded-xl focus:input-primary" placeholder="ยืนยันรหัสผ่านใหม่..." required />
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base-content/40"><i class="fa-solid fa-lock"></i></span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-2">
                        <button type="submit" class="btn btn-success w-full text-white rounded-xl gap-2 font-bold text-base shadow-lg shadow-success/25">
                            บันทึกเปลี่ยนรหัสผ่าน <i class="fa-solid fa-floppy-disk"></i>
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <a href="login.php" class="back-link link link-primary link-hover text-xs font-bold gap-1 flex items-center mt-6 text-base-content/50 hover:text-primary">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> กลับไปหน้าล็อกอิน
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