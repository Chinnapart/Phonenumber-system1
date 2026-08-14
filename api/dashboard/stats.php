<?php
// ==========================================
// API: Dashboard Stats & Overview
// Path: api/dashboard/stats.php
// ==========================================

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// อนุญาตเฉพาะ GET Request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

try {
    // 1. หาจำนวน Contacts ทั้งหมด และที่เพิ่มเข้ามาในเดือนนี้
    $stmtTotal = $pdo->query("SELECT COUNT(id) FROM contacts");
    $totalContacts = (int) $stmtTotal->fetchColumn();

    $stmtMonth = $pdo->query("SELECT COUNT(id) FROM contacts WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $newThisMonth = (int) $stmtMonth->fetchColumn();

    // 2. หาจำนวน Contacts ที่ Online และคำนวณเปอร์เซ็นต์
    $stmtOnline = $pdo->query("SELECT COUNT(id) FROM contacts WHERE status = 'online'");
    $onlineContacts = (int) $stmtOnline->fetchColumn();
    
    // ป้องกันการหารด้วย 0
    $onlinePercentage = ($totalContacts > 0) ? round(($onlineContacts / $totalContacts) * 100, 1) : 0;

// หาจำนวน Contacts ที่ Offline และคำนวณเปอร์เซ็นต์
    $stmtOffline = $pdo->query("SELECT COUNT(id) FROM contacts WHERE status = 'offline'");
    $offlineContacts = (int) $stmtOffline->fetchColumn();
    $offlinePercentage = ($totalContacts > 0) ? round(($offlineContacts / $totalContacts) * 100, 1) : 0;

    // 3. หาจำนวนแผนกทั้งหมด
    $stmtDept = $pdo->query("SELECT COUNT(id) FROM departments");
    $totalDepartments = (int) $stmtDept->fetchColumn();

    // 4. หาจำนวนที่อัปเดตวันนี้ (Recently Updated Today)
    $stmtToday = $pdo->query("SELECT COUNT(id) FROM contacts WHERE DATE(updated_at) = CURRENT_DATE()");
    $updatedToday = (int) $stmtToday->fetchColumn();

    // 5. ดึงข้อมูลสำหรับ Department Overview (Chart Data)
    // นับจำนวนคนในแต่ละแผนก พร้อมสีของแผนก (เรียงจากมากไปน้อย)
    $sqlChart = "SELECT d.name, d.color_code, COUNT(c.id) as count 
                 FROM departments d 
                 LEFT JOIN contacts c ON d.id = c.department_id 
                 GROUP BY d.id 
                 ORDER BY count DESC";
    $stmtChart = $pdo->query($sqlChart);
    $chartRawData = $stmtChart->fetchAll();

    // แปลงข้อมูลกราฟเป็นเปอร์เซ็นต์ให้ตรงกับ UI Design
    $chartData = [];
    foreach ($chartRawData as $row) {
        $count = (int)$row['count'];
        $percentage = ($totalContacts > 0) ? round(($count / $totalContacts) * 100, 1) : 0;
        
        $chartData[] = [
            'label' => $row['name'],
            'value' => $count,
            'percentage' => $percentage,
            'color' => $row['color_code'] ?? '#ccc' // ถ้าไม่มีสีให้ใช้สีเทา
        ];
    }

    // 6. รวมข้อมูลทั้งหมดและส่งกลับเป็น JSON
    echo json_encode([
        'status' => 'success',
        'data' => [
            'kpi' => [
                'total_contacts' => $totalContacts,
                'new_this_month' => $newThisMonth,
                'online_active' => $onlineContacts,
                'online_percentage' => $onlinePercentage,
                'offline_active' => $offlineContacts,           // <-- เพิ่มบรรทัดนี้
                'offline_percentage' => $offlinePercentage,     // <-- เพิ่มบรรทัดนี้
                'total_departments' => $totalDepartments,
                'recently_updated' => $updatedToday
            ],
            'chart_data' => $chartData
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Dashboard Stats Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสถิติ'
    ]);
}
?>