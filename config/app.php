<?php
// ==========================================
// System Constants & Settings
// ==========================================

// 1. กำหนดโซนเวลาของระบบ (ประเทศไทย)
// สำคัญมากสำหรับการบันทึก Activity Log และ Last Online
date_default_timezone_set('Asia/Bangkok');

// 2. ข้อมูลทั่วไปของแอปพลิเคชัน
define('APP_NAME', 'ConnectPro');
define('APP_VERSION', '1.0.0');

// 3. กำหนด Base URL 
// ใช้สำหรับอ้างอิง Path ของไฟล์ต่างๆ เช่น รูปภาพ, CSS, JS หรือการทำ Redirect
// (ตั้งค่าเป็นพอร์ต 8080 ตามที่คุณใช้งาน)
define('BASE_URL', 'http://localhost:8080/connectpro/');

// 4. การจัดการ Directory Path สำหรับ Backend
// ช่วยให้อ้างอิงตำแหน่งโฟลเดอร์ใน Server ได้แม่นยำขึ้น โดยไม่ต้องกังวลเรื่อง Relative Path (../)
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// 5. การตั้งค่าโหมด Environment (Development หรือ Production)
// - true: แสดง Error ทั้งหมด (สำหรับตอน Develop)
// - false: ซ่อน Error (สำหรับตอนนำไปขึ้น Server ใช้งานจริง)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // ซ่อน Error บนหน้าจอ แต่ยังบันทึกลง Error Log ของ Server
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 6. เริ่มต้นระบบ Session (สำหรับการ Login / Authentication)
// เช็คก่อนว่ามี Session เปิดไว้หรือยัง ถ้ายังให้เปิดขึ้นมา
if (session_status() === PHP_SESSION_NONE) {
    // ป้องกันการแฮ็กผ่าน Session ID เบื้องต้น (Session Fixation)
    ini_set('session.use_strict_mode', 1);
    session_start();
}
?>