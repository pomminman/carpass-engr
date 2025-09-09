<?php
// app/views/user/home/add_vehicle.php

require_once __DIR__ . '/../shared/auth_check.php';

// --- [ใหม่] ดึงข้อมูลรอบการสมัครที่เปิดใช้งานอยู่ ---
$active_period = null;
$sql_period = "SELECT * FROM application_periods WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date LIMIT 1";
$result_period = $conn->query($sql_period);
if ($result_period->num_rows > 0) {
    $active_period = $result_period->fetch_assoc();
}

// [ลบ] ข้อมูลยี่ห้อรถและจังหวัดถูกย้ายไปที่ auth_check.php แล้ว

// [แก้ไข] ลบบรรทัดนี้ออกเพื่อป้องกันการปิดการเชื่อมต่อซ้ำซ้อน
// $conn->close();

require_once __DIR__ . '/../layouts/header.php';
?>

<main class="flex-grow container mx-auto max-w-4xl p-4">
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
                <h2 class="card-title text-xl flex items-center gap-2"><i class="fa-solid fa-file-circle-plus"></i> เพิ่มยานพาหนะ/ยื่นคำร้อง</h2>
                <a href="dashboard.php" class="btn btn-sm btn-ghost"><i class="fa-solid fa-arrow-left"></i> กลับไปหน้าภาพรวม</a>
            </div>

            <?php if ($active_period): ?>
                <div role="alert" class="alert alert-success alert-soft mb-4">
                    <i class="fa-solid fa-bullhorn text-lg"></i>
                    <div>
                        <h3 class="font-bold">เปิดรับคำร้อง: <?php echo htmlspecialchars($active_period['period_name']); ?></h3>
                        <div class="text-xs">
                            สามารถยื่นคำร้องได้ตั้งแต่วันนี้ ถึงวันที่ <?php echo format_thai_date_helper($active_period['end_date']); ?>. 
                            บัตรที่ได้รับอนุมัติในรอบนี้จะหมดอายุวันที่ <?php echo format_thai_date_helper($active_period['card_expiry_date']); ?>
                        </div>
                    </div>
                </div>

                <form action="../../../controllers/user/vehicle/add_vehicle_process.php" method="POST" enctype="multipart/form-data" id="addVehicleForm" novalidate>
                    <div class="divider divider-start font-semibold">ข้อมูลยานพาหนะ</div>
                    <div role="alert" class="alert alert-error alert-soft mb-4">
                        <div class="flex items-center justify-start text-left">
                            <i class="fa-solid fa-ban text-lg mr-2"></i>
                            <span class="text-xs"><b class="font-bold">ไม่รับพิจารณารถป้ายแดง</b> (โปรดรอจนได้รับป้ายทะเบียนขาว)</span>
                        </div>
                    </div>
                    <div role="alert" class="alert alert-info alert-soft mb-4">
                        <div class="text-left"><ul class="list-disc list-inside text-xs"><li>โปรดตรวจสอบข้อมูลและเอกสารทั้งหมดให้ถูกต้องเพื่อความรวดเร็วในการอนุมัติ</li></ul></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="form-control w-full"><div class="label"><span class="label-text">ประเภทรถ</span></div><select name="vehicle_type" id="vehicle-type" class="select select-bordered select-sm" required><option disabled selected value="">เลือกประเภทรถ</option><option value="รถยนต์">รถยนต์</option><option value="รถจักรยานยนต์">รถจักรยานยนต์</option></select><p class="error-message hidden"></p></div>
                        <div class="form-control w-full"><div class="label"><span class="label-text">ยี่ห้อรถ</span></div><select name="vehicle_brand" id="vehicle-brand" class="select select-bordered select-sm" required><option disabled selected value="">เลือกยี่ห้อ</option><?php foreach ($car_brands as $brand): ?><option value="<?php echo htmlspecialchars($brand); ?>"><?php echo htmlspecialchars($brand); ?></option><?php endforeach; ?></select><p class="error-message hidden"></p></div>
                        <div class="form-control w-full"><div class="label"><span class="label-text">รุ่นรถ (ภาษาอังกฤษ)</span></div><input type="text" name="vehicle_model" placeholder="เช่น COROLLA, CIVIC" class="input input-bordered input-sm w-full" id="vehicle-model" required /><p class="error-message hidden"></p></div>
                        <div class="form-control w-full"><div class="label"><span class="label-text">สีรถ</span></div><input type="text" name="vehicle_color" placeholder="เช่น ดำ, ขาว, แดง" class="input input-bordered input-sm w-full" id="vehicle-color" required /><p class="error-message hidden"></p></div>
                        <div class="form-control w-full"><div class="label"><span class="label-text">เลขทะเบียนรถ</span></div><input type="text" name="license_plate" placeholder="เช่น กข1234" class="input input-bordered input-sm w-full" id="license-plate" required /><p class="error-message hidden"></p></div>
                        <div class="form-control w-full"><div class="label"><span class="label-text">จังหวัดทะเบียนรถ</span></div><select name="license_province" id="license-province" class="select select-bordered select-sm" required><option disabled selected value="">เลือกจังหวัด</option><?php foreach ($provinces as $province): ?><option value="<?php echo htmlspecialchars($province); ?>"><?php echo htmlspecialchars($province); ?></option><?php endforeach; ?></select><p class="error-message hidden"></p></div>
                        <div class="form-control w-full lg:col-span-2"><div class="label"><span class="label-text">วันสิ้นอายุภาษีรถ</span></div><div class="grid grid-cols-3 gap-2"><select name="tax_day" id="tax-day" class="select select-bordered select-sm" required><option disabled selected value="">วัน</option></select><select name="tax_month" id="tax-month" class="select select-bordered select-sm" required><option disabled selected value="">เดือน</option></select><select name="tax_year" id="tax-year" class="select select-bordered select-sm" required><option disabled selected value="">ปี (พ.ศ.)</option></select></div><p class="error-message hidden"></p></div>
                        <div class="form-control w-full"><div class="label"><span class="label-text">เป็นรถของใคร?</span></div><select name="owner_type" id="owner-type" class="select select-bordered select-sm" required><option disabled selected value="">กรุณาเลือก</option><option value="self">รถชื่อตนเอง</option><option value="other">รถคนอื่น</option></select><p class="error-message hidden"></p></div>
                    </div>
                    <div id="other-owner-details" class="hidden mt-4">
                        <div role="alert" class="alert alert-error alert-soft text-sm mb-4 flex"><i class="fa-solid fa-triangle-exclamation self-center"></i><span class="self-start sm:self-center"><b>คำเตือน:</b> ถ้ารถคันที่ท่านยื่นขอ มีปัญหา ท่านต้องเป็นผู้รับผิดชอบ</span></div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border rounded-lg bg-base-200">
                            <div class="form-control w-full"><div class="label"><span class="label-text">คำนำหน้า-ชื่อ-สกุล</span></div><input type="text" name="other_owner_name" placeholder="เช่น นายสมชาย ใจดี" class="input input-bordered input-sm w-full" /><p class="error-message hidden"></p></div>
                            <div class="form-control w-full"><div class="label"><span class="label-text">เกี่ยวข้องเป็น</span></div><input type="text" name="other_owner_relation" placeholder="เช่น บิดา, มารดา, เพื่อน" class="input input-bordered input-sm w-full" /><p class="error-message hidden"></p></div>
                        </div>
                    </div>
                    <div class="divider divider-start font-semibold mt-8">หลักฐานรูปถ่าย</div>
                        <div role="alert" class="alert alert-info alert-soft mb-6">
                        <div class="text-left">
                            <ul class="list-disc list-inside text-xs">
                                <li>โปรดตรวจสอบความถูกต้องและความคมชัดของรูปถ่าย (.jpg, .png)</li>
                                <li>ขนาดไฟล์แต่ละรูปต้องไม่เกิน 5 MB</li>
                            </ul>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control w-full">
                            <label class="block font-medium mb-2 text-center">สำเนาทะเบียนรถ</label>
                            <div id="reg-copy-container" class="flex justify-center bg-base-200 p-2 rounded-lg border overflow-hidden">
                                <img id="reg-copy-preview" src="https://img5.pic.in.th/file/secure-sv1/registration.jpg" alt="ตัวอย่างสำเนาทะเบียนรถ" class="w-full max-h-48 object-contain">
                            </div>
                            <input type="file" name="reg_copy_upload" id="reg-copy-upload" class="file-input file-input-bordered file-input-sm w-full mt-2" accept=".jpg, .jpeg, .png" required>
                            <p class="error-message hidden"></p>
                        </div>
                        <div class="form-control w-full">
                            <label class="block font-medium mb-2 text-center">ป้ายภาษีรถยนต์ (ป้ายวงกลม)</label>
                            <div id="tax-sticker-container" class="flex justify-center bg-base-200 p-2 rounded-lg border overflow-hidden">
                                <img id="tax-sticker-preview" src="https://img2.pic.in.th/pic/tax_sticker.jpg" alt="ตัวอย่างป้ายภาษี" class="w-full max-h-48 object-contain">
                            </div>
                            <input type="file" name="tax_sticker_upload" id="tax-sticker-upload" class="file-input file-input-bordered file-input-sm w-full mt-2" accept=".jpg, .jpeg, .png" required>
                            <p class="error-message hidden"></p>
                        </div>
                        <div class="form-control w-full">
                            <label class="block font-medium mb-2 text-center">รูปถ่ายรถด้านหน้า</label>
                            <div id="front-view-container" class="flex justify-center bg-base-200 p-2 rounded-lg border overflow-hidden">
                                <img id="front-view-preview" src="https://img2.pic.in.th/pic/front_view.png" alt="ตัวอย่างรูปถ่ายรถด้านหน้า" class="w-full max-h-48 object-contain">
                            </div>
                            <input type="file" name="front_view_upload" id="front-view-upload" class="file-input file-input-bordered file-input-sm w-full mt-2" accept=".jpg, .jpeg, .png" required>
                            <p class="error-message hidden"></p>
                        </div>
                        <div class="form-control w-full">
                            <label class="block font-medium mb-2 text-center">รูปถ่ายรถด้านหลัง</label>
                            <div id="rear-view-container" class="flex justify-center bg-base-200 p-2 rounded-lg border overflow-hidden">
                                <img id="rear-view-preview" src="https://img5.pic.in.th/file/secure-sv1/rear_view.png" alt="ตัวอย่างรูปถ่ายรถด้านหลัง" class="w-full max-h-48 object-contain">
                            </div>
                            <input type="file" name="rear_view_upload" id="rear-view-upload" class="file-input file-input-bordered file-input-sm w-full mt-2" accept=".jpg, .jpeg, .png" required>
                            <p class="error-message hidden"></p>
                        </div>
                    </div>
                    <div class="flex justify-center mt-6">
                        <div class="form-control w-full max-w-md"><label class="label cursor-pointer justify-start gap-4"><input type="checkbox" name="terms_confirm" id="terms-confirm" class="checkbox checkbox-primary checkbox-sm" required /><span class="label-text font-semibold">ยอมรับข้อตกลงและเงื่อนไข</span></label>
                        <div class="text-xs text-base-content/70 pl-10">
                            <ul class="list-disc list-inside">
                                <li>ยืนยันข้อมูลเป็นจริงทุกประการ</li>
                                <li>ยินยอมให้ตรวจสอบข้อมูล</li>
                                <li>ตรวจสอบข้อมูลแล้ว ไม่สามารถแก้ไขได้</li>
                            </ul>
                        </div>
                        <p class="error-message hidden pl-10"></p></div>
                    </div>
                    <div class="card-actions justify-center mt-6 gap-4"><button type="button" id="reset-form-btn" class="btn btn-ghost btn-sm"><i class="fa-solid fa-eraser"></i> ล้างข้อมูล</button><button type="submit" class="btn btn-primary btn-sm">ยืนยันและส่งคำร้อง</button></div>
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
