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

require_once __DIR__ . '/../layouts/header.php';
?>

<main class="flex-grow container mx-auto max-w-6xl p-0 sm:p-6 pb-24">
    <div class="bg-base-100 sm:shadow-xl sm:border sm:border-base-300/50 sm:rounded-2xl">
        <div class="card-body p-4 sm:p-6 md:p-8">
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
    
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                <h2 class="card-title text-xl flex items-center gap-2"><i class="fa-solid fa-file-circle-plus text-primary"></i> เพิ่มยานพาหนะ/ยื่นคำร้อง</h2>
                <a href="dashboard.php" class="btn btn-sm btn-ghost"><i class="fa-solid fa-arrow-left"></i> กลับไปหน้าภาพรวม</a>
            </div>

            <?php if ($active_period): ?>
                <div role="alert" class="alert alert-success alert-soft mb-6">
                    <i class="fa-solid fa-bullhorn text-lg"></i>
                    <div>
                        <h3 class="font-bold">เปิดรับคำร้อง: <?php echo htmlspecialchars($active_period['period_name']); ?></h3>
                        <div class="text-xs">
                            ยื่นคำร้องได้ถึงวันที่ <?php echo format_thai_date_helper($active_period['end_date']); ?> (บัตรหมดอายุ <?php echo format_thai_date_helper($active_period['card_expiry_date']); ?>)
                        </div>
                    </div>
                </div>

                <form action="../../../controllers/user/vehicle/add_vehicle_process.php" method="POST" enctype="multipart/form-data" id="addVehicleForm" novalidate>
                    
                    <!-- STEP 1: CHECK VEHICLE -->
                    <div id="vehicle-check-section">
                        <div class="text-center mb-4">
                            <h3 class="font-semibold text-lg">ตรวจสอบข้อมูลยานพาหนะ</h3>
                            <p class="text-sm text-slate-500">กรุณากรอกข้อมูลเพื่อตรวจสอบว่ายานพาหนะของท่านเคยยื่นคำร้องแล้วหรือไม่</p>
                        </div>
                         <div role="alert" class="alert alert-error alert-soft mb-4 text-xs">
                            <i class="fa-solid fa-ban"></i>
                            <span><b>ไม่รับพิจารณารถป้ายแดง</b> (โปรดรอจนได้รับป้ายทะเบียนขาว)</span>
                        </div>
                        <div class="card bg-base-200 shadow-inner">
                            <div class="card-body p-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="form-control w-full">
                                        <div class="label"><span class="label-text font-medium">ประเภทรถ</span></div>
                                        <select id="check-vehicle-type" class="select select-bordered select-sm" required>
                                            <option disabled selected value="">เลือกประเภทรถ</option>
                                            <option value="รถยนต์">รถยนต์</option>
                                            <option value="รถจักรยานยนต์">รถจักรยานยนต์</option>
                                        </select>
                                        <p class="error-message hidden"></p>
                                    </div>
                                    <div class="form-control w-full">
                                        <div class="label"><span class="label-text font-medium">เลขทะเบียนรถ</span></div>
                                        <input type="text" id="check-license-plate" placeholder="เช่น กข1234" class="input input-bordered input-sm w-full" required />
                                        <p class="error-message hidden"></p>
                                    </div>
                                    <div class="form-control w-full">
                                        <div class="label"><span class="label-text font-medium">จังหวัดทะเบียนรถ</span></div>
                                        <select id="check-license-province" class="select select-bordered select-sm" required>
                                            <option disabled selected value="">เลือกจังหวัด</option>
                                            <?php foreach ($provinces as $province): ?>
                                                <option value="<?php echo htmlspecialchars($province); ?>"><?php echo htmlspecialchars($province); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="error-message hidden"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-actions justify-center mt-6 gap-4">
                            <button type="button" id="clear-check-btn" class="btn btn-ghost btn-sm"><i class="fa-solid fa-eraser mr-2"></i>ล้างข้อมูล</button>
                            <button type="button" id="check-vehicle-btn" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-magnifying-glass mr-2"></i>ตรวจสอบข้อมูล
                            </button>
                        </div>
                    </div>
                    
                    <!-- STEP 2: DETAILS -->
                    <div id="vehicle-details-section" class="hidden">
                        <div class="text-center mb-4">
                            <h3 class="font-semibold text-lg">กรอกข้อมูลและแนบหลักฐาน</h3>
                            <p class="text-sm text-slate-500">โปรดตรวจสอบข้อมูลให้ถูกต้องครบถ้วน</p>
                        </div>

                        <!-- Vehicle Info -->
                        <div class="divider divider-start font-semibold text-base">ข้อมูลยานพาหนะ</div>
                        <div class="card bg-base-200 shadow-inner">
                             <div class="card-body p-6">
                                <div class="card bg-base-100 shadow-sm mb-6">
                                    <div class="card-body p-4">
                                        <div class="flex flex-col sm:flex-row justify-around items-center text-center gap-4">
                                            <div>
                                                <div class="text-xs text-base-content/70">ประเภทรถ</div>
                                                <div id="display-vehicle-type" class="text-lg font-semibold"></div>
                                            </div>
                                            <div class="divider sm:divider-horizontal"></div>
                                            <div>
                                                <div class="text-xs text-base-content/70">เลขทะเบียน</div>
                                                <div id="display-license-plate" class="text-lg font-bold"></div>
                                            </div>
                                            <div class="divider sm:divider-horizontal"></div>
                                            <div>
                                                <div class="text-xs text-base-content/70">จังหวัด</div>
                                                <div id="display-license-province" class="text-lg font-semibold"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="vehicle_type" id="vehicle-type" required />
                                <input type="hidden" name="license_plate" id="license-plate" required />
                                <input type="hidden" name="license_province" id="license-province" required />
                                <div class="divider text-sm">กรอกข้อมูลเพิ่มเติม</div>
                                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mt-4">
                                    <div class="form-control w-full"><div class="label"><span class="label-text font-medium">ยี่ห้อรถ</span></div><select name="vehicle_brand" id="vehicle-brand" class="select select-bordered select-sm" required><option disabled selected value="">เลือกยี่ห้อ</option><?php foreach ($car_brands as $brand): ?><option value="<?php echo htmlspecialchars($brand); ?>"><?php echo htmlspecialchars($brand); ?></option><?php endforeach; ?></select><p class="error-message hidden"></p></div>
                                    <div class="form-control w-full"><div class="label"><span class="label-text font-medium">รุ่นรถ (ภาษาอังกฤษ)</span></div><input type="text" name="vehicle_model" placeholder="เช่น COROLLA, CIVIC" class="input input-bordered input-sm w-full" id="vehicle-model" required /><p class="error-message hidden"></p></div>
                                    <div class="form-control w-full"><div class="label"><span class="label-text font-medium">สีรถ</span></div><input type="text" name="vehicle_color" placeholder="เช่น ดำ, ขาว" class="input input-bordered input-sm w-full" id="vehicle-color" required /><p class="error-message hidden"></p></div>
                                    <div class="form-control w-full lg:col-span-2"><div class="label"><span class="label-text font-medium">วันสิ้นอายุภาษีรถ</span></div><div class="grid grid-cols-3 gap-2"><select name="tax_day" id="tax-day" class="select select-bordered select-sm" required></select><select name="tax_month" id="tax-month" class="select select-bordered select-sm" required></select><select name="tax_year" id="tax-year" class="select select-bordered select-sm" required></select></div><p class="error-message hidden"></p></div>
                                </div>
                            </div>
                        </div>

                        <!-- Owner Info -->
                        <div class="divider divider-start font-semibold text-base mt-6">ข้อมูลความเป็นเจ้าของ</div>
                         <div class="card bg-base-200 shadow-inner">
                             <div class="card-body p-6">
                                <div class="form-control w-full max-w-xs"><div class="label"><span class="label-text font-medium">เป็นรถของใคร?</span></div><select name="owner_type" id="owner-type" class="select select-bordered select-sm" required><option disabled selected value="">กรุณาเลือก</option><option value="self">รถชื่อตนเอง</option><option value="other">รถคนอื่น</option></select><p class="error-message hidden"></p></div>
                                <div id="other-owner-details" class="hidden mt-4">
                                    <div role="alert" class="alert alert-error alert-soft text-sm mb-4"><i class="fa-solid fa-triangle-exclamation"></i><span><b>คำเตือน:</b> หากยานพาหนะเกิดปัญหาใดๆ ผู้ยื่นคำร้องจะต้องเป็นผู้รับผิดชอบ</span></div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="form-control w-full"><div class="label"><span class="label-text font-medium">คำนำหน้า-ชื่อ-สกุล เจ้าของรถ</span></div><input type="text" name="other_owner_name" placeholder="เช่น นายสมชาย ใจดี" class="input input-bordered input-sm w-full" /><p class="error-message hidden"></p></div>
                                        <div class="form-control w-full"><div class="label"><span class="label-text font-medium">เกี่ยวข้องเป็น</span></div><input type="text" name="other_owner_relation" placeholder="เช่น บิดา, มารดา, เพื่อน" class="input input-bordered input-sm w-full" /><p class="error-message hidden"></p></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Evidence -->
                        <div class="divider divider-start font-semibold text-base mt-6">หลักฐานรูปถ่าย</div>
                        <div role="alert" class="alert alert-info alert-soft mb-4 text-xs"><i class="fa-solid fa-circle-info"></i><span>โปรดตรวจสอบความคมชัดของรูปถ่าย (.jpg, .png) และขนาดไฟล์ต้องไม่เกิน 5 MB</span></div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="form-control w-full">
                                <label class="block font-medium mb-2 text-center text-sm">สำเนาทะเบียนรถ</label>
                                <div id="reg-copy-container" class="flex justify-center bg-base-200 p-2 rounded-lg border overflow-hidden h-48"><img id="reg-copy-preview" src="https://img5.pic.in.th/file/secure-sv1/registration.jpg" alt="ตัวอย่างสำเนาทะเบียนรถ" class="w-full h-full object-contain"></div>
                                <input type="file" name="reg_copy_upload" id="reg-copy-upload" class="file-input file-input-bordered file-input-sm w-full mt-2" accept=".jpg, .jpeg, .png" required><p class="error-message hidden"></p>
                            </div>
                            <div class="form-control w-full">
                                <label class="block font-medium mb-2 text-center text-sm">ป้ายภาษี (ป้ายวงกลม)</label>
                                <div id="tax-sticker-container" class="flex justify-center bg-base-200 p-2 rounded-lg border overflow-hidden h-48"><img id="tax-sticker-preview" src="https://img2.pic.in.th/pic/tax_sticker.jpg" alt="ตัวอย่างป้ายภาษี" class="w-full h-full object-contain"></div>
                                <input type="file" name="tax_sticker_upload" id="tax-sticker-upload" class="file-input file-input-bordered file-input-sm w-full mt-2" accept=".jpg, .jpeg, .png" required><p class="error-message hidden"></p>
                            </div>
                            <div class="form-control w-full">
                                <label class="block font-medium mb-2 text-center text-sm">รูปถ่ายรถด้านหน้า</label>
                                <div id="front-view-container" class="flex justify-center bg-base-200 p-2 rounded-lg border overflow-hidden h-48"><img id="front-view-preview" src="https://img2.pic.in.th/pic/front_view.png" alt="ตัวอย่างรูปถ่ายรถด้านหน้า" class="w-full h-full object-contain"></div>
                                <input type="file" name="front_view_upload" id="front-view-upload" class="file-input file-input-bordered file-input-sm w-full mt-2" accept=".jpg, .jpeg, .png" required><p class="error-message hidden"></p>
                            </div>
                            <div class="form-control w-full">
                                <label class="block font-medium mb-2 text-center text-sm">รูปถ่ายรถด้านหลัง</label>
                                <div id="rear-view-container" class="flex justify-center bg-base-200 p-2 rounded-lg border overflow-hidden h-48"><img id="rear-view-preview" src="https://img5.pic.in.th/file/secure-sv1/rear_view.png" alt="ตัวอย่างรูปถ่ายรถด้านหลัง" class="w-full h-full object-contain"></div>
                                <input type="file" name="rear_view_upload" id="rear-view-upload" class="file-input file-input-bordered file-input-sm w-full mt-2" accept=".jpg, .jpeg, .png" required><p class="error-message hidden"></p>
                            </div>
                        </div>

                        <!-- Agreement and Submit -->
                        <div class="divider mt-8"></div>
                        <div class="flex justify-center mt-6">
                            <div class="form-control w-full max-w-md">
                                <label class="label cursor-pointer justify-start gap-4">
                                    <input type="checkbox" name="terms_confirm" id="terms-confirm" class="checkbox checkbox-primary checkbox-sm" required />
                                    <span class="label-text font-semibold">ยอมรับข้อตกลงและเงื่อนไข</span>
                                </label>
                                <div class="text-xs text-base-content/70 pl-10">
                                    <ul class="list-disc list-inside">
                                        <li>ยืนยันข้อมูลเป็นจริงทุกประการ</li>
                                        <li>ยินยอมให้ตรวจสอบข้อมูล</li>
                                        <li>ตรวจสอบข้อมูลแล้ว ไม่สามารถแก้ไขได้</li>
                                    </ul>
                                </div>
                                <p class="error-message hidden pl-10"></p>
                            </div>
                        </div>
                        <div class="card-actions justify-center mt-6 gap-4">
                            <button type="button" id="reset-form-btn" class="btn btn-ghost btn-sm"><i class="fa-solid fa-eraser mr-2"></i>ล้างข้อมูล</button>
                            <button type="button" id="submit-request-btn" class="btn btn-primary btn-sm"><i class="fa-solid fa-paper-plane mr-2"></i>ยืนยันและส่งคำร้อง</button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div role="alert" class="alert alert-warning alert-soft">
                    <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    <div>
                        <h3 class="font-bold">ระบบปิดรับคำร้องชั่วคราว</h3>
                        <div class="text-xs">ขณะนี้ยังไม่ถึงช่วงเวลาสำหรับการยื่นคำร้องขอบัตรผ่านยานพาหนะ กรุณาตรวจสอบกำหนดการอีกครั้ง</div>
                    </div>
                </div>
                <div class="text-center mt-6">
                    <a href="dashboard.php" class="btn btn-primary btn-sm">กลับหน้าหลัก</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

