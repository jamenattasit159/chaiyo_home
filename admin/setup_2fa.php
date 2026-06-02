<?php
session_start();
require '../config.php';
require '../vendor/autoload.php';

// ตรวจสอบสิทธิ์
$userId = $_SESSION['admin_id'] ?? $_SESSION['temp_admin_id'] ?? null;

if (!$userId) {
    header('Location: login.php');
    exit;
}

$g = new \Google\Authenticator\GoogleAuthenticator();

// ดึงข้อมูล User
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$message = '';

// --- ส่วนที่ 1: บันทึกข้อมูล ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enable_2fa'])) {
    $secret = $_POST['secret'];
    $code = trim($_POST['code']);

    if ($g->checkCode($secret, $code)) {
        $pdo->prepare("UPDATE admin_users SET google_2fa_secret = ? WHERE id = ?")->execute([$secret, $userId]);

        if (isset($_SESSION['temp_admin_id'])) {
            $_SESSION['admin_id'] = $userId;
            $_SESSION['username'] = $user['username'];
            unset($_SESSION['temp_admin_id']);
            header('Location: index.php');
            exit;
        }

        // กรณีอัปเดตจากหน้า Profile (ถ้ามี)
        $message = '<div class="alert alert-success gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none flex items-center justify-center"><i class="fa-solid fa-circle-check text-lg"></i> เปิดใช้งาน 2FA สำเร็จเรียบร้อย!</div>';
        echo "<script>setTimeout(function(){ window.location.href = 'index.php'; }, 1500);</script>";

        // อัปเดตตัวแปรเพื่อให้หน้าเว็บเปลี่ยนสถานะทันทีโดยไม่ต้องรีโหลด
        $user['google_2fa_secret'] = $secret;
    } else {
        $message = '<div class="alert alert-error gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none flex items-center justify-center"><i class="fa-solid fa-circle-xmark text-lg"></i> รหัสไม่ถูกต้อง กรุณาลองใหม่</div>';
    }
}

// --- ส่วนที่ 2: เตรียมข้อมูล ---
$secret = $user['google_2fa_secret'];
$is_already_active = !empty($secret);
// ถ้ามี Secret แล้ว ให้ใช้ตัวเดิม แต่ถ้าไม่มีให้เจนใหม่
$display_secret = $is_already_active ? $secret : $g->generateSecret();

// สร้าง Text สำหรับ QR Code
$authUrl = "otpauth://totp/SSO Angthong:{$user['email']}?secret={$display_secret}&issuer=SSO Angthong";
?>

<!DOCTYPE html>
<html lang="th" data-theme="winter">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่า 2FA - SSO Angthong</title>
    <!-- โหลดฟอนต์ภาษาไทยและอังกฤษพรีเมียม -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Sarabun:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
    <!-- FontAwesome สำหรับไอคอนสวยๆ -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- QRCodeJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
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
                <span class="text-3xl">📱</span>
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
            <div class="flex flex-col items-center gap-2 mb-4">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center text-primary text-4xl mb-2">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                </div>
                <h2 class="card-title text-2xl font-black text-base-content tracking-tight">Google Authenticator</h2>
                <div class="badge badge-neutral bg-base-200 text-base-content/75 border-none font-bold py-3.5 px-4 rounded-full text-xs gap-1.5 mt-1 select-none">
                    <i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                </div>
            </div>

            <?php echo $message; ?>

            <?php if ($is_already_active): ?>
                <!-- แสดงเมื่อ 2FA เปิดใช้งานอยู่แล้ว -->
                <div class="flex flex-col items-center text-center py-6 w-full">
                    <div class="w-24 h-24 rounded-full bg-success/15 flex items-center justify-center text-success text-5xl mb-4 animate-bounce">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="text-xl font-bold text-base-content mb-2">ความปลอดภัยขั้นสูงเปิดทำงานแล้ว</h3>
                    <p class="text-sm text-base-content/50 leading-relaxed max-w-[280px] mb-8">บัญชีของคุณได้รับการปกป้องด้วยการยืนยันตัวตนสองขั้นตอนเสร็จสมบูรณ์</p>
                    
                    <a href="index.php" class="btn btn-primary w-full rounded-xl gap-2 font-bold text-base shadow-lg shadow-primary/25">
                        <i class="fa-solid fa-house"></i> กลับสู่หน้าหลัก
                    </a>
                </div>

            <?php else: ?>
                <!-- โหมดการตั้งค่าและแสกนเปิดใช้งานครั้งแรก -->
                <div class="flex flex-col items-center text-center w-full">
                    <p class="text-sm text-base-content/60 leading-relaxed mb-5 max-w-[320px]">
                        เพื่อความปลอดภัย กรุณาสแกน QR Code ด้วยแอป <strong class="text-primary font-bold">Google Authenticator</strong> ในโทรศัพท์ของท่าน
                    </p>

                    <!-- พื้นที่แสดง QR Code -->
                    <div class="border-2 border-dashed border-base-300 rounded-2xl p-4 bg-white shadow-inner mb-4 inline-block">
                        <div id="qrcode"></div>
                    </div>

                    <!-- Setup Key สำหรับผู้ใช้งานที่สแกนไม่ติด -->
                    <div class="w-full flex flex-col items-center mb-6">
                        <button type="button" onclick="toggleManual()" class="link link-primary link-hover text-xs font-semibold mb-2">
                            สแกนไม่ได้? ดูรหัส Setup Key
                        </button>
                        <div id="manual-key-box" class="hidden bg-base-200 border border-base-300 text-xs font-mono font-bold px-3 py-2.5 rounded-xl break-all w-full select-all">
                            KEY: <span><?php echo $display_secret; ?></span>
                        </div>
                    </div>

                    <!-- ฟอร์มยืนยัน OTP -->
                    <form method="POST" class="w-full flex flex-col gap-4">
                        <input type="hidden" name="secret" value="<?php echo $display_secret; ?>">

                        <div class="form-control w-full">
                            <label class="label pb-1.5">
                                <span class="label-text font-bold text-xs uppercase text-base-content/60">กรอกรหัสยืนยัน 6 หลักจากแอป</span>
                            </label>
                            <input type="text" name="code" class="input input-bordered w-full rounded-xl text-2xl tracking-[8px] font-mono text-center focus:input-primary h-14 bg-base-200/50" 
                                   placeholder="000000" maxlength="6" inputmode="numeric" required autofocus autocomplete="one-time-code">
                        </div>

                        <button type="submit" name="enable_2fa" class="btn btn-primary w-full rounded-xl gap-2 font-bold text-base shadow-lg shadow-primary/25 mt-2">
                            ยืนยันและเปิดใช้งาน <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>
                </div>

                <script>
                    // เจนรูป QR Code โดยใช้ QRCodeJS
                    new QRCode(document.getElementById("qrcode"), {
                        text: "<?php echo $authUrl; ?>",
                        width: 160,
                        height: 160,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });

                    function toggleManual() {
                        var x = document.getElementById("manual-key-box");
                        if (x.classList.contains("hidden")) {
                            x.classList.remove("hidden");
                        } else {
                            x.classList.add("hidden");
                        }
                    }
                </script>
            <?php endif; ?>

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