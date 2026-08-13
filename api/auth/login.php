<?php
// ==========================================
// API: Login Authentication
// Path: api/auth/login.php
// ==========================================

// 1. เรียกใช้งาน Config และ Database
// ใช้ __DIR__ เพื่อระบุตำแหน่ง Absolute Path ป้องกันปัญหาหาไฟล์ไม่เจอ
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

// 2. กำหนด Header ว่า API นี้จะตอบกลับเป็น JSON Format
header('Content-Type: application/json; charset=utf-8');

// 3. ตรวจสอบว่าต้องเป็น POST Request เท่านั้น
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่อนุญาตให้เข้าถึงด้วยวิธีนี้ (Method Not Allowed)'
    ]);
    exit();
}

// 4. รับค่า Username และ Password จาก Request
// รองรับทั้งแบบ x-www-form-urlencoded (จาก Form ปกติ) และ JSON Payload (จาก Fetch API)
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$username = $_POST['username'] ?? ($input['username'] ?? '');
$password = $_POST['password'] ?? ($input['password'] ?? '');

// ป้องกันการส่งค่าว่าง
if (empty(trim($username)) || empty(trim($password))) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'status' => 'error',
        'message' => 'กรุณากรอก Username และ Password ให้ครบถ้วน'
    ]);
    exit();
}

try {
    // 5. ค้นหา User จากฐานข้อมูลด้วย Prepared Statement
    $stmt = $pdo->prepare("SELECT id, username, password_hash, full_name, role FROM users WHERE username = :username LIMIT 1");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    $user = $stmt->fetch();

    // 6. ตรวจสอบว่าพบ User และ Password ตรงกันหรือไม่ (ใช้ password_verify แกะ Hash)
    if ($user && password_verify($password, $user['password_hash'])) {
        
        // 7. เมื่อผ่านการตรวจสอบ ให้สร้าง Session เก็บข้อมูลผู้ใช้
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role']; // 'admin' หรือ 'user'

        // 8. ส่ง Response กลับไปบอก Frontend ว่าสำเร็จ
        echo json_encode([
            'status' => 'success',
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'data' => [
                'role' => $user['role'],
                'full_name' => $user['full_name']
            ],
            // ส่ง URL สำหรับ Redirect ตาม Role (ไว้ให้ JS จัดการเปลี่ยนหน้า)
            'redirect' => BASE_URL . 'index.php' 
        ]);
        
    } else {
        // กรณี Username ไม่มี หรือ Password ผิด
        http_response_code(401); // Unauthorized
        echo json_encode([
            'status' => 'error',
            'message' => 'Username หรือ Password ไม่ถูกต้อง'
        ]);
    }

} catch (PDOException $e) {
    // กรณีมีปัญหาเชื่อมต่อ DB หรือ Query พัง
    http_response_code(500); // Internal Server Error
    error_log("Login Error: " . $e->getMessage()); // บันทึก Log 
    
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อระบบฐานข้อมูล'
        // 'debug' => $e->getMessage() // เปิดไว้ดูชั่วคราวตอน Dev ได้
    ]);
}
?>