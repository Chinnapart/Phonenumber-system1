<?php
// กำหนดค่าคงที่ (Constants) สำหรับการเชื่อมต่อฐานข้อมูล
// คุณสามารถปรับเปลี่ยนค่าเหล่านี้ให้ตรงกับ Server ของคุณได้ (เช่น XAMPP, MAMP หรือ Production Server)
define('DB_HOST', '127.0.0.1');      // ใช้ 127.0.0.1 แทน localhost เพื่อหลีกเลี่ยงปัญหา Socket ในบาง Server
define('DB_PORT', '3306');           // พอร์ตเริ่มต้นของ MySQL
define('DB_NAME', 'connectpro_db');  // ชื่อฐานข้อมูลที่เราสร้างจาก schema.sql
define('DB_USER', 'root');           // Username ของ MySQL (ค่าเริ่มต้นของ XAMPP มักจะเป็น root)
define('DB_PASS', '');               // Password ของ MySQL (ค่าเริ่มต้นของ XAMPP มักจะว่างเปล่า)
define('DB_CHARSET', 'utf8mb4');     // รองรับภาษาไทยและ Emoji ได้สมบูรณ์ 100%

// กำหนดพฤติกรรมการทำงานของ PDO (Options)
$options = [
    // ให้ PDO โยน Exception ออกมาเมื่อมี Error เพื่อให้เรา Catch ได้ง่าย
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // ตั้งค่าเริ่มต้นให้ดึงข้อมูลออกมาในรูปแบบ Associative Array (เช่น $row['first_name'])
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // ปิดการจำลอง Prepared Statements เพื่อบังคับให้ใช้ Prepared Statements ระดับ Database (ปลอดภัยที่สุด)
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// สร้าง Connection String (DSN)
$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

try {
    // สร้าง Object PDO สำหรับใช้งานทั่วทั้งระบบ
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // หมายเหตุ: ในการใช้งานจริง ไฟล์อื่นๆ จะใช้คำสั่ง require_once 'config/database.php'; 
    // และเรียกใช้ตัวแปร $pdo เพื่อ Query ข้อมูลได้เลย

} catch (\PDOException $e) {
    // หากเชื่อมต่อไม่สำเร็จ ให้บันทึก Error ลง Log และแสดงข้อความที่ปลอดภัยแก่ผู้ใช้
    // (ในโหมด Production ไม่ควร echo $e->getMessage() ออกมาตรงๆ เพื่อป้องกันข้อมูล Server รั่วไหล)
    error_log("Database Connection Error: " . $e->getMessage());
    
    // ตอบกลับเป็น JSON ในกรณีที่ API เรียกใช้งาน หรือหยุดการทำงาน
    http_response_code(500);
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed. Please check your configuration."
    ]));
}
?>