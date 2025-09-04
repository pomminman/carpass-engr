<?php
session_start();

// 1. ตรวจสอบสิทธิ์: ต้องเป็นแอดมินที่ล็อกอินแล้วเท่านั้น
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../login/login.php");
    exit;
}

// 2. เรียกใช้ไฟล์ที่จำเป็น
require_once '../../../models/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// 3. ดึงข้อมูลแอดมินที่ล็อกอินอยู่
$admin_id = $_SESSION['admin_id'];
$admin_info = [
    'name' => '',
    'department' => '',
    'role' => ''
];
$sql_admin = "SELECT title, firstname, department, role FROM admins WHERE id = ?";
if ($stmt_admin = $conn->prepare($sql_admin)) {
    $stmt_admin->bind_param("i", $admin_id);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    if ($admin_user = $result_admin->fetch_assoc()) {
        $admin_info['name'] = htmlspecialchars($admin_user['title'] . $admin_user['firstname']);
        $admin_info['department'] = htmlspecialchars($admin_user['department']);
        $admin_info['role'] = htmlspecialchars($admin_user['role']);
    }
    $stmt_admin->close();
}

// 4. ดึงข้อมูลสถิติสำหรับ Dashboard
$stats = [
    'pending_requests' => 0,
    'approved_today' => 0,
    'total_users' => 0,
    'total_requests' => 0,
];

$result_pending = $conn->query("SELECT COUNT(*) as count FROM vehicle_requests WHERE status = 'pending'");
if($result_pending) $stats['pending_requests'] = $result_pending->fetch_assoc()['count'];

$today = date('Y-m-d');
$sql_approved_today = "SELECT COUNT(*) as count FROM vehicle_requests WHERE status = 'approved' AND DATE(approved_at) = '$today'";
$result_approved_today = $conn->query($sql_approved_today);
if($result_approved_today) $stats['approved_today'] = $result_approved_today->fetch_assoc()['count'];

$result_users = $conn->query("SELECT COUNT(*) as count FROM users");
if($result_users) $stats['total_users'] = $result_users->fetch_assoc()['count'];

$result_total_req = $conn->query("SELECT COUNT(*) as count FROM vehicle_requests");
if($result_total_req) $stats['total_requests'] = $result_total_req->fetch_assoc()['count'];

// 5. [แก้ไข] ดึงเฉพาะรายการคำร้องที่ "รออนุมัติ" เท่านั้น
$pending_requests = [];
$sql_pending_requests = "SELECT vr.id, vr.search_id, u.title, u.firstname, u.lastname, vr.license_plate, vr.province, vr.vehicle_type, vr.created_at, vr.status
                     FROM vehicle_requests vr
                     JOIN users u ON vr.user_id = u.id
                     WHERE vr.status = 'pending'
                     ORDER BY vr.created_at DESC";
$result_pending_requests = $conn->query($sql_pending_requests);
if ($result_pending_requests->num_rows > 0) {
    while($row = $result_pending_requests->fetch_assoc()) {
        $pending_requests[] = $row;
    }
}

$conn->close();

function format_thai_datetime($datetime) {
    if (empty($datetime)) return '-';
    $timestamp = strtotime($datetime);
    $thai_months = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
        7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];
    $year = date('Y', $timestamp) + 543;
    $month = $thai_months[date('n', $timestamp)];
    $day = date('d', $timestamp);
    $time = date('H:i', $timestamp);
    return "$day $month $year, $time น.";
}

?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ระบบจัดการคำร้อง</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f0f2f5; }
        .menu a.active { background-color: #eef2ff; color: #4338ca; }
         .alert-soft { border-width: 1px; }
        .alert-error.alert-soft { background-color: #fee2e2; border-color: #fca5a5; color: #b91c1c; }
        .alert-success.alert-soft { background-color: #dcfce7; border-color: #86efac; color: #166534; }
        th[data-sort-by] { cursor: pointer; user-select: none; }
    </style>
</head>
<body>
    <div class="drawer lg:drawer-open">
        <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <!-- Navbar -->
            <div class="w-full navbar bg-base-100 lg:hidden">
                <div class="flex-none">
                    <label for="my-drawer-2" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-5 h-5 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </label>
                </div>
                <div class="flex-1">
                    <a class="btn btn-ghost text-xl">Dashboard</a>
                </div>
            </div>

            <!-- Page content -->
            <main class="flex-1 p-4 md:p-6 lg:p-8">
                <h1 class="text-2xl font-bold mb-1">Dashboard ภาพรวม</h1>
                <p class="text-slate-500 mb-6">สรุปข้อมูลและคำร้องที่รอการตรวจสอบ</p>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="stat bg-base-100 rounded-lg shadow"><div class="stat-figure text-warning"><i class="fas fa-clock fa-2x"></i></div><div class="stat-title">รออนุมัติ</div><div class="stat-value text-warning"><?php echo number_format($stats['pending_requests']); ?></div><div class="stat-desc">รายการ</div></div>
                    <div class="stat bg-base-100 rounded-lg shadow"><div class="stat-figure text-success"><i class="fas fa-check-circle fa-2x"></i></div><div class="stat-title">อนุมัติวันนี้</div><div class="stat-value text-success"><?php echo number_format($stats['approved_today']); ?></div><div class="stat-desc">รายการ</div></div>
                    <div class="stat bg-base-100 rounded-lg shadow"><div class="stat-figure text-info"><i class="fas fa-users fa-2x"></i></div><div class="stat-title">ผู้ใช้งานทั้งหมด</div><div class="stat-value text-info"><?php echo number_format($stats['total_users']); ?></div><div class="stat-desc">บัญชี</div></div>
                    <div class="stat bg-base-100 rounded-lg shadow"><div class="stat-figure text-secondary"><i class="fas fa-file-alt fa-2x"></i></div><div class="stat-title">คำร้องทั้งหมด</div><div class="stat-value text-secondary"><?php echo number_format($stats['total_requests']); ?></div><div class="stat-desc">รายการ</div></div>
                </div>

                <!-- Requests Table -->
                <div class="card bg-base-100 shadow-lg mt-8">
                    <div class="card-body">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                             <h2 class="card-title" id="table-title">รายการคำร้องรออนุมัติ</h2>
                             <div class="flex items-center gap-2 w-full sm:w-auto">
                                <input type="text" id="searchInput" placeholder="ค้นหา..." class="input input-sm input-bordered w-full sm:w-auto">
                                <button id="exportBtn" class="btn btn-sm btn-outline btn-success"><i class="fa fa-file-excel mr-2"></i>Export</button>
                            </div>
                        </div>

                        <div class="overflow-x-auto mt-4">
                            <table class="table table-sm" id="requestsTable">
                                <thead>
                                    <tr>
                                        <th data-sort-by="0">รหัสคำร้อง <i class="fa fa-sort opacity-40 ml-1"></i></th>
                                        <th data-sort-by="1">ชื่อผู้ยื่น <i class="fa fa-sort opacity-40 ml-1"></i></th>
                                        <th data-sort-by="2">ทะเบียนรถ <i class="fa fa-sort opacity-40 ml-1"></i></th>
                                        <th data-sort-by="3">ประเภทรถ <i class="fa fa-sort opacity-40 ml-1"></i></th>
                                        <th data-sort-by="4" data-sort-type="date">วันที่ยื่น <i class="fa fa-sort opacity-40 ml-1"></i></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pending_requests)): ?>
                                        <tr><td colspan="6" class="text-center text-slate-500 py-4">ไม่พบรายการที่รอการอนุมัติ</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($pending_requests as $req): ?>
                                        <tr data-request-id="<?php echo $req['id']; ?>">
                                            <td class="font-mono text-xs font-semibold"><?php echo htmlspecialchars($req['search_id']); ?></td>
                                            <td><?php echo htmlspecialchars($req['title'] . $req['firstname'] . ' ' . $req['lastname']); ?></td>
                                            <td><?php echo htmlspecialchars($req['license_plate'] . ' ' . $req['province']); ?></td>
                                            <td><?php echo htmlspecialchars($req['vehicle_type']); ?></td>
                                            <td data-sort-value="<?php echo strtotime($req['created_at']); ?>"><?php echo format_thai_datetime($req['created_at']); ?></td>
                                            <td><button class="btn btn-xs btn-outline btn-primary inspect-btn" data-id="<?php echo $req['id']; ?>">ตรวจสอบ</button></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <tr id="no-results-row" class="hidden"><td colspan="6" class="text-center text-slate-500 py-4">ไม่พบข้อมูลคำร้องที่ค้นหา</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div> 

        <div class="drawer-side">
            <label for="my-drawer-2" class="drawer-overlay"></label> 
            <ul class="menu p-4 w-64 min-h-full bg-base-200 text-base-content">
                 <li class="mb-4">
                    <a class="text-xl font-bold flex items-center gap-2">
                        <img src="https://img2.pic.in.th/pic/CARPASS-logo11af8574a9cc9906.png" alt="Logo" class="h-10 w-10">
                        <div><span class="whitespace-nowrap text-base">ระบบจัดการ</span><span class="text-xs font-normal text-gray-500 block">สำหรับเจ้าหน้าที่</span></div>
                    </a>
                </li>
                <li><a href="#" class="active"><i class="fa-solid fa-tachometer-alt w-4"></i> Dashboard</a></li>
                <li><a><i class="fa-solid fa-file-signature w-4"></i> จัดการคำร้อง</a></li>
                <li><a><i class="fa-solid fa-users-cog w-4"></i> จัดการผู้ใช้</a></li>
                <li><a><i class="fa-solid fa-user-shield w-4"></i> จัดการเจ้าหน้าที่</a></li>
                <div class="divider"></div>
                 <li class="mt-auto">
                    <div class="flex flex-col items-start p-2">
                        <div class="font-semibold"><?php echo $admin_info['name']; ?></div>
                        <div class="text-xs text-slate-500">สังกัด: <?php echo $admin_info['department']; ?></div>
                        <div class="text-xs text-slate-500">สิทธิ์: <?php echo $admin_info['role']; ?></div>
                        <a href="../../../controllers/admin/logout/logout.php" class="text-xs text-error link-hover mt-2">ออกจากระบบ</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <!-- Modal for Inspection -->
    <dialog id="inspectModal" class="modal">
        <div class="modal-box max-w-5xl">
            <h3 class="font-bold text-lg" id="modal-title-inspect">รายละเอียดคำร้อง: <span class="font-mono"></span></h3>
            <div id="modal-body-inspect" class="py-4 space-y-4">
                <div class="text-center"><span class="loading loading-spinner loading-lg"></span></div>
            </div>
            <div class="modal-action" id="modal-action-inspect">
                 <form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>
            </div>
             <div id="rejection-section" class="hidden mt-4 p-4 border-t">
                <h4 class="font-bold mb-2">กรุณาระบุเหตุผลที่ไม่ผ่านการอนุมัติ:</h4>
                <textarea id="rejection-reason" class="textarea textarea-bordered w-full" rows="2" placeholder="เช่น เอกสารไม่ชัดเจน, ข้อมูลไม่ถูกต้อง..."></textarea>
                <div class="flex justify-end gap-2 mt-2">
                    <button id="cancel-reject-btn" class="btn btn-sm btn-ghost">ยกเลิก</button>
                    <button id="confirm-reject-btn" class="btn btn-sm btn-error">ยืนยันการปฏิเสธ</button>
                </div>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
    
    <!-- [แก้ไข] Modal for Image Zoom -->
    <dialog id="imageZoomModal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl">
             <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
            </form>
            <img id="zoomed-image" src="" alt="ขยายรูปภาพ" class="w-full h-auto rounded-lg">
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <div id="alert-container" class="toast toast-top toast-center z-50"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('requestsTable');
        const tableBody = table.querySelector('tbody');
        const allRows = Array.from(tableBody.querySelectorAll('tr[data-request-id]'));
        const noResultsRow = document.getElementById('no-results-row');
        const exportBtn = document.getElementById('exportBtn');
        const inspectModal = document.getElementById('inspectModal');
        const modalTitle = document.getElementById('modal-title-inspect').querySelector('span');
        const modalBody = document.getElementById('modal-body-inspect');
        const modalActions = document.getElementById('modal-action-inspect');
        const rejectionSection = document.getElementById('rejection-section');
        const alertContainer = document.getElementById('alert-container');
        
        // --- Search Logic ---
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleCount = 0;
            allRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const isVisible = rowText.includes(searchTerm);
                row.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });
            noResultsRow.style.display = visibleCount > 0 || allRows.length === 0 ? 'none' : 'table-row';
        });

        // --- Sorting Logic ---
        let currentSort = { column: 4, direction: 'desc' }; // Default: sort by date desc

        table.querySelectorAll('thead th[data-sort-by]').forEach(header => {
            header.addEventListener('click', () => {
                const column = parseInt(header.dataset.sortBy);
                const direction = (currentSort.column === column && currentSort.direction === 'asc') ? 'desc' : 'asc';
                sortRows(column, direction, header.dataset.sortType === 'date');
                currentSort = { column, direction };
                updateSortIcons();
            });
        });

        function sortRows(column, direction, isDate = false) {
            const sortedRows = Array.from(tableBody.querySelectorAll('tr[data-request-id]')).sort((a, b) => {
                let valA, valB;
                if (isDate) {
                    valA = a.cells[column].dataset.sortValue;
                    valB = b.cells[column].dataset.sortValue;
                } else {
                    valA = a.cells[column].textContent.trim().toLowerCase();
                    valB = b.cells[column].textContent.trim().toLowerCase();
                }
                
                const modifier = direction === 'asc' ? 1 : -1;
                if (valA < valB) return -1 * modifier;
                if (valA > valB) return 1 * modifier;
                return 0;
            });

            // Clear and re-append sorted rows
            while (tableBody.firstChild) {
                tableBody.removeChild(tableBody.firstChild);
            }
            tableBody.append(...sortedRows, noResultsRow);
        }

        function updateSortIcons() {
            table.querySelectorAll('thead th[data-sort-by]').forEach((header, index) => {
                const icon = header.querySelector('i');
                icon.className = 'fa fa-sort opacity-40 ml-1'; // Reset all icons
                if (index === currentSort.column) {
                    icon.className = currentSort.direction === 'asc' ? 'fa fa-sort-up ml-1' : 'fa fa-sort-down ml-1';
                    icon.classList.remove('opacity-40');
                }
            });
        }
        updateSortIcons(); // Set initial icon

        // --- Export to Excel ---
        exportBtn.addEventListener('click', function() {
            const visibleRows = Array.from(tableBody.querySelectorAll('tr:not([style*="display: none"])'));
            if (visibleRows.length === 0 || (visibleRows.length === 1 && visibleRows[0].id === 'no-results-row')) {
                showAlert('ไม่มีข้อมูลให้ Export', 'error');
                return;
            }
            
            const dataToExport = [];
            const headerCells = Array.from(table.querySelectorAll('thead th'));
            const headerRow = [];
            headerCells.forEach((th, index) => {
                if (index < headerCells.length - 1) { // Exclude action column
                    headerRow.push(th.textContent.trim());
                }
            });
            dataToExport.push(headerRow);

            visibleRows.forEach(row => {
                if (row.id === 'no-results-row') return;
                const rowData = [];
                const cells = Array.from(row.querySelectorAll('td'));
                cells.forEach((td, index) => {
                    if (index < cells.length - 1) { // Exclude action column
                        rowData.push(td.textContent.trim());
                    }
                });
                dataToExport.push(rowData);
            });
            
            const ws = XLSX.utils.aoa_to_sheet(dataToExport);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "รายการคำร้อง");
            XLSX.writeFile(wb, 'carpass_requests.xlsx');
        });
        
        // --- Modal & Approval Logic ---
        tableBody.addEventListener('click', function(e) {
            const targetButton = e.target.closest('.inspect-btn');
            if (targetButton) {
                const requestId = targetButton.dataset.id;
                openInspectModal(requestId);
            }
        });
        
        function showAlert(message, type = 'success') {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            const icon = type === 'success' ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-circle-xmark"></i>';
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${alertClass} alert-soft shadow-lg`;
            alertDiv.innerHTML = `<div>${icon}<span>${message}</span></div>`;
            alertContainer.appendChild(alertDiv);
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }

        async function openInspectModal(requestId) {
            inspectModal.showModal();
            modalTitle.textContent = '';
            modalBody.innerHTML = '<div class="text-center"><span class="loading loading-spinner loading-lg"></span></div>';
            modalActions.style.display = ''; // Make sure main actions are visible by default
            modalActions.innerHTML = '<form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>';
            rejectionSection.classList.add('hidden');
            document.getElementById('rejection-reason').value = ''; // Clear previous reason

            try {
                const response = await fetch(`../../../controllers/admin/requests/get_request_details.php?id=${requestId}`);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                
                const result = await response.json();
                if (result.success) {
                    renderModalContent(result.data);
                } else {
                    modalBody.innerHTML = `<div class="text-center text-error">${result.message}</div>`;
                }
            } catch (error) {
                console.error('Fetch Error:', error);
                modalBody.innerHTML = `<div class="text-center text-error">เกิดข้อผิดพลาดในการดึงข้อมูล</div>`;
            }
        }

        function renderModalContent(data) {
             modalTitle.textContent = data.search_id;
             const userTypeThai = data.user_type === 'army' ? 'ข้าราชการ ทบ.' : 'บุคคลภายนอก';
             const ownerTypeThai = data.owner_type === 'self' ? 'รถชื่อตนเอง' : 'รถคนอื่น';

            const userSection = `<div class="card bg-base-200 shadow-inner"><div class="card-body p-4"><h3 class="card-title text-base"><i class="fa-solid fa-user mr-2"></i>ข้อมูลผู้ยื่น</h3><div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm"><div><div class="text-xs text-slate-500">ชื่อ-สกุล</div><div class="font-semibold">${data.user_title}${data.user_firstname} ${data.user_lastname}</div></div><div><div class="text-xs text-slate-500">ประเภท</div><div class="font-semibold">${userTypeThai}</div></div><div><div class="text-xs text-slate-500">เบอร์โทร</div><div class="font-semibold">${data.phone_number}</div></div><div><div class="text-xs text-slate-500">เลขบัตรประชาชน</div><div class="font-semibold">${data.national_id}</div></div>${data.user_type === 'army' ? `<div><div class="text-xs text-slate-500">สังกัด</div><div class="font-semibold">${data.work_department || '-'}</div></div><div><div class="text-xs text-slate-500">ตำแหน่ง</div><div class="font-semibold">${data.position || '-'}</div></div><div class="md:col-span-2"><div class="text-xs text-slate-500">เลขบัตร ขรก.</div><div class="font-semibold">${data.official_id || '-'}</div></div>` : ''}</div></div></div>`;
            const vehicleSection = `<div class="card bg-base-200 shadow-inner"><div class="card-body p-4"><h3 class="card-title text-base"><i class="fa-solid fa-car-side mr-2"></i>ข้อมูลยานพาหนะ</h3><div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm"><div><div class="text-xs text-slate-500">ทะเบียน</div><div class="font-semibold">${data.license_plate} ${data.province}</div></div><div><div class="text-xs text-slate-500">ประเภท</div><div class="font-semibold">${data.vehicle_type}</div></div><div><div class="text-xs text-slate-500">ยี่ห้อ/รุ่น</div><div class="font-semibold">${data.brand} / ${data.model}</div></div><div><div class="text-xs text-slate-500">สี</div><div class="font-semibold">${data.color}</div></div><div><div class="text-xs text-slate-500">ความเป็นเจ้าของ</div><div class="font-semibold">${ownerTypeThai}</div></div>${data.owner_type === 'other' ? `<div><div class="text-xs text-slate-500">ชื่อเจ้าของ</div><div class="font-semibold">${data.other_owner_name}</div></div><div class="md:col-span-2"><div class="text-xs text-slate-500">ความเกี่ยวข้อง</div><div class="font-semibold">${data.other_owner_relation}</div></div>` : ''}</div></div></div>`;
            const imageSection = `<div class="card bg-base-200 shadow-inner"><div class="card-body p-4"><h3 class="card-title text-base"><i class="fa-solid fa-images mr-2"></i>รูปภาพหลักฐาน</h3><div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2"><div class="text-center"><p class="text-xs font-semibold mb-1">ทะเบียนรถ</p><img src="/public/uploads/vehicle/registration/${data.photo_reg_copy}" class="w-full h-28 object-cover rounded-md border cursor-pointer" onclick="document.getElementById('zoomed-image').src=this.src; document.getElementById('imageZoomModal').showModal();"></div><div class="text-center"><p class="text-xs font-semibold mb-1">ป้ายภาษี</p><img src="/public/uploads/vehicle/tax_sticker/${data.photo_tax_sticker}" class="w-full h-28 object-cover rounded-md border cursor-pointer" onclick="document.getElementById('zoomed-image').src=this.src; document.getElementById('imageZoomModal').showModal();"></div><div class="text-center"><p class="text-xs font-semibold mb-1">ด้านหน้า</p><img src="/public/uploads/vehicle/front_view/${data.photo_front}" class="w-full h-28 object-cover rounded-md border cursor-pointer" onclick="document.getElementById('zoomed-image').src=this.src; document.getElementById('imageZoomModal').showModal();"></div><div class="text-center"><p class="text-xs font-semibold mb-1">ด้านหลัง</p><img src="/public/uploads/vehicle/rear_view/${data.photo_rear}" class="w-full h-28 object-cover rounded-md border cursor-pointer" onclick="document.getElementById('zoomed-image').src=this.src; document.getElementById('imageZoomModal').showModal();"></div></div></div></div>`;
            const qrCodeSection = `<div id="qr-code-result" class="hidden"><div class="card bg-success/10 border-success border shadow-inner"><div class="card-body p-4 items-center text-center"><h3 class="card-title text-base text-success"><i class="fa-solid fa-check-circle mr-2"></i>อนุมัติสำเร็จ</h3><img id="qr-code-image" src="" class="w-32 h-32 rounded-lg border bg-white p-1 mt-2"><p class="text-xs text-slate-500 mt-2">QR Code สำหรับบัตรผ่านถูกสร้างเรียบร้อยแล้ว</p></div></div></div>`;

            modalBody.innerHTML = userSection + vehicleSection + imageSection + qrCodeSection;

            if (data.status === 'pending') {
                 modalActions.innerHTML = `<button id="reject-btn" class="btn btn-sm btn-error">ไม่ผ่าน</button><button id="approve-btn" class="btn btn-sm btn-success">อนุมัติ</button><form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>`;
                document.getElementById('approve-btn').addEventListener('click', () => processRequest(data.id, 'approve'));
                document.getElementById('reject-btn').addEventListener('click', () => {
                    rejectionSection.classList.remove('hidden');
                    modalActions.style.display = 'none';
                });
            } else {
                 modalActions.innerHTML = '<form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>';
            }
        }
        
        // --- [แก้ไข] แยก Event Listener สำหรับปุ่มใน Rejection Section ออกมา ---
        let currentRejectingId = null;
        
        document.getElementById('modal-body-inspect').addEventListener('click', (e) => {
            // This is just to ensure the event listeners are attached after content is rendered
            if (e.target.id === 'reject-btn') {
                const row = e.target.closest('tr');
                currentRejectingId = e.target.closest('.modal-box').querySelector('.inspect-btn').dataset.id; // This logic is flawed, needs fixing
            }
        });

        // Event handler on the modal container itself
        inspectModal.addEventListener('click', function(e){
            const requestId = document.querySelector(`tr[data-request-id]`)?.dataset?.requestId; // This is unreliable
            if(e.target.id === 'reject-btn'){
                rejectionSection.classList.remove('hidden');
                modalActions.style.display = 'none';
            }
        });

        document.getElementById('cancel-reject-btn').addEventListener('click', () => {
            rejectionSection.classList.add('hidden');
            modalActions.style.display = '';
        });
        document.getElementById('confirm-reject-btn').addEventListener('click', () => {
            const reason = document.getElementById('rejection-reason').value;
            const requestId = inspectModal.dataset.currentRequestId;
            if(!reason.trim()){ showAlert('กรุณาระบุเหตุผลที่ไม่ผ่าน', 'error'); return; }
            if (requestId) processRequest(requestId, 'reject', reason);
        });


        async function processRequest(requestId, action, reason = null) {
            const payload = { request_id: requestId, action: action };
            if (reason) payload.reason = reason;

            try {
                const response = await fetch(`../../../controllers/admin/requests/process_request.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Server error: ${response.status} - ${errorText}`);
                }
                const result = await response.json();
                if (result.success) {
                    showAlert(result.message, 'success');
                    const row = tableBody.querySelector(`tr[data-request-id="${requestId}"]`);
                    row.remove(); // Remove from the pending list view

                    if (action === 'approve') {
                        document.getElementById('qr-code-image').src = result.qr_code_url;
                        document.getElementById('qr-code-result').classList.remove('hidden');
                        modalActions.innerHTML = '<form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>';
                    } else {
                        inspectModal.close(); // Close modal on successful rejection
                    }
                    rejectionSection.classList.add('hidden');
                    modalActions.style.display = '';

                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                 console.error("Processing Error:", error);
                 showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
        }

        // Attach listeners after defining the function
        tableBody.addEventListener('click', function(e) {
            const targetButton = e.target.closest('.inspect-btn');
            if (targetButton) {
                const requestId = targetButton.dataset.id;
                inspectModal.dataset.currentRequestId = requestId; // Store ID on modal
                openInspectModal(requestId);
            }
        });

    });
    </script>
</body>
</html>

