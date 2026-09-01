<?php
// ==========================================
// Component: Sidebar Layout
// Path: views/includes/sidebar.php
// ==========================================

// เช็คว่าไฟล์ถูกเรียกใช้ในบริบทที่มี Auth class หรือยัง ถ้ายังให้ require เข้ามา
if (!class_exists('Auth')) {
    require_once __DIR__ . '/../../core/AuthMiddleware.php';
}

$currentUser = Auth::getCurrentUser();
$isAdmin = Auth::isAdmin();

// ⭐⭐⭐ โค้ดที่ต้องเพิ่ม: สั่งให้ดึงรูปล่าสุดจาก Database โดยตรง ⭐⭐⭐
require_once __DIR__ . '/../../core/Database.php';
$latestContact = Database::getRow("SELECT avatar_url FROM contacts WHERE CONCAT(first_name, ' ', last_name) = ?", [$currentUser['full_name']]);
if ($latestContact && !empty($latestContact['avatar_url'])) {
    $currentUser['avatar_url'] = $latestContact['avatar_url'];
}
// ⭐⭐⭐ สิ้นสุดโค้ดที่เพิ่ม ⭐⭐⭐

// ฟังก์ชันช่วยเช็คหน้าปัจจุบัน เพื่อทำแถบสีไฮไลต์เมนู (Active State)
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Container (ซ่อนในจอมือถือ, โชว์ในจอระดับ md ขึ้นไป) -->
<aside class="w-72 flex-shrink-0 bg-white border-r border-gray-100 flex flex-col h-screen sticky top-0 z-40 shadow-[4px_0_24px_rgba(0,0,0,0.02)] hidden md:flex transition-all duration-300">
    
    <!-- Brand Logo -->
    <div class="h-20 flex items-center gap-3 px-8 border-b border-slate-50/50">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
            <i class="ph ph-phone-call text-2xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-900 tracking-tight">ConnectPro</h1>
            <p class="text-[0.65rem] uppercase tracking-widest text-gray-400 font-semibold">Directory</p>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="flex-1 overflow-y-auto py-6 px-4 custom-scrollbar">
        
        <?php if ($isAdmin): ?>
        <p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">ภาพรวมระบบ</p>
        <ul class="space-y-1 mb-8">
            <li>
                <a href="<?= BASE_URL ?>views/admin/dashboard.php" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $currentPage === 'dashboard.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="ph <?= $currentPage === 'dashboard.php' ? 'ph-squares-four text-blue-600' : 'ph-squares-four' ?> text-xl"></i>
                    แดชบอร์ด (Dashboard)
                </a>
            </li>
        </ul>

        <p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">จัดการข้อมูล</p>
        <ul class="space-y-1 mb-8">
            <li>
                <a href="<?= BASE_URL ?>views/admin/contacts_manage.php" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $currentPage === 'contacts_manage.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="ph <?= $currentPage === 'contacts_manage.php' ? 'ph-address-book text-blue-600' : 'ph-address-book' ?> text-xl"></i>
                    สมุดรายชื่อ (Directory)
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>views/admin/user_management.php" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $currentPage === 'user_management.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="ph <?= $currentPage === 'user_management.php' ? 'ph-users text-blue-600' : 'ph-users' ?> text-xl"></i>
                    จัดการผู้ใช้งาน (Users)
                </a>
            </li>
        <li>
                <a href="<?= BASE_URL ?>views/admin/departments_manage.php" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $currentPage === 'departments_manage.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="ph <?= $currentPage === 'departments_manage.php' ? 'ph-buildings text-blue-600' : 'ph-buildings' ?> text-xl"></i>
                    จัดการแผนก (Departments)
                </a>
            </li>
        
        </ul>


        
        <p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">ระบบ</p>
        <ul class="space-y-1">
            <li>
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 text-gray-500 hover:bg-gray-50 hover:text-gray-900 opacity-60 cursor-not-allowed" title="เร็วๆ นี้">
                    <i class="ph ph-clock-counter-clockwise text-xl"></i>
                    ประวัติกิจกรรม (Logs)
                </a>
            </li>
        </ul>

        <?php else: ?>
        <!-- ========================================== -->
        <!-- เมนูสำหรับผู้ใช้งานทั่วไป (User) -->
        <!-- ========================================== -->
        <p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">พื้นที่ของฉัน (My Workspace)</p>
        <ul class="space-y-1 mb-8">
            <li>
                <a href="<?= BASE_URL ?>views/user/dashboard.php" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $currentPage === 'dashboard.php' ? 'bg-blue-50 text-blue-700 font-semibold shadow-sm border border-blue-100/50' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="ph <?= $currentPage === 'dashboard.php' ? 'ph-squares-four text-blue-600' : 'ph-squares-four' ?> text-xl"></i>
                    ภาพรวมแผนก (My Team)
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>views/user/directory.php" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $currentPage === 'directory.php' ? 'bg-blue-50 text-blue-700 font-semibold shadow-sm border border-blue-100/50' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="ph <?= $currentPage === 'directory.php' ? 'ph-address-book text-blue-600' : 'ph-address-book' ?> text-xl"></i>
                    สมุดรายชื่อ (Directory)
                </a>
            </li>
        </ul>
        <?php endif; ?>

        <!-- 🌟 เริ่มต้นโค้ดเมนูแก้ไขโปรไฟล์ 🌟 -->
        <div class="mt-8 mb-6 px-4">
            <button onclick="if(typeof openMyProfileModal === 'function') openMyProfileModal(); else window.location.href='<?= BASE_URL ?>views/user/directory.php';" 
                    class="w-full relative group overflow-hidden rounded-2xl p-[1.5px] transition-all duration-300 hover:shadow-[0_8px_20px_rgb(59,130,246,0.25)] text-left">
                
                <!-- Animated Gradient Border -->
                <div class="absolute inset-0 bg-gradient-to-r from-blue-400 via-indigo-500 to-purple-500 opacity-50 group-hover:opacity-100 transition-opacity duration-300"></div>
                
                <!-- Inner Content -->
                <div class="relative flex items-center gap-3 bg-white px-4 py-3.5 rounded-[14px] transition-all duration-300 group-hover:bg-opacity-95">
                    <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 text-indigo-600 group-hover:scale-110 group-hover:text-blue-600 transition-all duration-300 shadow-sm">
                        <i class="ph-fill ph-user-circle-gear text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <span class="block text-sm font-bold text-slate-800 group-hover:text-indigo-700 transition-colors">โปรไฟล์ของฉัน</span>
                        <span class="block text-[0.65rem] text-slate-500 font-medium mt-0.5">จัดการข้อมูลติดต่อส่วนตัว</span>
                    </div>
                    <div class="w-6 h-6 rounded-full bg-slate-50 flex items-center justify-center group-hover:bg-indigo-50 transition-colors">
                        <i class="ph ph-caret-right text-xs text-slate-400 group-hover:text-indigo-600 transition-colors"></i>
                    </div>
                </div>
            </button>
        </div>
        <!-- 🌟 สิ้นสุดโค้ดเมนูแก้ไขโปรไฟล์ 🌟 -->

        <!-- 🌟 เริ่มต้น 4 ปุ่มสถานะ (Status Control - Wow Design) 🌟 -->
        <div class="px-4 pb-4">
            <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-widest mb-3 pl-1">อัปเดตสถานะ (My Status)</p>
            <div class="grid grid-cols-2 gap-3">
                
                <!-- 1. ว่าง (Available) -->
                <button onclick="updateMyWorkStatus('available')" class="relative group overflow-hidden rounded-2xl p-3 bg-white border border-emerald-100 hover:border-transparent hover:shadow-[0_8px_15px_rgba(16,185,129,0.15)] transition-all duration-300 hover:-translate-y-0.5 flex flex-col items-center gap-2">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 to-green-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:bg-gradient-to-br group-hover:from-emerald-400 group-hover:to-green-500 group-hover:text-white shadow-sm transition-all duration-300">
                        <i class="ph-fill ph-check-circle text-xl group-hover:scale-110 transition-transform"></i>
                    </div>
                    <span class="relative text-xs font-bold text-slate-700 group-hover:text-emerald-700 transition-colors">ว่าง</span>
                </button>

                <!-- 2. ติดสาย (On a call) -->
                <button onclick="updateMyWorkStatus('on_call')" class="relative group overflow-hidden rounded-2xl p-3 bg-white border border-indigo-100 hover:border-transparent hover:shadow-[0_8px_15px_rgba(99,102,241,0.15)] transition-all duration-300 hover:-translate-y-0.5 flex flex-col items-center gap-2">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-blue-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center group-hover:bg-gradient-to-br group-hover:from-indigo-400 group-hover:to-blue-500 group-hover:text-white shadow-sm transition-all duration-300">
                        <i class="ph-fill ph-phone-call text-xl group-hover:scale-110 transition-transform"></i>
                    </div>
                    <span class="relative text-xs font-bold text-slate-700 group-hover:text-indigo-700 transition-colors">ติดสาย</span>
                </button>

                <!-- 3. ไม่อยู่ (Away) -->
                <button onclick="updateMyWorkStatus('away')" class="relative group overflow-hidden rounded-2xl p-3 bg-white border border-amber-100 hover:border-transparent hover:shadow-[0_8px_15px_rgba(245,158,11,0.15)] transition-all duration-300 hover:-translate-y-0.5 flex flex-col items-center gap-2">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-50 to-orange-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center group-hover:bg-gradient-to-br group-hover:from-amber-400 group-hover:to-orange-500 group-hover:text-white shadow-sm transition-all duration-300">
                        <i class="ph-fill ph-clock text-xl group-hover:scale-110 transition-transform"></i>
                    </div>
                    <span class="relative text-xs font-bold text-slate-700 group-hover:text-amber-700 transition-colors">ไม่อยู่</span>
                </button>

                <!-- 4. ไม่ว่าง (Busy) -->
                <button onclick="updateMyWorkStatus('busy')" class="relative group overflow-hidden rounded-2xl p-3 bg-white border border-rose-100 hover:border-transparent hover:shadow-[0_8px_15px_rgba(244,63,94,0.15)] transition-all duration-300 hover:-translate-y-0.5 flex flex-col items-center gap-2">
                    <div class="absolute inset-0 bg-gradient-to-br from-rose-50 to-red-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center group-hover:bg-gradient-to-br group-hover:from-rose-400 group-hover:to-red-500 group-hover:text-white shadow-sm transition-all duration-300">
                        <i class="ph-fill ph-minus-circle text-xl group-hover:scale-110 transition-transform"></i>
                    </div>
                    <span class="relative text-xs font-bold text-slate-700 group-hover:text-rose-700 transition-colors">ไม่ว่าง</span>
                </button>

            </div>
        
        <!-- 🌟 ปุ่มเปลี่ยนรหัสผ่าน (เพิ่มใหม่) 🌟 -->
            <button onclick="openChangePasswordModal()" class="w-full mt-4 relative group overflow-hidden rounded-2xl p-3 bg-slate-50 border border-slate-200 hover:border-transparent hover:shadow-[0_8px_15px_rgba(71,85,105,0.15)] transition-all duration-300 hover:-translate-y-0.5 flex items-center justify-center gap-3">
                <div class="absolute inset-0 bg-gradient-to-r from-slate-700 to-slate-800 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <i class="ph-fill ph-lock-key text-lg text-slate-600 group-hover:text-white transition-colors"></i>
                <span class="text-sm font-bold text-slate-700 group-hover:text-white transition-colors">เปลี่ยนรหัสผ่าน</span>
            </button>
        
        </div>
        <!-- 🌟 สิ้นสุด 4 ปุ่มสถานะ 🌟 -->

    </div>

    <!-- User Profile & Logout Bottom -->
    <div class="p-4 border-t border-gray-100">
        <div class="bg-gray-50 rounded-2xl p-4 flex items-center justify-between border border-gray-200/60">
            <div class="flex items-center gap-3 overflow-hidden">
                
            <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold flex-shrink-0 overflow-hidden">
    <?php if (!empty($currentUser['avatar_url'])): ?>
        <img src="<?= BASE_URL . $currentUser['avatar_url'] ?>" alt="Profile" class="w-full h-full object-cover">
    <?php else: ?>
        <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
    <?php endif; ?>
</div>
                
                <div class="overflow-hidden">
                    <p class="text-sm font-semibold text-gray-900 truncate"><?= htmlspecialchars($currentUser['full_name']) ?></p>
                    <p class="text-xs text-gray-500 truncate capitalize"><?= htmlspecialchars($currentUser['role']) ?></p>
                </div>
            </div>
            <!-- ปุ่ม Logout -->
            <button onclick="handleLogout()" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors tooltip flex-shrink-0" title="ออกจากระบบ">
                <i class="ph ph-sign-out text-xl"></i>
            </button>
        </div>
    </div>
</aside>

<script>
    // สคริปต์สำหรับการ Logout จากเมนู Sidebar
    async function handleLogout() {
        if(confirm('ยืนยันการออกจากระบบ?')) {
            try {
                const response = await fetch('<?= BASE_URL ?>api/auth/logout.php');
                const data = await response.json();
                if(data.status === 'success') {
                    window.location.href = data.redirect;
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาดในการออกจากระบบ');
            }
        }
    }
</script>

<script>
    // สคริปต์สำหรับปุ่มอัปเดตสถานะการทำงาน
    async function updateMyWorkStatus(status) {
        try {
            const response = await fetch('<?= BASE_URL ?>api/contacts/update_work_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: status })
            });
            const result = await response.json();
            
            if (result.status === 'success') {
                showToast(result.message, 'success');
                // ถ้าเปิดอยู่หน้า Directory ให้รีเฟรชตารางอัตโนมัติ
                if (typeof loadContacts === 'function') {
                    loadContacts();
                }
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('เกิดข้อผิดพลาดในการเชื่อมต่อระบบ', 'error');
        }
    }
</script>