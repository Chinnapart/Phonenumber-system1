<?php
// ==========================================
// Entry Point: Login Page & Router
// Path: index.php
// ==========================================

require_once 'config/app.php';
require_once 'core/AuthMiddleware.php';

// Router Logic: ตรวจสอบว่ามี Session Login ค้างไว้หรือไม่
if (Auth::isLoggedIn()) {
    // ถ้าเป็น Admin ให้เด้งไปหน้า Dashboard
    if (Auth::isAdmin()) {
        header("Location: " . BASE_URL . "views/admin/dashboard.php");
    } else {
        // ถ้าเป็น User ทั่วไป ให้เด้งไปหน้า Phone Directory
        header("Location: " . BASE_URL . "views/user/directory.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | <?= APP_NAME ?></title>
    <!-- เรียกใช้งาน Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Inter สำหรับภาษาอังกฤษ, Prompt สำหรับภาษาไทย -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Phosphor Icons (ตามแบบใน Design) -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'Prompt', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', 'Prompt', sans-serif; }
        
        /* สไตล์สำหรับ Gradient พื้นหลังด้านซ้าย */
        .bg-gradient-split {
            background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 50%, #8b5cf6 100%);
        }

        /* Glassmorphism Effect แบบนุ่มนวล */
        .glass-panel {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4 lg:p-8">

    <!-- Main Card Container -->
    <!-- 🌟 แก้ไข: เพิ่ม lg:h-[600px] max-h-[95vh] เพื่อจำกัดความสูงไม่ให้ล้นจอ 🌟 -->
    <div class="w-full max-w-6xl bg-white rounded-3xl shadow-2xl flex flex-col lg:flex-row overflow-hidden lg:h-[600px] max-h-[95vh]">
        
        <!-- ========================================== -->
        <!-- Left Side: Brand & Marketing (Gradient)    -->
        <!-- ========================================== -->
        <!-- 🌟 แก้ไข: ลด padding จาก p-14 เป็น p-12 🌟 -->
        <div class="bg-gradient-split w-full lg:w-5/12 p-8 lg:p-12 text-white flex flex-col justify-between relative overflow-hidden hidden md:flex">
            
            <!-- ของตกแต่งพื้นหลัง (วงกลมโปร่งแสงด้านขวาบน) -->
            <div class="absolute top-12 right-12 w-32 h-32 bg-white/10 backdrop-blur-xl rounded-3xl rotate-12 border border-white/20"></div>

            <!-- Top: Logo & Badge -->
            <div>
                <div class="flex items-center gap-3 mb-10">
                    <div class="bg-white/20 p-2 rounded-xl backdrop-blur-sm">
                        <i class="ph ph-phone-call text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">ConnectPro</h1>
                        <p class="text-xs uppercase tracking-widest text-blue-100 font-medium">Internal Directory</p>
                    </div>
                </div>

                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-sm mb-8">
                    <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                    <span class="text-white/90">Secure organization access</span>
                </div>

                <!-- Middle: Main Heading -->
                <h2 class="text-4xl lg:text-5xl font-bold leading-tight mb-6">
                    ติดต่อกันง่ายขึ้น<br>ในพื้นที่เดียว
                </h2>
                <p class="text-blue-100 text-sm leading-relaxed max-w-sm mb-12">
                    ค้นหาเบอร์โทรภายใน แผนก สถานที่ และตรวจสอบสถานะการเชื่อมต่อของบุคลากรภายในองค์กรได้อย่างรวดเร็ว
                </p>
            </div>

            <!-- Bottom: Feature Cards -->
            <div class="grid grid-cols-3 gap-4 mt-auto relative z-10">
                <div class="glass-panel rounded-2xl p-4 flex flex-col justify-center transition-transform hover:-translate-y-1">
                    <i class="ph ph-users text-2xl mb-2 text-white/90"></i>
                    <span class="text-xs font-medium text-white/80">Directory</span>
                </div>
                <div class="glass-panel rounded-2xl p-4 flex flex-col justify-center transition-transform hover:-translate-y-1">
                    <i class="ph ph-map-pin text-2xl mb-2 text-white/90"></i>
                    <span class="text-xs font-medium text-white/80">Locations</span>
                </div>
                <div class="glass-panel rounded-2xl p-4 flex flex-col justify-center transition-transform hover:-translate-y-1">
                    <i class="ph ph-broadcast text-2xl mb-2 text-white/90"></i>
                    <span class="text-xs font-medium text-white/80">Live Status</span>
                </div>
            </div>

            <!-- Footer text -->
            <div class="mt-12 text-xs text-white/60">
                ConnectPro &copy; <?= date('Y') ?> - Internal use only
            </div>
        </div>

        <!-- ========================================== -->
        <!-- Right Side: Login Form                     -->
        <!-- ========================================== -->
        <!-- 🌟 แก้ไข: ลด padding และเพิ่ม overflow-y-auto เพื่อให้ scroll ได้ถ้าจอเล็กมาก 🌟 -->
        <div class="w-full lg:w-7/12 p-8 lg:px-16 lg:py-10 flex flex-col justify-center bg-white relative overflow-y-auto">
            
            <div class="max-w-md w-full mx-auto">
                
                <!-- Mobile Logo (Shows only on small screens) -->
                <div class="flex items-center gap-2 mb-8 md:hidden text-brand-600">
                    <i class="ph ph-phone-call text-3xl"></i>
                    <h1 class="text-2xl font-bold">ConnectPro</h1>
                </div>

                <!-- 🌟 แก้ไข: ลด margin bottom จาก 10 เป็น 8 🌟 -->
                <div class="mb-8">
                    <p class="text-brand-600 font-semibold text-sm tracking-widest uppercase mb-2">Welcome Back</p>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">เข้าสู่ระบบ</h2>
                    <p class="text-gray-500 text-sm">กรอกบัญชีผู้ใช้งานองค์กรเพื่อเข้าสู่ ConnectPro</p>
                </div>

                <!-- Alert Box -->
                <div id="alertBox" class="hidden mb-6 p-4 rounded-xl bg-red-50 border border-red-100 flex items-start gap-3">
                    <i class="ph-fill ph-warning-circle text-red-500 text-lg mt-0.5"></i>
                    <p id="alertMessage" class="text-red-700 text-sm font-medium"></p>
                </div>

                <!-- 🌟 แก้ไข: ลดระยะห่างช่องกรอกข้อมูลจาก space-y-6 เป็น space-y-5 🌟 -->
                <form id="loginForm" class="space-y-5">
                    
                    <!-- Username Input -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2" for="username">ชื่อผู้ใช้งาน</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-brand-500 transition-colors">
                                <i class="ph ph-user text-lg"></i>
                            </div>
                            <input type="text" id="username" name="username" required 
                                class="w-full pl-11 pr-4 py-3.5 bg-gray-50/50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all duration-200"
                                placeholder="เช่น admin หรือ employee ID">
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700" for="password">รหัสผ่าน</label>
                            <span class="text-xs text-gray-400">ระบบภายในองค์กร</span>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-brand-500 transition-colors">
                                <i class="ph ph-lock-key text-lg"></i>
                            </div>
                            <input type="password" id="password" name="password" required 
                                class="w-full pl-11 pr-12 py-3.5 bg-gray-50/50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all duration-200"
                                placeholder="กรอกรหัสผ่าน">
                            <!-- Toggle Password Visibility -->
                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                                <i class="ph ph-eye text-lg" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Options Row -->
                    <div class="flex items-center justify-between pt-2">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" class="w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 focus:ring-2 cursor-pointer">
                            <span class="text-sm text-gray-600 group-hover:text-gray-900 transition-colors">จดจำชื่อผู้ใช้งาน</span>
                        </label>
                        <a href="#" class="text-sm font-medium text-brand-600 hover:text-brand-800 transition-colors">ต้องการความช่วยเหลือ?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn"
                        class="w-full py-4 px-4 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-xl shadow-lg shadow-brand-500/30 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transform transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 flex justify-center items-center gap-2">
                        <span id="btnText">เข้าสู่ระบบ</span>
                        <i class="ph ph-arrow-right text-lg" id="btnIcon"></i>
                        <!-- Loading Spinner (Hidden by default) -->
                        <svg id="btnLoading" class="animate-spin h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </form>

                <!-- Security Info -->
                <!-- 🌟 แก้ไข: เปลี่ยน mt-8 เป็น mt-6 🌟 -->
                <div class="mt-6 flex items-start gap-3 p-4 bg-blue-50/50 rounded-xl border border-blue-100/50">
                    <i class="ph-fill ph-shield-check text-brand-500 text-xl mt-0.5"></i>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        ระบบนี้สำหรับบุคลากรภายในองค์กรเท่านั้น การเข้าสู่ระบบและการใช้งานอาจถูกบันทึกเพื่อความปลอดภัย
                    </p>
                </div>
                
                <!-- 🌟 แก้ไข: เปลี่ยน mt-8 เป็น mt-6 🌟 -->
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-400">หากไม่สามารถเข้าสู่ระบบได้ กรุณาติดต่อ IT Support</p>
                </div>
        </div>
    </div>

    <script>
        // Password Visibility Toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            if (type === 'text') {
                eyeIcon.classList.remove('ph-eye');
                eyeIcon.classList.add('ph-eye-closed');
            } else {
                eyeIcon.classList.remove('ph-eye-closed');
                eyeIcon.classList.add('ph-eye');
            }
        });

        // Form Submission Handling
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnIcon = document.getElementById('btnIcon');
            const btnLoading = document.getElementById('btnLoading');
            const alertBox = document.getElementById('alertBox');
            const alertMessage = document.getElementById('alertMessage');
            
            const formData = {
                username: document.getElementById('username').value,
                password: passwordInput.value
            };

            // Loading State
            btn.disabled = true;
            btn.classList.add('opacity-90', 'cursor-not-allowed');
            btnText.textContent = "กำลังตรวจสอบ...";
            btnIcon.classList.add('hidden');
            btnLoading.classList.remove('hidden');
            alertBox.classList.add('hidden');

            try {
                const response = await fetch('api/auth/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (response.ok && data.status === 'success') {
                    // Success State
                    btn.classList.remove('bg-brand-500', 'hover:bg-brand-600');
                    btn.classList.add('bg-emerald-500', 'hover:bg-emerald-600');
                    btnText.textContent = "เข้าสู่ระบบสำเร็จ!";
                    btnLoading.classList.add('hidden');
                    btnIcon.classList.remove('hidden', 'ph-arrow-right');
                    btnIcon.classList.add('ph-check-circle');
                    
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 800);
                } else {
                    throw new Error(data.message || 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ');
                }
            } catch (error) {
                // Error State
                btn.disabled = false;
                btn.classList.remove('opacity-90', 'cursor-not-allowed');
                btnText.textContent = "เข้าสู่ระบบ";
                btnIcon.classList.remove('hidden');
                btnLoading.classList.add('hidden');
                
                alertMessage.textContent = error.message;
                alertBox.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>