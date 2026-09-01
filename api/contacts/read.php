<?php
// ==========================================
// API: Read / Search Contacts
// Path: api/contacts/read.php
// ==========================================

// 1. เรียกใช้งาน Config และ Database
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

// 2. กำหนด Header ว่า API นี้ตอบกลับเป็น JSON
header('Content-Type: application/json; charset=utf-8');

// 3. อนุญาตเฉพาะ GET Request สำหรับการดึงข้อมูล
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

// 4. รับค่าพารามิเตอร์ต่างๆ จาก Frontend (ถ้าไม่มีให้ใช้ค่า Default)
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$department_id = isset($_GET['department_id']) ? $_GET['department_id'] : '';
$status        = isset($_GET['status']) ? $_GET['status'] : '';

// การแบ่งหน้า (Pagination)
$page  = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

try {
    // 5. เตรียมคำสั่ง SQL พื้นฐาน (JOIN ตาราง contacts กับ departments)
    $sql = "SELECT 
                c.id, c.employee_id, c.first_name, c.last_name, c.job_title, 
                c.extension, c.mobile_number, c.ip_address, c.status, c.avatar_url, c.work_status,
                d.name AS department_name, d.color_code AS department_color
            FROM contacts c
            LEFT JOIN departments d ON c.department_id = d.id
            WHERE 1=1"; // 1=1 เป็นเทคนิคช่วยให้ต่อคำสั่ง AND ได้ง่ายขึ้น
    
    // [แก้ไขบั๊กตรงนี้] เพิ่ม LEFT JOIN เข้าไปใน $countSql ด้วย เพื่อให้รู้จัก d.name ตอนค้นหา
    $countSql = "SELECT COUNT(c.id) as total 
                 FROM contacts c 
                 LEFT JOIN departments d ON c.department_id = d.id 
                 WHERE 1=1";
    
    $params = []; // Array สำหรับเก็บค่าที่จะ Bind

    // 6. เพิ่มเงื่อนไขการค้นหา (ถ้ามีการพิมพ์คำค้นหามา)
    if ($search !== '') {
        $searchCond = " AND (c.id = :search_id
                       OR c.first_name LIKE :search_fn 
                       OR c.last_name LIKE :search_ln 
                       OR CONCAT(c.first_name, ' ', c.last_name) LIKE :search_fullname
                       OR c.job_title LIKE :search_jt
                       OR c.extension LIKE :search_ext 
                       OR c.mobile_number LIKE :search_mob
                       OR c.ip_address LIKE :search_ip
                       OR d.name LIKE :search_dept)";
        
        $sql .= $searchCond;
        $countSql .= $searchCond;

        $params[':search_id']       = $search;
        $params[':search_fn']       = "%{$search}%";
        $params[':search_ln']       = "%{$search}%";
        $params[':search_fullname'] = "%{$search}%";
        $params[':search_jt']       = "%{$search}%";
        $params[':search_ext']      = "%{$search}%";
        $params[':search_mob']      = "%{$search}%";
        $params[':search_ip']       = "%{$search}%";
        $params[':search_dept']     = "%{$search}%";
    }

    // 7. เพิ่มเงื่อนไขการกรองตามแผนก (Dropdown)
    if ($department_id !== '') {
        $sql .= " AND c.department_id = :dept_id";
        $countSql .= " AND c.department_id = :dept_id";
        $params[':dept_id'] = $department_id;
    }

    // 8. เพิ่มเงื่อนไขการกรองตามสถานะ Online/Offline
    if ($status !== '') {
        $sql .= " AND c.status = :status";
        $countSql .= " AND c.status = :status";
        $params[':status'] = $status;
    }

    // 9. หาจำนวนรายการทั้งหมด (เพื่อเอาไปทำเลขหน้า Pagination บน Frontend)
    $stmtCount = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value);
    }
    $stmtCount->execute();
    $totalRecords = $stmtCount->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // 10. จัดเรียงข้อมูล และเพิ่ม LIMIT สำหรับ Pagination
    $sql .= " ORDER BY c.first_name ASC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind ค่า Parameter เดิม
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    // Bind ค่า Limit และ Offset (ต้องบังคับเป็น INT)
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $contacts = $stmt->fetchAll();

    // 11. ส่งข้อมูลกลับไปให้ Frontend ในรูปแบบ JSON
    echo json_encode([
        'status' => 'success',
        'data' => $contacts,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages
        ]
    ]);

} catch (PDOException $e) {
    // 12. จัดการ Error กรณีฐานข้อมูลมีปัญหา
    http_response_code(500);
    error_log("Read Contacts Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลฐานข้อมูล'
    ]);
}
?>