<?php
require 'config.php';

// 1. ดึงข้อมูล ผอ. (แถวบนสุด)
$director = $pdo->query("SELECT * FROM directors WHERE status='active' AND category='director' LIMIT 1")->fetch();

// 2. ดึงข้อมูลบุคลากรทั่วไป (แถวละ 4)
$personnels = $pdo->query("SELECT * FROM directors WHERE status='active' AND category='personnel' ORDER BY created_at ASC")->fetchAll();

$orgInfo = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();
$logoData = $orgInfo['logo'] ?? '🏥';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บุคลากร - <?php echo $orgInfo['name']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* CSS หลัก */
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
            font-family: 'Segoe UI', sans-serif;
            background: var(--light);
            color: var(--text);
            margin: 0;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 15px 0;
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .nav-menu {
            display: flex;
            gap: 20px;
            list-style: none;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
        }

        .page-header {
            text-align: center;
            margin: 40px 0;
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .divider {
            width: 60px;
            height: 4px;
            background: var(--primary);
            margin: 0 auto;
            border-radius: 2px;
        }

        /* การ์ดบุคคล (ใช้ร่วมกันทั้ง ผอ. และ บุคลากร) */
        .person-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
            text-align: center;
            height: 100%;
            /* ให้การ์ดสูงเท่ากันในแถว */
            display: flex;
            flex-direction: column;
        }

        .person-card:hover {
            transform: translateY(-5px);
        }

        .person-img {
            width: 100%;
            height: 280px;
            /* ปรับความสูงรูปให้พอดี */
            object-fit: cover;
            border-bottom: 3px solid var(--primary);
        }

        .person-info {
            padding: 20px 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .person-name {
            font-weight: bold;
            font-size: 1.15rem;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .person-pos {
            color: var(--primary);
            font-size: 0.95rem;
        }

        /* --- ส่วนแสดงผล ผอ. (แถวบน ตรงกลาง) --- */
        .director-section {
            display: flex;
            justify-content: center;
            margin-bottom: 50px;
            padding-bottom: 40px;
            border-bottom: 1px dashed #ddd;
        }

        .director-wrapper {
            width: 100%;
            max-width: 350px;
            /* ผอ. อาจจะกรอบใหญ่กว่านิดหน่อยได้ */
        }

        .director-wrapper .person-card {
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.15);
            /* เงาสีส้มจางๆ ให้ดูเด่น */
            border: 1px solid rgba(249, 115, 22, 0.1);
        }

        /* --- Grid สำหรับบุคลากร (แถวละ 4) --- */
        .personnel-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            /* บังคับ 4 คอลัมน์ */
            gap: 30px;
            margin-top: 30px;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1024px) {
            .personnel-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            /* แท็บเล็ตแนวนอน = 3 */
        }

        @media (max-width: 768px) {
            .personnel-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            /* แท็บเล็ตแนวตั้ง = 2 */
        }

        @media (max-width: 480px) {
            .personnel-grid {
                grid-template-columns: 1fr;
            }

            /* มือถือ = 1 */
            .person-img {
                height: 250px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="navbar-container">
            <h3><?php echo $orgInfo['name']; ?></h3>
            <ul class="nav-menu">
                <li><a href="index.php">หน้าแรก</a></li>
                <li><a href="personnel.php" style="font-weight: bold; text-decoration: underline;">บุคลากร</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>คณะผู้บริหารและบุคลากร</h1>
            <div class="divider"></div>
            <p style="color: #666; margin-top: 10px;">โครงสร้างการบริหารงาน</p>
        </div>

        <?php if ($director): ?>
            <div class="director-section" data-aos="fade-up">
                <div class="director-wrapper">
                    <div class="person-card">
                        <?php if ($director['image']): ?>
                            <img src="uploads/directors/<?php echo $director['image']; ?>" class="person-img" loading="lazy"
                                alt="<?php echo htmlspecialchars($director['name']); ?>">
                        <?php else: ?>
                            <div
                                style="height: 280px; background: #eee; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user-tie" style="font-size: 80px; color: #ccc;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="person-info">
                            <div class="person-name" style="font-size: 1.3rem;">
                                <?php echo htmlspecialchars($director['name']); ?></div>
                            <div class="person-pos" style="font-weight: 500;">
                                <?php echo htmlspecialchars($director['position']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($personnels)): ?>
            <div class="personnel-grid">
                <?php 
                $delay = 100;
                foreach ($personnels as $p): 
                ?>
                    <div class="person-card" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                        <?php if ($p['image']): ?>
                            <img src="uploads/directors/<?php echo $p['image']; ?>" class="person-img" loading="lazy"
                                alt="<?php echo htmlspecialchars($p['name']); ?>">
                        <?php else: ?>
                            <div
                                style="height: 280px; background: #eee; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user" style="font-size: 60px; color: #ccc;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="person-info">
                            <div class="person-name"><?php echo htmlspecialchars($p['name']); ?></div>
                            <div class="person-pos"><?php echo htmlspecialchars($p['position']); ?></div>
                        </div>
                    </div>
                <?php 
                $delay += 50;
                if ($delay > 400) $delay = 100;
                endforeach; 
                ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 30px; color: #999;">
                <p>ยังไม่มีข้อมูลบุคลากรเพิ่มเติม</p>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 60px;"></div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
    </script>
</body>

</html>