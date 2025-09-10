<?php
// app/views/user/home/dashboard.php
require_once __DIR__ . '/../shared/auth_check.php';

// --- ดึงข้อมูลรอบการสมัครที่เปิดใช้งานอยู่ ---
$active_period = null;
$sql_period = "SELECT * FROM application_periods WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date LIMIT 1";
$result_period = $conn->query($sql_period);
if ($result_period->num_rows > 0) {
    $active_period = $result_period->fetch_assoc();
}

// --- ดึงข้อมูลยานพาหนะที่ยื่นคำร้อง "ที่ยังไม่ถูกปฏิเสธ" ในรอบปัจจุบันแล้ว ---
$renewed_vehicle_ids = [];
if ($active_period) {
    $sql_renewed = "SELECT vehicle_id FROM vehicle_requests WHERE user_id = ? AND period_id = ? AND status IN ('pending', 'approved')";
    $stmt_renewed = $conn->prepare($sql_renewed);
    $stmt_renewed->bind_param("ii", $user_id, $active_period['id']);
    $stmt_renewed->execute();
    $result_renewed = $stmt_renewed->get_result();
    while($row = $result_renewed->fetch_assoc()) {
        $renewed_vehicle_ids[] = $row['vehicle_id'];
    }
    $stmt_renewed->close();
}


// --- ดึงข้อมูลสถิติ ---
$stats = ['total' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0];
$sql_stats = "SELECT status, COUNT(*) as count FROM vehicle_requests WHERE user_id = ? GROUP BY status";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("i", $user_id);
$stmt_stats->execute();
$result_stats = $stmt_stats->get_result();
while ($row = $result_stats->fetch_assoc()) {
    if (isset($stats[$row['status']])) $stats[$row['status']] = $row['count'];
    $stats['total'] += $row['count'];
}
$stmt_stats->close();

// --- ดึงข้อมูลยานพาหนะ/คำร้อง ---
$vehicle_requests = [];
$sql_vehicles = "SELECT vr.*, v.vehicle_type, v.brand, v.model, v.color, v.license_plate, v.province as vehicle_province, a.firstname as admin_firstname, a.lastname as admin_lastname FROM vehicle_requests vr JOIN vehicles v ON vr.vehicle_id = v.id LEFT JOIN admins a ON vr.approved_by_id = a.id WHERE vr.user_id = ? ORDER BY v.id, vr.created_at DESC";
$stmt_vehicles = $conn->prepare($sql_vehicles);
$stmt_vehicles->bind_param("i", $user_id);
$stmt_vehicles->execute();
$result_vehicles = $stmt_vehicles->get_result();
if ($result_vehicles->num_rows > 0) {
    while($row = $result_vehicles->fetch_assoc()) {
        $vehicle_requests[] = $row;
    }
}
$stmt_vehicles->close();

require_once __DIR__ . '/../layouts/header.php';
?>
<!-- Main Content -->
<main class="flex-grow container mx-auto max-w-6xl p-0 sm:p-6 pb-24" id="dashboard-section">
    <div class="bg-base-100 sm:shadow-xl sm:border sm:border-base-300/50 sm:rounded-2xl">
        <div class="p-4 sm:p-6 md:p-8">
            <!-- User Welcome -->
            <div class="block sm:flex sm:items-baseline sm:gap-2">
                <h1 class="text-xl sm:text-2xl font-bold mb-1">ยินดีต้อนรับ,</h1>
                <h1 class="text-xl sm:text-2xl font-bold"><?php echo htmlspecialchars($title . ' ' . $firstname . ' ' . $lastname); ?></h1>
            </div>
            <div class="flex flex-wrap gap-2 mt-2 mb-6">
                <div class="badge badge-lg badge-outline gap-2"><?php echo $user_type_icon; ?><?php echo htmlspecialchars($user_type_thai); ?></div>
                <?php if ($user['user_type'] === 'army' && !empty($user['work_department'])): ?>
                <div class="badge badge-lg badge-outline gap-2"><i class="fa-solid fa-sitemap text-slate-500"></i>สังกัด: <?php echo htmlspecialchars($user['work_department']); ?></div>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="card bg-base-100 shadow-lg cursor-pointer hover:shadow-xl transition-shadow duration-200 stat-filter" data-filter="all">
                    <div class="card-body p-3 sm:p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-full"><i class="fa-solid fa-file-alt text-md sm:text-lg text-blue-600"></i></div>
                            <div class="ml-2 sm:ml-3"><p class="text-xs text-gray-500">ทั้งหมด</p><p class="text-lg sm:text-xl font-bold"><?php echo $stats['total']; ?></p></div>
                        </div>
                    </div>
                </div>
                <div class="card bg-base-100 shadow-lg cursor-pointer hover:shadow-xl transition-shadow duration-200 stat-filter" data-filter="approved">
                    <div class="card-body p-3 sm:p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-full"><i class="fa-solid fa-check-circle text-md sm:text-lg text-green-600"></i></div>
                            <div class="ml-2 sm:ml-3"><p class="text-xs text-gray-500">อนุมัติ</p><p class="text-lg sm:text-xl font-bold"><?php echo $stats['approved']; ?></p></div>
                        </div>
                    </div>
                </div>
                <div class="card bg-base-100 shadow-lg cursor-pointer hover:shadow-xl transition-shadow duration-200 stat-filter" data-filter="pending">
                    <div class="card-body p-3 sm:p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-full"><i class="fa-solid fa-clock text-md sm:text-lg text-yellow-600"></i></div>
                            <div class="ml-2 sm:ml-3"><p class="text-xs text-gray-500">รออนุมัติ</p><p class="text-lg sm:text-xl font-bold"><?php echo $stats['pending']; ?></p></div>
                        </div>
                    </div>
                </div>
                <div class="card bg-base-100 shadow-lg cursor-pointer hover:shadow-xl transition-shadow duration-200 stat-filter" data-filter="rejected">
                    <div class="card-body p-3 sm:p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-full"><i class="fa-solid fa-circle-xmark text-md sm:text-lg text-red-600"></i></div>
                            <div class="ml-2 sm:ml-3"><p class="text-xs text-gray-500">ไม่ผ่าน</p><p class="text-lg sm:text-xl font-bold"><?php echo $stats['rejected']; ?></p></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requests List -->
             <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="card-title text-base sm:text-xl flex items-center gap-2"><i class="fa-solid fa-car-side"></i> ภาพรวมยานพาหนะ/คำร้องของคุณ</h2>
                <a href="add_vehicle.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> เพิ่มยานพาหนะ/ยื่นคำร้อง</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4" id="vehicle-list-container">
                <?php if (empty($vehicle_requests)): ?>
                    <div class="col-span-full text-center p-8 text-gray-500"><i class="fa-solid fa-folder-open fa-3x mb-4"></i><p>ยังไม่พบข้อมูลคำร้อง</p></div>
                <?php else: ?>
                    <div id="no-filter-results" class="col-span-full text-center p-8 text-gray-500 hidden"><i class="fa-solid fa-magnifying-glass fa-3x mb-4"></i><p>ไม่พบข้อมูลตามสถานะที่เลือก</p></div>
                    <?php 
                    $processed_vehicles = [];
                    foreach ($vehicle_requests as $request): 
                        if (in_array($request['vehicle_id'], $processed_vehicles)) continue;
                        $processed_vehicles[] = $request['vehicle_id'];

                        // --- [แก้ไข] ตรรกะการต่ออายุให้ถูกต้องและชัดเจนขึ้น ---
                        $can_renew = false;
                        $is_expired = !empty($request['card_expiry']) && (new DateTime() > new DateTime($request['card_expiry']));

                        // เงื่อนไข: 1.มีรอบแอคทีฟ 2.ยังไม่เคยยื่นเรื่องในรอบนี้ 3.(บัตรหมดอายุแล้ว หรือ คำร้องล่าสุดถูกปฏิเสธ)
                        if ($active_period && !in_array($request['vehicle_id'], $renewed_vehicle_ids)) {
                            if (($request['status'] === 'approved' && $is_expired) || $request['status'] === 'rejected') {
                                $can_renew = true;
                            }
                        }
                        
                        $status_text = ''; 
                        $status_class = '';
                        $border_class = 'border-transparent'; // Default border

                        if ($is_expired && $request['status'] === 'approved') {
                            $status_text = 'หมดอายุ'; 
                            $status_class = 'badge-neutral';
                            $border_class = 'border-gray-400';
                        } else {
                            switch ($request['status']) {
                                case 'approved': 
                                    $status_text = 'อนุมัติแล้ว'; 
                                    $status_class = 'badge-success'; 
                                    $border_class = 'border-green-500';
                                    break;
                                case 'pending': 
                                    $status_text = 'รออนุมัติ'; 
                                    $status_class = 'badge-warning'; 
                                    $border_class = 'border-yellow-500';
                                    break;
                                case 'rejected': 
                                    $status_text = 'ไม่ผ่าน'; 
                                    $status_class = 'badge-error'; 
                                    $border_class = 'border-red-500';
                                    break;
                            }
                        }

                        $admin_name = ($request['admin_firstname'] && $request['admin_lastname']) ? $request['admin_firstname'] . ' ' . $request['admin_lastname'] : '-';
                        $front_image_path = "/public/uploads/" . htmlspecialchars($user_key) . "/vehicle/" . htmlspecialchars($request['request_key']) . "/" . htmlspecialchars($request['photo_front']);
                    ?>
                        <div class="card bg-base-100 shadow-md hover:shadow-xl transition-shadow duration-200 vehicle-card cursor-pointer overflow-hidden border-2 <?php echo $border_class; ?>"
                            onclick="openDetailModal(this)"
                            data-request-id="<?php echo htmlspecialchars($request['id']); ?>"
                            data-vehicle-id="<?php echo htmlspecialchars($request['vehicle_id']); ?>"
                            data-user-key="<?php echo htmlspecialchars($user_key); ?>"
                            data-request-key="<?php echo htmlspecialchars($request['request_key']); ?>"
                            data-type="<?php echo htmlspecialchars($request['vehicle_type']); ?>"
                            data-brand="<?php echo htmlspecialchars($request['brand']); ?>"
                            data-model="<?php echo htmlspecialchars($request['model']); ?>"
                            data-color="<?php echo htmlspecialchars($request['color']); ?>"
                            data-plate="<?php echo htmlspecialchars($request['license_plate']); ?>"
                            data-province="<?php echo htmlspecialchars($request['vehicle_province']); ?>"
                            data-tax-expiry="<?php echo htmlspecialchars($request['tax_expiry_date']); ?>"
                            data-owner-type="<?php echo htmlspecialchars($request['owner_type']); ?>"
                            data-other-owner-name="<?php echo htmlspecialchars($request['other_owner_name'] ?? '-'); ?>"
                            data-other-owner-relation="<?php echo htmlspecialchars($request['other_owner_relation'] ?? '-'); ?>"
                            data-status-text="<?php echo $status_text; ?>"
                            data-status="<?php echo htmlspecialchars($request['status']); ?>"
                            data-status-class="<?php echo $status_class; ?>"
                            data-card-number="<?php echo htmlspecialchars($request['card_number'] ?? '-'); ?>"
                            data-admin-name="<?php echo htmlspecialchars($admin_name); ?>"
                            data-img-reg-filename="<?php echo htmlspecialchars($request['photo_reg_copy']); ?>"
                            data-img-tax-filename="<?php echo htmlspecialchars($request['photo_tax_sticker']); ?>"
                            data-img-front-filename="<?php echo htmlspecialchars($request['photo_front']); ?>"
                            data-img-rear-filename="<?php echo htmlspecialchars($request['photo_rear']); ?>"
                            data-card-pickup-date="<?php echo htmlspecialchars($request['card_pickup_date'] ?? ''); ?>"
                            data-card-type="<?php echo htmlspecialchars($request['card_type'] ?? ''); ?>"
                            data-card-expiry="<?php echo htmlspecialchars($request['card_expiry'] ?? ''); ?>"
                            data-rejection-reason="<?php echo htmlspecialchars($request['rejection_reason'] ?? ''); ?>"
                            data-search-id="<?php echo htmlspecialchars($request['search_id'] ?? ''); ?>"
                            data-can-renew="<?php echo $can_renew ? 'true' : 'false'; ?>">
                            
                            <figure class="bg-base-200">
                                <img src="<?php echo $front_image_path; ?>" alt="รูปถ่ายหน้ารถ" class="h-32 w-full object-cover" onerror="this.onerror=null;this.src='https://placehold.co/300x200/e2e8f0/475569?text=No+Image';">
                            </figure>
                            
                            <div class="card-body p-3">
                                <div class="font-bold text-sm flex items-center gap-2"><?php if ($request['vehicle_type'] == 'รถยนต์'): ?><i class="fa-solid fa-car"></i> รถยนต์<?php else: ?><i class="fa-solid fa-motorcycle"></i> รถจักรยานยนต์<?php endif; ?></div>
                                <div class="mt-1">
                                    <p class="text-lg font-bold leading-tight"><?php echo htmlspecialchars($request['license_plate']); ?></p>
                                    <p class="text-xs text-gray-600"><?php echo htmlspecialchars($request['vehicle_province']); ?></p>
                                </div>
                                <div class="flex justify-between items-end mt-2">
                                    <div>
                                        <div class="text-xs">เลขที่บัตร</div>
                                        <div class="font-semibold text-xs"><?php echo htmlspecialchars($request['card_number'] ?? '-'); ?></div>
                                    </div>
                                    <div class="badge <?php echo $status_class; ?> text-white font-semibold"><?php echo $status_text; ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

