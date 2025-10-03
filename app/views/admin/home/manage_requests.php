<?php
// app/views/admin/home/manage_requests.php
require_once __DIR__ . '/../layouts/header.php';

// --- Fetch Application Periods for the filter dropdown ---
$application_periods = [];
$sql_periods = "SELECT id, period_name FROM application_periods ORDER BY start_date DESC";
$result_periods = $conn->query($sql_periods);
if ($result_periods) {
    while($row = $result_periods->fetch_assoc()) {
        $application_periods[] = $row;
    }
}

// --- Fetch Departments for the filter dropdown ---
$departments = [];
$sql_dept = "SELECT name FROM departments ORDER BY display_order ASC, name ASC";
$result_dept = $conn->query($sql_dept);
if ($result_dept && $result_dept->num_rows > 0) {
    while($row = $result_dept->fetch_assoc()) {
        $departments[] = $row['name'];
    }
}


// --- Filtering & Sorting Logic ---
$filters = [
    'period_id' => $_GET['period_id'] ?? 'all',
    'status' => $_GET['status'] ?? 'all',
    'payment_pickup_status' => $_GET['payment_pickup_status'] ?? 'all',
    'department' => $_GET['department'] ?? 'all',
    'vehicle_type' => $_GET['vehicle_type'] ?? 'all',
    'date_start' => $_GET['date_start'] ?? '',
    'date_end' => $_GET['date_end'] ?? '',
    'user_id' => isset($_GET['user_id']) ? (int)$_GET['user_id'] : 'all',
    'search' => $_GET['search'] ?? ''
];

// Sorting
$sort_by = $_GET['sort'] ?? 'created_at'; // Default sort column
$sort_dir = isset($_GET['dir']) && in_array(strtoupper($_GET['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['dir']) : 'DESC'; // Default direction
$valid_sort_columns = ['search_id', 'status', 'card_number', 'name', 'license_plate', 'created_at', 'card_pickup_date'];
if (!in_array($sort_by, $valid_sort_columns)) {
    $sort_by = 'created_at'; // Fallback to default
}
// Map front-end name to actual DB column(s) for the ORDER BY clause
$sort_column_map = [
    'search_id' => 'vr.search_id',
    'status' => 'vr.status',
    'card_number' => 'vr.card_number',
    'name' => 'u.firstname ' . $sort_dir . ', u.lastname', // Stable sort for name
    'license_plate' => 'v.license_plate',
    'created_at' => 'vr.created_at',
    'card_pickup_date' => 'vr.card_pickup_date'
];
$order_by_sql = "ORDER BY " . $sort_column_map[$sort_by] . " " . $sort_dir;


$where_clauses = [];
$params = [];
$types = '';

if ($filters['period_id'] !== 'all' && is_numeric($filters['period_id'])) {
    $where_clauses[] = 'vr.period_id = ?';
    $params[] = (int)$filters['period_id'];
    $types .= 'i';
}
if ($filters['status'] !== 'all') {
    $where_clauses[] = 'vr.status = ?';
    $params[] = $filters['status'];
    $types .= 's';
}
if ($filters['department'] !== 'all') {
    $where_clauses[] = 'u.work_department = ?';
    $params[] = $filters['department'];
    $types .= 's';
}
if ($filters['vehicle_type'] !== 'all') {
    $where_clauses[] = 'v.vehicle_type = ?';
    $params[] = $filters['vehicle_type'];
    $types .= 's';
}
if ($filters['payment_pickup_status'] === 'paid') {
    $where_clauses[] = "vr.payment_status = 'paid' AND vr.card_pickup_status = 1";
} elseif ($filters['payment_pickup_status'] === 'unpaid') {
    $where_clauses[] = "vr.payment_status = 'unpaid' AND vr.card_pickup_status = 0";
}
if (!empty($filters['date_start'])) {
    $where_clauses[] = 'DATE(vr.created_at) >= ?';
    $params[] = $filters['date_start'];
    $types .= 's';
}
if (!empty($filters['date_end'])) {
    $where_clauses[] = 'DATE(vr.created_at) <= ?';
    $params[] = $filters['date_end'];
    $types .= 's';
}

if ($filters['user_id'] !== 'all') {
    $where_clauses[] = 'vr.user_id = ?';
    $params[] = $filters['user_id'];
    $types .= 'i';
}
if (!empty($filters['search'])) {
    $where_clauses[] = "(vr.search_id LIKE ? OR u.firstname LIKE ? OR u.lastname LIKE ? OR v.license_plate LIKE ?)";
    $search_term = "%" . $filters['search'] . "%";
    array_push($params, $search_term, $search_term, $search_term, $search_term);
    $types .= "ssss";
}


// --- Pagination Logic ---
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$count_where_sql = !empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : "";
$count_sql = "SELECT COUNT(vr.id) as total FROM vehicle_requests vr JOIN users u ON vr.user_id = u.id JOIN vehicles v ON vr.vehicle_id = v.id $count_where_sql";
$stmt_count = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
$stmt_count->close();

// --- Data Fetching ---
$all_requests = [];
$sql_all_requests = "SELECT vr.id, vr.search_id, vr.status, vr.payment_status, vr.card_pickup_status, vr.card_type, vr.card_number, vr.qr_code_path, u.title, u.firstname, u.lastname, u.work_department, v.license_plate, v.province, v.vehicle_type, vr.created_at, vr.card_pickup_date FROM vehicle_requests vr JOIN users u ON vr.user_id = u.id JOIN vehicles v ON vr.vehicle_id = v.id $count_where_sql $order_by_sql LIMIT ? OFFSET ?";
$data_params = $params;
array_push($data_params, $limit, $offset);
$data_types = $types . "ii";

$stmt_all_requests = $conn->prepare($sql_all_requests);
$stmt_all_requests->bind_param($data_types, ...$data_params);
$stmt_all_requests->execute();
$result_all_requests = $stmt_all_requests->get_result();
if ($result_all_requests) {
    while($row = $result_all_requests->fetch_assoc()) {
        $all_requests[] = $row;
    }
}
$stmt_all_requests->close();

// --- Helpers for Links ---
function get_link_with_params($new_params) {
    $current_params = $_GET;
    unset($current_params['page'], $current_params['sort'], $current_params['dir']);
    $final_params = array_merge($current_params, $new_params);
    return '?' . http_build_query($final_params);
}

function get_sort_link($column, $current_sort, $current_dir) {
    $dir = ($current_sort === $column && $current_dir === 'ASC') ? 'desc' : 'asc';
    return get_link_with_params(['page' => 1, 'sort' => $column, 'dir' => $dir]);
}

function get_pagination_link($page, $sort, $dir) {
    return get_link_with_params(['page' => $page, 'sort' => $sort, 'dir' => $dir]);
}

?>

<!-- Page content -->
<main id="manage-requests-page" class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-file-signature text-primary"></i> จัดการคำร้องทั้งหมด</h1>
            <p class="text-slate-500">ตรวจสอบและจัดการคำร้องขอบัตรผ่าน (พบ <?php echo number_format($total_rows); ?> รายการ)</p>
        </div>
        <div class="flex items-center gap-2">
            <button class="btn btn-secondary btn-sm" onclick="bulk_qr_modal.showModal()">
                <i class="fa-solid fa-qrcode"></i> ดาวน์โหลด QR เป็นชุด
            </button>
            <button class="btn btn-info btn-sm" onclick="bulk_payment_modal.showModal()">
                <i class="fa-solid fa-cash-register"></i> ชำระเงิน/รับบัตรเป็นชุด
            </button>
            <button class="btn btn-primary btn-sm" onclick="add_request_modal.showModal()">
                <i class="fa-solid fa-plus"></i> เพิ่มคำร้อง
            </button>
        </div>
    </div>
    
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body p-4">
            <form method="GET" action="" id="filterForm">
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sort_dir); ?>">
                <input type="hidden" name="date_start" id="date_start_hidden" value="<?php echo htmlspecialchars($filters['date_start']); ?>">
                <input type="hidden" name="date_end" id="date_end_hidden" value="<?php echo htmlspecialchars($filters['date_end']); ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-12 gap-4 items-end">
                    <div class="form-control w-full xl:col-span-1">
                        <label class="label py-1"><span class="label-text">รอบการยื่น</span></label>
                        <select name="period_id" class="select select-bordered select-sm filter-input">
                            <option value="all">ทุกรอบ</option>
                            <?php foreach ($application_periods as $period): ?>
                                <option value="<?php echo $period['id']; ?>" <?php echo ($filters['period_id'] == $period['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($period['period_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-control w-full xl:col-span-1">
                        <label class="label py-1"><span class="label-text">สถานะ</span></label>
                        <select name="status" class="select select-bordered select-sm filter-input">
                            <option value="all" <?php echo ($filters['status'] == 'all' ? 'selected' : ''); ?>>ทั้งหมด</option>
                            <option value="pending" <?php echo ($filters['status'] == 'pending' ? 'selected' : ''); ?>>รออนุมัติ</option>
                            <option value="approved" <?php echo ($filters['status'] == 'approved' ? 'selected' : ''); ?>>อนุมัติ</option>
                            <option value="rejected" <?php echo ($filters['status'] == 'rejected' ? 'selected' : ''); ?>>ไม่ผ่าน</option>
                        </select>
                    </div>
                    <div class="form-control w-full xl:col-span-1">
                        <label class="label py-1"><span class="label-text">ประเภทรถ</span></label>
                        <select name="vehicle_type" class="select select-bordered select-sm filter-input">
                            <option value="all" <?php echo ($filters['vehicle_type'] == 'all' ? 'selected' : ''); ?>>ทั้งหมด</option>
                            <option value="รถยนต์" <?php echo ($filters['vehicle_type'] == 'รถยนต์' ? 'selected' : ''); ?>>รถยนต์</option>
                            <option value="รถจักรยานยนต์" <?php echo ($filters['vehicle_type'] == 'รถจักรยานยนต์' ? 'selected' : ''); ?>>รถจักรยานยนต์</option>
                        </select>
                    </div>
                     <div class="form-control w-full xl:col-span-2">
                        <label class="label py-1"><span class="label-text">สังกัด</span></label>
                        <select name="department" class="select select-bordered select-sm filter-input">
                            <option value="all">ทุกสังกัด</option>
                             <?php foreach($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($filters['department'] == $dept ? 'selected' : ''); ?>><?php echo htmlspecialchars($dept); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-control w-full xl:col-span-2">
                        <label class="label py-1"><span class="label-text">สถานะชำระเงิน</span></label>
                        <select name="payment_pickup_status" class="select select-bordered select-sm filter-input">
                            <option value="all" <?php echo ($filters['payment_pickup_status'] == 'all' ? 'selected' : ''); ?>>ทั้งหมด</option>
                            <option value="paid" <?php echo ($filters['payment_pickup_status'] == 'paid' ? 'selected' : ''); ?>>ชำระแล้ว/รับบัตรแล้ว</option>
                            <option value="unpaid" <?php echo ($filters['payment_pickup_status'] == 'unpaid' ? 'selected' : ''); ?>>ยังไม่ชำระ/ยังไม่รับบัตร</option>
                        </select>
                    </div>
                    <div class="form-control w-full xl:col-span-2">
                         <label class="label py-1"><span class="label-text">วันที่ยื่น</span></label>
                         <input type="text" id="date-range-filter" class="input input-sm input-bordered w-full" placeholder="เลือกช่วงวันที่">
                    </div>
                    <div class="form-control w-full xl:col-span-3">
                        <label class="label py-1"><span class="label-text">ค้นหา</span></label>
                        <div class="flex gap-2">
                            <input type="text" name="search" placeholder="รหัส, ชื่อ, ทะเบียนรถ..." class="input input-sm input-bordered w-full filter-input" value="<?php echo htmlspecialchars($filters['search']); ?>">
                            <a href="manage_requests.php" class="btn btn-sm btn-ghost"><i class="fa-solid fa-eraser mr-1"></i></a>
                            <a href="../../../controllers/admin/requests/export_requests.php?<?php echo http_build_query($filters); ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel mr-1"></i></a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="overflow-x-auto mt-4">
                <table class="table table-sm table-pin-rows" id="requestsTable">
                    <thead class="bg-slate-50">
                        <tr>
                            <th><a href="<?php echo get_sort_link('search_id', $sort_by, $sort_dir); ?>">รหัสคำร้อง <i class="fa-solid <?php echo $sort_by === 'search_id' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('status', $sort_by, $sort_dir); ?>">สถานะ <i class="fa-solid <?php echo $sort_by === 'status' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('card_number', $sort_by, $sort_dir); ?>">เลขที่บัตร <i class="fa-solid <?php echo $sort_by === 'card_number' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th class="text-center">ชำระเงิน</th>
                            <th><a href="<?php echo get_sort_link('name', $sort_by, $sort_dir); ?>">ชื่อผู้ยื่น <i class="fa-solid <?php echo $sort_by === 'name' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th>สังกัด</th>
                            <th><a href="<?php echo get_sort_link('license_plate', $sort_by, $sort_dir); ?>">ทะเบียนรถ <i class="fa-solid <?php echo $sort_by === 'license_plate' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th class="text-center">รถ</th>
                            <th>บัตร</th>
                            <th><a href="<?php echo get_sort_link('created_at', $sort_by, $sort_dir); ?>">วันที่ยื่น <i class="fa-solid <?php echo $sort_by === 'created_at' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('card_pickup_date', $sort_by, $sort_dir); ?>">วันที่นัดรับบัตร <i class="fa-solid <?php echo $sort_by === 'card_pickup_date' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th>การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_requests)): ?>
                            <tr id="no-results-row"><td colspan="12" class="text-center text-slate-500 py-4">ไม่พบข้อมูลคำร้องตามเงื่อนไขที่กำหนด</td></tr>
                        <?php else: ?>
                            <?php foreach ($all_requests as $req): ?>
                            <tr class="hover:bg-slate-50" data-request-id="<?php echo $req['id']; ?>">
                                <td class="font-semibold whitespace-nowrap"><?php echo htmlspecialchars($req['search_id']); ?></td>
                                <td class="whitespace-nowrap">
                                    <?php
                                        $status_badge = '';
                                        switch ($req['status']) {
                                            case 'pending': $status_badge = '<div class="badge badge-warning badge-sm">รออนุมัติ</div>'; break;
                                            case 'approved': $status_badge = '<div class="badge badge-success badge-sm">อนุมัติ</div>'; break;
                                            case 'rejected': $status_badge = '<div class="badge badge-error badge-sm">ไม่ผ่าน</div>'; break;
                                        }
                                        echo $status_badge;
                                    ?>
                                </td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['card_number'] ?? '-'); ?></td>
                                <td class="text-center whitespace-nowrap">
                                    <?php if ($req['payment_status'] === 'paid' && $req['card_pickup_status'] == 1): ?>
                                        <i class="fa-solid fa-check-circle text-success"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-times-circle text-error"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['title'] . $req['firstname'] . ' ' . $req['lastname']); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['work_department'] ?? '-'); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['license_plate'] . ' ' . $req['province']); ?></td>
                                <td class="text-center whitespace-nowrap">
                                    <?php if ($req['vehicle_type'] === 'รถยนต์'): ?>
                                        <span class="text-blue-600"><i class="fa-solid fa-car-side" title="รถยนต์"></i></span>
                                    <?php else: ?>
                                        <span class="text-green-600"><i class="fa-solid fa-motorcycle" title="รถจักรยานยนต์"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap"><?php echo $req['card_type'] === 'internal' ? 'ภายใน' : 'ภายนอก'; ?></td>
                                <td class="whitespace-nowrap"><?php echo format_thai_datetime_short($req['created_at']); ?></td>
                                <td class="whitespace-nowrap"><?php echo format_thai_date_short($req['card_pickup_date']); ?></td>
                                <td class="whitespace-nowrap space-x-1">
                                    <button class="btn btn-xs btn-square btn-primary inspect-btn" data-id="<?php echo $req['id']; ?>" title="ตรวจสอบรายละเอียด">
                                        <i class="fa-solid fa-search"></i>
                                    </button>
                                    <?php if ($req['status'] === 'approved'): ?>
                                        <button class="btn btn-xs btn-square <?php echo ($req['payment_status'] !== 'paid') ? 'btn-success' : 'btn-ghost text-success'; ?> payment-btn" data-id="<?php echo $req['id']; ?>" title="<?php echo ($req['payment_status'] !== 'paid') ? 'ชำระเงิน/รับบัตร' : 'ดูข้อมูล'; ?>">
                                            <i class="fa-solid <?php echo ($req['payment_status'] !== 'paid') ? 'fa-hand-holding-dollar' : 'fa-circle-check'; ?>"></i>
                                        </button>
                                        <?php if (!empty($req['qr_code_path'])): ?>
                                            <a href="../../../controllers/admin/requests/download_qr.php?file=<?php echo urlencode($req['qr_code_path']); ?>" class="btn btn-xs btn-square btn-accent" title="ดาวน์โหลด QR Code">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
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
                    <a href="<?php echo get_pagination_link(1, $sort_by, $sort_dir); ?>" class="join-item btn btn-sm <?php echo ($page <= 1 ? 'btn-disabled' : ''); ?>">«</a>
                    <a href="<?php echo get_pagination_link($page - 1, $sort_by, $sort_dir); ?>" class="join-item btn btn-sm <?php echo ($page <= 1 ? 'btn-disabled' : ''); ?>">‹</a>
                    <button class="join-item btn btn-sm">หน้า <?php echo $page; ?> / <?php echo $total_pages; ?></button>
                    <a href="<?php echo get_pagination_link($page + 1, $sort_by, $sort_dir); ?>" class="join-item btn btn-sm <?php echo ($page >= $total_pages ? 'btn-disabled' : ''); ?>">›</a>
                    <a href="<?php echo get_pagination_link($total_pages, $sort_by, $sort_dir); ?>" class="join-item btn btn-sm <?php echo ($page >= $total_pages ? 'btn-disabled' : ''); ?>">»</a>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<!-- Modal for selecting user -->
<dialog id="add_request_modal" class="modal modal-fade">
    <div class="modal-box">
        <h3 class="font-bold text-lg">ขั้นตอนที่ 1: เลือกผู้ใช้งาน</h3>
        <p class="py-2 text-sm">กรุณาค้นหาและเลือกผู้ใช้ที่ต้องการสร้างคำร้องให้</p>
        <form id="selectUserForm" method="GET" action="add_request.php">
            <div class="form-control w-full mt-4">
                <select id="user-search-select" name="user_id" class="w-full" required>
                    <option></option>
                </select>
                <p class="text-xs text-error mt-1 hidden" id="user-select-error">กรุณาเลือกผู้ใช้งาน</p>
            </div>
            <div class="modal-action">
                <button type="button" class="btn btn-sm" onclick="add_request_modal.close()">ยกเลิก</button>
                <button type="submit" class="btn btn-sm btn-primary">ถัดไป <i class="fa-solid fa-arrow-right"></i></button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>


<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

