<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';

Auth::requireAdmin();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$id = trim($input['id'] ?? ($_POST['id'] ?? ''));
$username = trim($input['username'] ?? ($_POST['username'] ?? ''));
$fullName = trim($input['full_name'] ?? ($_POST['full_name'] ?? ''));
$password = trim($input['password'] ?? ($_POST['password'] ?? ''));
$role = trim($input['role'] ?? ($_POST['role'] ?? 'user'));

if (empty($id) || empty($username) || empty($fullName)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit();
}

try {
    if (!empty($password)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username = :username, full_name = :full_name, password_hash = :password_hash, role = :role WHERE id = :id");
        $stmt->execute([
            ':username' => $username,
            ':full_name' => $fullName,
            ':password_hash' => $passwordHash,
            ':role' => $role,
            ':id' => $id
        ]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = :username, full_name = :full_name, role = :role WHERE id = :id");
        $stmt->execute([
            ':username' => $username,
            ':full_name' => $fullName,
            ':role' => $role,
            ':id' => $id
        ]);
    }

    echo json_encode(['status' => 'success', 'message' => 'อัปเดตข้อมูลผู้ใช้งานสำเร็จ']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล']);
}
?>