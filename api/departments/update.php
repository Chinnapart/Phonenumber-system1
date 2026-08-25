<?php
ob_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = isset($data['id']) ? (int)$data['id'] : 0;
$name = trim($data['name'] ?? '');
$color_code = trim($data['color_code'] ?? '#3b82f6');

if ($id === 0 || empty($name)) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE departments SET name = ?, color_code = ? WHERE id = ?");
    $stmt->execute([$name, $color_code, $id]);
    
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'อัปเดตข้อมูลแผนกสำเร็จ']);
    exit;
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถอัปเดตแผนกได้: ' . $e->getMessage()]);
    exit;
}