<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';

Auth::requireAdmin();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$id = trim($input['id'] ?? ($_POST['id'] ?? ($_GET['id'] ?? '')));

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุ ID ของผู้ใช้งาน']);
    exit();
}

// Prevent deleting self
if ($_SESSION['user_id'] == $id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบบัญชีของตนเองได้']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['status' => 'success', 'message' => 'ลบบัญชีผู้ใช้งานสำเร็จ']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล']);
}
?>