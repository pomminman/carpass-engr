<?php
require_once __DIR__ . '/../shared/auth_check.php';

// Fetch active application period
$active_period = null;
$sql_period = "SELECT * FROM application_periods WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date LIMIT 1";
$result_period = $conn->query($sql_period);
if ($result_period->num_rows > 0) {
    $active_period = $result_period->fetch_assoc();
}

// Fetch vehicle requests that have already been renewed in the current period
$renewed_vehicle_ids = [];
if ($active_period) {
    $sql_renewed = "SELECT vehicle_id FROM vehicle_requests WHERE user_id = ? AND period_id = ? AND status IN ('pending', 'approved')";
    $stmt_renewed = $conn->prepare($sql_renewed);
    $stmt_renewed->bind_param("ii", $user_id, $active_period['id']);
    $stmt_renewed->execute();
    $result_renewed = $stmt_renewed->get_result();
    while ($row = $result_renewed->fetch_assoc()) {
        $renewed_vehicle_ids[] = $row['vehicle_id'];
    }
    $stmt_renewed->close();
}

// Fetch stats
$stats = ['all' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0, 'expired' => 0];
$sql_stats = "SELECT status, card_expiry, COUNT(*) as count FROM vehicle_requests WHERE user_id = ? GROUP BY status, card_expiry";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("i", $user_id);
$stmt_stats->execute();
$result_stats = $stmt_stats->get_result();
while ($row = $result_stats->fetch_assoc()) {
    $stats['all'] += $row['count'];
    $is_expired = !empty($row['card_expiry']) && (new DateTime() > new DateTime($row['card_expiry']));
    if ($row['status'] === 'approved' && $is_expired) {
        $stats['expired'] += $row['count'];
    } elseif (isset($stats[$row['status']])) {
        $stats[$row['status']] += $row['count'];
    }
}
$stmt_stats->close();

// Fetch all vehicle requests for the user
$vehicle_requests = [];
$sql_vehicles = "
    SELECT 
        vr.*, 
        v.vehicle_type, v.brand, v.model, v.color, v.license_plate, v.province as vehicle_province, 
        a.firstname as admin_firstname, a.title as admin_title
    FROM vehicle_requests vr 
    JOIN vehicles v ON vr.vehicle_id = v.id 
    LEFT JOIN admins a ON vr.approved_by_id = a.id 
    WHERE vr.user_id = ? 
    ORDER BY vr.created_at DESC";
$stmt_vehicles = $conn->prepare($sql_vehicles);
$stmt_vehicles->bind_param("i", $user_id);
$stmt_vehicles->execute();
$result_vehicles = $stmt_vehicles->get_result();
while ($row = $result_vehicles->fetch_assoc()) {
    $vehicle_requests[] = $row;
}
$stmt_vehicles->close();

require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Welcome Header -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">ภาพรวมยานพาหนะ</h1>
        <p class="text-sm sm:text-base text-base-content/70">จัดการและติดตามสถานะคำร้องขอบัตรผ่านของคุณ</p>
    </div>
    <a href="add_vehicle.php" class="btn btn-primary btn-sm mt-2 sm:mt-0">
        <i class="fa-solid fa-plus"></i> เพิ่มยานพาหนะ / ยื่นคำร้อง
    </a>
</div>

<!-- User Info -->
<div class="card bg-base-100 shadow-md mb-6">
    <div class="card-body p-4 flex-row items-center gap-4">
        <div class="avatar">
            <div class="w-16 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                <img src="<?php echo htmlspecialchars($user_photo_path); ?>" />
            </div>
        </div>
        <div>
            <h2 class="card-title"><?php echo htmlspecialchars($title . ' ' . $firstname . ' ' . $lastname); ?></h2>
            <div class="flex flex-wrap gap-2 mt-1">
                <div class="badge badge-outline gap-2 h-auto whitespace-normal">
                    <?php echo $user_type_icon; ?>
                    <span class="text-left text-[10px] sm:text-xs lg:text-sm"><?php echo htmlspecialchars($user_type_thai); ?></span>
                </div>
                <?php if ($user['user_type'] === 'army' && !empty($user['work_department'])): ?>
                <div class="badge badge-outline gap-2"><i class="fa-solid fa-sitemap"></i><?php echo htmlspecialchars($user['work_department']); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Stats and Search -->
<div class="flex flex-col md:flex-row gap-4 mb-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 flex-grow">
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter" data-filter="all">
            <div class="card-body p-3 items-center text-center"><div class="text-2xl font-bold"><?php echo $stats['all']; ?></div><div class="text-xs text-base-content/70">ทั้งหมด</div></div>
        </div>
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter" data-filter="approved">
            <div class="card-body p-3 items-center text-center"><div class="text-2xl font-bold text-success"><?php echo $stats['approved']; ?></div><div class="text-xs text-base-content/70">อนุมัติ</div></div>
        </div>
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter" data-filter="pending">
            <div class="card-body p-3 items-center text-center"><div class="text-2xl font-bold text-warning"><?php echo $stats['pending']; ?></div><div class="text-xs text-base-content/70">รออนุมัติ</div></div>
        </div>
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter" data-filter="rejected">
            <div class="card-body p-3 items-center text-center"><div class="text-2xl font-bold text-error"><?php echo $stats['rejected']; ?></div><div class="text-xs text-base-content/70">ไม่ผ่าน</div></div>
        </div>
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter" data-filter="expired">
            <div class="card-body p-3 items-center text-center"><div class="text-2xl font-bold text-base-content/50"><?php echo $stats['expired']; ?></div><div class="text-xs text-base-content/70">หมดอายุ</div></div>
        </div>
    </div>
    <!-- Search -->
    <div class="form-control">
        <label class="input input-bordered flex items-center gap-2">
            <input type="text" id="search-input" class="grow" placeholder="ค้นหาทะเบียน, รุ่นรถ..." />
            <i class="fa-solid fa-magnifying-glass opacity-70"></i>
        </label>
    </div>
</div>

<!-- Vehicle Grid -->
<div id="vehicle-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php if (empty($vehicle_requests)): ?>
        <div class="col-span-full text-center p-8 text-base-content/60">
            <i class="fa-solid fa-folder-open fa-3x mb-4"></i>
            <p>ยังไม่พบข้อมูลคำร้อง</p>
        </div>
    <?php else: ?>
        <?php foreach ($vehicle_requests as $request): 
            $can_renew = false;
            $is_expired = !empty($request['card_expiry']) && (new DateTime() > new DateTime($request['card_expiry']));
            
            if ($active_period && !in_array($request['vehicle_id'], $renewed_vehicle_ids)) {
                if (($request['status'] === 'approved' && $is_expired) || $request['status'] === 'rejected') {
                    $can_renew = true;
                }
            }
            
            $status_key = $request['status'];
            $status_text = ''; 
            $status_class = '';
            $status_icon = '';
            $border_class = 'border-transparent';

            if ($request['status'] === 'approved' && $is_expired) {
                $status_key = 'expired';
                $status_text = 'หมดอายุ'; 
                $status_class = 'badge-neutral';
                $status_icon = 'fa-solid fa-calendar-times';
                $border_class = 'border-base-300';
            } else {
                switch ($request['status']) {
                    case 'approved': $status_text = 'อนุมัติแล้ว'; $status_class = 'badge-success'; $status_icon = 'fa-solid fa-check-circle'; $border_class = 'border-success/50'; break;
                    case 'pending': $status_text = 'รออนุมัติ'; $status_class = 'badge-warning'; $status_icon = 'fa-solid fa-clock'; $border_class = 'border-warning/50'; break;
                    case 'rejected': $status_text = 'ไม่ผ่าน'; $status_class = 'badge-error'; $status_icon = 'fa-solid fa-circle-xmark'; $border_class = 'border-error/50'; break;
                }
            }

            $approved_by = ($request['admin_title'] && $request['admin_firstname']) ? $request['admin_title'] . $request['admin_firstname'] : '-';
            $front_image_path = "/public/uploads/" . htmlspecialchars($user_key) . "/vehicle/" . htmlspecialchars($request['request_key']) . "/" . htmlspecialchars($request['photo_front']);
        ?>
            <div class="card bg-base-100 shadow-md hover:shadow-xl transition-shadow duration-300 vehicle-card cursor-pointer border-2 <?= $border_class ?>"
                data-status-key="<?= $status_key ?>"
                data-request-id="<?= htmlspecialchars($request['id']) ?>"
                data-vehicle-id="<?= htmlspecialchars($request['vehicle_id']) ?>"
                data-user-key="<?= htmlspecialchars($user_key) ?>"
                data-request-key="<?= htmlspecialchars($request['request_key']) ?>"
                data-vehicle-type="<?= htmlspecialchars($request['vehicle_type']) ?>"
                data-brand="<?= htmlspecialchars($request['brand']) ?>"
                data-model="<?= htmlspecialchars($request['model']) ?>"
                data-color="<?= htmlspecialchars($request['color']) ?>"
                data-license-plate="<?= htmlspecialchars($request['license_plate']) ?>"
                data-province="<?= htmlspecialchars($request['vehicle_province']) ?>"
                data-tax-expiry="<?= htmlspecialchars($request['tax_expiry_date']) ?>"
                data-owner-type="<?= htmlspecialchars($request['owner_type']) ?>"
                data-other-owner-name="<?= htmlspecialchars($request['other_owner_name'] ?? '-') ?>"
                data-other-owner-relation="<?= htmlspecialchars($request['other_owner_relation'] ?? '-') ?>"
                data-status-text="<?= $status_text ?>"
                data-status-class="<?= $status_class ?>"
                data-status-icon="<?= $status_icon ?>"
                data-card-number="<?= htmlspecialchars($request['card_number'] ?? '-') ?>"
                data-approved-by="<?= htmlspecialchars($approved_by) ?>"
                data-photo-reg="<?= htmlspecialchars($request['photo_reg_copy']) ?>"
                data-photo-tax="<?= htmlspecialchars($request['photo_tax_sticker']) ?>"
                data-photo-front="<?= htmlspecialchars($request['photo_front']) ?>"
                data-photo-rear="<?= htmlspecialchars($request['photo_rear']) ?>"
                data-card-type="<?= htmlspecialchars($request['card_type'] ?? '') ?>"
                data-card-expiry="<?= htmlspecialchars($request['card_expiry'] ?? '') ?>"
                data-rejection-reason="<?= htmlspecialchars($request['rejection_reason'] ?? '') ?>"
                data-search-id="<?= htmlspecialchars($request['search_id'] ?? '') ?>"
                data-created-at="<?= htmlspecialchars($request['created_at']) ?>"
                data-updated-at="<?= htmlspecialchars($request['updated_at']) ?>"
                data-approved-at="<?= htmlspecialchars($request['approved_at'] ?? '') ?>"
                data-can-renew="<?= $can_renew ? 'true' : 'false' ?>">
                
                <figure class="bg-base-200">
                    <img src="<?= $front_image_path ?>" alt="รูปถ่ายหน้ารถ" class="h-40 w-full object-cover" onerror="this.onerror=null;this.src='https://placehold.co/300x200/e2e8f0/475569?text=No+Image';">
                </figure>
                
                <div class="card-body p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold text-base sm:text-lg leading-tight truncate"><?= htmlspecialchars($request['license_plate']) ?></p>
                            <p class="text-xs text-base-content/60"><?= htmlspecialchars($request['vehicle_province']) ?></p>
                        </div>
                        <div class="badge <?= $status_class ?> text-white font-semibold text-xs"><?= $status_text ?></div>
                    </div>
                    <p class="text-sm mt-1 truncate"><?= htmlspecialchars($request['brand']) ?> / <?= htmlspecialchars($request['model']) ?></p>
                    <div class="text-xs text-base-content/60 mt-2 space-y-1">
                        <p>รหัสคำร้อง: <span class="font-medium"><?= htmlspecialchars($request['search_id'] ?? '-') ?></span></p>
                        <p>เลขที่บัตร: <span class="font-medium"><?= htmlspecialchars($request['card_number'] ?? '-') ?></span></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <div id="no-results-message" class="col-span-full text-center p-8 text-base-content/60 hidden">
        <i class="fa-solid fa-magnifying-glass fa-3x mb-4"></i>
        <p>ไม่พบข้อมูลที่ตรงกับการค้นหา</p>
    </div>
</div>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>




