<?php
// ==========================================
// Component: Topbar Layout (Header)
// Path: views/includes/topbar.php
// ==========================================

// เช็คว่าไฟล์ถูกเรียกใช้ในบริบทที่มี Auth class หรือยัง
if (!class_exists('Auth')) {
    require_once __DIR__ . '/../../core/AuthMiddleware.php';
}

$currentUser = Auth::getCurrentUser();

// Logic จัดการคำทักทายตามช่วงเวลาของวัน
$hour = date('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = "สวัสดีตอนเช้า ☀️";
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = "สวัสดีตอนบ่าย 🌤️";
} elseif ($hour >= 17 && $hour < 20) {
    $greeting = "สวัสดีตอนเย็น 🌅";
} else {
    $greeting = "สวัสดีตอนค่ำ 🌙";
}
?>

<header class="sticky top-0 z-30 bg-white/80 backdrop-blur-xl border-b border-gray-100 h-20 flex items-center justify-between px-4 lg:px-8 shadow-sm transition-all duration-300">
    
    <!-- Left Section: Mobile Menu & Greeting -->
    <div class="flex items-center gap-4">
        <!-- ปุ่มแฮมเบอร์เกอร์ สำหรับจอมือถือ (ซ่อนในจอใหญ่) -->
        <button id="mobileMenuToggle" class="p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 rounded-xl transition-colors md:hidden flex-shrink-0">
            <i class="ph ph-list text-2xl"></i>
        </button>
        
        <!-- ข้อความทักทาย (ซ่อนในจอมือถือขนาดเล็กมาก เพื่อประหยัดพื้นที่) -->
        <div class="hidden sm:block">
            <h2 class="text-xl font-bold text-gray-900 tracking-tight">
                <?= $greeting ?>, <?= htmlspecialchars($currentUser['full_name']) ?>!
            </h2>
            <p class="text-sm text-gray-500">ยินดีต้อนรับเข้าสู่ระบบ ConnectPro</p>
        </div>
    </div>

    <!-- Right Section: Actions & Profile -->
    <div class="flex items-center gap-2 md:gap-5">
        
        <!-- Search Icon (Mobile Only - โชว์เฉพาะจอมือถือ) -->
        <button class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors sm:hidden">
            <i class="ph ph-magnifying-glass text-xl"></i>
        </button>

        <!-- Notifications -->
        <div class="relative">
            <button class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors relative" title="การแจ้งเตือน">
                <i class="ph ph-bell text-xl"></i>
                <!-- จุดแดงแจ้งเตือน (พัลส์กระพริบเบาๆ) -->
                <span class="absolute top-2 right-2.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white animate-pulse"></span>
            </button>
        </div>

        <!-- Divider เส้นกั้นแนวตั้ง -->
        <div class="w-px h-8 bg-gray-200 hidden sm:block"></div>

        <!-- User Profile -->
        <div class="flex items-center gap-3 cursor-pointer hover:opacity-80 transition-opacity p-1 pr-2 rounded-full hover:bg-gray-50">
            <!-- ข้อมูลชื่อ (ซ่อนในมือถือ) -->
            <div class="hidden md:block text-right">
                <p class="text-sm font-semibold text-gray-900 leading-tight"><?= htmlspecialchars($currentUser['full_name']) ?></p>
                <p class="text-xs text-brand-600 font-medium capitalize"><?= htmlspecialchars($currentUser['role']) ?></p>
            </div>
            <!-- รูปโปรไฟล์ Avatar -->
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-500 to-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-md shadow-brand-500/20 border-2 border-white">
                <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
            </div>
        </div>
    </div>
</header>

<script>
    // สคริปต์สำหรับจัดการเปิด/ปิด Sidebar บนจอมือถือ
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('mobileMenuToggle');
        // ค้นหา Sidebar (แท็ก aside ที่เราทำไว้ใน sidebar.php)
        const sidebar = document.querySelector('aside');
        
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                // เปลี่ยนสถานะ class เพื่อโชว์ Sidebar แบบเต็มจอทับลงมา (Overlay)
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('z-50');
                sidebar.classList.toggle('w-72');
                sidebar.classList.toggle('w-full'); // ขยายเต็มหน้าจอมือถือถ้าต้องการ
                
                // หากกำลังเปิด Sidebar อยู่ ให้เปลี่ยนไอคอนเป็นปุ่ม X (กากบาท)
                const icon = toggleBtn.querySelector('i');
                if (sidebar.classList.contains('hidden')) {
                    icon.classList.remove('ph-x');
                    icon.classList.add('ph-list');
                } else {
                    icon.classList.remove('ph-list');
                    icon.classList.add('ph-x');
                }
            });
        }
    });
</script>