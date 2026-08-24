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

// 🌟 เพิ่มใหม่: ดึงข้อมูลจริงจาก Database สำหรับกราฟ 7 วันย้อนหลัง
$trendLabels = [];
$trendOnline = [];
$trendOffline = [];
$daysTh = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

for ($i = 6; $i >= 0; $i--) {
    $targetDate = date('Y-m-d', strtotime("-$i days"));
    $dayOfWeek = date('w', strtotime($targetDate));
    
    $trendLabels[] = $daysTh[$dayOfWeek]; // ใส่ชื่อวันภาษาไทย
    
    if ($i === 0) {
        // 1. สำหรับ "วันปัจจุบัน" (แท่งขวาสุด) 
        // ให้นับยอดรวมสถานะทั้งหมดที่มีอยู่จริง ณ ตอนนี้ เพื่อให้ตรงกับตัวเลขสถิติด้านบน
        $onData = Database::getRow("SELECT COUNT(id) as total FROM contacts WHERE status = 'online'");
        $trendOnline[] = $onData ? (int)$onData['total'] : 0;
        
        $offData = Database::getRow("SELECT COUNT(id) as total FROM contacts WHERE status = 'offline'");
        $trendOffline[] = $offData ? (int)$offData['total'] : 0;
    } else {
        // 2. สำหรับ "วันย้อนหลัง" (6 วันก่อนหน้า)
        // เนื่องจากระบบยังไม่มีตารางเก็บประวัติรายวัน (History Log) จึงให้แสดงค่าเป็น 0 ตามความจริง 
        $trendOnline[] = 0;
        $trendOffline[] = 0;
    }
}

// แปลงเป็น JSON เพื่อส่งให้ Javascript
$jsLabels = json_encode($trendLabels, JSON_UNESCAPED_UNICODE);
$jsOnline = json_encode($trendOnline);
$jsOffline = json_encode($trendOffline);
// 🌟 สิ้นสุดส่วนที่เพิ่มใหม่

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

<!-- 🌟 Network Trend Chart (Luxurious Design) -->
    <div class="mt-8 bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-6 lg:p-8 relative overflow-hidden group">
        <!-- Glow Effects (แสงเรืองแสงพื้นหลัง) -->
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-emerald-400/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-rose-400/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 relative z-10">
            <div>
                <h3 class="text-xl font-bold text-gray-900 tracking-tight">สรุปสถานะการเชื่อมต่อย้อนหลัง (7 วัน)</h3>
                <p class="text-sm text-gray-500 mt-1">แนวโน้มอุปกรณ์ <span class="text-emerald-500 font-semibold">ออนไลน์</span> และ <span class="text-rose-500 font-semibold">ออฟไลน์</span> ในแต่ละวัน</p>
            </div>
            <!-- Custom Legend -->
            <div class="mt-4 sm:mt-0 flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-100 shadow-sm">
                <div class="flex items-center gap-2 px-3 py-1.5 bg-white rounded-lg shadow-sm">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)] animate-pulse"></span>
                    <span class="text-xs font-semibold text-slate-700">Online</span>
                </div>
                <div class="flex items-center gap-2 px-3 py-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.6)]"></span>
                    <span class="text-xs font-semibold text-slate-600">Offline</span>
                </div>
            </div>
        
        </div>

        <!-- พื้นที่วาดกราฟ -->
        <div class="relative w-full h-80 z-10">
            <canvas id="networkTrendChart"></canvas>
        </div>
    </div>

    <!-- 🌟 Monthly Network Trend Chart (Luxurious Area Chart) -->
    <div class="mt-6 bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-6 lg:p-8 relative overflow-hidden group">
        <!-- Glow Effects (แสงเรืองแสงสีม่วง/น้ำเงิน) -->
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-indigo-400/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-blue-400/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 relative z-10">
            <div>
                <h3 class="text-xl font-bold text-gray-900 tracking-tight">สรุปสถานะการเชื่อมต่อรายเดือน (ปีปัจจุบัน)</h3>
                <p class="text-sm text-gray-500 mt-1">ภาพรวมแนวโน้มอุปกรณ์ <span class="text-indigo-500 font-semibold">ออนไลน์</span> และ <span class="text-rose-500 font-semibold">ออฟไลน์</span> ตลอดทั้งปี</p>
            </div>
            <!-- Custom Legend -->
            <div class="mt-4 sm:mt-0 flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-100 shadow-sm">
                <div class="flex items-center gap-2 px-3 py-1.5 bg-white rounded-lg shadow-sm">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 shadow-[0_0_8px_rgba(99,102,241,0.6)] animate-pulse"></span>
                    <span class="text-xs font-semibold text-slate-700">Online (Avg)</span>
                </div>
                <div class="flex items-center gap-2 px-3 py-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.6)]"></span>
                    <span class="text-xs font-semibold text-slate-600">Offline (Avg)</span>
                </div>
            </div>
        </div>

        <!-- พื้นที่วาดกราฟรายเดือน -->
        <div class="relative w-full h-80 z-10">
            <canvas id="monthlyTrendChart"></canvas>
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

<!-- Script สำหรับวาดกราฟ Trend (Luxurious Bar Chart) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ctxTrend = document.getElementById('networkTrendChart');
            if (!ctxTrend) return;

            const ctx = ctxTrend.getContext('2d');

            // 🎨 สร้าง Gradient ไล่สีแบบหรูหราสำหรับแท่งกราฟ
            const gradientOnline = ctx.createLinearGradient(0, 0, 0, 300);
            gradientOnline.addColorStop(0, 'rgba(16, 185, 129, 1)'); // Emerald (สีสดด้านบน)
            gradientOnline.addColorStop(1, 'rgba(16, 185, 129, 0.2)'); // อ่อนลงด้านล่าง

            const gradientOffline = ctx.createLinearGradient(0, 0, 0, 300);
            gradientOffline.addColorStop(0, 'rgba(244, 63, 94, 1)'); // Rose (สีสดด้านบน)
            gradientOffline.addColorStop(1, 'rgba(244, 63, 94, 0.2)'); // อ่อนลงด้านล่าง

            // 💡 ข้อมูลจริงจาก Database
            const daysLabel = <?= $jsLabels ?>;
            const onlineData = <?= $jsOnline ?>;
            const offlineData = <?= $jsOffline ?>;

            // 🌟 Custom Plugin สำหรับวาดตัวเลขบนแท่งกราฟโดยไม่ต้อง Hover
            const alwaysShowDataLabels = {
                id: 'alwaysShowDataLabels',
                afterDatasetsDraw(chart, args, pluginOptions) {
                    const { ctx } = chart;
                    ctx.font = "bold 14px 'Inter', 'Prompt', sans-serif";
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';

                    chart.data.datasets.forEach((dataset, i) => {
                        const meta = chart.getDatasetMeta(i);
                        meta.data.forEach((bar, index) => {
                            const data = dataset.data[index];
                            if (data > 0) { // ซ่อนเลข 0 เพื่อให้กราฟดูคลีน
                                ctx.fillStyle = i === 0 ? '#10b981' : '#f43f5e'; // สีตัวเลขตามสถานะ
                                ctx.fillText(data, bar.x, bar.y - 6); // วาดตัวเลขเหนือแท่งกราฟ
                            }
                        });
                    });
                }
            };

            new Chart(ctx, {
                type: 'bar', // เปลี่ยนเป็น Bar Chart
                data: {
                    labels: daysLabel,
                    datasets: [
                        {
                            label: 'ออนไลน์ (Online)',
                            data: onlineData,
                            backgroundColor: gradientOnline,
                            borderRadius: 6, // ขอบมนสวยงาม
                            borderSkipped: false, // ให้ขอบมนทั้งด้านบนและล่าง
                            barPercentage: 0.6, // ขนาดความกว้างของแท่ง
                            categoryPercentage: 0.8
                        },
                        {
                            label: 'ออฟไลน์ (Offline)',
                            data: offlineData,
                            backgroundColor: gradientOffline,
                            borderRadius: 6,
                            borderSkipped: false,
                            barPercentage: 0.6,
                            categoryPercentage: 0.8
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 25 // เพิ่มพื้นที่ด้านบนเล็กน้อย ป้องกันตัวเลขถูกตัดขอบ
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleFont: { family: "'Inter', 'Prompt', sans-serif", size: 13 },
                            bodyFont: { family: "'Inter', 'Prompt', sans-serif", size: 14, weight: 'bold' },
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: true,
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false, drawBorder: false }, // ซ่อนเส้นตารางแนวตั้ง
                            ticks: { font: { family: "'Inter', 'Prompt', sans-serif" }, color: '#64748b' }
                        },
                        y: {
                            grid: { color: '#f1f5f9', borderDash: [5, 5], drawBorder: false },
                            ticks: {
                                display: false, // ซ่อนตัวเลขแกน Y ด้านซ้าย เพราะเราโชว์บนแท่งกราฟแล้ว
                                beginAtZero: true,
                                suggestedMax: 15 // เพิ่มระยะความสูงเผื่อไว้ไม่ให้กราฟชนขอบบนสุด
                            }
                        }
                    }
                },
                plugins: [alwaysShowDataLabels] // เรียกใช้งาน Plugin ที่เราเขียนไว้
            });
        });
    
    
    // ========================================================
            // 🌟 กราฟรายเดือน (Monthly Trend Bar Chart)
            // ========================================================
            const ctxMonthly = document.getElementById('monthlyTrendChart');
            if (ctxMonthly) {
                const ctxM = ctxMonthly.getContext('2d');

                // 🎨 Gradient สำหรับกราฟรายเดือน (โทน Indigo หรูหรา)
                const gradientOnlineM = ctxM.createLinearGradient(0, 0, 0, 300);
                gradientOnlineM.addColorStop(0, 'rgba(99, 102, 241, 1)'); // Indigo สด
                gradientOnlineM.addColorStop(1, 'rgba(99, 102, 241, 0.2)');

                const gradientOfflineM = ctxM.createLinearGradient(0, 0, 0, 300);
                gradientOfflineM.addColorStop(0, 'rgba(244, 63, 94, 1)'); // Rose สด
                gradientOfflineM.addColorStop(1, 'rgba(244, 63, 94, 0.2)');

                // 💡 ข้อมูลจำลอง 12 เดือน (Mock Data)
                const monthsLabel = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                const monthlyOnlineData = [45, 52, 48, 60, 65, 58, 70, 75, 72, 80, 85, 90];
                const monthlyOfflineData = [12, 10, 15, 8, 5, 9, 4, 3, 5, 2, 4, 1];

                // 🌟 Custom Plugin สำหรับโชว์ตัวเลขบนแท่งกราฟรายเดือน
                const alwaysShowDataLabelsMonthly = {
                    id: 'alwaysShowDataLabelsMonthly',
                    afterDatasetsDraw(chart, args, pluginOptions) {
                        const { ctx } = chart;
                        ctx.font = "bold 12px 'Inter', 'Prompt', sans-serif"; // ปรับขนาดฟอนต์ให้เล็กลงนิดนึงเพราะมี 12 แท่ง
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';

                        chart.data.datasets.forEach((dataset, i) => {
                            const meta = chart.getDatasetMeta(i);
                            meta.data.forEach((bar, index) => {
                                const data = dataset.data[index];
                                if (data > 0) { // ซ่อนเลข 0
                                    ctx.fillStyle = i === 0 ? '#6366f1' : '#f43f5e'; // สี Indigo สำหรับ Online, สี Rose สำหรับ Offline
                                    ctx.fillText(data, bar.x, bar.y - 6);
                                }
                            });
                        });
                    }
                };

                new Chart(ctxM, {
                    type: 'bar', // เปลี่ยนเป็นกราฟแท่ง
                    data: {
                        labels: monthsLabel,
                        datasets: [
                            {
                                label: 'ออนไลน์เฉลี่ย (Online)',
                                data: monthlyOnlineData,
                                backgroundColor: gradientOnlineM,
                                borderRadius: 4, // ขอบมน
                                borderSkipped: false,
                                barPercentage: 0.7,
                                categoryPercentage: 0.8
                            },
                            {
                                label: 'ออฟไลน์เฉลี่ย (Offline)',
                                data: monthlyOfflineData,
                                backgroundColor: gradientOfflineM,
                                borderRadius: 4,
                                borderSkipped: false,
                                barPercentage: 0.7,
                                categoryPercentage: 0.8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                top: 25 // เพิ่มระยะด้านบนไม่ให้ตัวเลขถูกตัด
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                titleFont: { family: "'Inter', 'Prompt', sans-serif", size: 13 },
                                bodyFont: { family: "'Inter', 'Prompt', sans-serif", size: 14, weight: 'bold' },
                                padding: 12,
                                cornerRadius: 8,
                                displayColors: true,
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false, drawBorder: false },
                                ticks: { font: { family: "'Inter', 'Prompt', sans-serif", size: 11 }, color: '#64748b' }
                            },
                            y: {
                                grid: { color: '#f1f5f9', borderDash: [5, 5], drawBorder: false },
                                ticks: {
                                    display: false, // ซ่อนตัวเลขแกน Y ด้านซ้าย
                                    beginAtZero: true,
                                    suggestedMax: 110 // เผื่อระยะด้านบนไว้ให้ตัวเลข 90 ไม่ชนขอบ
                                }
                            }
                        }
                    },
                    plugins: [alwaysShowDataLabelsMonthly] // เรียกใช้ Plugin แสดงตัวเลข
                });
            }
    
    </script>

</body>
</html>
