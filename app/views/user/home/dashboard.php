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
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">ภาพรวมยานพาหนะ</h1>
        <p class="text-sm sm:text-base text-base-content/70">จัดการและติดตามสถานะคำร้องขอบัตรผ่านของคุณ</p>
    </div>
    <a href="add_vehicle.php" class="btn btn-primary mt-2 sm:mt-0">
        <i class="fa-solid fa-plus"></i> เพิ่มยานพาหนะ / ยื่นคำร้อง
    </a>
</div>

<!-- User Info -->
<div class="card bg-base-100 shadow-md mb-4">
    <div class="card-body p-3 flex-row items-center gap-4">
        <div class="avatar">
            <div class="w-14 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                <img src="<?php echo htmlspecialchars($user_photo_path); ?>" />
            </div>
        </div>
        <div>
            <h2 class="card-title text-base"><?php echo htmlspecialchars($title . ' ' . $firstname . ' ' . $lastname); ?></h2>
            <div class="flex flex-wrap gap-2 mt-1">
                <div class="badge badge-outline gap-2 h-auto whitespace-normal">
                    <?php echo $user_type_icon; ?>
                    <span class="text-left text-[10px] sm:text-xs"><?php echo htmlspecialchars($user_type_thai); ?></span>
                </div>
                <?php if ($user['user_type'] === 'army' && !empty($user['work_department'])): ?>
                <div class="badge badge-outline gap-2 text-[10px] sm:text-xs"><i class="fa-solid fa-sitemap"></i><?php echo htmlspecialchars($user['work_department']); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Stats and Search -->
<div class="flex flex-col md:flex-row gap-2 mb-4">
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 flex-grow">
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter" data-filter="all">
            <div class="card-body p-2 items-center text-center"><div class="text-xl font-bold"><?php echo $stats['all']; ?></div><div class="text-xs text-base-content/70">ทั้งหมด</div></div>
        </div>
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter" data-filter="approved">
            <div class="card-body p-2 items-center text-center"><div class="text-xl font-bold text-success"><?php echo $stats['approved']; ?></div><div class="text-xs text-base-content/70">อนุมัติ</div></div>
        </div>
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter" data-filter="pending">
            <div class="card-body p-2 items-center text-center"><div class="text-xl font-bold text-warning"><?php echo $stats['pending']; ?></div><div class="text-xs text-base-content/70">รออนุมัติ</div></div>
        </div>
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter" data-filter="rejected">
            <div class="card-body p-2 items-center text-center"><div class="text-xl font-bold text-error"><?php echo $stats['rejected']; ?></div><div class="text-xs text-base-content/70">ไม่ผ่าน</div></div>
        </div>
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter" data-filter="expired">
            <div class="card-body p-2 items-center text-center"><div class="text-xl font-bold text-base-content/50"><?php echo $stats['expired']; ?></div><div class="text-xs text-base-content/70">หมดอายุ</div></div>
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
<div id="vehicle-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
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
                    <img src="<?= $front_image_path ?>" alt="รูปถ่ายหน้ารถ" class="h-32 w-full object-cover" onerror="this.onerror=null;this.src='https://placehold.co/300x200/e2e8f0/475569?text=No+Image';">
                </figure>
                
                <div class="card-body p-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold text-base leading-tight truncate"><?= htmlspecialchars($request['license_plate']) ?></p>
                            <p class="text-xs text-base-content/60"><?= htmlspecialchars($request['vehicle_province']) ?></p>
                        </div>
                        <div class="badge <?= $status_class ?> text-white font-semibold text-xs"><?= $status_text ?></div>
                    </div>
                    <p class="text-sm mt-1 truncate"><?= htmlspecialchars($request['brand']) ?> / <?= htmlspecialchars($request['model']) ?></p>
                    <div class="text-xs text-base-content/60 mt-2 space-y-0.5">
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

<!-- Request Details Modal -->
<dialog id="request_details_modal" class="modal">
    <div class="modal-box w-11/12 max-w-4xl">
        <!-- Main Content Wrapper -->
        <div id="modal-content-wrapper">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 id="modal-license-plate" class="font-bold text-xl"></h3>
                    <p id="modal-brand-model" class="text-base-content/80"></p>
                </div>
                <div id="modal-status-badge" class="badge badge-lg"></div>
            </div>

            <div id="modal-rejection-reason-box" class="alert alert-error alert-soft my-3 hidden">
                    <div class="flex items-center">
                    <i class="fa-solid fa-circle-info mr-2"></i>
                    <div>
                        <h3 class="font-bold text-sm">เหตุผลที่ไม่ผ่าน</h3>
                        <p id="modal-rejection-reason-text" class="text-xs"></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <!-- Left Column -->
                <div>
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-medium text-sm border-b border-base-300 pb-1 mb-2"><i class="fa-solid fa-car-side w-4 mr-2"></i>ข้อมูลยานพาหนะและความเป็นเจ้าของ</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 text-sm pt-2">
                                <div class="space-y-1" id="modal-vehicle-info-list"></div>
                                <div class="space-y-1" id="modal-owner-info-list"></div>
                            </div>
                        </div>
                        <div class="divider my-1"></div>
                        <div>
                             <h4 class="font-medium text-sm border-b border-base-300 pb-1 mb-2"><i class="fa-solid fa-images w-4 mr-2"></i>หลักฐานประกอบ</h4>
                             <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 pt-2">
                                <a href="#" target="_blank" data-pswp-src="#" data-pswp-width="800" data-pswp-height="600" class="group relative">
                                    <img id="modal-photo-reg" class="w-full h-20 object-cover rounded-md" alt="สำเนาทะเบียนรถ">
                                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center text-white text-xs opacity-0 group-hover:opacity-100 transition-opacity">สำเนาทะเบียน</div>
                                </a>
                                <a href="#" target="_blank" data-pswp-src="#" data-pswp-width="800" data-pswp-height="600" class="group relative">
                                    <img id="modal-photo-tax" class="w-full h-20 object-cover rounded-md" alt="ป้ายภาษี">
                                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center text-white text-xs opacity-0 group-hover:opacity-100 transition-opacity">ป้ายภาษี</div>
                                </a>
                                <a href="#" target="_blank" data-pswp-src="#" data-pswp-width="800" data-pswp-height="600" class="group relative">
                                    <img id="modal-photo-front" class="w-full h-20 object-cover rounded-md" alt="รูปถ่ายด้านหน้า">
                                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center text-white text-xs opacity-0 group-hover:opacity-100 transition-opacity">ด้านหน้า</div>
                                </a>
                                <a href="#" target="_blank" data-pswp-src="#" data-pswp-width="800" data-pswp-height="600" class="group relative">
                                    <img id="modal-photo-rear" class="w-full h-20 object-cover rounded-md" alt="รูปถ่ายด้านหลัง">
                                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center text-white text-xs opacity-0 group-hover:opacity-100 transition-opacity">ด้านหลัง</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                     <div class="space-y-4">
                        <div>
                            <h4 class="font-medium text-sm border-b border-base-300 pb-1 mb-2"><i class="fa-solid fa-file-lines w-4 mr-2"></i>ข้อมูลคำร้องและบัตร</h4>
                            <div class="text-sm space-y-1 pt-2" id="modal-card-info-list"></div>
                        </div>
                         <div class="divider my-1"></div>
                        <div id="modal-qr-code-container" class="text-center hidden">
                             <h4 class="font-medium text-sm border-b border-base-300 pb-1 mb-2"><i class="fa-solid fa-qrcode w-4 mr-2"></i>QR Code</h4>
                            <img src="" class="mx-auto w-1/2 mt-2" alt="QR Code">
                            <p class="text-xs mt-2 text-base-content/70">สแกนเพื่อตรวจสอบสถานะ</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-action mt-6" id="modal-action-buttons"></div>
        </div>
        
        <!-- Edit Form Wrapper -->
        <div id="modal-edit-form-wrapper" class="hidden">
            <h3 class="font-bold text-lg mb-4">แก้ไขข้อมูลยานพาหนะและคำร้อง</h3>
            <form id="editVehicleForm" action="../../../controllers/user/vehicle/edit_vehicle_process.php" method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="request_id" id="edit-request-id">

                <!-- Vehicle Details -->
                <div class="divider text-sm">ข้อมูลยานพาหนะ</div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text">ยี่ห้อ</span></label>
                        <input type="text" name="brand" id="edit-vehicle-brand" class="input input-bordered" required>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">รุ่น</span></label>
                        <input type="text" name="model" id="edit-vehicle-model" class="input input-bordered" required>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">สี</span></label>
                        <input type="text" name="color" id="edit-vehicle-color" class="input input-bordered" required>
                    </div>
                </div>

                <!-- Request Details -->
                <div class="divider text-sm mt-6">ข้อมูลคำร้อง</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text">วันสิ้นอายุภาษี</span></label>
                        <div class="grid grid-cols-3 gap-2">
                            <select name="tax_day" id="edit-tax-day" class="select select-bordered" required></select>
                            <select name="tax_month" id="edit-tax-month" class="select select-bordered" required></select>
                            <select name="tax_year" id="edit-tax-year" class="select select-bordered" required></select>
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">ความเป็นเจ้าของ</span></label>
                        <select name="owner_type" id="edit-owner-type" class="select select-bordered" required>
                            <option value="self">รถชื่อตนเอง</option>
                            <option value="other">รถคนอื่น</option>
                        </select>
                    </div>
                </div>
                <div id="edit-other-owner-details" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text">ชื่อเจ้าของ</span></label>
                        <input type="text" name="other_owner_name" id="edit-other-owner-name" class="input input-bordered">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">ความเกี่ยวข้อง</span></label>
                        <input type="text" name="other_owner_relation" id="edit-other-owner-relation" class="input input-bordered">
                    </div>
                </div>
                
                <!-- File Uploads -->
                <div class="divider text-sm mt-6">หลักฐาน (แนบไฟล์ใหม่หากต้องการเปลี่ยนแปลง)</div>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-center">
                    <!-- Reg Copy -->
                    <div class="form-control">
                        <label for="edit-reg-copy-upload" class="cursor-pointer">
                            <span class="label-text mb-1 text-xs sm:text-sm">สำเนาทะเบียนรถ</span>
                            <img id="edit-reg-copy-preview" src="" class="w-full h-24 object-cover rounded-lg border-2 border-dashed border-base-300 p-1" alt="Preview">
                        </label>
                        <input type="file" name="photo_reg_copy" id="edit-reg-copy-upload" class="hidden" accept="image/*">
                    </div>
                    <!-- Tax Sticker -->
                    <div class="form-control">
                        <label for="edit-tax-sticker-upload" class="cursor-pointer">
                            <span class="label-text mb-1 text-xs sm:text-sm">ป้ายภาษี</span>
                            <img id="edit-tax-sticker-preview" src="" class="w-full h-24 object-cover rounded-lg border-2 border-dashed border-base-300 p-1" alt="Preview">
                        </label>
                        <input type="file" name="photo_tax_sticker" id="edit-tax-sticker-upload" class="hidden" accept="image/*">
                    </div>
                    <!-- Front View -->
                    <div class="form-control">
                        <label for="edit-front-view-upload" class="cursor-pointer">
                            <span class="label-text mb-1 text-xs sm:text-sm">รูปถ่ายด้านหน้า</span>
                            <img id="edit-front-view-preview" src="" class="w-full h-24 object-cover rounded-lg border-2 border-dashed border-base-300 p-1" alt="Preview">
                        </label>
                        <input type="file" name="photo_front" id="edit-front-view-upload" class="hidden" accept="image/*">
                    </div>
                    <!-- Rear View -->
                    <div class="form-control">
                        <label for="edit-rear-view-upload" class="cursor-pointer">
                            <span class="label-text mb-1 text-xs sm:text-sm">รูปถ่ายด้านหลัง</span>
                            <img id="edit-rear-view-preview" src="" class="w-full h-24 object-cover rounded-lg border-2 border-dashed border-base-300 p-1" alt="Preview">
                        </label>
                        <input type="file" name="photo_rear" id="edit-rear-view-upload" class="hidden" accept="image/*">
                    </div>
                </div>

                <div class="modal-action mt-6">
                    <button type="button" id="cancel-edit-btn" class="btn btn-ghost">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

    <!-- [CORRECTED] Image Zoom Modal - To match admin's fullscreen behavior -->
    <dialog id="image_zoom_modal" class="modal">
        <div class="modal-box w-full max-w-none p-4 bg-transparent shadow-none flex justify-center items-center">
            <div class="relative">
                <img id="zoomed_image" src="" alt="ขยายรูปภาพ" class="rounded-lg max-h-[90vh] max-w-[90vw] object-contain">
                <form method="dialog">
                    <button class="btn btn-circle btn-sm absolute -top-3 -right-3 bg-black/50 hover:bg-black/75 text-white border-none z-10">✕</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>



    <!-- Delete Confirmation Modal -->
    <dialog id="delete_confirm_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-error"><i class="fa-solid fa-triangle-exclamation mr-2"></i>ยืนยันการลบคำร้อง</h3>
            <p class="py-4">คุณแน่ใจหรือไม่ว่าต้องการลบคำร้องนี้? การกระทำนี้ไม่สามารถย้อนกลับได้</p>
            <div class="modal-action">
                <form method="dialog"><button class="btn btn-sm">ยกเลิก</button></form>
                <form id="deleteRequestForm" action="../../../controllers/user/vehicle/delete_vehicle_process.php" method="POST">
                    <input type="hidden" name="request_id" id="delete-request-id">
                    <button type="submit" class="btn btn-sm btn-error">ยืนยันการลบ</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>

