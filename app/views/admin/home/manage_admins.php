<?php
// app/views/admin/home/manage_admins.php
require_once __DIR__ . '/../layouts/header.php';

// --- Check permission ---
if (!in_array($admin_info['role'], ['admin', 'superadmin'])) {
    header("Location: dashboard.php");
    exit;
}

// --- Data for dropdowns ---
$departments = [];
$sql_dept = "SELECT name FROM departments ORDER BY display_order ASC, name ASC";
$result_dept = $conn->query($sql_dept);
if ($result_dept->num_rows > 0) {
    while($row = $result_dept->fetch_assoc()) {
        $departments[] = $row['name'];
    }
}

// --- Filtering & Sorting Logic ---
$filters = [
    'role' => $_GET['role'] ?? 'all',
    'department' => $_GET['department'] ?? 'all',
    'search' => $_GET['search'] ?? ''
];

// Sorting
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_dir = isset($_GET['dir']) && in_array(strtoupper($_GET['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['dir']) : 'DESC';
$valid_sort_columns = ['name', 'department', 'role', 'permission', 'created_at'];
if (!in_array($sort_by, $valid_sort_columns)) {
    $sort_by = 'created_at';
}
// Map front-end name to actual DB column
$sort_column_map = [
    'name' => 'a.firstname',
    'department' => 'a.department',
    'role' => 'a.role',
    'permission' => 'a.view_permission',
    'created_at' => 'a.created_at'
];
$order_by_sql = "ORDER BY " . $sort_column_map[$sort_by] . " " . $sort_dir;


$where_clauses = [];
$params = [];
$types = '';

if ($filters['role'] !== 'all') {
    $where_clauses[] = 'a.role = ?';
    $params[] = $filters['role'];
    $types .= 's';
}
if ($filters['department'] !== 'all') {
    $where_clauses[] = 'a.department = ?';
    $params[] = $filters['department'];
    $types .= 's';
}
if (!empty($filters['search'])) {
    $where_clauses[] = "(a.firstname LIKE ? OR a.lastname LIKE ? OR a.username LIKE ?)";
    $search_term = "%" . $filters['search'] . "%";
    array_push($params, $search_term, $search_term, $search_term);
    $types .= "sss";
}

// --- Pagination Logic ---
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$count_where_sql = !empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : "";
$count_sql = "SELECT COUNT(a.id) as total FROM admins a $count_where_sql";
$stmt_count = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
$stmt_count->close();

// --- Data Fetching ---
$admins = [];
$sql_admins = "SELECT a.id, a.username, a.title, a.firstname, a.lastname, a.department, a.role, a.view_permission, a.created_at 
               FROM admins a
               " . $count_where_sql . "
               " . $order_by_sql . "
               LIMIT ? OFFSET ?";

$data_params = $params;
array_push($data_params, $limit, $offset);
$data_types = $types . "ii";

$stmt_admins = $conn->prepare($sql_admins);
$stmt_admins->bind_param($data_types, ...$data_params);
$stmt_admins->execute();
$result_admins = $stmt_admins->get_result();
if ($result_admins->num_rows > 0) {
    while($row = $result_admins->fetch_assoc()) {
        $admins[] = $row;
    }
}
$stmt_admins->close();

// --- Helper for Links ---
function get_sort_link($column, $current_sort, $current_dir, $filters) {
    $dir = ($current_sort === $column && $current_dir === 'ASC') ? 'desc' : 'asc';
    $query_params = array_merge($filters, ['page' => 1, 'sort' => $column, 'dir' => $dir]);
    return '?' . http_build_query($query_params);
}

function get_pagination_link($page, $filters, $sort, $dir) {
    $query = http_build_query(array_merge($filters, ['page' => $page, 'sort' => $sort, 'dir' => $dir]));
    return '?' . $query;
}
?>

<!-- Page content -->
<main id="manage-admins-page" class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-user-shield text-primary"></i> จัดการเจ้าหน้าที่</h1>
            <p class="text-slate-500">เพิ่ม ลบ และแก้ไขข้อมูลเจ้าหน้าที่ (พบ <?php echo number_format($total_rows); ?> รายการ)</p>
        </div>
        <div>
             <?php if (in_array($admin_info['role'], ['admin', 'superadmin'])): ?>
                <button class="btn btn-primary btn-sm" onclick="add_admin_modal.showModal()"><i class="fa-solid fa-user-plus"></i> เพิ่มเจ้าหน้าที่</button>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
             <form method="GET" action="" id="filterForm">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sort_dir); ?>">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
                    <div class="form-control w-full">
                        <label class="label py-1"><span class="label-text">ระดับสิทธิ์</span></label>
                        <select name="role" class="select select-bordered select-sm filter-input">
                            <option value="all" <?php echo ($filters['role'] == 'all' ? 'selected' : ''); ?>>ทั้งหมด</option>
                            <option value="superadmin" <?php echo ($filters['role'] == 'superadmin' ? 'selected' : ''); ?>>Superadmin</option>
                            <option value="admin" <?php echo ($filters['role'] == 'admin' ? 'selected' : ''); ?>>Admin</option>
                            <option value="viewer" <?php echo ($filters['role'] == 'viewer' ? 'selected' : ''); ?>>Viewer</option>
                        </select>
                    </div>
                    <div class="form-control w-full">
                        <label class="label py-1"><span class="label-text">สังกัด</span></label>
                        <select name="department" class="select select-bordered select-sm filter-input">
                            <option value="all">ทุกสังกัด</option>
                            <?php foreach($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($filters['department'] == $dept ? 'selected' : ''); ?>><?php echo htmlspecialchars($dept); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-control w-full md:col-span-2">
                        <label class="label py-1"><span class="label-text">ค้นหา</span></label>
                        <div class="flex gap-2">
                            <input type="text" name="search" placeholder="ชื่อ, นามสกุล, Username..." class="input input-sm input-bordered w-full filter-input" value="<?php echo htmlspecialchars($filters['search']); ?>">
                            <a href="manage_admins.php" class="btn btn-sm btn-ghost"><i class="fa-solid fa-eraser mr-1"></i></a>
                            <a href="../../../controllers/admin/admins/export_admins.php?<?php echo http_build_query($filters); ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel mr-1"></i></a>
                        </div>
                    </div>
                </div>
             </form>

            <div class="overflow-x-auto mt-4">
                <table class="table table-sm" id="adminsTable">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="md:hidden">การกระทำ</th>
                            <th><a href="<?php echo get_sort_link('name', $sort_by, $sort_dir, $filters); ?>">ชื่อ-นามสกุล <i class="fa-solid <?php echo $sort_by === 'name' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('department', $sort_by, $sort_dir, $filters); ?>">สังกัด <i class="fa-solid <?php echo $sort_by === 'department' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('role', $sort_by, $sort_dir, $filters); ?>">ระดับสิทธิ์ <i class="fa-solid <?php echo $sort_by === 'role' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('permission', $sort_by, $sort_dir, $filters); ?>">สิทธิ์เข้าถึง <i class="fa-solid <?php echo $sort_by === 'permission' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('created_at', $sort_by, $sort_dir, $filters); ?>">วันที่เพิ่ม <i class="fa-solid <?php echo $sort_by === 'created_at' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th class="hidden md:table-cell">การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admins)): ?>
                            <tr id="no-results-row"><td colspan="7" class="text-center text-slate-500 py-4">ไม่พบข้อมูลเจ้าหน้าที่ตามเงื่อนไข</td></tr>
                        <?php else: ?>
                            <?php foreach ($admins as $admin): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap md:hidden">
                                    <button class="btn btn-xs btn-square btn-primary inspect-admin-btn" data-id="<?php echo $admin['id']; ?>" title="ตรวจสอบรายละเอียด">
                                        <i class="fa-solid fa-search"></i>
                                    </button>
                                </td>
                                <td data-cell="name" class="font-semibold whitespace-nowrap"><?php echo htmlspecialchars($admin['title'] . ' ' . $admin['firstname'] . ' ' . $admin['lastname']); ?></td>
                                <td data-cell="department" class="whitespace-nowrap"><?php echo htmlspecialchars($admin['department']); ?></td>
                                <td data-cell="role" class="whitespace-nowrap"><div class="badge <?php echo $admin['role'] === 'superadmin' ? 'badge-secondary' : 'badge-primary'; ?>"><?php echo ucfirst(htmlspecialchars($admin['role'])); ?></div></td>
                                <td data-cell="permission" class="whitespace-nowrap"><?php echo $admin['view_permission'] == 1 ? 'ทุกสังกัด' : 'เฉพาะสังกัด'; ?></td>
                                <td data-cell="date" class="whitespace-nowrap" data-sort-value="<?php echo strtotime($admin['created_at']); ?>"><?php echo format_thai_datetime($admin['created_at']); ?></td>
                                <td class="whitespace-nowrap hidden md:table-cell">
                                     <button class="btn btn-xs btn-square btn-primary inspect-admin-btn" data-id="<?php echo $admin['id']; ?>" title="ตรวจสอบรายละเอียด">
                                        <i class="fa-solid fa-search"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <?php if($total_pages > 1): ?>
            <div class="mt-4 flex justify-center">
                <div class="join">
                    <a href="<?php echo get_pagination_link(1, $filters, $sort_by, $sort_dir); ?>" class="join-item btn btn-sm <?php echo ($page <= 1 ? 'btn-disabled' : ''); ?>">«</a>
                    <a href="<?php echo get_pagination_link($page - 1, $filters, $sort_by, $sort_dir); ?>" class="join-item btn btn-sm <?php echo ($page <= 1 ? 'btn-disabled' : ''); ?>">‹</a>
                    <button class="join-item btn btn-sm">หน้า <?php echo $page; ?> / <?php echo $total_pages; ?></button>
                    <a href="<?php echo get_pagination_link($page + 1, $filters, $sort_by, $sort_dir); ?>" class="join-item btn btn-sm <?php echo ($page >= $total_pages ? 'btn-disabled' : ''); ?>">›</a>
                    <a href="<?php echo get_pagination_link($total_pages, $filters, $sort_by, $sort_dir); ?>" class="join-item btn btn-sm <?php echo ($page >= $total_pages ? 'btn-disabled' : ''); ?>">»</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modals remain the same -->
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

