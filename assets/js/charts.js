/* ==========================================
ConnectPro - Dashboard Charts & Stats Logic
Path: assets/js/charts.js
Description: Fetch dashboard stats, update KPI cards, and render Chart.js Donut Chart
========================================== */

// เก็บ Instance ของ Chart ไว้เพื่อใช้ Destroy เวลาต้องการ Reload กราฟใหม่
let departmentChartInstance = null;

// เริ่มทำงานเมื่อหน้าเว็บโหลดเสร็จ (และตรวจเช็คว่ามี Element กราฟอยู่บนหน้าหรือไม่)
document.addEventListener('DOMContentLoaded', () => {
    const chartCanvas = document.getElementById('departmentChart');
    if (chartCanvas) {
        loadDashboardStats();
    }
});

/**
 * ฟังก์ชันดึงข้อมูลสถิติจาก API 
 */
async function loadDashboardStats() {
    try {
        // ใช้ฟังก์ชัน apiCall จาก app.js
        const response = await apiCall('api/dashboard/stats.php');
        
        if (response.status === 'success') {
            const data = response.data;
            
            // 1. อัปเดตตัวเลข KPI ด้านบน
            updateKpiCards(data.kpi);
            
            // 2. วาดกราฟโดนัท (Department Overview)
            renderDepartmentChart(data.chart_data);
            
            // 3. วาดรายการสรุปแผนกด้านข้างกราฟ (Custom Legend / List)
            renderDepartmentList(data.chart_data);
        }
    } catch (error) {
        console.error("Dashboard Stats Error:", error);
        showToast('ไม่สามารถโหลดข้อมูลสถิติได้', 'error');
    }
}

/**
 * ฟังก์ชันนำตัวเลขไปหยอดใส่กล่อง KPI ต่างๆ 
 * โดยเช็คก่อนว่ามี ID นั้นๆ บนหน้าเว็บหรือไม่ (เพื่อป้องกัน Error)
 */
function updateKpiCards(kpi) {
    // ฟังก์ชันย่อยสำหรับอัปเดตข้อความ
    const setElementText = (id, text) => {
        const el = document.getElementById(id);
        if (el) el.innerText = text;
    };

    // ใส่ข้อมูลลงไปใน Element ทั่วไป
    setElementText('kpiTotalContacts', kpi.total_contacts);
    setElementText('kpiNewThisMonth', `+${kpi.new_this_month} เดือนนี้`);
    
    // ใส่ข้อมูล Online
    setElementText('kpiOnlineActive', kpi.online_active);
    setElementText('kpiOnlinePercent', `${kpi.online_percentage}%`);
    
    // ใส่ข้อมูล Offline (ที่เราเพิ่งเพิ่มไป)
    setElementText('kpiOfflineActive', kpi.offline_active);
    setElementText('kpiOfflinePercent', `${kpi.offline_percentage}%`);
    
    // ใส่ข้อมูลแผนกและการอัปเดต
    setElementText('kpiTotalDepartments', kpi.total_departments);
    setElementText('kpiRecentlyUpdated', `อัปเดตวันนี้ ${kpi.recently_updated} รายการ`);
}

/**
 * ฟังก์ชันวาดกราฟ Donut ด้วย Chart.js
 */
function renderDepartmentChart(chartData) {
    const ctx = document.getElementById('departmentChart');
    if (!ctx) return;

    // ถ้าไม่มีข้อมูลเลย ให้แสดงกราฟสีเทา
    if (!chartData || chartData.length === 0) {
        chartData = [{ label: 'ไม่มีข้อมูล', value: 1, color: '#e2e8f0', percentage: 100 }];
    }

    // เตรียม Data ให้ Chart.js
    const labels = chartData.map(item => item.label);
    const dataValues = chartData.map(item => item.value);
    const backgroundColors = chartData.map(item => item.color);

    // ลบกราฟเก่าทิ้งก่อนวาดใหม่ (กรณีมีการกด Refresh ข้อมูล)
    if (departmentChartInstance) {
        departmentChartInstance.destroy();
    }

    // วาดกราฟใหม่
    departmentChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: dataValues,
                backgroundColor: backgroundColors,
                borderWidth: 0, // ปิดเส้นขอบเพื่อความมินิมอล
                hoverOffset: 6, // เอฟเฟกต์เด้งออกมาตอนเอาเมาส์ชี้
                borderRadius: 4 // ขอบมนด้านใน/นอก ของกราฟ (ต้องการ Chart.js v3+)
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%', // ความบางของวงแหวน (75% คือวงแหวนบางๆ แบบโมเดิร์น)
            plugins: {
                // ปิด Legend พื้นฐานของ Chart.js เพราะเราจะเขียน HTML แปะแยกเองให้สวยกว่า
                legend: {
                    display: false 
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)', // bg-slate-900 โปร่งแสง
                    titleFont: {
                        family: "'Inter', 'Prompt', sans-serif",
                        size: 13
                    },
                    bodyFont: {
                        family: "'Inter', 'Prompt', sans-serif",
                        size: 14,
                        weight: 'bold'
                    },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        // ปรับแต่งข้อความใน Tooltip (กล่องดำๆ เวลาเอาเมาส์ชี้)
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.parsed || 0;
                            let originalData = chartData[context.dataIndex];
                            return ` ${label} : ${value} คน (${originalData.percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    });
}

/**
 * ฟังก์ชันสร้าง Legend (คำอธิบายกราฟ) ในรูปแบบ HTML 
 * เพื่อความยืดหยุ่นในการจัด Layout ให้สวยงามตาม Design
 */
function renderDepartmentList(chartData) {
    const listContainer = document.getElementById('departmentLegendList');
    if (!listContainer) return;

    if (!chartData || chartData.length === 0) {
        listContainer.innerHTML = '<p class="text-sm text-gray-400">ไม่มีข้อมูลแผนก</p>';
        return;
    }

    let html = '';
    
    // โชว์แค่ 5 อันดับแรก (ถ้ามีเยอะเกินไปกราฟจะดูรก)
    const displayData = chartData.slice(0, 5);

    displayData.forEach(item => {
        html += `
            <div class="flex items-center justify-between p-2 hover:bg-slate-50 rounded-lg transition-colors">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full shadow-sm" style="background-color: ${item.color}"></span>
                    <span class="text-sm font-medium text-gray-700">${item.label}</span>
                </div>
                <div class="text-right">
                    <div class="text-sm font-bold text-gray-900">${item.value}</div>
                    <div class="text-xs text-gray-400">${item.percentage}%</div>
                </div>
            </div>
        `;
    });

    // ถ้ามีมากกว่า 5 แผนก ให้บอกว่ามีอื่นๆ อีก
    if (chartData.length > 5) {
        const othersCount = chartData.slice(5).reduce((sum, item) => sum + item.value, 0);
        html += `
            <div class="flex items-center justify-between p-2 mt-1 border-t border-gray-100">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-gray-200"></span>
                    <span class="text-sm font-medium text-gray-500">แผนกอื่นๆ</span>
                </div>
                <div class="text-right text-sm font-bold text-gray-500">${othersCount}</div>
            </div>
        `;
    }

    listContainer.innerHTML = html;
}