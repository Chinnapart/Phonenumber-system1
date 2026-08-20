<?php
// Path: api/contacts/update_work_status.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';

Auth::requireLogin();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$status = $input['status'] ?? 'available';
$fullName = $_SESSION['full_name']; // อ้างอิงจากคนที่ล็อกอิน

try {
    // อัปเดตสถานะของตัวเอง
    $stmt = $pdo->prepare("UPDATE contacts SET work_status = :status WHERE CONCAT(first_name, ' ', last_name) = :fullname");
    $stmt->execute([':status' => $status, ':fullname' => $fullName]);
    
    echo json_encode(['status' => 'success', 'message' => 'อัปเดตสถานะเป็น "'.$status.'" แล้ว']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดจากฐานข้อมูล']);
}
?>