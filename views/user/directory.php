<?php
// ==========================================
// User View: Phone Directory (สมุดรายชื่อ)
// Path: views/user/directory.php
// ==========================================

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Database.php';

Auth::requireLogin();

$pageTitle = 'สมุดรายชื่อ (Directory)';
$departments = Database::getAll("SELECT id, name FROM departments ORDER BY name ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* ซ่อนคอลัมน์จัดการ สำหรับ User ทั่วไป */
    .custom-table th:last-child,
    .custom-table td:last-child {
        display: none !important;
    }
</style>

<div class="max-w-7xl mx-auto space-y-6 animate-fade-in relative pb-10">
    
    <!-- Page Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">สมุดรายชื่อบุคลากร</h1>
            <p class="text-sm text-gray-500 mt-1">ค้นหาเบอร์โทรภายในและตรวจสอบสถานะการเชื่อมต่อ</p>
        </div>
        <div class="flex items-center gap-3">
           
            <!-- 🌟 ปุ่มดูแดชบอร์ดแผนกอื่น (Wow & Luxury Design) 🌟 -->
            <div class="relative group">
                <button class="animate-shine relative inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-white overflow-hidden transition-all duration-300 hover:scale-105 hover:shadow-[0_8px_25px_rgba(168,85,247,0.4)]">
                    <!-- พื้นหลัง Gradient สีม่วง-ชมพู หรูหรา -->
                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 animate-gradient-xy"></div>
                    
                    <!-- เนื้อหาปุ่ม -->
                    <span class="relative z-10 flex items-center gap-2 drop-shadow-md">
                        <i class="ph-fill ph-magic-wand text-xl animate-pulse text-yellow-300"></i>
                        ดูแดชบอร์ดแผนกอื่น
                        <i class="ph ph-caret-down text-sm ml-0.5 transition-transform duration-300 group-hover:rotate-180"></i>
                    </span>
                </button>

                <!-- 🌟 Dropdown Menu แผนกต่างๆ 🌟 -->
                <div class="absolute right-0 mt-3 w-64 bg-white/80 backdrop-blur-2xl rounded-2xl shadow-[0_15px_50px_rgba(0,0,0,0.15)] border border-white/60 opacity-0 invisible translate-y-3 group-hover:opacity-100 group-hover:visible group-hover:translate-y-0 transition-all duration-300 z-50 overflow-hidden before:absolute before:-top-2 before:right-6 before:w-4 before:h-4 before:bg-white/80 before:rotate-45 before:backdrop-blur-2xl before:border-l before:border-t before:border-white/60">
                    <div class="p-2 max-h-[60vh] overflow-y-auto custom-scrollbar relative z-10">
                        <p class="text-[0.65rem] font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 px-3 py-2 uppercase tracking-widest">
                            เลือกแผนกที่ต้องการ
                        </p>
                        
                        <?php foreach ($departments as $dept): ?>
                            <a href="<?= BASE_URL ?>views/user/dashboard.php?dept_id=<?= $dept['id'] ?>" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 hover:text-indigo-700 transition-all group/item">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center group-hover/item:bg-white group-hover/item:shadow-md group-hover/item:text-indigo-600 transition-all duration-300">
                                    <i class="ph-fill ph-buildings text-lg"></i>
                                </div>
                                <?= htmlspecialchars($dept['name']) ?>
                            </a>
                        <?php endforeach; ?>
                        
                    </div>
                </div>
            </div>
            <!-- 🌟 สิ้นสุดปุ่มแดชบอร์ดแผนกอื่น 🌟 -->

            <!-- 🌟 ปุ่มแก้ไขโปรไฟล์ของฉัน (ของเดิม) -->
            <button onclick="openMyProfileModal()" ...>
        
        
        
        <!-- 🌟 ปุ่มแก้ไขโปรไฟล์ของฉัน (เพิ่มใหม่) -->
            <button onclick="openMyProfileModal()" class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2.5 rounded-xl shadow-md shadow-brand-500/30 transition-all duration-200 hover:-translate-y-0.5">
                <i class="ph ph-user-circle text-lg"></i>
                โปรไฟล์ของฉัน
            </button>
            
            <button onclick="exportToCSV(this)" class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2.5 rounded-xl shadow-sm transition-all duration-200">
                <i class="ph ph-download-simple text-lg"></i>
                Export CSV
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="glass-panel p-4 md:p-5 rounded-2xl flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-brand-500 transition-colors">
                <i class="ph ph-magnifying-glass text-lg"></i>
            </div>
            <input type="text" id="searchInput" class="w-full pl-11 pr-4 py-2.5 bg-white/80 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all shadow-sm" placeholder="ค้นหาชื่อ, นามสกุล, เบอร์ต่อ, IP...">
        </div>

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
                        <th class="text-center w-2/6">ชื่อ - นามสกุล</th>
                        <th class="text-center w-28">สถานะ User</th>
                        <th class="text-center w-1/6">แผนก</th>
                        <th class="text-center w-1/6">เบอร์</th>
                        <th class="text-center w-1/6">IP </th>
                        <th class="text-center w-1/6">อุปกรณ์</th>
                        <th class="text-center w-24">จัดการ</th> 
                    </tr>
                </thead>
                <tbody id="contactsTableBody">
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
        
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-slate-50/50">
            <div id="pageInfo" class="text-sm text-gray-500 font-medium"></div>
            <div id="paginationContainer" class="flex gap-1.5"></div>
        </div>
    </div>
</div>

<!-- ==========================================
     🌟 MODAL: Edit My Profile (สำหรับ User ทั่วไป)
     ========================================== -->
<div id="myProfileModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="modal-backdrop absolute inset-0"></div>
    <div class="bg-white rounded-2xl w-full max-w-lg mx-4 z-10 modal-content shadow-2xl flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/50 rounded-t-2xl">
            <div class="flex items-center gap-2 text-brand-600">
                <i class="ph-fill ph-user-circle text-2xl"></i>
                <h3 class="text-lg font-bold text-gray-900">โปรไฟล์ของฉัน</h3>
            </div>
            <button onclick="closeModal('myProfileModal')" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>

        <div class="p-6 overflow-y-auto custom-scrollbar">
            <form id="myProfileForm" class="space-y-5" onsubmit="submitMyProfile(event)">
                <input type="hidden" id="my_profile_id" name="id">

                <!-- เพิ่มส่วนอัปโหลดรูปภาพตรงนี้ -->
    <div class="flex flex-col items-center mb-6">
        <div class="relative w-24 h-24 mb-2">
            <img id="my_profile_avatar_preview" src="https://ui-avatars.com/api/?name=User&background=random" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md">
            <label for="my_profile_avatar" class="absolute bottom-0 right-0 bg-brand-500 text-white p-1.5 rounded-full cursor-pointer shadow-sm hover:bg-brand-600 transition-colors">
                <i class="ph ph-camera"></i>
            </label>
        </div>
        <input type="file" id="my_profile_avatar" name="avatar" accept="image/jpeg, image/png" class="hidden" onchange="previewAvatar(this)">
        <p class="text-[0.65rem] text-gray-400">รองรับไฟล์ JPG, PNG</p>
    </div>
    <!-- สิ้นสุดส่วนอัปโหลดรูปภาพ -->

                <!-- แสดงชื่อนามสกุล แบบ Read-only -->
                <div class="p-4 bg-blue-50/50 border border-blue-100 rounded-xl mb-4">
                    <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide mb-1">เชื่อมโยงบัญชีจากชื่อ</p>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <input type="text" id="my_profile_fn" class="w-full bg-transparent text-gray-700 font-medium outline-none cursor-not-allowed" readonly placeholder="ชื่อ">
                        </div>
                        <div class="flex-1">
                            <input type="text" id="my_profile_ln" class="w-full bg-transparent text-gray-700 font-medium outline-none cursor-not-allowed" readonly placeholder="นามสกุล">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ตำแหน่ง (Job Title)</label>
                    <input type="text" id="my_profile_jt" name="job_title" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">แผนก (Department)</label>
                    <div class="relative">
                        <select id="my_profile_dept" name="department_id" class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none appearance-none">
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">เบอร์ต่อ (Extension)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400"><i class="ph ph-phone"></i></div>
                            <input type="text" id="my_profile_ext" name="extension" class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">เบอร์มือถือ (Mobile)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400"><i class="ph ph-device-mobile"></i></div>
                            <input type="text" id="my_profile_mobile" name="mobile_number" class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none">
                        </div>
                    </div>
                </div>

<!-- 🌟 เพิ่มใหม่: Email และ รุ่นโทรศัพท์ 🌟 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- อีเมล -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">อีเมล (E-mail)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-envelope-simple"></i>
                            </div>
                            <input type="email" id="my_profile_email" name="email" 
                                   class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none" 
                                   placeholder="example@company.com">
                        </div>
                    </div>
                    <!-- รุ่นโทรศัพท์ -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">รุ่นโทรศัพท์ (Phone Model)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-device-mobile-camera"></i>
                            </div>
                            <input type="text" id="my_profile_phone_model" name="phone_model" 
                                   class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none" 
                                   placeholder="เช่น iPhone 15, Galaxy S24">
                        </div>
                    </div>
                </div>
                <!-- 🌟 สิ้นสุดส่วนที่เพิ่มใหม่ 🌟 -->

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">IP Address ประจำเครื่อง</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400"><i class="ph ph-laptop"></i></div>
                        <input type="text" id="my_profile_ip" name="ip_address" class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none" placeholder="192.168.x.x">
                    </div>
                    <p class="text-xs text-gray-400 mt-1.5">ใช้สำหรับให้ระบบตรวจสอบสถานะ Online อัตโนมัติ</p>
                </div>
                
                <button type="submit" class="hidden"></button>
            </form>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 bg-gray-50 rounded-b-2xl">
            <button type="button" onclick="closeModal('myProfileModal')" class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">
                ปิด
            </button>
            <button type="button" onclick="document.getElementById('myProfileForm').requestSubmit()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 shadow-md shadow-brand-500/30 transition-all duration-200 hover:-translate-y-0.5">
                <i class="ph ph-floppy-disk text-lg"></i>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </div>
</div>

<?php
// เนื่องจากเรายังไม่ได้แยกไฟล์ footer.php ผมจึงทำการปิดแท็กและแนบ Script ไว้ตรงนี้เลยครับ
?>
       
    <!-- ==========================================
     🌟 MODAL: Change Password
     ========================================== -->
<div id="changePasswordModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="modal-backdrop absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
    <div class="bg-white rounded-2xl w-full max-w-sm mx-4 z-10 modal-content shadow-2xl flex flex-col">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/50 rounded-t-2xl">
            <div class="flex items-center gap-2 text-slate-700">
                <i class="ph-fill ph-lock-key text-2xl"></i>
                <h3 class="text-lg font-bold text-gray-900">เปลี่ยนรหัสผ่าน</h3>
            </div>
            <button onclick="closeModal('changePasswordModal')" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>

        <div class="p-6">
            <form id="changePasswordForm" class="space-y-4" onsubmit="submitChangePassword(event)">
                <!-- รหัสผ่านปัจจุบัน -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">รหัสผ่านปัจจุบัน</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 group-focus-within:text-brand-500">
                            <i class="ph ph-lock-key"></i>
                        </div>
                        <input type="password" name="current_password" required 
                               class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none" placeholder="กรอกรหัสผ่านปัจจุบัน">
                    </div>
                </div>

                <!-- รหัสผ่านใหม่ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">รหัสผ่านใหม่</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 group-focus-within:text-brand-500">
                            <i class="ph ph-shield-check"></i>
                        </div>
                        <input type="password" name="new_password" id="new_password" required minlength="6"
                               class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none" placeholder="ตั้งรหัสผ่านใหม่อย่างน้อย 6 ตัวอักษร">
                    </div>
                </div>

                <!-- ยืนยันรหัสผ่านใหม่ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ยืนยันรหัสผ่านใหม่</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 group-focus-within:text-brand-500">
                            <i class="ph ph-check-circle"></i>
                        </div>
                        <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                               class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง">
                    </div>
                </div>
                
                <button type="submit" class="hidden"></button>
            </form>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 bg-gray-50 rounded-b-2xl">
            <button type="button" onclick="closeModal('changePasswordModal')" class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">
                ยกเลิก
            </button>
            <button type="button" onclick="document.getElementById('changePasswordForm').requestSubmit()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium text-white bg-slate-800 hover:bg-slate-900 shadow-md shadow-slate-500/30 transition-all duration-200 hover:-translate-y-0.5">
                <i class="ph ph-check-circle text-lg"></i>
                บันทึกรหัสผ่าน
            </button>
        </div>
    </div>
</div>
    
    
    </main> <!-- ปิดแท็ก main จาก header.php -->
    </div> <!-- ปิดแท็ก div.flex-1 จาก header.php -->

    <!-- ========================================== -->
    <!-- Core Scripts -->
    <!-- ========================================== -->
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
    <script src="<?= BASE_URL ?>assets/js/contacts.js"></script>

   
    
    <!-- Script จัดการหน้าโปรไฟล์ส่วนตัวของ User -->
    <script>
        
        function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('my_profile_avatar_preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
        
        async function openMyProfileModal() {
            showToast('กำลังโหลดข้อมูลโปรไฟล์...', 'success');
            try {
                const response = await apiCall('api/contacts/my_profile.php');
                if (response.status === 'success') {
                    const data = response.data;
                    document.getElementById('my_profile_id').value = data.id || '';
                    document.getElementById('my_profile_fn').value = data.first_name || '';
                    document.getElementById('my_profile_ln').value = data.last_name || '';
                    document.getElementById('my_profile_jt').value = data.job_title || '';
                    document.getElementById('my_profile_dept').value = data.department_id || '';
                    document.getElementById('my_profile_ext').value = data.extension || '';
                    document.getElementById('my_profile_mobile').value = data.mobile_number || '';
                    document.getElementById('my_profile_ip').value = data.ip_address || '';
                    document.getElementById('my_profile_email').value = data.email || '';
                document.getElementById('my_profile_phone_model').value = data.phone_model || '';

                    // เพิ่มบรรทัดนี้: โหลดรูปโปรไฟล์เดิมมาแสดง ถ้าไม่มีให้ใช้รูป Default
const avatarPreview = document.getElementById('my_profile_avatar_preview');
avatarPreview.src = data.avatar_url ? BASE_URL + data.avatar_url : `https://ui-avatars.com/api/?name=${data.first_name}&background=random`;            
                    
                    openModal('myProfileModal');
                }
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        async function submitMyProfile(e) {
    e.preventDefault();
    const form = e.target;
    
    const btn = document.querySelector('#myProfileModal button.bg-brand-500'); 
    const origText = btn ? btn.innerHTML : '';
    
    if (btn) {
        btn.innerHTML = '<div class="spinner-ring border-white w-4 h-4"></div> บันทึก...';
        btn.disabled = true;
    }

    // เก็บข้อมูลฟอร์มและไฟล์ภาพ
    const formData = new FormData(form);

    try {
        // ใช้ fetch ปกติแทน apiCall เพื่อให้สามารถส่งไฟล์รูป (multipart/form-data) ได้
        const response = await fetch(BASE_URL + 'api/contacts/my_profile.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.status === 'success') {
            showToast(result.message, 'success');
            closeModal('myProfileModal');
            loadContacts(); 
            
            // --- เพิ่มโค้ดส่วนนี้เข้าไปใหม่ ---
            // 1. อัปเดตรูปบน Sidebar (จุดที่คุณต้องการ)
            // หา element รูปโปรไฟล์ที่แถบด้านซ้ายล่าง แล้วอัปเดต src 
            const sidebarAvatar = document.querySelector('aside .rounded-full img'); 
            if (sidebarAvatar) {
                // ดึงรูปใหม่จาก preview หรือจาก result (ถ้า API ส่งกลับมา) 
                const newAvatarUrl = document.getElementById('my_profile_avatar_preview').src;
                sidebarAvatar.src = newAvatarUrl;
            } else {
                // ถ้ารูปเดิมเป็น div วงกลม (ยังไม่มีรูป) อาจจะต้อง reload เพื่อให้แสดงผลถูกต้อง
                window.location.reload(); 
            }
            
            // 2. อัปเดตรูปบน Topbar (มุมขวาบน)
            const topbarAvatar = document.querySelector('header .rounded-full img');
            if (topbarAvatar) {
                const newAvatarUrl = document.getElementById('my_profile_avatar_preview').src;
                topbarAvatar.src = newAvatarUrl;
            }
            // ---------------------------------
            
        } else {
            showToast(result.message, 'error');
        }
    
    
    } catch (error) {
        showToast(error.message || 'เกิดข้อผิดพลาดในการบันทึก', 'error');
    } finally {
        if (btn) {
            btn.innerHTML = origText;
            btn.disabled = false;
        }
    }
}
    // ---------------------------------------------
        // ฟังก์ชันจัดการเปลี่ยนรหัสผ่าน
        // ---------------------------------------------
        function openChangePasswordModal() {
            document.getElementById('changePasswordForm').reset();
            openModal('changePasswordModal');
        }

        async function submitChangePassword(e) {
            e.preventDefault();
            
            const form = e.target;
            const newPwd = document.getElementById('new_password').value;
            const confirmPwd = document.getElementById('confirm_password').value;

            if (newPwd !== confirmPwd) {
                showToast('รหัสผ่านใหม่และการยืนยันไม่ตรงกัน', 'error');
                return;
            }

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            try {
                // สร้าง API สำหรับเปลี่ยนรหัสผ่าน 
                const response = await fetch(BASE_URL + 'api/auth/change_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();

                if (result.status === 'success') {
                    showToast(result.message, 'success');
                    closeModal('changePasswordModal');
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน', 'error');
            }
        }
    
    </script>
</body>
</html>