<?php
// app/views/admin/home/manage_requests.php
require_once __DIR__ . '/../layouts/header.php';

// --- Data fetching specific to this page ---
$all_requests = [];
// [แก้ไข] JOIN ตาราง vehicles เพื่อดึงข้อมูลยานพาหนะ
$sql_all_requests = "SELECT 
                        vr.id, vr.search_id, 
                        u.title, u.firstname, u.lastname, u.work_department, 
                        v.license_plate, v.province, v.vehicle_type, 
                        vr.created_at, vr.status, vr.card_pickup_date
                     FROM vehicle_requests vr
                     JOIN users u ON vr.user_id = u.id
                     JOIN vehicles v ON vr.vehicle_id = v.id
                     ORDER BY 
                        CASE vr.status
                            WHEN 'pending' THEN 1
                            WHEN 'rejected' THEN 2
                            WHEN 'approved' THEN 3
                            ELSE 4
                        END, vr.created_at DESC";
$result_all_requests = $conn->query($sql_all_requests);
if ($result_all_requests->num_rows > 0) {
    while($row = $result_all_requests->fetch_assoc()) {
        $all_requests[] = $row;
    }
}
?>

<!-- Page content -->
<main class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
    <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-file-signature text-primary"></i> จัดการคำร้องทั้งหมด</h1>
    <p class="text-slate-500 mb-6">ตรวจสอบและจัดการคำร้องขอบัตรผ่านทั้งหมดในระบบ</p>
    
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="tabs tabs-boxed">
                    <a class="tab tab-sm sm:tab-md status-filter active" data-filter="all">ทั้งหมด</a>
                    <a class="tab tab-sm sm:tab-md status-filter" data-filter="pending">รออนุมัติ</a>
                    <a class="tab tab-sm sm:tab-md status-filter" data-filter="approved">อนุมัติแล้ว</a>
                    <a class="tab tab-sm sm:tab-md status-filter" data-filter="rejected">ไม่ผ่าน</a>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                   <input type="text" id="searchInput" placeholder="ค้นหา..." class="input input-sm input-bordered w-full sm:w-auto">
                </div>
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="table table-sm" id="requestsTable">
                     <thead class="bg-slate-50">
                        <tr>
                            <th data-sort-by="search_id">รหัสคำร้อง<i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="name">ชื่อผู้ยื่น<i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="department">สังกัด<i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="license">ทะเบียนรถ<i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="type">ประเภทรถ<i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="date">วันที่ยื่น<i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="pickup_date">วันที่นัดรับบัตร<i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="status">สถานะ<i class="fa-solid fa-sort"></i></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_requests)): ?>
                            <tr><td colspan="9" class="text-center text-slate-500 py-4">ไม่พบข้อมูลคำร้องในระบบ</td></tr>
                        <?php else: ?>
                            <?php foreach ($all_requests as $req): ?>
                            <tr class="hover:bg-slate-50" data-request-id="<?php echo $req['id']; ?>" data-status="<?php echo $req['status']; ?>">
                                <td class="font-semibold whitespace-nowrap"><?php echo htmlspecialchars($req['search_id']); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['title'] . $req['firstname'] . ' ' . $req['lastname']); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['work_department'] ?? '-'); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['license_plate'] . ' ' . $req['province']); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['vehicle_type']); ?></td>
                                <td class="whitespace-nowrap"><?php echo format_thai_datetime($req['created_at']); ?></td>
                                <td class="whitespace-nowrap font-semibold text-info"><?php echo format_thai_date($req['card_pickup_date']); ?></td>
                                <td>
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
                                <td class="whitespace-nowrap">
                                    <button class="btn btn-xs btn-primary inspect-btn" data-id="<?php echo $req['id']; ?>">
                                        <span><i class="fa-solid fa-search mr-1"></i>ตรวจสอบ</span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <tr id="no-results-row" class="hidden"><td colspan="9" class="text-center text-slate-500 py-4">ไม่พบข้อมูลคำร้องที่ค้นหา</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

