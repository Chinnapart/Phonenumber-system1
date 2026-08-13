/* ==========================================
ConnectPro - Main JavaScript (Vanilla JS)
Path: assets/js/app.js
Description: Global utilities, Fetch API wrapper, Modals, and Toasts.
========================================== */

// กำหนด Base URL ของระบบให้ตรงกับฝั่ง PHP 
// (หากนำไปขึ้น Server จริง ให้เปลี่ยนเป็นโดเมนจริง)
const BASE_URL = 'http://localhost:8080/connectpro/';

/**
 * 1. Toast Notification System (ระบบแจ้งเตือนมุมจอ)
 * @param {string} message - ข้อความที่ต้องการแสดง
 * @param {string} type - 'success' (สีเขียว) หรือ 'error' (สีแดง)
 */
function showToast(message, type = 'success') {
    // ตรวจสอบว่ามี Container สำหรับใส่ Toast หรือยัง ถ้ายังให้สร้างใหม่
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        // จัดให้อยู่มุมขวาบน และอยู่บนสุด (z-50)
        container.className = 'fixed top-4 right-4 z-50 flex flex-col gap-3';
        document.body.appendChild(container);
    }

    // สร้าง Element ของ Toast
    const toast = document.createElement('div');
    const isSuccess = type === 'success';
    
    // กำหนดสีและไอคอนตามสถานะ (Success หรือ Error)
    const icon = isSuccess 
        ? '<i class="ph-fill ph-check-circle text-green-500 text-xl"></i>' 
        : '<i class="ph-fill ph-warning-circle text-red-500 text-xl"></i>';
    const borderColor = isSuccess ? 'border-green-200' : 'border-red-200';
    const bgColor = isSuccess ? 'bg-green-50' : 'bg-red-50';
    const textColor = isSuccess ? 'text-green-800' : 'text-red-800';

    toast.className = `flex items-center gap-3 p-4 rounded-xl border ${borderColor} ${bgColor} shadow-lg toast-slide-in min-w-[300px]`;
    toast.innerHTML = `
        ${icon}
        <span class="font-medium text-sm ${textColor}">${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600 transition-colors">
            <i class="ph ph-x text-lg"></i>
        </button>
    `;

    // นำไปแปะใน Container
    container.appendChild(toast);

    // ตั้งเวลาให้ปิดอัตโนมัติภายใน 3 วินาที
    setTimeout(() => {
        // เปลี่ยนแอนิเมชันให้สไลด์ออก
        toast.classList.replace('toast-slide-in', 'toast-slide-out');
        // รอให้แอนิเมชันเล่นจบ (0.3 วินาที) ค่อยลบ Element ทิ้ง
        setTimeout(() => toast.remove(), 300); 
    }, 3000);
}

/**
 * 2. Fetch API Wrapper (ศูนย์กลางการเรียก API)
 * ช่วยลดความซ้ำซ้อนในการเขียน Fetch แบบยาวๆ
 * @param {string} endpoint - เช่น 'api/contacts/read.php'
 * @param {string} method - 'GET', 'POST', 'PUT', 'DELETE'
 * @param {object} data - ข้อมูลที่ต้องการส่ง (ถ้ามี)
 */
async function apiCall(endpoint, method = 'GET', data = null) {
    const url = endpoint.startsWith('http') ? endpoint : BASE_URL + endpoint;
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    };

    // ถ้ามีข้อมูลและไม่ใช่ GET ให้แปลงเป็น JSON ใส่ Body
    if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);
        
        // ตรวจสอบว่า Backend ตอบกลับมาเป็น JSON หรือไม่
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            const result = await response.json();
            
            if (!response.ok || result.status === 'error') {
                throw new Error(result.message || 'เกิดข้อผิดพลาดจากระบบ');
            }
            return result;
        } else {
            // กรณี Backend พังหรือพ่น Error แบบไม่ใช่ JSON ออกมา
            throw new Error('เซิร์ฟเวอร์ไม่ได้ตอบกลับในรูปแบบที่ถูกต้อง');
        }
    } catch (error) {
        console.error('API Error:', error);
        // โยน Error ต่อไปให้หน้าเว็บจัดการ (เช่น เอาไปแสดง Toast)
        throw error;
    }
}

/**
 * 3. File Download Helper (ฟังก์ชันสำหรับโหลดไฟล์ Text/CSV)
 * @param {string} content - เนื้อหาดิบของไฟล์ (เช่น String รูปแบบ CSV)
 * @param {string} filename - ชื่อไฟล์พร้อมนามสกุล
 * @param {string} mimeType - ประเภทไฟล์
 */
function downloadStringAsFile(content, filename, mimeType = 'text/plain') {
    // ใส่ \ufeff (BOM) ลงไปด้านหน้าเพื่อให้โปรแกรมเช่น Excel อ่านภาษาไทย (UTF-8) ได้ถูกต้องเสมอ
    const blob = new Blob(["\ufeff" + content], { type: `${mimeType};charset=utf-8;` });
    const url = URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    
    // คืนพื้นที่หน่วยความจำ
    setTimeout(() => URL.revokeObjectURL(url), 100);
}

/**
 * 4. Modal Management (ระบบเปิด/ปิด ป๊อปอัป)
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        // ปิดการ Scroll ของหน้าเว็บด้านหลัง (ป้องกันเลื่อนแล้วงง)
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        // คืนค่าให้หน้าเว็บ Scroll ได้ตามปกติ
        document.body.style.overflow = '';
    }
}

// ตรวจจับการคลิก หากคลิกโดนพื้นที่สีดำ (Backdrop) ให้ปิด Modal อัตโนมัติ
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal-backdrop')) {
        event.target.parentElement.classList.add('hidden');
        document.body.style.overflow = '';
    }
});