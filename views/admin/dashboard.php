<?php
// ==========================================
// Admin View: Dashboard Overview
// Path: views/admin/dashboard.php
// ==========================================

// 1. โหลด Config และ Middleware
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Database.php';

// 2. บังคับว่าต้องเป็น Admin เท่านั้น
Auth::requireAdmin();

// 3. กำหนดชื่อหน้า และเรียกใช้ Header
$pageTitle = 'แดชบอร์ด (Overview)';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-6 animate-fade-in">
    
    <!-- Page Header & Global Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-2">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">ภาพรวมระบบ (Overview)</h1>
            <p class="text-sm text-gray-500 mt-1">สรุปข้อมูลสถิติของสมุดรายชื่อและสถานะเครือข่าย</p>
        </div>
        <div class="flex items-center gap-3">
             
        <!-- Live Clock Widget (เพิ่มใหม่) -->
            <div class="hidden lg:flex items-center bg-white/60 backdrop-blur-lg border border-gray-200/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] rounded-2xl p-1.5 pr-5 transition-all hover:bg-white/80">
                <div class="w-10 h-10 flex items-center justify-center bg-gradient-to-br from-brand-500 to-indigo-600 text-white rounded-xl mr-3 shadow-inner">
                    <i class="ph ph-clock text-xl"></i>
                </div>
                <div class="flex flex-col justify-center mt-0.5">
                    <div class="flex items-baseline gap-1">
                        <!-- ใช้ font-mono เพื่อให้ตัวเลขมีความกว้างเท่ากัน ป้องกัน UI สั่น -->
                        <span id="liveTime" class="text-lg font-bold text-slate-800 tracking-tight leading-none font-mono">--:--:--</span>
                        <span class="text-xs font-bold text-brand-600 leading-none">น.</span>
                    </div>
                    <span id="liveDate" class="text-[0.65rem] font-medium text-slate-500 uppercase tracking-wider mt-1">กำลังโหลด...</span>
                </div>
            </div>

            <button onclick="runBatchPing(this)" class="inline-flex items-center gap-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium px-4 py-2.5 rounded-xl shadow-sm transition-all duration-200">
                <i class="ph ph-radar text-lg text-emerald-400"></i>
                สแกนสถานะเครือข่าย (Ping All)
            </button>
        </div>
    </div>

    <!-- KPI Cards Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 md:gap-6">
        
        <!-- Card 1: Total Contacts -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group hover:border-brand-200 transition-colors">
            <div class="flex items-start justify-between relative z-10">
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">รายชื่อทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-900" id="kpiTotalContacts">-</h3>
                    <div class="mt-2 flex items-center text-xs text-brand-600 font-medium">
                        <i class="ph ph-trend-up mr-1"></i>
                        <span id="kpiNewThisMonth">โหลดข้อมูล...</span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ph ph-users text-2xl"></i>
                </div>
            </div>
            <!-- Background Decoration -->
            <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-gradient-to-br from-brand-50 to-transparent rounded-full opacity-50 pointer-events-none"></div>
        </div>

        <!-- Card 2: Online Status -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group hover:border-emerald-200 transition-colors">
            <div class="flex items-start justify-between relative z-10">
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">ออนไลน์ (Online)</p>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-bold text-gray-900" id="kpiOnlineActive">-</h3>
                        <span class="text-sm font-semibold text-emerald-500" id="kpiOnlinePercent">-%</span>
                    </div>
                    <div class="mt-2 flex items-center text-xs text-gray-400 font-medium">
                        <span class="status-dot online mr-1.5 w-2 h-2"></span>
                        ใช้งานอยู่ขณะนี้
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ph ph-broadcast text-2xl"></i>
                </div>
            </div>
            <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-gradient-to-br from-emerald-50 to-transparent rounded-full opacity-50 pointer-events-none"></div>
        </div>

<!-- Card 3: Offline Status (เพิ่มใหม่) -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group hover:border-rose-200 transition-colors">
            <div class="flex items-start justify-between relative z-10">
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">ออฟไลน์ (Offline)</p>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-bold text-gray-900" id="kpiOfflineActive">-</h3>
                        <span class="text-sm font-semibold text-rose-500" id="kpiOfflinePercent">-%</span>
                    </div>
                    <div class="mt-2 flex items-center text-xs text-gray-400 font-medium">
                        <span class="status-dot offline mr-1.5 w-2 h-2"></span>
                        ขาดการเชื่อมต่อ
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ph ph-wifi-slash text-2xl"></i>
                </div>
            </div>
            <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-gradient-to-br from-rose-50 to-transparent rounded-full opacity-50 pointer-events-none"></div>
        </div>

        <!-- Card 3: Departments -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group hover:border-indigo-200 transition-colors">
            <div class="flex items-start justify-between relative z-10">
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">จำนวนแผนก</p>
                    <h3 class="text-3xl font-bold text-gray-900" id="kpiTotalDepartments">-</h3>
                    <div class="mt-2 flex items-center text-xs text-gray-400 font-medium">
                        <i class="ph ph-buildings mr-1"></i>
                        โครงสร้างองค์กร
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ph ph-briefcase text-2xl"></i>
                </div>
            </div>
            <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-gradient-to-br from-indigo-50 to-transparent rounded-full opacity-50 pointer-events-none"></div>
        </div>

        <!-- Card 4: Updates -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group hover:border-amber-200 transition-colors">
            <div class="flex items-start justify-between relative z-10">
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">ความเคลื่อนไหว</p>
                    <h3 class="text-xl font-bold text-gray-900 mt-2" id="kpiRecentlyUpdated">อัปเดตวันนี้ 0</h3>
                    <div class="mt-3 flex items-center text-xs text-amber-600 font-medium">
                        <i class="ph ph-clock-counter-clockwise mr-1"></i>
                        การแก้ไขล่าสุด
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ph ph-activity text-2xl"></i>
                </div>
            </div>
            <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-gradient-to-br from-amber-50 to-transparent rounded-full opacity-50 pointer-events-none"></div>
        </div>

    </div>

    <!-- Charts & Tables Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Department Overview Chart -->
        <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col">
            <h3 class="text-base font-bold text-gray-900 mb-4">สัดส่วนบุคลากรตามแผนก</h3>
            
            <!-- พื้นที่วาดกราฟ Donut -->
            <div class="relative w-full h-56 flex items-center justify-center">
                <!-- <canvas> จะถูก Chart.js วาดทับตรงนี้ -->
                <canvas id="departmentChart"></canvas>
            </div>
            
            <!-- รายชื่อแผนกแบบ List (Custom Legend) -->
            <div id="departmentLegendList" class="mt-6 flex flex-col gap-1 flex-1 overflow-y-auto custom-scrollbar pr-2 max-h-48">
                <div class="flex justify-center items-center h-full text-gray-400 text-sm">
                    <div class="spinner-ring border-gray-300 mr-2 w-4 h-4"></div> กำลังโหลด...
                </div>
            </div>
        </div>

        <!-- Quick Access / Placeholder -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-center items-center text-center relative overflow-hidden">
            <!-- Background Graphic -->
            <div class="absolute inset-0 bg-slate-50/50"></div>
            
            <div class="relative z-10 max-w-md">
                <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ph ph-address-book text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">จัดการสมุดรายชื่อ</h3>
                <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                    เพิ่ม แก้ไข หรือลบข้อมูลเบอร์โทรศัพท์และ IP Address ของพนักงานในองค์กรได้ผ่านระบบจัดการ
                </p>
                <a href="<?= BASE_URL ?>views/admin/contacts_manage.php" class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white font-medium px-6 py-2.5 rounded-xl shadow-lg shadow-brand-500/30 transition-all duration-200 hover:-translate-y-0.5">
                    เปิดสมุดรายชื่อ
                    <i class="ph ph-arrow-right font-bold"></i>
                </a>
            </div>
        </div>
        
    </div>

</div>

<?php
// เนื่องจากยังไม่ได้แยกไฟล์ footer.php จึงทำการปิดแท็กและแนบ Script ตรงนี้
?>
        </main> <!-- ปิดแท็ก main จาก header.php -->
    </div> <!-- ปิดแท็ก div.flex-1 จาก header.php -->

    <!-- ========================================== -->
    <!-- Core Scripts -->
    <!-- ========================================== -->
    <!-- นำเข้าฟังก์ชันหลัก (Toast, API Fetch) -->
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
    
    <!-- นำเข้าไฟล์ contacts.js เพื่อใช้ฟังก์ชัน Ping Batch จากหน้า Dashboard ได้ -->
    <script src="<?= BASE_URL ?>assets/js/contacts.js"></script>

    <!-- นำเข้าไฟล์ charts.js สำหรับวาดกราฟ และจัดการตัวเลข Dashboard -->
    <script src="<?= BASE_URL ?>assets/js/charts.js"></script>

    <!-- Script สำหรับ Live Clock (เพิ่มใหม่) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const timeEl = document.getElementById('liveTime');
            const dateEl = document.getElementById('liveDate');
            
            if (!timeEl || !dateEl) return;

            function updateLiveClock() {
                const now = new Date();
                
                // จัดการเวลา (HH:mm:ss)
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                
                // สร้างแอนิเมชัน Pulse ให้เครื่องหมาย Colon (:)
                timeEl.innerHTML = `${hours}<span class="opacity-40 animate-pulse">:</span>${minutes}<span class="opacity-40 animate-pulse">:</span>${seconds}`;
                
                // จัดการวันที่ (วัน...ที่...เดือน...ปี)
                const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
                dateEl.textContent = now.toLocaleDateString('th-TH', options);
            }

            // เรียกใช้งานครั้งแรกทันที และตั้งเวลาให้ทำงานทุกๆ 1 วินาที
            updateLiveClock();
            setInterval(updateLiveClock, 1000);
        });
    </script>

</body>
</html>
