<?php
session_start();
date_default_timezone_set('Asia/Bangkok');

// 1. ตรวจสอบว่าล็อกอินแล้วหรือยัง
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

// 3. ดึงข้อมูลแอดมินที่ล็อกอินอยู่จาก DB เพื่อตรวจสอบสิทธิ์ล่าสุด
$admin_id = $_SESSION['admin_id'];
$admin_info = [];
$sql_admin_info = "SELECT title, firstname, lastname, department, role, view_permission FROM admins WHERE id = ?";
if ($stmt_admin_info = $conn->prepare($sql_admin_info)) {
    $stmt_admin_info->bind_param("i", $admin_id);
    $stmt_admin_info->execute();
    $result_admin_info = $stmt_admin_info->get_result();
    if ($admin_user = $result_admin_info->fetch_assoc()) {
        $admin_info = $admin_user;
        $admin_info['name'] = htmlspecialchars($admin_user['title'] . $admin_user['firstname']);
        $admin_info['lastname'] = htmlspecialchars($admin_user['lastname']);
        $admin_info['view_permission_text'] = $admin_user['view_permission'] == 1 ? 'ดูได้ทุกสังกัด' : 'เฉพาะสังกัดตนเอง';
    }
    $stmt_admin_info->close();
}

// 4. [แก้ไข] ตรวจสอบสิทธิ์จากข้อมูลที่ดึงจาก DB
if (empty($admin_info) || !in_array($admin_info['role'], ['admin', 'superadmin'])) {
    // หากไม่มีข้อมูลหรือไม่ใช่ admin/superadmin ให้ redirect
    header("Location: ../home/home.php");
    exit;
}

// 5. ดึงข้อมูลเจ้าหน้าที่ทั้งหมด
$admins = [];
$sql_admins = "SELECT id, title, firstname, lastname, department, role, view_permission FROM admins ORDER BY created_at DESC";
$result_admins = $conn->query($sql_admins);
if ($result_admins->num_rows > 0) {
    while($row = $result_admins->fetch_assoc()) {
        $admins[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเจ้าหน้าที่ - ระบบจัดการคำร้อง</title>
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
                        <?php if (in_array($admin_info['role'], ['admin', 'superadmin'])): ?>
                        <li><a href="../admins/manage_admins.php"><i class="fa-solid fa-user-shield w-4"></i> จัดการเจ้าหน้าที่</a></li>
                        <?php endif; ?>
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
                    <?php if (in_array($admin_info['role'], ['admin', 'superadmin'])): ?>
                    <li><a href="../admins/manage_admins.php"><i class="fa-solid fa-user-shield"></i> จัดการเจ้าหน้าที่</a></li>
                    <?php endif; ?>
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
                            <div class="text-xs text-slate-500">สังกัด: <?php echo htmlspecialchars($admin_info['department']); ?></div>
                            <div class="text-xs text-slate-500">ระดับสิทธิ์: <?php echo ucfirst(htmlspecialchars($admin_info['role'])); ?></div>
                            <div class="text-xs text-slate-500">สิทธิ์เข้าถึง: <?php echo $admin_info['view_permission_text']; ?></div>
                         </li>
                         <div class="divider my-0"></div>
                         <?php if ($admin_info['role'] === 'superadmin'): ?>
                         <li>
                            <a href="../admins/manage_admins.php">
                                <i class="fa-solid fa-user-pen"></i> แก้ไขข้อมูลส่วนตัว
                            </a>
                        </li>
                        <?php endif; ?>
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
            <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-user-shield text-primary"></i> จัดการเจ้าหน้าที่</h1>
            <p class="text-slate-500 mb-6">เพิ่ม ลบ และแก้ไขข้อมูลเจ้าหน้าที่ในระบบ</p>
            
            <div class="card bg-base-100 shadow-lg">
                <div class="card-body">
                     <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <h2 class="card-title text-base"><i class="fa-solid fa-users"></i> รายชื่อเจ้าหน้าที่</h2>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <input type="text" id="searchInput" placeholder="ค้นหา..." class="input input-sm input-bordered w-full sm:w-auto">
                            <?php if (in_array($admin_info['role'], ['admin', 'superadmin'])): ?>
                            <button class="btn btn-primary btn-sm" onclick="add_admin_modal.showModal()"><i class="fa-solid fa-user-plus"></i> เพิ่มเจ้าหน้าที่</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="overflow-x-auto mt-4">
                        <table class="table table-sm" id="adminsTable">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>สังกัด</th>
                                    <th>ระดับสิทธิ์</th>
                                    <th>สิทธิ์เข้าถึง</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="font-semibold"><?php echo htmlspecialchars($admin['title'] . $admin['firstname'] . ' ' . $admin['lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['department']); ?></td>
                                    <td><div class="badge <?php echo $admin['role'] === 'superadmin' ? 'badge-secondary' : 'badge-primary'; ?>"><?php echo ucfirst(htmlspecialchars($admin['role'])); ?></div></td>
                                    <td><?php echo $admin['view_permission'] == 1 ? 'ทุกสังกัด' : 'เฉพาะสังกัด'; ?></td>
                                    <td>
                                        <!-- Action buttons like edit/delete can go here -->
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
    <!-- Footer -->
    <footer class="fixed bottom-0 left-0 right-0 bg-base-200 text-base-content shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] p-1 text-center z-40">
        <p class="text-[10px] sm:text-xs whitespace-nowrap">Developed by ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ/กยข.กช.)</p>
    </footer>

    <!-- Add Admin Modal -->
    <dialog id="add_admin_modal" class="modal">
        <div class="modal-box">
            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
            </form>
            <h3 class="font-bold text-lg"><i class="fa-solid fa-user-plus"></i> เพิ่มเจ้าหน้าที่ใหม่</h3>
            <form action="../../../controllers/admin/register/process_register.php" method="POST" class="mt-4 space-y-3">
                <input type="text" name="title" placeholder="คำนำหน้า (เช่น นาย, นางสาว)" class="input input-sm input-bordered w-full" required>
                <input type="text" name="firstname" placeholder="ชื่อจริง" class="input input-sm input-bordered w-full" required>
                <input type="text" name="lastname" placeholder="นามสกุล" class="input input-sm input-bordered w-full" required>
                <input type="text" name="department" placeholder="สังกัด" class="input input-sm input-bordered w-full" required>
                <input type="text" name="username" placeholder="Username (สำหรับเข้าระบบ)" class="input input-sm input-bordered w-full" required>
                <input type="password" name="password" placeholder="Password" class="input input-sm input-bordered w-full" required>
                <select name="role" class="select select-sm select-bordered w-full" required>
                    <option disabled selected>เลือกระดับสิทธิ์</option>
                    <option value="admin">Admin</option>
                    <?php if ($admin_info['role'] === 'superadmin'): ?>
                    <option value="superadmin">Superadmin</option>
                    <?php endif; ?>
                </select>
                <select name="view_permission" class="select select-sm select-bordered w-full" required>
                    <option disabled selected>เลือกสิทธิ์การเข้าถึง</option>
                    <option value="0">เฉพาะสังกัดตนเอง</option>
                    <option value="1">ดูได้ทุกสังกัด</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm w-full mt-4">เพิ่มเจ้าหน้าที่</button>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

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
        const table = document.getElementById('adminsTable');
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

