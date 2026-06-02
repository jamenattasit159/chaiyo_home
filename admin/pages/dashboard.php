<?php
// ตรวจสอบ Session
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// ดึงสถิติต่างๆ
try {
    $bannerCount = $pdo->query("SELECT COUNT(*) as count FROM banners")->fetch()['count'];
    $directorCount = $pdo->query("SELECT COUNT(*) as count FROM directors WHERE status='active'")->fetch()['count'];
    $announcementCount = $pdo->query("SELECT COUNT(*) as count FROM announcements WHERE status='active'")->fetch()['count'];
    $fileCount = $pdo->query("SELECT COUNT(*) as count FROM files WHERE status='active'")->fetch()['count'];

    // ประกาศล่าสุด
    $recentAnnouncements = $pdo->query(
        "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5"
    )->fetchAll();

    // ไฟล์ล่าสุด
    $recentFiles = $pdo->query(
        "SELECT * FROM files WHERE status='active' ORDER BY created_at DESC LIMIT 5"
    )->fetchAll();

} catch (Exception $e) {
    $bannerCount = $directorCount = $announcementCount = $fileCount = 0;
    $recentAnnouncements = $recentFiles = [];
}
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-black text-base-content flex items-center gap-2 tracking-tight">👋 ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
        <p class="text-base-content/60 mt-1 max-w-[65ch] leading-relaxed">ยินดีต้อนรับเข้าสู่ระบบจัดการและควบคุมเว็บไซต์อย่างเป็นทางการ</p>
    </div>
    <div class="badge badge-neutral font-bold py-3.5 px-4 text-xs gap-2">
        <i class="fa-regular fa-clock"></i> <span class="tabular-nums"><?php echo date('วันที่ d/m/Y เวลา H:i:s', time()); ?></span>
    </div>
</div>

<!-- สถิติแบบพรีเมียม -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 w-full">
    <div class="card bg-base-100 shadow-sm border border-base-200 hover:shadow-md transition-shadow stagger-card" style="--stagger: 1;">
        <div class="card-body p-6 flex flex-row items-center justify-between">
            <div>
                 <span class="text-base-content/50 font-bold text-xs uppercase tracking-wider block">ภาพแบนเนอร์</span>
                 <span class="text-3xl font-black text-primary block mt-1 tabular-nums tracking-tight"><?php echo $bannerCount; ?></span>
                 <a href="?page=banners" class="link link-primary link-hover text-xs font-bold block mt-2 tracking-wide">จัดการแบนเนอร์ →</a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary text-2xl">📸</div>
        </div>
    </div>
    
    <div class="card bg-base-100 shadow-sm border border-base-200 hover:shadow-md transition-shadow stagger-card" style="--stagger: 2;">
        <div class="card-body p-6 flex flex-row items-center justify-between">
            <div>
                 <span class="text-base-content/50 font-bold text-xs uppercase tracking-wider block">คณะผู้บริหาร</span>
                 <span class="text-3xl font-black text-secondary block mt-1 tabular-nums tracking-tight"><?php echo $directorCount; ?></span>
                 <a href="?page=directors" class="link link-secondary link-hover text-xs font-bold block mt-2 tracking-wide">จัดการบุคลากร →</a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-secondary/10 flex items-center justify-center text-secondary text-2xl">👔</div>
        </div>
    </div>
    
    <div class="card bg-base-100 shadow-sm border border-base-200 hover:shadow-md transition-shadow stagger-card" style="--stagger: 3;">
        <div class="card-body p-6 flex flex-row items-center justify-between">
            <div>
                 <span class="text-base-content/50 font-bold text-xs uppercase tracking-wider block">ประกาศเผยแพร่</span>
                 <span class="text-3xl font-black text-accent block mt-1 tabular-nums tracking-tight"><?php echo $announcementCount; ?></span>
                 <a href="?page=announcements" class="link link-accent link-hover text-xs font-bold block mt-2 tracking-wide">จัดการประกาศ →</a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-accent/10 flex items-center justify-center text-accent text-2xl">📢</div>
        </div>
    </div>
    
    <div class="card bg-base-100 shadow-sm border border-base-200 hover:shadow-md transition-shadow stagger-card" style="--stagger: 4;">
        <div class="card-body p-6 flex flex-row items-center justify-between">
            <div>
                 <span class="text-base-content/50 font-bold text-xs uppercase tracking-wider block">ไฟล์ในระบบ</span>
                 <span class="text-3xl font-black text-info block mt-1 tabular-nums tracking-tight"><?php echo $fileCount; ?></span>
                 <a href="?page=files" class="link link-info link-hover text-xs font-bold block mt-2 tracking-wide">จัดการไฟล์ →</a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-info/10 flex items-center justify-center text-info text-2xl">📄</div>
        </div>
    </div>
</div>

<!-- การกระทำด่วน (Quick Actions) ดีไซน์พรีเมียม -->
<div class="card shadow-xl mb-8 border-none overflow-hidden relative stagger-card quick-links-card" style="--stagger: 5;">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.15),transparent)] pointer-events-none"></div>
    <div class="card-body p-8 relative z-10">
        <h2 class="card-title text-2xl font-black flex items-center gap-2 tracking-tight"><i class="fa-solid fa-bolt text-warning animate-bounce"></i> ทางลัดการจัดการข้อมูล (Quick Links)</h2>
        <p class="text-white/80 text-sm max-w-[65ch] leading-relaxed">เข้าถึงส่วนการบริหารจัดการข้อมูลของระบบหลักได้อย่างสะดวกรวดเร็วในคลิกเดียว</p>
        <div class="card-actions justify-start gap-3 mt-6">
            <a href="?page=banners" class="btn bg-white/20 hover:bg-white/30 text-white border-none rounded-xl gap-2 font-bold"><i class="fa-solid fa-images"></i> จัดการแบนเนอร์</a>
            <a href="?page=directors" class="btn bg-white/20 hover:bg-white/30 text-white border-none rounded-xl gap-2 font-bold"><i class="fa-solid fa-user-tie"></i> ข้อมูลผู้บริหาร</a>
            <a href="?page=announcements" class="btn bg-white/20 hover:bg-white/30 text-white border-none rounded-xl gap-2 font-bold"><i class="fa-solid fa-bullhorn"></i> ข่าวประกาศ & ไฟล์แนบ</a>
        </div>
    </div>
</div>

<!-- ตารางแสดงรายการล่าสุดแบบสองฝั่ง -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- ประกาศล่าสุด -->
    <div class="card bg-base-100 shadow-xl border border-base-200 stagger-card" style="--stagger: 6;">
        <div class="card-body p-6 flex flex-col justify-between">
            <div>
                <h2 class="card-title text-lg font-bold text-base-content flex items-center gap-2 mb-4 border-b border-base-200 pb-4">
                    <i class="fa-solid fa-newspaper text-primary text-xl"></i> ประกาศอัปเดตล่าสุด
                </h2>
                <?php if (empty($recentAnnouncements)): ?>
                    <p class="text-base-content/50 text-center py-12">ยังไม่มีข้อมูลประกาศล่าสุดในระบบ</p>
                <?php else: ?>
                    <div class="divide-y divide-base-200">
                        <?php foreach ($recentAnnouncements as $announce): ?>
                            <div class="py-3 flex justify-between items-center gap-4 hover:bg-base-200/40 px-2 rounded-lg transition-colors">
                                <div class="flex flex-col gap-1 min-w-0">
                                    <span class="font-bold text-base-content/90 line-clamp-1 text-sm"><?php echo htmlspecialchars($announce['title']); ?></span>
                                    <span class="text-xs text-base-content/50 flex items-center gap-1"><i class="fa-regular fa-calendar"></i> เผยแพร่เมื่อ: <span class="tabular-nums"><?php echo date('d/m/Y H:i', strtotime($announce['created_at'])); ?></span></span>
                                </div>
                                <div class="badge badge-success text-white font-bold text-[10px] uppercase py-2 px-2.5 tracking-wider">Active</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-actions justify-end border-t border-base-200 pt-4 mt-4">
                <a href="?page=announcements" class="btn btn-ghost btn-sm text-primary font-bold gap-1">ดูประกาศทั้งหมด <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <!-- ไฟล์ล่าสุด -->
    <div class="card bg-base-100 shadow-xl border border-base-200 stagger-card" style="--stagger: 7;">
        <div class="card-body p-6 flex flex-col justify-between">
            <div>
                <h2 class="card-title text-lg font-bold text-base-content flex items-center gap-2 mb-4 border-b border-base-200 pb-4">
                    <i class="fa-solid fa-file-arrow-up text-secondary text-xl"></i> รายการอัปโหลดไฟล์ล่าสุด
                </h2>
                <?php if (empty($recentFiles)): ?>
                    <p class="text-base-content/50 text-center py-12">ยังไม่มีข้อมูลไฟล์อัปโหลดในระบบ</p>
                <?php else: ?>
                    <div class="divide-y divide-base-200">
                        <?php foreach ($recentFiles as $file): ?>
                            <div class="py-3 flex justify-between items-center gap-4 hover:bg-base-200/40 px-2 rounded-lg transition-colors">
                                <div class="flex flex-col gap-1 min-w-0">
                                    <span class="font-bold text-base-content/90 flex items-center gap-2 line-clamp-1 text-sm">
                                        <?php
                                        $icons = [
                                            'pdf' => '📄',
                                            'doc' => '📝',
                                            'docx' => '📝',
                                            'xls' => '📊',
                                            'xlsx' => '📊',
                                            'ppt' => '📑',
                                            'pptx' => '📑',
                                            'jpg' => '🖼️',
                                            'png' => '🖼️',
                                            'zip' => '🗜️'
                                        ];
                                        $ext = strtolower($file['file_type']);
                                        echo $icons[$ext] ?? '📦';
                                        ?>
                                        <?php echo htmlspecialchars($file['filename']); ?>
                                    </span>
                                    <span class="text-xs text-base-content/50 flex items-center gap-3">
                                        <span><i class="fa-regular fa-calendar"></i> <span class="tabular-nums"><?php echo date('d/m/Y', strtotime($file['created_at'])); ?></span></span>
                                        <span class="badge badge-ghost badge-sm text-[9px] uppercase font-bold py-1.5 px-2 tracking-wider"><?php echo htmlspecialchars($file['category']); ?></span>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-actions justify-end border-t border-base-200 pt-4 mt-4">
                <a href="?page=announcements" class="btn btn-ghost btn-sm text-secondary font-bold gap-1">ดูไฟล์ทั้งหมด <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
</div>
