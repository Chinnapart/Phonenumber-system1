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
                        <th class="text-left w-2/6">ชื่อ - นามสกุล</th>
                        <th class="text-left w-1/6">แผนก</th>
                        <th class="text-left w-1/6">เบอร์ต่อ (Ext.)</th>
                        <th class="text-left w-1/6">IP Address</th>
                        <th class="text-left w-1/6">สถานะ</th>
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
        </main> <!-- ปิดแท็ก main จาก header.php -->
    </div> <!-- ปิดแท็ก div.flex-1 จาก header.php -->

    <!-- ========================================== -->
    <!-- Core Scripts -->
    <!-- ========================================== -->
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
    <script src="<?= BASE_URL ?>assets/js/contacts.js"></script>

    <!-- Script จัดการหน้าโปรไฟล์ส่วนตัวของ User -->
    <script>
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

                    openModal('myProfileModal');
                }
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        async function submitMyProfile(e) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button[type="button"]:nth-child(2)'); // ปุ่มบันทึก
            const origText = btn.innerHTML;
            
            btn.innerHTML = '<div class="spinner-ring border-white w-4 h-4"></div> บันทึก...';
            btn.disabled = true;

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await apiCall('api/contacts/my_profile.php', 'POST', data);
                if (response.status === 'success') {
                    showToast(response.message, 'success');
                    closeModal('myProfileModal');
                    loadContacts(); // รีโหลดตาราง (ฟังก์ชันนี้อยู่ใน contacts.js)
                }
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                btn.innerHTML = origText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>