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
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page['title']); ?> - <?php echo htmlspecialchars($orgInfo['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* CSS พื้นฐาน เหมือนหน้า Index */
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
    </style>
    <?php echo get_theme_style_block($pdo); ?>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light);
            color: var(--text);
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

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-btn {
            color: white;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Layout */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
            align-items: start;
        }

        /* Content Area */
        .content-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            min-height: 500px;
        }

        .content-card h1 {
            font-size: 28px;
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .page-body {
            font-size: 16px;
            color: #444;
            line-height: 1.8;
        }

        /* Sidebar Buttons */
        .sidebar-menu-box {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sidebar-btn {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            color: #333;
            padding: 15px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border-left: 5px solid var(--primary);
            transition: all 0.3s;
        }

        .sidebar-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateX(5px);
            border-left-color: white;
        }

        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            padding: 40px 0;
            margin-top: 60px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }

            .sidebar-menu-box {
                order: 2;
            }

            .content-card {
                order: 1;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="navbar-container">
            <h1 style="font-size: 20px; margin:0;"><?php echo htmlspecialchars($orgInfo['name']); ?></h1>
            <a href="index.php" class="back-btn"><i class="fas fa-home"></i> หน้าหลัก</a>
        </div>
    </nav>

    <div class="container">
        <aside class="sidebar-menu-box" data-aos="fade-right">
            <h3
                style="font-size: 18px; color: #2c3e50; margin-bottom: 10px; padding-left: 10px; border-left: 4px solid #2c3e50;">
                เมนู</h3>
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

        <main class="content-card" data-aos="fade-left" data-aos-delay="100">
            <h1><?php echo htmlspecialchars($page['title']); ?></h1>
            <div class="page-body">
                <?php echo $page['content']; ?>
            </div>
            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999;">
                แก้ไขล่าสุด: <?php echo date('d/m/Y H:i', strtotime($page['updated_at'])); ?>
            </div>
        </main>
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