<?php
// ==========================================
// API: Create Contact
// Path: api/contacts/create.php
// ==========================================

// 1. เรียกใช้งาน Config และ Database
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

// 2. กำหนด Header ว่า API นี้ตอบกลับเป็น JSON
header('Content-Type: application/json; charset=utf-8');

// 3. อนุญาตเฉพาะ POST Request เท่านั้น
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

// 4. รับข้อมูลจาก Request (รองรับทั้ง JSON Payload และ x-www-form-urlencoded)
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// ฟังก์ชันช่วยดึงค่า (ถ้าไม่มีให้คืนค่าว่าง)
function getValue($field, $input) {
    return isset($_POST[$field]) ? trim($_POST[$field]) : (isset($input[$field]) ? trim($input[$field]) : '');
}

$employeeId   = getValue('employee_id', $input);
$firstName    = getValue('first_name', $input);
$lastName     = getValue('last_name', $input);
$jobTitle     = getValue('job_title', $input);
$departmentId = getValue('department_id', $input);
$extension    = getValue('extension', $input);
$mobileNumber = getValue('mobile_number', $input);
$ipAddress    = getValue('ip_address', $input);

// 5. ตรวจสอบความถูกต้องของข้อมูล (Validation)
if (empty($firstName) || empty($lastName)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอก ชื่อ และ นามสกุล ให้ครบถ้วน']);
    exit();
}

// แปลง department_id ให้เป็น NULL หากไม่ได้ส่งค่ามา (ป้องกัน Error Foreign Key)
$departmentId = ($departmentId !== '') ? (int)$departmentId : null;

// ดึง ID ของผู้ใช้งานที่กำลัง Login อยู่ (ถ้ามี) เพื่อบันทึกว่าใครเป็นคนสร้าง
$createdBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    // 6. เริ่มต้น Transaction (เพื่อความชัวร์ในการบันทึกข้อมูลหลายตาราง)
    $pdo->beginTransaction();

    // 7. คำสั่ง SQL สำหรับเพิ่มผู้ติดต่อ
    $sql = "INSERT INTO contacts (employee_id, first_name, last_name, job_title, department_id, extension, mobile_number, ip_address, status, created_by) 
            VALUES (:employee_id, :first_name, :last_name, :job_title, :department_id, :extension, :mobile_number, :ip_address, 'unknown', :created_by)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':employee_id'   => $employeeId, // <-- ถ้าไม่มีตัวแปร $employeeId ประกาศไว้ข้างบน จะทำให้พังได้
        ':first_name'    => $firstName,
        ':last_name'     => $lastName,
        ':job_title'     => $jobTitle,
        ':department_id' => $departmentId,
        ':extension'     => $extension,
        ':mobile_number' => $mobileNumber,
        ':ip_address'    => $ipAddress,
        ':created_by'    => $createdBy
    ]);

    // ดึง ID ของรายการที่เพิ่งเพิ่มเข้าไป
    $newContactId = $pdo->lastInsertId();

    // 8. บันทึกประวัติกิจกรรม (Activity Log) 
    // เฉพาะในกรณีที่มีผู้ใช้ Login อยู่ (มี $createdBy)
    if ($createdBy) {
        $logSql = "INSERT INTO activity_logs (user_id, action_type, target_id, description) 
                   VALUES (:user_id, 'add_contact', :target_id, :description)";
        $logStmt = $pdo->prepare($logSql);
        
        $fullName = $firstName . ' ' . $lastName;
        $adminName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'System';
        
        $logStmt->execute([
            ':user_id'     => $createdBy,
            ':target_id'   => $newContactId,
            ':description' => "{$adminName} added new contact: {$fullName}"
        ]);
    }

    // 9. ยืนยันการบันทึกข้อมูล (Commit)
    $pdo->commit();

    // 10. ส่งผลลัพธ์กลับไปยัง Frontend
    echo json_encode([
        'status' => 'success',
        'message' => 'เพิ่มข้อมูลผู้ติดต่อสำเร็จ',
        'data' => [
            'id' => $newContactId
        ]
    ]);

} catch (PDOException $e) {
    // หากมีข้อผิดพลาด ให้ยกเลิกการบันทึกข้อมูลทั้งหมด (Rollback)
    $pdo->rollBack();
    
    http_response_code(500);
    error_log("Create Contact Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'
    ]);
}
?>