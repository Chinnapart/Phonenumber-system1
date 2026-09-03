<?php
// ==========================================
// API: Auto Generate Users from Contacts
// Path: api/contacts/auto_generate.php
// ==========================================

require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../core/AuthMiddleware.php';

// ปิดการแสดง Error ปกติออกทางหน้าจอ เพื่อไม่ให้ JSON พัง
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

// บังคับว่าต้องเป็น Admin
if (!Auth::isAdmin()) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง (Unauthorized)']);
    exit;
}

// รับค่า JSON ที่ส่งมาจาก Javascript
$data = json_decode(file_get_contents('php://input'), true);
$contactIds = $data['contact_ids'] ?? [];

if (empty($contactIds)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเลือกพนักงานอย่างน้อย 1 รายการ']);
    exit;
}

try {
    $pdo->beginTransaction();
    $successCount = 0;
    $skipCount = 0;

    // 🌟 กำหนดรหัสผ่านเริ่มต้นคือคำว่า 'password'
    $defaultPassword = password_hash('password', PASSWORD_DEFAULT);

    foreach ($contactIds as $id) {
        $stmt = $pdo->prepare("SELECT employee_id, first_name, last_name FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);

        // ถ้าไม่มีข้อมูล หรือไม่มีรหัสพนักงาน (EMP) ให้ข้าม
        if (!$contact || empty(trim($contact['employee_id']))) {
            $skipCount++;
            continue;
        }

        $empId = trim($contact['employee_id']);
        $fullName = trim($contact['first_name'] . ' ' . $contact['last_name']);

        // เช็คว่ามี Username (EMP) นี้ในระบบ Users แล้วหรือยัง
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->execute([$empId]);
        if ($checkStmt->fetch()) {
            $skipCount++;
            continue; 
        }

        // สร้างบัญชี User ใหม่
        $insertStmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, 'user')");
        $insertStmt->execute([$empId, $defaultPassword, $fullName]);
        $successCount++;
    }

    $pdo->commit();
    
    // เคลียร์ Output ขยะที่อาจจะติดมาก่อนหน้านี้
    ob_clean(); 
    echo json_encode([
        'status' => 'success', 
        'message' => "สร้างบัญชีสำเร็จ $successCount รายการ (ข้าม $skipCount รายการ)"
    ]);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
    exit;
}