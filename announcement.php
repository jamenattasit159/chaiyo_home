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
    <title><?php echo htmlspecialchars($announce['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <?php echo get_theme_style_block($pdo); ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #059669;
            --secondary: #047857;
            --accent: #10b981;
            --light-green: #d1fae5;
            --lighter-green: #f0fdf4;
            --text: #1f2937;
            --text-gray: #6b7280;
            --white: #ffffff;
            --border: #e5e7eb;
            --shadow-sm: 0 2px 8px rgba(5, 150, 105, 0.08);
            --shadow-md: 0 4px 12px rgba(5, 150, 105, 0.12);
            --shadow-lg: 0 8px 24px rgba(5, 150, 105, 0.15);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background: var(--light);
            line-height: 1.6;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .navbar h1 {
            font-size: 22px;
            margin: 0;
        }

        .back-link {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            transition: all 0.3s;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Content Layout */
        .content-wrapper {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            padding: 40px 0;
        }

        /* Main Article */
        .article {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .article h1 {
            font-size: 28px;
            margin-bottom: 15px;
            color: #2c3e50;
            line-height: 1.4;
        }

        .article-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border);
            flex-wrap: wrap;
            font-size: 14px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .meta-item i {
            color: var(--primary);
            font-size: 16px;
        }

        /* Download Button Style */
        .download-btn {
            display: inline-block;
            background: #2563eb;
            /* Blue color for download */
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin: 25px 0 15px 0;
            transition: background 0.3s;
        }

        .download-btn:hover {
            background: #1d4ed8;
        }

        .article-content {
            color: #555;
            font-size: 15px;
            line-height: 1.8;
        }

        .article-content p {
            margin-bottom: 15px;
        }

        .article-content img {
            max-width: 100%;
            height: auto;
            margin: 20px 0;
            border-radius: 8px;
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .sidebar-box h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary);
            color: #2c3e50;
            font-size: 16px;
        }

        .contact-info p {
            margin-bottom: 12px;
            font-size: 13px;
            line-height: 1.6;
        }

        .contact-info strong {
            color: #2c3e50;
            display: block;
            margin-bottom: 5px;
        }

        .contact-info a {
            color: var(--primary);
            text-decoration: none;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        /* Styles for Grouped Sidebar Items */
        .category-group {
            margin-bottom: 25px;
        }

        .category-group:last-child {
            margin-bottom: 0;
        }

        .category-header {
            font-size: 14px;
            font-weight: bold;
            color: var(--secondary);
            margin-bottom: 10px;
            border-left: 3px solid var(--secondary);
            padding-left: 8px;
            background: #fff3e0;
            padding: 5px 8px;
            border-radius: 0 4px 4px 0;
        }

        .related-item {
            padding: 10px 0;
            border-bottom: 1px dashed var(--border);
            margin-left: 5px;
        }

        .related-item:last-child {
            border-bottom: none;
        }

        .related-link {
            color: #444;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: block;
            line-height: 1.4;
            transition: color 0.3s;
        }

        .related-link:hover {
            color: var(--primary);
        }

        .related-date {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }

        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            padding: 40px 0 20px;
            margin-top: 60px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .footer-section h4 {
            color: var(--primary);
            margin-bottom: 15px;
        }

        .footer-section p,
        .footer-section a {
            font-size: 13px;
            color: #ecf0f1;
            line-height: 1.8;
        }

        .footer-section a {
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: var(--primary);
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #bdc3c7;
            font-size: 13px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .navbar h1 {
                font-size: 18px;
            }

            .back-link {
                width: 100%;
                justify-content: center;
            }

            .content-wrapper {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 20px 0;
            }

            .article {
                padding: 20px;
            }

            .article h1 {
                font-size: 22px;
            }

            .article-meta {
                flex-direction: column;
                gap: 10px;
            }

            .article-content {
                font-size: 14px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1>🏥 <?php echo sanitize($orgInfo['name'] ?? 'สถาบัน'); ?></h1>
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                <span>กลับหน้าแรก</span>
            </a>
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
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                        <h3 style="font-size: 18px; margin-bottom: 15px; color: #333;"><i class="fas fa-paperclip"></i> เอกสารแนบ</h3>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($attachments as $att): ?>
                                <a href="uploads/files/<?php echo htmlspecialchars($att['file_path']); ?>" target="_blank" class="download-btn" style="margin:0; background-color: #f3f4f6; color: #1f2937; border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
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
                        echo '<img src="' . htmlspecialchars($filepath) . '" alt="ภาพประกอบประกาศ">';
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
                            <div>
                                <strong>📞 โทรศัพท์:</strong>
                                <a href="tel:<?php echo htmlspecialchars($orgInfo['phone']); ?>">
                                    <?php echo sanitize($orgInfo['phone']); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($orgInfo && $orgInfo['email']): ?>
                            <div>
                                <strong>✉️ อีเมล:</strong>
                                <a href="mailto:<?php echo htmlspecialchars($orgInfo['email']); ?>">
                                    <?php echo sanitize($orgInfo['email']); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($orgInfo && $orgInfo['address']): ?>
                            <div>
                                <strong>📍 ที่อยู่:</strong>
                                <p><?php echo sanitize($orgInfo['address']); ?></p>
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
                        <p style="color: #999; font-size: 13px;">ยังไม่มีข้อมูลประกาศ</p>
                    <?php endif; ?>

                </div>
            </aside>
        </div>
    </div>

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
                        <li><a href="tel:<?php echo htmlspecialchars($orgInfo['phone']); ?>">📱
                                <?php echo sanitize($orgInfo['phone']); ?></a></li>
                    <?php endif; ?>
                    <?php if ($orgInfo && $orgInfo['email']): ?>
                        <li><a href="mailto:<?php echo htmlspecialchars($orgInfo['email']); ?>">✉️
                                <?php echo sanitize($orgInfo['email']); ?></a></li>
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
     
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
    </script>
</body>

</html>