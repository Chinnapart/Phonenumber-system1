<?php
// ==========================================
// Admin View: Contacts Management (CRUD)
// Path: views/admin/contacts_manage.php
// ==========================================

// 1. โหลดไฟล์ Config และ Middleware
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Database.php';

// 2. บังคับว่าต้องเป็น Admin เท่านั้น
Auth::requireAdmin();

// 3. กำหนดชื่อหน้าเว็บ และดึงข้อมูลแผนกมาทำ Dropdown ใน Form
$pageTitle = 'จัดการสมุดรายชื่อ';
$departments = Database::getAll("SELECT id, name FROM departments ORDER BY name ASC");

// 4. เรียกใช้ Header (โหลด CSS, Sidebar, Topbar อัตโนมัติ)
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-6 animate-fade-in relative pb-10">
    
    <!-- Page Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">จัดการสมุดรายชื่อ (Manage Contacts)</h1>
            <p class="text-sm text-gray-500 mt-1">เพิ่ม แก้ไข ลบข้อมูล และตรวจสอบสถานะ IP ภายในองค์กร</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <!-- ปุ่ม Export -->
            <button onclick="exportToCSV(this)" class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2.5 rounded-xl shadow-sm transition-all duration-200">
                <i class="ph ph-download-simple text-lg"></i>
                Export
            </button>
            <!-- ปุ่มสแกน Ping -->
            <button onclick="runBatchPing(this)" class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium px-4 py-2.5 rounded-xl shadow-sm transition-all duration-200">
                <i class="ph ph-radar text-lg text-emerald-400"></i>
                Ping Scan
            </button>
            <!-- ปุ่มเพิ่มรายชื่อใหม่ -->
            <button onclick="openCreateModal()" class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2.5 rounded-xl shadow-md shadow-brand-500/30 transition-all duration-200 hover:-translate-y-0.5">
                <i class="ph ph-plus-circle text-lg"></i>
                เพิ่มผู้ติดต่อ
            </button>
        </div>
    </div>

    <!-- Filter Bar (Glassmorphism Effect) -->
    <div class="glass-panel p-4 rounded-2xl flex flex-col md:flex-row gap-4">
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
        <div class="w-full md:w-56 relative">
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
        
        <!-- ⭐⭐⭐ 1. เพิ่ม overflow-x-auto และ custom-scrollbar ตรงนี้ ⭐⭐⭐ -->
        <div class="table-container overflow-x-auto custom-scrollbar">
            
            <!-- ⭐⭐⭐ 2. แนะนำให้เพิ่ม w-full เข้าไปที่แท็ก table ด้วยครับ ⭐⭐⭐ -->
            <table class="custom-table whitespace-nowrap w-full">
                <thead>
                    <tr>
                        <th class="text-center w-24">EMP</th>
                        <th class="text-center w-2/6">ชื่อ - นามสกุล</th>
                        <th class="text-center w-2/6">สถานะUser</th>
                        <th class="text-center w-1/6">แผนก</th>
                        <th class="text-center w-1/6">เบอร์</th>
                        <th class="text-center w-1/6">IP</th>
                        <th class="text-center w-1/6">อุปกรณ์</th>
                        <th class="text-center w-32">จัดการ</th> 
                    </tr>
                </thead>
                <tbody id="contactsTableBody">
                    <!-- ข้อมูลจะถูกดึงมาเติมตรงนี้ด้วย JS -->
                    <tr>
                        <td colspan="8" class="text-center py-10">
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
                <!-- โชว์จำนวนหน้า -->
            </div>
            <div id="paginationContainer" class="flex gap-1.5">
                <!-- ปุ่มเปลี่ยนหน้าต่างๆ -->
            </div>
        </div>
    </div>

<!-- ==========================================
     MODAL: Add / Edit Contact Form
     ========================================== -->
<div id="contactModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <!-- พื้นหลังสีดำโปร่งแสง (คลิกเพื่อปิดได้) -->
    <div class="modal-backdrop absolute inset-0"></div>
    
    <!-- ตัวกล่อง Modal -->
    <div class="bg-white rounded-2xl w-full max-w-lg mx-4 z-10 modal-content shadow-2xl flex flex-col max-h-[90vh]">
        
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/50 rounded-t-2xl">
            <h3 class="text-lg font-bold text-gray-900" id="modalTitle">เพิ่มผู้ติดต่อใหม่</h3>
            <button onclick="closeModal('contactModal')" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>

        <!-- Modal Body (เลื่อนขึ้นลงได้ถ้าจอเล็ก) -->
        <div class="p-6 overflow-y-auto custom-scrollbar">
            <form id="contactForm" class="space-y-5">
                
                <!-- Input ซ่อน สำหรับเก็บ ID กรณีแก้ไขข้อมูล -->
                <input type="hidden" id="form_id" name="id">

                <!-- 🌟 เพิ่มใหม่: รหัสพนักงาน (Employee ID) 🌟 -->
<div>
    <label class="block text-sm font-medium text-gray-700 mb-1.5">รหัสพนักงาน (Employee ID) <span class="text-red-500">*</span></label>
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
            <i class="ph ph-identification-badge"></i> 
        </div>
        <!-- ❗เช็คตรงนี้ให้ดีครับ name="employee_id" ต้องสะกดแบบนี้เป๊ะๆ❗ -->
        <input type="text" id="form_employee_id" name="employee_id" required 
               class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none"
               placeholder="เช่น EMP001">
    </div>
</div>

                <div class="grid grid-cols-2 gap-5">
                    <!-- First Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">ชื่อ (First Name) <span class="text-red-500">*</span></label>
                        <input type="text" id="form_first_name" name="first_name" required
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none"
                               placeholder="ชื่อจริง">
                    </div>
                    <!-- Last Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">นามสกุล (Last Name) <span class="text-red-500">*</span></label>
                        <input type="text" id="form_last_name" name="last_name" required
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none"
                               placeholder="นามสกุล">
                    </div>
                </div>

                <!-- Job Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ตำแหน่ง (Job Title)</label>
                    <input type="text" id="form_job_title" name="job_title"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none"
                           placeholder="เช่น IT Support, Manager">
                </div>

                <!-- Department -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">แผนก (Department)</label>
                    <div class="relative">
                        <select id="form_department_id" name="department_id"
                                class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none appearance-none">
                            <option value="">-- ไม่ระบุแผนก --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400">
                            <i class="ph ph-caret-down"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <!-- Extension -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">เบอร์ต่อ (Extension)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-phone"></i>
                            </div>
                            <input type="text" id="form_extension" name="extension"
                                   class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none"
                                   placeholder="เช่น 101">
                        </div>
                    </div>
                    <!-- IP Address -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">IP Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-laptop"></i>
                            </div>
                            <input type="text" id="form_ip_address" name="ip_address"
                                   class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none"
                                   placeholder="192.168.x.x">
                        </div>
                    </div>
                </div>

                <!-- Hidden form submit button to allow Enter key submission -->
                <button type="submit" class="hidden"></button>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 bg-gray-50 rounded-b-2xl">
            <button type="button" onclick="closeModal('contactModal')" class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">
                ยกเลิก
            </button>
            <button type="button" onclick="document.getElementById('contactForm').requestSubmit()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 shadow-md shadow-brand-500/30 transition-all duration-200 hover:-translate-y-0.5">
                <i class="ph ph-floppy-disk text-lg"></i>
                บันทึกข้อมูล
            </button>
        </div>
    </div>
</div>

<?php
// เนื่องจากเราไม่ได้แยกไฟล์ footer.php ผมจึงทำการปิดแท็กตรงนี้ครับ
?>
        </main> <!-- ปิดแท็ก main จาก header.php -->
    </div> <!-- ปิดแท็ก div.flex-1 จาก header.php -->

    <!-- ========================================== -->
    <!-- Core Scripts -->
    <!-- ========================================== -->
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
    <script src="<?= BASE_URL ?>assets/js/contacts.js"></script>

</body>
</html>