<?php
// ==========================================
// Admin View: Import Contacts from CSV/Excel
// Path: views/admin/import_excel.php
// ==========================================

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';

// บังคับว่าต้องเป็น Admin เท่านั้น
Auth::requireAdmin();

$pageTitle = 'นำเข้าข้อมูลบุคลากร';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-6 animate-fade-in relative pb-10">
    
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">นำเข้าข้อมูล (Import Data)</h1>
            <p class="text-sm text-gray-500 mt-1">อัปโหลดไฟล์ Excel (.csv) เพื่อเพิ่มรายชื่อพนักงานเข้าสู่ระบบแบบอัตโนมัติ</p>
        </div>
        <div>
            <!-- ปุ่มโหลด Template -->
            <button onclick="downloadTemplate()" class="inline-flex items-center gap-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-sm font-semibold px-4 py-2.5 rounded-xl border border-emerald-200 shadow-sm transition-all duration-200">
                <i class="ph ph-file-csv text-lg"></i>
                ดาวน์โหลดไฟล์ต้นแบบ (Template)
            </button>
        </div>
    </div>

    <!-- 🌟 Premium Upload Area (Luxurious Design) 🌟 -->
    <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8 relative overflow-hidden group">
        
        <!-- Glow Effects -->
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-400/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-indigo-400/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10">
            <!-- Upload Box -->
            <div id="dropzone" class="border-2 border-dashed border-gray-300 rounded-[2rem] p-10 text-center transition-all duration-300 hover:border-brand-500 hover:bg-brand-50/50 cursor-pointer bg-gray-50/50">
                
                <input type="file" id="csvFileInput" accept=".csv" class="hidden">
                
                <div class="w-20 h-20 mx-auto bg-white rounded-2xl shadow-md flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-brand-500 to-indigo-600 flex items-center justify-center text-white shadow-inner">
                        <i class="ph ph-microsoft-excel-logo text-3xl"></i>
                    </div>
                </div>

                <h3 class="text-xl font-bold text-gray-800 mb-2">ลากไฟล์มาวางที่นี่ หรือ <span class="text-brand-600 underline decoration-2 underline-offset-4">คลิกเพื่อเลือกไฟล์</span></h3>
                <p class="text-sm text-gray-500 mb-4">รองรับเฉพาะไฟล์ .csv ที่บันทึกมาจาก Excel เท่านั้น</p>
                
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-100 text-xs font-medium text-amber-700">
                    <i class="ph-fill ph-warning-circle"></i> ลำดับคอลัมน์ต้องเรียงตาม Template อย่างเคร่งครัด
                </div>
            </div>

            <!-- File Selected Status (Hidden by default) -->
            <div id="fileInfoArea" class="hidden mt-6 p-4 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-indigo-600 shadow-sm">
                        <i class="ph-fill ph-file-csv text-2xl"></i>
                    </div>
                    <div>
                        <p id="fileName" class="text-sm font-bold text-gray-900">filename.csv</p>
                        <p id="fileSize" class="text-xs font-medium text-gray-500">0 KB</p>
                    </div>
                </div>
                <button onclick="removeFile()" class="p-2 text-rose-500 hover:bg-rose-100 rounded-xl transition-colors tooltip" title="ยกเลิกไฟล์นี้">
                    <i class="ph ph-trash text-xl"></i>
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" onclick="window.history.back()" class="px-6 py-3 rounded-xl text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors shadow-sm">
                    ยกเลิก
                </button>
                <button type="button" id="btnImport" disabled onclick="processImport()" class="inline-flex items-center gap-2 px-8 py-3 rounded-xl text-sm font-bold text-white bg-slate-800 shadow-lg shadow-slate-800/30 transition-all duration-300 hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                    <i class="ph ph-upload-simple text-lg"></i>
                    <span id="btnText">เริ่มนำเข้าข้อมูล</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Instructions / Format Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
            <div class="flex items-center gap-3 mb-4 text-brand-600">
                <i class="ph-fill ph-info text-2xl"></i>
                <h3 class="text-lg font-bold text-gray-900">รูปแบบคอลัมน์ที่รองรับ</h3>
            </div>
            <ul class="space-y-3 text-sm text-gray-600">
                <li class="flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center font-bold text-xs text-gray-500">A</span> <b>รหัสพนักงาน</b> (Employee ID) *ห้ามซ้ำ</li>
                <li class="flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center font-bold text-xs text-gray-500">B</span> <b>ชื่อ</b> (First Name) *จำเป็น</li>
                <li class="flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center font-bold text-xs text-gray-500">C</span> <b>นามสกุล</b> (Last Name) *จำเป็น</li>
                <li class="flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center font-bold text-xs text-gray-500">D</span> <b>แผนก</b> (Department Name) *ต้องตรงกับในระบบ</li>
                <li class="flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center font-bold text-xs text-gray-500">E</span> <b>เบอร์ต่อ</b> (Extension)</li>
            </ul>
        </div>

        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 shadow-md text-white">
            <div class="flex items-center gap-3 mb-4 text-emerald-400">
                <i class="ph-fill ph-lightbulb text-2xl"></i>
                <h3 class="text-lg font-bold text-white">วิธี Save ไฟล์จาก Excel</h3>
            </div>
            <ol class="list-decimal list-inside space-y-2 text-sm text-slate-300 ml-2">
                <li>เปิดไฟล์ Excel ที่มีรายชื่อพนักงาน</li>
                <li>จัดเรียงคอลัมน์ให้ตรงตามรูปแบบด้านซ้าย</li>
                <li>ลบแถวหัวตาราง (Header) ออก (ให้เหลือแต่ข้อมูล)</li>
                <li>ไปที่ <b class="text-white">File > Save As</b></li>
                <li>เลือกนามสกุลไฟล์เป็น <b class="text-emerald-400">CSV UTF-8 (Comma delimited) (*.csv)</b></li>
                <li>นำไฟล์ .csv ที่ได้มาอัปโหลดที่หน้านี้</li>
            </ol>
        </div>
    </div>

</div>

<?php // ปิดแท็กและเรียก Script ?>
        </main>
    </div>

    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
    <script>
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('csvFileInput');
        const fileInfoArea = document.getElementById('fileInfoArea');
        const fileNameDisplay = document.getElementById('fileName');
        const fileSizeDisplay = document.getElementById('fileSize');
        const btnImport = document.getElementById('btnImport');
        const btnText = document.getElementById('btnText');
        let selectedFile = null;

        // --- Drag & Drop Events ---
        dropzone.addEventListener('click', () => fileInput.click());

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('border-brand-500', 'bg-brand-50/50');
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('border-brand-500', 'bg-brand-50/50');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-brand-500', 'bg-brand-50/50');
            
            if (e.dataTransfer.files.length > 0) {
                handleFileSelect(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });

        function handleFileSelect(file) {
            // เช็คนามสกุลไฟล์
            if (file.name.split('.').pop().toLowerCase() !== 'csv') {
                showToast('กรุณาอัปโหลดไฟล์นามสกุล .csv เท่านั้น', 'error');
                removeFile();
                return;
            }

            selectedFile = file;
            fileNameDisplay.textContent = file.name;
            fileSizeDisplay.textContent = (file.size / 1024).toFixed(2) + ' KB';
            
            dropzone.classList.add('hidden');
            fileInfoArea.classList.remove('hidden');
            btnImport.disabled = false;
        }

        function removeFile() {
            selectedFile = null;
            fileInput.value = '';
            dropzone.classList.remove('hidden');
            fileInfoArea.classList.add('hidden');
            btnImport.disabled = true;
        }

        // --- สร้างไฟล์ Template ให้โหลด ---
        function downloadTemplate() {
            const csvContent = "EMP001,John,Doe,IT Support,101\nEMP002,Jane,Smith,Human Resources,102";
            const blob = new Blob(["\ufeff" + csvContent], { type: 'text/csv;charset=utf-8;' }); // \ufeff สำหรับรองรับภาษาไทยใน Excel
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", "ConnectPro_Import_Template.csv");
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // --- ส่งไฟล์ไปประมวลผล ---
        async function processImport() {
            if (!selectedFile) return;

            btnImport.disabled = true;
            btnText.innerHTML = '<div class="spinner-ring border-white w-4 h-4 inline-block align-middle mr-2"></div> กำลังนำเข้า...';

            const formData = new FormData();
            formData.append('csv_file', selectedFile);

            try {
                // ใช้ fetch ตรงๆ เพราะต้องส่งไฟล์
                const response = await fetch('<?= BASE_URL ?>api/contacts/import_csv.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();

                if (result.status === 'success') {
                    showToast(`นำเข้าสำเร็จ ${result.success_count} รายการ (ข้าม ${result.skip_count} รายการ)`, 'success');
                    setTimeout(() => {
                        window.location.href = '<?= BASE_URL ?>views/admin/contacts_manage.php';
                    }, 2000);
                } else {
                    showToast(result.message, 'error');
                    btnImport.disabled = false;
                    btnText.textContent = 'เริ่มนำเข้าข้อมูล';
                }
            } catch (error) {
                showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
                btnImport.disabled = false;
                btnText.textContent = 'เริ่มนำเข้าข้อมูล';
            }
        }
    </script>
</body>
</html>