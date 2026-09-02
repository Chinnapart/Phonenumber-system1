<?php
// ==========================================
// API: Import Contacts from CSV
// Path: api/contacts/import_csv.php
// ==========================================

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';

Auth::requireAdmin();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีไฟล์อัปโหลด หรือไฟล์มีปัญหา']);
    exit();
}

$file = $_FILES['csv_file']['tmp_name'];
$successCount = 0;
$skipCount = 0;

try {
    // เปิดไฟล์ CSV
    $handle = fopen($file, "r");
    if ($handle !== FALSE) {
        
        // เตรียม Statement ล่วงหน้าเพื่อความรวดเร็วในการ Loop
        $stmtCheckEmp = $pdo->prepare("SELECT id FROM contacts WHERE employee_id = ?");
        $stmtGetDept = $pdo->prepare("SELECT id FROM departments WHERE name = ? LIMIT 1");
        
        $sqlInsert = "INSERT INTO contacts (employee_id, first_name, last_name, department_id, extension, status, created_by) 
                      VALUES (:emp_id, :fname, :lname, :dept_id, :ext, 'online', :created_by)";
        $stmtInsert = $pdo->prepare($sqlInsert);

        $adminId = $_SESSION['user_id'];

        $pdo->beginTransaction(); // เริ่ม Transaction

        // ⭐⭐⭐ 1. เพิ่มตัวแปรสำหรับเช็คแถวแรก (Header) ⭐⭐⭐
        $isFirstRow = true; 

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // ข้ามแถวว่าง
            if (empty(array_filter($data))) continue;

            // ⭐⭐⭐ 2. เช็คว่าถ้าเป็นบรรทัดแรกสุด ให้ข้ามไปเลยไม่ต้องเซฟ ⭐⭐⭐
            if ($isFirstRow) {
                $isFirstRow = false;
                continue;
            }

            // Map ข้อมูลตาม Template (Index 0 ถึง 4)
            // เช็คว่ามีข้อมูลครบก่อนเรียกใช้ ป้องกัน Index Undefined
            $empId = isset($data[0]) ? trim($data[0]) : '';
            $fName = isset($data[1]) ? trim($data[1]) : '';
            $lName = isset($data[2]) ? trim($data[2]) : '';
            $deptName = isset($data[3]) ? trim($data[3]) : '';
            $ext = isset($data[4]) ? trim($data[4]) : '';

           
            // ข้ามแถวที่ชื่อหรือนามสกุลว่าง (ข้อมูลบังคับ)
            if (empty($fName) || empty($lName)) {
                $skipCount++;
                continue;
            }

            // ถ้ามี Emp ID ให้เช็คว่าซ้ำไหม ถ้าซ้ำให้ข้าม
            if (!empty($empId)) {
                $stmtCheckEmp->execute([$empId]);
                if ($stmtCheckEmp->fetch()) {
                    $skipCount++;
                    continue;
                }
            }

            // ค้นหา ID ของแผนกจากชื่อแผนก
            $deptId = null;
            if (!empty($deptName)) {
                $stmtGetDept->execute([$deptName]);
                $deptRow = $stmtGetDept->fetch();
                if ($deptRow) {
                    $deptId = $deptRow['id'];
                }
            }

            // นำข้อมูลเข้า Database
            $stmtInsert->execute([
                ':emp_id' => $empId,
                ':fname' => $fName,
                ':lname' => $lName,
                ':dept_id' => $deptId,
                ':ext' => $ext,
                ':created_by' => $adminId
            ]);

            $successCount++;
        }

        fclose($handle);
        $pdo->commit(); // ยืนยันการบันทึกข้อมูลทั้งหมด

        // บันทึก Log การกระทำของ Admin
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, description) VALUES (?, 'import_csv', ?)");
        $logStmt->execute([$adminId, "นำเข้าข้อมูลจาก CSV จำนวน {$successCount} รายการ"]);

        echo json_encode([
            'status' => 'success', 
            'message' => 'Import สำเร็จ',
            'success_count' => $successCount,
            'skip_count' => $skipCount
        ]);

    } else {
        throw new Exception("ไม่สามารถอ่านไฟล์ CSV ได้");
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // ยกเลิกข้อมูลที่กำลังทำถ้าพังกลางคัน
    }
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>