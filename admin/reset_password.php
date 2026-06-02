<?php
session_start();
require '../config.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$token = trim($_GET['token'] ?? '');
$error = '';
$success = '';
$valid_token = false;
$token_data = null;

// ---- ตรวจสอบ Token ----
if (empty($token)) {
    $error = 'ลิงก์ไม่ถูกต้องหรือหมดอายุแล้ว';
} else {
    try {
        $stmt = $pdo->prepare("
            SELECT pr.*, au.username
            FROM password_resets pr
            JOIN admin_users au ON au.id = pr.admin_id
            WHERE pr.token = ?
              AND pr.used = 0
              AND pr.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $token_data = $stmt->fetch();

        if ($token_data) {
            $valid_token = true;
        } else {
            $error = 'ลิงก์หมดอายุแล้วหรือถูกใช้งานไปแล้ว กรุณาขอรีเซ็ตรหัสผ่านใหม่อีกครั้ง';
        }
    } catch (PDOException $e) {
        $error = 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง';
    }
}

// ---- รับฟอร์มตั้งรหัสผ่านใหม่ ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password  = $_POST['new_password']  ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 8) {
        $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
    } elseif ($new_password !== $confirm_password) {
        $error = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
    } elseif (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $error = 'รหัสผ่านต้องมีทั้งตัวอักษรและตัวเลขอย่างน้อย 1 ตัว';
    } else {
        try {
            // อัปเดตรหัสผ่านใหม่
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?")
                ->execute([$hashed, $token_data['admin_id']]);

            // Mark token ว่าใช้แล้ว
            $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?")
                ->execute([$token]);

            $success = 'เปลี่ยนรหัสผ่านสำเร็จ! กำลังพาท่านไปหน้าเข้าสู่ระบบ...';
            $valid_token = false; // ซ่อนฟอร์ม
        } catch (PDOException $e) {
            $error = 'เกิดข้อผิดพลาดในการบันทึก กรุณาลองใหม่อีกครั้ง';
        }
    }
}

// คำนวณเวลาที่เหลือของ token
$time_remaining = '';
if ($valid_token && $token_data) {
    $expires = new DateTime($token_data['expires_at']);
    $now = new DateTime();
    $diff = $now->diff($expires);
    if ($diff->h > 0) {
        $time_remaining = $diff->h . ' ชั่วโมง ' . $diff->i . ' นาที';
    } else {
        $time_remaining = $diff->i . ' นาที';
    }
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="winter">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งรหัสผ่านใหม่ — SSO Angthong</title>
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
        --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1);
        --ease-out-quart: cubic-bezier(0.25, 1, 0.5, 1);
      }
      h1, h2, h3, h4, h5, h6, .stat-value, .stat-title, .menu-title, .badge, .font-display {
        font-family: var(--font-display);
        font-weight: 800;
        letter-spacing: -0.02em;
      }

      /* Global Easing & Animations */
      @keyframes authCardEntrance {
        from {
          opacity: 0;
          transform: translateY(24px) scale(0.96);
        }
        to {
          opacity: 1;
          transform: translateY(0) scale(1);
        }
      }
      .animate-auth-card {
        animation: authCardEntrance 600ms var(--ease-out-expo) forwards;
      }

      @keyframes shake {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-6px); }
        40%, 80% { transform: translateX(6px); }
      }
      .animate-shake {
        animation: shake 400ms cubic-bezier(0.25, 0.8, 0.25, 1) both;
      }

      /* Global Input Focus */
      .input {
        transition: all 200ms var(--ease-out-quart) !important;
      }
      .input:focus {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(var(--color-primary), 0.1) !important;
      }

      /* Global Button */
      .btn {
        transition: all 200ms var(--ease-out-quart) !important;
      }
      .btn:active {
        transform: scale(0.96) !important;
      }

      /* Prefers Reduced Motion Compatibility */
      @media (prefers-reduced-motion: reduce) {
        *, ::before, ::after {
          animation-delay: 0s !important;
          animation-duration: 0.01ms !important;
          animation-iteration-count: 1 !important;
          transition-delay: 0s !important;
          transition-duration: 0.01ms !important;
          scroll-behavior: auto !important;
          transform: none !important;
        }
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

    <div class="card w-full max-w-md bg-base-100 shadow-2xl rounded-2xl overflow-hidden border border-base-200 relative z-10 transition-all hover:shadow-primary/10 animate-auth-card">
        <div class="card-body p-8 flex flex-col items-center">

            <?php if ($success): ?>
                <!-- แสดงเมื่อเปลี่ยนสำเร็จ -->
                <div class="flex flex-col items-center text-center py-6 w-full">
                    <div class="w-20 h-20 rounded-full bg-success/15 flex items-center justify-center text-success text-4xl mb-4 animate-bounce">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h3 class="text-xl font-bold text-base-content mb-2">เปลี่ยนรหัสผ่านสำเร็จ!</h3>
                    <p class="text-sm text-base-content/50 leading-relaxed max-w-[280px] mb-6">ระบบได้ทำการบันทึกรหัสผ่านใหม่ของท่านแล้ว ลิงก์กู้คืนเดิมจะใช้งานไม่ได้อีกต่อไป</p>
                    
                    <a href="login.php" class="btn btn-primary w-full rounded-xl gap-2 font-bold text-base shadow-lg shadow-primary/25">
                        <i class="fa-solid fa-right-to-bracket"></i> ไปหน้าเข้าสู่ระบบ
                    </a>
                </div>

            <?php elseif (!$valid_token): ?>
                <!-- แสดงเมื่อโทเคนหมดอายุหรือลิงก์ไม่ถูกต้อง -->
                <div class="flex flex-col items-center text-center py-6 w-full">
                    <div class="w-20 h-20 rounded-full bg-error/15 flex items-center justify-center text-error text-4xl mb-4">
                        <i class="fa-solid fa-link-slash"></i>
                    </div>
                    <h3 class="text-xl font-bold text-base-content mb-2">ลิงก์ไม่ถูกต้อง</h3>
                    <p class="text-sm text-base-content/50 leading-relaxed max-w-[280px] mb-6"><?php echo htmlspecialchars($error ?: 'ลิงก์กู้คืนรหัสผ่านของท่านหมดอายุหรือถูกใช้งานไปแล้ว'); ?></p>
                    
                    <a href="forgot_password.php" class="btn btn-primary w-full rounded-xl gap-2 font-bold text-base shadow-lg shadow-primary/25">
                        <i class="fa-solid fa-rotate-right"></i> ขอลิงก์ใหม่อีกครั้ง
                    </a>
                </div>

            <?php else: ?>
                <!-- ฟอร์มตั้งรหัสใหม่ -->
                <div class="flex flex-col items-center gap-2 mb-4 text-center">
                    <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center text-primary text-4xl mb-2">
                        <i class="fa-solid fa-lock-open"></i>
                    </div>
                    <h2 class="card-title text-2xl font-black text-base-content tracking-tight">ตั้งรหัสผ่านใหม่</h2>
                    <p class="text-xs text-base-content/50 max-w-[280px] leading-relaxed mt-1">กรุณากำหนดและป้อนรหัสผ่านใหม่เพื่อความปลอดภัยของระบบ</p>
                </div>

                <!-- สเต็ปแจ้งสถานะ -->
                <div class="stats bg-base-200 w-full rounded-2xl py-2 mb-5 select-none text-center shadow-inner">
                    <div class="stat p-1 flex justify-center gap-3 text-[10px] font-bold text-base-content/40">
                        <span class="flex items-center gap-1"><span class="w-4 h-4 rounded-full bg-success text-white flex items-center justify-center text-[8px] font-extrabold">✓</span> ตรวจสอบ</span>
                        <span>&bull;</span>
                        <span class="flex items-center gap-1"><span class="w-4 h-4 rounded-full bg-success text-white flex items-center justify-center text-[8px] font-extrabold">✓</span> ส่งอีเมล</span>
                        <span>&bull;</span>
                        <span class="text-primary font-extrabold flex items-center gap-1"><span class="w-4 h-4 rounded-full bg-primary text-white flex items-center justify-center text-[8px]">3</span> รหัสใหม่</span>
                    </div>
                </div>

                <!-- ยูสเซอร์ที่รีเซ็ตและเวลาที่เหลือ -->
                <div class="w-full flex flex-col gap-2 mb-4">
                    <div class="bg-primary/5 border border-primary/10 rounded-xl px-3 py-2 flex items-center gap-2 text-xs font-bold text-primary">
                        <i class="fa-solid fa-user-shield"></i>
                        <span>บัญชี: <?php echo htmlspecialchars($token_data['username']); ?></span>
                    </div>
                    <?php if ($time_remaining): ?>
                        <div class="bg-success/5 border border-success/10 rounded-xl px-3 py-2 flex items-center gap-2 text-xs font-bold text-success">
                            <i class="fa-solid fa-hourglass-half"></i>
                            <span>ลิงก์หมดอายุใน: <?php echo $time_remaining; ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Error Alert -->
                <?php if ($error): ?>
                    <div class="alert alert-error gap-2 py-3 px-4 rounded-xl text-sm font-semibold mb-5 text-white shadow-md border-none flex items-center justify-center w-full animate-shake">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="reset-form" class="w-full flex flex-col gap-4">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <!-- New Password Input -->
                    <div class="form-control w-full">
                        <label class="label pb-1.5"><span class="label-text font-bold text-xs uppercase text-base-content/60">รหัสผ่านใหม่</span></label>
                        <div class="relative w-full">
                            <input type="password" name="new_password" id="new_password" class="input input-bordered w-full pl-12 pr-12 rounded-xl focus:input-primary" 
                                   placeholder="รหัสผ่านใหม่..." required autocomplete="new-password">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base-content/40"><i class="fa-solid fa-lock"></i></span>
                            <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-primary transition" onclick="togglePw('new_password', this)" tabindex="-1">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Strength Meter -->
                    <div class="w-full space-y-1.5">
                        <div class="bg-base-200 h-1.5 w-full rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-error transition-all duration-300 w-0" id="strength-fill"></div>
                        </div>
                        <div class="text-[10px] font-bold text-base-content/40 flex justify-between items-center" id="strength-label">ความแข็งแรงของรหัสผ่าน</div>
                    </div>

                    <!-- Rules List -->
                    <ul class="text-[10px] font-bold text-base-content/40 space-y-1 pl-1">
                        <li id="rule-len" class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-full bg-base-200 flex items-center justify-center text-[7px] text-base-content/40 font-mono">1</span> ความยาวอย่างน้อย 8 ตัวอักษร</li>
                        <li id="rule-letter" class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-full bg-base-200 flex items-center justify-center text-[7px] text-base-content/40 font-mono">2</span> มีตัวอักษรภาษาอังกฤษ (A-Z, a-z)</li>
                        <li id="rule-num" class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-full bg-base-200 flex items-center justify-center text-[7px] text-base-content/40 font-mono">3</span> มีตัวเลขประกอบอยู่ (0-9)</li>
                    </ul>

                    <!-- Confirm Password Input -->
                    <div class="form-control w-full">
                        <label class="label pb-1.5"><span class="label-text font-bold text-xs uppercase text-base-content/60">ยืนยันรหัสผ่านใหม่</span></label>
                        <div class="relative w-full">
                            <input type="password" name="confirm_password" id="confirm_password" class="input input-bordered w-full pl-12 pr-12 rounded-xl focus:input-primary" 
                                   placeholder="ยืนยันรหัสผ่านใหม่อีกครั้ง..." required autocomplete="new-password">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base-content/40"><i class="fa-solid fa-lock"></i></span>
                            <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-primary transition" onclick="togglePw('confirm_password', this)" tabindex="-1">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Match Indicator -->
                    <div class="text-[10px] font-bold text-error leading-relaxed -mt-2 min-h-[15px]" id="match-indicator"></div>

                    <button type="submit" id="submit-btn" class="btn btn-primary w-full rounded-xl gap-2 font-bold text-base shadow-lg shadow-primary/25 mt-2">
                        <i class="fa-solid fa-floppy-disk text-sm"></i> บันทึกรหัสผ่านใหม่
                    </button>
                </form>
            <?php endif; ?>

            <?php if (!$success): ?>
                <a href="login.php" class="back-link link link-primary link-hover text-xs font-bold gap-1 flex items-center mt-6 text-base-content/50 hover:text-primary">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> กลับไปหน้าล็อกอิน
                </a>
            <?php endif; ?>

            <!-- System Badge -->
            <div class="w-full border-t border-base-200 mt-6 pt-4 text-center text-xs text-base-content/40 font-semibold tracking-wide">
                <i class="fa-solid fa-building-columns mr-1"></i> ระบบความปลอดภัย SSO Angthong
            </div>
        </div>
    </div>

    <script>
        // ---- Toggle password visibility ----
        function togglePw(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // ---- Password strength and match verification ----
        const pwInput  = document.getElementById('new_password');
        const cfInput  = document.getElementById('confirm_password');
        const fill     = document.getElementById('strength-fill');
        const label    = document.getElementById('strength-label');
        const ruleLen  = document.getElementById('rule-len');
        const ruleLet  = document.getElementById('rule-letter');
        const ruleNum  = document.getElementById('rule-num');
        const matchEl  = document.getElementById('match-indicator');

        if (pwInput) {
            pwInput.addEventListener('input', function () {
                const val = this.value;
                let score = 0;
                const hasLen    = val.length >= 8;
                const hasLetter = /[A-Za-z]/.test(val);
                const hasNum    = /[0-9]/.test(val);
                const hasSpec   = /[^A-Za-z0-9]/.test(val);
                const hasUpper  = /[A-Z]/.test(val);

                // ตรวจความยาว
                if (hasLen) { 
                    score++; 
                    ruleLen.querySelector('span').className = 'w-3.5 h-3.5 rounded-full bg-success text-white flex items-center justify-center text-[7px] font-extrabold';
                    ruleLen.querySelector('span').innerHTML = '✓';
                } else { 
                    ruleLen.querySelector('span').className = 'w-3.5 h-3.5 rounded-full bg-base-200 flex items-center justify-center text-[7px] text-base-content/40 font-mono';
                    ruleLen.querySelector('span').innerHTML = '1';
                }

                // ตรวจตัวอักษร
                if (hasLetter) { 
                    score++; 
                    ruleLet.querySelector('span').className = 'w-3.5 h-3.5 rounded-full bg-success text-white flex items-center justify-center text-[7px] font-extrabold';
                    ruleLet.querySelector('span').innerHTML = '✓';
                } else { 
                    ruleLet.querySelector('span').className = 'w-3.5 h-3.5 rounded-full bg-base-200 flex items-center justify-center text-[7px] text-base-content/40 font-mono';
                    ruleLet.querySelector('span').innerHTML = '2';
                }

                // ตรวจตัวเลข
                if (hasNum) { 
                    score++; 
                    ruleNum.querySelector('span').className = 'w-3.5 h-3.5 rounded-full bg-success text-white flex items-center justify-center text-[7px] font-extrabold';
                    ruleNum.querySelector('span').innerHTML = '✓';
                } else { 
                    ruleNum.querySelector('span').className = 'w-3.5 h-3.5 rounded-full bg-base-200 flex items-center justify-center text-[7px] text-base-content/40 font-mono';
                    ruleNum.querySelector('span').innerHTML = '3';
                }
                
                if (hasSpec)   score++;
                if (hasUpper)  score++;

                const pct = Math.min((score / 5) * 100, 100);
                fill.style.width = pct + '%';

                fill.classList.remove('bg-error', 'bg-warning', 'bg-info', 'bg-success');
                if (score <= 1) { fill.classList.add('bg-error'); label.textContent = 'ความแข็งแรง: ต่ำมาก ❌'; }
                else if (score <= 2) { fill.classList.add('bg-warning'); label.textContent = 'ความแข็งแรง: ปานกลาง ⚠️'; }
                else if (score <= 4) { fill.classList.add('bg-info'); label.textContent = 'ความแข็งแรง: แข็งแรง 👍'; }
                else { fill.classList.add('bg-success'); label.textContent = 'ความแข็งแรง: แข็งแรงมาก ✓'; }

                checkMatch();
            });
        }

        if (cfInput) {
            cfInput.addEventListener('input', checkMatch);
        }

        function checkMatch() {
            if (!cfInput || !pwInput || cfInput.value === '') { matchEl.textContent = ''; return; }
            if (pwInput.value === cfInput.value) {
                matchEl.innerHTML = '<span class="text-success flex items-center gap-1"><i class="fa-solid fa-circle-check text-xs"></i> รหัสผ่านตรงกันเรียบร้อย</span>';
            } else {
                matchEl.innerHTML = '<span class="text-error flex items-center gap-1"><i class="fa-solid fa-circle-xmark text-xs"></i> รหัสผ่านทั้งสองช่องไม่ตรงกัน</span>';
            }
        }

        // ---- Loading status on submit ----
        document.getElementById('reset-form')?.addEventListener('submit', function () {
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> กำลังบันทึก...';
        });

        // ---- Auto redirect after success ----
        <?php if ($success): ?>
        setTimeout(() => { window.location.href = 'login.php'; }, 3000);
        <?php endif; ?>
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
