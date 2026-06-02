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
        <h1 class="text-3xl font-black text-base-content flex items-center gap-2 tracking-tight font-display">ระบบบริหารจัดการเว็บไซต์</h1>
        <p class="text-base-content/60 mt-1 max-w-[65ch] leading-relaxed">สวัสดีคุณ <?php echo htmlspecialchars($_SESSION['username']); ?> — อัปเดตข้อมูลหน่วยงาน เผยแพร่ข่าวประกาศจัดซื้อจัดจ้าง และจัดการโครงสร้างบุคลากรล่าสุด</p>
    </div>
    <div class="badge badge-neutral font-bold py-3.5 px-4 text-xs gap-2 border-base-200/40">
        <i class="fa-regular fa-clock"></i> <span class="tabular-nums"><?php echo date('วันที่ d/m/Y เวลา H:i:s', time()); ?></span>
    </div>
</div>

<!-- สถิติแบบพรีเมียม -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 w-full">
    <!-- Metric 1: Banners -->
    <div class="card bg-base-100 shadow-sm border border-base-200 hover:shadow-md hover:border-primary/30 transition-all duration-300 group stagger-card" style="--stagger: 1;">
        <div class="card-body p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between w-full">
                 <span class="text-[10px] font-black text-base-content/40 uppercase tracking-widest block">ภาพแบนเนอร์</span>
                 <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary transition-transform duration-300 group-hover:scale-110">
                     <i class="fa-solid fa-images text-sm"></i>
                 </span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                 <span class="text-3xl font-black text-base-content block tabular-nums tracking-tight"><?php echo $bannerCount; ?></span>
                 <span class="text-xs text-base-content/40">ภาพแบนเนอร์</span>
            </div>
            <a href="?page=banners" class="text-xs text-primary font-bold inline-flex items-center gap-1 mt-4 group-hover:translate-x-1 transition-transform w-fit">
                จัดการแบนเนอร์ <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    </div>
    
    <!-- Metric 2: Directors -->
    <div class="card bg-base-100 shadow-sm border border-base-200 hover:shadow-md hover:border-secondary/30 transition-all duration-300 group stagger-card" style="--stagger: 2;">
        <div class="card-body p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between w-full">
                 <span class="text-[10px] font-black text-base-content/40 uppercase tracking-widest block">คณะผู้บริหาร</span>
                 <span class="w-8 h-8 rounded-lg bg-secondary/10 flex items-center justify-center text-secondary transition-transform duration-300 group-hover:scale-110">
                     <i class="fa-solid fa-user-tie text-sm"></i>
                 </span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                 <span class="text-3xl font-black text-base-content block tabular-nums tracking-tight"><?php echo $directorCount; ?></span>
                 <span class="text-xs text-base-content/40">คนในทำเนียบ</span>
            </div>
            <a href="?page=directors" class="text-xs text-secondary font-bold inline-flex items-center gap-1 mt-4 group-hover:translate-x-1 transition-transform w-fit">
                จัดการบุคลากร <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    </div>
    
    <!-- Metric 3: Announcements -->
    <div class="card bg-base-100 shadow-sm border border-base-200 hover:shadow-md hover:border-accent/30 transition-all duration-300 group stagger-card" style="--stagger: 3;">
        <div class="card-body p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between w-full">
                 <span class="text-[10px] font-black text-base-content/40 uppercase tracking-widest block">ประกาศเผยแพร่</span>
                 <span class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent transition-transform duration-300 group-hover:scale-110">
                     <i class="fa-solid fa-bullhorn text-sm"></i>
                 </span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                 <span class="text-3xl font-black text-base-content block tabular-nums tracking-tight"><?php echo $announcementCount; ?></span>
                 <span class="text-xs text-base-content/40">ประกาศข่าว</span>
            </div>
            <a href="?page=announcements" class="text-xs text-accent font-bold inline-flex items-center gap-1 mt-4 group-hover:translate-x-1 transition-transform w-fit">
                จัดการประกาศ <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    </div>
    
    <!-- Metric 4: Files -->
    <div class="card bg-base-100 shadow-sm border border-base-200 hover:shadow-md hover:border-info/30 transition-all duration-300 group stagger-card" style="--stagger: 4;">
        <div class="card-body p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between w-full">
                 <span class="text-[10px] font-black text-base-content/40 uppercase tracking-widest block">ไฟล์ในระบบ</span>
                 <span class="w-8 h-8 rounded-lg bg-info/10 flex items-center justify-center text-info transition-transform duration-300 group-hover:scale-110">
                     <i class="fa-solid fa-file-shield text-sm"></i>
                 </span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                 <span class="text-3xl font-black text-base-content block tabular-nums tracking-tight"><?php echo $fileCount; ?></span>
                 <span class="text-xs text-base-content/40">ไฟล์ทั้งหมด</span>
            </div>
            <a href="?page=files" class="text-xs text-info font-bold inline-flex items-center gap-1 mt-4 group-hover:translate-x-1 transition-transform w-fit">
                จัดการไฟล์ <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    </div>
</div>

<!-- การกระทำด่วน (Quick Actions) ดีไซน์พรีเมียม -->
<div class="card shadow-xl mb-8 border-none overflow-hidden relative stagger-card quick-links-card" style="--stagger: 5;">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.15),transparent)] pointer-events-none"></div>
    <div class="card-body p-8 relative z-10">
        <h2 class="card-title text-2xl font-black flex items-center gap-2 tracking-tight">
            <i class="fa-solid fa-bolt-lightning text-white"></i> 
            ทางลัดการจัดการข้อมูล (Quick Actions)
        </h2>
        <p class="text-white/80 text-sm max-w-[65ch] leading-relaxed">เข้าถึงส่วนการบริหารจัดการข้อมูลหลักของระบบได้อย่างสะดวกรวดเร็วในคลิกเดียว</p>
        <div class="card-actions justify-start gap-3 mt-6">
            <a href="?page=banners" class="btn bg-white/20 hover:bg-white/30 text-white border-none rounded-xl gap-2 font-bold transition-all duration-200"><i class="fa-solid fa-images"></i> จัดการแบนเนอร์</a>
            <a href="?page=directors" class="btn bg-white/20 hover:bg-white/30 text-white border-none rounded-xl gap-2 font-bold transition-all duration-200"><i class="fa-solid fa-user-tie"></i> ข้อมูลผู้บริหาร</a>
            <a href="?page=announcements" class="btn bg-white/20 hover:bg-white/30 text-white border-none rounded-xl gap-2 font-bold transition-all duration-200"><i class="fa-solid fa-bullhorn"></i> ข่าวประกาศ & ไฟล์แนบ</a>
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
                                <div class="flex items-center gap-2 px-2.5 py-1 rounded-full bg-success/10 border border-success/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-success animate-pulse"></span>
                                    <span class="text-[10px] font-bold text-success tracking-wider uppercase">Active</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-actions justify-end border-t border-base-200 pt-4 mt-4">
                <a href="?page=announcements" class="text-xs font-bold text-primary hover:text-primary-focus inline-flex items-center gap-1 group/btn transition-colors py-2 px-3 rounded-lg hover:bg-primary/5 w-fit">
                    ดูประกาศทั้งหมด 
                    <i class="fa-solid fa-arrow-right text-[10px] group-hover/btn:translate-x-0.5 transition-transform"></i>
                </a>
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
                                            'pdf' => '<i class="fa-solid fa-file-pdf text-red-500 text-sm"></i>',
                                            'doc' => '<i class="fa-solid fa-file-word text-blue-500 text-sm"></i>',
                                            'docx' => '<i class="fa-solid fa-file-word text-blue-500 text-sm"></i>',
                                            'xls' => '<i class="fa-solid fa-file-excel text-green-600 text-sm"></i>',
                                            'xlsx' => '<i class="fa-solid fa-file-excel text-green-600 text-sm"></i>',
                                            'ppt' => '<i class="fa-solid fa-file-powerpoint text-orange-500 text-sm"></i>',
                                            'pptx' => '<i class="fa-solid fa-file-powerpoint text-orange-500 text-sm"></i>',
                                            'jpg' => '<i class="fa-solid fa-file-image text-purple-500 text-sm"></i>',
                                            'png' => '<i class="fa-solid fa-file-image text-purple-500 text-sm"></i>',
                                            'zip' => '<i class="fa-solid fa-file-zipper text-amber-500 text-sm"></i>'
                                        ];
                                        $ext = strtolower($file['file_type']);
                                        echo $icons[$ext] ?? '<i class="fa-solid fa-file text-slate-400 text-sm"></i>';
                                        ?>
                                        <?php echo htmlspecialchars($file['filename']); ?>
                                    </span>
                                    <span class="text-xs text-base-content/50 flex items-center gap-3">
                                        <span><i class="fa-regular fa-calendar"></i> <span class="tabular-nums"><?php echo date('d/m/Y', strtotime($file['created_at'])); ?></span></span>
                                        <span class="px-2 py-0.5 rounded text-[9px] uppercase font-bold tracking-wider bg-base-200 text-base-content/70 border border-base-300/40"><?php echo htmlspecialchars($file['category']); ?></span>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-actions justify-end border-t border-base-200 pt-4 mt-4">
                <a href="?page=announcements" class="text-xs font-bold text-secondary hover:text-secondary-focus inline-flex items-center gap-1 group/btn transition-colors py-2 px-3 rounded-lg hover:bg-secondary/5 w-fit">
                    ดูไฟล์ทั้งหมด 
                    <i class="fa-solid fa-arrow-right text-[10px] group-hover/btn:translate-x-0.5 transition-transform"></i>
                </a>
            </div>
        </div>
    </div>
</div>
