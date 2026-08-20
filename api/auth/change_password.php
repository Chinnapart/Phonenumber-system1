<?php
// ==========================================
// API: Change Password
// Path: api/auth/change_password.php
// ==========================================

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';

// เช็คสิทธิ์ว่าต้อง Login ก่อนถึงจะใช้งาน API นี้ได้
Auth::requireLogin();
header('Content-Type: application/json; charset=utf-8');

// รับเฉพาะ Method POST เท่านั้น
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

// อ่านข้อมูล JSON ที่ส่งมาจากหน้าบ้าน (Frontend)
$input = json_decode(file_get_contents('php://input'), true);
$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

// 1. ตรวจสอบว่ากรอกข้อมูลครบหรือไม่
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit();
}

// 2. ตรวจสอบรหัสผ่านใหม่และการยืนยันว่าตรงกันหรือไม่
if ($newPassword !== $confirmPassword) {
    echo json_encode(['status' => 'error', 'message' => 'รหัสผ่านใหม่และการยืนยันไม่ตรงกัน']);
    exit();
}

// 3. ตรวจสอบความยาวของรหัสผ่าน (เพื่อความปลอดภัย)
if (strlen($newPassword) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร']);
    exit();
}

try {
    // 4. ดึงข้อมูล Hash รหัสผ่านปัจจุบันของผู้ใช้งานจากฐานข้อมูล
    // อิงจาก Username ที่ถูกเก็บไว้ใน Session ตอน Login
    $username = $_SESSION['username'];
    
    global $pdo; // เรียกใช้ตัวแปร $pdo จาก config/database.php
    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลบัญชีผู้ใช้งานในระบบ']);
        exit();
    }

    // 5. นำรหัสผ่านเดิมที่ผู้ใช้กรอก มาเช็คกับ Hash ในฐานข้อมูล
    if (!password_verify($currentPassword, $user['password_hash'])) {
        echo json_encode(['status' => 'error', 'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง']);
        exit();
    }

    // 6. ถ้ารหัสผ่านเดิมถูกต้อง ให้สร้าง Hash สำหรับรหัสผ่านใหม่
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // 7. อัปเดตรหัสผ่านใหม่ลงฐานข้อมูล
    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = :new_hash WHERE id = :id");
    $updateStmt->execute([
        ':new_hash' => $newPasswordHash,
        ':id' => $user['id']
    ]);

    // ส่งข้อความกลับไปยังหน้าบ้านว่าสำเร็จ
    echo json_encode(['status' => 'success', 'message' => 'เปลี่ยนรหัสผ่านสำเร็จเรียบร้อยแล้ว']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดจากฐานข้อมูล: ' . $e->getMessage()]);
}
?>