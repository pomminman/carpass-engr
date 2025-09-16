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
<div id="add-vehicle-section" class="space-y-4">
     <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold"><?php echo $is_renewal ? 'ต่ออายุบัตรผ่านยานพาหนะ' : 'เพิ่มยานพาหนะ / ยื่นคำร้อง'; ?></h1>
            <p class="text-sm sm:text-base text-base-content/70">กรอกข้อมูลให้ครบถ้วนเพื่อยื่นคำร้อง</p>
        </div>
    </div>

    <?php if ($active_period): ?>
        <div role="alert" class="alert alert-success alert-soft flex-row items-center text-left">
             <i class="fa-solid fa-bullhorn text-lg"></i>
            <div>
                <h3 class="font-bold text-sm sm:text-base">เปิดรับคำร้อง: <?php echo htmlspecialchars($active_period['period_name']); ?></h3>
                <div class="text-xs sm:text-sm">
                    ยื่นคำร้องได้ถึงวันที่ <?php echo format_thai_date_helper($active_period['end_date']); ?> (บัตรหมดอายุ <?php echo format_thai_date_helper($active_period['card_expiry_date']); ?>)
                </div>
            </div>
        </div>

        <form action="../../../controllers/user/vehicle/add_vehicle_process.php" method="POST" enctype="multipart/form-data" id="addVehicleForm" data-renewal-tax-date="<?php echo htmlspecialchars($renewal_data['tax_expiry_date'] ?? ''); ?>" novalidate>
            
            <!-- STEP 1: CHECK VEHICLE (จะถูกซ่อนถ้าเป็นการต่ออายุ) -->
            <div id="vehicle-check-section" class="card bg-base-100 shadow <?php echo $is_renewal ? 'hidden' : ''; ?>">
                <div class="card-body p-4">
                    <div class="text-center">
                        <h3 class="font-semibold text-lg flex flex-col sm:flex-row justify-center items-center gap-x-2"><span>ขั้นตอนที่ 1</span><span class="hidden sm:inline">:</span> <span>ตรวจสอบข้อมูลยานพาหนะ</span></h3>
                        <p class="text-sm text-base-content/70">กรุณากรอกข้อมูลเพื่อตรวจสอบว่ายานพาหนะของท่านเคยยื่นคำร้องแล้วหรือไม่</p>
                    </div>
                     <div role="alert" class="alert alert-error alert-soft my-4 text-sm justify-start text-left">
                        <i class="fa-solid fa-ban"></i>
                        <span><b>ไม่รับพิจารณารถป้ายแดง</b> (โปรดรอจนได้รับป้ายทะเบียนขาว)</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        <div class="form-control w-full">
                            <div class="label py-1"><span class="label-text font-medium text-xs">ประเภทรถ</span></div>
                            <select id="check-vehicle-type" class="select select-bordered" required>
                                <option disabled selected value="">เลือกประเภทรถ</option>
                                <option value="รถยนต์">รถยนต์</option>
                                <option value="รถจักรยานยนต์">รถจักรยานยนต์</option>
                            </select>
                            <p class="error-message hidden text-error text-xs mt-1"></p>
                        </div>
                        <div class="form-control w-full">
                            <div class="label py-1"><span class="label-text font-medium text-xs">เลขทะเบียนรถ</span></div>
                            <input type="text" id="check-license-plate" placeholder="เช่น 1กข1234" class="input input-bordered w-full" required oninput="this.value = this.value.replace(/[^ก-๙0-9]/g, '')" />
                            <p class="error-message hidden text-error text-xs mt-1"></p>
                        </div>
                        <div class="form-control w-full">
                            <div class="label py-1"><span class="label-text font-medium text-xs">จังหวัดทะเบียนรถ</span></div>
                            <select id="check-license-province" class="select select-bordered" required>
                                <option disabled selected value="">เลือกจังหวัด</option>
                                <?php foreach ($provinces as $province): ?>
                                    <option value="<?php echo htmlspecialchars($province); ?>"><?php echo htmlspecialchars($province); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="error-message hidden text-error text-xs mt-1"></p>
                        </div>
                    </div>
                    <div class="card-actions justify-center mt-4 gap-4">
                        <button type="button" id="check-vehicle-btn" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass mr-2"></i>ตรวจสอบข้อมูล</button>
                    </div>
                </div>
            </div>
            
            <!-- STEP 2: DETAILS (จะแสดงทันทีถ้าเป็นการต่ออายุ) -->
            <div id="vehicle-details-section" class="<?php echo $is_renewal ? '' : 'hidden'; ?>">
                <div class="text-center mb-4">
                    <h3 class="font-semibold text-lg">ขั้นตอนที่ 2: กรอกข้อมูลและแนบหลักฐาน</h3>
                    <p class="text-sm text-base-content/70">โปรดตรวจสอบข้อมูลให้ถูกต้องครบถ้วน</p>
                </div>
                <!-- Vehicle Info -->
                <div class="card bg-base-100 shadow">
                    <div class="card-body p-4">
                        <h4 class="card-title text-sm font-semibold">ข้อมูลยานพาหนะ</h4>
                        <div class="stats stats-vertical sm:stats-horizontal shadow bg-base-200">
                          <div class="stat p-3">
                            <div class="stat-title text-xs">ประเภทรถ</div>
                            <div id="display-vehicle-type" class="stat-value text-base text-neutral"><?php echo htmlspecialchars($renewal_data['vehicle_type'] ?? ''); ?></div>
                          </div>
                          <div class="stat p-3">
                            <div class="stat-title text-xs">เลขทะเบียน</div>
                            <div class="stat-value text-base text-neutral">
                                <span id="display-license-plate"><?php echo htmlspecialchars($renewal_data['license_plate'] ?? ''); ?></span>
                                <span id="display-license-province"><?php echo htmlspecialchars($renewal_data['province'] ?? ''); ?></span>
                            </div>
                          </div>
                        </div>
                        <input type="hidden" name="vehicle_type" value="<?php echo htmlspecialchars($renewal_data['vehicle_type'] ?? ''); ?>" required />
                        <input type="hidden" name="license_plate" value="<?php echo htmlspecialchars($renewal_data['license_plate'] ?? ''); ?>" required />
                        <input type="hidden" name="license_province" value="<?php echo htmlspecialchars($renewal_data['province'] ?? ''); ?>" required />
                        <div class="grid grid-cols-1 lg:grid-cols-5 gap-2">
                            <div class="form-control w-full"><div class="label py-1"><span class="label-text font-medium text-xs">ยี่ห้อรถ</span></div><select name="vehicle_brand" class="select select-bordered" required><option disabled selected value="">เลือกยี่ห้อ</option><?php foreach ($car_brands as $brand): ?><option value="<?php echo htmlspecialchars($brand); ?>" <?php echo (isset($renewal_data['brand']) && $renewal_data['brand'] == $brand) ? 'selected' : ''; ?>><?php echo htmlspecialchars($brand); ?></option><?php endforeach; ?></select><p class="error-message hidden text-error text-xs mt-1"></p></div>
                            <div class="form-control w-full"><div class="label py-1"><span class="label-text font-medium text-xs">รุ่นรถ (ภาษาอังกฤษ)</span></div><input type="text" name="vehicle_model" placeholder="เช่น COROLLA" class="input input-bordered w-full" value="<?php echo htmlspecialchars($renewal_data['model'] ?? ''); ?>" required oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~`]/g, '')" /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                            <div class="form-control w-full"><div class="label py-1"><span class="label-text font-medium text-xs">สีรถ</span></div><input type="text" name="vehicle_color" placeholder="เช่น ดำ" class="input input-bordered w-full" value="<?php echo htmlspecialchars($renewal_data['color'] ?? ''); ?>" required oninput="this.value = this.value.replace(/[^ก-๙\s!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~`]/g, '')" /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                            <div class="form-control w-full lg:col-span-2"><div class="label py-1"><span class="label-text font-medium text-xs">วันสิ้นอายุภาษีรถ</span></div><div class="grid grid-cols-3 gap-2"><select name="tax_day" class="select select-bordered" required></select><select name="tax_month" class="select select-bordered" required></select><select name="tax_year" class="select select-bordered" required></select></div><p class="error-message hidden text-error text-xs mt-1"></p></div>
                        </div>
                    </div>
                </div>
                 <!-- Owner Info -->
                <div class="card bg-base-100 shadow mt-4"><div class="card-body p-4"><h4 class="card-title text-sm font-semibold">ข้อมูลความเป็นเจ้าของ</h4><div class="form-control w-full max-w-xs"><div class="label py-1"><span class="label-text font-medium text-xs">เป็นรถของใคร?</span></div><select name="owner_type" class="select select-bordered" required><option disabled selected value="">กรุณาเลือก</option><option value="self" <?php echo (isset($renewal_data['owner_type']) && $renewal_data['owner_type'] == 'self') ? 'selected' : ''; ?>>รถชื่อตนเอง</option><option value="other" <?php echo (isset($renewal_data['owner_type']) && $renewal_data['owner_type'] == 'other') ? 'selected' : ''; ?>>รถคนอื่น</option></select><p class="error-message hidden text-error text-xs mt-1"></p></div><div id="other-owner-details" class="hidden mt-4"><div role="alert" class="alert alert-warning alert-soft text-sm mb-4 justify-start text-left"><i class="fa-solid fa-triangle-exclamation"></i><span><b>คำเตือน:</b> หากยานพาหนะเกิดปัญหาใดๆ ผู้ยื่นคำร้องจะต้องเป็นผู้รับผิดชอบ</span></div><div class="grid grid-cols-1 md:grid-cols-2 gap-2"><div class="form-control w-full"><div class="label py-1"><span class="label-text font-medium text-xs">คำนำหน้า-ชื่อ-สกุล เจ้าของรถ</span></div><input type="text" name="other_owner_name" placeholder="เช่น นายสมชาย ใจดี" class="input input-bordered w-full" value="<?php echo htmlspecialchars($renewal_data['other_owner_name'] ?? ''); ?>" oninput="this.value = this.value.replace(/[^ก-๙\s!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~`]/g, '')" /><p class="error-message hidden text-error text-xs mt-1"></p></div><div class="form-control w-full"><div class="label py-1"><span class="label-text font-medium text-xs">เกี่ยวข้องเป็น</span></div><input type="text" name="other_owner_relation" placeholder="เช่น บิดา, มารดา, เพื่อน" class="input input-bordered w-full" value="<?php echo htmlspecialchars($renewal_data['other_owner_relation'] ?? ''); ?>" oninput="this.value = this.value.replace(/[^ก-๙\s!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~`]/g, '')" /><p class="error-message hidden text-error text-xs mt-1"></p></div></div></div></div></div>
                <!-- Evidence -->
                <div class="card bg-base-100 shadow mt-4"><div class="card-body p-4"><h4 class="card-title text-sm font-semibold">หลักฐานรูปถ่าย</h4><div role="alert" class="alert alert-info alert-soft my-4 text-sm justify-start text-left"><i class="fa-solid fa-circle-info"></i><span>โปรดตรวจสอบความคมชัดของรูปถ่าย (.jpg, .png) และขนาดไฟล์ต้องไม่เกิน 5 MB</span></div><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="form-control w-full"><label class="block font-medium mb-1 text-center text-xs">สำเนาทะเบียนรถ</label><div class="flex justify-center bg-base-200 p-2 rounded-box border overflow-hidden h-32"><img id="reg-copy-preview" src="/public/assets/images/registration.jpg" alt="ตัวอย่าง" class="w-full h-full object-contain"></div><input type="file" id="reg_copy_upload" name="reg_copy_upload" class="file-input file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png" required><p class="error-message hidden text-error text-xs mt-1"></p></div>
                    <div class="form-control w-full"><label class="block font-medium mb-1 text-center text-xs">ป้ายภาษี (ป้ายวงกลม)</label><div class="flex justify-center bg-base-200 p-2 rounded-box border overflow-hidden h-32"><img id="tax-sticker-preview" src="/public/assets/images/tax_sticker.jpg" alt="ตัวอย่าง" class="w-full h-full object-contain"></div><input type="file" id="tax_sticker_upload" name="tax_sticker_upload" class="file-input file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png" required><p class="error-message hidden text-error text-xs mt-1"></p></div>
                    <div class="form-control w-full"><label class="block font-medium mb-1 text-center text-xs">รูปถ่ายรถด้านหน้า</label><div class="flex justify-center bg-base-200 p-2 rounded-box border overflow-hidden h-32"><img id="front-view-preview" src="/public/assets/images/front_view.png" alt="ตัวอย่าง" class="w-full h-full object-contain"></div><input type="file" id="front_view_upload" name="front_view_upload" class="file-input file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png" required><p class="error-message hidden text-error text-xs mt-1"></p></div>
                    <div class="form-control w-full"><label class="block font-medium mb-1 text-center text-xs">รูปถ่ายรถด้านหลัง</label><div class="flex justify-center bg-base-200 p-2 rounded-box border overflow-hidden h-32"><img id="rear-view-preview" src="/public/assets/images/rear_view.png" alt="ตัวอย่าง" class="w-full h-full object-contain"></div><input type="file" id="rear_view_upload" name="rear_view_upload" class="file-input file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png" required><p class="error-message hidden text-error text-xs mt-1"></p></div>
                </div></div></div>
                <!-- Agreement and Submit -->
                <div class="card bg-base-100 shadow mt-4"><div class="card-body p-4 items-center"><div class="form-control w-full max-w-md"><label class="label cursor-pointer justify-start gap-4"><input type="checkbox" name="terms_confirm" class="checkbox checkbox-primary" required /><span class="label-text text-sm">ข้าพเจ้ายืนยันและยอมรับเงื่อนไขการใช้งาน</span></label><div class="text-xs text-base-content/70 pl-10"><ul class="list-disc list-inside"><li>ข้าพเจ้ายืนยันว่าข้อมูลที่กรอกข้างต้นเป็นความจริงทุกประการ</li><li>ยินยอมให้เจ้าหน้าที่ตรวจสอบข้อมูลส่วนบุคคลและข้อมูลยานพาหนะ</li><li>รับทราบและจะปฏิบัติตามกฎระเบียบการผ่านเข้า-ออกของหน่วยอย่างเคร่งครัด</li></ul></div><p class="error-message hidden text-error text-xs mt-1 pl-10"></p></div><div class="card-actions justify-center mt-6 gap-4"><button type="button" id="reset-form-btn" class="btn btn-ghost"><i class="fa-solid fa-rotate-left mr-2"></i>รีเซ็ท</button><button type="button" id="submit-request-btn" class="btn btn-primary"><i class="fa-solid fa-paper-plane mr-2"></i>ยืนยันและส่งคำร้อง</button></div></div></div>
            </div>
        </form>
    <?php else: ?>
        <div class="card bg-base-100 shadow"><div class="card-body items-center text-center"><div role="alert" class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation text-lg"></i><div><h3 class="font-bold text-sm sm:text-base">ระบบปิดรับคำร้องชั่วคราว</h3><div class="text-xs sm:text-sm">ขณะนี้ยังไม่ถึงช่วงเวลาสำหรับการยื่นคำร้อง กรุณาตรวจสอบกำหนดการอีกครั้ง</div></div></div><div class="mt-6"><a href="dashboard.php" class="btn btn-primary">กลับหน้าหลัก</a></div></div></div>
    <?php endif; ?>
</div>

<dialog id="resetConfirmModal" class="modal modal-middle">
  <div class="modal-box w-11/12 max-w-sm">
    <h3 class="font-bold text-lg">ยืนยันการรีเซ็ท</h3>
    <p class="py-4 text-sm">คุณแน่ใจหรือไม่ว่าต้องการล้างข้อมูลและกลับไปเริ่มต้นที่ขั้นตอนที่ 1 ใหม่?</p>
    <div class="modal-action">
      <form method="dialog">
        <button class="btn">ยกเลิก</button>
      </form>
      <button id="confirm-reset-btn" class="btn btn-error">ยืนยัน</button>
    </div>
  </div>
   <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<!-- Loading Modal -->
<dialog id="loading_modal" class="modal modal-middle">
    <div class="modal-box text-center">
        <span class="loading loading-spinner loading-lg text-primary"></span>
        <h3 class="font-bold text-lg mt-4">กรุณารอสักครู่...</h3>
    </div>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const plateInput = document.getElementById('check-license-plate');
    if (!plateInput) return;

    const checkBtn = document.getElementById('check-vehicle-btn');
    if (!checkBtn) return;

    // Helper function to show error
    const showError = (element, message) => {
        const parent = element.closest('.form-control');
        const errorEl = parent?.querySelector('.error-message');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
        element.classList.add('input-error');
    };

    // Helper function to clear error
    const clearError = (element) => {
        const parent = element.closest('.form-control');
        const errorEl = parent?.querySelector('.error-message');
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
        }
        element.classList.remove('input-error');
    };

    // Add a new validation listener that runs before the existing one
    checkBtn.addEventListener('click', function(event) {
        clearError(plateInput); // Clear previous errors first

        const plateValue = plateInput.value.trim();
        if (plateValue === '') {
            // Let the original required validation handle this
            return;
        }

        const hasThai = /[ก-๙]/.test(plateValue);
        const hasNumber = /[0-9]/.test(plateValue);

        if (!hasThai || !hasNumber) {
            showError(plateInput, 'ทะเบียนรถต้องมีทั้งตัวอักษรภาษาไทยและตัวเลข');
            // Stop the event, preventing the original click handler in script.js from running
            event.stopImmediatePropagation(); 
        }

    }, true); // Use capture phase to ensure this listener runs first
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

