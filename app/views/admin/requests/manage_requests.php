<?php
session_start();
date_default_timezone_set('Asia/Bangkok');

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
    'lastname' => '',
    'department' => '',
    'role' => '',
    'view_permission_text' => ''
];
$sql_admin = "SELECT title, firstname, lastname, department, role, view_permission FROM admins WHERE id = ?";
if ($stmt_admin = $conn->prepare($sql_admin)) {
    $stmt_admin->bind_param("i", $admin_id);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    if ($admin_user = $result_admin->fetch_assoc()) {
        $admin_info['name'] = htmlspecialchars($admin_user['title'] . $admin_user['firstname']);
        $admin_info['lastname'] = htmlspecialchars($admin_user['lastname']);
        $admin_info['department'] = htmlspecialchars($admin_user['department']);
        $admin_info['role'] = htmlspecialchars($admin_user['role']);
        $admin_info['view_permission_text'] = $admin_user['view_permission'] == 1 ? 'ดูได้ทุกสังกัด' : 'เฉพาะสังกัดตนเอง';
    }
    $stmt_admin->close();
}

// 4. ดึงข้อมูลคำร้องทั้งหมด
$all_requests = [];
$sql_all_requests = "SELECT vr.id, vr.search_id, u.title, u.firstname, u.lastname, u.work_department, vr.license_plate, vr.province, vr.vehicle_type, vr.created_at, vr.status, vr.card_pickup_date
                     FROM vehicle_requests vr
                     JOIN users u ON vr.user_id = u.id
                     ORDER BY 
                        CASE vr.status
                            WHEN 'pending' THEN 1
                            WHEN 'rejected' THEN 2
                            WHEN 'approved' THEN 3
                            ELSE 4
                        END, vr.created_at DESC";
$result_all_requests = $conn->query($sql_all_requests);
if ($result_all_requests->num_rows > 0) {
    while($row = $result_all_requests->fetch_assoc()) {
        $all_requests[] = $row;
    }
}

$conn->close();

function format_thai_datetime($datetime) {
    if (empty($datetime)) return '-';
    $timestamp = strtotime($datetime);
    $thai_months = [1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'];
    return date('d', $timestamp) . ' ' . $thai_months[date('n', $timestamp)] . ' ' . (date('Y', $timestamp) + 543) . ', ' . date('H:i', $timestamp) . ' น.';
}

function format_thai_date($date) {
    if (empty($date) || $date === '0000-00-00') return '-';
    $timestamp = strtotime($date);
    $thai_months = [1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'];
    return date('d', $timestamp) . ' ' . $thai_months[date('n', $timestamp)] . ' ' . (date('Y', $timestamp) + 543);
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำร้อง - ระบบจัดการคำร้อง</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f0f2f5; }
        .menu a.active { background-color: #eef2ff; color: #4338ca; }
        .alert-soft { border-width: 1px; }
        .alert-error.alert-soft { background-color: #fee2e2; border-color: #fca5a5; color: #b91c1c; }
        .alert-success.alert-soft { background-color: #dcfce7; border-color: #86efac; color: #166534; }
        th[data-sort-by] { cursor: pointer; user-select: none; }
        th[data-sort-by] .fa-sort, th[data-sort-by] .fa-sort-up, th[data-sort-by] .fa-sort-down {
            color: #9ca3af;
            margin-left: 0.5rem;
            transition: color 0.2s ease-in-out;
        }
        th[data-sort-by]:hover .fa-sort { color: #1f2937; }
        th[data-sort-by].sort-asc .fa-sort-up, th[data-sort-by].sort-desc .fa-sort-down { color: #2563eb; }
        #zoomed-image-container { display: inline-block; position: relative; }
        #zoomed-image { max-height: 85vh; width: auto; margin: auto; object-fit: contain; }
    </style>
</head>
<body>
    <div class="flex flex-col min-h-screen">
        <!-- Navbar -->
        <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
            <div class="navbar-start">
                <div class="dropdown">
                    <label tabindex="0" class="btn btn-ghost lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" /></svg>
                    </label>
                    <ul tabindex="0" id="mobile-menu" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                        <li><a href="../home/home.php"><i class="fa-solid fa-tachometer-alt w-4"></i> Dashboard</a></li>
                        <li><a href="../requests/manage_requests.php"><i class="fa-solid fa-file-signature w-4"></i> จัดการคำร้อง</a></li>
                        <li><a href="../users/manage_users.php"><i class="fa-solid fa-users-cog w-4"></i> จัดการผู้ใช้</a></li>
                        <li><a href="../admins/manage_admins.php"><i class="fa-solid fa-user-shield w-4"></i> จัดการเจ้าหน้าที่</a></li>
                    </ul>
                </div>
                 <div class="flex items-center gap-2 ml-2">
                    <img src="https://img2.pic.in.th/pic/CARPASS-logo11af8574a9cc9906.png" alt="Logo" class="h-12 w-12">
                    <div>
                        <div class="font-bold text-sm sm:text-base whitespace-nowrap">ระบบจัดการ</div>
                        <div class="text-xs font-normal text-gray-500 whitespace-nowrap">สำหรับเจ้าหน้าที่</div>
                    </div>
                </div>
            </div>
            <div class="navbar-center hidden lg:flex">
                <ul class="menu menu-horizontal px-1" id="desktop-menu">
                    <li><a href="../home/home.php"><i class="fa-solid fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="../requests/manage_requests.php"><i class="fa-solid fa-file-signature"></i> จัดการคำร้อง</a></li>
                    <li><a href="../users/manage_users.php"><i class="fa-solid fa-users-cog"></i> จัดการผู้ใช้</a></li>
                    <li><a href="../admins/manage_admins.php"><i class="fa-solid fa-user-shield"></i> จัดการเจ้าหน้าที่</a></li>
                </ul>
            </div>
            <div class="navbar-end">
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                        <div class="w-10 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                             <i class="fa-solid fa-user text-xl text-primary flex items-center justify-center h-full"></i>
                        </div>
                    </label>
                    <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-64 space-y-1">
                         <li class="p-2 text-center">
                            <div class="font-semibold"><?php echo $admin_info['name'] . ' ' . $admin_info['lastname']; ?></div>
                            <div class="text-xs text-slate-500">สังกัด: <?php echo $admin_info['department']; ?></div>
                            <div class="text-xs text-slate-500">ระดับสิทธิ์: <?php echo ucfirst($admin_info['role']); ?></div>
                            <div class="text-xs text-slate-500">สิทธิ์เข้าถึง: <?php echo $admin_info['view_permission_text']; ?></div>
                         </li>
                         <div class="divider my-0"></div>
                         <li>
                            <a href="../admins/manage_admins.php">
                                <i class="fa-solid fa-user-pen"></i> แก้ไขข้อมูลส่วนตัว
                            </a>
                        </li>
                         <li>
                            <a href="../../../controllers/admin/logout/logout.php" class="text-error">
                                <i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Page content -->
        <main class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
            <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-file-signature text-primary"></i> จัดการคำร้องทั้งหมด</h1>
            <p class="text-slate-500 mb-6">ตรวจสอบและจัดการคำร้องขอบัตรผ่านทั้งหมดในระบบ</p>
            
            <div class="card bg-base-100 shadow-lg">
                <div class="card-body">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="tabs tabs-boxed">
                            <a class="tab tab-sm sm:tab-md status-filter active" data-filter="all">ทั้งหมด</a>
                            <a class="tab tab-sm sm:tab-md status-filter" data-filter="pending">รออนุมัติ</a>
                            <a class="tab tab-sm sm:tab-md status-filter" data-filter="approved">อนุมัติแล้ว</a>
                            <a class="tab tab-sm sm:tab-md status-filter" data-filter="rejected">ไม่ผ่าน</a>
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                           <input type="text" id="searchInput" placeholder="ค้นหา..." class="input input-sm input-bordered w-full sm:w-auto">
                        </div>
                    </div>

                    <div class="overflow-x-auto mt-4">
                        <table class="table table-sm" id="requestsTable">
                             <thead class="bg-slate-50">
                                <tr>
                                    <th data-sort-by="search_id">รหัสคำร้อง<i class="fa-solid fa-sort"></i></th>
                                    <th data-sort-by="name">ชื่อผู้ยื่น<i class="fa-solid fa-sort"></i></th>
                                    <th data-sort-by="department">สังกัด<i class="fa-solid fa-sort"></i></th>
                                    <th data-sort-by="license">ทะเบียนรถ<i class="fa-solid fa-sort"></i></th>
                                    <th data-sort-by="type">ประเภทรถ<i class="fa-solid fa-sort"></i></th>
                                    <th data-sort-by="date">วันที่ยื่น<i class="fa-solid fa-sort"></i></th>
                                    <th data-sort-by="pickup_date">วันที่นัดรับบัตร<i class="fa-solid fa-sort"></i></th>
                                    <th data-sort-by="status">สถานะ<i class="fa-solid fa-sort"></i></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_requests)): ?>
                                    <tr><td colspan="9" class="text-center text-slate-500 py-4">ไม่พบข้อมูลคำร้องในระบบ</td></tr>
                                <?php else: ?>
                                    <?php foreach ($all_requests as $req): ?>
                                    <tr class="hover:bg-slate-50" data-request-id="<?php echo $req['id']; ?>" data-status="<?php echo $req['status']; ?>">
                                        <td class="font-semibold whitespace-nowrap"><?php echo htmlspecialchars($req['search_id']); ?></td>
                                        <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['title'] . $req['firstname'] . ' ' . $req['lastname']); ?></td>
                                        <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['work_department'] ?? '-'); ?></td>
                                        <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['license_plate'] . ' ' . $req['province']); ?></td>
                                        <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['vehicle_type']); ?></td>
                                        <td class="whitespace-nowrap"><?php echo format_thai_datetime($req['created_at']); ?></td>
                                        <td class="whitespace-nowrap font-semibold text-info"><?php echo format_thai_date($req['card_pickup_date']); ?></td>
                                        <td>
                                            <?php
                                                $status_badge = '';
                                                switch ($req['status']) {
                                                    case 'pending': $status_badge = '<div class="badge badge-warning">รออนุมัติ</div>'; break;
                                                    case 'approved': $status_badge = '<div class="badge badge-success">อนุมัติแล้ว</div>'; break;
                                                    case 'rejected': $status_badge = '<div class="badge badge-error">ไม่ผ่าน</div>'; break;
                                                }
                                                echo $status_badge;
                                            ?>
                                        </td>
                                        <td class="whitespace-nowrap">
                                            <button class="btn btn-xs btn-primary inspect-btn" data-id="<?php echo $req['id']; ?>">
                                                <span><i class="fa-solid fa-search mr-1"></i>ตรวจสอบ</span>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <tr id="no-results-row" class="hidden"><td colspan="9" class="text-center text-slate-500 py-4">ไม่พบข้อมูลคำร้องที่ค้นหา</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <!-- Footer -->
    <footer class="fixed bottom-0 left-0 right-0 bg-base-200 text-base-content shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] p-1 text-center z-40">
        <p class="text-[10px] sm:text-xs whitespace-nowrap">Developed by ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ/กยข.กช.)</p>
    </footer>

    <!-- Modals -->
    <dialog id="inspectModal" class="modal">
        <div class="modal-box max-w-5xl">
            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2 text-xl bg-base-200/50 hover:bg-base-200/80">✕</button>
            </form>
            <h3 class="font-bold text-lg" id="modal-title-inspect">รายละเอียดคำร้อง: <span></span></h3>
            <div id="modal-body-inspect" class="py-4 space-y-4">
                <div class="text-center"><span class="loading loading-spinner loading-lg"></span></div>
            </div>
            <div class="modal-action" id="modal-action-inspect">
                 <form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>
            </div>
             <div id="rejection-section" class="hidden mt-4 p-4 border-t">
                <h4 class="font-bold mb-2">กรุณาระบุเหตุผลที่ไม่ผ่านการอนุมัติ:</h4>
                <div class="form-control">
                    <textarea id="rejection-reason" class="textarea textarea-bordered w-full" rows="2" placeholder="เช่น เอกสารไม่ชัดเจน, ข้อมูลไม่ถูกต้อง..."></textarea>
                    <p id="rejection-error-msg" class="text-error text-xs mt-1 hidden">กรุณาระบุเหตุผล</p>
                </div>
                <div class="flex justify-end gap-2 mt-2">
                    <button id="cancel-reject-btn" class="btn btn-sm btn-ghost">ยกเลิก</button>
                    <button id="confirm-reject-btn" class="btn btn-sm btn-error">ยืนยันการปฏิเสธ</button>
                </div>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
    
    <dialog id="imageZoomModal" class="modal">
        <div class="modal-box w-11/12 max-w-5xl p-0 bg-transparent shadow-none flex justify-center items-center">
            <div id="zoomed-image-container">
                <img id="zoomed-image" src="" alt="ขยายรูปภาพ" class="rounded-lg">
                <form method="dialog">
                    <button class="btn btn-circle absolute right-2 top-2 bg-black/25 hover:bg-black/50 text-white border-none text-xl z-10">✕</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
    
    <dialog id="confirmActionModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg" id="confirm-title"></h3>
            <p class="py-4" id="confirm-message"></p>
            <div class="modal-action">
                <button id="confirm-cancel-btn" class="btn btn-sm">ยกเลิก</button>
                <button id="confirm-ok-btn" class="btn btn-sm"></button>
            </div>
        </div>
    </dialog>

    <div id="alert-container" class="toast toast-top toast-center z-50"></div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Set Active Menu ---
        const currentPage = window.location.pathname;
        const menuLinks = document.querySelectorAll('#desktop-menu a, #mobile-menu a');
        menuLinks.forEach(link => link.classList.remove('active'));
        const currentPageFilename = currentPage.substring(currentPage.lastIndexOf('/') + 1);
        const activeLinks = Array.from(menuLinks).filter(link => {
            const linkHref = link.getAttribute('href');
            return linkHref && linkHref.endsWith(currentPageFilename);
        });
        activeLinks.forEach(link => link.classList.add('active'));

        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('requestsTable');
        const tableBody = table.querySelector('tbody');
        const allRows = Array.from(tableBody.querySelectorAll('tr[data-request-id]'));
        const noResultsRow = document.getElementById('no-results-row');
        const statusFilters = document.querySelectorAll('.status-filter');
        let currentFilter = 'all';

        function filterAndSearch() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;
            allRows.forEach(row => {
                const statusMatch = currentFilter === 'all' || row.dataset.status === currentFilter;
                const searchMatch = row.textContent.toLowerCase().includes(searchTerm);
                const isVisible = statusMatch && searchMatch;
                row.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });
            noResultsRow.style.display = visibleCount > 0 ? 'none' : 'table-row';
        }

        searchInput.addEventListener('input', filterAndSearch);

        statusFilters.forEach(filter => {
            filter.addEventListener('click', function(e) {
                e.preventDefault();
                statusFilters.forEach(f => f.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.dataset.filter;
                filterAndSearch();
            });
        });

        // --- Table Sorting ---
        const headers = table.querySelectorAll('th[data-sort-by]');
        headers.forEach(header => {
            header.addEventListener('click', () => {
                const isAsc = header.classList.contains('sort-asc');
                const direction = isAsc ? -1 : 1;
                const columnIndex = Array.from(header.parentNode.children).indexOf(header);

                headers.forEach(h => {
                    h.classList.remove('sort-asc', 'sort-desc');
                    h.querySelector('i').className = 'fa-solid fa-sort';
                });

                header.classList.toggle('sort-asc', !isAsc);
                header.classList.toggle('sort-desc', isAsc);
                header.querySelector('i').className = !isAsc ? 'fa-solid fa-sort-up' : 'fa-solid fa-sort-down';

                const rows = Array.from(tableBody.querySelectorAll('tr[data-request-id]'));
                rows.sort((rowA, rowB) => {
                    let valA = rowA.children[columnIndex].textContent.trim();
                    let valB = rowB.children[columnIndex].textContent.trim();
                    
                    if (header.dataset.sortBy === 'date' || header.dataset.sortBy === 'pickup_date') {
                        const thaiMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                        const parseThaiDate = (str) => {
                            if (str === '-') return 0;
                            const parts = str.split(/[\s,:]+/);
                            if (parts.length < 3) return 0;
                            const day = parseInt(parts[0], 10);
                            const monthIndex = thaiMonths.indexOf(parts[1]);
                            const yearBE = parseInt(parts[2], 10);
                            const hours = parts.length > 3 ? parseInt(parts[3], 10) : 0;
                            const minutes = parts.length > 4 ? parseInt(parts[4], 10) : 0;
                            return new Date(yearBE - 543, monthIndex, day, hours, minutes).getTime();
                        };
                        valA = parseThaiDate(valA);
                        valB = parseThaiDate(valB);
                    }
                    
                    return valA.toString().localeCompare(valB.toString(), undefined, {numeric: true}) * direction;
                });

                rows.forEach(row => tableBody.appendChild(row));
            });
        });
        
        // --- Modal & Approval Logic ---
        const inspectModal = document.getElementById('inspectModal');
        const modalTitle = document.getElementById('modal-title-inspect').querySelector('span');
        const modalBody = document.getElementById('modal-body-inspect');
        const modalActions = document.getElementById('modal-action-inspect');
        const rejectionSection = document.getElementById('rejection-section');
        const alertContainer = document.getElementById('alert-container');
        const confirmModal = document.getElementById('confirmActionModal');

        tableBody.addEventListener('click', function(e) {
            const targetButton = e.target.closest('.inspect-btn');
            if (targetButton) {
                const requestId = targetButton.dataset.id;
                inspectModal.dataset.currentRequestId = requestId;
                openInspectModal(requestId);
            }
        });
        
        function showAlert(message, type = 'success') {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            const icon = type === 'success' ? '<i class="fa-solid fa-circle-check mr-2"></i>' : '<i class="fa-solid fa-circle-xmark mr-2"></i>';
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
            modalActions.innerHTML = '<form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>';
            rejectionSection.classList.add('hidden');
            document.getElementById('rejection-reason').value = '';
            document.getElementById('rejection-reason').classList.remove('textarea-error');
            document.getElementById('rejection-error-msg').classList.add('hidden');

            try {
                const response = await fetch(`../../../controllers/admin/requests/get_request_details.php?id=${requestId}`);
                const result = await response.json();
                if (result.success) {
                    renderModalContent(result.data);
                } else {
                    modalBody.innerHTML = `<div class="text-center text-error">${result.message}</div>`;
                }
            } catch (error) {
                modalBody.innerHTML = `<div class="text-center text-error">เกิดข้อผิดพลาดในการดึงข้อมูล</div>`;
            }
        }

        function renderModalContent(data) {
            modalTitle.textContent = data.search_id;
            const userTypeThai = data.user_type === 'army' ? 'ข้าราชการ/ลูกจ้าง/พนักงานราชการ ทบ.' : 'บุคคลภายนอก';
            const ownerTypeThai = data.owner_type === 'self' ? 'รถชื่อตนเอง' : 'รถคนอื่น';

            let historySection = '';
            if (data.status === 'pending' && data.edit_status == 1 && data.rejection_reason) {
                historySection = `<div role="alert" class="alert alert-warning alert-soft mb-4">
                    <i class="fa-solid fa-clock-rotate-left text-lg"></i>
                    <div>
                        <h3 class="font-bold">คำร้องนี้เคยถูกส่งกลับไปแก้ไข</h3>
                        <div class="text-xs">เหตุผลครั้งก่อน: ${data.rejection_reason}</div>
                    </div>
                </div>`;
            }
            
            const profileImageSrc = `/public/uploads/user_photos/${data.photo_profile}`;
            
            const addressParts = [data.address, data.subdistrict, data.district, data.user_province, data.zipcode].filter(Boolean);
            const fullAddress = addressParts.join(', ') || '-';

            const userDetails = `
                <h3 class="font-semibold text-base mb-2 uppercase tracking-wider text-slate-500">ข้อมูลผู้ยื่น</h3>
                <div class="flex flex-col items-center">
                    <div class="w-24 h-24 bg-base-300 rounded-lg p-1 mb-4">
                        <img src="${profileImageSrc}" class="w-full h-full object-contain rounded-md cursor-pointer" onclick="zoomImage('${profileImageSrc}')" />
                    </div>
                    <div class="text-center">
                        <div class="font-bold">${data.user_title}${data.user_firstname} ${data.user_lastname}</div>
                        <div class="text-sm text-slate-500">${userTypeThai}</div>
                    </div>
                    <div class="divider my-2"></div>
                    <div class="w-full space-y-2 text-sm text-left">
                        <div class="grid grid-cols-3 gap-2"><span class="text-slate-500 col-span-1">เบอร์โทร:</span><span class="font-semibold col-span-2">${data.phone_number || '-'}</span></div>
                        <div class="grid grid-cols-3 gap-2"><span class="text-slate-500 col-span-1">เลขบัตรฯ:</span><span class="font-semibold col-span-2">${data.national_id || '-'}</span></div>
                        <div class="grid grid-cols-3 gap-2"><span class="text-slate-500 col-span-1">วันเกิด:</span><span class="font-semibold col-span-2">${format_thai_date(data.dob)}</span></div>
                        <div class="grid grid-cols-3 gap-2"><span class="text-slate-500 col-span-1">ที่อยู่:</span><span class="font-semibold col-span-2">${fullAddress}</span></div>
                        ${data.user_type === 'army' ? `
                        <div class="divider my-1"></div>
                        <div class="grid grid-cols-3 gap-2"><span class="text-slate-500 col-span-1">สังกัด:</span><span class="font-semibold col-span-2">${data.work_department || '-'}</span></div>
                        <div class="grid grid-cols-3 gap-2"><span class="text-slate-500 col-span-1">ตำแหน่ง:</span><span class="font-semibold col-span-2">${data.position || '-'}</span></div>
                        <div class="grid grid-cols-3 gap-2"><span class="text-slate-500 col-span-1">เลข ขรก.:</span><span class="font-semibold col-span-2">${data.official_id || '-'}</span></div>
                        ` : ''}
                    </div>
                </div>`;

            const vehicleDetails = `
                <h3 class="font-semibold text-base mb-2 uppercase tracking-wider text-slate-500">ข้อมูลยานพาหนะ</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-xs text-slate-500">ทะเบียน</div>
                        <div class="font-bold text-xl bg-base-300 text-center p-2 rounded-md">${data.license_plate} ${data.province}</div>
                    </div>
                    <div><div class="text-xs text-slate-500">ประเภท</div><div class="font-semibold">${data.vehicle_type}</div></div>
                    <div><div class="text-xs text-slate-500">ยี่ห้อ / รุ่น</div><div class="font-semibold">${data.brand} / ${data.model}</div></div>
                    <div><div class="text-xs text-slate-500">สี</div><div class="font-semibold">${data.color}</div></div>
                    <div><div class="text-xs text-slate-500">วันสิ้นภาษี</div><div class="font-semibold">${format_thai_date(data.tax_expiry_date)}</div></div>
                    <div><div class="text-xs text-slate-500">ความเป็นเจ้าของ</div><div class="font-semibold">${ownerTypeThai} ${data.owner_type === 'other' ? `(${data.other_owner_name}, ${data.other_owner_relation})` : ''}</div></div>
                    <div><div class="text-xs text-slate-500">วันที่นัดรับบัตร</div><div class="font-semibold text-blue-600">${format_thai_date(data.card_pickup_date)}</div></div>
                </div>`;
            
            const imageSection = `
                <h3 class="font-semibold text-base mb-2 uppercase tracking-wider text-slate-500">หลักฐาน</h3>
                <div class="grid grid-cols-2 gap-2">
                    <div class="text-center">
                        <div class="bg-base-300 rounded-lg p-1 flex items-center justify-center h-28">
                            <img src="/public/uploads/vehicle/registration/${data.photo_reg_copy}" class="w-full h-full object-contain rounded-md cursor-pointer" onclick="zoomImage(this.src)">
                        </div>
                        <p class="text-xs font-semibold mt-1">ทะเบียนรถ</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-base-300 rounded-lg p-1 flex items-center justify-center h-28">
                            <img src="/public/uploads/vehicle/tax_sticker/${data.photo_tax_sticker}" class="w-full h-full object-contain rounded-md cursor-pointer" onclick="zoomImage(this.src)">
                        </div>
                        <p class="text-xs font-semibold mt-1">ป้ายภาษี</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-base-300 rounded-lg p-1 flex items-center justify-center h-28">
                            <img src="/public/uploads/vehicle/front_view/${data.photo_front}" class="w-full h-full object-contain rounded-md cursor-pointer" onclick="zoomImage(this.src)">
                        </div>
                        <p class="text-xs font-semibold mt-1">ด้านหน้า</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-base-300 rounded-lg p-1 flex items-center justify-center h-28">
                            <img src="/public/uploads/vehicle/rear_view/${data.photo_rear}" class="w-full h-full object-contain rounded-md cursor-pointer" onclick="zoomImage(this.src)">
                        </div>
                        <p class="text-xs font-semibold mt-1">ด้านหลัง</p>
                    </div>
                </div>`;

            const qrCodeSection = `<div id="qr-code-result" class="hidden mt-4"><div class="card bg-success/10 border-success border shadow-inner"><div class="card-body p-4 items-center text-center"><h3 class="card-title text-base text-success"><i class="fa-solid fa-check-circle mr-2"></i>อนุมัติสำเร็จ</h3><img id="qr-code-image" src="" class="w-32 h-32 rounded-lg p-1 mt-2 bg-white"><p class="text-xs text-slate-500 mt-2">QR Code สำหรับบัตรผ่านถูกสร้างเรียบร้อยแล้ว</p></div></div></div>`;

            modalBody.innerHTML = `
                ${historySection}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-lg bg-base-200">${userDetails}</div>
                    <div class="p-4 rounded-lg bg-base-200">${vehicleDetails}</div>
                    <div class="p-4 rounded-lg bg-base-200">${imageSection}</div>
                </div>
                ${qrCodeSection}`;
            
            if (data.status === 'pending') {
                 modalActions.innerHTML = `<button id="reject-btn" class="btn btn-sm btn-error">ไม่ผ่าน</button><button id="approve-btn" class="btn btn-sm btn-success">อนุมัติ</button><form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>`;
            } else {
                 modalActions.innerHTML = '<form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>';
            }
        }
        
        window.zoomImage = function(src) {
            document.getElementById('zoomed-image').src = src;
            document.getElementById('imageZoomModal').showModal();
        }

        inspectModal.addEventListener('click', function(e){
            const requestId = inspectModal.dataset.currentRequestId;
            if (e.target.id === 'approve-btn') {
                showConfirmModal('อนุมัติคำร้อง', 'คุณต้องการยืนยันการอนุมัติคำร้องนี้ใช่หรือไม่?', 'btn-success', () => processRequest(requestId, 'approve'));
            } else if (e.target.id === 'reject-btn') {
                rejectionSection.classList.remove('hidden');
                modalActions.style.display = 'none';
            }
        });

        document.getElementById('cancel-reject-btn').addEventListener('click', () => {
            rejectionSection.classList.add('hidden');
            modalActions.style.display = '';
        });

        document.getElementById('rejection-reason').addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('textarea-error');
                document.getElementById('rejection-error-msg').classList.add('hidden');
            }
        });

        document.getElementById('confirm-reject-btn').addEventListener('click', () => {
            const reasonInput = document.getElementById('rejection-reason');
            const reasonErrorMsg = document.getElementById('rejection-error-msg');
            const reason = reasonInput.value.trim();
            const requestId = inspectModal.dataset.currentRequestId;
            
            if(!reason){
                reasonInput.classList.add('textarea-error');
                reasonErrorMsg.classList.remove('hidden');
                return;
            }
            
            showConfirmModal('ปฏิเสธคำร้อง', 'คุณต้องการยืนยันการปฏิเสธคำร้องนี้ใช่หรือไม่?', 'btn-error', () => processRequest(requestId, 'reject', reason));
        });
        
        function showConfirmModal(title, message, btnClass, callback) {
            confirmModal.querySelector('#confirm-title').textContent = title;
            confirmModal.querySelector('#confirm-message').textContent = message;
            const okBtn = confirmModal.querySelector('#confirm-ok-btn');
            okBtn.className = `btn btn-sm ${btnClass}`;
            okBtn.textContent = 'ยืนยัน';
            
            const newOkBtn = okBtn.cloneNode(true);
            okBtn.parentNode.replaceChild(newOkBtn, okBtn);

            newOkBtn.addEventListener('click', () => {
                callback();
                confirmModal.close();
            });
            confirmModal.querySelector('#confirm-cancel-btn').onclick = () => confirmModal.close();
            confirmModal.showModal();
        }

        async function processRequest(requestId, action, reason = null) {
            const payload = { request_id: requestId, action: action, reason: reason };
            try {
                const response = await fetch(`../../../controllers/admin/requests/process_request.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    showAlert(result.message, 'success');
                    
                    // Update table row
                    const row = tableBody.querySelector(`tr[data-request-id="${requestId}"]`);
                    if (row) {
                        row.dataset.status = action;
                        const statusCell = row.querySelector('td:nth-last-child(2)');
                        let newBadge = '';
                        if (action === 'approved') newBadge = '<div class="badge badge-success">อนุมัติแล้ว</div>';
                        else if (action === 'rejected') newBadge = '<div class="badge badge-error">ไม่ผ่าน</div>';
                        statusCell.innerHTML = newBadge;
                    }
                    
                    filterAndSearch(); // Refresh table view

                    if (action === 'approve') {
                        document.getElementById('qr-code-image').src = result.qr_code_url;
                        document.getElementById('qr-code-result').classList.remove('hidden');
                        modalActions.innerHTML = '<form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>';
                        rejectionSection.classList.add('hidden');
                    } else { // 'reject'
                        inspectModal.close();
                    }

                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                 showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
        }
        
        const imageZoomModal = document.getElementById('imageZoomModal');
        if (inspectModal) {
            inspectModal.addEventListener('click', function(e) {
                if (e.target === inspectModal) {
                    inspectModal.close();
                }
            });
        }
        if (imageZoomModal) {
            imageZoomModal.addEventListener('click', function(e) {
                const imageContainer = document.getElementById('zoomed-image-container');
                if (imageContainer && !imageContainer.contains(e.target)) {
                    imageZoomModal.close();
                }
            });
        }
    });
    </script>
</body>
</html>
