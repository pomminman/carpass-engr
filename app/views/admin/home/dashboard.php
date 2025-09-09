<?php
// app/views/admin/home/dashboard.php
require_once __DIR__ . '/../layouts/header.php';

// --- Data fetching specific to this page ---
$stats = [
    'pending_requests' => 0,
    'approved_requests' => 0,
    'total_users' => 0,
    'total_requests' => 0,
];
$result_pending = $conn->query("SELECT COUNT(*) as count FROM vehicle_requests WHERE status = 'pending'");
if($result_pending) $stats['pending_requests'] = $result_pending->fetch_assoc()['count'];

$result_approved = $conn->query("SELECT COUNT(*) as count FROM vehicle_requests WHERE status = 'approved'");
if($result_approved) $stats['approved_requests'] = $result_approved->fetch_assoc()['count'];

$result_users = $conn->query("SELECT COUNT(*) as count FROM users");
if($result_users) $stats['total_users'] = $result_users->fetch_assoc()['count'];

$result_total_req = $conn->query("SELECT COUNT(*) as count FROM vehicle_requests");
if($result_total_req) $stats['total_requests'] = $result_total_req->fetch_assoc()['count'];


$pending_requests = [];
// [แก้ไข] JOIN ตาราง vehicles เพื่อดึงข้อมูลยานพาหนะ
$sql_pending_requests = "SELECT 
                            vr.id, vr.search_id, 
                            u.title, u.firstname, u.lastname, u.work_department, 
                            v.license_plate, v.province, v.vehicle_type, 
                            vr.created_at, vr.status, vr.card_pickup_date
                         FROM vehicle_requests vr
                         JOIN users u ON vr.user_id = u.id
                         JOIN vehicles v ON vr.vehicle_id = v.id
                         WHERE vr.status = 'pending'
                         ORDER BY vr.created_at ASC";
$result_pending_requests = $conn->query($sql_pending_requests);
if ($result_pending_requests->num_rows > 0) {
    while($row = $result_pending_requests->fetch_assoc()) {
        $pending_requests[] = $row;
    }
}
?>

<!-- Page content -->
<main class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
    <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-tachometer-alt text-primary"></i> Dashboard ภาพรวม</h1>
    <p class="text-slate-500 mb-6">สรุปข้อมูลและคำร้องที่รอการตรวจสอบ</p>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4">
            <div class="bg-yellow-100 p-3 rounded-full"><i class="fas fa-clock text-xl text-yellow-500"></i></div>
            <div><p class="text-sm text-gray-500">รออนุมัติ</p><p class="text-2xl font-bold text-gray-800" id="stat-pending"><?php echo number_format($stats['pending_requests']); ?></p></div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4">
            <div class="bg-green-100 p-3 rounded-full"><i class="fas fa-check-circle text-xl text-green-500"></i></div>
            <div><p class="text-sm text-gray-500">อนุมัติแล้ว</p><p class="text-2xl font-bold text-gray-800" id="stat-approved-total"><?php echo number_format($stats['approved_requests']); ?></p></div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4">
            <div class="bg-blue-100 p-3 rounded-full"><i class="fas fa-users text-xl text-blue-500"></i></div>
            <div><p class="text-sm text-gray-500">ผู้ใช้งานทั้งหมด</p><p class="text-2xl font-bold text-gray-800" id="stat-total-users"><?php echo number_format($stats['total_users']); ?></p></div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4">
            <div class="bg-purple-100 p-3 rounded-full"><i class="fas fa-file-alt text-xl text-purple-500"></i></div>
            <div><p class="text-sm text-gray-500">คำร้องทั้งหมด</p><p class="text-2xl font-bold text-gray-800" id="stat-total-requests"><?php echo number_format($stats['total_requests']); ?></p></div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card bg-base-100 shadow-lg mt-8">
        <div class="card-body">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                 <h2 class="card-title flex items-center gap-2 text-lg"><i class="fa-solid fa-inbox text-slate-600"></i> รายการคำร้องรออนุมัติ</h2>
                 <div class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="text" id="searchInput" placeholder="ค้นหา..." class="input input-sm input-bordered w-full sm:w-auto">
                    <button id="openExportModalBtn" class="btn btn-sm btn-outline btn-success hidden"><i class="fa-solid fa-file-excel mr-1"></i>Export</button>
                </div>
            </div>
            <div class="overflow-x-auto mt-4">
                <table class="table table-sm" id="requestsTable">
                    <thead class="bg-slate-50">
                        <!-- [แก้ไข] เพิ่มคอลัมน์ให้เหมือนกับ manage_requests.php -->
                        <tr>
                            <th data-sort-by="search_id">รหัสคำร้อง<i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="name">ชื่อผู้ยื่น<i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="department">สังกัด<i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="license">ทะเบียนรถ<i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="type">ประเภทรถ<i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="date" class="sort-asc">วันที่ยื่น<i class="fa-solid fa-sort-up"></i></th>
                            <th data-sort-by="pickup_date">วันที่นัดรับบัตร<i class="fa-solid fa-sort"></i></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_requests)): ?>
                            <tr><td colspan="8" class="text-center text-slate-500 py-4">ไม่พบรายการที่รอการอนุมัติ</td></tr>
                        <?php else: ?>
                            <?php foreach ($pending_requests as $req): ?>
                            <tr class="hover:bg-slate-50" data-request-id="<?php echo $req['id']; ?>" data-status="pending">
                                <td class="font-semibold whitespace-nowrap"><?php echo htmlspecialchars($req['search_id']); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['title'] . $req['firstname'] . ' ' . $req['lastname']); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['work_department'] ?? '-'); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['license_plate'] . ' ' . $req['province']); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['vehicle_type']); ?></td>
                                <td class="whitespace-nowrap"><?php echo format_thai_datetime($req['created_at']); ?></td>
                                <td class="whitespace-nowrap font-semibold text-info"><?php echo format_thai_date($req['card_pickup_date']); ?></td>
                                <td class="whitespace-nowrap">
                                    <button class="btn btn-xs btn-primary inspect-btn" data-id="<?php echo $req['id']; ?>">
                                        <span><i class="fa-solid fa-search mr-1"></i>ตรวจสอบ</span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <tr id="no-results-row" class="hidden"><td colspan="8" class="text-center text-slate-500 py-4">ไม่พบข้อมูลคำร้องที่ค้นหา</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

