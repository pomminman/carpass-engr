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

// 4. ดึงข้อมูลผู้ใช้ทั้งหมด
$users = [];
$sql_users = "SELECT id, title, firstname, lastname, user_type, phone_number, national_id, work_department FROM users ORDER BY created_at DESC";
$result_users = $conn->query($sql_users);
if ($result_users->num_rows > 0) {
    while($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน - ระบบจัดการคำร้อง</title>
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
            <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-users-cog text-primary"></i> จัดการผู้ใช้งาน</h1>
            <p class="text-slate-500 mb-6">ดูและจัดการข้อมูลผู้ใช้งานในระบบ</p>
            
            <div class="card bg-base-100 shadow-lg">
                <div class="card-body">
                    <div class="flex flex-col sm:flex-row justify-end items-start sm:items-center gap-4">
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                           <input type="text" id="searchInput" placeholder="ค้นหา..." class="input input-sm input-bordered w-full sm:w-auto">
                        </div>
                    </div>

                    <div class="overflow-x-auto mt-4">
                        <table class="table table-sm" id="usersTable">
                             <thead class="bg-slate-50">
                                <tr>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>ประเภท</th>
                                    <th>เบอร์โทรศัพท์</th>
                                    <th>เลขบัตรประชาชน</th>
                                    <th>สังกัด</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr><td colspan="6" class="text-center text-slate-500 py-4">ไม่พบข้อมูลผู้ใช้งาน</td></tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="font-semibold whitespace-nowrap"><?php echo htmlspecialchars($user['title'] . $user['firstname'] . ' ' . $user['lastname']); ?></td>
                                        <td class="whitespace-nowrap"><?php echo $user['user_type'] === 'army' ? 'กำลังพล' : 'บุคคลภายนอก'; ?></td>
                                        <td class="whitespace-nowrap"><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                        <td class="whitespace-nowrap"><?php echo htmlspecialchars($user['national_id']); ?></td>
                                        <td class="whitespace-nowrap"><?php echo htmlspecialchars($user['work_department'] ?? '-'); ?></td>
                                        <td class="whitespace-nowrap">
                                            <!-- Action buttons can be added here if needed -->
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <tr id="no-results-row" class="hidden"><td colspan="6" class="text-center text-slate-500 py-4">ไม่พบข้อมูลผู้ใช้ที่ค้นหา</td></tr>
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
        const table = document.getElementById('usersTable');
        const tableBody = table.querySelector('tbody');
        const allRows = tableBody.querySelectorAll('tr');
        const noResultsRow = document.getElementById('no-results-row');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleCount = 0;
            allRows.forEach(row => {
                if (row.id === 'no-results-row') return;
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
