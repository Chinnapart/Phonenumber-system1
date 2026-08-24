/* ==========================================
ConnectPro - Contacts Management & Ping Logic
Path: assets/js/contacts.js
Description: Handle CRUD operations, Table rendering, and Network Pinging
========================================== */

// ตัวแปรสถานะ (State) ของหน้าเว็บปัจจุบัน
let currentPage = 1;
const limit = 10; // จำนวนรายการต่อหน้า

// ฟังก์ชันดึง DOM Elements แบบ Dynamic เพื่อให้แน่ใจว่าอ้างอิง Element ถูกต้องเสมอ
function getElements() {
    return {
        tableBody: document.getElementById('contactsTableBody'),
        searchInput: document.getElementById('searchInput'),
        deptFilter: document.getElementById('deptFilter'),
        statusFilter: document.getElementById('statusFilter'),
        paginationContainer: document.getElementById('paginationContainer'),
        pageInfo: document.getElementById('pageInfo'),
        contactForm: document.getElementById('contactForm'),
        contactModal: 'contactModal'
    };
}

// เมื่อหน้าเว็บโหลดเสร็จ ให้เริ่มดึงข้อมูลและผูก Event Listeners ทันที
document.addEventListener('DOMContentLoaded', () => {
    const els = getElements();

    // 1. โหลดข้อมูลรายชื่อครั้งแรก
    loadContacts();

    // 2. ผูก Event Listener ให้ช่องค้นหาและตัวกรอง
    if (els.searchInput) {
        let timeout = null;
        els.searchInput.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentPage = 1; // กลับไปหน้าแรกเสมอเมื่อค้นหา
                loadContacts();
            }, 300);
        });
    }

    if (els.deptFilter) els.deptFilter.addEventListener('change', () => { currentPage = 1; loadContacts(); });
    if (els.statusFilter) els.statusFilter.addEventListener('change', () => { currentPage = 1; loadContacts(); });

    // 3. ผูก Event ให้ฟอร์มบันทึกข้อมูล (เพิ่ม/แก้ไข)
    if (els.contactForm) {
        els.contactForm.addEventListener('submit', handleContactSubmit);
    }
});

/**
 * ฟังก์ชันหลักสำหรับดึงข้อมูลจาก API และวาดตาราง
 */
async function loadContacts() {
    const els = getElements();
    if (!els.tableBody) return;

    // แสดงสถานะกำลังโหลดในตาราง
    els.tableBody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-8 text-gray-500">
                <div class="flex justify-center items-center gap-2">
                    <div class="spinner-ring border-blue-500"></div>
                    กำลังโหลดข้อมูล...
                </div>
            </td>
        </tr>
    `;

    // เตรียมพารามิเตอร์สำหรับการค้นหา
    const search = els.searchInput ? els.searchInput.value.trim() : '';
    const deptId = els.deptFilter ? els.deptFilter.value : '';
    const status = els.statusFilter ? els.statusFilter.value : '';

    const queryParams = new URLSearchParams({
        search: search,
        department_id: deptId,
        status: status,
        page: currentPage,
        limit: limit
    });

    try {
        const response = await apiCall(`api/contacts/read.php?${queryParams.toString()}`);
        if (response.status === 'success') {
            renderTable(response.data);
            renderPagination(response.pagination);
        }
    } catch (error) {
        els.tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-8 text-red-500">
                    <i class="ph-fill ph-warning-circle text-2xl mb-2"></i><br>
                    เกิดข้อผิดพลาดในการดึงข้อมูล: ${error.message}
                </td>
            </tr>
        `;
        showToast('ไม่สามารถโหลดข้อมูลได้', 'error');
    }
}

/**
 * ฟังก์ชันนำข้อมูล JSON มาสร้างเป็น HTML Table Rows
 */
function renderTable(contacts) {
    const els = getElements();
    if (contacts.length === 0) {
        els.tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-8 text-gray-400">
                    <i class="ph ph-folder-open text-4xl mb-2"></i><br>
                    ไม่พบข้อมูลผู้ติดต่อ
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    contacts.forEach(contact => {
        // --- 🌟 เริ่มต้นส่วนคำนวณสถานะอุปกรณ์และวัน Offline 🌟 ---
        let deviceStatusHtml = '';

        if (contact.status === 'online') {
            deviceStatusHtml = `
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Online
                </span>
            `;
        } else if (contact.status === 'offline') {
            // คำนวณจำนวนวันที่ออฟไลน์
            let offlineDaysText = '';
            if (contact.last_online && contact.last_online !== '0000-00-00 00:00:00') {
                const lastOnlineDate = new Date(contact.last_online);
                const today = new Date();
                
                // หาความต่างของเวลาเป็นมิลลิวินาที แล้วแปลงเป็นวัน
                const diffTime = Math.abs(today - lastOnlineDate);
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays > 0) {
                    offlineDaysText = `<div class="text-[0.65rem] text-rose-500 font-medium mt-1">ไม่ออนไลน์ ${diffDays} วัน</div>`;
                } else {
                    // กรณียังไม่ถึง 1 วัน
                    offlineDaysText = `<div class="text-[0.65rem] text-rose-400 font-medium mt-1">ออฟไลน์วันนี้</div>`;
                }
            } else {
                offlineDaysText = `<div class="text-[0.65rem] text-gray-400 font-medium mt-1">ไม่เคยออนไลน์</div>`;
            }

            deviceStatusHtml = `
                <div class="flex flex-col items-center justify-center">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-600 border border-rose-100 shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Offline
                    </span>
                    ${offlineDaysText}
                </div>
            `;
        } else {
            deviceStatusHtml = `
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200 shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Unknown
                </span>
            `;
        }
        // --- 🌟 สิ้นสุดส่วนคำนวณสถานะอุปกรณ์ 🌟 ---

        // จัดการแผนก (Department)
        const deptColor = contact.department_color || '#94a3b8';
        const deptHtml = contact.department_name 
            ? `<div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full" style="background-color: ${deptColor}"></span>${contact.department_name}</div>`
            : '<span class="text-gray-400">-</span>';

        // นำตัวแปร deviceStatusHtml มาใช้งานตรง td ที่ id="status-cell-${contact.id}"
        html += `
            <tr class="hover:bg-slate-50 transition-colors group">
                <td class="font-medium text-gray-900">
                    <div class="flex items-center gap-3">
                        ${contact.avatar_url 
                            ? `<img src="${BASE_URL}${contact.avatar_url}" alt="Profile" class="w-9 h-9 rounded-full object-cover shadow-sm border border-gray-100 flex-shrink-0">` 
                            : `<div class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs uppercase flex-shrink-0 shadow-sm">
                                ${contact.first_name.charAt(0)}${contact.last_name.charAt(0)}
                               </div>`
                        }
                        <div>
                            ${contact.first_name} ${contact.last_name}
                            <div class="text-xs text-gray-500 font-normal">${contact.job_title || ''}</div>
                        </div>
                    </div>
                </td>

                <!-- สถานะ User -->
                <td class="text-center">
                    ${(function() {
                        switch(contact.work_status) {
                            case 'on_call':
                                return `<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-wider bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-sm"><i class="ph-fill ph-phone-call mr-1.5 text-sm"></i> ติดสาย</span>`;
                            case 'away':
                                return `<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-wider bg-amber-50 text-amber-600 border border-amber-100 shadow-sm"><i class="ph-fill ph-clock mr-1.5 text-sm"></i> ไม่อยู่</span>`;
                            case 'busy':
                                return `<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-wider bg-rose-50 text-rose-600 border border-rose-100 shadow-sm"><i class="ph-fill ph-minus-circle mr-1.5 text-sm"></i> ไม่ว่าง</span>`;
                            default: // 'available' หรือค่าว่าง
                                return `<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-wider bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-sm"><i class="ph-fill ph-check-circle mr-1.5 text-sm"></i> ว่าง</span>`;
                        }
                    })()}
                </td>

                <td>${deptHtml}</td>
                <td class="font-mono text-gray-600">${contact.extension || '-'}</td>
                <td class="font-mono text-blue-600" id="ip-cell-${contact.id}">${contact.ip_address || '-'}</td>
                
                <!-- 🌟 ตรงนี้คือคอลัมน์อุปกรณ์ ที่ถูกแทนที่ด้วยตัวแปร deviceStatusHtml 🌟 -->
                <td id="status-cell-${contact.id}" class="text-center">
                    ${deviceStatusHtml}
                </td>
                
                <td>
                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick="pingSingleIp(${contact.id}, '${contact.ip_address}')" class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg tooltip" title="ตรวจสอบสถานะ (Ping)">
                            <i class="ph ph-broadcast text-lg"></i>
                        </button>
                        <button onclick="openEditModal(${contact.id})" class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg tooltip" title="แก้ไขข้อมูล">
                            <i class="ph ph-pencil-simple text-lg"></i>
                        </button>
                        <button onclick="deleteContact(${contact.id})" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg tooltip" title="ลบข้อมูล">
                            <i class="ph ph-trash text-lg"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    els.tableBody.innerHTML = html;
}
/**
 * ฟังก์ชันจัดการปุ่มเปลี่ยนหน้า (Pagination)
 */
function renderPagination(pageData) {
    const els = getElements(); // แก้ไขดึงค่า elements มาใช้
    if (!els.paginationContainer) return;

    const totalPages = pageData.total_pages;
    const curr = pageData.current_page;
    
    // อัปเดตข้อความบอกจำนวน
    if (els.pageInfo) {
        els.pageInfo.innerText = `หน้า ${curr} จาก ${totalPages} (รวม ${pageData.total_records} รายการ)`;
    }

    let html = '';
    
    // ปุ่ม Previous
    html += `<button onclick="changePage(${curr - 1})" class="px-3 py-1 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed" ${curr <= 1 ? 'disabled' : ''}>
                <i class="ph ph-caret-left"></i>
             </button>`;

    // ตัวเลขหน้า (แสดงแบบย่อๆ)
    let startPage = Math.max(1, curr - 2);
    let endPage = Math.min(totalPages, curr + 2);

    for (let i = startPage; i <= endPage; i++) {
        if (i === curr) {
            html += `<button class="px-3 py-1 rounded border border-blue-500 bg-blue-50 text-blue-700 font-medium">${i}</button>`;
        } else {
            html += `<button onclick="changePage(${i})" class="px-3 py-1 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600">${i}</button>`;
        }
    }

    // ปุ่ม Next
    html += `<button onclick="changePage(${curr + 1})" class="px-3 py-1 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed" ${curr >= totalPages ? 'disabled' : ''}>
                <i class="ph ph-caret-right"></i>
             </button>`;

    els.paginationContainer.innerHTML = html;
}

// ฟังก์ชันถูกเรียกเมื่อกดเปลี่ยนหน้า
window.changePage = function(newPage) {
    currentPage = newPage;
    loadContacts();
};

/**
 * จัดการเมื่อมีการกด Submit ฟอร์มเพิ่ม/แก้ไข
 */
async function handleContactSubmit(e) {
    e.preventDefault(); // ป้องกันหน้าเว็บรีเฟรช

    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    
    // เปลี่ยนสถานะปุ่มเป็นกำลังโหลด
    submitBtn.innerHTML = '<div class="spinner-ring"></div> กำลังบันทึก...';
    submitBtn.disabled = true;

    // รวบรวมข้อมูลจากฟอร์ม
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // เช็คว่ามี ID ไหม? (ถ้ามี = Update, ถ้าไม่มี = Create)
    const isUpdate = data.id && data.id !== '';
    const endpoint = isUpdate ? 'api/contacts/update.php' : 'api/contacts/create.php';

    try {
        const response = await apiCall(endpoint, 'POST', data);
        
        if (response.status === 'success') {
            showToast(response.message, 'success');
            closeModal('contactModal'); // แก้ไขลบการเรียก elements ผิดพลาด
            form.reset();
            // รีโหลดตาราง
            loadContacts();
        }
    } catch (error) {
        showToast(error.message, 'error');
    } finally {
        // คืนค่าปุ่มกลับมา
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    }
}

/**
 * ฟังก์ชันดึงข้อมูลมาใส่ฟอร์มเพื่อแก้ไข
 */
window.openEditModal = async function(id) {
    showToast('กำลังโหลดข้อมูล...', 'success');
    
    const els = getElements();
    if (els.contactForm) els.contactForm.reset();

    try {
        const response = await apiCall(`api/contacts/read.php?limit=10000`);
        const contact = response.data.find(c => c.id == id);

        if (contact) {
            document.getElementById('form_id').value = contact.id;
            document.getElementById('form_first_name').value = contact.first_name;
            document.getElementById('form_last_name').value = contact.last_name;
            document.getElementById('form_job_title').value = contact.job_title || '';
            document.getElementById('form_department_id').value = contact.department_id || '';
            document.getElementById('form_extension').value = contact.extension || '';
            document.getElementById('form_ip_address').value = contact.ip_address || '';

            document.getElementById('modalTitle').innerText = 'แก้ไขข้อมูลผู้ติดต่อ';
            
            openModal(els.contactModal);
        }
    } catch (error) {
        showToast('ไม่สามารถดึงข้อมูลได้', 'error');
    }
}

// ฟังก์ชันเปิด Modal สำหรับเพิ่มใหม่ (เคลียร์ค่าเก่าทิ้ง)
window.openCreateModal = function() {
    const els = getElements();
    if (els.contactForm) {
        els.contactForm.reset();
        document.getElementById('form_id').value = '';
    }
    const modalTitle = document.getElementById('modalTitle');
    if (modalTitle) modalTitle.innerText = 'เพิ่มผู้ติดต่อใหม่';
    
    openModal(els.contactModal);
}

/**
 * ฟังก์ชันลบข้อมูลผู้ติดต่อ
 */
window.deleteContact = async function(id) {
    // สร้าง HTML สำหรับ Custom Confirm Modal (ทำแบบฉีดโค้ดเข้าไปเลย)
    const confirmHtml = `
        <div id="deleteConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="modal-backdrop absolute inset-0 bg-gray-900/50"></div>
            <div class="bg-white rounded-2xl p-6 shadow-xl z-10 max-w-sm w-full mx-4 modal-content">
                <div class="flex items-center gap-3 text-red-600 mb-4">
                    <i class="ph-fill ph-warning-circle text-3xl"></i>
                    <h3 class="text-lg font-bold text-gray-900">ยืนยันการลบข้อมูล?</h3>
                </div>
                <p class="text-sm text-gray-600 mb-6">คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลนี้? การกระทำนี้ไม่สามารถกู้คืนได้</p>
                <div class="flex justify-end gap-3">
                    <button onclick="document.getElementById('deleteConfirmModal').remove()" class="px-4 py-2 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200">ยกเลิก</button>
                    <button onclick="executeDelete(${id}); document.getElementById('deleteConfirmModal').remove()" class="px-4 py-2 rounded-xl text-sm font-medium text-white bg-red-600 hover:bg-red-700">ลบข้อมูล</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', confirmHtml);
}

// ตัวรันคำสั่งลบหลังจากกดยืนยันแล้ว
window.executeDelete = async function(id) {
    try {
        const response = await apiCall('api/contacts/delete.php', 'POST', { id: id });
        if (response.status === 'success') {
            showToast(response.message, 'success');
            loadContacts(); // โหลดตารางใหม่
        }
    } catch (error) {
        showToast(error.message, 'error');
    }
}

/**
 * 1. Ping เช็คสถานะ 1 IP (Real-time AJAX)
 */
window.pingSingleIp = async function(id, ipAddress) {
    if (!ipAddress || ipAddress === '' || ipAddress === 'null') {
        showToast('ไม่มีหมายเลข IP ให้ตรวจสอบ', 'error');
        return;
    }

    const statusCell = document.getElementById(`status-cell-${id}`);
    
    // เปลี่ยนสถานะเป็น Loading Spinner
    if (statusCell) {
        statusCell.innerHTML = `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium border border-blue-200">
                                    <div class="spinner-ring border-blue-500 w-3 h-3"></div> Checking...
                                </span>`;
    }

    try {
        const response = await apiCall(`api/network/ping_single.php`, 'POST', {
            ip_address: ipAddress,
            contact_id: id
        });
        
        if (response.status === 'success') {
            const isOnline = response.data.is_online;
            
            // อัปเดต UI ทันทีไม่ต้องโหลดตารางใหม่
            if (statusCell) {
                if (isOnline) {
                    statusCell.innerHTML = `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-50 text-green-700 text-xs font-medium border border-green-200">
                            <span class="status-dot online"></span> Online
                           </span>`;
                } else {
                    statusCell.innerHTML = `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-50 text-red-700 text-xs font-medium border border-red-200">
                            <span class="status-dot offline"></span> Offline
                           </span>`;
                }
            }
            
            showToast(`Ping IP: ${ipAddress} เสร็จสิ้น (${isOnline ? 'Online' : 'Offline'})`, isOnline ? 'success' : 'error');
        }
    } catch (error) {
        showToast('การตรวจสอบล้มเหลว', 'error');
        // รีเซ็ตสถานะกลับ
        if (statusCell) {
             statusCell.innerHTML = `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-50 text-gray-700 text-xs font-medium border border-gray-200">
                            <span class="status-dot unknown"></span> Unknown
                           </span>`;
        }
    }
}

/**
 * 2. Ping Batch เช็คทั้งหมด (Scan All)
 */
window.runBatchPing = async function(buttonElement) {
    const originalText = buttonElement.innerHTML;
    
    // เปลี่ยนปุ่มเป็นสถานะกำลังสแกน
    buttonElement.innerHTML = `<div class="spinner-ring border-white"></div> กำลังสแกน...`;
    buttonElement.disabled = true;
    buttonElement.classList.add('opacity-75', 'cursor-not-allowed');

    showToast('กำลังสแกนเครือข่ายเบื้องหลัง อาจใช้เวลาสักครู่...', 'success');

    try {
        const response = await apiCall('api/network/ping_batch.php');
        
        if (response.status === 'success') {
            const data = response.data;
            showToast(`สแกนสำเร็จ! โหลดข้อมูล: ${data.total_scanned} รายการ (Online: ${data.online_count})`, 'success');
            // รีโหลดตารางเพื่อโชว์ผลลัพธ์
            loadContacts();
        }
    } catch (error) {
        showToast('เกิดข้อผิดพลาดในการสแกนเครือข่าย', 'error');
    } finally {
        buttonElement.innerHTML = originalText;
        buttonElement.disabled = false;
        buttonElement.classList.remove('opacity-75', 'cursor-not-allowed');
    }
}

/**
 * ฟังก์ชันดึงข้อมูลทั้งหมดมาส่งออกเป็นไฟล์ CSV
 */
window.exportToCSV = async function(buttonElement) {
    const originalText = buttonElement.innerHTML;
    buttonElement.innerHTML = `<div class="spinner-ring border-blue-600"></div> เตรียมไฟล์...`;
    buttonElement.disabled = true;

    try {
        // ดึงข้อมูลทั้งหมดโดยตั้ง Limit สูงๆ (เช่น 10000)
        const response = await apiCall('api/contacts/read.php?limit=10000');
        
        if (response.status === 'success') {
            const contacts = response.data;
            
            // สร้าง Header ของ CSV
            let csvContent = "รหัสพนักงาน,ชื่อ,นามสกุล,ตำแหน่ง,แผนก,เบอร์โทรภายใน,เบอร์มือถือ,IP Address,สถานะ\n";
            
            // วนลูปข้อมูลเติมลงไปแต่ละแถว
            contacts.forEach(c => {
                // ต้องใส่เครื่องหมาย " ครอบข้อความที่มี , หรือเว้นวรรค ป้องกันไฟล์ CSV แตก
                const row = [
                    c.id,
                    `"${c.first_name || ''}"`,
                    `"${c.last_name || ''}"`,
                    `"${c.job_title || ''}"`,
                    `"${c.department_name || ''}"`,
                    `"${c.extension || ''}"`,
                    `"${c.mobile_number || ''}"`,
                    `"${c.ip_address || ''}"`,
                    `"${c.status || 'unknown'}"`
                ];
                csvContent += row.join(",") + "\n";
            });

            // หา Date ปัจจุบันเพื่อตั้งชื่อไฟล์
            const dateStr = new Date().toISOString().split('T')[0];
            
            // เรียกใช้ฟังก์ชันจาก app.js (ฟังก์ชันดาวน์โหลดที่คุณเพิ่งให้ผมรวมไว้)
            downloadStringAsFile(csvContent, `ConnectPro_Export_${dateStr}.csv`, 'text/csv');
            showToast('เริ่มการดาวน์โหลดไฟล์แล้ว', 'success');
        }
    } catch (error) {
        showToast('ไม่สามารถเตรียมข้อมูลส่งออกได้', 'error');
    } finally {
        buttonElement.innerHTML = originalText;
        buttonElement.disabled = false;
    }
}