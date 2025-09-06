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

// --- [เพิ่ม] จัดการข้อความแจ้งเตือนจากการลงทะเบียน ---
$alert_message = $_SESSION['register_message'] ?? null;
$alert_type = ($_SESSION['register_status'] ?? 'error') === 'success' ? 'success' : 'error';
unset($_SESSION['register_message'], $_SESSION['register_status']);


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

// ดึงข้อมูลผู้ดูแลระบบทั้งหมดตามสิทธิ์
$all_admins = [];
$sql_all_admins = "SELECT id, username, title, firstname, lastname, role, department FROM admins WHERE id != ?";

// Superadmin sees all other admins.
// Admin and Viewer cannot see superadmins.
if ($admin_info['role'] !== 'superadmin') {
    $sql_all_admins .= " AND role != 'superadmin'";
}

$sql_all_admins .= " ORDER BY created_at DESC";

$stmt_all_admins = $conn->prepare($sql_all_admins);
if ($stmt_all_admins) {
    $stmt_all_admins->bind_param("i", $admin_id);
    $stmt_all_admins->execute();
    $result_all_admins = $stmt_all_admins->get_result();
    if ($result_all_admins->num_rows > 0) {
        while($row = $result_all_admins->fetch_assoc()) {
            $all_admins[] = $row;
        }
    }
    $stmt_all_admins->close();
}


// --- [เพิ่ม] ดึงข้อมูลสำหรับฟอร์มใน Modal ---
$departments = [];
$sql_dept = "SELECT name FROM departments ORDER BY display_order ASC, name ASC";
$result_dept = $conn->query($sql_dept);
if ($result_dept->num_rows > 0) {
    while($row = $result_dept->fetch_assoc()) {
        $departments[] = $row;
    }
}
$titles = ["นาย", "นาง", "นางสาว", "พล.อ.", "พล.อ.หญิง", "พล.ท.", "พล.ท.หญิง", "พล.ต.", "พล.ต.หญิง", "พ.อ.", "พ.อ.หญิง", "พ.ท.", "พ.ท.หญิง", "พ.ต.", "พ.ต.หญิง", "ร.อ.", "ร.อ.หญิง", "ร.ท.", "ร.ท.หญิง", "ร.ต.", "ร.ต.หญิง", "จ.ส.อ.", "จ.ส.อ.หญิง", "จ.ส.ท.", "จ.ส.ท.หญิง", "จ.ส.ต.", "จ.ส.ต.หญิง", "ส.อ.", "ส.อ.หญิง", "ส.ท.", "ส.ท.หญิง", "ส.ต.", "ส.ต.หญิง", "พลทหาร"];

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
        .error-message { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; }
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

                 <div id="alert-container" class="toast toast-top toast-center z-50"></div>

                <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-user-shield text-primary"></i> จัดการเจ้าหน้าที่</h1>
                <p class="text-slate-500 mb-6">เพิ่ม ลบ และแก้ไขข้อมูลผู้ดูแลระบบ</p>

                <div class="card bg-base-100 shadow-lg">
                    <div class="card-body">
                         <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <?php if ($admin_info['role'] !== 'viewer'): ?>
                                <button class="btn btn-sm btn-primary" onclick="add_admin_modal.showModal()"><i class="fa-solid fa-user-plus mr-2"></i>เพิ่มเจ้าหน้าที่ใหม่</button>
                            <?php endif; ?>
                            <input type="text" id="searchInput" placeholder="ค้นหาจากชื่อ, username..." class="input input-sm input-bordered w-full sm:w-auto <?php if ($admin_info['role'] === 'viewer') echo 'ml-auto'; ?>">
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
                                    <?php if (empty($all_admins)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-slate-500 py-4">ไม่พบข้อมูลเจ้าหน้าที่</td>
                                        </tr>
                                    <?php else: ?>
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
                                    <?php endif; ?>
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
            <ul class="menu p-4 w-56 min-h-full bg-base-200 text-base-content space-y-1" id="sidebar-menu">
                 <li class="mb-4">
                    <a href="../home/home.php" class="text-xl font-bold flex items-center gap-2">
                        <img src="https://img2.pic.in.th/pic/CARPASS-logo11af8574a9cc9906.png" alt="Logo" class="h-10 w-10">
                        <div><span class="whitespace-nowrap text-base">ระบบจัดการ</span><span class="text-xs font-normal text-gray-500 block">สำหรับเจ้าหน้าที่</span></div>
                    </a>
                </li>
                <li><a href="../home/home.php"><i class="fa-solid fa-tachometer-alt w-4"></i> Dashboard</a></li>
                <li><a href="../requests/manage_requests.php"><i class="fa-solid fa-file-signature w-4"></i> จัดการคำร้อง</a></li>
                <li><a href="../users/manage_users.php"><i class="fa-solid fa-users-cog w-4"></i> จัดการผู้ใช้</a></li>
                <?php if ($admin_info['role'] !== 'viewer'): ?>
                    <li><a href="../admins/manage_admins.php"><i class="fa-solid fa-user-shield w-4"></i> จัดการเจ้าหน้าที่</a></li>
                <?php endif; ?>
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
    
    <dialog id="add_admin_modal" class="modal">
        <div class="modal-box w-11/12 max-w-3xl">
            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
            </form>
            <h3 class="font-bold text-lg">สร้างบัญชีเจ้าหน้าที่ใหม่</h3>
            
            <form id="add_admin_form" action="../../../controllers/admin/register/process_register.php" method="POST" class="mt-4" novalidate>
                <div class="divider divider-start font-semibold">ข้อมูลบัญชี</div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="form-control sm:col-span-1">
                        <label class="label"><span class="label-text">ชื่อผู้ใช้งาน (Username)</span></label>
                        <input type="text" name="username" placeholder="เช่น admin01" class="input input-sm input-bordered" required>
                        <p class="error-message hidden"></p>
                    </div>
                    <div class="form-control sm:col-span-1">
                        <label class="label"><span class="label-text">รหัสผ่าน</span></label>
                        <input type="password" name="password" placeholder="กรอกรหัสผ่าน" class="input input-sm input-bordered" required>
                        <p class="error-message hidden"></p>
                    </div>
                    <div class="form-control sm:col-span-1">
                        <label class="label"><span class="label-text">ยืนยันรหัสผ่าน</span></label>
                        <input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่าน" class="input input-sm input-bordered" required>
                        <p class="error-message hidden"></p>
                    </div>
                </div>

                <div class="divider divider-start font-semibold mt-6">ข้อมูลส่วนตัว</div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text">คำนำหน้า</span></label>
                        <select name="title" class="select select-sm select-bordered w-full" id="title-select" required>
                            <option disabled selected value="">เลือกคำนำหน้า</option>
                            <?php foreach ($titles as $title): ?>
                                <option value="<?php echo htmlspecialchars($title); ?>"><?php echo htmlspecialchars($title); ?></option>
                            <?php endforeach; ?>
                            <option value="other">อื่นๆ (โปรดระบุ)</option>
                        </select>
                        <p class="error-message hidden"></p>
                        <input type="text" id="title-other" name="title_other" placeholder="ระบุคำนำหน้าใหม่" class="input input-sm input-bordered w-full mt-2 hidden" />
                        <p class="error-message hidden"></p>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">ชื่อจริง</span></label>
                        <input type="text" name="firstname" placeholder="ชื่อจริง" class="input input-sm input-bordered" required>
                        <p class="error-message hidden"></p>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">นามสกุล</span></label>
                        <input type="text" name="lastname" placeholder="นามสกุล" class="input input-sm input-bordered" required>
                        <p class="error-message hidden"></p>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">เบอร์โทร</span></label>
                        <input type="tel" name="phone_number" placeholder="000-000-0000" class="input input-sm input-bordered" required>
                        <p class="error-message hidden"></p>
                    </div>
                </div>

                <div class="divider divider-start font-semibold mt-6">ข้อมูลการทำงาน</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text">ตำแหน่ง</span></label>
                        <input type="text" name="position" placeholder="เช่น น.สารบรรณ" class="input input-sm input-bordered" required>
                        <p class="error-message hidden"></p>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">สังกัด</span></label>
                        <select name="department" class="select select-sm select-bordered w-full" id="department-select" required>
                            <option disabled selected value="">เลือกสังกัด</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['name']); ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                            <option value="other">อื่นๆ (โปรดระบุ)</option>
                        </select>
                         <p class="error-message hidden"></p>
                        <input type="text" id="department-other" name="department_other" placeholder="ระบุสังกัดใหม่" class="input input-sm input-bordered w-full mt-2 hidden" />
                         <p class="error-message hidden"></p>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">ระดับสิทธิ์ (Role)</span></label>
                        <select name="role" class="select select-sm select-bordered" required>
                            <option disabled selected value="">เลือก</option>
                            <option value="viewer">Viewer</option>
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                        <p class="error-message hidden"></p>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">สิทธิ์การเข้าถึงข้อมูล</span></label>
                        <div class="flex items-center gap-4 mt-2">
                            <label class="label cursor-pointer gap-2">
                                <input type="radio" name="view_permission" class="radio radio-sm" value="1" />
                                <span class="label-text">ดูข้อมูลได้ทุกสังกัด</span> 
                            </label>
                            <label class="label cursor-pointer gap-2">
                                <input type="radio" name="view_permission" class="radio radio-sm" value="0" checked/>
                                <span class="label-text">เฉพาะสังกัดตนเอง</span> 
                            </label>
                        </div>
                    </div>
                </div>

                <div class="modal-action mt-6">
                     <button type="submit" class="btn btn-sm btn-primary">สร้างบัญชี</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Alert Logic ---
        const alertMessage = <?php echo json_encode($alert_message); ?>;
        const alertType = <?php echo json_encode($alert_type); ?>;
        const alertContainer = document.getElementById('alert-container');
        if (alertMessage) {
            const alertClass = alertType === 'success' ? 'alert-success' : 'alert-error';
            const icon = alertType === 'success' ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-circle-xmark"></i>';
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${alertClass} shadow-lg`;
            alertDiv.innerHTML = `<div>${icon}<span>${alertMessage}</span></div>`;
            alertContainer.appendChild(alertDiv);
            setTimeout(() => {
                alertDiv.style.transition = 'opacity 0.5s ease';
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 500);
            }, 3000);
        }
        
        // --- Sidebar & Search Logic ---
        const currentPage = window.location.pathname;
        const menuLinks = document.querySelectorAll('#sidebar-menu a');
        menuLinks.forEach(link => link.classList.remove('active'));
        const currentPageFilename = currentPage.substring(currentPage.lastIndexOf('/') + 1);
        const activeLink = Array.from(menuLinks).find(link => {
            const linkHref = link.getAttribute('href');
            return linkHref && linkHref.endsWith(currentPageFilename);
        });
        if (activeLink) activeLink.classList.add('active');

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

        // --- Add Admin Modal Form Validation Logic ---
        const addAdminForm = document.getElementById('add_admin_form');

        function showError(element, message) {
            const parent = element.closest('.form-control');
            let errorElement = parent.querySelector('.error-message');
            // In case of 'other' fields, the error message might be outside the immediate parent
            if (!errorElement) {
                errorElement = parent.nextElementSibling;
            }
             if (errorElement) {
                errorElement.textContent = message;
                errorElement.classList.remove('hidden');
            }
            element.classList.add('input-error', 'select-error');
        }

        function clearError(element) {
            const parent = element.closest('.form-control');
            let errorElement = parent.querySelector('.error-message');
            if (!errorElement) {
                errorElement = parent.nextElementSibling;
            }
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.classList.add('hidden');
            }
            element.classList.remove('input-error', 'select-error');
        }
        
        function formatInput(input, pattern) {
            const numbers = input.value.replace(/\D/g, '');
            let result = '';
            let patternIndex = 0;
            let numbersIndex = 0;
            while(patternIndex < pattern.length && numbersIndex < numbers.length) {
                if (pattern[patternIndex] === '-') {
                    result += '-';
                    patternIndex++;
                } else {
                    result += numbers[numbersIndex];
                    patternIndex++;
                    numbersIndex++;
                }
            }
            input.value = result;
        }

        addAdminForm.addEventListener('submit', function(e) {
            let isValid = true;
            const inputs = addAdminForm.querySelectorAll('input[required], select[required]');
            
            inputs.forEach(input => {
                clearError(input);
                if (input.value.trim() === '') {
                    isValid = false;
                    showError(input, 'กรุณากรอกข้อมูล');
                }
            });

            // Specific validations
            const username = addAdminForm.querySelector('input[name="username"]');
            const password = addAdminForm.querySelector('input[name="password"]');
            const confirmPassword = addAdminForm.querySelector('input[name="confirm_password"]');
            const phone = addAdminForm.querySelector('input[name="phone_number"]');

            const noThaiRegex = /^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]*$/;
            const noSpaceRegex = /^\S*$/;
            const noEnglishRegex = /^[^a-zA-Z]*$/;

            if (username.value.trim() !== '' && (!noThaiRegex.test(username.value) || !noSpaceRegex.test(username.value))) { showError(username, 'ต้องเป็นภาษาอังกฤษ/ตัวเลข ไม่มีเว้นวรรค'); isValid = false; }
            if (password.value.trim() !== '' && (!noThaiRegex.test(password.value) || !noSpaceRegex.test(password.value))) { showError(password, 'ต้องเป็นภาษาอังกฤษ/ตัวเลข ไม่มีเว้นวรรค'); isValid = false; }
            if (password.value !== confirmPassword.value) { showError(confirmPassword, 'รหัสผ่านไม่ตรงกัน'); isValid = false; }

            const firstname = addAdminForm.querySelector('input[name="firstname"]');
            const lastname = addAdminForm.querySelector('input[name="lastname"]');
            const titleOther = addAdminForm.querySelector('input[name="title_other"]');
            if (firstname.value.trim() !== '' && !noEnglishRegex.test(firstname.value)) { showError(firstname, 'ต้องเป็นภาษาไทย'); isValid = false; }
            if (lastname.value.trim() !== '' && !noEnglishRegex.test(lastname.value)) { showError(lastname, 'ต้องเป็นภาษาไทย'); isValid = false; }
            if (addAdminForm.querySelector('#title-select').value === 'other' && (titleOther.value.trim() === '' || !noEnglishRegex.test(titleOther.value))) { showError(titleOther, 'ต้องเป็นภาษาไทยและห้ามว่าง'); isValid = false; }
            
            if (phone.value.replace(/\D/g, '').length !== 10) { showError(phone, 'ต้องเป็น 10 หลัก'); isValid = false; }
            
            const position = addAdminForm.querySelector('input[name="position"]');
            const departmentOther = addAdminForm.querySelector('input[name="department_other"]');
            if (position.value.trim() !== '' && !noEnglishRegex.test(position.value)) { showError(position, 'ต้องเป็นภาษาไทย'); isValid = false; }
            if (addAdminForm.querySelector('#department-select').value === 'other' && (departmentOther.value.trim() === '' || !noEnglishRegex.test(departmentOther.value))) { showError(departmentOther, 'ต้องเป็นภาษาไทยและห้ามว่าง'); isValid = false; }

            if (!isValid) {
                e.preventDefault();
            }
        });

        // Clear error on input
        addAdminForm.querySelectorAll('input, select').forEach(el => {
            el.addEventListener('input', () => clearError(el));
            el.addEventListener('change', () => clearError(el));
        });
        
        // Format phone number
        addAdminForm.querySelector('input[name="phone_number"]').addEventListener('input', function() {
            formatInput(this, 'xxx-xxx-xxxx');
        });

        // Other option logic
        function setupOtherOption(selectId, otherInputId) {
            const select = document.getElementById(selectId);
            const otherInput = document.getElementById(otherInputId);
            if(select && otherInput){
                select.addEventListener('change', function() {
                    const isOther = this.value === 'other';
                    otherInput.classList.toggle('hidden', !isOther);
                    if(isOther) {
                        otherInput.setAttribute('required', '');
                    } else {
                        otherInput.removeAttribute('required');
                        otherInput.value = '';
                        clearError(otherInput);
                    }
                });
            }
        }
        setupOtherOption('department-select', 'department-other');
        setupOtherOption('title-select', 'title-other');
    });
    </script>
</body>
</html>

