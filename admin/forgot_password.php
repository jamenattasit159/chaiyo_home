<?php
session_start();
require '../config.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ============================================================
//  ⚙️  ตั้งค่า Gmail SMTP — แก้ไขค่าด้านล่างนี้ก่อนใช้งาน
// ============================================================
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'nattasitkung.am@gmail.com');     // ← เปลี่ยนเป็น Gmail ของท่าน
define('SMTP_PASS',     'gqow twdt wvnv rwoc');      // ← Gmail App Password (16 ตัวอักษร)
define('SMTP_FROM',     'nattasitkung.am@gmail.com');     // ← ชื่อที่แสดงในอีเมล
define('SMTP_FROM_NAME','ระบบ SSO Angthong');
// ============================================================

if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$message_type = '';
$step = 'form'; // form | sent

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if (empty($username) || empty($email)) {
        $message = 'กรุณากรอกชื่อผู้ใช้และอีเมลให้ครบถ้วน';
        $message_type = 'error';
    } else {
        try {
            // ตรวจสอบว่า username + email ตรงกันในฐานข้อมูล
            $stmt = $pdo->prepare("SELECT id, email FROM admin_users WHERE username = ? AND email = ? AND status = 'active'");
            $stmt->execute([$username, $email]);
            $user = $stmt->fetch();

            if ($user) {
                // ลบ token เก่าของ user นี้ (ถ้ามี)
                $pdo->prepare("DELETE FROM password_resets WHERE admin_id = ?")->execute([$user['id']]);

                // สร้าง token ใหม่
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $stmt = $pdo->prepare("INSERT INTO password_resets (admin_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $token, $expires_at]);

                // สร้างลิงก์รีเซ็ต
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $dir  = dirname($_SERVER['PHP_SELF']);
                $reset_link = "{$protocol}://{$host}{$dir}/reset_password.php?token={$token}";

                // ส่งอีเมลด้วย PHPMailer
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USER;
                    $mail->Password   = SMTP_PASS;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = SMTP_PORT;
                    $mail->CharSet    = 'UTF-8';

                    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
                    $mail->addAddress($user['email']);
                    $mail->isHTML(true);
                    $mail->Subject = 'รีเซ็ตรหัสผ่าน — ' . SMTP_FROM_NAME;

                    $mail->Body = '
<!DOCTYPE html>
<html lang="th">
<head><meta charset="UTF-8">
<style>
  body { font-family: "Sarabun", Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
  .wrap { max-width: 560px; margin: 30px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
  .header { background: linear-gradient(135deg, #f97316, #ea580c); padding: 36px 40px; text-align: center; }
  .header h1 { color: #fff; margin: 0; font-size: 1.5rem; }
  .header p { color: rgba(255,255,255,0.85); margin: 6px 0 0; font-size: 0.95rem; }
  .body { padding: 36px 40px; }
  .body p { color: #444; line-height: 1.8; font-size: 1rem; }
  .btn { display: block; width: fit-content; margin: 28px auto; padding: 14px 36px; background: linear-gradient(135deg, #f97316, #ea580c); color: #fff !important; text-decoration: none; border-radius: 10px; font-size: 1rem; font-weight: 600; text-align: center; }
  .note { background: #fff7ed; border-left: 4px solid #f97316; padding: 14px 16px; border-radius: 6px; color: #9a3412; font-size: 0.88rem; margin-top: 20px; }
  .footer { text-align: center; padding: 20px; color: #999; font-size: 0.8rem; border-top: 1px solid #eee; }
  .url-fallback { word-break: break-all; color: #666; font-size: 0.82rem; margin-top: 12px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>🔐 รีเซ็ตรหัสผ่าน</h1>
    <p>ระบบ SSO Angthong — Admin Control Panel</p>
  </div>
  <div class="body">
    <p>สวัสดีครับ,</p>
    <p>เราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับบัญชี <strong>' . htmlspecialchars($username) . '</strong> คลิกปุ่มด้านล่างเพื่อตั้งรหัสผ่านใหม่</p>
    <a href="' . $reset_link . '" class="btn">ตั้งรหัสผ่านใหม่</a>
    <div class="note">
      ⏰ ลิงก์นี้จะหมดอายุภายใน <strong>1 ชั่วโมง</strong><br>
      หากท่านไม่ได้ขอรีเซ็ตรหัสผ่าน กรุณาเพิกเฉยต่ออีเมลฉบับนี้
    </div>
    <p class="url-fallback">หากปุ่มด้านบนไม่ทำงาน ให้คัดลอก URL นี้ไปวางในเบราว์เซอร์:<br>' . $reset_link . '</p>
  </div>
  <div class="footer">' . SMTP_FROM_NAME . ' &bull; ระบบส่งอัตโนมัติ กรุณาอย่าตอบกลับ</div>
</div>
</body>
</html>';

                    $mail->AltBody = "รีเซ็ตรหัสผ่านของท่าน: {$reset_link}\n\nลิงก์หมดอายุใน 1 ชั่วโมง";
                    $mail->send();

                    $step = 'sent';
                } catch (Exception $e) {
                    // ลบ token ที่สร้างไว้ถ้าส่งอีเมลไม่สำเร็จ
                    $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);
                    $message = 'ไม่สามารถส่งอีเมลได้: ' . $mail->ErrorInfo;
                    $message_type = 'error';
                }
            } else {
                // ไม่แจ้งว่าหาไม่เจอ (Security: prevent user enumeration)
                $step = 'sent';
            }
        } catch (PDOException $e) {
            $message = 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง';
            $message_type = 'error';
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

            <?php if ($step === 'form'): ?>
                <!-- โหมดการกู้คืนรหัสผ่านด้วยฟอร์มกรอกข้อมูล -->
                <div class="flex flex-col items-center gap-2 mb-4 text-center">
                    <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center text-primary text-4xl mb-2">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <h2 class="card-title text-2xl font-black text-base-content tracking-tight">ลืมรหัสผ่าน?</h2>
                    <p class="text-xs text-base-content/50 max-w-[280px] leading-relaxed mt-1">กรอกชื่อผู้ใช้และอีเมลที่ผูกกับบัญชีของท่าน ระบบจะทำการตรวจสอบและส่งลิงก์ตั้งค่ารหัสใหม่ให้ทางอีเมล</p>
                </div>

                <!-- แสดงขั้นตอน (Steps Indicator) -->
                <div class="stats bg-base-200 w-full rounded-2xl py-2 mb-5 select-none text-center shadow-inner">
                    <div class="stat p-1 flex justify-center gap-3 text-[10px] font-bold text-base-content/40">
                        <span class="text-primary font-extrabold flex items-center gap-1"><span class="w-4 h-4 rounded-full bg-primary text-white flex items-center justify-center text-[8px]">1</span> ตรวจสอบ</span>
                        <span>&bull;</span>
                        <span class="flex items-center gap-1"><span class="w-4 h-4 rounded-full bg-base-300 flex items-center justify-center text-[8px]">2</span> ส่งอีเมล</span>
                        <span>&bull;</span>
                        <span class="flex items-center gap-1"><span class="w-4 h-4 rounded-full bg-base-300 flex items-center justify-center text-[8px]">3</span> รหัสใหม่</span>
                    </div>
                </div>

                <!-- Error Alert -->
                <?php if ($message): ?>
                    <div class="alert alert-error gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none flex items-center justify-center w-full">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="forgot-form" class="w-full flex flex-col gap-4">
                    <!-- Username Input -->
                    <div class="form-control w-full">
                        <label class="label pb-1.5"><span class="label-text font-bold text-xs uppercase text-base-content/60">ชื่อผู้ใช้งาน (Username)</span></label>
                        <div class="relative w-full">
                            <input type="text" name="username" class="input input-bordered w-full pl-12 rounded-xl focus:input-primary" placeholder="กรอกชื่อผู้ใช้ (Username)"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus />
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base-content/40"><i class="fa-solid fa-user"></i></span>
                        </div>
                    </div>

                    <!-- Email Input -->
                    <div class="form-control w-full">
                        <label class="label pb-1.5"><span class="label-text font-bold text-xs uppercase text-base-content/60">อีเมลที่ผูกกับบัญชี</span></label>
                        <div class="relative w-full">
                            <input type="email" name="email" class="input input-bordered w-full pl-12 rounded-xl focus:input-primary" placeholder="กรอกอีเมลผู้ดูแลระบบ"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required />
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base-content/40"><i class="fa-solid fa-envelope"></i></span>
                        </div>
                    </div>

                    <button type="submit" id="submit-btn" class="btn btn-primary w-full rounded-xl gap-2 font-bold text-base shadow-lg shadow-primary/25 mt-2">
                        <i class="fa-solid fa-paper-plane text-sm"></i> ส่งลิงก์รีเซ็ตรหัสผ่าน
                    </button>
                </form>

            <?php else: ?>
                <!-- แสดงข้อความเมื่อส่งอีเมลเรียบร้อยแล้ว (Success State) -->
                <div class="flex flex-col items-center text-center py-6 w-full">
                    <div class="w-20 h-20 rounded-full bg-success/15 flex items-center justify-center text-success text-4xl mb-4 animate-pulse">
                        <i class="fa-solid fa-envelope-circle-check"></i>
                    </div>
                    <h3 class="text-xl font-bold text-base-content mb-2">ส่งลิงก์รีเซ็ตสำเร็จแล้ว!</h3>
                    <p class="text-sm text-base-content/50 leading-relaxed max-w-[280px] mb-4">หากมีบัญชีในระบบ เราได้ทำการส่งลิงก์รีเซ็ตรหัสผ่านไปยังอีเมลผู้ดูแลระบบที่ท่านระบุเรียบร้อยแล้ว</p>
                    
                    <div class="p-3 bg-warning/10 border border-warning/20 text-warning-content rounded-2xl text-[11px] font-semibold leading-relaxed mb-6 w-full">
                        <i class="fa-solid fa-clock mr-1"></i> ลิงก์กู้คืนจะหมดอายุภายใน <strong>1 ชั่วโมง</strong><br>
                        โปรดตรวจสอบในกล่องข้อความ และโฟลเดอร์ Spam/Junk Mail ด้วย
                    </div>

                    <!-- สเต็ปหลังส่งสำเร็จ -->
                    <div class="stats bg-base-200 w-full rounded-2xl py-2 mb-2 select-none text-center shadow-inner">
                        <div class="stat p-1 flex justify-center gap-3 text-[10px] font-bold text-base-content/40">
                            <span class="flex items-center gap-1"><span class="w-4 h-4 rounded-full bg-success text-white flex items-center justify-center text-[8px] font-extrabold">✓</span> ตรวจสอบ</span>
                            <span>&bull;</span>
                            <span class="text-primary font-extrabold flex items-center gap-1"><span class="w-4 h-4 rounded-full bg-primary text-white flex items-center justify-center text-[8px]">2</span> ส่งอีเมล</span>
                            <span>&bull;</span>
                            <span class="flex items-center gap-1"><span class="w-4 h-4 rounded-full bg-base-300 flex items-center justify-center text-[8px]">3</span> รหัสใหม่</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <a href="login.php" class="back-link link link-primary link-hover text-xs font-bold gap-1 flex items-center mt-6 text-base-content/50 hover:text-primary">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> กลับไปหน้าเข้าสู่ระบบ
            </a>

            <!-- System Badge -->
            <div class="w-full border-t border-base-200 mt-6 pt-4 text-center text-xs text-base-content/40 font-semibold tracking-wide">
                <i class="fa-solid fa-building-columns mr-1"></i> ระบบความปลอดภัย SSO Angthong
            </div>
        </div>
    </div>

    <script>
        // แสดงสถานะส่งอีเมลขณะทำการประมวลผล
        document.getElementById('forgot-form')?.addEventListener('submit', function () {
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> กำลังส่งลิงก์...';
        });
    </script>
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
