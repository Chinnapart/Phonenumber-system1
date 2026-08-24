<?php
require_once '../../config/app.php';
require_once '../../core/Database.php';

header('Content-Type: application/json');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

try {
    $whereClause = "";
    $params = [];

    if (!empty($search)) {
        $whereClause = "WHERE name LIKE ?";
        $params[] = "%$search%";
    }

    // นับจำนวนทั้งหมดเพื่อทำ Pagination
    $totalRow = Database::getRow("SELECT COUNT(id) as total FROM departments $whereClause", $params);
    $totalRecords = $totalRow['total'];
    $totalPages = ceil($totalRecords / $limit);

    // ดึงข้อมูล
    $sql = "SELECT * FROM departments $whereClause ORDER BY name ASC LIMIT $limit OFFSET $offset";
    $departments = Database::getAll($sql, $params);

    echo json_encode([
        'status' => 'success',
        'data' => $departments,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}