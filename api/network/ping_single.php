<?php
// ==========================================
// API: Ping Single IP Address
// Path: api/network/ping_single.php
// ==========================================

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// อนุญาตให้ใช้ GET หรือ POST ก็ได้เพื่อความยืดหยุ่นในการทดสอบ
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$ipAddress = $_REQUEST['ip_address'] ?? ($input['ip_address'] ?? '');
$contactId = $_REQUEST['contact_id'] ?? ($input['contact_id'] ?? null);

// ตรวจสอบความถูกต้องของ IP Address (สำคัญมาก เพื่อป้องกัน Command Injection)
if (empty($ipAddress) || !filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'รูปแบบ IP Address ไม่ถูกต้อง']);
    exit();
}

// ฟังก์ชันสำหรับ Ping เช็คสถานะ
function pingAddress($ip) {
    // กำหนดคำสั่ง Ping โดยแยกตามระบบปฏิบัติการ (Windows หรือ Linux/Mac)
    $os = strtoupper(substr(PHP_OS, 0, 3));
    
    // ใช้ escapeshellarg เพื่อความปลอดภัยสูงสุด (แม้จะ filter มาแล้ว)
    $safeIp = escapeshellarg($ip);
    
    if ($os === 'WIN') {
        // Windows: -n 1 (ปิง 1 ครั้ง), -w 1000 (รอ Timeout 1 วินาที)
        $pingCommand = "ping -n 1 -w 1000 " . $safeIp;
    } else {
        // Linux/Mac: -c 1 (ปิง 1 ครั้ง), -W 1 (รอ Timeout 1 วินาที)
        $pingCommand = "ping -c 1 -W 1 " . $safeIp;
    }
    
    // ประมวลผลคำสั่ง Ping ผ่าน OS
    exec($pingCommand, $output, $resultCode);
    
    // หาก Ping สำเร็จ ค่า $resultCode จะเป็น 0
    return ($resultCode === 0);
}

try {
    // 1. ทำการ Ping ไปยัง IP ปลายทาง
    $isOnline = pingAddress($ipAddress);
    $statusText = $isOnline ? 'online' : 'offline';
    
    // 2. ถ้ามีการส่ง contact_id มาด้วย ให้อัปเดตข้อมูลลงฐานข้อมูล
    if (!empty($contactId) && is_numeric($contactId)) {
        
        if ($isOnline) {
            // กรณี Online: อัปเดตสถานะและเวลาที่ออนไลน์ล่าสุด
            $sql = "UPDATE contacts SET status = 'online', last_online = NOW() WHERE id = :id";
        } else {
            // กรณี Offline: อัปเดตแค่สถานะ
            $sql = "UPDATE contacts SET status = 'offline' WHERE id = :id";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $contactId]);
    }
    
    // 3. ส่งผลลัพธ์กลับไปยัง Frontend
    echo json_encode([
        'status' => 'success',
        'message' => 'Ping ตรวจสอบสถานะเสร็จสิ้น',
        'data' => [
            'ip_address' => $ipAddress,
            'is_online' => $isOnline,
            'status_text' => $statusText,
            'contact_id' => $contactId
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Ping Update Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการอัปเดตสถานะลงฐานข้อมูล'
    ]);
}
?>