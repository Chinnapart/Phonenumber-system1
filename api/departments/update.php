<?php
require_once '../../config/app.php';
require_once '../../core/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = isset($data['id']) ? (int)$data['id'] : 0;
$name = trim($data['name'] ?? '');
$color_code = trim($data['color_code'] ?? '#94a3b8');

if ($id === 0 || empty($name)) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    Database::execute("UPDATE departments SET name = ?, color_code = ? WHERE id = ?", [$name, $color_code, $id]);
    echo json_encode(['status' => 'success', 'message' => 'อัปเดตข้อมูลแผนกสำเร็จ']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถอัปเดตแผนกได้: ' . $e->getMessage()]);
}