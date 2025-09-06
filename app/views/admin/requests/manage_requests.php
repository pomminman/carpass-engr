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
$admin_info = ['name' => '', 'department' => '', 'role' => ''];
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

// 4. ดึงข้อมูลคำร้องทั้งหมด
$all_requests = [];
$sql_all_requests = "SELECT vr.id, vr.search_id, u.title, u.firstname, u.lastname, vr.license_plate, vr.province, vr.vehicle_type, vr.created_at, vr.status, vr.card_number
                     FROM vehicle_requests vr
                     JOIN users u ON vr.user_id = u.id
                     ORDER BY vr.created_at DESC";
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
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำร้อง - ระบบแอดมิน</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f0f2f5; }
        .menu a.active { background-color: #eef2ff; color: #4338ca; }
    </style>
</head>
<body>
    <div class="drawer lg:drawer-open">
        <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <!-- Navbar -->
            <div class="w-full navbar bg-base-100 lg:hidden">
                <div class="flex-none"><label for="my-drawer-2" class="btn btn-square btn-ghost"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-5 h-5 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg></label></div>
                <div class="flex-1"><a class="btn btn-ghost text-xl">จัดการคำร้อง</a></div>
            </div>

            <!-- Page content -->
            <main class="flex-1 p-4 md:p-6 lg:p-8">
                <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-file-signature text-primary"></i> จัดการคำร้องทั้งหมด</h1>
                <p class="text-slate-500 mb-6">ค้นหาและตรวจสอบคำร้องทั้งหมดในระบบ</p>

                <!-- Requests Table -->
                <div class="card bg-base-100 shadow-lg">
                    <div class="card-body">
                         <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div class="flex items-center gap-2">
                                <select id="statusFilter" class="select select-sm select-bordered">
                                    <option value="all" selected>ทุกสถานะ</option>
                                    <option value="pending">รออนุมัติ</option>
                                    <option value="approved">อนุมัติแล้ว</option>
                                    <option value="rejected">ไม่ผ่าน</option>
                                </select>
                            </div>
                            <input type="text" id="searchInput" placeholder="ค้นหาจากชื่อ, ทะเบียน, รหัส..." class="input input-sm input-bordered w-full sm:w-auto">
                        </div>

                        <div class="overflow-x-auto mt-4">
                            <table class="table table-sm" id="requestsTable">
                                <thead>
                                    <tr>
                                        <th class="whitespace-nowrap">รหัสคำร้อง</th>
                                        <th class="whitespace-nowrap">ชื่อผู้ยื่น</th>
                                        <th class="whitespace-nowrap">ทะเบียนรถ</th>
                                        <th class="whitespace-nowrap">สถานะ</th>
                                        <th class="whitespace-nowrap">เลขที่บัตร</th>
                                        <th class="whitespace-nowrap">วันที่ยื่น</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_requests as $req): ?>
                                    <tr data-request-id="<?php echo $req['id']; ?>">
                                        <td class="font-semibold whitespace-nowrap"><?php echo htmlspecialchars($req['search_id']); ?></td>
                                        <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['title'] . $req['firstname'] . ' ' . $req['lastname']); ?></td>
                                        <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['license_plate'] . ' ' . $req['province']); ?></td>
                                        <td class="whitespace-nowrap">
                                            <?php
                                            $status_text = ''; $status_class = '';
                                            switch ($req['status']) {
                                                case 'approved': $status_text = 'อนุมัติ'; $status_class = 'badge-success'; break;
                                                case 'pending': $status_text = 'รออนุมัติ'; $status_class = 'badge-warning'; break;
                                                case 'rejected': $status_text = 'ไม่ผ่าน'; $status_class = 'badge-error'; break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?> text-white text-xs"><?php echo $status_text; ?></span>
                                        </td>
                                        <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['card_number'] ?? '-'); ?></td>
                                        <td class="whitespace-nowrap"><?php echo format_thai_datetime($req['created_at']); ?></td>
                                        <td>
                                            <div class="tooltip" data-tip="ตรวจสอบ">
                                                <button class="btn btn-xs btn-ghost btn-square inspect-btn" data-id="<?php echo $req['id']; ?>"><i class="fa-solid fa-search text-primary"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr id="no-results-row" class="hidden"><td colspan="7" class="text-center text-slate-500 py-4">ไม่พบข้อมูลคำร้องที่ตรงตามเงื่อนไข</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div> 

        <div class="drawer-side">
            <label for="my-drawer-2" class="drawer-overlay"></label> 
            <ul class="menu p-4 w-64 min-h-full bg-base-200 text-base-content space-y-1" id="sidebar-menu">
                 <li class="mb-4">
                    <a href="../home/home.php" class="text-xl font-bold flex items-center gap-2">
                        <img src="https://img2.pic.in.th/pic/CARPASS-logo11af8574a9cc9906.png" alt="Logo" class="h-10 w-10">
                        <div><span class="whitespace-nowrap text-base">ระบบจัดการ</span><span class="text-xs font-normal text-gray-500 block">สำหรับเจ้าหน้าที่</span></div>
                    </a>
                </li>
                <li><a href="../home/home.php"><i class="fa-solid fa-tachometer-alt w-4"></i> Dashboard</a></li>
                <li><a href="../requests/manage_requests.php"><i class="fa-solid fa-file-signature w-4"></i> จัดการคำร้อง</a></li>
                <li><a href="../users/manage_users.php"><i class="fa-solid fa-users-cog w-4"></i> จัดการผู้ใช้</a></li>
                <li><a href="../admins/manage_admins.php"><i class="fa-solid fa-user-shield w-4"></i> จัดการเจ้าหน้าที่</a></li>
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
    
    <!-- Modals (can be reused from home.php or customized) -->
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar active state
        const currentPage = window.location.pathname;
        const menuLinks = document.querySelectorAll('#sidebar-menu a');

        menuLinks.forEach(link => link.classList.remove('active'));

        const currentPageFilename = currentPage.substring(currentPage.lastIndexOf('/') + 1);
        const activeLink = Array.from(menuLinks).find(link => {
            const linkHref = link.getAttribute('href');
            return linkHref && linkHref.endsWith(currentPageFilename);
        });

        if (activeLink) {
            activeLink.classList.add('active');
        }

        // Filtering Logic
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const tableBody = document.getElementById('requestsTable').querySelector('tbody');
        const allRows = Array.from(tableBody.querySelectorAll('tr[data-request-id]'));
        const noResultsRow = document.getElementById('no-results-row');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const statusValue = statusFilter.value;
            let visibleCount = 0;

            allRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const statusCellText = row.cells[3].textContent.trim().toLowerCase();
                
                const matchesSearch = rowText.includes(searchTerm);
                let matchesStatus = false;
                if (statusValue === 'all') {
                    matchesStatus = true;
                } else if (statusValue === 'pending' && statusCellText.includes('รออนุมัติ')) {
                    matchesStatus = true;
                } else if (statusValue === 'approved' && statusCellText.includes('อนุมัติ')) {
                    matchesStatus = true;
                } else if (statusValue === 'rejected' && statusCellText.includes('ไม่ผ่าน')) {
                    matchesStatus = true;
                }

                const isVisible = matchesSearch && matchesStatus;
                row.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });

            noResultsRow.style.display = visibleCount > 0 ? 'none' : 'table-row';
        }

        searchInput.addEventListener('input', filterTable);
        statusFilter.addEventListener('change', filterTable);
    });
    </script>
</body>
</html>

