<?php
// ==========================================
// Admin View: Departments Management (CRUD)
// Path: views/admin/departments_manage.php
// ==========================================

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Database.php';

// บังคับว่าต้องเป็น Admin เท่านั้น
Auth::requireAdmin();

$pageTitle = 'จัดการแผนก';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-6 animate-fade-in relative pb-10">
    
    <!-- Page Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">จัดการแผนก (Departments)</h1>
            <p class="text-sm text-gray-500 mt-1">เพิ่ม แก้ไข และจัดการข้อมูลแผนกภายในองค์กร</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button onclick="openDeptModal()" class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2.5 rounded-xl shadow-md shadow-brand-500/30 transition-all duration-200 hover:-translate-y-0.5">
                <i class="ph ph-plus-circle text-lg"></i>
                เพิ่มแผนก
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="glass-panel p-4 rounded-2xl flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-brand-500 transition-colors">
                <i class="ph ph-magnifying-glass text-lg"></i>
            </div>
            <input type="text" id="searchDeptInput" 
                   class="w-full pl-11 pr-4 py-2.5 bg-white/80 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all shadow-sm"
                   placeholder="ค้นหาชื่อแผนก...">
        </div>
    </div>

    <!-- Table Container -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="table-container">
            <table class="custom-table whitespace-nowrap w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="px-6 py-4 border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">ID</th>
                        <th class="px-6 py-4 border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">ชื่อแผนก</th>
                        <th class="px-6 py-4 border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">สี (Color Code)</th>
                        <th class="px-6 py-4 border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center w-24">จัดการ</th> 
                    </tr>
                </thead>
                <tbody id="deptsTableBody" class="bg-white divide-y divide-gray-100">
                    <tr>
                        <td colspan="4" class="text-center py-10">
                            <div class="flex justify-center items-center gap-3 text-gray-400">
                                <div class="spinner-ring border-gray-300"></div>
                                <span class="font-medium text-sm">กำลังโหลดข้อมูลแผนก...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Section -->
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-slate-50/50">
            <div id="deptPageInfo" class="text-sm text-gray-500 font-medium"></div>
            <div id="deptPaginationContainer" class="flex gap-1.5"></div>
        </div>
    </div>

</div>

<!-- ==========================================
     MODAL: Add / Edit Department Form
     ========================================== -->
<div id="deptModal" class="fixed inset-0 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
    <div class="modal-backdrop absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModal('deptModal')"></div>
    
    <div class="bg-white rounded-2xl w-full max-w-md mx-4 z-10 modal-content shadow-2xl flex flex-col transform scale-95 transition-transform duration-300">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/50 rounded-t-2xl">
            <h3 class="text-lg font-bold text-gray-900" id="deptModalTitle">เพิ่มแผนกใหม่</h3>
            <button onclick="closeModal('deptModal')" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>

        <div class="p-6">
            <form id="deptForm" class="space-y-5">
                <input type="hidden" id="form_dept_id" name="id">

                <!-- Department Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ชื่อแผนก <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="ph ph-buildings"></i>
                        </div>
                        <input type="text" id="form_dept_name" name="name" required
                               class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none"
                               placeholder="เช่น IT Support">
                    </div>
                </div>

                <!-- Color Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">โค้ดสี (Color Code)</label>
                    <div class="relative flex items-center gap-3">
                        <input type="color" id="form_dept_color_picker" 
                               class="h-10 w-10 rounded-lg cursor-pointer border border-gray-200" 
                               value="#3b82f6"
                               onchange="document.getElementById('form_dept_color').value = this.value">
                        <input type="text" id="form_dept_color" name="color_code"
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono uppercase focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none"
                               placeholder="#3B82F6" value="#3b82f6"
                               oninput="document.getElementById('form_dept_color_picker').value = this.value">
                    </div>
                    <p class="text-[0.65rem] text-gray-400 mt-1.5">ใช้สำหรับแสดงสีป้ายกำกับแผนกในระบบ (Hex Color)</p>
                </div>

                <button type="submit" class="hidden"></button>
            </form>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 bg-gray-50 rounded-b-2xl">
            <button type="button" onclick="closeModal('deptModal')" class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">
                ยกเลิก
            </button>
            <button type="button" onclick="document.getElementById('deptForm').requestSubmit()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 shadow-md shadow-brand-500/30 transition-all duration-200 hover:-translate-y-0.5">
                <i class="ph ph-floppy-disk text-lg"></i>
                บันทึกแผนก
            </button>
        </div>
    </div>
</div>

<?php
// เนื่องจากเราไม่ได้แยกไฟล์ footer.php ผมจึงทำการปิดแท็กตรงนี้ครับ
?>
        </main>
    </div>

    <!-- Core Scripts -->
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
    
    <!-- Script ควบคุมเฉพาะหน้าจัดการแผนก -->
    <script>
        let currentDeptPage = 1;
        const deptLimit = 10;

        document.addEventListener('DOMContentLoaded', () => {
            loadDepartments();

            const searchInput = document.getElementById('searchDeptInput');
            if (searchInput) {
                let timeout = null;
                searchInput.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        currentDeptPage = 1;
                        loadDepartments();
                    }, 300);
                });
            }

            const deptForm = document.getElementById('deptForm');
            if (deptForm) {
                deptForm.addEventListener('submit', handleDeptSubmit);
            }
        });

        // ดึงข้อมูลแผนกมาแสดง
        async function loadDepartments() {
            const tableBody = document.getElementById('deptsTableBody');
            if (!tableBody) return;

            tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-8 text-gray-500"><div class="flex justify-center items-center gap-2"><div class="spinner-ring border-blue-500"></div>กำลังโหลดข้อมูล...</div></td></tr>`;

            const search = document.getElementById('searchDeptInput') ? document.getElementById('searchDeptInput').value.trim() : '';
            
            // หมายเหตุ: คุณจะต้องสร้าง API สำหรับดึงข้อมูลแผนก (api/departments/read.php) ด้วยนะครับ
            // โค้ดส่วนนี้เป็นการจำลองการส่งรีเควสต์ 
            try {
                const queryParams = new URLSearchParams({ search: search, page: currentDeptPage, limit: deptLimit });
                const response = await apiCall(`api/departments/read.php?${queryParams.toString()}`);
                
                if (response.status === 'success') {
                    renderDeptTable(response.data);
                    renderDeptPagination(response.pagination);
                }
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-8 text-red-500"><i class="ph-fill ph-warning-circle text-2xl mb-2"></i><br>โปรดตรวจสอบให้แน่ใจว่าได้สร้าง API สำหรับจัดการแผนกแล้ว</td></tr>`;
            }
        }

        // วาดตารางแผนก
        function renderDeptTable(depts) {
            const tableBody = document.getElementById('deptsTableBody');
            if (depts.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-8 text-gray-400"><i class="ph ph-folder-open text-4xl mb-2"></i><br>ไม่พบข้อมูลแผนก</td></tr>`;
                return;
            }

            let html = '';
            depts.forEach(dept => {
                const colorCode = dept.color_code || '#cccccc';
                html += `
                    <tr class="hover:bg-slate-50 transition-colors group">
                        <td class="px-6 py-4 font-mono text-gray-500">${dept.id}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">${dept.name}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full border border-gray-200" style="background-color: ${colorCode}"></span>
                                <span class="text-sm font-mono text-gray-600">${colorCode}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick="openEditDeptModal(${dept.id}, '${dept.name}', '${colorCode}')" class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg tooltip" title="แก้ไข">
                                    <i class="ph ph-pencil-simple text-lg"></i>
                                </button>
                                <button onclick="deleteDept(${dept.id})" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg tooltip" title="ลบ">
                                    <i class="ph ph-trash text-lg"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            tableBody.innerHTML = html;
        }

        function renderDeptPagination(pageData) {
            // โค้ดสำหรับแสดงปุ่มเปลี่ยนหน้า (คล้ายกับของ user)
            // ...
        }

        // เปิด Modal เพิ่ม
        function openDeptModal() {
            const form = document.getElementById('deptForm');
            if (form) form.reset();
            document.getElementById('form_dept_id').value = '';
            document.getElementById('form_dept_color_picker').value = '#3b82f6';
            document.getElementById('form_dept_color').value = '#3b82f6';
            
            document.getElementById('deptModalTitle').innerText = 'เพิ่มแผนกใหม่';
            
            const modal = document.getElementById('deptModal');
            modal.classList.remove('hidden');
            void modal.offsetWidth;
            modal.classList.remove('opacity-0');
            modal.querySelector('.modal-content').classList.remove('scale-95');
        }

        // เปิด Modal แก้ไข
        window.openEditDeptModal = function(id, name, colorCode) {
            const form = document.getElementById('deptForm');
            if (form) form.reset();
            
            document.getElementById('form_dept_id').value = id;
            document.getElementById('form_dept_name').value = name;
            document.getElementById('form_dept_color_picker').value = colorCode;
            document.getElementById('form_dept_color').value = colorCode;
            
            document.getElementById('deptModalTitle').innerText = 'แก้ไขแผนก';
            
            const modal = document.getElementById('deptModal');
            modal.classList.remove('hidden');
            void modal.offsetWidth;
            modal.classList.remove('opacity-0');
            modal.querySelector('.modal-content').classList.remove('scale-95');
        }

        // ปิด Modal (รองรับ animation)
        window.closeModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if(modal) {
                modal.classList.add('opacity-0');
                const content = modal.querySelector('.modal-content');
                if(content) content.classList.add('scale-95');
                
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 300);
            }
        }

        // จัดการ Form Submit (ต้องการ API)
        async function handleDeptSubmit(e) {
            e.preventDefault();
            // ... (เขียนโค้ดเรียก API create/update คล้ายกับของ user_management)
            showToast('ฟังก์ชันนี้ยังไม่ได้เชื่อมต่อกับ API', 'error');
        }

        // ลบแผนก (ต้องการ API)
        window.deleteDept = async function(id) {
            if(confirm('ยืนยันการลบแผนก?')) {
                // ... (เขียนโค้ดเรียก API delete)
                showToast('ฟังก์ชันนี้ยังไม่ได้เชื่อมต่อกับ API', 'error');
            }
        }
    </script>
</body>
</html>