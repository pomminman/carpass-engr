<?php
session_start();

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../login/login.php");
    exit;
}

require_once '../../../models/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// ดึงข้อมูลแอดมินที่ล็อกอินอยู่
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

// ดึงข้อมูลผู้ดูแลระบบทั้งหมด
$all_admins = [];
$sql_all_admins = "SELECT id, username, title, firstname, lastname, role, department FROM admins ORDER BY created_at DESC";
$result_all_admins = $conn->query($sql_all_admins);
if ($result_all_admins->num_rows > 0) {
    while($row = $result_all_admins->fetch_assoc()) {
        $all_admins[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเจ้าหน้าที่ - ระบบแอดมิน</title>
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
                <div class="flex-1"><a class="btn btn-ghost text-xl">จัดการเจ้าหน้าที่</a></div>
            </div>

            <!-- Page content -->
            <main class="flex-1 p-4 md:p-6 lg:p-8">
                <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-user-shield text-primary"></i> จัดการเจ้าหน้าที่</h1>
                <p class="text-slate-500 mb-6">เพิ่ม ลบ และแก้ไขข้อมูลผู้ดูแลระบบ</p>

                <div class="card bg-base-100 shadow-lg">
                    <div class="card-body">
                         <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <a href="../register/register.php" class="btn btn-sm btn-primary"><i class="fa-solid fa-user-plus mr-2"></i>เพิ่มเจ้าหน้าที่ใหม่</a>
                            <input type="text" id="searchInput" placeholder="ค้นหาจากชื่อ, username..." class="input input-sm input-bordered w-full sm:w-auto">
                        </div>

                        <div class="overflow-x-auto mt-4">
                            <table class="table table-sm" id="adminsTable">
                                <thead>
                                    <tr>
                                        <th class="whitespace-nowrap">ชื่อ-สกุล</th>
                                        <th class="whitespace-nowrap">Username</th>
                                        <th class="whitespace-nowrap">ระดับสิทธิ์ (Role)</th>
                                        <th class="whitespace-nowrap">สังกัด</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_admins as $admin): ?>
                                    <tr>
                                        <td class="font-semibold whitespace-nowrap"><?php echo htmlspecialchars($admin['title'] . $admin['firstname'] . ' ' . $admin['lastname']); ?></td>
                                        <td class="whitespace-nowrap"><?php echo htmlspecialchars($admin['username']); ?></td>
                                        <td class="whitespace-nowrap"><span class="badge badge-ghost"><?php echo htmlspecialchars(ucfirst($admin['role'])); ?></span></td>
                                        <td class="whitespace-nowrap"><?php echo htmlspecialchars($admin['department'] ?? '-'); ?></td>
                                        <td class="whitespace-nowrap">
                                            <div class="flex items-center gap-1">
                                                <div class="tooltip" data-tip="แก้ไข">
                                                    <button class="btn btn-xs btn-ghost btn-square"><i class="fa-solid fa-pen-to-square text-warning"></i></button>
                                                </div>
                                                <div class="tooltip" data-tip="ลบ">
                                                    <button class="btn btn-xs btn-ghost btn-square"><i class="fa-solid fa-trash-can text-error"></i></button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                     <tr id="no-results-row" class="hidden"><td colspan="5" class="text-center text-slate-500 py-4">ไม่พบข้อมูลเจ้าหน้าที่ที่ค้นหา</td></tr>
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
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

        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('adminsTable').querySelector('tbody');
        const allRows = Array.from(tableBody.querySelectorAll('tr:not(#no-results-row)'));
        const noResultsRow = document.getElementById('no-results-row');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleCount = 0;
            allRows.forEach(row => {
                const isVisible = row.textContent.toLowerCase().includes(searchTerm);
                row.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });
            noResultsRow.style.display = visibleCount > 0 ? 'none' : 'table-row';
        });
    });
    </script>
</body>
</html>

