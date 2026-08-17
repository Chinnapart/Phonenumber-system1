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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // รับค่าจากฟอร์ม (เฉพาะข้อมูลที่อนุญาตให้ User แก้ไขเองได้)
    $id = $input['id'] ?? '';
    $jobTitle = $input['job_title'] ?? '';
    $departmentId = !empty($input['department_id']) ? (int)$input['department_id'] : null;
    $extension = $input['extension'] ?? '';
    $mobileNumber = $input['mobile_number'] ?? '';
    $ipAddress = $input['ip_address'] ?? '';

    // บังคับใช้ ชื่อ-นามสกุล จาก Session ของคนที่ล็อกอินอยู่เท่านั้น (ป้องกันการสวมรอย)
    $parts = explode(' ', $fullName, 2);
    $firstName = $parts[0] ?? '';
    $lastName = $parts[1] ?? '';

    try {
        if (empty($id)) {
            // สร้างเรคคอร์ดใหม่ หากยังไม่เคยมีในสมุดรายชื่อ
            $sql = "INSERT INTO contacts (first_name, last_name, job_title, department_id, extension, mobile_number, ip_address, status, created_by) 
                    VALUES (:fn, :ln, :jt, :dept, :ext, :mob, :ip, 'online', :cb)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':fn' => $firstName, ':ln' => $lastName, ':jt' => $jobTitle,
                ':dept' => $departmentId, ':ext' => $extension, ':mob' => $mobileNumber,
                ':ip' => $ipAddress, ':cb' => $_SESSION['user_id']
            ]);
        } else {
            // หากมีอยู่แล้ว ต้องเช็คให้ชัวร์ว่ากำลังแก้ไขข้อมูลของตัวเองจริงๆ
            $stmtCheck = $pdo->prepare("SELECT id FROM contacts WHERE id = :id AND CONCAT(first_name, ' ', last_name) = :fullname");
            $stmtCheck->execute([':id' => $id, ':fullname' => $fullName]);
            if (!$stmtCheck->fetch()) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'ไม่อนุญาตให้แก้ไขข้อมูลส่วนตัวของผู้อื่น']);
                exit();
            }

            // ทำการอัปเดต
            $sql = "UPDATE contacts SET job_title = :jt, department_id = :dept, extension = :ext, mobile_number = :mob, ip_address = :ip 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':jt' => $jobTitle, ':dept' => $departmentId, ':ext' => $extension, 
                ':mob' => $mobileNumber, ':ip' => $ipAddress, ':id' => $id
            ]);
        }
        
        echo json_encode(['status' => 'success', 'message' => 'อัปเดตข้อมูลโปรไฟล์ส่วนตัวสำเร็จ!']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดจากฐานข้อมูล']);
    }
    exit();
}
?>