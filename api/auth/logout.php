<?php
// ==========================================
// API: Logout Authentication
// Path: api/auth/logout.php
// ==========================================

// 1. เรียกใช้งาน Config (เพื่อเปิด Session จาก app.php)
require_once __DIR__ . '/../../config/app.php';

// 2. กำหนด Header ว่า API นี้จะตอบกลับเป็น JSON Format
header('Content-Type: application/json; charset=utf-8');

// 3. เคลียร์ข้อมูลตัวแปรใน Session ทั้งหมดให้เป็น Array ว่าง
$_SESSION = [];

// 4. ลบ Cookie ของ Session ใน Browser (เพื่อความปลอดภัยสูงสุด ป้องกันการขโมย Session ID)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. ทำลาย Session ทิ้งอย่างเป็นทางการบน Server
session_destroy();

// 6. ส่ง Response กลับไปบอก Frontend ว่าออกจากระบบสำเร็จแล้ว
echo json_encode([
    'status' => 'success',
    'message' => 'ออกจากระบบเรียบร้อยแล้ว',
    // ส่ง URL สำหรับ Redirect กลับไปหน้า Login (ให้ JavaScript ฝั่ง Frontend จัดการเปลี่ยนหน้า)
    'redirect' => BASE_URL . 'index.php' 
]);
exit();
?>