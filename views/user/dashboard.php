<?php
// ==========================================
// User View: My Department Dashboard
// Path: views/user/dashboard.php
// ==========================================

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Database.php';

Auth::requireLogin();

// 1. ดึงข้อมูล User ปัจจุบัน
$currentUser = Auth::getCurrentUser();
$fullName = $currentUser['full_name'];

// 2. หาแผนกของ User ปัจจุบัน (โยงจากชื่อ-นามสกุล)
$myProfile = Database::getRow(
    "SELECT c.department_id, d.name AS dept_name, d.color_code 
     FROM contacts c 
     LEFT JOIN departments d ON c.department_id = d.id 
     WHERE CONCAT(c.first_name, ' ', c.last_name) = ?", 
    [$fullName]
);

// แก้เป็น: (รับค่า dept_id จากปุ่มที่กดมา ถ้าไม่มีให้ใช้แผนกตัวเอง)
$myDeptId = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : ($myProfile ? $myProfile['department_id'] : null);
$myDeptName = ($myProfile && $myProfile['dept_name']) ? $myProfile['dept_name'] : 'ยังไม่ระบุแผนก';
$myDeptColor = ($myProfile && $myProfile['color_code']) ? $myProfile['color_code'] : '#94a3b8';

// 3. ดึงรายชื่อพนักงานทั้งหมดที่อยู่ในแผนกเดียวกัน
$deptContacts = [];
$onlineCount = 0;
$offlineCount = 0;

// ตัวแปรนับจำนวนสถานะการทำงาน (เพิ่มใหม่)
$availableCount = 0;
$onCallCount = 0;
$awayCount = 0;
$busyCount = 0;

if ($myDeptId) {
    $deptContacts = Database::getAll(
        "SELECT * FROM contacts WHERE department_id = ? ORDER BY first_name ASC", 
        [$myDeptId]
    );
    // นับสถิติออนไลน์ และสถานะการทำงาน
    foreach ($deptContacts as $c) {
        if ($c['status'] === 'online') $onlineCount++;
        if ($c['status'] === 'offline') $offlineCount++;
        
        $ws = $c['work_status'] ?? 'available';
        if ($ws === 'on_call') {
            $onCallCount++;
        } elseif ($ws === 'away') {
            $awayCount++;
        } elseif ($ws === 'busy') {
            $busyCount++;
        } else {
            $availableCount++;
        }
    }
}

$pageTitle = 'ภาพรวมแผนก (My Team)';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* แอนิเมชันสำหรับก้อนสีเรืองแสงพื้นหลัง (Aura Blobs) */
    @keyframes blob {
        0% { transform: translate(0px, 0px) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
        100% { transform: translate(0px, 0px) scale(1); }
    }
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 { animation-delay: 2s; }
    .animation-delay-4000 { animation-delay: 4s; }
    
    /* เอฟเฟกต์ Glassmorphism แบบพรีเมียมสุดๆ */
    .premium-glass {
        background: rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
    }
</style>

<div class="relative min-h-[85vh] w-full p-2 lg:p-4 rounded-3xl overflow-hidden animate-fade-in">
    
    <!-- 🌟 Abstract Aura Backgrounds (ก้อนสีฟุ้งๆ ทันสมัย) -->
    <div class="absolute top-[-10%] left-[10%] w-96 h-96 bg-purple-400/30 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob pointer-events-none"></div>
    <div class="absolute top-[20%] right-[-5%] w-96 h-96 bg-cyan-400/30 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-2000 pointer-events-none"></div>
    <div class="absolute bottom-[-10%] left-[30%] w-96 h-96 bg-pink-400/30 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-4000 pointer-events-none"></div>

    <!-- 🌟 Content Container -->
    <div class="relative z-10 max-w-7xl mx-auto space-y-6">

        <?php if (!$myDeptId): ?>
            <!-- กรณีที่ User ยังไม่ได้อัปเดตโปรไฟล์/ระบุแผนก -->
            <div class="premium-glass rounded-3xl p-10 text-center flex flex-col items-center justify-center min-h-[50vh]">
                <div class="w-24 h-24 bg-white/60 rounded-full flex items-center justify-center shadow-inner mb-6">
                    <i class="ph-fill ph-users-three text-5xl text-gray-400"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">ยังไม่พบข้อมูลกลุ่มงานของคุณ</h2>
                <p class="text-gray-500 max-w-md mx-auto mb-8">คุณต้องทำการระบุ "แผนก (Department)" ในโปรไฟล์ส่วนตัวของคุณก่อน ระบบจึงจะสามารถดึงรายชื่อเพื่อนร่วมงานมาแสดงได้</p>
            </div>
        <?php else: ?>
            
            <!-- Header & Stats (Glass Design) -->
            <div class="premium-glass rounded-3xl p-6 lg:p-8 flex flex-col lg:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg text-white text-3xl font-bold" style="background: <?= $myDeptColor ?>">
                        <i class="ph-fill ph-users-three"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase tracking-widest mb-1">กลุ่มงานของคุณ</p>
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                            แผนก <?= htmlspecialchars($myDeptName) ?>
                        </h1>
                    </div>
                </div>

                <!-- กลุ่มกล่องด้านขวา (Clock & Stats) -->
                <div class="flex flex-wrap items-center justify-start lg:justify-end gap-4 w-full lg:w-auto mt-4 lg:mt-0">
                    
                    <!-- 🌟 Live Clock Widget (Wow Design) 🌟 -->
                    <div class="relative overflow-hidden bg-white/40 border border-white/60 backdrop-blur-md rounded-2xl px-5 py-3 flex items-center gap-4 shadow-[0_8px_30px_rgb(0,0,0,0.04)] group hover:bg-white/70 transition-all duration-300 hover:-translate-y-1 cursor-default">
                        <!-- แสง Glow สวยๆ ซ่อนอยู่ข้างหลัง -->
                        <div class="absolute -left-4 -top-4 w-16 h-16 bg-brand-400/20 rounded-full blur-2xl group-hover:bg-brand-400/40 transition-all duration-500"></div>
                        
                        <!-- ไอคอนนาฬิกา -->
                        <div class="relative w-11 h-11 rounded-xl bg-gradient-to-br from-brand-500 via-indigo-500 to-purple-600 text-white flex items-center justify-center shadow-lg shadow-brand-500/30">
                            <i class="ph-fill ph-clock text-2xl group-hover:animate-pulse"></i>
                        </div>
                        
                        <!-- ตัวเลขเวลา -->
                        <div class="flex flex-col relative z-10 min-w-[90px]">
                            <div class="flex items-baseline gap-1">
                                <span id="userLiveTime" class="text-2xl font-bold text-gray-800 font-mono tracking-tight leading-none drop-shadow-sm">--:--:--</span>
                            </div>
                            <span id="userLiveDate" class="text-[0.65rem] font-bold text-brand-600 uppercase tracking-widest mt-1">กำลังโหลด...</span>
                        </div>
                    </div>

                    <!-- กล่องสถิติเดิม (ปรับดีไซน์ให้เข้าเซ็ตกัน) -->
                    <div class="flex gap-3">
                        <div class="bg-white/60 rounded-2xl px-5 py-3 text-center border border-white/80 shadow-sm min-w-[90px] hover:-translate-y-1 transition-transform duration-300">
                            <p class="text-[0.65rem] text-gray-500 font-bold uppercase tracking-wider mb-1">สมาชิกทีม</p>
                            <p class="text-2xl font-black text-gray-800 leading-none"><?= count($deptContacts) ?></p>
                        </div>
                        <div class="bg-gradient-to-br from-emerald-50/80 to-emerald-100/50 rounded-2xl px-5 py-3 text-center border border-emerald-200/50 shadow-sm min-w-[90px] hover:-translate-y-1 transition-transform duration-300">
                            <p class="text-[0.65rem] text-emerald-600 font-bold uppercase tracking-wider mb-1">Phone Online</p>
                            <p class="text-2xl font-black text-emerald-600 leading-none"><?= $onlineCount ?></p>
                        </div>
                    </div>
                </div>
            </div>

            
            <!-- 🌟 สรุปสถานะคนในทีม (Wow Design) 🌟 -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-2">
                
                <!-- 1. ว่าง (Available) -->
                <div class="bg-white/50 backdrop-blur-xl rounded-2xl p-4 border border-emerald-100/80 shadow-[0_8px_20px_rgb(16,185,129,0.05)] flex items-center gap-4 hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-50 to-emerald-100 text-emerald-600 flex items-center justify-center text-xl shadow-sm border border-emerald-200/50 group-hover:scale-110 transition-transform">
                        <i class="ph-fill ph-check-circle"></i>
                    </div>
                    <div>
                        <p class="text-[0.65rem] font-bold text-gray-500 uppercase tracking-widest mb-0.5">ว่าง</p>
                        <p class="text-2xl font-black text-emerald-600 leading-none"><?= $availableCount ?></p>
                    </div>
                </div>

                <!-- 2. ติดสาย (On a call) -->
                <div class="bg-white/50 backdrop-blur-xl rounded-2xl p-4 border border-indigo-100/80 shadow-[0_8px_20px_rgb(99,102,241,0.05)] flex items-center gap-4 hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-50 to-indigo-100 text-indigo-600 flex items-center justify-center text-xl shadow-sm border border-indigo-200/50 group-hover:scale-110 transition-transform">
                        <i class="ph-fill ph-phone-call"></i>
                    </div>
                    <div>
                        <p class="text-[0.65rem] font-bold text-gray-500 uppercase tracking-widest mb-0.5">ติดสาย</p>
                        <p class="text-2xl font-black text-indigo-600 leading-none"><?= $onCallCount ?></p>
                    </div>
                </div>

                <!-- 3. ไม่อยู่ (Away) -->
                <div class="bg-white/50 backdrop-blur-xl rounded-2xl p-4 border border-amber-100/80 shadow-[0_8px_20px_rgb(245,158,11,0.05)] flex items-center gap-4 hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-amber-50 to-amber-100 text-amber-600 flex items-center justify-center text-xl shadow-sm border border-amber-200/50 group-hover:scale-110 transition-transform">
                        <i class="ph-fill ph-clock"></i>
                    </div>
                    <div>
                        <p class="text-[0.65rem] font-bold text-gray-500 uppercase tracking-widest mb-0.5">ไม่อยู่</p>
                        <p class="text-2xl font-black text-amber-600 leading-none"><?= $awayCount ?></p>
                    </div>
                </div>

                <!-- 4. ไม่ว่าง (Busy) -->
                <div class="bg-white/50 backdrop-blur-xl rounded-2xl p-4 border border-rose-100/80 shadow-[0_8px_20px_rgb(244,63,94,0.05)] flex items-center gap-4 hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-rose-50 to-rose-100 text-rose-600 flex items-center justify-center text-xl shadow-sm border border-rose-200/50 group-hover:scale-110 transition-transform">
                        <i class="ph-fill ph-minus-circle"></i>
                    </div>
                    <div>
                        <p class="text-[0.65rem] font-bold text-gray-500 uppercase tracking-widest mb-0.5">ไม่ว่าง</p>
                        <p class="text-2xl font-black text-rose-600 leading-none"><?= $busyCount ?></p>
                    </div>
                </div>

            </div>
            <!-- 🌟 สิ้นสุดสรุปสถานะ 🌟 -->
            
            <!-- Team Members Grid Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pt-4">
                <?php foreach ($deptContacts as $contact): ?>
                    <!-- Glass Card for Contact -->
                    <div class="premium-glass rounded-3xl p-6 transition-all duration-300 hover:-translate-y-1 hover:bg-white/60 group relative cursor-pointer">
                        
                        <!-- 🌟 Status Badge (อิงตามสถานะการทำงาน) 🌟 -->
                        <div class="absolute top-4 right-4 z-10">
                            <?php 
                                $wStatus = $contact['work_status'] ?? 'available';
                                switch($wStatus) {
                                    case 'on_call':
                                        echo '<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-wider bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-sm"><i class="ph-fill ph-phone-call mr-1.5 text-sm"></i> ติดสาย</span>';
                                        break;
                                    case 'away':
                                        echo '<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-wider bg-amber-50 text-amber-600 border border-amber-100 shadow-sm"><i class="ph-fill ph-clock mr-1.5 text-sm"></i> ไม่อยู่</span>';
                                        break;
                                    case 'busy':
                                        echo '<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-wider bg-rose-50 text-rose-600 border border-rose-100 shadow-sm"><i class="ph-fill ph-minus-circle mr-1.5 text-sm"></i> ไม่ว่าง</span>';
                                        break;
                                    default:
                                        echo '<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-wider bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-sm"><i class="ph-fill ph-check-circle mr-1.5 text-sm"></i> ว่าง</span>';
                                        break;
                                }
                            ?>
                        </div>
                        <!-- 🌟 สิ้นสุด Status Badge 🌟 -->

                        <!-- Avatar -->
                        <div class="flex flex-col items-center text-center">
                            <div class="w-20 h-20 rounded-[1.25rem] bg-gradient-to-br from-indigo-100 to-purple-50 flex items-center justify-center shadow-inner mb-4 group-hover:scale-105 transition-transform duration-300 border border-white overflow-hidden">
    <?php if (!empty($contact['avatar_url'])): ?>
        <img src="<?= BASE_URL . $contact['avatar_url'] ?>" alt="Profile" class="w-full h-full object-cover">
    <?php else: ?>
        <span class="text-2xl font-bold text-indigo-500 uppercase tracking-widest">
            <?= substr($contact['first_name'], 0, 1) . substr($contact['last_name'], 0, 1) ?>
        </span>
    <?php endif; ?>
</div>
                            
                            <h3 class="text-lg font-bold text-gray-900 leading-tight mb-1">
                                <?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?>
                            </h3>
                            <p class="text-xs font-semibold text-brand-600 mb-5 bg-brand-50 px-3 py-1 rounded-full">
                                <?= htmlspecialchars($contact['job_title'] ?: 'พนักงาน') ?>
                            </p>
                        </div>

                        <!-- Contact Details -->
                        <div class="space-y-3 pt-4 border-t border-white/50">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 flex items-center gap-2"><i class="ph ph-phone text-lg"></i> เบอร์ต่อ</span>
                                <span class="font-mono font-bold text-gray-800"><?= htmlspecialchars($contact['extension'] ?: '-') ?></span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 flex items-center gap-2"><i class="ph ph-device-mobile text-lg"></i> มือถือ</span>
                                <span class="font-mono font-semibold text-gray-800"><?= htmlspecialchars($contact['mobile_number'] ?: '-') ?></span>
                            </div>
                            
                            
                            <div class="flex items-center justify-between text-sm gap-2">
                                <span class="text-gray-500 flex items-center gap-2 whitespace-nowrap">
                                    <i class="ph ph-envelope-simple text-lg"></i> อีเมล
                                </span>
                                <span class="text-xs text-gray-700 font-medium truncate" title="<?= htmlspecialchars($contact['email'] ?: '') ?>">
                                    <?= htmlspecialchars($contact['email'] ?: '-') ?>
                                </span>
                            </div>

                            <!-- 🌟 เพิ่มใหม่: รุ่นโทรศัพท์ -->
                            <div class="flex items-center justify-between text-sm gap-2">
                                <span class="text-gray-500 flex items-center gap-2 whitespace-nowrap">
                                    <i class="ph ph-device-mobile-camera text-lg"></i> รุ่นโทรศัพท์
                                </span>
                                <span class="text-xs text-gray-700 font-medium truncate">
                                    <?= htmlspecialchars($contact['phone_model'] ?: '-') ?>
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 flex items-center gap-2"><i class="ph ph-laptop text-lg"></i> IP</span>
                                <span class="font-mono text-indigo-600 font-semibold bg-white/50 px-2 py-0.5 rounded-md border border-indigo-100/50">
                                    <?= htmlspecialchars($contact['ip_address'] ?: '-') ?>
                                </span>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php
// เนื่องจากเราไม่ได้แยกไฟล์ footer.php จึงแนบการเรียก JS ปิดท้ายตรงนี้
?>
        </main> <!-- ปิดแท็ก main จาก header.php -->
    </div> <!-- ปิดแท็ก div.flex-1 จาก header.php -->

    <!-- Core Scripts -->
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>

    <!-- Script สำหรับ Live Clock ว้าวๆ -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const timeEl = document.getElementById('userLiveTime');
            const dateEl = document.getElementById('userLiveDate');
            
            if (!timeEl || !dateEl) return;

            function updateUserClock() {
                const now = new Date();
                
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                
                // ใส่ลูกเล่นให้เครื่องหมาย : กะพริบ และมีสีสัน
                timeEl.innerHTML = `${hours}<span class="animate-pulse text-brand-500 mx-0.5">:</span>${minutes}<span class="animate-pulse text-brand-500 mx-0.5">:</span>${seconds}`;
                
                // แปลงรูปแบบวันที่เป็นภาษาไทย
                const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
                dateEl.textContent = now.toLocaleDateString('th-TH', options);
            }

            // เรียกใช้งานครั้งแรกทันที และตั้งเวลาให้ทำงานทุกๆ 1 วินาที
            updateUserClock();
            setInterval(updateUserClock, 1000);
        });
    </script>
</body>
</html>