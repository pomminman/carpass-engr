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

// (Other data fetching logic remains the same)
$selected_period_id = $_GET['period_id'] ?? 'all';
$selected_status = $_GET['status'] ?? 'all';
// [New] Filter by user_id
$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 'all';

$where_clauses = [];
$params = [];
$types = '';

if ($selected_period_id !== 'all' && is_numeric($selected_period_id)) {
    $where_clauses[] = 'vr.period_id = ?';
    $params[] = (int)$selected_period_id;
    $types .= 'i';
}
if ($selected_status !== 'all') {
    $where_clauses[] = 'vr.status = ?';
    $params[] = $selected_status;
    $types .= 's';
}
// [New] Add user_id to where clause
if ($selected_user_id !== 'all') {
    $where_clauses[] = 'vr.user_id = ?';
    $params[] = $selected_user_id;
    $types .= 'i';
}


$where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);
$stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
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
$stats['total'] = $stats['pending'] + $stats['approved'] + $stats['rejected'];
$all_requests = [];
$sql_all_requests = "SELECT vr.id, vr.search_id, vr.card_number, u.title, u.firstname, u.lastname, v.license_plate, v.province, v.vehicle_type, vr.created_at, vr.status FROM vehicle_requests vr JOIN users u ON vr.user_id = u.id JOIN vehicles v ON vr.vehicle_id = v.id $where_sql ORDER BY vr.created_at DESC";
$stmt_all_requests = $conn->prepare($sql_all_requests);
if(!empty($params)){
    $stmt_all_requests->bind_param($types, ...$params);
}
$stmt_all_requests->execute();
$result_all_requests = $stmt_all_requests->get_result();
if ($result_all_requests) {
    while($row = $result_all_requests->fetch_assoc()) {
        $all_requests[] = $row;
    }
}
$stmt_all_requests->close();
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

?>

<!-- Page content -->
<main id="manage-requests-page" class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-file-signature text-primary"></i> จัดการคำร้องทั้งหมด</h1>
            <p class="text-slate-500">ตรวจสอบและจัดการคำร้องขอบัตรผ่านทั้งหมดในระบบ</p>
        </div>
        <div class="w-full sm:w-auto">
            <button class="btn btn-primary btn-sm w-full sm:w-auto" onclick="add_request_modal.showModal()">
                <i class="fa-solid fa-plus"></i> เพิ่มคำร้อง
            </button>
        </div>
    </div>
    
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="tabs tabs-boxed">
                    <a href="?period_id=<?php echo $selected_period_id; ?>&status=all" class="tab tab-sm sm:tab-md <?php echo ($selected_status == 'all' ? 'tab-active' : ''); ?>">ทั้งหมด</a>
                    <a href="?period_id=<?php echo $selected_period_id; ?>&status=pending" class="tab tab-sm sm:tab-md <?php echo ($selected_status == 'pending' ? 'tab-active' : ''); ?>">รออนุมัติ</a>
                    <a href="?period_id=<?php echo $selected_period_id; ?>&status=approved" class="tab tab-sm sm:tab-md <?php echo ($selected_status == 'approved' ? 'tab-active' : ''); ?>">อนุมัติแล้ว</a>
                    <a href="?period_id=<?php echo $selected_period_id; ?>&status=rejected" class="tab tab-sm sm:tab-md <?php echo ($selected_status == 'rejected' ? 'tab-active' : ''); ?>">ไม่ผ่าน</a>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <form method="GET" action="" class="w-full sm:w-auto">
                        <input type="hidden" name="status" value="<?php echo $selected_status; ?>">
                        <select name="period_id" class="select select-bordered select-sm" onchange="this.form.submit()">
                            <option value="all">ทุกรอบการยื่น</option>
                            <?php foreach ($application_periods as $period): ?>
                                <option value="<?php echo $period['id']; ?>" <?php echo ($selected_period_id == $period['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($period['period_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                   <input type="text" id="searchInput" placeholder="ค้นหา..." class="input input-sm input-bordered w-full sm:w-auto">
                </div>
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="table table-sm" id="requestsTable">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="md:hidden">การกระทำ</th>
                            <th data-sort-by="search_id">รหัสคำร้อง <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="status">สถานะ <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="card_number">เลขที่บัตร <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="name">ชื่อผู้ยื่น <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="license">ทะเบียนรถ <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="type">ประเภทรถ <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="date" class="sort-desc">วันที่ยื่น <i class="fa-solid fa-sort-down"></i></th>
                            <th class="hidden md:table-cell">การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_requests)): ?>
                            <tr><td colspan="9" class="text-center text-slate-500 py-4">ไม่พบข้อมูลคำร้องในระบบ</td></tr>
                        <?php else: ?>
                            <?php foreach ($all_requests as $req): ?>
                            <tr class="hover:bg-slate-50" data-request-id="<?php echo $req['id']; ?>" data-status="<?php echo $req['status']; ?>">
                                <td class="whitespace-nowrap md:hidden">
                                    <button class="btn btn-xs btn-primary inspect-btn" data-id="<?php echo $req['id']; ?>">
                                        <span><i class="fa-solid fa-search mr-1"></i>ตรวจสอบ</span>
                                    </button>
                                </td>
                                <td data-cell="search_id" class="font-semibold whitespace-nowrap">
                                    <div class="flex items-center">
                                         <?php 
                                            if ($req['status'] === 'pending' && !isset($viewed_request_ids[$req['id']])) {
                                                echo '<i class="fa-solid fa-bell text-warning animate-pulse mr-2 notification-badge" title="ยังไม่ได้อ่าน"></i>';
                                            }
                                        ?>
                                        <span><?php echo htmlspecialchars($req['search_id']); ?></span>
                                    </div>
                                </td>
                                <td data-cell="status" class="whitespace-nowrap">
                                    <?php
                                        $status_badge = '';
                                        switch ($req['status']) {
                                            case 'pending': $status_badge = '<div class="badge badge-warning">รออนุมัติ</div>'; break;
                                            case 'approved': $status_badge = '<div class="badge badge-success">อนุมัติแล้ว</div>'; break;
                                            case 'rejected': $status_badge = '<div class="badge badge-error">ไม่ผ่าน</div>'; break;
                                        }
                                        echo $status_badge;
                                    ?>
                                </td>
                                <td data-cell="card_number" data-sort-value="<?php echo (int)$req['card_number']; ?>" class="whitespace-nowrap"><?php echo htmlspecialchars($req['card_number'] ?: '-'); ?></td>
                                <td data-cell="name" class="whitespace-nowrap"><?php echo htmlspecialchars($req['title'] . ' ' . $req['firstname'] . ' ' . $req['lastname']); ?></td>
                                <td data-cell="license" class="whitespace-nowrap"><?php echo htmlspecialchars($req['license_plate'] . ' ' . $req['province']); ?></td>
                                <td data-cell="type" class="whitespace-nowrap"><?php echo htmlspecialchars($req['vehicle_type']); ?></td>
                                <td data-cell="date" class="whitespace-nowrap" data-sort-value="<?php echo strtotime($req['created_at']); ?>"><?php echo format_thai_datetime($req['created_at']); ?></td>
                                <td class="whitespace-nowrap hidden md:table-cell">
                                    <button class="btn btn-xs btn-primary inspect-btn" data-id="<?php echo $req['id']; ?>">
                                        <span><i class="fa-solid fa-search mr-1"></i>ตรวจสอบ</span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <tr id="no-search-results-row" class="hidden"><td colspan="9" class="text-center text-slate-500 py-4">ไม่พบข้อมูลคำร้องที่ค้นหา</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- [NEW] Modal for selecting user -->
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

