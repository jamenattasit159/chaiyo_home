<?php
// admin/pages/theme_settings.php

// ตรวจสอบ Session
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// จัดการการบันทึกข้อมูลธีม
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_theme') {
    $primary = $_POST['theme_primary'] ?? '#059669';
    $secondary = $_POST['theme_secondary'] ?? '#047857';
    $accent = $_POST['theme_accent'] ?? '#10b981';

    try {
        // เซฟค่าใส่ตาราง settings
        $settings_to_save = [
            'theme_primary' => $primary,
            'theme_secondary' => $secondary,
            'theme_accent' => $accent
        ];

        foreach ($settings_to_save as $key => $val) {
            $stmt = $pdo->prepare("
                INSERT INTO settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ");
            $stmt->execute([$key, $val, $val]);
        }

        $message = '<div class="alert alert-success shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-check-circle text-lg"></i> บันทึกการตั้งค่าสีและธีมเรียบร้อยแล้ว</div>';
    } catch (Exception $e) {
        $message = '<div class="alert alert-error shadow-sm mb-6 flex items-center gap-2"><i class="fas fa-times-circle text-lg"></i> เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// ดึงการตั้งค่าธีมปัจจุบัน
$theme_primary = '#059669';
$theme_secondary = '#047857';
$theme_accent = '#10b981';

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'theme_%'");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (isset($settings['theme_primary'])) {
        $theme_primary = $settings['theme_primary'];
    }
    if (isset($settings['theme_secondary'])) {
        $theme_secondary = $settings['theme_secondary'];
    }
    if (isset($settings['theme_accent'])) {
        $theme_accent = $settings['theme_accent'];
    }
} catch (Exception $e) {
    // ไม่มีข้อมูล หรือยังไม่ได้สร้างตาราง
}
?>

<style>
    /* CSS เฉพาะสำหรับการควบคุมสไตล์ picker และ preview ให้มีความเป็นสไตล์พรีเมียมที่สุด */
    .preset-card.active {
        border-color: var(--color-primary, #f97316);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        transform: scale(1.03);
    }
    
    .picker-color-swatch {
        -webkit-appearance: none;
        border: none;
        width: 44px;
        height: 44px;
        border-radius: 10px;
        cursor: pointer;
        background: none;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    .picker-color-swatch::-webkit-color-swatch-wrapper {
        padding: 0;
    }
    .picker-color-swatch::-webkit-color-swatch {
        border: 2px solid #cbd5e1;
        border-radius: 10px;
    }
    .picker-color-swatch:hover {
        transform: scale(1.08);
    }

    /* สไตล์จำลองภายใน Browser Live Preview */
    .preview-nav {
        background: linear-gradient(135deg, var(--p-color) 0%, var(--s-color) 100%);
        color: white;
        padding: 10px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid var(--a-color);
        transition: all 0.3s;
    }
    .preview-main {
        padding: 20px;
        background: var(--lighter-green-mix);
        display: grid;
        grid-template-columns: 80px 1fr;
        gap: 15px;
        min-height: 200px;
        transition: all 0.3s;
    }
    .preview-widget {
        background: white;
        border-radius: 8px;
        padding: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid var(--light-green-mix);
        text-align: center;
    }
    .preview-btn {
        background: linear-gradient(135deg, var(--p-color) 0%, var(--s-color) 100%);
        color: white;
        border-radius: 6px;
        padding: 6px;
        font-size: 10px;
        font-weight: 700;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0,0,0,0.08);
        transition: all 0.3s;
    }
    .preview-body-card {
        background: white;
        border-radius: 10px;
        padding: 15px;
        border: 1px solid var(--light-green-mix);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .preview-article-header {
        font-size: 14px;
        font-weight: bold;
        color: #1e293b;
        border-left: 3px solid var(--p-color);
        padding-left: 8px;
    }
    .preview-article-item {
        background: white;
        border-radius: 6px;
        padding: 10px;
        border: 1px solid var(--light-green-mix);
        display: flex;
        align-items: center;
        gap: 8px;
        transition: transform 0.2s;
    }
    .preview-article-item:hover {
        transform: translateX(4px);
        border-left: 3px solid var(--p-color);
    }
    .preview-article-date {
        background: var(--light-green-mix);
        color: var(--p-color);
        padding: 4px;
        border-radius: 4px;
        font-size: 9px;
        font-weight: bold;
        text-align: center;
        min-width: 32px;
    }
    .preview-footer {
        background: #1e293b;
        color: #94a3b8;
        padding: 12px;
        text-align: center;
        font-size: 9px;
        border-top: 3px solid var(--p-color);
    }
</style>

<!-- หัวข้อของหน้า -->
<div class="mb-6">
    <h2 class="text-2xl font-extrabold text-base-content flex items-center gap-2.5">
        <i class="fas fa-palette text-primary"></i> ตั้งค่าสีและธีมเว็บไซต์
    </h2>
    <p class="text-sm text-base-content/60 mt-1">กำหนดสไตล์และอารมณ์สีของเว็บไซต์หลักโดยใช้ชุดสีสำเร็จรูปหรือปรับแต่งเองอย่างรวดเร็ว</p>
</div>

<?php echo $message; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    <!-- ฟอร์มการตั้งค่าด้านซ้าย (7 ส่วน) -->
    <div class="card bg-base-100 shadow-xl border border-base-200 lg:col-span-7">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_theme">

                <!-- การเลือก Presets -->
                <h3 class="text-lg font-bold text-base-content flex items-center gap-2 mb-4">
                    <i class="fas fa-magic text-primary"></i> เลือกธีมสีสำเร็จรูป (Presets)
                </h3>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mb-6">
                    <!-- 1. Emerald Forest -->
                    <div class="preset-card cursor-pointer border-2 rounded-xl p-3 text-center transition-all bg-base-200/40 border-base-300 hover:border-base-content/30 active:scale-95" 
                         data-name="emerald" data-p="#059669" data-s="#047857" data-a="#10b981">
                        <div class="font-bold text-xs text-base-content mb-2 truncate">Emerald Forest</div>
                        <div class="flex justify-center gap-1">
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #059669;"></div>
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #047857;"></div>
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #10b981;"></div>
                        </div>
                    </div>

                    <!-- 2. Royal Ocean -->
                    <div class="preset-card cursor-pointer border-2 rounded-xl p-3 text-center transition-all bg-base-200/40 border-base-300 hover:border-base-content/30 active:scale-95" 
                         data-name="ocean" data-p="#0284c7" data-s="#0369a1" data-a="#38bdf8">
                        <div class="font-bold text-xs text-base-content mb-2 truncate">Royal Ocean</div>
                        <div class="flex justify-center gap-1">
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #0284c7;"></div>
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #0369a1;"></div>
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #38bdf8;"></div>
                        </div>
                    </div>

                    <!-- 3. Sunset Glow -->
                    <div class="preset-card cursor-pointer border-2 rounded-xl p-3 text-center transition-all bg-base-200/40 border-base-300 hover:border-base-content/30 active:scale-95" 
                         data-name="sunset" data-p="#ea580c" data-s="#c2410c" data-a="#fb923c">
                        <div class="font-bold text-xs text-base-content mb-2 truncate">Sunset Glow</div>
                        <div class="flex justify-center gap-1">
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #ea580c;"></div>
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #c2410c;"></div>
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #fb923c;"></div>
                        </div>
                    </div>

                    <!-- 4. Electric Violet -->
                    <div class="preset-card cursor-pointer border-2 rounded-xl p-3 text-center transition-all bg-base-200/40 border-base-300 hover:border-base-content/30 active:scale-95" 
                         data-name="violet" data-p="#7c3aed" data-s="#6d28d9" data-a="#a78bfa">
                        <div class="font-bold text-xs text-base-content mb-2 truncate">Electric Violet</div>
                        <div class="flex justify-center gap-1">
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #7c3aed;"></div>
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #6d28d9;"></div>
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #a78bfa;"></div>
                        </div>
                    </div>

                    <!-- 5. Midnight Charcoal -->
                    <div class="preset-card cursor-pointer border-2 rounded-xl p-3 text-center transition-all bg-base-200/40 border-base-300 hover:border-base-content/30 active:scale-95" 
                         data-name="midnight" data-p="#334155" data-s="#1e293b" data-a="#64748b">
                        <div class="font-bold text-xs text-base-content mb-2 truncate">Midnight Slate</div>
                        <div class="flex justify-center gap-1">
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #334155;"></div>
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #1e293b;"></div>
                            <div class="w-4 h-4 rounded-full border border-black/10" style="background: #64748b;"></div>
                        </div>
                    </div>
                </div>

                <div class="divider my-6">หรือ</div>

                <!-- การปรับแต่งสี Custom Colors -->
                <h3 class="text-lg font-bold text-base-content flex items-center gap-2 mb-4">
                    <i class="fas fa-sliders-h text-primary"></i> ปรับแต่งสีด้วยตนเอง (Custom Colors)
                </h3>
                
                <div class="bg-base-200/60 rounded-2xl p-5 border border-base-200 space-y-5 mb-6">
                    <!-- Primary Color -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-base-300/50 pb-4">
                        <div>
                            <label class="font-bold text-sm text-base-content block">🟢 สีหลักของเว็บไซต์ (Primary Color)</label>
                            <span class="text-xs text-base-content/60 block mt-0.5">ใช้สำหรับ Navbar, หัวข้อเด่น, สีพื้นฐานหน้าเว็บ</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <input type="color" id="primaryPicker" name="theme_primary" value="<?php echo htmlspecialchars($theme_primary); ?>" class="picker-color-swatch">
                            <input type="text" id="primaryHex" value="<?php echo htmlspecialchars($theme_primary); ?>" maxlength="7" class="input input-bordered input-sm w-24 text-center font-mono uppercase text-xs">
                        </div>
                    </div>

                    <!-- Secondary Color -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-base-300/50 pb-4">
                        <div>
                            <label class="font-bold text-sm text-base-content block">🔵 สีรองของเว็บไซต์ (Secondary Color)</label>
                            <span class="text-xs text-base-content/60 block mt-0.5">ใช้สำหรับเฉดสีไล่สี, เมนูกลุ่มย่อย, แถบข่าว</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <input type="color" id="secondaryPicker" name="theme_secondary" value="<?php echo htmlspecialchars($theme_secondary); ?>" class="picker-color-swatch">
                            <input type="text" id="secondaryHex" value="<?php echo htmlspecialchars($theme_secondary); ?>" maxlength="7" class="input input-bordered input-sm w-24 text-center font-mono uppercase text-xs">
                        </div>
                    </div>

                    <!-- Accent Color -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <label class="font-bold text-sm text-base-content block">🟡 สีเน้นของเว็บไซต์ (Accent Color)</label>
                            <span class="text-xs text-base-content/60 block mt-0.5">ใช้สำหรับลิงก์แนบ, ป้ายประกาศสำคัญ, ขอบไฮไลต์</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <input type="color" id="accentPicker" name="theme_accent" value="<?php echo htmlspecialchars($theme_accent); ?>" class="picker-color-swatch">
                            <input type="text" id="accentHex" value="<?php echo htmlspecialchars($theme_accent); ?>" maxlength="7" class="input input-bordered input-sm w-24 text-center font-mono uppercase text-xs">
                        </div>
                    </div>
                </div>

                <div class="card-actions justify-end mt-4">
                    <button type="submit" class="btn btn-primary w-full gap-2 text-base">
                        <i class="fas fa-save"></i> บันทึกสีธีมของเว็บ
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ส่วนดูตัวอย่างสดแบบ Real-time ด้านขวา (5 ส่วน) -->
    <div class="card bg-base-100 shadow-xl border border-base-200 lg:col-span-5 sticky top-6">
        <div class="card-body">
            <h3 class="card-title text-base font-bold text-base-content flex items-center gap-2">
                <i class="fas fa-eye text-primary"></i> หน้าจอดูตัวอย่างสด (Live Preview)
            </h3>
            <p class="text-xs text-base-content/50 mt-[-8px]">จะจำลองโครงร่างหน้าแรกเพื่อดูว่าสไตล์ที่ปรับแต่งเป็นอย่างไร</p>

            <div class="mockup-browser border border-base-300 bg-base-200 mt-3 overflow-hidden shadow-inner">
                <div class="mockup-browser-toolbar">
                    <div class="input text-xs">https://your-website.com</div>
                </div>
                
                <div class="bg-base-100 text-base-content text-[11px]" id="previewElements">
                    <!-- Navbar จำลอง -->
                    <div class="preview-nav select-none">
                        <div class="font-bold flex items-center gap-1">🏥 รพ. ของเรา</div>
                        <div class="flex gap-2 text-[9px] opacity-90">
                            <span>หน้าแรก</span>
                            <span>ผู้บริหาร</span>
                            <span class="border-b border-white">ข่าวประกาศ</span>
                        </div>
                    </div>

                    <!-- เนื้อหาจำลอง -->
                    <div class="preview-main select-none">
                        <!-- ซ้าย -->
                        <div class="flex flex-col gap-2">
                            <div class="preview-widget">
                                <div class="w-8 h-8 rounded-full bg-base-300 mx-auto mb-1"></div>
                                <div class="text-[7px] font-bold truncate">ผอ. หน่วยงาน</div>
                            </div>
                            <div class="preview-btn text-[7px] py-1 cursor-pointer">บุคลากรทั้งหมด</div>
                        </div>

                        <!-- ขวา -->
                        <div class="preview-body-card">
                            <div class="preview-article-header font-bold">📢 ศูนย์ข่าวสารและประกาศ</div>
                            
                            <div class="preview-article-item">
                                <div class="preview-article-date">
                                    <div class="text-[10px]">26</div>
                                    <div class="text-[6px] tracking-tighter">MAY</div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-[9px] truncate">ประกาศจัดซื้อเวชภัณฑ์ยาปีงบประมาณ...</div>
                                    <div class="text-[7px] text-base-content/50 mt-0.5 flex items-center gap-1">
                                        <i class="fas fa-paperclip" style="color:var(--a-color);"></i> doc-file.pdf
                                    </div>
                                </div>
                            </div>

                            <div class="preview-article-item">
                                <div class="preview-article-date">
                                    <div class="text-[10px]">24</div>
                                    <div class="text-[6px] tracking-tighter">MAY</div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-[9px] truncate">รับสมัครบุคคลเพื่อสอบแข่งขันเข้าบรรจุเป็น...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ฟุตเตอร์จำลอง -->
                    <div class="preview-footer text-[8px] py-2 text-center text-white/50 bg-neutral">
                        &copy; 2026 โรงพยาบาลของเรา. All rights reserved.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ดึง Element ต่างๆ
    const primaryPicker = document.getElementById('primaryPicker');
    const primaryHex = document.getElementById('primaryHex');
    
    const secondaryPicker = document.getElementById('secondaryPicker');
    const secondaryHex = document.getElementById('secondaryHex');

    const accentPicker = document.getElementById('accentPicker');
    const accentHex = document.getElementById('accentHex');

    const previewContainer = document.getElementById('previewElements');
    const presets = document.querySelectorAll('.preset-card');

    // ฟังก์ชันอัปเดตสีใน Live Preview และ CSS Custom Property
    function updateLivePreview() {
        const p = primaryPicker.value;
        const s = secondaryPicker.value;
        const a = accentPicker.value;

        // อัปเดต Hex Text Input ให้ตรงกัน
        primaryHex.value = p;
        secondaryHex.value = s;
        accentHex.value = a;

        // อัปเดตตัวแปรใน Preview Wrapper
        previewContainer.style.setProperty('--p-color', p);
        previewContainer.style.setProperty('--s-color', s);
        previewContainer.style.setProperty('--a-color', a);
        
        // ผสมสีจำลองสำหรับความสว่างอ่อน-เข้ม (เลียนแบบ color-mix ใน CSS)
        previewContainer.style.setProperty('--light-green-mix', p + '24'); // Alpha 15% (ประมาณ 24 ในฐาน 16)
        previewContainer.style.setProperty('--lighter-green-mix', p + '0D'); // Alpha 5% (ประมาณ 0D ในฐาน 16)

        // เช็คว่าสีที่ใช้อยู่ตรงกับ Preset ไหน เพื่อเพิ่มคลาส Active
        checkActivePreset(p, s, a);
    }

    function checkActivePreset(p, s, a) {
        let matched = false;
        presets.forEach(card => {
            const cp = card.getAttribute('data-p').toLowerCase();
            const cs = card.getAttribute('data-s').toLowerCase();
            const ca = card.getAttribute('data-a').toLowerCase();

            if (cp === p.toLowerCase() && cs === s.toLowerCase() && ca === a.toLowerCase()) {
                card.classList.add('active');
                matched = true;
            } else {
                card.classList.remove('active');
            }
        });
    }

    // Event listener เมื่อปรับ Picker
    primaryPicker.addEventListener('input', updateLivePreview);
    secondaryPicker.addEventListener('input', updateLivePreview);
    accentPicker.addEventListener('input', updateLivePreview);

    // Event listener เมื่อปรับ Hex Text Input
    function setupHexInputListener(hexEl, pickerEl) {
        hexEl.addEventListener('input', function() {
            let val = this.value;
            if (!val.startsWith('#')) {
                val = '#' + val;
            }
            // ถ้ารูปแบบ Hex ถูกต้อง ค่อยอัปเดต Picker
            if (/^#[0-9A-F]{6}$/i.test(val)) {
                pickerEl.value = val;
                updateLivePreview();
            }
        });
    }
    setupHexInputListener(primaryHex, primaryPicker);
    setupHexInputListener(secondaryHex, secondaryPicker);
    setupHexInputListener(accentHex, accentPicker);

    // Event listener เมื่อเลือก Preset Card
    presets.forEach(card => {
        card.addEventListener('click', function() {
            const p = this.getAttribute('data-p');
            const s = this.getAttribute('data-s');
            const a = this.getAttribute('data-a');

            primaryPicker.value = p;
            secondaryPicker.value = s;
            accentPicker.value = a;

            updateLivePreview();
        });
    });

    // เริ่มทำงานครั้งแรกเพื่อตั้งค่าสีเริ่มต้นให้ preview
    updateLivePreview();
</script>
