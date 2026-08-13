<?php
// ==========================================
// Component: Global Header & Layout Wrapper
// Path: views/includes/header.php
// ==========================================

// เช็คว่าไฟล์ถูกเรียกใช้ในบริบทที่มีตัวแปร BASE_URL หรือยัง
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

// กำหนดชื่อหน้า (ถ้าไม่มีการตั้งตัวแปร $pageTitle ไว้หน้าไฟล์ จะใช้ชื่อแอปเป็นค่าเริ่มต้น)
$title = isset($pageTitle) ? $pageTitle . ' | ' . APP_NAME : APP_NAME;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>

    <!-- Google Fonts: Inter & Prompt -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- Chart.js (เตรียมไว้สำหรับกราฟใน Dashboard) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
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

    <!-- Custom CSS (ที่เราเขียนไว้) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/components.css">
</head>

<body class="bg-[#f8fafc] text-slate-900 flex h-screen overflow-hidden selection:bg-brand-500 selection:text-white">
    
    <!-- 1. ดึง Sidebar มาแสดงทางซ้ายมือ -->
    <?php include_once __DIR__ . '/sidebar.php'; ?>

    <!-- 2. พื้นที่เนื้อหาหลัก (ฝั่งขวา) -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative w-full">
        
        <!-- 3. ดึง Topbar (Header ด้านบน) มาแสดง -->
        <?php include_once __DIR__ . '/topbar.php'; ?>

        <!-- 4. พื้นที่เนื้อหาที่เลื่อน (Scroll) ได้ -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50/50 p-4 md:p-6 lg:p-8 custom-scrollbar">
            <!-- (หลังจากนี้ ไฟล์ Views ต่างๆ จะนำเนื้อหามาแทรกตรงนี้) -->