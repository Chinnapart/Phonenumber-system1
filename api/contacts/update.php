<?php
// ==========================================
// API: Update Contact
// Path: api/contacts/update.php
// ==========================================

// 1. เรียกใช้งาน Config และ Database
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

// 2. กำหนด Header ว่า API นี้ตอบกลับเป็น JSON
header('Content-Type: application/json; charset=utf-8');

// 3. อนุญาตเฉพาะ POST หรือ PUT Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

// 4. รับข้อมูลจาก Request (รองรับทั้ง JSON Payload และ Form Data)
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// ฟังก์ชันช่วยดึงค่า
function getValue($field, $input) {
    // สำหรับ PUT method ผ่าน Fetch API มักจะมาเป็น JSON
    return isset($_POST[$field]) ? trim($_POST[$field]) : (isset($input[$field]) ? trim($input[$field]) : null);
}

// รับค่า ID ที่ต้องการแก้ไข (จำเป็นต้องมี)
$id = getValue('id', $input);

if (empty($id)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุ ID ของผู้ติดต่อที่ต้องการแก้ไข']);
    exit();
}

// รับค่าอื่นๆ ที่อาจจะถูกส่งมาแก้ไข
$employeeId   = getValue('employee_id', $input);
$firstName    = getValue('first_name', $input);
$lastName     = getValue('last_name', $input);
$jobTitle     = getValue('job_title', $input);
$departmentId = getValue('department_id', $input);
$extension    = getValue('extension', $input);
$mobileNumber = getValue('mobile_number', $input);
$ipAddress    = getValue('ip_address', $input);

// ตรวจสอบข้อมูลเบื้องต้น
if ($firstName === '' || $lastName === '') {
    http_response_code(400); 
    echo json_encode(['status' => 'error', 'message' => 'ชื่อและนามสกุลไม่สามารถเป็นค่าว่างได้']);
    exit();
}

$departmentId = ($departmentId !== '' && $departmentId !== null) ? (int)$departmentId : null;
$updatedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    // 5. เริ่มต้น Transaction
    $pdo->beginTransaction();

    // 6. คำสั่ง SQL สำหรับอัปเดตข้อมูลผู้ติดต่อ
    $sql = "UPDATE contacts SET 
                employee_id = :employee_id,
                first_name = :first_name, 
                last_name = :last_name, 
                job_title = :job_title, 
                department_id = :department_id, 
                extension = :extension, 
                mobile_number = :mobile_number, 
                ip_address = :ip_address 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':employee_id'   => $employeeId,
        ':first_name'    => $firstName,
        ':last_name'     => $lastName,
        ':job_title'     => $jobTitle,
        ':department_id' => $departmentId,
        ':extension'     => $extension,
        ':mobile_number' => $mobileNumber,
        ':ip_address'    => $ipAddress,
        ':id'            => $id
    ]);

    // ตรวจสอบว่ามีการอัปเดตข้อมูลจริงหรือไม่
    if ($stmt->rowCount() > 0) {
        // 7. บันทึกประวัติกิจกรรม (Activity Log)
        if ($updatedBy) {
            $logSql = "INSERT INTO activity_logs (user_id, action_type, target_id, description) 
                       VALUES (:user_id, 'update_contact', :target_id, :description)";
            $logStmt = $pdo->prepare($logSql);
            
            $fullName = $firstName . ' ' . $lastName;
            $adminName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'System';
            
            $logStmt->execute([
                ':user_id'     => $updatedBy,
                ':target_id'   => $id,
                ':description' => "{$adminName} updated contact details for: {$fullName}"
            ]);
        }

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'อัปเดตข้อมูลผู้ติดต่อสำเร็จ'
        ]);
    } else {
        // กรณีที่ไม่พบ ID หรือ ข้อมูลที่ส่งมาเหมือนเดิมทุกอย่าง
        $pdo->rollBack();
        echo json_encode([
            'status' => 'info',
            'message' => 'ไม่พบข้อมูลที่ต้องเปลี่ยนแปลง หรือ ID ไม่ถูกต้อง'
        ]);
    }

} catch (PDOException $e) {
    // ยกเลิกการบันทึกถ้ามี Error
    $pdo->rollBack();
    
    http_response_code(500);
    error_log("Update Contact Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล'
    ]);
}
?>