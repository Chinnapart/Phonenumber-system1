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

if ($id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสแผนก']);
    exit;
}

try {
    // เช็คว่ามีรายชื่อที่ผูกกับแผนกนี้ไหม
    $check = Database::getRow("SELECT COUNT(id) as total FROM contacts WHERE department_id = ?", [$id]);
    if ($check['total'] > 0) {
        echo json_encode(['status' => 'error', 'message' => "ไม่สามารถลบได้ มีพนักงานอยู่ในแผนกนี้ ({$check['total']} คน)"]);
        exit;
    }

    Database::execute("DELETE FROM departments WHERE id = ?", [$id]);
    echo json_encode(['status' => 'success', 'message' => 'ลบแผนกเรียบร้อยแล้ว']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบแผนกได้: ' . $e->getMessage()]);
}