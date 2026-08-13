<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';

Auth::requireAdmin();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? ($_POST['username'] ?? ''));
$fullName = trim($input['full_name'] ?? ($_POST['full_name'] ?? ''));
$password = trim($input['password'] ?? ($_POST['password'] ?? ''));
$role = trim($input['role'] ?? ($_POST['role'] ?? 'user'));

if (empty($username) || empty($fullName) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit();
}

try {
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmtCheck->execute([':username' => $username]);
    if ($stmtCheck->fetch()) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Username นี้ถูกใช้งานไปแล้ว']);
        exit();
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (:username, :password_hash, :full_name, :role)");
    $stmt->execute([
        ':username' => $username,
        ':password_hash' => $passwordHash,
        ':full_name' => $fullName,
        ':role' => $role
    ]);

    echo json_encode(['status' => 'success', 'message' => 'เพิ่มผู้ใช้งานสำเร็จ']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล']);
}
?>