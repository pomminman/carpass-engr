<?php
// app/views/admin/home/dashboard.php
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

// --- Determine the selected period from URL, default to 'all' ---
$selected_period_id = $_GET['period_id'] ?? 'all';

// --- Sorting Logic ---
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_dir = isset($_GET['dir']) && in_array(strtoupper($_GET['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['dir']) : 'ASC';
$valid_sort_columns = ['search_id', 'name', 'department', 'license', 'type', 'created_at', 'card_pickup_date'];
if (!in_array($sort_by, $valid_sort_columns)) {
    $sort_by = 'created_at';
}
// Map front-end names to actual DB columns for a stable sort
$sort_column_map = [
    'search_id' => 'vr.search_id',
    'name' => 'u.firstname ' . $sort_dir . ', u.lastname',
    'department' => 'u.work_department',
    'license' => 'v.license_plate',
    'type' => 'v.vehicle_type',
    'created_at' => 'vr.created_at',
    'card_pickup_date' => 'vr.card_pickup_date'
];
$order_by_sql = "ORDER BY " . $sort_column_map[$sort_by] . " " . $sort_dir;


// --- Build dynamic WHERE clauses for SQL queries based on selection ---
$where_clauses = [];
$params = [];
$types = '';

if ($selected_period_id !== 'all' && is_numeric($selected_period_id)) {
    $where_clauses[] = 'vr.period_id = ?';
    $params[] = (int)$selected_period_id;
    $types .= 'i';
}

$where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);

// --- Data fetching specific to this page ---
$stats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'expired' => 0,
    'total_users' => 0,
    'total_requests' => 0,
];

// Fetch total users count (this is a global stat, not filtered by period)
$result_users = $conn->query("SELECT COUNT(id) as count FROM users");
if($result_users) {
    $stats['total_users'] = $result_users->fetch_assoc()['count'];
}

// [REVISE] Fetch total requests count with period filter
$sql_total_requests = "SELECT COUNT(id) as count FROM vehicle_requests vr " . ($where_sql ? str_replace('vr.','',$where_sql) : '');
$stmt_total_requests = $conn->prepare($sql_total_requests);
if (!empty($params)) {
    $stmt_total_requests->bind_param($types, ...$params);
}
$stmt_total_requests->execute();
$result_total_requests = $stmt_total_requests->get_result();
if($result_total_requests) {
    $stats['total_requests'] = $result_total_requests->fetch_assoc()['count'];
}
$stmt_total_requests->close();


// [REVISE] Fetch status counts with period filter
$stats_where_clauses = [];
$stats_params = [];
$stats_types = ''; 

if ($selected_period_id !== 'all' && is_numeric($selected_period_id)) {
    $stats_where_clauses[] = 'period_id = ?';
    $stats_params[] = (int)$selected_period_id;
    $stats_types .= 'i';
}
$stats_where_sql = empty($stats_where_clauses) ? '' : 'WHERE ' . implode(' AND ', $stats_where_clauses);
$sql_stats = "SELECT status, COUNT(id) as count FROM vehicle_requests " . $stats_where_sql . " GROUP BY status";
$stmt_stats = $conn->prepare($sql_stats);
if(!empty($stats_params)){
    $stmt_stats->bind_param($stats_types, ...$stats_params);
}
$stmt_stats->execute();
$result_stats = $stmt_stats->get_result();
if($result_stats) {
    while($row = $result_stats->fetch_assoc()){
        if(isset($stats[$row['status']])) {
            $stats[$row['status']] = $row['count'];
        }
    }
}
$stmt_stats->close();


// [REVISE] Fetch expired counts with period filter
$expired_where_clauses = [];
$expired_params = [];
$expired_types = '';
if ($selected_period_id !== 'all' && is_numeric($selected_period_id)) {
    $expired_where_clauses[] = 'vr.period_id = ?';
    $expired_params[] = (int)$selected_period_id;
    $expired_types .= 'i';
}
$expired_where_clauses[] = "vr.status = 'approved'";
$expired_where_clauses[] = "vr.card_expiry < CURDATE()";
$expired_where_sql = 'WHERE ' . implode(' AND ', $expired_where_clauses);

$sql_expired = "SELECT COUNT(vr.id) as count FROM vehicle_requests vr " . $expired_where_sql;
$stmt_expired = $conn->prepare($sql_expired);
if(!empty($expired_params)){
    $stmt_expired->bind_param($expired_types, ...$expired_params);
}
$stmt_expired->execute();
$result_expired = $stmt_expired->get_result();
if($result_expired) {
    $stats['expired'] = $result_expired->fetch_assoc()['count'];
}
$stmt_expired->close();


// [REVISE] Fetch pending requests for the table with period filter
$pending_where_clauses = $where_clauses;
$pending_where_clauses[] = "vr.status = 'pending'";
$pending_where_sql = 'WHERE ' . implode(' AND ', $pending_where_clauses);

$pending_requests = [];
$sql_pending_requests = "SELECT 
                            vr.id, vr.search_id, vr.card_type,
                            u.title, u.firstname, u.lastname, u.work_department, 
                            v.license_plate, v.province, v.vehicle_type, 
                            vr.created_at, vr.status, vr.card_pickup_date
                         FROM vehicle_requests vr
                         JOIN users u ON vr.user_id = u.id
                         JOIN vehicles v ON vr.vehicle_id = v.id
                         $pending_where_sql
                         $order_by_sql";
$stmt_pending = $conn->prepare($sql_pending_requests);
if(!empty($params)){
    $stmt_pending->bind_param($types, ...$params);
}
$stmt_pending->execute();
$result_pending_requests = $stmt_pending->get_result();
if ($result_pending_requests) {
    while($row = $result_pending_requests->fetch_assoc()) {
        $pending_requests[] = $row;
    }
}
$stmt_pending->close();

// --- Fetch viewed status for pending requests ---
$viewed_request_ids = [];
$sql_viewed = "SELECT DISTINCT details FROM activity_logs WHERE action = 'admin_view_details' AND admin_id IS NOT NULL";
$result_viewed = $conn->query($sql_viewed);
if ($result_viewed) {
    while ($row = $result_viewed->fetch_assoc()) {
        $details_data = json_decode($row['details'], true);
        if (isset($details_data['request_id'])) {
            $viewed_request_ids[$details_data['request_id']] = true;
        }
    }
}

// --- Helper for Links ---
function get_sort_link($column, $current_sort, $current_dir, $filters) {
    $dir = ($current_sort === $column && $current_dir === 'ASC') ? 'desc' : 'asc';
    $query_params = array_merge($filters, ['sort' => $column, 'dir' => $dir]);
    return '?' . http_build_query($query_params);
}

?>

<!-- Page content -->
<main id="dashboard-page" class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-4">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-tachometer-alt text-primary"></i> Dashboard ภาพรวม</h1>
            <p class="text-slate-500">สรุปข้อมูลและคำร้องที่รอการตรวจสอบ</p>
        </div>
        <form method="GET" action="" class="w-full sm:w-auto">
            <div class="form-control">
                <select name="period_id" class="select select-bordered select-sm" onchange="this.form.submit()">
                    <option value="all">ดูข้อมูลทุกรอบ</option>
                    <?php foreach ($application_periods as $period): ?>
                        <option value="<?php echo $period['id']; ?>" <?php echo ($selected_period_id == $period['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($period['period_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-6 gap-2 sm:gap-4">
        <div class="card bg-blue-500 text-blue-100 shadow-lg">
            <div class="card-body p-3 sm:p-4 flex-row items-center gap-2 sm:gap-4">
                <i class="fas fa-users text-2xl sm:text-4xl opacity-80"></i>
                <div>
                    <p class="text-xl sm:text-3xl font-bold"><?php echo number_format($stats['total_users']); ?></p>
                    <p class="text-xs sm:text-sm opacity-90">ผู้ใช้ทั้งหมด</p>
                </div>
            </div>
        </div>
        <div class="card bg-slate-600 text-slate-100 shadow-lg">
            <div class="card-body p-3 sm:p-4 flex-row items-center gap-2 sm:gap-4">
                <i class="fas fa-file-alt text-2xl sm:text-4xl opacity-80"></i>
                <div>
                    <p class="text-xl sm:text-3xl font-bold"><?php echo number_format($stats['total_requests']); ?></p>
                    <p class="text-xs sm:text-sm opacity-90">คำร้องทั้งหมด</p>
                </div>
            </div>
        </div>
        <div class="card bg-amber-500 text-white shadow-lg">
            <div class="card-body p-3 sm:p-4 flex-row items-center gap-2 sm:gap-4">
                <i class="fas fa-clock text-2xl sm:text-4xl opacity-80"></i>
                <div>
                    <p class="text-xl sm:text-3xl font-bold"><?php echo number_format($stats['pending']); ?></p>
                    <p class="text-xs sm:text-sm opacity-90">รออนุมัติ</p>
                </div>
            </div>
        </div>
        <div class="card bg-green-500 text-white shadow-lg">
            <div class="card-body p-3 sm:p-4 flex-row items-center gap-2 sm:gap-4">
                <i class="fas fa-check-circle text-2xl sm:text-4xl opacity-80"></i>
                <div>
                    <p class="text-xl sm:text-3xl font-bold"><?php echo number_format($stats['approved']); ?></p>
                    <p class="text-xs sm:text-sm opacity-90">อนุมัติแล้ว</p>
                </div>
            </div>
        </div>
        <div class="card bg-red-500 text-white shadow-lg">
            <div class="card-body p-3 sm:p-4 flex-row items-center gap-2 sm:gap-4">
                <i class="fas fa-times-circle text-2xl sm:text-4xl opacity-80"></i>
                <div>
                    <p class="text-xl sm:text-3xl font-bold"><?php echo number_format($stats['rejected']); ?></p>
                    <p class="text-xs sm:text-sm opacity-90">ไม่ผ่าน</p>
                </div>
            </div>
        </div>
        <div class="card bg-gray-400 text-white shadow-lg">
            <div class="card-body p-3 sm:p-4 flex-row items-center gap-2 sm:gap-4">
                <i class="fas fa-calendar-times text-2xl sm:text-4xl opacity-80"></i>
                <div>
                    <p class="text-xl sm:text-3xl font-bold"><?php echo number_format($stats['expired']); ?></p>
                    <p class="text-xs sm:text-sm opacity-90">หมดอายุ</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-lg mt-6">
        <div class="card-body p-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                 <h2 class="card-title flex items-center gap-2 text-lg"><i class="fa-solid fa-inbox text-slate-600"></i> รายการคำร้องรออนุมัติ</h2>
                 <div class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="text" id="searchInput" placeholder="ค้นหา..." class="input input-sm input-bordered w-full sm:w-auto">
                </div>
            </div>
            <div class="overflow-x-auto mt-4">
                <table class="table table-sm" id="requestsTable">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="md:hidden">การกระทำ</th>
                            <th><a href="<?php echo get_sort_link('search_id', $sort_by, $sort_dir, ['period_id' => $selected_period_id]); ?>">รหัสคำร้อง <i class="fa-solid <?php echo $sort_by === 'search_id' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('name', $sort_by, $sort_dir, ['period_id' => $selected_period_id]); ?>">ชื่อผู้ยื่น <i class="fa-solid <?php echo $sort_by === 'name' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('department', $sort_by, $sort_dir, ['period_id' => $selected_period_id]); ?>">สังกัด <i class="fa-solid <?php echo $sort_by === 'department' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('license', $sort_by, $sort_dir, ['period_id' => $selected_period_id]); ?>">ทะเบียนรถ <i class="fa-solid <?php echo $sort_by === 'license' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('type', $sort_by, $sort_dir, ['period_id' => $selected_period_id]); ?>">รถ <i class="fa-solid <?php echo $sort_by === 'type' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th>บัตร</th>
                            <th><a href="<?php echo get_sort_link('created_at', $sort_by, $sort_dir, ['period_id' => $selected_period_id]); ?>">วันที่ยื่น <i class="fa-solid <?php echo $sort_by === 'created_at' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th><a href="<?php echo get_sort_link('card_pickup_date', $sort_by, $sort_dir, ['period_id' => $selected_period_id]); ?>">วันที่นัดรับบัตร <i class="fa-solid <?php echo $sort_by === 'card_pickup_date' ? 'fa-sort-' . strtolower($sort_dir) : 'fa-sort'; ?>"></i></a></th>
                            <th class="hidden md:table-cell">การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_requests)): ?>
                            <tr><td colspan="10" class="text-center text-slate-500 py-4">ไม่พบรายการที่รอการอนุมัติ<?php echo ($selected_period_id !== 'all' ? 'ในรอบนี้' : ''); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($pending_requests as $req): ?>
                            <tr class="hover:bg-slate-50" data-request-id="<?php echo $req['id']; ?>" data-status="pending">
                                <td class="whitespace-nowrap md:hidden">
                                    <button class="btn btn-xs btn-square btn-primary inspect-btn" data-id="<?php echo $req['id']; ?>" title="ตรวจสอบรายละเอียด">
                                        <i class="fa-solid fa-search"></i>
                                    </button>
                                </td>
                                <td data-cell="search_id" class="font-semibold whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php 
                                            if (!isset($viewed_request_ids[$req['id']])) {
                                                echo '<i class="fa-solid fa-bell text-warning animate-pulse mr-2 notification-badge" title="ยังไม่ได้อ่าน"></i>';
                                            }
                                        ?>
                                        <span><?php echo htmlspecialchars($req['search_id']); ?></span>
                                    </div>
                                </td>
                                <td data-cell="name" class="whitespace-nowrap"><?php echo htmlspecialchars($req['title'] . ' ' . $req['firstname'] . '  ' . $req['lastname']); ?></td>
                                <td data-cell="department" class="whitespace-nowrap"><?php echo htmlspecialchars($req['work_department'] ?? '-'); ?></td>
                                <td data-cell="license" class="whitespace-nowrap"><?php echo htmlspecialchars($req['license_plate'] . ' ' . $req['province']); ?></td>
                                <td data-cell="type" class="text-center whitespace-nowrap">
                                    <?php if ($req['vehicle_type'] === 'รถยนต์'): ?>
                                        <span class="text-blue-600"><i class="fa-solid fa-car-side" title="รถยนต์"></i></span>
                                    <?php else: ?>
                                        <span class="text-green-600"><i class="fa-solid fa-motorcycle" title="รถจักรยานยนต์"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap"><?php echo $req['card_type'] === 'internal' ? 'ภายใน' : 'ภายนอก'; ?></td>
                                <td data-cell="date" class="whitespace-nowrap" data-sort-value="<?php echo strtotime($req['created_at']); ?>"><?php echo format_thai_datetime_short($req['created_at']); ?></td>
                                <td data-cell="pickup_date" class="whitespace-nowrap font-semibold text-info" data-sort-value="<?php echo strtotime($req['card_pickup_date']); ?>"><?php echo format_thai_date_short($req['card_pickup_date']); ?></td>
                                <td class="whitespace-nowrap hidden md:table-cell">
                                    <button class="btn btn-xs btn-square btn-primary inspect-btn" data-id="<?php echo $req['id']; ?>" title="ตรวจสอบรายละเอียด">
                                        <i class="fa-solid fa-search"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <tr id="no-results-row" class="hidden"><td colspan="10" class="text-center text-slate-500 py-4">ไม่พบข้อมูลคำร้องที่ค้นหา</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
