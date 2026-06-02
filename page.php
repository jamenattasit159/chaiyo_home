<?php
require 'config.php';

// --- เปลี่ยนการรับค่าตรงนี้ ---
$id = 0;
if (isset($_GET['ref'])) {
    $id = decode_id($_GET['ref']); // ถอดรหัสกลับเป็นตัวเลข
} elseif (isset($_GET['id'])) {
    $id = intval($_GET['id']); // (เผื่อของเก่า) ยังรับ id แบบเดิมได้
}

$page = $pdo->query("SELECT * FROM custom_pages WHERE id = " . intval($id))->fetch();

// ถ้าไม่เจอหน้า ให้เด้งกลับ index
if (!$page) {
    header('Location: index.php');
    exit;
}

// ... (ส่วนที่เหลือเหมือนเดิมทุกประการ) ...
$orgInfo = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();
$sidebarButtons = $pdo->query("SELECT * FROM sidebar_buttons WHERE status='active' ORDER BY sort_order ASC")->fetchAll();

$logoData = $orgInfo['logo'] ?? '🏥';
$isLogoFile = false;
if (strpos($logoData, 'uploads/') !== false && file_exists($logoData)) {
    $isLogoFile = true;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page['title']); ?> - <?php echo htmlspecialchars($orgInfo['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <?php echo get_theme_style_block($pdo); ?>
    <style>
        /* page.php custom content styling */
        .content-card {
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: var(--shadow-md);
            min-height: 500px;
            border: 1px solid var(--border);
        }

        .content-card h1 {
            font-size: 28px;
            color: var(--text);
            border-bottom: 2px solid var(--border);
            padding-bottom: 15px;
            margin-bottom: 25px;
            font-weight: 800;
        }

        .page-body {
            font-size: 16px;
            color: var(--text);
            line-height: 1.8;
        }

        @media (max-width: 768px) {
            .content-card {
                padding: 24px;
            }
        }
    </style>
</head>

<body>

    <!-- Unified Glassmorphism Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand">
                <?php if ($isLogoFile): ?>
                    <img src="<?php echo $logoData; ?>" alt="Logo" class="navbar-logo-img">
                <?php else: ?>
                    <span style="font-size: 28px;"><?php echo $logoData; ?></span>
                <?php endif; ?>
                <span><?php echo sanitize($orgInfo['name'] ?? 'สถาบันอุตสาหกรรมสุขภาพ'); ?></span>
            </a>

            <ul class="nav-menu">
                <li><a href="index.php#home"><i class="fas fa-home"></i> หน้าแรก</a></li>
                <li><a href="index.php#pr"><i class="fas fa-bullhorn"></i> ประชาสัมพันธ์</a></li>
                <li><a href="index.php#directors"><i class="fas fa-users"></i> ผู้บริหาร</a></li>
                <li><a href="index.php#news"><i class="fas fa-newspaper"></i> ประกาศทั่วไป</a></li>
                <li><a href="admin/login.php"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a></li>
            </ul>
            <div class="hamburger" onclick="toggleMenu()">
                <span></span><span></span><span></span>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <div class="main-content-wrapper" style="padding: 40px 0;">
            <aside class="sidebar-left" data-aos="fade-right">
                <div class="sidebar-menu-box">
                    <h3 style="font-size: 18px; color: var(--text); margin-bottom: 20px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-list-ul" style="color: var(--primary);"></i> เมนู
                    </h3>
                    <?php foreach ($sidebarButtons as $btn): ?>
                        <?php 
                        // เช็คว่าลิงก์ภายนอกหรือไม่ เพื่อเปิดแท็บใหม่เฉพาะลิงก์ภายนอก
                        $is_external = (strpos($btn['link'], 'http://') === 0 || strpos($btn['link'], 'https://') === 0) && strpos($btn['link'], $_SERVER['HTTP_HOST']) === false;
                        ?>
                        <a href="<?php echo htmlspecialchars($btn['link']); ?>" class="sidebar-btn" <?php echo $is_external ? 'target="_blank"' : ''; ?>>
                            <span><?php echo htmlspecialchars($btn['name']); ?></span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>

            <main class="content-card" data-aos="fade-left" data-aos-delay="100">
                <h1><?php echo htmlspecialchars($page['title']); ?></h1>
                <div class="page-body">
                    <?php echo $page['content']; ?>
                </div>
                <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--border); font-size: 12px; color: var(--text-gray);">
                    แก้ไขล่าสุด: <?php echo date('d/m/Y H:i', strtotime($page['updated_at'])); ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Unified Premium Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>เกี่ยวกับเรา</h3>
                <p><?php echo sanitize($orgInfo['description'] ?? ''); ?></p>
            </div>
            <div class="footer-section">
                <h3>📞 ติดต่อเรา</h3>
                <ul>
                    <?php if ($orgInfo && $orgInfo['phone']): ?>
                        <li>
                            <a href="tel:<?php echo htmlspecialchars($orgInfo['phone']); ?>">
                                <i class="fas fa-phone" style="margin-right: 8px;"></i>
                                <?php echo sanitize($orgInfo['phone']); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($orgInfo && $orgInfo['email']): ?>
                        <li>
                            <a href="mailto:<?php echo htmlspecialchars($orgInfo['email']); ?>">
                                <i class="fas fa-envelope" style="margin-right: 8px;"></i>
                                <?php echo sanitize($orgInfo['email']); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($orgInfo && $orgInfo['address']): ?>
                        <li>
                            <a href="https://maps.google.com/?q=<?php echo urlencode($orgInfo['address']); ?>"
                                target="_blank">
                                <i class="fas fa-location-dot" style="margin-right: 8px;"></i>
                                <?php echo sanitize($orgInfo['address']); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-section">
                <h3>เมนูด่วน</h3>
                <ul>
                    <li><a href="index.php#home">หน้าแรก</a></li>
                    <li><a href="index.php#pr">ประชาสัมพันธ์</a></li>
                    <li><a href="index.php#news">ประกาศ</a></li>
                    <li><a href="admin/login.php">สำหรับเจ้าหน้าที่</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025
                <?php if ($isLogoFile): ?>
                    <img src="<?php echo $logoData; ?>" alt="Logo"
                        style="height: 30px; width: auto; vertical-align: middle; margin-right: 5px; border-radius: 4px;">
                <?php else: ?>
                    <span style="margin-right: 5px;"><?php echo $logoData; ?></span>
                <?php endif; ?>
                <?php echo sanitize($orgInfo['name'] ?? 'สถาบันอุตสาหกรรมสุขภาพ'); ?>. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- AOS & Mobile Navigation Toggle Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });

        function toggleMenu() {
            const menu = document.querySelector('.nav-menu');
            menu.classList.toggle('active');
        }
        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                document.querySelector('.nav-menu').classList.remove('active');
            });
        });
    </script>
</body>

</html>