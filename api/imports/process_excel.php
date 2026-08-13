<?php
// ==========================================
// API: Import Excel (CSV format)
// Path: api/imports/process_excel.php
// ==========================================

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// อนุญาตเฉพาะ POST Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

// ตรวจสอบว่ามีการอัปโหลดไฟล์เข้ามาหรือไม่
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'กรุณาอัปโหลดไฟล์ CSV เพื่อนำเข้าข้อมูล']);
    exit();
}

$file = $_FILES['file'];
$fileName = $file['name'];
$fileTmp = $file['tmp_name'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// อนุญาตเฉพาะไฟล์ .csv
if ($fileExt !== 'csv') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ระบบรองรับเฉพาะไฟล์ .csv เท่านั้น (กรุณา Export จาก Excel เป็น CSV)']);
    exit();
}

try {
    // 1. ดึงข้อมูลแผนกทั้งหมดมาเก็บไว้ใน Array เพื่อช่วยแปลชื่อแผนกเป็น ID (Mapping)
    $stmtDept = $pdo->query("SELECT id, name FROM departments");
    $departments = [];
    while ($row = $stmtDept->fetch()) {
        // เก็บชื่อแผนกเป็นตัวพิมพ์เล็กทั้งหมด เพื่อให้การค้นหาง่ายขึ้น
        $departments[strtolower(trim($row['name']))] = $row['id'];
    }

    // 2. เปิดไฟล์ CSV เพื่อเตรียมอ่านข้อมูล
    $handle = fopen($fileTmp, "r");
    if ($handle !== FALSE) {
        
        // เริ่มต้น Transaction ป้องกันข้อมูลเข้าไม่ครบ
        $pdo->beginTransaction();
        
        $importedCount = 0;
        $rowNumber = 0;
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        // คำสั่ง SQL เตรียมไว้สำหรับการเพิ่มข้อมูล
        $sqlInsert = "INSERT INTO contacts (first_name, last_name, job_title, department_id, extension, mobile_number, ip_address, status, created_by) 
                      VALUES (:first_name, :last_name, :job_title, :department_id, :extension, :mobile_number, :ip_address, 'unknown', :created_by)";
        $stmtInsert = $pdo->prepare($sqlInsert);

        // วนลูปอ่านข้อมูลทีละแถว
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rowNumber++;
            
            // ข้ามแถวแรก (มักจะเป็น Header ของตาราง เช่น First Name, Last Name)
            if ($rowNumber === 1) {
                continue; 
            }

            // รับค่าและตัดช่องว่างซ้ายขวา (trim)
            // Column Index: [0]=First Name, [1]=Last Name, [2]=Job Title, [3]=Department, [4]=Extension, [5]=Mobile, [6]=IP
            $firstName = trim($data[0] ?? '');
            $lastName = trim($data[1] ?? '');
            
            // บังคับว่าต้องมีชื่อและนามสกุล หากไม่มีให้ข้ามแถวนี้ไป
            if (empty($firstName) || empty($lastName)) {
                continue; 
            }

            $jobTitle = trim($data[2] ?? '');
            $deptName = strtolower(trim($data[3] ?? '')); // ทำเป็นตัวพิมพ์เล็กเพื่อเทียบ
            $extension = trim($data[4] ?? '');
            $mobileNumber = trim($data[5] ?? '');
            $ipAddress = trim($data[6] ?? '');

            // หา Department ID จากชื่อแผนก ถ้าไม่มีให้เป็น null
            $departmentId = $departments[$deptName] ?? null;

            // ทำการบันทึกลง Database
            $stmtInsert->execute([
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':job_title' => $jobTitle,
                ':department_id' => $departmentId,
                ':extension' => $extension,
                ':mobile_number' => $mobileNumber,
                ':ip_address' => $ipAddress,
                ':created_by' => $userId
            ]);

            $importedCount++;
        }
        fclose($handle); // ปิดไฟล์เมื่ออ่านเสร็จ

        // 3. บันทึก Activity Log ของระบบ
        if ($importedCount > 0 && $userId) {
            $logSql = "INSERT INTO activity_logs (user_id, action_type, description) VALUES (:user_id, 'import_excel', :description)";
            $logStmt = $pdo->prepare($logSql);
            $adminName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'System';
            
            $logStmt->execute([
                ':user_id' => $userId,
                ':description' => "{$adminName} imported {$importedCount} contacts from Excel file"
            ]);
        }

        // 4. ยืนยันการบันทึกข้อมูลทั้งหมด
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => "นำเข้าข้อมูลสำเร็จจำนวน {$importedCount} รายการ",
            'data' => [
                'imported_count' => $importedCount
            ]
        ]);

    } else {
        throw new Exception("ไม่สามารถเปิดไฟล์เพื่ออ่านข้อมูลได้");
    }

} catch (Exception $e) {
    // หากมี Error ให้ Rollback สิ่งที่ทำมาทั้งหมด
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    error_log("Import Excel Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล หรือรูปแบบข้อมูลไม่ถูกต้อง'
    ]);
}
?>