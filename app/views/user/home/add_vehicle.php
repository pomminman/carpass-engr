<?php
// app/views/user/home/add_vehicle.php
require_once __DIR__ . '/../shared/auth_check.php';

// --- ดึงข้อมูลรอบการสมัครที่เปิดใช้งานอยู่ ---
$active_period = null;
$sql_period = "SELECT * FROM application_periods WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date LIMIT 1";
$result_period = $conn->query($sql_period);
if ($result_period->num_rows > 0) {
    $active_period = $result_period->fetch_assoc();
}

// [NEW] Logic to pre-calculate the estimated pickup date
function calculate_pickup_date() {
    // Note: In a real-world scenario, holidays should be managed in a database table.
    $holidays = ['2025-10-13', '2025-10-23', '2025-12-05', '2025-12-10', '2025-12-31', '2026-01-01']; 
    $working_days_to_add = 15;
    $current_date = new DateTime();
    while ($working_days_to_add > 0) {
        $current_date->modify('+1 day');
        $day_of_week = $current_date->format('N'); // 1 (Mon) - 7 (Sun)
        $date_string = $current_date->format('Y-m-d');
        if ($day_of_week < 6 && !in_array($date_string, $holidays)) {
            $working_days_to_add--;
        }
    }
    return $current_date->format('Y-m-d');
}
$estimated_pickup_date = calculate_pickup_date();


// [ใหม่] ตรวจสอบว่าเป็นการต่ออายุหรือไม่
$is_renewal = false;
$renewal_data = null;
if (isset($_GET['renew_id']) && is_numeric($_GET['renew_id'])) {
    $renew_vehicle_id = $_GET['renew_id'];
    $sql_renew = "SELECT v.*, vr.tax_expiry_date, vr.owner_type, vr.other_owner_name, vr.other_owner_relation 
                  FROM vehicles v
                  LEFT JOIN (
                      SELECT * FROM vehicle_requests 
                      WHERE vehicle_id = ? 
                      ORDER BY created_at DESC 
                      LIMIT 1
                  ) AS vr ON v.id = vr.vehicle_id
                  WHERE v.id = ? AND v.user_id = ?";
    $stmt_renew = $conn->prepare($sql_renew);
    $stmt_renew->bind_param("iii", $renew_vehicle_id, $renew_vehicle_id, $user_id);
    $stmt_renew->execute();
    $result_renew = $stmt_renew->get_result();
    if ($result_renew->num_rows > 0) {
        $is_renewal = true;
        $renewal_data = $result_renew->fetch_assoc();
    }
    $stmt_renew->close();
}


require_once __DIR__ . '/../layouts/header.php';
?>
<!-- Main Content for Add Vehicle -->
<div id="add-vehicle-section" class="mx-auto space-y-4" data-page="add_vehicle" data-is-renewal="<?php echo $is_renewal ? 'true' : 'false'; ?>">
     <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold">
            <?php echo $is_renewal ? 'ต่ออายุบัตรผ่านยานพาหนะ' : 'ยื่นคำร้องขอบัตรผ่านยานพาหนะ'; ?>
        </h1>
        <p class="text-base-content/70">กรุณากรอกข้อมูลตามขั้นตอนให้ครบถ้วน</p>
    </div>

    <!-- [FIXED] Stepper Design V2 with initial state classes -->
    <div class="grid grid-cols-2 gap-2 sm:gap-4">
        <div id="step1-indicator" class="p-3 sm:p-4 rounded-lg flex items-center gap-3 transition-all duration-300">
            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <div>
                <p class="font-semibold text-sm sm:text-base">ขั้นตอนที่ 1</p>
                <p class="text-xs sm:text-sm">ตรวจสอบข้อมูล</p>
            </div>
        </div>
        <div id="step2-indicator" class="p-3 sm:p-4 rounded-lg flex items-center gap-3 transition-all duration-300">
             <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-file-lines"></i>
            </div>
            <div>
                <p class="font-semibold text-sm sm:text-base">ขั้นตอนที่ 2</p>
                <p class="text-xs sm:text-sm">กรอกรายละเอียด</p>
            </div>
        </div>
    </div>


    <?php if ($active_period): ?>
        <form action="../../../controllers/user/vehicle/add_vehicle_process.php" method="POST" enctype="multipart/form-data" id="addVehicleForm" data-renewal-tax-date="<?php echo htmlspecialchars($renewal_data['tax_expiry_date'] ?? ''); ?>" data-pickup-date="<?php echo htmlspecialchars($estimated_pickup_date); ?>" novalidate>
            <div class="card bg-base-100 shadow-xl border">
                <div class="card-body p-4 md:p-6">
                    <!-- STEP 1: CHECK VEHICLE -->
                    <div id="vehicle-check-section">
                        <div role="alert" class="alert alert-error alert-soft my-4 text-sm justify-start">
                            <span><i class="fa-solid fa-ban"></i> <b>ไม่รับพิจารณาคำร้องสำหรับรถป้ายแดง</b> (โปรดรอจนได้รับป้ายทะเบียนขาว)</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-base-200 rounded-box">
                            <div class="form-control w-full">
                                <label class="label py-1"><span class="label-text font-medium">ประเภทรถ</span></label>
                                <select id="check-vehicle-type" class="select select-sm select-bordered" required>
                                    <option disabled selected value="">เลือกประเภทรถ</option>
                                    <option value="รถยนต์">รถยนต์</option>
                                    <option value="รถจักรยานยนต์">รถจักรยานยนต์</option>
                                </select>
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                            <div class="form-control w-full">
                                <label class="label py-1"><span class="label-text font-medium">เลขทะเบียนรถ</span></label>
                                <input type="text" id="check-license-plate" placeholder="เช่น 1กข1234" class="input input-sm input-bordered w-full" required oninput="this.value = this.value.replace(/[^ก-๙0-9]/g, '')" />
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                            <div class="form-control w-full">
                                <label class="label py-1"><span class="label-text font-medium">จังหวัดทะเบียนรถ</span></label>
                                <select id="check-license-province" class="select select-sm select-bordered" required>
                                    <option disabled selected value="">เลือกจังหวัด</option>
                                    <?php foreach ($provinces as $province): ?>
                                        <option value="<?php echo htmlspecialchars($province); ?>"><?php echo htmlspecialchars($province); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                        </div>
                        <div class="card-actions justify-center mt-6">
                            <button type="button" id="check-vehicle-btn" class="btn btn-sm btn-primary"><i class="fa-solid fa-arrow-right mr-2"></i>ขั้นตอนถัดไป</button>
                        </div>
                    </div>
                    
                    <!-- STEP 2: DETAILS -->
                    <div id="vehicle-details-section" class="hidden">
                        <div class="stats stats-vertical sm:stats-horizontal shadow w-full mb-4" style="background-color: oklch(0.25 0.08 255.06); color: oklch(1 0 0);">
                            <div class="stat">
                                <div class="flex items-center gap-4">
                                    <i id="display-vehicle-type-icon" class="fa-solid fa-car-side text-3xl opacity-80"></i>
                                    <div>
                                        <div class="stat-title" style="color: oklch(1 0 0);">ประเภทรถ</div>
                                        <div id="display-vehicle-type" class="stat-value text-lg"><?php echo htmlspecialchars($renewal_data['vehicle_type'] ?? ''); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="stat">
                                <div class="flex items-center gap-4">
                                    <i class="fa-regular fa-window-maximize text-3xl opacity-80"></i>
                                    <div>
                                        <div class="stat-title" style="color: oklch(1 0 0);">เลขทะเบียน</div>
                                        <div class="stat-value text-lg">
                                            <span id="display-license-plate"><?php echo htmlspecialchars($renewal_data['license_plate'] ?? ''); ?></span>
                                            <span id="display-license-province"><?php echo htmlspecialchars($renewal_data['province'] ?? ''); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="vehicle_type" value="<?php echo htmlspecialchars($renewal_data['vehicle_type'] ?? ''); ?>" required />
                        <input type="hidden" name="license_plate" value="<?php echo htmlspecialchars($renewal_data['license_plate'] ?? ''); ?>" required />
                        <input type="hidden" name="license_province" value="<?php echo htmlspecialchars($renewal_data['province'] ?? ''); ?>" required />

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Left Column: Information -->
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <div class="divider divider-start text-sm font-semibold m-0">ข้อมูลยานพาหนะและเจ้าของ</div>
                                    <div class="form-control w-full"><label class="label py-1"><span class="label-text font-medium text-xs">ยี่ห้อรถ</span></label><select name="vehicle_brand" class="select select-sm select-bordered" required><option disabled selected value="">เลือกยี่ห้อ</option><?php foreach ($car_brands as $brand): ?><option value="<?php echo htmlspecialchars($brand); ?>" <?php echo (isset($renewal_data['brand']) && $renewal_data['brand'] == $brand) ? 'selected' : ''; ?>><?php echo htmlspecialchars($brand); ?></option><?php endforeach; ?></select><p class="error-message hidden text-error text-xs mt-1"></p></div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="form-control w-full"><label class="label py-1"><span class="label-text font-medium text-xs">รุ่นรถ (ภาษาอังกฤษ)</span></label><input type="text" name="vehicle_model" placeholder="เช่น COROLLA" class="input input-sm input-bordered w-full" value="<?php echo htmlspecialchars($renewal_data['model'] ?? ''); ?>" required oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~`]/g, '')" /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                                        <div class="form-control w-full"><label class="label py-1"><span class="label-text font-medium text-xs">สีรถ</span></label><input type="text" name="vehicle_color" placeholder="เช่น ดำ" class="input input-sm input-bordered w-full" value="<?php echo htmlspecialchars($renewal_data['color'] ?? ''); ?>" required oninput="this.value = this.value.replace(/[^ก-๙\s!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~`]/g, '')" /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                                    </div>
                                    <div class="form-control w-full"><label class="label py-1"><span class="label-text font-medium text-xs">วันสิ้นอายุภาษีรถ</span></label><div class="grid grid-cols-3 gap-2"><select name="tax_day" class="select select-sm select-bordered" required></select><select name="tax_month" class="select select-sm select-bordered" required></select><select name="tax_year" class="select select-sm select-bordered" required></select></div><p class="error-message hidden text-error text-xs mt-1"></p></div>
                                    <div class="form-control w-full"><label class="label py-1"><span class="label-text font-medium text-xs">เป็นรถของใคร?</span></label><select name="owner_type" class="select select-sm select-bordered" required><option disabled selected value="">กรุณาเลือก</option><option value="self" <?php echo (isset($renewal_data['owner_type']) && $renewal_data['owner_type'] == 'self') ? 'selected' : ''; ?>>รถชื่อตนเอง</option><option value="other" <?php echo (isset($renewal_data['owner_type']) && $renewal_data['owner_type'] == 'other') ? 'selected' : ''; ?>>รถคนอื่น</option></select><p class="error-message hidden text-error text-xs mt-1"></p></div>
                                    <div id="other-owner-details" class="hidden space-y-2 p-3 bg-base-200 rounded-box"><div role="alert" class="alert alert-warning alert-soft text-xs p-2 justify-start text-left"><span><i class="fa-solid fa-triangle-exclamation"></i> <b>คำเตือน:</b> หากยานพาหนะเกิดปัญหาใดๆ ผู้ยื่นคำร้องจะต้องเป็นผู้รับผิดชอบ</span></div><div class="grid grid-cols-2 gap-2"><div class="form-control w-full"><label class="label py-1"><span class="label-text font-medium text-xs">ชื่อ-สกุล เจ้าของรถ</span></label><input type="text" name="other_owner_name" placeholder="เช่น นายสมชาย ใจดี" class="input input-sm input-bordered w-full" value="<?php echo htmlspecialchars($renewal_data['other_owner_name'] ?? ''); ?>" oninput="this.value = this.value.replace(/[^ก-๙\s!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~`]/g, '')" /><p class="error-message hidden text-error text-xs mt-1"></p></div><div class="form-control w-full"><label class="label py-1"><span class="label-text font-medium text-xs">เกี่ยวข้องเป็น</span></label><input type="text" name="other_owner_relation" placeholder="เช่น บิดา, มารดา, เพื่อน" class="input input-sm input-bordered w-full" value="<?php echo htmlspecialchars($renewal_data['other_owner_relation'] ?? ''); ?>" oninput="this.value = this.value.replace(/[^ก-๙\s!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~`]/g, '')" /><p class="error-message hidden text-error text-xs mt-1"></p></div></div></div>
                                </div>
                            </div>

                            <!-- Right Column: Evidence -->
                            <div class="space-y-2">
                                <div class="divider divider-start text-sm font-semibold m-0">หลักฐานประกอบ</div>
                                <div role="alert" class="alert alert-info alert-soft text-xs p-2 justify-start"><span><i class="fa-solid fa-circle-info"></i> โปรดตรวจสอบความคมชัดของรูปถ่าย (.jpg, .png) และขนาดไฟล์ต้องไม่เกิน 5 MB</span></div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="form-control w-full">
                                        <label class="block font-medium mb-1 text-center text-xs">สำเนาทะเบียนรถ</label>
                                        <a href="/public/assets/images/registration.jpg" data-fancybox="evidence-gallery" data-caption="สำเนาทะเบียนรถ">
                                            <div class="flex justify-center bg-base-200 p-2 rounded-box border overflow-hidden h-28">
                                                <img id="reg-copy-preview" src="/public/assets/images/registration.jpg" alt="สำเนาทะเบียนรถ" class="w-full h-full object-contain cursor-pointer">
                                            </div>
                                        </a>
                                        <input type="file" id="reg_copy_upload" name="reg_copy_upload" class="file-input file-input-sm file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png" required>
                                        <p class="error-message hidden text-error text-xs mt-1"></p>
                                    </div>
                                    <div class="form-control w-full">
                                        <label class="block font-medium mb-1 text-center text-xs">ป้ายภาษี (ป้ายวงกลม)</label>
                                        <a href="/public/assets/images/tax_sticker.jpg" data-fancybox="evidence-gallery" data-caption="ป้ายภาษี">
                                            <div class="flex justify-center bg-base-200 p-2 rounded-box border overflow-hidden h-28">
                                                <img id="tax-sticker-preview" src="/public/assets/images/tax_sticker.jpg" alt="ป้ายภาษี" class="w-full h-full object-contain cursor-pointer">
                                            </div>
                                        </a>
                                        <input type="file" id="tax_sticker_upload" name="tax_sticker_upload" class="file-input file-input-sm file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png" required>
                                        <p class="error-message hidden text-error text-xs mt-1"></p>
                                    </div>
                                    <div class="form-control w-full">
                                        <label class="block font-medium mb-1 text-center text-xs">รูปถ่ายรถด้านหน้า</label>
                                        <a href="/public/assets/images/front_view.png" data-fancybox="evidence-gallery" data-caption="รูปถ่ายรถด้านหน้า">
                                            <div class="flex justify-center bg-base-200 p-2 rounded-box border overflow-hidden h-28">
                                                <img id="front-view-preview" src="/public/assets/images/front_view.png" alt="รูปถ่ายรถด้านหน้า" class="w-full h-full object-contain cursor-pointer">
                                            </div>
                                        </a>
                                        <input type="file" id="front_view_upload" name="front_view_upload" class="file-input file-input-sm file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png" required>
                                        <p class="error-message hidden text-error text-xs mt-1"></p>
                                    </div>
                                    <div class="form-control w-full">
                                        <label class="block font-medium mb-1 text-center text-xs">รูปถ่ายรถด้านหลัง</label>
                                        <a href="/public/assets/images/rear_view.png" data-fancybox="evidence-gallery" data-caption="รูปถ่ายรถด้านหลัง">
                                            <div class="flex justify-center bg-base-200 p-2 rounded-box border overflow-hidden h-28">
                                                <img id="rear-view-preview" src="/public/assets/images/rear_view.png" alt="รูปถ่ายรถด้านหลัง" class="w-full h-full object-contain cursor-pointer">
                                            </div>
                                        </a>
                                        <input type="file" id="rear_view_upload" name="rear_view_upload" class="file-input file-input-sm file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png" required>
                                        <p class="error-message hidden text-error text-xs mt-1"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="divider mt-6"></div>
                        
                        <!-- Agreement and Submit -->
                        <div class="flex flex-col items-center gap-4">
                            <div class="form-control w-full max-w-md">
                                <label class="label cursor-pointer justify-start gap-4">
                                    <input type="checkbox" name="terms_confirm" class="checkbox checkbox-sm checkbox-primary" required />
                                    <span class="label-text text-sm font-semibold">ข้าพเจ้ายืนยันและยอมรับเงื่อนไขการใช้งาน</span>
                                </label>
                                <div class="text-xs text-base-content/70 pl-10">
                                    <ul class="list-disc list-inside">
                                        <li>ข้าพเจ้ายืนยันว่าข้อมูลที่กรอกข้างต้นเป็นความจริงทุกประการ</li>
                                        <li>ยินยอมให้เจ้าหน้าที่ตรวจสอบข้อมูลส่วนบุคคลและข้อมูลยานพาหนะ</li>
                                        <li>รับทราบและจะปฏิบัติตามกฎระเบียบการผ่านเข้า-ออกของหน่วยอย่างเคร่งครัด</li>
                                    </ul>
                                </div>
                                <p class="error-message hidden text-error text-xs mt-1 pl-10"></p>
                            </div>
                            <div class="card-actions justify-center mt-2 gap-4">
                                <button type="button" id="back-to-step1-btn" class="btn btn-sm btn-ghost"><i class="fa-solid fa-arrow-left mr-2"></i>ย้อนกลับ</button>
                                <button type="button" id="submit-request-btn" class="btn btn-sm btn-primary"><i class="fa-solid fa-paper-plane mr-2"></i>ยืนยันและส่งคำร้อง</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="card bg-base-100 shadow"><div class="card-body items-center text-center"><div role="alert" class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation text-lg"></i><div><h3 class="font-bold text-sm sm:text-base">ระบบปิดรับคำร้องชั่วคราว</h3><div class="text-xs sm:text-sm">ขณะนี้ยังไม่ถึงช่วงเวลาสำหรับการยื่นคำร้อง กรุณาตรวจสอบกำหนดการอีกครั้ง</div></div></div><div class="mt-6"><a href="dashboard.php" class="btn btn-primary">กลับหน้าหลัก</a></div></div></div>
    <?php endif; ?>
</div>

<!-- Loading Modal -->
<dialog id="loading_modal" class="modal modal-middle">
    <div class="modal-box text-center">
        <span class="loading loading-spinner loading-lg text-primary"></span>
        <h3 class="font-bold text-lg mt-4">กรุณารอสักครู่...</h3>
    </div>
</dialog>

<!-- [เพิ่ม] Review Modal -->
<dialog id="review_request_modal" class="modal modal-middle">
  <div class="modal-box w-11/12 max-w-3xl">
    <h3 class="font-bold text-lg">โปรดตรวจสอบข้อมูลคำร้อง</h3>
    <div id="summary-content" class="py-4 space-y-4 text-sm"></div>
    <div class="modal-action">
      <form method="dialog">
        <button class="btn btn-sm">แก้ไข</button>
      </form>
      <button id="final-submit-btn" class="btn btn-sm btn-primary">ยืนยันและส่งคำร้อง</button>
    </div>
  </div>
</dialog>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


