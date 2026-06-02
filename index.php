<?php
require 'config.php';

// Function to get attachments
function getAnnouncementAttachments($pdo, $id) {
    try {
        return $pdo->query("SELECT * FROM announcement_attachments WHERE announcement_id = " . (int)$id)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// Function to split content for better display
function getContentExcerpt($text, $limit = 200) {
    $text = strip_tags($text);
    if (mb_strlen($text) > $limit) {
        return mb_substr($text, 0, $limit) . '...';
    }
    return $text;
}

// 1. ดึงข้อมูลแบนเนอร์
$banners = $pdo->query("SELECT * FROM banners WHERE status='active' ORDER BY created_at DESC")->fetchAll();

// 2. ดึงข้อมูลผู้บริหาร (สำหรับฝั่งซ้าย)
$director = $pdo->query("SELECT * FROM directors WHERE status='active' AND category='director' LIMIT 1")->fetch();

// 3. ดึงข้อมูลรูปภาพประชาสัมพันธ์ (สำหรับฝั่งขวา) — กรองเฉพาะ category = 'pr_activity'
$pr_images = $pdo->query("SELECT * FROM files WHERE status='active' AND category='pr_activity' AND file_type IN ('jpg', 'jpeg', 'png', 'gif', 'webp') ORDER BY created_at DESC LIMIT 10")->fetchAll();

// 4. ดึงข้อมูลประกาศ (สำหรับส่วนล่างสุด)
$announcements = $pdo->query("SELECT * FROM announcements WHERE status='active' ORDER BY created_at DESC LIMIT 4")->fetchAll();

// ดึงข้อมูลหน่วยงาน
$orgInfo = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();

// ดึงข้อมูลประกาศตามหมวดหมู่
$generals = $pdo->query("SELECT * FROM announcements WHERE status='active' AND category='general' ORDER BY created_at DESC LIMIT 6")->fetchAll();
$downloads = $pdo->query("SELECT * FROM announcements WHERE status='active' AND category='download' ORDER BY created_at DESC LIMIT 6")->fetchAll();
$procurements = $pdo->query("SELECT * FROM announcements WHERE status='active' AND category='procurement' ORDER BY created_at DESC LIMIT 6")->fetchAll();
$itas = $pdo->query("SELECT * FROM announcements WHERE status='active' AND category='ita' ORDER BY created_at DESC LIMIT 6")->fetchAll();

// --- [แก้ไข] ใช้ตรรกะเดียวกับ page.php เพื่อดึงเมนูปุ่ม ---
$sidebarButtons = [];
try {
    // ดึงจาก sidebar_buttons โดยตรง (รวมทั้งลิ้งค์ภายนอก และลิ้งค์เพจภายในที่ถูกบันทึกไว้แล้ว)
    $sidebarButtons = $pdo->query("SELECT * FROM sidebar_buttons WHERE status='active' ORDER BY sort_order ASC")->fetchAll();
} catch (Exception $e) {
    // กรณีตารางยังไม่ถูกสร้าง
}
// -----------------------------------------------------

// --- ส่วนจัดการ LOGO และ FAVICON ---
$logoData = $orgInfo['logo'] ?? '🏥';
$isLogoFile = false;
$faviconHtml = '';

if (strpos($logoData, 'uploads/') !== false && file_exists($logoData)) {
    $isLogoFile = true;
    $ext = strtolower(pathinfo($logoData, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'ico' => 'image/x-icon',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        default => 'image/x-icon'
    };
    $faviconHtml = '<link rel="shortcut icon" href="' . $logoData . '" type="' . $mime . '">';
} else {
    $faviconHtml = '<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>' . $logoData . '</text></svg>">';
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $orgInfo['name'] ?? 'สำนักงานสาธารณะสุขอ่างทอง'; ?></title>

    <?php echo $faviconHtml; ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <?php echo get_theme_style_block($pdo); ?>

    <style>
        /* CSS Sidebar Menu */
        .sidebar-menu-box {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
        }

        .sidebar-btn {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 8px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            color: #495057;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .sidebar-btn:hover {
            background-color: var(--primary, #059669);
            color: #fff;
            transform: translateX(5px);
            border-color: var(--primary, #059669);
        }

        .sidebar-btn:last-child {
            margin-bottom: 0;
        }

        .sidebar-btn i {
            font-size: 12px;
            opacity: 0.7;
        }

        .sidebar-btn span {
            font-weight: 500;
        }
    </style>
</head>

<body>
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
                <li><a href="#home"><i class="fas fa-home"></i> หน้าแรก</a></li>
                <li><a href="#pr"><i class="fas fa-bullhorn"></i> ประชาสัมพันธ์</a></li>
                <li><a href="#directors"><i class="fas fa-users"></i> ผู้บริหาร</a></li>
                <li><a href="#news"><i class="fas fa-newspaper"></i> ประกาศทั่วไป</a></li>
                <li><a href="admin/login.php"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a></li>
            </ul>
            <div class="hamburger" onclick="toggleMenu()">
                <span></span><span></span><span></span>
            </div>
        </div>
    </nav>

    <section class="banner-section" id="home">

        <?php
        $active_banners = $pdo->query("SELECT * FROM banners WHERE status='active' ORDER BY created_at DESC")->fetchAll();
        ?>

        <?php if (!empty($active_banners)): ?>
           

            <div class="banner-slider-wrapper" data-aos="zoom-in" data-aos-duration="1000">
                <div class="banner-track">
                    <?php foreach ($active_banners as $b): ?>
                        <img src="uploads/banners/<?php echo sanitize($b['image']); ?>" class="banner-item" alt="Banner">
                    <?php endforeach; ?>
                    <?php foreach ($active_banners as $b): ?>
                        <img src="uploads/banners/<?php echo sanitize($b['image']); ?>" class="banner-item" alt="Banner">
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div style="color: white; font-size: 20px; text-align: center; padding: 80px; background: #ea580c;">
                <i class="fas fa-image" style="font-size: 48px; margin-bottom: 15px; display: block; opacity: 0.5;"></i>
                ยังไม่มีภาพประชาสัมพันธ์
            </div>
        <?php endif; ?>

    </section>

    <div class="container">
        <div class="main-content-wrapper">

            <!-- Sidebar Left (Streamlined & Compact) -->
            <aside class="sidebar-left" data-aos="fade-right" data-aos-delay="100">
             
                <?php if ($director): ?>
                    <div class="director-card" id="directors">
                        <?php if ($director['image']): ?>
                            <img src="uploads/directors/<?php echo sanitize($director['image']); ?>"
                                alt="<?php echo sanitize($director['name']); ?>">
                        <?php else: ?>
                            <div
                                style="width: 100%; height: 250px; background: #eee; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user-tie" style="font-size: 60px; color: #bbb;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="director-info">
                            <h3><?php echo sanitize($director['name']); ?></h3>
                            <p><?php echo sanitize($director['position']); ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="director-card" id="directors" style="padding: 20px; color: #999;">ยังไม่มีข้อมูลผู้บริหาร</div>
                <?php endif; ?>

                <a href="personnel.php" class="btn-all-staff">
                    <i class="fas fa-users"></i> บุคลากรทั้งหมด
                </a>

                <?php if (!empty($sidebarButtons)): ?>
                    <aside class="sidebar-menu-box">
                        <h3 style="font-size: 18px; color: var(--text); margin-bottom: 15px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
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
                    </aside>
                <?php endif; ?>
               
                <div class="sidebar-banners" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
                            
                    <a href="https://saraban.moph.go.th/archive/login.jsp" target="_blank" title="ลิงค์รูปที่ 1">
                        <img src="uploads\m-edoc.jpg" 
                             alt="Banner 1" 
                             style="width: 100%; height: auto; border-radius: 6px; margin-bottom: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.2s;">
                    </a>

                    <a href="https://atg-health.moph.go.th/payslip/" target="_blank" title="ลิงค์รูปที่ 2">
                        <img src="uploads\m-epayslip.jpg" 
                             alt="Banner 2" 
                             style="width: 100%; height: auto; border-radius: 6px; margin-bottom: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.2s;">
                    </a>

                    <a href="https://atg-health.moph.go.th/payslipemp/" target="_blank" title="ลิงค์รูปที่ 3">
                        <img src="uploads\m-epayslip2.jpg" 
                             alt="Banner 3" 
                             style="width: 100%; height: auto; border-radius: 6px; margin-bottom: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.2s;">
                    </a>

                    <a href="index.php#news" target="_blank" title="ลิงค์รูปที่ 4">
                        <img src="uploads\Untitled-1_0.jpg" 
                             alt="Banner 4" 
                             style="width: 100%; height: auto; border-radius: 6px; margin-bottom: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.2s;">
                    </a>

                </div>

            </aside>

            <!-- Main Content Area: Announcements (Primary Focus above the fold) -->
            <main class="main-right" id="news" data-aos="fade-left" data-aos-delay="200">
                <div class="section-header" style="margin-bottom: 24px;">
                    <span><i class="fas fa-newspaper text-primary"></i> ศูนย์ข่าวสารและบริการ</span>
                </div>

                <div class="tabs-wrapper" style="margin-bottom: 25px;">
                    <button class="tab-btn active" onclick="openTab(event, 'tab-general')">
                        <i class="fas fa-bullhorn"></i> ประกาศทั่วไป
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'tab-download')">
                        <i class="fas fa-download"></i> ดาวน์โหลด
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'tab-procurement')">
                        <i class="fas fa-shopping-cart"></i> จัดซื้อ-จัดจ้าง
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'tab-ita')">
                        <i class="fas fa-balance-scale"></i> ประกาศ ITA
                    </button>
                </div>

                <!-- Tab: General Announcements -->
                <div id="tab-general" class="tab-content active">
                    <div class="list-container">
                        <?php if (!empty($generals)): ?>
                            <?php foreach ($generals as $item): ?>
                                <div class="list-item">
                                    <div class="list-date">
                                        <span class="day"><?php echo date('d', strtotime($item['created_at'])); ?></span>
                                        <span class="month"><?php echo date('M', strtotime($item['created_at'])); ?></span>
                                    </div>
                                    <div class="list-info">
                                        <a href="announcement.php?id=<?php echo $item['id']; ?>" class="list-title">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </a>
                                        <div style="font-size:14px; color:#555; margin-top:5px; margin-bottom:8px;">
                                            <?php echo getContentExcerpt($item['content'], 90); ?>
                                        </div>

                                        <div class="list-meta">
                                            <i class="far fa-clock"></i>
                                            <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                                        </div>

                                        <?php 
                                        $atts = getAnnouncementAttachments($pdo, $item['id']);
                                        if (!empty($atts)): 
                                        ?>
                                            <div style="margin-top:10px; border-top:1px dashed #eee; padding-top:5px;">
                                                <?php foreach ($atts as $att): ?>
                                                    <a href="uploads/files/<?php echo htmlspecialchars($att['file_path']); ?>" target="_blank" style="display:inline-block; margin-right:10px; font-size:13px; color:#059669; text-decoration:none;">
                                                        <i class="fas fa-paperclip"></i> <?php echo htmlspecialchars($att['file_name'] ?: 'File'); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <a href="announcement.php?id=<?php echo $item['id']; ?>" class="btn-read">
                                        อ่านต่อ <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">ยังไม่มีข้อมูลในหมวดนี้</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab: Downloads -->
                <div id="tab-download" class="tab-content">
                    <div class="list-container">
                        <?php if (!empty($downloads)): ?>
                            <?php foreach ($downloads as $item): ?>
                                <div class="list-item">
                                    <div class="list-date">
                                        <span class="day"><?php echo date('d', strtotime($item['created_at'])); ?></span>
                                        <span class="month"><?php echo date('M', strtotime($item['created_at'])); ?></span>
                                    </div>
                                    <div class="list-info">
                                        <a href="announcement.php?id=<?php echo $item['id']; ?>" class="list-title">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </a>
                                        <div style="font-size:14px; color:#555; margin-top:5px; margin-bottom:8px;">
                                            <?php echo getContentExcerpt($item['content'], 90); ?>
                                        </div>

                                        <div class="list-meta">
                                            <i class="far fa-clock"></i>
                                            <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                                        </div>

                                        <?php 
                                        $atts = getAnnouncementAttachments($pdo, $item['id']);
                                        if (!empty($atts)): 
                                        ?>
                                            <div style="margin-top:10px; border-top:1px dashed #eee; padding-top:5px;">
                                                <?php foreach ($atts as $att): ?>
                                                    <a href="uploads/files/<?php echo htmlspecialchars($att['file_path']); ?>" target="_blank" style="display:inline-block; margin-right:10px; font-size:13px; color:#059669; text-decoration:none;">
                                                        <i class="fas fa-paperclip"></i> <?php echo htmlspecialchars($att['file_name'] ?: 'File'); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <a href="announcement.php?id=<?php echo $item['id']; ?>" class="btn-read">
                                        อ่านต่อ <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">ยังไม่มีข้อมูลในหมวดนี้</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab: Procurements -->
                <div id="tab-procurement" class="tab-content">
                    <div class="list-container">
                        <?php if (!empty($procurements)): ?>
                            <?php foreach ($procurements as $item): ?>
                                <div class="list-item">
                                    <div class="list-date">
                                        <span class="day"><?php echo date('d', strtotime($item['created_at'])); ?></span>
                                        <span class="month"><?php echo date('M', strtotime($item['created_at'])); ?></span>
                                    </div>
                                    <div class="list-info">
                                        <a href="announcement.php?id=<?php echo $item['id']; ?>" class="list-title">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </a>
                                        <div style="font-size:14px; color:#555; margin-top:5px; margin-bottom:8px;">
                                            <?php echo getContentExcerpt($item['content'], 90); ?>
                                        </div>

                                        <div class="list-meta">
                                            <i class="far fa-clock"></i>
                                            <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                                        </div>

                                        <?php 
                                        $atts = getAnnouncementAttachments($pdo, $item['id']);
                                        if (!empty($atts)): 
                                        ?>
                                            <div style="margin-top:10px; border-top:1px dashed #eee; padding-top:5px;">
                                                <?php foreach ($atts as $att): ?>
                                                    <a href="uploads/files/<?php echo htmlspecialchars($att['file_path']); ?>" target="_blank" style="display:inline-block; margin-right:10px; font-size:13px; color:#059669; text-decoration:none;">
                                                        <i class="fas fa-paperclip"></i> <?php echo htmlspecialchars($att['file_name'] ?: 'File'); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <a href="announcement.php?id=<?php echo $item['id']; ?>" class="btn-read">
                                        อ่านต่อ <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">ยังไม่มีข้อมูลประกาศจัดซื้อจัดจ้าง</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab: ITA -->
                <div id="tab-ita" class="tab-content">
                    <div class="list-container">
                        <?php if (!empty($itas)): ?>
                            <?php foreach ($itas as $item): ?>
                                <div class="list-item">
                                    <div class="list-date">
                                        <span class="day"><?php echo date('d', strtotime($item['created_at'])); ?></span>
                                        <span class="month"><?php echo date('M', strtotime($item['created_at'])); ?></span>
                                    </div>
                                    <div class="list-info">
                                        <a href="announcement.php?id=<?php echo $item['id']; ?>" class="list-title">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </a>
                                        <div class="list-meta">
                                            <i class="far fa-clock"></i>
                                            <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                                        </div>

                                        <?php 
                                        $atts = getAnnouncementAttachments($pdo, $item['id']);
                                        if (!empty($atts)): 
                                        ?>
                                            <div style="margin-top:10px; border-top:1px dashed #eee; padding-top:5px;">
                                                <?php foreach ($atts as $att): ?>
                                                    <a href="uploads/files/<?php echo htmlspecialchars($att['file_path']); ?>" target="_blank" style="display:inline-block; margin-right:10px; font-size:13px; color:#059669; text-decoration:none;">
                                                        <i class="fas fa-paperclip"></i> <?php echo htmlspecialchars($att['file_name'] ?: 'File'); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <a href="announcement.php?id=<?php echo $item['id']; ?>" class="btn-read">
                                        อ่านต่อ <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">ยังไม่มีข้อมูลประกาศ ITA</div>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- PR Section: Widescreen Horizontal Grid Gallery (Below Main Grid) -->
    <section class="pr-section" id="pr" style="padding: 60px 0; background: var(--white); border-top: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" style="border-left: 6px solid var(--primary); padding-left: 20px; background: transparent; box-shadow: none; margin-bottom: 30px;">
                <span>📸 ข่าวสารกิจกรรมประชาสัมพันธ์</span>
            </div>

            <div class="pr-list-large" data-aos="fade-up" data-aos-delay="200">
                <?php if (!empty($pr_images)): ?>
                    <?php foreach ($pr_images as $img): ?>
                        <a href="uploads/files/<?php echo htmlspecialchars($img['filepath']); ?>" target="_blank"
                            class="pr-card-large">
                            <div class="pr-date-badge">
                                <?php echo date('Y', strtotime($img['created_at'])); ?>
                            </div>
                            <div class="pr-image-wrapper-large">
                                <img src="uploads/files/<?php echo htmlspecialchars($img['filepath']); ?>"
                                    alt="<?php echo htmlspecialchars($img['filename']); ?>">
                            </div>
                            <div class="pr-content-large">
                                <div class="pr-title-large"><?php echo htmlspecialchars($img['filename']); ?></div>
                                <div style="font-size: 13px; color: #888;">
                                    <i class="far fa-calendar-alt"></i> โพสต์เมื่อ:
                                    <?php echo date('d/m/Y', strtotime($img['created_at'])); ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div
                        style="text-align: center; padding: 60px; border: 2px dashed #eee; background: white; border-radius: 8px; grid-column: 1 / -1;">
                        <i class="fas fa-images" style="font-size: 50px; color: #ddd;"></i>
                        <p style="color: #999; margin-top: 15px; font-size: 16px;">ยังไม่มีภาพกิจกรรมประชาสัมพันธ์</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Secondary Widgets Panel (Bottom 3-Column horizontal cluster) -->
    <?php
    $counterFile = 'counter.txt';
    if (!file_exists($counterFile)) {
        file_put_contents($counterFile, '0');
    }
    $count = (int)file_get_contents($counterFile);
    if (!isset($_SESSION['visited_before'])) {
        $_SESSION['visited_before'] = true;
        $count++;
        file_put_contents($counterFile, $count);
    }
    $countStr = str_pad($count, 4, '0', STR_PAD_LEFT);
    $digits = str_split($countStr);
    ?>

    <section class="bottom-widgets-section" style="padding: 60px 0; background: var(--lighter-green); border-top: 1px solid var(--border);">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px;">
                
                <!-- Column 1: Sub-agencies list -->
                <div class="sidebar-box-styled" style="background: #fff; border-radius: 20px; overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid var(--border); display: flex; flex-direction: column;">
                    <div style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 16px 20px; color: #fff; font-weight: 800; font-size: 16px;">
                        <i class="fas fa-network-wired" style="margin-right: 8px;"></i> หน่วยงานในสังกัด
                    </div>
                    <div style="padding: 20px; font-size: 14px; line-height: 1.8; color: #334155; flex-grow: 1;">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            <div style="display: flex; gap: 8px;"><span style="color: var(--primary); font-family: monospace; font-weight: 700;">10782</span> <span>รพ.ไชโย</span></div>
                            <div style="display: flex; gap: 8px;"><span style="color: var(--primary); font-family: monospace; font-weight: 700;">01368</span> <span>รพ.สต.จรเข้ร้อง</span></div>
                            <div style="display: flex; gap: 8px;"><span style="color: var(--primary); font-family: monospace; font-weight: 700;">01369</span> <span>รพ.สต.ไชยภูมิ</span></div>
                            <div style="display: flex; gap: 8px;"><span style="color: var(--primary); font-family: monospace; font-weight: 700;">01370</span> <span>รพ.สต.ชัยฤทธิ์</span></div>
                            <div style="display: flex; gap: 8px;"><span style="color: var(--primary); font-family: monospace; font-weight: 700;">01371</span> <span>รพ.สต.เทวราช</span></div>
                            <div style="display: flex; gap: 8px;"><span style="color: var(--primary); font-family: monospace; font-weight: 700;">01372</span> <span>รพ.สต.ราชสถิตย์</span></div>
                            <div style="display: flex; gap: 8px;"><span style="color: var(--primary); font-family: monospace; font-weight: 700;">01373</span> <span>รพ.สต.หลักฟ้า</span></div>
                            <div style="display: flex; gap: 8px;"><span style="color: var(--primary); font-family: monospace; font-weight: 700;">01374</span> <span>รพ.สต.ชะไว</span></div>
                            <div style="display: flex; gap: 8px;"><span style="color: var(--primary); font-family: monospace; font-weight: 700;">01375</span> <span>รพ.สต.บ้านเบิก</span></div>
                            <div style="display: flex; gap: 8px;"><span style="color: var(--primary); font-family: monospace; font-weight: 700;">01376</span> <span>รพ.สต.ตรีณรงค์</span></div>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Visitor counter & Quick Info -->
                <div class="sidebar-box-styled" style="background: #fff; border-radius: 20px; overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid var(--border); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 16px 20px; color: #fff; font-weight: 800; font-size: 16px;">
                            <i class="fas fa-users" style="margin-right: 8px;"></i> สถิติผู้เข้าชมเว็บไซต์
                        </div>
                        <div style="padding: 20px 20px 10px 20px;">
                            <div style="display: flex; gap: 6px; justify-content: flex-start; align-items: center; margin-bottom: 15px;">
                                <?php foreach ($digits as $digit): ?>
                                    <span style="background-color: #0f172a; color: #fff; font-family: 'Outfit', monospace; font-size: 24px; font-weight: 700; padding: 6px 12px; border-radius: 6px; border: 1px solid #1e293b; box-shadow: inset 0 2px 4px rgba(0,0,0,0.8); text-shadow: 0 1px 2px rgba(0,0,0,0.8);">
                                        <?php echo $digit; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="padding: 0 20px 20px 20px; border-top: 1px dashed #e2e8f0; pt: 15px;">
                        <h4 style="font-weight: 800; font-size: 14px; margin-bottom: 8px; color: var(--text);"><i class="fas fa-address-book text-primary"></i> ข้อมูลติดต่อเร่งด่วน</h4>
                        <?php if ($orgInfo && $orgInfo['phone']): ?>
                            <p style="font-size: 13px; color: var(--text-gray); margin-bottom: 4px;"><strong>โทรศัพท์:</strong> <?php echo sanitize($orgInfo['phone']); ?></p>
                        <?php endif; ?>
                        <?php if ($orgInfo && $orgInfo['email']): ?>
                            <p style="font-size: 13px; color: var(--text-gray);"><strong>อีเมล:</strong> <?php echo sanitize($orgInfo['email']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Column 3: Contact & Google Map -->
                <div class="sidebar-box-styled" style="background: #fff; border-radius: 20px; overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid var(--border); display: flex; flex-direction: column;">
                    <div style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 16px 20px; color: #fff; font-weight: 800; font-size: 16px;">
                        <i class="fas fa-map-marker-alt" style="margin-right: 8px;"></i> แผนที่ & ที่ตั้งหน่วยงาน
                    </div>
                    <div style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <?php if ($orgInfo && $orgInfo['address']): ?>
                            <p style="font-size: 13px; color: var(--text-gray); line-height: 1.6; margin-bottom: 12px;">
                                <?php echo sanitize($orgInfo['address']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($orgInfo['google_map'])): ?>
                            <div class="map-container"
                                style="position: relative; padding-bottom: 50%; height: 0; overflow: hidden; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: var(--shadow-sm);">
                                <?php echo $orgInfo['google_map']; ?>
                                <style>
                                    .map-container iframe {
                                        position: absolute;
                                        top: 0;
                                        left: 0;
                                        width: 100% !important;
                                        height: 100% !important;
                                        border: 0;
                                    }
                                </style>
                            </div>
                            <div style="text-align: center; margin-top: 8px;">
                                <a href="https://www.google.com/maps" target="_blank"
                                    style="font-size: 12px; color: var(--primary); text-decoration: none; font-weight: bold;">
                                    <i class="fas fa-external-link-alt"></i> เปิดใน Google Maps
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </section>

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
                    <li><a href="#home">หน้าแรก</a></li>
                    <li><a href="#pr">ประชาสัมพันธ์</a></li>
                    <li><a href="#news">ประกาศ</a></li>
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
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>
</body>

</html>