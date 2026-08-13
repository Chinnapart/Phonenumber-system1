<?php
// ==========================================
// User View: Phone Directory (สมุดรายชื่อ)
// Path: views/user/directory.php
// ==========================================

// 1. โหลดไฟล์ Config และ Middleware
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Database.php';

// 2. บังคับว่าต้อง Login เท่านั้น (เข้าได้ทั้ง Admin และ User)
Auth::requireLogin();

// 3. กำหนดชื่อหน้าเว็บ และดึงข้อมูลแผนกมาทำ Dropdown
$pageTitle = 'สมุดรายชื่อ (Directory)';
$departments = Database::getAll("SELECT id, name FROM departments ORDER BY name ASC");

// 4. เรียกใช้ Header (ซึ่งจะโหลด CSS, Sidebar และ Topbar มาให้อัตโนมัติ)
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* 
     * HACK: ซ่อนคอลัมน์ "Action (ปุ่มแก้ไข/ลบ)" สำหรับผู้ใช้ทั่วไป 
     * เนื่องจาก contacts.js ถูกเขียนมาให้ render ปุ่มด้วย เราจึงใช้ CSS ซ่อนคอลัมน์สุดท้ายทิ้ง
     */
    .custom-table th:last-child,
    .custom-table td:last-child {
        display: none !important;
    }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-fade-in">
    
    <!-- Page Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">สมุดรายชื่อบุคลากร</h1>
            <p class="text-sm text-gray-500 mt-1">ค้นหาเบอร์โทรภายในและตรวจสอบสถานะการเชื่อมต่อ</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="exportToCSV(this)" class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2.5 rounded-xl shadow-sm transition-all duration-200">
                <i class="ph ph-download-simple text-lg"></i>
                Export CSV
            </button>
        </div>
    </div>

    <!-- Filter Bar (Glassmorphism Effect) -->
    <div class="glass-panel p-4 md:p-5 rounded-2xl flex flex-col md:flex-row gap-4">
        <!-- Search -->
        <div class="flex-1 relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-brand-500 transition-colors">
                <i class="ph ph-magnifying-glass text-lg"></i>
            </div>
            <input type="text" id="searchInput" 
                   class="w-full pl-11 pr-4 py-2.5 bg-white/80 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all shadow-sm"
                   placeholder="ค้นหาชื่อ, นามสกุล, เบอร์ต่อ, IP...">
        </div>

        <!-- Department Filter -->
        <div class="w-full md:w-64 relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                <i class="ph ph-buildings text-lg"></i>
            </div>
            <select id="deptFilter" class="w-full pl-11 pr-10 py-2.5 bg-white/80 border border-gray-200 rounded-xl text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all shadow-sm appearance-none cursor-pointer">
                <option value="">ทุกแผนก (All)</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400">
                <i class="ph ph-caret-down"></i>
            </div>
        </div>

        <!-- Status Filter -->
        <div class="w-full md:w-48 relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                <i class="ph ph-broadcast text-lg"></i>
            </div>
            <select id="statusFilter" class="w-full pl-11 pr-10 py-2.5 bg-white/80 border border-gray-200 rounded-xl text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all shadow-sm appearance-none cursor-pointer">
                <option value="">ทุกสถานะ</option>
                <option value="online">🟢 Online</option>
                <option value="offline">🔴 Offline</option>
                <option value="unknown">⚪️ Unknown</option>
            </select>
            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400">
                <i class="ph ph-caret-down"></i>
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="table-container">
            <table class="custom-table whitespace-nowrap">
                <thead>
                    <tr>
                        <th class="text-left w-2/6">ชื่อ - นามสกุล</th>
                        <th class="text-left w-1/6">แผนก</th>
                        <th class="text-left w-1/6">เบอร์ต่อ (Ext.)</th>
                        <th class="text-left w-1/6">IP Address</th>
                        <th class="text-left w-1/6">สถานะ</th>
                        <!-- ขา Render ใน contacts.js จะมีคอลัมน์ Actions แทรกลงมา แต่จะถูกซ่อนด้วย CSS ด้านบน -->
                        <th class="text-center w-24">จัดการ</th> 
                    </tr>
                </thead>
                <tbody id="contactsTableBody">
                    <!-- ข้อมูลรายชื่อจะถูกเติมลงมาตรงนี้ด้วย JavaScript (contacts.js) -->
                    <tr>
                        <td colspan="6" class="text-center py-10">
                            <div class="flex justify-center items-center gap-3 text-gray-400">
                                <div class="spinner-ring border-gray-300"></div>
                                <span class="font-medium text-sm">กำลังโหลดข้อมูล...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Section -->
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-slate-50/50">
            <div id="pageInfo" class="text-sm text-gray-500 font-medium">
                <!-- โชว์จำนวนหน้า (เช่น หน้า 1 จาก 5) -->
            </div>
            <div id="paginationContainer" class="flex gap-1.5">
                <!-- ปุ่มเปลี่ยนหน้าต่างๆ จะถูกสร้างด้วย JavaScript -->
            </div>
        </div>
    </div>

</div>

<?php
// เนื่องจากเรายังไม่ได้แยกไฟล์ footer.php ผมจึงทำการปิดแท็กและแนบ Script ไว้ตรงนี้เลยครับ
?>
        </main> <!-- ปิดแท็ก main จาก header.php -->
    </div> <!-- ปิดแท็ก div.flex-1 จาก header.php -->

    <!-- ========================================== -->
    <!-- Core Scripts -->
    <!-- ========================================== -->
    <!-- นำเข้าฟังก์ชันหลัก (Toast, API Fetch) -->
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
    
    <!-- นำเข้าฟังก์ชันจัดการตารางรายชื่อ (ค้นหา, แบ่งหน้า, โหลดข้อมูล) -->
    <script src="<?= BASE_URL ?>assets/js/contacts.js"></script>

</body>
</html>