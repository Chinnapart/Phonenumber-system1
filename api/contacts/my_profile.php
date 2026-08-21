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
                'email' => '',             // 🌟 เพิ่ม email
                'phone_model' => '',       // 🌟 เพิ่ม phone_model
                'ip_address' => ''
            ],
            'is_new' => true // ตัวบ่งชี้ว่าเป็นข้อมูลใหม่
        ]);
    }
    exit();
}

// ---------------------------------------------------------
// [POST] สำหรับบันทึกการแก้ไขข้อมูล และอัปโหลดรูปภาพ
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // เปลี่ยนมารับค่าจาก $_POST แทนการใช้ json_decode
    $id = $_POST['id'] ?? '';
    $jobTitle = $_POST['job_title'] ?? '';
    $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $extension = $_POST['extension'] ?? '';
    $mobileNumber = $_POST['mobile_number'] ?? '';
    $email = $_POST['email'] ?? '';             // 🌟 รับค่า email
    $phoneModel = $_POST['phone_model'] ?? '';  // 🌟 รับค่า phone_model
    $ipAddress = $_POST['ip_address'] ?? '';

    $parts = explode(' ', $fullName, 2);
    $firstName = $parts[0] ?? '';
    $lastName = $parts[1] ?? '';

    // =====================================
    // ระบบจัดการอัปโหลดรูปภาพ
    // =====================================
    $avatarUrl = null;
    // ตรวจสอบว่ามีการแนบไฟล์รูปมาด้วยและไม่มี Error
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        // สร้างโฟลเดอร์ uploads/avatars ถ้าระบบยังไม่มี
        $uploadDir = __DIR__ . '/../../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // ตรวจสอบนามสกุลไฟล์
        $fileExt = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
            // ตั้งชื่อไฟล์ใหม่ป้องกันชื่อซ้ำกัน
            $newFileName = 'avatar_' . time() . '_' . rand(1000, 9999) . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;

            // ย้ายไฟล์ที่อัปโหลดไปเก็บในโฟลเดอร์
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destPath)) {
                $avatarUrl = 'uploads/avatars/' . $newFileName; // เก็บ Path ลง Database
            }
        }
    }

    try {
        if (empty($id)) {
            // กรณีเป็นข้อมูลใหม่ที่ยังไม่มีในสมุดรายชื่อ (🌟 เพิ่ม email, phone_model ในคำสั่ง INSERT)
            $sql = "INSERT INTO contacts (first_name, last_name, job_title, department_id, extension, mobile_number, email, phone_model, ip_address, status, created_by, avatar_url) 
                    VALUES (:fn, :ln, :jt, :dept, :ext, :mob, :email, :phone_model, :ip, 'online', :cb, :avatar)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':fn' => $firstName, ':ln' => $lastName, ':jt' => $jobTitle,
                ':dept' => $departmentId, ':ext' => $extension, ':mob' => $mobileNumber,
                ':email' => $email, ':phone_model' => $phoneModel,
                ':ip' => $ipAddress, ':cb' => $_SESSION['user_id'], ':avatar' => $avatarUrl
            ]);
        } else {
            // กรณีมีข้อมูลอยู่แล้ว เช็คสิทธิ์ก่อนแก้ไข
            $stmtCheck = $pdo->prepare("SELECT id FROM contacts WHERE id = :id AND CONCAT(first_name, ' ', last_name) = :fullname");
            $stmtCheck->execute([':id' => $id, ':fullname' => $fullName]);
            if (!$stmtCheck->fetch()) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'ไม่อนุญาตให้แก้ไขข้อมูลส่วนตัวของผู้อื่น']);
                exit();
            }

            // ถ้ารูปมีการอัปโหลดใหม่ให้อัปเดต path รูปด้วย (🌟 เพิ่ม email, phone_model ในคำสั่ง UPDATE)
            if ($avatarUrl) {
                $sql = "UPDATE contacts SET 
                        job_title = :jt, department_id = :dept, extension = :ext, 
                        mobile_number = :mob, email = :email, phone_model = :phone_model, 
                        ip_address = :ip, avatar_url = :avatar 
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':jt' => $jobTitle, ':dept' => $departmentId, ':ext' => $extension, 
                    ':mob' => $mobileNumber, ':email' => $email, ':phone_model' => $phoneModel, 
                    ':ip' => $ipAddress, ':avatar' => $avatarUrl, ':id' => $id
                ]);
            } else {
                // ถ้าไม่ได้เปลี่ยนรูป ก็อัปเดตแค่ข้อมูลปกติ (🌟 เพิ่ม email, phone_model ในคำสั่ง UPDATE)
                $sql = "UPDATE contacts SET 
                        job_title = :jt, department_id = :dept, extension = :ext, 
                        mobile_number = :mob, email = :email, phone_model = :phone_model, 
                        ip_address = :ip 
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':jt' => $jobTitle, ':dept' => $departmentId, ':ext' => $extension, 
                    ':mob' => $mobileNumber, ':email' => $email, ':phone_model' => $phoneModel, 
                    ':ip' => $ipAddress, ':id' => $id
                ]);
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'อัปเดตข้อมูลโปรไฟล์และรูปภาพสำเร็จ!']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดจากฐานข้อมูล']);
    }
    exit();
}
?>