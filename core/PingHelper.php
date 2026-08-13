<?php
// ==========================================
// Core: Network Ping Helper
// Path: core/PingHelper.php
// ==========================================

class PingHelper {
    
    /**
     * ตรวจสอบสถานะการเชื่อมต่อของ IP Address ผ่านคำสั่ง Ping (ICMP)
     * 
     * @param string $ip หมายเลข IP Address ที่ต้องการทดสอบ
     * @return bool คืนค่า true ถ้าอุปกรณ์ Online, คืนค่า false ถ้า Offline หรือรูปแบบ IP ผิด
     */
    public static function checkIp($ip) {
        // 1. ตรวจสอบความถูกต้องของรูปแบบ IP ก่อน (ป้องกัน Command Injection)
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        // 2. ครอบ IP ด้วย escapeshellarg เพื่อความปลอดภัยสูงสุดในการรันคำสั่ง OS
        $safeIp = escapeshellarg($ip);
        
        // 3. ตรวจสอบระบบปฏิบัติการของ Server (เพื่อใช้ Syntax คำสั่ง Ping ให้ถูกต้อง)
        $os = strtoupper(substr(PHP_OS, 0, 3));
        
        if ($os === 'WIN') {
            // Windows: -n 1 (ปิง 1 ครั้ง), -w 1000 (รอ Timeout 1000 มิลลิวินาที / 1 วินาที)
            $pingCommand = "ping -n 1 -w 1000 " . $safeIp;
        } else {
            // Linux/Mac: -c 1 (ปิง 1 ครั้ง), -W 1 (รอ Timeout 1 วินาที)
            $pingCommand = "ping -c 1 -W 1 " . $safeIp;
        }
        
        // 4. รันคำสั่งผ่านระบบปฏิบัติการ
        exec($pingCommand, $output, $resultCode);
        
        // 5. หาก Ping สำเร็จ ค่า $resultCode จะเป็น 0
        return ($resultCode === 0);
    }
}
?>