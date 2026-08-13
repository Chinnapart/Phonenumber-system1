<?php
// ==========================================
// API: Ping Batch (Scanner / Cron Job)
// Path: api/network/ping_batch.php
// ==========================================

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// ปลดล็อกระยะเวลาประมวลผลของ PHP (เนื่องจากการ Ping หลายๆ IP ต่อกันอาจใช้เวลานาน)
set_time_limit(0); 

// ฟังก์ชันสำหรับ Ping เช็คสถานะ (เหมือน ping_single แต่เตรียมไว้รันแบบลูป)
function pingAddress($ip) {
    $os = strtoupper(substr(PHP_OS, 0, 3));
    $safeIp = escapeshellarg($ip);
    
    if ($os === 'WIN') {
        $pingCommand = "ping -n 1 -w 1000 " . $safeIp;
    } else {
        $pingCommand = "ping -c 1 -W 1 " . $safeIp;
    }
    
    exec($pingCommand, $output, $resultCode);
    return ($resultCode === 0);
}

try {
    // 1. ดึงข้อมูลรายชื่อทั้งหมดที่มี IP Address และไม่ได้เป็นค่าว่าง
    $stmt = $pdo->query("SELECT id, ip_address FROM contacts WHERE ip_address IS NOT NULL AND ip_address != ''");
    $contacts = $stmt->fetchAll();

    $totalScanned = 0;
    $totalOnline = 0;
    $totalOffline = 0;

    // เตรียมคำสั่ง SQL อัปเดตสถานะล่วงหน้า เพื่อความรวดเร็วในการรันลูป (Prepared Statement Performance)
    $stmtOnline = $pdo->prepare("UPDATE contacts SET status = 'online', last_online = NOW() WHERE id = :id");
    $stmtOffline = $pdo->prepare("UPDATE contacts SET status = 'offline' WHERE id = :id");

    // 2. วนลูป Ping ทีละ IP
    foreach ($contacts as $contact) {
        $ip = $contact['ip_address'];
        
        // เช็คความถูกต้องของรูปแบบ IP เบื้องต้น
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            continue; // ข้าม IP ที่รูปแบบไม่ถูกต้องไป
        }

        // ทำการ Ping
        $isOnline = pingAddress($ip);
        $totalScanned++;

        // 3. อัปเดตผลลัพธ์ลงฐานข้อมูล
        if ($isOnline) {
            $stmtOnline->execute([':id' => $contact['id']]);
            $totalOnline++;
        } else {
            $stmtOffline->execute([':id' => $contact['id']]);
            $totalOffline++;
        }
    }

    // 4. บันทึก Activity Log ของระบบ (ทำโดย System อัตโนมัติ ไม่มี user_id)
    $logSql = "INSERT INTO activity_logs (user_id, action_type, description) VALUES (NULL, 'system_ping_batch', :desc)";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        ':desc' => "System completed background IP scan. Scanned: {$totalScanned}, Online: {$totalOnline}, Offline: {$totalOffline}"
    ]);

    // 5. ส่งผลลัพธ์การสแกนกลับไป
    echo json_encode([
        'status' => 'success',
        'message' => 'Batch ping scan completed successfully.',
        'data' => [
            'total_scanned' => $totalScanned,
            'online_count' => $totalOnline,
            'offline_count' => $totalOffline
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Ping Batch Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการประมวลผล Batch Ping หรือฐานข้อมูล'
    ]);
}
?>