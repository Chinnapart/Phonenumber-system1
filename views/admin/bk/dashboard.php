<?php
// ==========================================
// Admin Dashboard (Basic View for Testing)
// Path: views/admin/dashboard.php
// ==========================================

require_once '../../config/app.php';
require_once '../../core/AuthMiddleware.php';

// บังคับว่าต้อง Login และต้องเป็น Admin เท่านั้นถึงจะเข้าหน้านี้ได้
// ถ้าไม่ใช่ จะถูกเตะกลับไปหน้า Login ทันที!
Auth::requireAdmin();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?= APP_NAME ?></title>
    <!-- เรียกใช้งาน Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { font-family: 'Inter', 'Prompt', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col items-center justify-center p-6">

    <div class="bg-white rounded-3xl shadow-xl w-full max-w-2xl p-10 text-center border border-gray-100">
        
        <div class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="ph ph-check-circle text-5xl"></i>
        </div>

        <h1 class="text-3xl font-bold text-gray-800 mb-2">เข้าสู่ระบบสำเร็จ!</h1>
        <p class="text-gray-500 mb-8">นี่คือหน้า Admin Dashboard พื้นฐานสำหรับการทดสอบ</p>

        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-6 text-left mb-8 inline-block w-full max-w-md">
            <h3 class="text-sm font-semibold text-blue-800 uppercase tracking-wider mb-4">ข้อมูล Session ปัจจุบัน</h3>
            <ul class="space-y-3 text-sm text-gray-700">
                <li class="flex justify-between border-b border-blue-100/50 pb-2">
                    <span class="text-gray-500">ชื่อผู้ใช้งาน (Name):</span> 
                    <span class="font-medium"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                </li>
                <li class="flex justify-between border-b border-blue-100/50 pb-2">
                    <span class="text-gray-500">บัญชี (Username):</span> 
                    <span class="font-medium"><?= htmlspecialchars($_SESSION['username']) ?></span>
                </li>
                <li class="flex justify-between pb-1">
                    <span class="text-gray-500">ระดับสิทธิ์ (Role):</span> 
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700 font-medium text-xs">
                        <i class="ph-fill ph-shield-check"></i>
                        <?= strtoupper(htmlspecialchars($_SESSION['role'])) ?>
                    </span>
                </li>
            </ul>
        </div>

        <div>
            <button id="logoutBtn" class="inline-flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-600 font-medium px-6 py-3 rounded-xl transition-colors duration-200">
                <i class="ph ph-sign-out text-xl"></i>
                ออกจากระบบ (Logout)
            </button>
        </div>

    </div>

    <script>
        // จัดการเหตุการณ์เมื่อกดปุ่ม Logout
        document.getElementById('logoutBtn').addEventListener('click', async function() {
            // โชว์สถานะกำลังโหลด
            const btn = this;
            btn.innerHTML = '<i class="ph ph-spinner animate-spin text-xl"></i> กำลังออกจากระบบ...';
            btn.classList.add('opacity-75', 'cursor-not-allowed');

            try {
                // เรียกใช้ API Logout ที่เราทำไว้
                const response = await fetch('../../api/auth/logout.php');
                const data = await response.json();

                if(data.status === 'success') {
                    // เปลี่ยนหน้ากลับไปที่ index.php (หน้า Login)
                    window.location.href = data.redirect;
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาดในการออกจากระบบ');
                btn.innerHTML = '<i class="ph ph-sign-out text-xl"></i> ออกจากระบบ (Logout)';
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        });
    </script>
</body>
</html>