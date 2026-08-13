<?php
// ==========================================
// Admin View: User Management (System Logins)
// Path: views/admin/user_management.php
// ==========================================

// 1. โหลดไฟล์ Config และ Middleware
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Database.php';

// 2. บังคับว่าต้องเป็น Admin เท่านั้น
Auth::requireAdmin();

// 3. กำหนดชื่อหน้าเว็บ
$pageTitle = 'จัดการผู้ใช้งานระบบ';

// 4. เรียกใช้ Header (โหลด CSS, Sidebar, Topbar อัตโนมัติ)
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-6 animate-fade-in relative pb-10">
    
    <!-- Page Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">จัดการผู้ใช้งานระบบ (Users)</h1>
            <p class="text-sm text-gray-500 mt-1">กำหนดสิทธิ์การเข้าถึง และจัดการบัญชีสำหรับล็อกอินเข้าระบบ</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <!-- ปุ่มเพิ่มผู้ใช้งานใหม่ -->
            <button onclick="openUserModal()" class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2.5 rounded-xl shadow-md shadow-brand-500/30 transition-all duration-200 hover:-translate-y-0.5">
                <i class="ph ph-user-plus text-lg"></i>
                เพิ่มผู้ใช้งาน
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
            <input type="text" id="searchUserInput" 
                   class="w-full pl-11 pr-4 py-2.5 bg-white/80 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all shadow-sm"
                   placeholder="ค้นหาชื่อ, หรือบัญชีผู้ใช้งาน (Username)...">
        </div>

        <!-- Role Filter -->
        <div class="w-full md:w-56 relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                <i class="ph ph-shield text-lg"></i>
            </div>
            <select id="roleFilter" class="w-full pl-11 pr-10 py-2.5 bg-white/80 border border-gray-200 rounded-xl text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all shadow-sm appearance-none cursor-pointer">
                <option value="">ทุกระดับสิทธิ์ (All Roles)</option>
                <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                <option value="user">User (ผู้ใช้งานทั่วไป)</option>
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
                        <th class="text-left w-2/5">ชื่อ - นามสกุล</th>
                        <th class="text-left w-1/5">บัญชีผู้ใช้ (Username)</th>
                        <th class="text-left w-1/5">ระดับสิทธิ์ (Role)</th>
                        <th class="text-left w-1/5">วันที่สร้าง</th>
                        <th class="text-center w-24">จัดการ</th> 
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <!-- ข้อมูลจะถูกดึงมาเติมตรงนี้ด้วย JS -->
                    <tr>
                        <td colspan="5" class="text-center py-10">
                            <div class="flex justify-center items-center gap-3 text-gray-400">
                                <div class="spinner-ring border-gray-300"></div>
                                <span class="font-medium text-sm">กำลังโหลดข้อมูลบัญชี...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Section -->
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-slate-50/50">
            <div id="userPageInfo" class="text-sm text-gray-500 font-medium">
                <!-- โชว์จำนวนหน้า -->
            </div>
            <div id="userPaginationContainer" class="flex gap-1.5">
                <!-- ปุ่มเปลี่ยนหน้าต่างๆ -->
            </div>
        </div>
    </div>

</div>

<!-- ==========================================
     MODAL: Add / Edit User Form
     ========================================== -->
<div id="userModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <!-- พื้นหลังสีดำโปร่งแสง -->
    <div class="modal-backdrop absolute inset-0"></div>
    
    <!-- ตัวกล่อง Modal -->
    <div class="bg-white rounded-2xl w-full max-w-lg mx-4 z-10 modal-content shadow-2xl flex flex-col max-h-[90vh]">
        
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/50 rounded-t-2xl">
            <h3 class="text-lg font-bold text-gray-900" id="userModalTitle">เพิ่มผู้ใช้งานใหม่</h3>
            <button onclick="closeModal('userModal')" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto custom-scrollbar">
            <form id="userForm" class="space-y-5">
                
                <!-- Input ซ่อน สำหรับเก็บ ID กรณีแก้ไขข้อมูล -->
                <input type="hidden" id="form_user_id" name="id">

                <!-- Full Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ชื่อ - นามสกุล (Display Name) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="ph ph-identification-card"></i>
                        </div>
                        <input type="text" id="form_full_name" name="full_name" required
                               class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none"
                               placeholder="เช่น สมชาย ใจดี">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">บัญชีผู้ใช้ (Username) <span class="text-red-500">*</span></label>
                        <input type="text" id="form_username" name="username" required
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none"
                               placeholder="เช่น admin">
                    </div>
                    
                    <!-- Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">ระดับสิทธิ์ (Role) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select id="form_role" name="role" required
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none appearance-none cursor-pointer">
                                <option value="user">User (ผู้ใช้งาน)</option>
                                <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-caret-down"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password -->
                <div class="border-t border-gray-100 pt-4 mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">รหัสผ่าน (Password)</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="ph ph-lock-key"></i>
                        </div>
                        <input type="password" id="form_password" name="password"
                               class="w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none"
                               placeholder="กรอกรหัสผ่าน">
                    </div>
                    <p class="text-xs text-gray-400 mt-1.5" id="passwordHelpText">
                        * กรณีเพิ่มใหม่ กรุณากำหนดรหัสผ่าน<br>
                        * กรณีแก้ไข หากปล่อยว่างไว้ จะใช้รหัสผ่านเดิม
                    </p>
                </div>

                <!-- Hidden form submit button to allow Enter key submission -->
                <button type="submit" class="hidden"></button>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 bg-gray-50 rounded-b-2xl">
            <button type="button" onclick="closeModal('userModal')" class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">
                ยกเลิก
            </button>
            <button type="button" onclick="document.getElementById('userForm').requestSubmit()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 shadow-md shadow-brand-500/30 transition-all duration-200 hover:-translate-y-0.5">
                <i class="ph ph-floppy-disk text-lg"></i>
                บันทึกผู้ใช้งาน
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
    <!-- ไฟล์สำหรับจัดการ Logic การดึง/บันทึก User โดยเฉพาะ -->
    <script src="<?= BASE_URL ?>assets/js/users.js"></script>

</body>
</html>