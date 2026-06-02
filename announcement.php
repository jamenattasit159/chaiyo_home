<?php
require 'config.php';

// ดึง ID จาก URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($id)) {
    header('Location: index.php');
    exit;
}

// ดึงข้อมูลประกาศหลัก
try {
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ? AND status = 'active'");
    $stmt->execute([$id]);
    $announce = $stmt->fetch();

    if (!$announce) {
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}

// ดึงข้อมูลหน่วยงาน
$orgInfo = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();

// ดึงไฟล์แนบ (Multiple Attachments)
$attachments = $pdo->query("SELECT * FROM announcement_attachments WHERE announcement_id = " . (int)$id)->fetchAll(PDO::FETCH_ASSOC);

$logoData = $orgInfo['logo'] ?? '🏥';
$isLogoFile = false;
if (strpos($logoData, 'uploads/') !== false && file_exists($logoData)) {
    $isLogoFile = true;
}

// ฟังก์ชันสำหรับแปลงชื่อหมวดหมู่เป็นภาษาไทย (เอาไว้ใช้แสดงชื่อหัวข้อ)
function getCategoryName($category)
{
    return match ($category) {
        'general' => 'ประกาศทั่วไป',
        'download' => 'ดาวน์โหลด',
        'procurement' => 'จัดซื้อ-จัดจ้าง',
        'ita' => 'ประกาศ ITA',
        default => 'ประกาศ',
    };
}

// --- ส่วนที่แก้ไขใหม่: ดึงข้อมูลของทุกหมวดหมู่ ---
$categories_config = [
    'general' => 'ประกาศทั่วไป',
    'download' => 'ดาวน์โหลด',
    'procurement' => 'จัดซื้อ-จัดจ้าง',
    'ita' => 'ประกาศ ITA'
];

$all_cats_data = [];

foreach ($categories_config as $cat_key => $cat_name) {
    // ดึง 3 รายการล่าสุดของแต่ละหมวด (ยกเว้นเรื่องที่เปิดอยู่ปัจจุบัน)
    $stmt_cat = $pdo->prepare("SELECT id, title, created_at FROM announcements WHERE status='active' AND category = ? AND id != ? ORDER BY created_at DESC LIMIT 3");
    $stmt_cat->execute([$cat_key, $id]);
    $items = $stmt_cat->fetchAll();

    // ถ้าหมวดนั้นมีข้อมูล ให้เก็บใส่ array ไว้แสดงผล
    if (!empty($items)) {
        $all_cats_data[] = [
            'name' => $cat_name,
            'items' => $items
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($announce['title']); ?> - <?php echo htmlspecialchars($orgInfo['name'] ?? 'ประกาศ'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <?php echo get_theme_style_block($pdo); ?>
    <style>
        /* announcement.php custom layouts */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 30px;
            align-items: start;
        }

        /* Article styling */
        .article {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
        }

        .article h1 {
            font-size: clamp(20px, 3vw, 28px);
            margin-bottom: 20px;
            color: var(--text);
            line-height: 1.4;
            font-weight: 800;
        }

        .article-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            font-size: 13px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-gray);
        }

        .meta-item i {
            color: var(--primary);
        }

        .article-content {
            color: var(--text);
            font-size: 16px;
            line-height: 1.8;
        }

        .article-content p {
            margin-bottom: 15px;
        }

        /* Premium Quiet Download Button */
        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--lighter-green);
            color: var(--primary);
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none !important;
            font-weight: 700;
            font-size: 14px;
            margin: 15px 0 25px 0;
            transition: var(--transition);
            border: 1px solid var(--light-green);
        }

        .download-btn:hover {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px color-mix(in srgb, var(--primary) 20%, transparent);
        }

        /* Sidebar & category groups */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .category-group {
            margin-bottom: 20px;
        }

        .category-group:last-child {
            margin-bottom: 0;
        }

        .category-header {
            font-size: 13px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 12px;
            border-left: 3px solid var(--primary);
            padding: 6px 10px;
            background: var(--lighter-green);
            border-radius: 0 8px 8px 0;
        }

        .related-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            margin-left: 4px;
        }

        .related-item:last-child {
            border-bottom: none;
        }

        .related-link {
            color: var(--text);
            text-decoration: none !important;
            font-size: 14px;
            font-weight: 600;
            display: block;
            line-height: 1.5;
            transition: var(--transition);
        }

        .related-link:hover {
            color: var(--primary);
            transform: translateX(4px);
        }

        .related-date {
            font-size: 11px;
            color: var(--text-gray);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        @media (max-width: 992px) {
            .content-wrapper {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
            }

            .article {
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

    <div class="container">
        <div class="content-wrapper">
            <article class="article" data-aos="fade-up">
                <h1><?php echo sanitize($announce['title']); ?></h1>

                <div class="article-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('d/m/Y H:i', strtotime($announce['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span>ประกาศจากหน่วยงาน</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-layer-group"></i>
                        <span>หมวดหมู่: <?php echo getCategoryName($announce['category']); ?></span>
                    </div>
                </div>

                <?php
                // Logic for PDF Download Button
                if (!empty($announce['image'])) {
                    $filepath = 'uploads/files/' . $announce['image'];
                    $file_ext = strtolower(pathinfo($announce['image'], PATHINFO_EXTENSION));

                    // Check if file is PDF and exists
                    if ($file_ext === 'pdf' && file_exists($filepath)) {
                        echo '<a href="' . htmlspecialchars($filepath) . '" class="download-btn" target="_blank" download>';
                        echo '    <i class="fas fa-file-pdf"></i> ดาวน์โหลดไฟล์แนบ (PDF)';
                        echo '</a>';
                    }
                }
                ?>

                <div class="article-content">
                    <?php echo nl2br(sanitize($announce['content'])); ?>
                </div>

                <?php if (!empty($attachments)): ?>
                    <div style="margin-top: 40px; padding-top: 25px; border-top: 1px solid var(--border);">
                        <h3 style="font-size: 18px; margin-bottom: 15px; color: var(--text); font-weight: 800; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-paperclip" style="color: var(--primary);"></i> เอกสารแนบ
                        </h3>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($attachments as $att): ?>
                                <a href="uploads/files/<?php echo htmlspecialchars($att['file_path']); ?>" target="_blank" class="download-btn" style="margin: 0; background-color: var(--lighter-green); color: var(--text); border: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; border-radius: 12px; padding: 14px 20px;">
                                    <span><i class="fas fa-file-alt" style="margin-right: 10px; color: var(--primary);"></i> <?php echo htmlspecialchars($att['file_name'] ?: 'ดาวน์โหลดเอกสาร'); ?></span>
                                    <i class="fas fa-download" style="color: var(--text-gray);"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php
                // Display image if it's not a PDF and is present
                if (!empty($announce['image'])) {
                    $filepath = 'uploads/files/' . $announce['image'];
                    $file_ext = strtolower(pathinfo($announce['image'], PATHINFO_EXTENSION));
                    if ($file_ext !== 'pdf' && file_exists($filepath)) {
                        echo '<div style="text-align: center; margin-top: 30px;">';
                        echo '<img src="' . htmlspecialchars($filepath) . '" alt="ภาพประกอบประกาศ" style="max-width: 100%; height: auto; border-radius: 16px; border: 1px solid var(--border); box-shadow: var(--shadow-sm);">';
                        echo '</div>';
                    }
                }
                ?>
            </article>

            <aside class="sidebar" data-aos="fade-left" data-aos-delay="100">
                <div class="sidebar-box">
                    <h3><i class="fas fa-phone" style="margin-right: 8px;"></i>ติดต่อเรา</h3>
                    <div class="contact-info">
                        <?php if ($orgInfo && $orgInfo['phone']): ?>
                            <div style="margin-bottom: 12px;">
                                <strong>📞 โทรศัพท์:</strong>
                                <a href="tel:<?php echo htmlspecialchars($orgInfo['phone']); ?>" style="color: var(--primary); text-decoration: none;">
                                    <?php echo sanitize($orgInfo['phone']); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($orgInfo && $orgInfo['email']): ?>
                            <div style="margin-bottom: 12px;">
                                <strong>✉️ อีเมล:</strong>
                                <a href="mailto:<?php echo htmlspecialchars($orgInfo['email']); ?>" style="color: var(--primary); text-decoration: none;">
                                    <?php echo sanitize($orgInfo['email']); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($orgInfo && $orgInfo['address']): ?>
                            <div>
                                <strong>📍 ที่อยู่:</strong>
                                <p style="margin-top: 4px; line-height: 1.5;"><?php echo sanitize($orgInfo['address']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sidebar-box">
                    <h3><i class="fas fa-newspaper" style="margin-right: 8px;"></i>ประกาศทั้งหมด</h3>

                    <?php if (!empty($all_cats_data)): ?>
                        <?php foreach ($all_cats_data as $group): ?>
                            <div class="category-group">
                                <div class="category-header">
                                    <?php echo $group['name']; ?>
                                </div>

                                <?php foreach ($group['items'] as $item): ?>
                                    <div class="related-item">
                                        <a href="announcement.php?id=<?php echo $item['id']; ?>" class="related-link">
                                            <?php echo sanitize($item['title']); ?>
                                        </a>
                                        <div class="related-date">
                                            <i class="far fa-clock" style="font-size: 10px;"></i>
                                            <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-gray); font-size: 13px;">ยังไม่มีข้อมูลประกาศ</p>
                    <?php endif; ?>
                </div>
            </aside>
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
                            <a href="https://maps.google.com/?q=<?php echo urlencode($orgInfo['address']); ?>" target="_blank">
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
                    <img src="<?php echo $logoData; ?>" alt="Logo" style="height: 30px; width: auto; vertical-align: middle; margin-right: 5px; border-radius: 4px;">
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
    </script>
</body>

</html>