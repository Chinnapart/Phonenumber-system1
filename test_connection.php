<?php
// 1. ดึงไฟล์ตั้งค่า Database มาใช้งาน
require_once 'config/database.php';

// ตัวแปรสำหรับเก็บข้อความแจ้งเตือน (Success / Error)
$alertMessage = '';
$alertType = ''; // 'success' หรือ 'error'

// 2. ตรวจสอบว่ามีการส่งฟอร์ม (POST Request) เข้ามาหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม ป้องกัน XSS เบื้องต้นด้วย htmlspecialchars
    $firstName = htmlspecialchars($_POST['first_name'] ?? '');
    $lastName = htmlspecialchars($_POST['last_name'] ?? '');
    $departmentId = $_POST['department_id'] ?? null;
    $extension = htmlspecialchars($_POST['extension'] ?? '');
    $ipAddress = htmlspecialchars($_POST['ip_address'] ?? '');

    // ตรวจสอบว่ากรอกข้อมูลจำเป็นครบไหม
    if (!empty($firstName) && !empty($lastName)) {
        try {
            // 3. เตรียมคำสั่ง SQL (Prepared Statement) เพื่อป้องกัน SQL Injection
            $sql = "INSERT INTO contacts (first_name, last_name, department_id, extension, ip_address, status) 
                    VALUES (:first_name, :last_name, :department_id, :extension, :ip_address, 'online')";
            
            $stmt = $pdo->prepare($sql);
            
            // ผูกค่า (Bind Parameters) เข้ากับ SQL
            $stmt->bindParam(':first_name', $firstName);
            $stmt->bindParam(':last_name', $lastName);
            $stmt->bindParam(':department_id', $departmentId);
            $stmt->bindParam(':extension', $extension);
            $stmt->bindParam(':ip_address', $ipAddress);
            
            // 4. สั่งประมวลผล (Execute)
            $stmt->execute();
            
            $alertMessage = "บันทึกข้อมูลคุณ {$firstName} {$lastName} ลงฐานข้อมูลสำเร็จ! (เช็คใน phpMyAdmin ได้เลย)";
            $alertType = 'success';

        } catch (PDOException $e) {
            $alertMessage = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
            $alertType = 'error';
        }
    } else {
        $alertMessage = "กรุณากรอก ชื่อ และ นามสกุล ให้ครบถ้วน";
        $alertType = 'error';
    }
}

// 5. ดึงข้อมูลแผนกจาก Database เพื่อมาแสดงใน Dropdown
$departments = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name ASC");
    $departments = $stmt->fetchAll();
} catch (PDOException $e) {
    $alertMessage = "ไม่สามารถดึงข้อมูลแผนกได้: " . $e->getMessage();
    $alertType = 'error';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test DB Connection | ConnectPro</title>
    <!-- เรียกใช้ Tailwind CSS ผ่าน CDN สำหรับหน้า Test -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-100 via-indigo-50 to-purple-100 min-h-screen flex items-center justify-center p-6">

    <!-- Glassmorphism Card -->
    <div class="bg-white/70 backdrop-blur-xl border border-white/40 shadow-xl shadow-indigo-100/50 rounded-3xl w-full max-w-lg p-8">
        
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-800">ทดสอบเพิ่มข้อมูล (Test Insert)</h2>
            <p class="text-gray-500 mt-2 text-sm">ทดสอบส่งข้อมูลขึ้น Database `connectpro_db`</p>
        </div>

        <!-- Alert Message -->
        <?php if (!empty($alertMessage)): ?>
            <div class="mb-6 p-4 rounded-xl border <?php echo $alertType === 'success' ? 'bg-green-50/50 border-green-200 text-green-700' : 'bg-red-50/50 border-red-200 text-red-700'; ?>">
                <div class="flex items-center">
                    <?php if ($alertType === 'success'): ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <?php endif; ?>
                    <span class="font-medium text-sm"><?php echo $alertMessage; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-5">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ (First Name) *</label>
                    <input type="text" name="first_name" required 
                           class="w-full px-4 py-2 bg-white/50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-transparent outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">นามสกุล (Last Name) *</label>
                    <input type="text" name="last_name" required 
                           class="w-full px-4 py-2 bg-white/50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-transparent outline-none transition">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">แผนก (Department)</label>
                <select name="department_id" 
                        class="w-full px-4 py-2 bg-white/50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-transparent outline-none transition appearance-none">
                    <option value="">-- เลือกแผนก --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์ต่อ (Extension)</label>
                    <input type="text" name="extension" placeholder="เช่น 101"
                           class="w-full px-4 py-2 bg-white/50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-transparent outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                    <input type="text" name="ip_address" placeholder="192.168.x.x"
                           class="w-full px-4 py-2 bg-white/50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-transparent outline-none transition">
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-indigo-500 to-blue-500 hover:from-indigo-600 hover:to-blue-600 text-white font-medium py-2.5 px-4 rounded-lg shadow-lg shadow-indigo-200 transition duration-200">
                    ทดสอบบันทึกข้อมูล
                </button>
            </div>
        </form>

    </div>

</body>
</html>