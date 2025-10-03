<?php
// app/views/admin/home/manage_users.php
require_once __DIR__ . '/../layouts/header.php';

// --- 1. INITIALIZE VARIABLES ---
$departments = [];
$sql_dept = "SELECT name FROM departments ORDER BY display_order ASC, name ASC";
$result_dept = $conn->query($sql_dept);
if ($result_dept->num_rows > 0) {
    while($row = $result_dept->fetch_assoc()) {
        $departments[] = $row['name'];
    }
}

// --- 2. FILTERING LOGIC ---
$filters = [
    'type' => $_GET['type'] ?? 'all',
    'department' => $_GET['department'] ?? 'all',
    'date' => $_GET['date'] ?? 'all',
    'vehicles' => $_GET['vehicles'] ?? 'all',
    'search' => $_GET['search'] ?? ''
];

$sort_by = $_GET['sort'] ?? 'created_at';
$sort_dir = isset($_GET['dir']) && in_array(strtoupper($_GET['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['dir']) : 'DESC';
$valid_sort_columns = ['name', 'user_type', 'vehicle_count', 'phone_number', 'national_id', 'work_department', 'created_at'];
if (!in_array($sort_by, $valid_sort_columns)) {
    $sort_by = 'created_at';
}
$order_by_sql = "ORDER BY " . $sort_by . " " . $sort_dir;


$where_clauses = [];
$params = [];
$types = '';

if ($filters['type'] !== 'all') {
    $where_clauses[] = 'u.user_type = ?';
    $params[] = $filters['type'];
    $types .= 's';
}
if ($filters['department'] !== 'all') {
    $where_clauses[] = 'u.work_department = ?';
    $params[] = $filters['department'];
    $types .= 's';
}
if ($filters['date'] !== 'all' && !empty($filters['date'])) {
    $where_clauses[] = 'DATE(u.created_at) = ?';
    $params[] = $filters['date'];
    $types .= 's';
}
$having_clauses = [];
if ($filters['vehicles'] !== 'all') {
    $having_clauses[] = ($filters['vehicles'] === 'yes') ? 'COUNT(vr.id) > 0' : 'COUNT(vr.id) = 0';
}
if (!empty($filters['search'])) {
    $where_clauses[] = "(u.firstname LIKE ? OR u.lastname LIKE ? OR u.phone_number LIKE ? OR u.national_id LIKE ?)";
    $search_term = "%" . $filters['search'] . "%";
    array_push($params, $search_term, $search_term, $search_term, $search_term);
    $types .= "ssss";
}


// --- 3. PAGINATION LOGIC ---
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; 
$offset = ($page - 1) * $limit;

$count_sql_where = !empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : "";
$count_sql_having = !empty($having_clauses) ? "HAVING " . implode(' AND ', $having_clauses) : "";

$count_sql = "SELECT COUNT(*) as total FROM (
                SELECT u.id 
                FROM users u 
                LEFT JOIN vehicle_requests vr ON u.id = vr.user_id 
                $count_sql_where
                GROUP BY u.id 
                $count_sql_having
              ) as subquery";

$stmt_count = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
$stmt_count->close();


// --- 4. DATA FETCHING ---
$users = [];
$sql_users = "SELECT 
                u.id, u.title, u.firstname, u.lastname, u.user_type,
                u.phone_number, u.national_id, u.work_department, u.created_at,
                COUNT(vr.id) as vehicle_count
              FROM users u
              LEFT JOIN vehicle_requests vr ON u.id = vr.user_id
              " . (!empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : "") . "
              GROUP BY u.id
              " . (!empty($having_clauses) ? "HAVING " . implode(' AND ', $having_clauses) : "") . "
              " . $order_by_sql . "
              LIMIT ? OFFSET ?";

$data_params = $params;
array_push($data_params, $limit, $offset);
$data_types = $types . "ii";

$stmt_users = $conn->prepare($sql_users);
$stmt_users->bind_param($data_types, ...$data_params);
$stmt_users->execute();
$result_users = $stmt_users->get_result();
if ($result_users->num_rows > 0) {
    while($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
}
$stmt_users->close();

// --- 5. HELPER FOR PAGINATION & SORTING LINKS ---
function get_pagination_link($page, $filters, $sort, $dir) {
    $query = http_build_query(array_merge($filters, ['page' => $page, 'sort' => $sort, 'dir' => $dir]));
    return '?' . $query;
}

function get_sort_link($column, $current_sort, $current_dir, $filters) {
    $dir = ($current_sort === $column && $current_dir === 'ASC') ? 'desc' : 'asc';
    $query_params = array_merge($filters, ['page' => 1, 'sort' => $column, 'dir' => $dir]);
    return '?' . http_build_query($query_params);
}

?>

<!-- Page content -->
<main id="manage-users-page" class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-users-cog text-primary"></i> จัดการผู้ใช้งาน</h1>
            <p class="text-slate-500">ดูและจัดการข้อมูลผู้ใช้งานในระบบ (พบ <?php echo number_format($total_rows); ?> รายการ)</p>
        </div>
        <div class="w-full sm:w-auto">
            <a href="add_user.php" class="btn btn-primary btn-sm w-full sm:w-auto"><i class="fa-solid fa-user-plus"></i> เพิ่มผู้ใช้งาน</a>
        </div>
    </div>
    
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body p-4">
            <form method="GET" action="" id="filterForm">
            <input type="hidden" name="page" value="1">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
            <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sort_dir); ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 items-end">
                <!-- Main Filters -->
                <div class="form-control w-full">
                    <label class="label py-1"><span class="label-text">ประเภทผู้สมัคร</span></label>
                    <select name="type" class="select select-bordered select-sm filter-input">
                        <option value="all" <?php echo ($filters['type'] == 'all' ? 'selected' : ''); ?>>ทั้งหมด</option>
                        <option value="army" <?php echo ($filters['type'] == 'army' ? 'selected' : ''); ?>>กำลังพล ทบ.</option>
                        <option value="external" <?php echo ($filters['type'] == 'external' ? 'selected' : ''); ?>>บุคคลภายนอก</option>
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
                <div class="form-control w-full">
                    <label class="label py-1"><span class="label-text">วันที่สมัคร</span></label>
                    <input type="date" name="date" class="input input-sm input-bordered w-full filter-input" value="<?php echo htmlspecialchars($filters['date'] !== 'all' ? $filters['date'] : ''); ?>">
                </div>
                <div class="form-control w-full">
                    <label class="label py-1"><span class="label-text">จำนวนยานพาหนะ</span></label>
                    <select name="vehicles" class="select select-bordered select-sm filter-input">
                        <option value="all" <?php echo ($filters['vehicles'] == 'all' ? 'selected' : ''); ?>>ทั้งหมด</option>
                        <option value="yes" <?php echo ($filters['vehicles'] == 'yes' ? 'selected' : ''); ?>>มียานพาหนะ</option>
                        <option value="no" <?php echo ($filters['vehicles'] == 'no' ? 'selected' : ''); ?>>ไม่มียานพาหนะ</option>
                    </select>
                </div>
                <!-- Search and Actions -->
                <div class="form-control w-full lg:col-span-2">
                    <label class="label py-1"><span class="label-text">ค้นหา</span></label>
                    <div class="flex gap-2">
                        <input type="text" name="search" placeholder="ชื่อ, เบอร์โทร, เลขบัตร..." class="input input-sm input-bordered w-full filter-input" value="<?php echo htmlspecialchars($filters['search']); ?>">
                        <a href="manage_users.php" class="btn btn-sm btn-ghost"><i class="fa-solid fa-eraser mr-1"></i></a>
                        <a href="../../../controllers/admin/users/export_users.php?<?php echo http_build_query($filters); ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel mr-1"></i></a>
                    </div>
                </div>
            </div>
            </form>

            <div class="overflow-x-auto mt-4">
                <table class="table table-sm" id="usersTable">
                     <thead class="bg-slate-50">
                        <tr>
                            <th class="md:hidden">การกระทำ</th>
                            <th><a href="<?php echo get_sort_link('name', $sort_by, $sort_dir, $filters); ?>">ชื่อ-นามสกุล <i class="fa-solid <?php echo $sort_by === 'name' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('user_type', $sort_by, $sort_dir, $filters); ?>">ประเภท <i class="fa-solid <?php echo $sort_by === 'user_type' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th class="text-center"><a href="<?php echo get_sort_link('vehicle_count', $sort_by, $sort_dir, $filters); ?>">ยานพาหนะ <i class="fa-solid <?php echo $sort_by === 'vehicle_count' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('phone_number', $sort_by, $sort_dir, $filters); ?>">เบอร์โทรศัพท์ <i class="fa-solid <?php echo $sort_by === 'phone_number' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('national_id', $sort_by, $sort_dir, $filters); ?>">เลขบัตรประชาชน <i class="fa-solid <?php echo $sort_by === 'national_id' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('work_department', $sort_by, $sort_dir, $filters); ?>">สังกัด <i class="fa-solid <?php echo $sort_by === 'work_department' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('created_at', $sort_by, $sort_dir, $filters); ?>">วันที่สมัคร <i class="fa-solid <?php echo $sort_by === 'created_at' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th class="hidden md:table-cell">การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr id="no-results-row"><td colspan="9" class="text-center text-slate-500 py-4">ไม่พบข้อมูลผู้ใช้งานตามเงื่อนไขที่กำหนด</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap md:hidden">
                                    <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-xs btn-primary btn-square" title="ตรวจสอบ">
                                        <i class="fa-solid fa-search"></i> 
                                    </a>
                                </td>
                                <td data-cell="name" class="font-semibold whitespace-nowrap"><?php echo htmlspecialchars($user['title'] . ' ' . $user['firstname'] . ' ' . $user['lastname']); ?></td>
                                <td data-cell="type" class="whitespace-nowrap">
                                    <?php if ($user['user_type'] === 'army'): ?>
                                        <div class="badge badge-success badge-outline gap-2"><i class="fa-solid fa-shield-halved"></i>กำลังพล ทบ.</div>
                                    <?php else: ?>
                                        <div class="badge badge-info badge-outline gap-2"><i class="fa-solid fa-user-group"></i>บุคคลภายนอก</div>
                                    <?php endif; ?>
                                </td>
                                <td data-cell="vehicles" class="text-center whitespace-nowrap" data-sort-value="<?php echo $user['vehicle_count']; ?>">
                                    <a href="manage_requests.php?user_id=<?php echo $user['id']; ?>" class="link link-primary" title="ดูคำร้องของผู้ใช้นี้">
                                        <div class="badge badge-ghost"><?php echo $user['vehicle_count']; ?> คัน</div>
                                    </a>
                                </td>
                                <td data-cell="phone" class="whitespace-nowrap"><?php echo htmlspecialchars($user['phone_number'] ?: '-'); ?></td>
                                <td data-cell="nid" class="whitespace-nowrap"><?php echo htmlspecialchars($user['national_id'] ?: '-'); ?></td>
                                <td data-cell="department" class="whitespace-nowrap"><?php echo htmlspecialchars($user['work_department'] ?? '-'); ?></td>
                                <td data-cell="date" class="whitespace-nowrap" data-sort-value="<?php echo strtotime($user['created_at']); ?>"><?php echo format_thai_datetime_short($user['created_at']); ?></td>
                                <td class="whitespace-nowrap hidden md:table-cell">
                                    <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-xs btn-primary btn-square" title="ตรวจสอบ">
                                        <i class="fa-solid fa-search"></i> 
                                    </a>
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

