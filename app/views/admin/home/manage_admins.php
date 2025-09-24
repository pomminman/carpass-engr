<?php
// app/views/admin/home/manage_admins.php
require_once __DIR__ . '/../layouts/header.php';

// --- Check permission for this specific page ---
if (!in_array($admin_info['role'], ['admin', 'superadmin'])) {
    header("Location: dashboard.php");
    exit;
}

// --- Data fetching for departments dropdown ---
$departments = [];
$sql_dept = "SELECT name FROM departments ORDER BY display_order ASC, name ASC";
$result_dept = $conn->query($sql_dept);
if ($result_dept->num_rows > 0) {
    while($row = $result_dept->fetch_assoc()) {
        $departments[] = $row['name'];
    }
}

// --- Data fetching for admins table ---
$admins = [];
$sql_admins = "SELECT id, username, title, firstname, lastname, department, role, view_permission, created_at FROM admins ORDER BY created_at ASC";
$result_admins = $conn->query($sql_admins);
if ($result_admins->num_rows > 0) {
    while($row = $result_admins->fetch_assoc()) {
        $admins[] = $row;
    }
}
?>

<!-- Page content -->
<main id="manage-admins-page" class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
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
                            <th class="md:hidden">การกระทำ</th>
                            <th data-sort-by="name">ชื่อ-นามสกุล <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="department">สังกัด <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="role">ระดับสิทธิ์ <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="permission">สิทธิ์เข้าถึง <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="date" class="sort-asc">วันที่เพิ่ม <i class="fa-solid fa-sort-up"></i></th>
                            <th class="hidden md:table-cell">การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admins)): ?>
                            <tr id="no-results-row"><td colspan="7" class="text-center text-slate-500 py-4">ไม่พบข้อมูลเจ้าหน้าที่</td></tr>
                        <?php else: ?>
                            <?php foreach ($admins as $admin): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap md:hidden">
                                    <button class="btn btn-xs btn-info inspect-admin-btn whitespace-nowrap" data-id="<?php echo $admin['id']; ?>">
                                        <i class="fa-solid fa-search"></i> ตรวจสอบ
                                    </button>
                                </td>
                                <td data-cell="name" class="font-semibold whitespace-nowrap"><?php echo htmlspecialchars($admin['title'] . ' ' . $admin['firstname'] . ' ' . $admin['lastname']); ?></td>
                                <td data-cell="department" class="whitespace-nowrap"><?php echo htmlspecialchars($admin['department']); ?></td>
                                <td data-cell="role" class="whitespace-nowrap"><div class="badge <?php echo $admin['role'] === 'superadmin' ? 'badge-secondary' : 'badge-primary'; ?>"><?php echo ucfirst(htmlspecialchars($admin['role'])); ?></div></td>
                                <td data-cell="permission" class="whitespace-nowrap"><?php echo $admin['view_permission'] == 1 ? 'ทุกสังกัด' : 'เฉพาะสังกัด'; ?></td>
                                <td data-cell="date" class="whitespace-nowrap" data-sort-value="<?php echo strtotime($admin['created_at']); ?>"><?php echo format_thai_datetime($admin['created_at']); ?></td>
                                <td class="whitespace-nowrap hidden md:table-cell">
                                     <button class="btn btn-xs btn-info inspect-admin-btn whitespace-nowrap" data-id="<?php echo $admin['id']; ?>">
                                        <i class="fa-solid fa-search"></i> ตรวจสอบ
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <tr id="no-search-results-row" class="hidden"><td colspan="7" class="text-center text-slate-500 py-4">ไม่พบข้อมูลเจ้าหน้าที่ที่ค้นหา</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add Admin Modal -->
<dialog id="add_admin_modal" class="modal modal-fade">
    <div class="modal-box w-11/12 max-w-3xl">
        <h3 class="font-bold text-lg">เพิ่มเจ้าหน้าที่ใหม่</h3>
        <form id="addAdminForm" method="POST" action="../../../controllers/admin/register/process_register.php" enctype="multipart/form-data" novalidate>
            <div class="py-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-2 md:col-span-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div class="form-control"><label class="label py-1"><span class="label-text">ชื่อผู้ใช้ (Username)</span></label><input type="text" name="username" placeholder="ห้ามซ้ำกับคนอื่น" class="input input-sm input-bordered" required> <p class="error-message hidden text-xs text-error mt-1"></p></div>
                        <div class="form-control"><label class="label py-1"><span class="label-text">สังกัด</span></label><select name="department" class="select select-sm select-bordered" required><option disabled selected value="">เลือกสังกัด</option><?php foreach($departments as $dept) echo "<option value='".htmlspecialchars($dept)."'>".htmlspecialchars($dept)."</option>"; ?></select> <p class="error-message hidden text-xs text-error mt-1"></p></div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div class="form-control"><label class="label py-1"><span class="label-text">คำนำหน้า</span></label><input type="text" name="title" placeholder="เช่น ร.ท." class="input input-sm input-bordered" required> <p class="error-message hidden text-xs text-error mt-1"></p></div>
                        <div class="form-control"><label class="label py-1"><span class="label-text">ชื่อจริง</span></label><input type="text" name="firstname" placeholder="เช่น พรหมินทร์" class="input input-sm input-bordered" required> <p class="error-message hidden text-xs text-error mt-1"></p></div>
                        <div class="form-control"><label class="label py-1"><span class="label-text">นามสกุล</span></label><input type="text" name="lastname" placeholder="เช่น อินทมาตย์" class="input input-sm input-bordered" required> <p class="error-message hidden text-xs text-error mt-1"></p></div>
                    </div>
                     <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div class="form-control"><label class="label py-1"><span class="label-text">รหัสผ่าน</span></label><input type="password" name="password" placeholder="อย่างน้อย 6 ตัวอักษร" class="input input-sm input-bordered" required> <p class="error-message hidden text-xs text-error mt-1"></p></div>
                        <div class="form-control"><label class="label py-1"><span class="label-text">ยืนยันรหัสผ่าน</span></label><input type="password" name="confirm_password" placeholder="กรอกรหัสผ่านอีกครั้ง" class="input input-sm input-bordered" required> <p class="error-message hidden text-xs text-error mt-1"></p></div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div class="form-control"><label class="label py-1"><span class="label-text">ระดับสิทธิ์</span></label><select name="role" class="select select-sm select-bordered" required><option value="viewer">Viewer</option><option value="admin" selected>Admin</option><option value="superadmin">Superadmin</option></select> <p class="error-message hidden text-xs text-error mt-1"></p></div>
                        <div class="form-control"><label class="label py-1"><span class="label-text">สิทธิ์เข้าถึงข้อมูล</span></label><div class="flex gap-4"><label class="label cursor-pointer"><input type="radio" name="view_permission" value="0" class="radio radio-sm" checked> <span class="label-text ml-2">เฉพาะสังกัด</span></label><label class="label cursor-pointer"><input type="radio" name="view_permission" value="1" class="radio radio-sm"> <span class="label-text ml-2">ทุกสังกัด</span></label></div></div>
                    </div>
                </div>
            </div>
            <div class="modal-action">
                <button type="button" class="btn btn-sm" onclick="add_admin_modal.close()">ยกเลิก</button>
                <button type="submit" class="btn btn-sm btn-primary">บันทึก</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<!-- View Admin Modal -->
<dialog id="view_admin_modal" class="modal modal-fade">
    <div class="modal-box">
        <h3 class="font-bold text-lg">รายละเอียดเจ้าหน้าที่</h3>
        <div class="divider"></div>
        <div class="text-sm space-y-2 py-4">
            <p><strong>ชื่อ-สกุล:</strong> <span id="modal-admin-fullname"></span></p>
            <p><strong>ชื่อผู้ใช้:</strong> <span id="modal-admin-username"></span></p>
            <p><strong>สังกัด:</strong> <span id="modal-admin-department"></span></p>
            <p><strong>ระดับสิทธิ์:</strong> <span id="modal-admin-role"></span></p>
            <p><strong>สิทธิ์เข้าถึงข้อมูล:</strong> <span id="modal-admin-permission"></span></p>
        </div>
        <div class="modal-action">
            <button type="button" class="btn btn-sm btn-ghost" onclick="view_admin_modal.close()">ปิด</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

