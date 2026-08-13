/* ==========================================
ConnectPro - User Management Logic
Path: assets/js/users.js
Description: Handle CRUD operations, Table rendering for system users
========================================== */

let currentUserPage = 1;
const userLimit = 10;

const userElements = {
    tableBody: document.getElementById('usersTableBody'),
    searchInput: document.getElementById('searchUserInput'),
    roleFilter: document.getElementById('roleFilter'),
    paginationContainer: document.getElementById('userPaginationContainer'),
    pageInfo: document.getElementById('userPageInfo'),
    userForm: document.getElementById('userForm'),
    modalId: 'userModal'
};

document.addEventListener('DOMContentLoaded', () => {
    loadUsers();

    if (userElements.searchInput) {
        let timeout = null;
        userElements.searchInput.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentUserPage = 1;
                loadUsers();
            }, 500);
        });
    }

    if (userElements.roleFilter) {
        userElements.roleFilter.addEventListener('change', () => {
            currentUserPage = 1;
            loadUsers();
        });
    }

    if (userElements.userForm) {
        userElements.userForm.addEventListener('submit', handleUserFormSubmit);
    }
});

async function loadUsers() {
    if (!userElements.tableBody) return;

    userElements.tableBody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-8 text-gray-500">
                <div class="flex justify-center items-center gap-2">
                    <div class="spinner-ring border-blue-500"></div>
                    กำลังโหลดข้อมูลบัญชี...
                </div>
            </td>
        </tr>
    `;

    const search = userElements.searchInput ? userElements.searchInput.value : '';
    const role = userElements.roleFilter ? userElements.roleFilter.value : '';

    const queryParams = new URLSearchParams({
        search: search,
        role: role,
        page: currentUserPage,
        limit: userLimit
    });

    try {
        const response = await apiCall(`api/users/read.php?${queryParams.toString()}`);
        if (response.status === 'success') {
            renderUsersTable(response.data);
            renderUserPagination(response.pagination);
        }
    } catch (error) {
        userElements.tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-8 text-red-500">
                    <i class="ph-fill ph-warning-circle text-2xl mb-2"></i><br>
                    เกิดข้อผิดพลาดในการโหลดข้อมูล: ${error.message}
                </td>
            </tr>
        `;
        showToast('ไม่สามารถโหลดข้อมูลบัญชีได้', 'error');
    }
}

function renderUsersTable(users) {
    if (users.length === 0) {
        userElements.tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-8 text-gray-400">
                    <i class="ph ph-users text-4xl mb-2"></i><br>
                    ไม่พบบัญชีผู้ใช้งานในระบบ
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    users.forEach(user => {
        const roleBadge = user.role === 'admin'
            ? `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 text-xs font-medium border border-purple-200"><i class="ph-fill ph-shield-check"></i> Admin</span>`
            : `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium border border-blue-200"><i class="ph ph-user"></i> User</span>`;

        html += `
            <tr class="hover:bg-slate-50 transition-colors group">
                <td class="font-medium text-gray-900">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-blue-600 text-white flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                            ${user.full_name.charAt(0)}
                        </div>
                        <div>
                            ${user.full_name}
                        </div>
                    </div>
                </td>
                <td class="font-mono text-gray-600">${user.username}</td>
                <td>${roleBadge}</td>
                <td class="text-gray-500 text-xs">${user.created_at || '-'}</td>
                <td>
                    <div class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick="openEditUserModal(${user.id})" class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg tooltip" title="แก้ไขบัญชี">
                            <i class="ph ph-pencil-simple text-lg"></i>
                        </button>
                        <button onclick="deleteUser(${user.id})" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg tooltip" title="ลบบัญชี">
                            <i class="ph ph-trash text-lg"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    userElements.tableBody.innerHTML = html;
}

function renderUserPagination(pageData) {
    if (!userElements.paginationContainer) return;

    const totalPages = pageData.total_pages;
    const curr = pageData.current_page;

    if (userElements.pageInfo) {
        userElements.pageInfo.innerText = `หน้า ${curr} จาก ${totalPages} (รวม ${pageData.total_records} บัญชี)`;
    }

    let html = '';
    html += `<button onclick="changeUserPage(${curr - 1})" class="px-3 py-1 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed" ${curr <= 1 ? 'disabled' : ''}><i class="ph ph-caret-left"></i></button>`;

    let startPage = Math.max(1, curr - 2);
    let endPage = Math.min(totalPages, curr + 2);

    for (let i = startPage; i <= endPage; i++) {
        if (i === curr) {
            html += `<button class="px-3 py-1 rounded border border-blue-500 bg-blue-50 text-blue-700 font-medium">${i}</button>`;
        } else {
            html += `<button onclick="changeUserPage(${i})" class="px-3 py-1 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600">${i}</button>`;
        }
    }

    html += `<button onclick="changeUserPage(${curr + 1})" class="px-3 py-1 rounded border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed" ${curr >= totalPages ? 'disabled' : ''}><i class="ph ph-caret-right"></i></button>`;

    userElements.paginationContainer.innerHTML = html;
}

window.changeUserPage = function(newPage) {
    currentUserPage = newPage;
    loadUsers();
};

async function handleUserFormSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const submitBtn = form.closest('.modal-content').querySelector('button.bg-brand-500');
    const originalBtnText = submitBtn ? submitBtn.innerHTML : '';

    if (submitBtn) {
        submitBtn.innerHTML = '<div class="spinner-ring"></div> กำลังบันทึก...';
        submitBtn.disabled = true;
    }

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    const isUpdate = data.id && data.id !== '';
    const endpoint = isUpdate ? 'api/users/update.php' : 'api/users/create.php';

    try {
        const response = await apiCall(endpoint, 'POST', data);
        if (response.status === 'success') {
            showToast(response.message, 'success');
            closeModal(userElements.modalId);
            form.reset();
            loadUsers();
        }
    } catch (error) {
        showToast(error.message, 'error');
    } finally {
        if (submitBtn) {
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
        }
    }
}

window.openUserModal = function() {
    if (userElements.userForm) {
        userElements.userForm.reset();
        document.getElementById('form_user_id').value = '';
    }
    const title = document.getElementById('userModalTitle');
    if (title) title.innerText = 'เพิ่มผู้ใช้งานใหม่';
    const help = document.getElementById('passwordHelpText');
    if (help) help.style.display = 'block';

    openModal(userElements.modalId);
};

window.openEditUserModal = async function(id) {
    showToast('กำลังโหลดข้อมูลบัญชี...', 'success');
    if (userElements.userForm) userElements.userForm.reset();

    try {
        const response = await apiCall(`api/users/read.php?search=${id}`);
        const user = response.data.find(u => u.id == id);

        if (user) {
            document.getElementById('form_user_id').value = user.id;
            document.getElementById('form_full_name').value = user.full_name;
            document.getElementById('form_username').value = user.username;
            document.getElementById('form_role').value = user.role;
            document.getElementById('form_password').value = '';

            const title = document.getElementById('userModalTitle');
            if (title) title.innerText = 'แก้ไขข้อมูลผู้ใช้งาน';

            openModal(userElements.modalId);
        }
    } catch (error) {
        showToast('ไม่สามารถดึงข้อมูลบัญชีได้', 'error');
    }
};

window.deleteUser = function(id) {
    const confirmHtml = `
        <div id="deleteUserConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="modal-backdrop absolute inset-0 bg-gray-900/50"></div>
            <div class="bg-white rounded-2xl p-6 shadow-xl z-10 max-w-sm w-full mx-4 modal-content">
                <div class="flex items-center gap-3 text-red-600 mb-4">
                    <i class="ph-fill ph-warning-circle text-3xl"></i>
                    <h3 class="text-lg font-bold text-gray-900">ยืนยันการลบบัญชี?</h3>
                </div>
                <p class="text-sm text-gray-600 mb-6">คุณแน่ใจหรือไม่ว่าต้องการลบบัญชีผู้ใช้นี้?</p>
                <div class="flex justify-end gap-3">
                    <button onclick="document.getElementById('deleteUserConfirmModal').remove()" class="px-4 py-2 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200">ยกเลิก</button>
                    <button onclick="executeDeleteUser(${id}); document.getElementById('deleteUserConfirmModal').remove()" class="px-4 py-2 rounded-xl text-sm font-medium text-white bg-red-600 hover:bg-red-700">ลบบัญชี</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', confirmHtml);
};

window.executeDeleteUser = async function(id) {
    try {
        const response = await apiCall('api/users/delete.php', 'POST', { id: id });
        if (response.status === 'success') {
            showToast(response.message, 'success');
            loadUsers();
        }
    } catch (error) {
        showToast(error.message, 'error');
    }
};