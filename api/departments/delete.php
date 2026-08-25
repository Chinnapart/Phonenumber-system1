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

if ($id === 0) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสแผนก']);
    exit;
}

try {
    global $pdo;
    
    // เช็คว่ามีรายชื่อที่ผูกกับแผนกนี้ไหม
    $stmtCheck = $pdo->prepare("SELECT COUNT(id) as total FROM contacts WHERE department_id = ?");
    $stmtCheck->execute([$id]);
    $check = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($check && $check['total'] > 0) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => "ไม่สามารถลบได้ มีพนักงานอยู่ในแผนกนี้ ({$check['total']} คน)"]);
        exit;
    }

    $stmtDel = $pdo->prepare("DELETE FROM departments WHERE id = ?");
    $stmtDel->execute([$id]);
    
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'ลบแผนกเรียบร้อยแล้ว']);
    exit;
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบแผนกได้: ' . $e->getMessage()]);
    exit;
}