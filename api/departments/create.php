<?php
ob_start(); // ป้องกันไม่ให้มี HTML หรือข้อความแปลกปลอมส่งออกไป
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$name = trim($data['name'] ?? '');
$color_code = trim($data['color_code'] ?? '#3b82f6');

if (empty($name)) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อแผนก']);
    exit;
}

try {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO departments (name, color_code) VALUES (?, ?)");
    $stmt->execute([$name, $color_code]);
    
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'เพิ่มแผนกสำเร็จ']);
    exit;
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    exit;
}