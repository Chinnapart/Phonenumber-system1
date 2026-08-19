<?php
// ==========================================
// API: Get & Update My Profile (For Normal Users)
// Path: api/contacts/my_profile.php
// ==========================================

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';

// บังคับว่าต้อง Login (เข้าได้ทั้ง User และ Admin)
Auth::requireLogin();
header('Content-Type: application/json; charset=utf-8');

// ดึงชื่อ-นามสกุลจาก Session ที่ Login อยู่
$fullName = $_SESSION['full_name'];

// ---------------------------------------------------------
// [GET] สำหรับดึงข้อมูลโปรไฟล์มาแสดงในฟอร์ม
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // ค้นหาจากฐานข้อมูลโดยเอา first_name และ last_name มาต่อกัน
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE CONCAT(first_name, ' ', last_name) = :fullname LIMIT 1");
    $stmt->execute([':fullname' => $fullName]);
    $contact = $stmt->fetch();

    if ($contact) {
        echo json_encode(['status' => 'success', 'data' => $contact]);
    } else {
        // หากยังไม่มีข้อมูลเบอร์โทรในระบบ (เข้าครั้งแรก) ให้แยกชื่อ-นามสกุลเตรียมไว้สำหรับฟอร์ม
        $parts = explode(' ', $fullName, 2);
        echo json_encode([
            'status' => 'success', 
            'data' => [
                'id' => '',
                'first_name' => $parts[0] ?? '',
                'last_name' => $parts[1] ?? '',
                'job_title' => '',
                'department_id' => '',
                'extension' => '',
                'mobile_number' => '',
                'ip_address' => ''
            ],
            'is_new' => true // ตัวบ่งชี้ว่าเป็นข้อมูลใหม่
        ]);
    }
    exit();
}

// ---------------------------------------------------------
// [POST] สำหรับบันทึกการแก้ไขข้อมูล
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST')
?>