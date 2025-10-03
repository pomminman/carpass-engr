<?php
// app/views/user/home/dashboard.php
require_once __DIR__ . '/../shared/auth_check.php';

// Fetch active application period
$active_period = null;
$sql_period = "SELECT * FROM application_periods WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date LIMIT 1";
$result_period = $conn->query($sql_period);
if ($result_period->num_rows > 0) {
    $active_period = $result_period->fetch_assoc();
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
            <div class="w-14 h-14 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                <img src="<?php echo htmlspecialchars($user_photo_path); ?>" class="object-cover w-full h-full" />
            </div>
        </div>
        <div>
            <h2 class="card-title text-base"><?php echo htmlspecialchars($user['title'] . ' ' . $user['firstname'] . ' ' . $user['lastname']); ?></h2>
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
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter active ring-2 ring-primary" data-filter="all">
            <div class="card-body p-3 flex-row items-center gap-4">
                <i class="fa-solid fa-layer-group text-2xl text-info opacity-80"></i>
                <div>
                    <div class="text-xl font-bold"><?php echo $stats['all']; ?></div>
                    <div class="text-xs text-base-content/70">ทั้งหมด</div>
                </div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter" data-filter="approved">
            <div class="card-body p-3 flex-row items-center gap-4">
                <i class="fa-solid fa-check-to-slot text-2xl text-success opacity-80"></i>
                <div>
                    <div class="text-xl font-bold text-success"><?php echo $stats['approved']; ?></div>
                    <div class="text-xs text-base-content/70">อนุมัติ</div>
                </div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter" data-filter="pending">
            <div class="card-body p-3 flex-row items-center gap-4">
                <i class="fa-solid fa-clock text-2xl text-warning opacity-80"></i>
                <div>
                    <div class="text-xl font-bold text-warning"><?php echo $stats['pending']; ?></div>
                    <div class="text-xs text-base-content/70">รออนุมัติ</div>
                </div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter" data-filter="rejected">
            <div class="card-body p-3 flex-row items-center gap-4">
                <i class="fa-solid fa-ban text-2xl text-error opacity-80"></i>
                <div>
                    <div class="text-xl font-bold text-error"><?php echo $stats['rejected']; ?></div>
                    <div class="text-xs text-base-content/70">ไม่ผ่าน</div>
                </div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm cursor-pointer hover:shadow-lg transition-shadow stat-filter" data-filter="expired">
            <div class="card-body p-3 flex-row items-center gap-4">
                <i class="fa-solid fa-calendar-xmark text-2xl text-base-content/50 opacity-80"></i>
                <div>
                    <div class="text-xl font-bold text-base-content/50"><?php echo $stats['expired']; ?></div>
                    <div class="text-xs text-base-content/70">หมดอายุ</div>
                </div>
            </div>
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
<div id="vehicle-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
    <!-- Loading Spinner Placeholder -->
    <div id="grid-loader" class="col-span-full text-center p-8 text-base-content/60">
        <span class="loading loading-spinner loading-lg text-primary"></span>
        <p class="mt-4">กำลังโหลดข้อมูลยานพาหนะ...</p>
    </div>
    <!-- This message is shown if no requests exist at all -->
    <div id="no-requests-message" class="col-span-full text-center p-8 text-base-content/60 hidden">
        <i class="fa-solid fa-folder-open fa-3x mb-4"></i>
        <p>ยังไม่พบข้อมูลคำร้อง</p>
    </div>
    <!-- This message is shown if filters result in no matches -->
    <div id="no-results-message" class="col-span-full text-center p-8 text-base-content/60 hidden">
        <i class="fa-solid fa-magnifying-glass fa-3x mb-4"></i>
        <p>ไม่พบข้อมูลที่ตรงกับการค้นหา</p>
    </div>
</div>

<!-- Request Details Modal -->
<dialog id="request_details_modal" class="modal modal-fade">
    <div class="modal-box w-11/12 max-w-5xl p-0">
        
        <!-- Loader State -->
        <div id="modal-loader" class="min-h-[400px] flex flex-col items-center justify-center text-center">
            <span class="loading loading-spinner loading-lg text-primary"></span>
            <p class="mt-4">กำลังโหลดรายละเอียด...</p>
        </div>

        <!-- VIEW DETAILS SECTION -->
        <div id="modal-content-wrapper" class="hidden">
            <div class="p-4 sm:p-5">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-grow">
                         <h3 id="modal-license-plate" class="font-bold text-lg sm:text-xl"></h3>
                         <p id="modal-brand-model" class="text-sm text-base-content/70"></p>
                         <div id="modal-card-status" class="mt-2"></div>
                    </div>
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost">✕</button>
                    </form>
                </div>
                <div id="modal-rejection-reason-box" class="alert alert-error alert-soft hidden my-4 text-sm">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div>
                        <h3 class="font-bold">เหตุผลที่ไม่ผ่านการอนุมัติ</h3>
                        <p id="modal-rejection-reason-text" class="text-sm"></p>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-base-200 p-4 sm:p-5">
                 <div class="space-y-3">
                     <div id="modal-card-number-box" class="card text-center hidden p-2">
                         <div class="text-sm opacity-80">เลขที่บัตร</div>
                         <div class="text-2xl font-bold"><span></span></div>
                     </div>
                     <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 space-y-1 text-sm" id="modal-card-info-list"></div></div>
                     <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 space-y-1 text-sm" id="modal-vehicle-info-list"></div></div>
                     <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 space-y-1 text-sm" id="modal-owner-info-list"></div></div>
                 </div>
                 <div class="space-y-3">
                     <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
                         <h4 class="font-semibold text-sm mb-2 text-center">หลักฐานประกอบ</h4>
                         <div id="modal-evidence-gallery" class="grid grid-cols-2 gap-2"></div>
                     </div></div>
                 </div>
            </div>
            
             <div class="p-4 flex flex-wrap justify-end items-center gap-2" id="modal-action-buttons"></div>
        </div>

        <!-- [MODIFIED] EDIT FORM SECTION -->
        <div id="modal-edit-form-wrapper" class="hidden">
             <form action="../../../controllers/user/vehicle/edit_vehicle_process.php" method="POST" enctype="multipart/form-data" id="editVehicleForm" novalidate>
                 <input type="hidden" name="request_id" id="edit-request-id">
                 <input type="hidden" name="can_edit_license" id="edit-can-edit-license" value="false">
                 <h3 class="font-bold text-lg text-center p-4 bg-base-200">แก้ไขข้อมูลคำร้อง</h3>

                 <div class="p-4 space-y-4">
                    
                    <fieldset class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 rounded-lg border p-4 pt-2">
                        <legend class="px-2 text-sm font-semibold text-base-content/80">ข้อมูลหลัก</legend>
                        
                        <!-- License Plate Edit Section -->
                        <div id="edit-license-section" class="hidden md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                            <div class="form-control">
                                <div class="label py-1"><span class="label-text text-xs">เลขทะเบียนรถ</span></div>
                                <input type="text" name="license_plate" id="edit-license-plate" placeholder="เช่น 1กข1234" class="input input-sm input-bordered w-full" oninput="this.value = this.value.replace(/[^ก-๙0-9]/g, '')" />
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                            <div class="form-control">
                                <div class="label py-1"><span class="label-text text-xs">จังหวัดทะเบียนรถ</span></div>
                                <select name="license_province" id="edit-license-province" class="select select-sm select-bordered">
                                    <option disabled selected value="">เลือกจังหวัด</option>
                                    <?php if(isset($provinces)) foreach ($provinces as $province): ?>
                                    <option value="<?= htmlspecialchars($province); ?>"><?= htmlspecialchars($province); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="form-control"><div class="label py-1"><span class="label-text text-xs">ยี่ห้อรถ</span></div><select name="vehicle_brand" id="edit-vehicle-brand" class="select select-sm select-bordered" required><option disabled selected value="">เลือกยี่ห้อ</option><?php if(isset($car_brands)) foreach ($car_brands as $brand): ?><option value="<?= htmlspecialchars($brand); ?>"><?= htmlspecialchars($brand); ?></option><?php endforeach; ?></select><p class="error-message hidden text-error text-xs mt-1"></p></div>
                            <div class="form-control"><div class="label py-1"><span class="label-text text-xs">รุ่นรถ (อังกฤษ)</span></div><input type="text" name="vehicle_model" id="edit-vehicle-model" placeholder="เช่น COROLLA, CIVIC" class="input input-sm input-bordered w-full" required oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~`]/g, '')" /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                            <div class="form-control"><div class="label py-1"><span class="label-text text-xs">สีรถ</span></div><input type="text" name="vehicle_color" id="edit-vehicle-color" placeholder="เช่น ดำ, ขาว, บรอนซ์เงิน" class="input input-sm input-bordered w-full" required oninput="this.value = this.value.replace(/[^ก-๙\s!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~`]/g, '')"/><p class="error-message hidden text-error text-xs mt-1"></p></div>
                        </div>

                        <div class="space-y-2">
                            <div class="form-control"><div class="label py-1"><span class="label-text text-xs">วันสิ้นอายุภาษี</span></div><div class="grid grid-cols-3 gap-2"><select name="tax_day" id="edit-tax-day" class="select select-sm select-bordered" required><option disabled selected value="">วัน</option></select><select name="tax_month" id="edit-tax-month" class="select select-sm select-bordered" required><option disabled selected value="">เดือน</option></select><select name="tax_year" id="edit-tax-year" class="select select-sm select-bordered" required><option disabled selected value="">ปี</option></select></div><p class="error-message hidden text-error text-xs mt-1"></p></div>
                            <div class="form-control"><div class="label py-1"><span class="label-text text-xs">ความเป็นเจ้าของ</span></div><select name="owner_type" id="edit-owner-type" class="select select-sm select-bordered" required><option disabled selected value="">เลือกความเป็นเจ้าของ</option><option value="self">รถชื่อตนเอง</option><option value="other">รถคนอื่น</option></select><p class="error-message hidden text-error text-xs mt-1"></p></div>
                            <div id="edit-other-owner-details" class="hidden space-y-2 pt-1"><div class="form-control"><div class="label py-1"><span class="label-text text-xs">ชื่อ-สกุล เจ้าของ</span></div><input type="text" name="other_owner_name" id="edit-other-owner-name" placeholder="เช่น นายสมชาย ใจดี" class="input input-sm input-bordered w-full" oninput="this.value = this.value.replace(/[^ก-๙\s!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~`]/g, '')" /><p class="error-message hidden text-error text-xs mt-1"></p></div><div class="form-control"><div class="label py-1"><span class="label-text text-xs">เกี่ยวข้องเป็น</span></div><input type="text" name="other_owner_relation" id="edit-other-owner-relation" placeholder="เช่น บิดา, มารดา, เพื่อน" class="input input-sm input-bordered w-full" oninput="this.value = this.value.replace(/[^ก-๙\s!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~`]/g, '')" /><p class="error-message hidden text-error text-xs mt-1"></p></div></div>
                        </div>
                    </fieldset>
                    
                    <fieldset class="rounded-lg border p-4 pt-2">
                        <legend class="px-2 text-sm font-semibold text-base-content/80">หลักฐาน (อัปโหลดใหม่เฉพาะที่ต้องการเปลี่ยน)</legend>
                         <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                            <div class="form-control w-full"><label class="label pb-1"><span class="label-text text-xs">สำเนาทะเบียนรถ</span></label><div class="flex justify-center items-center bg-base-200 p-1 rounded-box border h-24"><img id="edit-reg-copy-preview" src="" class="max-w-full max-h-full object-contain"></div><input type="file" name="reg_copy_upload" id="edit-reg-copy-upload" class="file-input file-input-sm file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png"><p class="error-message hidden text-error text-xs mt-1"></p></div>
                            <div class="form-control w-full"><label class="label pb-1"><span class="label-text text-xs">ป้ายภาษี</span></label><div class="flex justify-center items-center bg-base-200 p-1 rounded-box border h-24"><img id="edit-tax-sticker-preview" src="" class="max-w-full max-h-full object-contain"></div><input type="file" name="tax_sticker_upload" id="edit-tax-sticker-upload" class="file-input file-input-sm file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png"><p class="error-message hidden text-error text-xs mt-1"></p></div>
                            <div class="form-control w-full"><label class="label pb-1"><span class="label-text text-xs">รูปถ่ายด้านหน้า</span></label><div class="flex justify-center items-center bg-base-200 p-1 rounded-box border h-24"><img id="edit-front-view-preview" src="" class="max-w-full max-h-full object-contain"></div><input type="file" name="front_view_upload" id="edit-front-view-upload" class="file-input file-input-sm file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png"><p class="error-message hidden text-error text-xs mt-1"></p></div>
                            <div class="form-control w-full"><label class="label pb-1"><span class="label-text text-xs">รูปถ่ายด้านหลัง</span></label><div class="flex justify-center items-center bg-base-200 p-1 rounded-box border h-24"><img id="edit-rear-view-preview" src="" class="max-w-full max-h-full object-contain"></div><input type="file" name="rear_view_upload" id="edit-rear-view-upload" class="file-input file-input-sm file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png"><p class="error-message hidden text-error text-xs mt-1"></p></div>
                        </div>
                    </fieldset>
                 </div>
                 <div class="p-4 bg-base-200 flex justify-end items-center gap-2">
                     <button type="button" id="cancel-edit-btn" class="btn btn-sm btn-ghost">ยกเลิก</button>
                     <button type="submit" class="btn btn-sm btn-primary">บันทึกการแก้ไข</button>
                 </div>
             </form>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<!-- Delete Confirmation Modal -->
<dialog id="delete_confirm_modal" class="modal modal-fade">
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

<!-- Loading Modal -->
<dialog id="loading_modal" class="modal modal-middle modal-fade">
    <div class="modal-box text-center">
        <span class="loading loading-spinner loading-lg text-primary"></span>
        <h3 class="font-bold text-lg mt-4">กรุณารอสักครู่...</h3>
    </div>
</dialog>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

