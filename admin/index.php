<?php
session_start();
require '../config.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$page = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="th" data-theme="winter">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการเว็บไซต์ - แผงควบคุมผู้ดูแลระบบ</title>
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
      @keyframes fadeSlideUp {
        from {
          opacity: 0;
          transform: translateY(16px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      .animate-fade-slide-up {
        animation: fadeSlideUp 500ms var(--ease-out-expo) forwards;
      }

      /* Sidebar list micro-interaction */
      .menu li a {
        transition: all 250ms var(--ease-out-quart);
      }
      .menu li a:hover {
        transform: translateX(6px);
      }
      .menu li a:active {
        transform: scale(0.97) translateX(6px);
      }

      /* Global Button Micro-interactions */
      .btn {
        transition: all 200ms var(--ease-out-quart) !important;
      }
      .btn:active {
        transform: scale(0.96) !important;
      }

      /* Global Form Inputs Glow Focus */
      .input, .textarea, .select, .checkbox, .radio {
        transition: all 200ms var(--ease-out-quart) !important;
      }
      .input:focus, .textarea:focus, .select:focus {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(var(--color-primary), 0.1) !important;
      }

      /* Staggered Cards Entrance */
      @keyframes cardEntrance {
        from {
          opacity: 0;
          transform: translateY(20px) scale(0.98);
        }
        to {
          opacity: 1;
          transform: translateY(0) scale(1);
        }
      }
      .stagger-card {
        opacity: 0;
        animation: cardEntrance 600ms var(--ease-out-expo) forwards;
        animation-delay: calc(var(--stagger, 0) * 50ms);
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

<body class="bg-base-200 min-h-screen font-sans">
    <!-- หน้าจอโหลดดิ้งพรีเมียม (Page Loading Overlay) -->
    <div id="page-loader" class="fixed inset-0 z-[9999] bg-base-100 flex flex-col items-center justify-center gap-4 transition-all duration-500 ease-out opacity-100 visible">
        <div class="flex flex-col items-center gap-3">
            <span class="loading loading-ring loading-lg text-primary scale-125"></span>
            <div class="flex items-center gap-2">
                <span class="text-3xl">⚙️</span>
                <span class="font-black text-xl text-primary tracking-tight">กำลังโหลดระบบ...</span>
            </div>
            <p class="text-xs text-base-content/40 font-bold uppercase tracking-widest">Admin Control Panel</p>
        </div>
    </div>

    <div class="drawer lg:drawer-open min-h-screen">
        <input id="admin-drawer" type="checkbox" class="drawer-toggle" />
        
        <div class="drawer-content flex flex-col bg-base-200">
            <!-- แถบด้านบน (Topbar) ดีไซน์พรีเมียม -->
            <header class="navbar bg-base-100 shadow-sm border-b border-base-200 px-4 py-3">
                <div class="flex-none lg:hidden">
                    <label for="admin-drawer" class="btn btn-square btn-ghost">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </label>
                </div>
                
                <div class="flex-1">
                    <h1 class="text-lg font-bold text-base-content/90 ml-2">
                        <?php
                        $titles = [
                            'dashboard' => '📊 แดชบอร์ดภาพรวม',
                            'organization' => '🏢 ข้อมูลหน่วยงาน',
                            'theme_settings' => '🎨 ตั้งค่าสีและธีม',
                            'banners' => '📸 จัดการแบนเนอร์สไลด์',
                            'pr_images' => '🖼️ จัดการรูปประชาสัมพันธ์',
                            'directors' => '👔 จัดการข้อมูลบุคลากร',
                            'announcements' => '📢 จัดการข่าวประกาศ',
                            'files' => '📂 จัดการไฟล์และเอกสาร',
                            'buttons' => '🔗 จัดการเมนูปุ่มทางลัด',
                            'manage_pages' => '📝 สร้าง/จัดการหน้าเนื้อหา',
                            'users' => '👥 จัดการข้อมูลผู้ใช้งาน'
                        ];
                        echo $titles[$page] ?? '⚙️ ระบบจัดการเว็บไซต์';
                        ?>
                    </h1>
                </div>
                
                <div class="flex-none gap-4">
                    <a href="../" target="_blank" class="btn btn-outline btn-primary btn-sm gap-2 rounded-lg">
                        <i class="fa-solid fa-globe"></i> <span>ดูหน้าเว็บหลัก</span>
                    </a>
                    
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-ghost btn-circle avatar bg-primary text-white font-bold text-lg select-none">
                            <?php echo mb_substr(htmlspecialchars($_SESSION['username']), 0, 1, 'UTF-8'); ?>
                        </label>
                        <ul tabindex="0" class="mt-3 z-[20] p-2 shadow-lg menu menu-sm dropdown-content bg-base-100 rounded-box w-56 border border-base-200">
                            <li class="menu-title border-b border-base-200 pb-2 mb-2">
                                <span class="font-bold text-base-content block"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <span class="text-xs text-base-content/60 block mt-0.5"><?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') ? 'Super Admin' : 'Admin'; ?></span>
                            </li>
                            <li>
                                <a href="logout.php" class="text-error active:bg-error active:text-white font-medium gap-2">
                                    <i class="fa-solid fa-door-open"></i> ออกจากระบบ
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- พื้นที่หน้าย่อย (Sub-pages Viewport) -->
            <main class="p-6 flex-1 overflow-y-auto max-w-[100vw]">
                <div class="animate-fade-slide-up">
                    <?php
                    // โหลดไฟล์หน้าย่อยอย่างปลอดภัย
                    $pagePath = __DIR__ . "/pages/{$page}.php";

                    if (file_exists($pagePath)) {
                        include $pagePath;
                    } else {
                        include __DIR__ . "/pages/dashboard.php";
                    }
                    ?>
                </div>
            </main>
        </div>

        <!-- แถบเมนูด้านซ้าย (Sidebar) -->
        <div class="drawer-side z-30">
            <label for="admin-drawer" class="drawer-overlay" aria-label="ปิดเมนู"></label>
            <div class="menu p-4 w-80 min-h-full bg-base-100 text-base-content flex flex-col justify-between shadow-xl border-r border-base-200 select-none">
                <div>
                    <!-- Logo Area -->
                    <div class="flex items-center gap-3 px-2 py-4 mb-6 border-b border-base-200">
                        <span class="text-3xl">⚙️</span>
                        <div>
                            <h2 class="font-black text-xl text-primary tracking-tight">ระบบจัดการเว็บ</h2>
                            <div class="badge badge-success badge-sm gap-1 mt-1 text-white py-2.5 font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Menu -->
                    <ul class="menu menu-md gap-1 p-0">
                        <li>
                            <a href="?page=dashboard" class="flex items-center gap-3 <?php echo $page === 'dashboard' ? 'active font-bold text-white bg-primary' : 'hover:bg-base-200'; ?>">
                                <i class="fa-solid fa-chart-line w-5 text-center text-lg"></i><span>Dashboard ภาพรวม</span>
                            </a>
                        </li>
                        <li>
                            <a href="?page=organization" class="flex items-center gap-3 <?php echo $page === 'organization' ? 'active font-bold text-white bg-primary' : 'hover:bg-base-200'; ?>">
                                <i class="fa-solid fa-building w-5 text-center text-lg"></i><span>ข้อมูลหน่วยงาน</span>
                            </a>
                        </li>
                        <li>
                            <a href="?page=theme_settings" class="flex items-center gap-3 <?php echo $page === 'theme_settings' ? 'active font-bold text-white bg-primary' : 'hover:bg-base-200'; ?>">
                                <i class="fa-solid fa-palette w-5 text-center text-lg"></i><span>ตั้งค่าสีและธีม</span>
                            </a>
                        </li>
                        <li>
                            <a href="?page=banners" class="flex items-center gap-3 <?php echo $page === 'banners' ? 'active font-bold text-white bg-primary' : 'hover:bg-base-200'; ?>">
                                <i class="fa-solid fa-images w-5 text-center text-lg"></i><span>จัดการแบนเนอร์</span>
                            </a>
                        </li>
                        <li>
                            <a href="?page=pr_images" class="flex items-center gap-3 <?php echo $page === 'pr_images' ? 'active font-bold text-white bg-primary' : 'hover:bg-base-200'; ?>">
                                <i class="fa-solid fa-photo-film w-5 text-center text-lg"></i><span>รูปประชาสัมพันธ์</span>
                            </a>
                        </li>
                        <li>
                            <a href="?page=directors" class="flex items-center gap-3 <?php echo $page === 'directors' ? 'active font-bold text-white bg-primary' : 'hover:bg-base-200'; ?>">
                                <i class="fa-solid fa-user-tie w-5 text-center text-lg"></i><span>จัดการบุคลากร</span>
                            </a>
                        </li>
                        <li>
                            <a href="?page=announcements" class="flex items-center gap-3 <?php echo $page === 'announcements' ? 'active font-bold text-white bg-primary' : 'hover:bg-base-200'; ?>">
                                <i class="fa-solid fa-bullhorn w-5 text-center text-lg"></i><span>จัดการประกาศ</span>
                            </a>
                        </li>
                        <li>
                            <a href="?page=files" class="flex items-center gap-3 <?php echo $page === 'files' ? 'active font-bold text-white bg-primary' : 'hover:bg-base-200'; ?>">
                                <i class="fa-solid fa-folder-open w-5 text-center text-lg"></i><span>จัดการไฟล์ทั่วไป</span>
                            </a>
                        </li>
                        <li>
                            <a href="?page=buttons" class="flex items-center gap-3 <?php echo $page === 'buttons' ? 'active font-bold text-white bg-primary' : 'hover:bg-base-200'; ?>">
                                <i class="fa-solid fa-link w-5 text-center text-lg"></i><span>จัดการเมนูข้าง/ปุ่ม</span>
                            </a>
                        </li>
                        <li>
                            <a href="?page=manage_pages" class="flex items-center gap-3 <?php echo $page === 'manage_pages' ? 'active font-bold text-white bg-primary' : 'hover:bg-base-200'; ?>">
                                <i class="fa-solid fa-file-pen w-5 text-center text-lg"></i><span>สร้าง/จัดการหน้าเว็บ</span>
                            </a>
                        </li>
                        
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
                            <div class="divider my-4 text-xs font-semibold text-base-content/40 tracking-wider">SUPER ADMIN</div>
                            <li>
                                <a href="?page=users" class="flex items-center gap-3 <?php echo $page === 'users' ? 'active font-bold text-white bg-primary' : 'hover:bg-base-200'; ?>">
                                    <i class="fa-solid fa-users-gear w-5 text-center text-lg"></i><span>จัดการผู้ใช้งาน</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- ออกจากระบบ (Sidebar Bottom) -->
                <div class="mt-auto pt-6 border-t border-base-200">
                    <a href="logout.php" class="btn btn-error btn-outline w-full gap-2 rounded-lg font-bold">
                        <i class="fa-solid fa-door-open"></i> ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- โหลดสคริปต์ jQuery -->
    <script src="../assets/js/jquery.min.js"></script>
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