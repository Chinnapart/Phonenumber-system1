<?php
// ==========================================
// API: Delete Contact
// Path: api/contacts/delete.php
// ==========================================

// 1. เรียกใช้งาน Config และ Database
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

// 2. กำหนด Header ว่า API นี้ตอบกลับเป็น JSON
header('Content-Type: application/json; charset=utf-8');

// 3. อนุญาตเฉพาะ POST หรือ DELETE Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

// 4. รับข้อมูลจาก Request
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// ดึงค่า ID ที่ต้องการลบ (รองรับทั้งการส่งแบบ JSON, POST Form หรือ Query String)
$id = isset($_POST['id']) ? trim($_POST['id']) : 
      (isset($input['id']) ? trim($input['id']) : 
      (isset($_GET['id']) ? trim($_GET['id']) : null));

if (empty($id)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุ ID ของผู้ติดต่อที่ต้องการลบ']);
    exit();
}

$deletedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    // 5. เริ่มต้น Transaction
    $pdo->beginTransaction();

    // 6. ดึงข้อมูลชื่อ-นามสกุล ของคนที่จะลบออกมาก่อน (เพื่อเอาไปลง Log)
    $stmtCheck = $pdo->prepare("SELECT first_name, last_name FROM contacts WHERE id = :id LIMIT 1");
    $stmtCheck->execute([':id' => $id]);
    $contact = $stmtCheck->fetch();

    if (!$contact) {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลผู้ติดต่อที่ต้องการลบ']);
        exit();
    }

    // 7. คำสั่ง SQL สำหรับลบข้อมูล
    $sqlDelete = "DELETE FROM contacts WHERE id = :id";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute([':id' => $id]);

    // 8. บันทึกประวัติกิจกรรม (Activity Log)
    if ($deletedBy) {
        $logSql = "INSERT INTO activity_logs (user_id, action_type, target_id, description) 
                   VALUES (:user_id, 'delete_contact', :target_id, :description)";
        $logStmt = $pdo->prepare($logSql);
        
        $fullName = $contact['first_name'] . ' ' . $contact['last_name'];
        $adminName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'System';
        
        $logStmt->execute([
            ':user_id'     => $deletedBy,
            ':target_id'   => $id,
            ':description' => "{$adminName} deleted contact: {$fullName}"
        ]);
    }

    // 9. ยืนยันการลบข้อมูล (Commit)
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'ลบข้อมูลผู้ติดต่อเรียบร้อยแล้ว'
    ]);

} catch (PDOException $e) {
    // ยกเลิกถ้ามี Error (Rollback)
    $pdo->rollBack();
    
    http_response_code(500);
    error_log("Delete Contact Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล'
    ]);
}
?>