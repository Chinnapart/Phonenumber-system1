<?php
ob_start();
require_once __DIR__ . '/../../config/database.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

try {
    global $pdo;
    $whereClause = "";
    $params = [];

    if (!empty($search)) {
        $whereClause = "WHERE name LIKE ?";
        $params[] = "%$search%";
    }

    // นับจำนวนแผนกทั้งหมด
    $stmtCount = $pdo->prepare("SELECT COUNT(id) as total FROM departments $whereClause");
    $stmtCount->execute($params);
    $totalRow = $stmtCount->fetch(PDO::FETCH_ASSOC);
    $totalRecords = $totalRow ? $totalRow['total'] : 0;
    $totalPages = ceil($totalRecords / $limit);

    // ดึงข้อมูลแผนก
    $sql = "SELECT * FROM departments $whereClause ORDER BY id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'data' => $departments,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords
        ]
    ]);
    exit;
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}