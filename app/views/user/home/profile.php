<?php
// app/views/user/home/profile.php
require_once __DIR__ . '/../shared/auth_check.php';

// ดึงข้อมูลสังกัดสำหรับ dropdown
$departments = [];
$sql_dept = "SELECT name FROM departments ORDER BY display_order ASC, name ASC";
$result_dept = $conn->query($sql_dept);
if ($result_dept->num_rows > 0) {
    while($row = $result_dept->fetch_assoc()) {
        $departments[] = $row;
    }
}

// [Overwrite] กำหนด path รูปโปรไฟล์ใหม่ให้เป็นรูปขนาดเต็มเสมอสำหรับหน้านี้
$user_photo_path = "/public/uploads/{$user['user_key']}/profile/{$user['photo_profile']}";

$standard_titles = ["นาย", "นาง", "นางสาว", "พล.อ.", "พล.อ.หญิง", "พล.ท.", "พล.ท.หญิง", "พล.ต.", "พล.ต.หญิง", "พ.อ.", "พ.อ.หญิง", "พ.ท.", "พ.ท.หญิง", "พ.ต.", "พ.ต.หญิง", "ร.อ.", "ร.อ.หญิง", "ร.ท.", "ร.ท.หญิง", "ร.ต.", "ร.ต.หญิง", "จ.ส.อ.", "จ.ส.อ.หญิง", "จ.ส.ท.", "จ.ส.ท.หญิง", "จ.ส.ต.", "จ.ส.ต.หญิง", "ส.อ.", "ส.อ.หญิง", "ส.ท.", "ส.ท.หญิง", "ส.ต.", "ส.ต.หญิง", "พลทหาร"];
$is_other_title = !in_array($user['title'], $standard_titles);

// [เพิ่ม] Helper functions for formatting
function format_phone_number($phone) {
    $numbers = preg_replace('/\D/', '', $phone);
    if (strlen($numbers) == 10) {
        return substr($numbers, 0, 3) . '-' . substr($numbers, 3, 3) . '-' . substr($numbers, 6);
    }
    return $phone;
}

function format_national_id($nid) {
    $numbers = preg_replace('/\D/', '', $nid);
    if (strlen($numbers) == 13) {
        return substr($numbers, 0, 1) . '-' . substr($numbers, 1, 4) . '-' . substr($numbers, 5, 5) . '-' . substr($numbers, 10, 2) . '-' . substr($numbers, 12, 1);
    }
    return $nid;
}


require_once __DIR__ . '/../layouts/header.php';
?>
<!-- Custom Styles for Profile Page Inputs -->
<style>
    #profileForm.form-view-mode .input,
    #profileForm.form-view-mode .select {
        background-color: #f3f4f6; 
        border-color: #e5e7eb; 
        color: #1f2937 !important; 
        opacity: 1 !important;
        -webkit-text-fill-color: #1f2937 !important;
    }
    #profileForm.form-edit-mode .input:not(:disabled),
    #profileForm.form-edit-mode .select:not(:disabled),
    #profileForm.form-edit-mode .tt-input:not(:disabled) {
        background-color: #ffffff !important;
        color: #1f2937;
        border-color: #d1d5db;
    }
    #profileForm.form-edit-mode .input:disabled,
    #profileForm.form-edit-mode .select:disabled {
        background-color: #f3f4f6;
        color: #4b5563;
        border-color: #e5e7eb;
        opacity: 1;
    }
    #profileForm.form-edit-mode .input.input-error,
    #profileForm.form-edit-mode .select.select-error,
    #profileForm.form-edit-mode .file-input.file-input-error {
        border-color: #f87272;
    }

    /* สไตล์สำหรับ dropdown ของ jquery.Thailand.js ให้มี scrollbar */
    .twitter-typeahead .tt-menu {
        max-height: 200px; /* ลดความสูงลง */
        overflow-y: auto;
        display: block;
        scrollbar-width: thin;
        scrollbar-color: #a0aec0 #e2e8f0;
    }
    .twitter-typeahead .tt-menu::-webkit-scrollbar {
        width: 8px;
    }
    .twitter-typeahead .tt-menu::-webkit-scrollbar-track {
        background: #e2e8f0;
    }
    .twitter-typeahead .tt-menu::-webkit-scrollbar-thumb {
        background-color: #a0aec0;
        border-radius: 4px;
        border: 2px solid #e2e8f0;
    }
</style>

<!-- Main Content for Profile -->
<div id="profile-section" class="space-y-4" data-page="profile">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">ข้อมูลส่วนตัว</h1>
            <p class="text-sm sm:text-base text-base-content/70">จัดการข้อมูลส่วนตัวและที่อยู่ของคุณ</p>
        </div>
        <div id="profile-action-buttons" class="flex gap-2 w-full sm:w-auto">
             <button id="edit-profile-btn" class="btn btn-sm btn-warning w-1/2 sm:w-auto"><i class="fa-solid fa-pencil"></i> แก้ไขข้อมูล</button>
             <button id="save-profile-btn" class="btn btn-sm btn-success hidden w-1/2 sm:w-auto"><i class="fa-solid fa-save"></i> บันทึกข้อมูล</button>
             <button id="cancel-edit-btn" class="btn btn-sm btn-ghost hidden w-1/2 sm:w-auto"><i class="fa-solid fa-times"></i> ยกเลิก</button>
        </div>
    </div>
    
    <form id="profileForm" action="../../../controllers/user/profile/edit_profile_process.php" method="POST" enctype="multipart/form-data" novalidate>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Profile Picture Column -->
                    <div class="lg:col-span-1 flex flex-col items-center">
                        <div class="form-control w-full max-w-[250px]">
                            <label class="block font-medium mb-1 text-center text-sm">รูปถ่ายหน้าตรง</label>
                            <div id="profile-photo-container" class="w-full aspect-square">
                                <a href="<?php echo htmlspecialchars($user_photo_path); ?>" data-fancybox data-caption="รูปถ่ายหน้าตรง">
                                    <div class="flex justify-center bg-base-200 p-2 rounded-box border overflow-hidden w-full h-full">
                                        <img id="profile-photo-preview" src="<?php echo htmlspecialchars($user_photo_path); ?>" alt="รูปโปรไฟล์" class="w-full h-full object-contain cursor-pointer" onerror="this.onerror=null;this.src='https://placehold.co/300x300/e2e8f0/475569?text=Profile';">
                                    </div>
                                </a>
                            </div>
                            <div id="photo-guidance" class="mt-2 text-xs p-2 rounded-box bg-info alert-soft hidden">
                                <ul class="list-disc list-inside"><li>ไฟล์ .jpg, .png ไม่เกิน 5 MB</li></ul>
                            </div>
                            <input type="file" id="profile-photo-upload" name="photo_upload" class="file-input file-input-sm file-input-bordered w-full mt-2 hidden" accept=".jpg, .jpeg, .png">
                            <p class="error-message hidden text-error text-xs mt-1"></p>
                        </div>
                    </div>
                    <!-- Details Column -->
                    <div class="lg:col-span-2 space-y-3">
                         <div>
                            <h4 class="font-semibold text-base-content/80 mb-1 text-sm">ข้อมูลส่วนตัว</h4>
                            <?php
                                $dob_parts = explode('-', $user['dob']);
                                $user_dob_year = isset($dob_parts[0]) ? (int)$dob_parts[0] + 543 : '';
                                $user_dob_month_num = isset($dob_parts[1]) ? ltrim($dob_parts[1], '0') : '';
                                $user_dob_day = isset($dob_parts[2]) ? ltrim($dob_parts[2], '0') : '';
                                $months = ["มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
                                $user_dob_month_text = ($user_dob_month_num) ? $months[$user_dob_month_num - 1] : '';
                            ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="form-control w-full sm:col-span-2"><div class="grid grid-cols-3 gap-2">
                                    <div class="form-control w-full">
                                        <div class="label py-1"><span class="label-text text-xs">คำนำหน้า</span></div>
                                        
                                        <div class="view-mode-element">
                                        <?php if (!$is_other_title): ?>
                                            <input type="text" value="<?php echo htmlspecialchars($user['title']); ?>" class="input input-sm input-bordered w-full" disabled />
                                        <?php else: ?>
                                            <div class="grid grid-cols-2 gap-2">
                                                <input type="text" value="อื่นๆ" class="input input-sm input-bordered w-full" disabled />
                                                <input type="text" value="<?php echo htmlspecialchars($user['title']); ?>" class="input input-sm input-bordered w-full" disabled />
                                            </div>
                                        <?php endif; ?>
                                        </div>

                                        <div class="edit-mode-element hidden">
                                            <select id="profile-title" name="title" class="select select-sm select-bordered w-full" disabled required>
                                                <?php 
                                                    foreach($standard_titles as $t) {
                                                        echo "<option value='$t'" . ($user['title'] == $t ? ' selected' : '') . ">$t</option>";
                                                    }
                                                ?>
                                                <option value="other" <?php echo $is_other_title ? 'selected' : ''; ?>>อื่นๆ</option>
                                            </select>
                                            <input type="text" id="profile-title-other" name="title_other" placeholder="ระบุ" class="input input-sm input-bordered w-full mt-2 hidden" value="<?php echo $is_other_title ? htmlspecialchars($user['title']) : ''; ?>" disabled oninput="this.value = this.value.replace(/[^ก-๙\s.()]/g, '')"/>
                                        </div>
                                        <p class="error-message hidden text-error text-xs mt-1"></p>
                                    </div>
                                    <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">ชื่อจริง</span></div><input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" class="input input-sm input-bordered w-full" disabled required oninput="this.value = this.value.replace(/[^ก-๙\s]/g, '')" /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                                    <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">นามสกุล</span></div><input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" class="input input-sm input-bordered w-full" disabled required oninput="this.value = this.value.replace(/[^ก-๙\s]/g, '')" /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                                </div></div>
                                <div class="form-control w-full sm:col-span-2"><div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                    <div class="form-control w-full sm:col-span-8">
                                        <div class="label py-1"><span class="label-text text-xs">วันเดือนปีเกิด</span></div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <input type="text" value="<?php echo $user_dob_day; ?>" class="input input-sm input-bordered w-full view-mode-element" disabled />
                                            <input type="text" value="<?php echo $user_dob_month_text; ?>" class="input input-sm input-bordered w-full view-mode-element" disabled />
                                            <input type="text" value="<?php echo $user_dob_year; ?>" class="input input-sm input-bordered w-full view-mode-element" disabled />
                                            <select id="profile-dob-day" name="dob_day" class="select select-sm select-bordered w-full edit-mode-element hidden" disabled required><option value="">วัน</option><?php for ($i = 1; $i <= 31; $i++): ?><option value="<?php echo $i; ?>" <?php echo ($user_dob_day == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option><?php endfor; ?></select>
                                            <select id="profile-dob-month" name="dob_month" class="select select-sm select-bordered w-full edit-mode-element hidden" disabled required><option value="">เดือน</option><?php foreach ($months as $index => $month): ?><option value="<?php echo $index + 1; ?>" <?php echo ($user_dob_month_num == ($index + 1)) ? 'selected' : ''; ?>><?php echo $month; ?></option><?php endforeach; ?></select>
                                            <select id="profile-dob-year" name="dob_year" class="select select-sm select-bordered w-full edit-mode-element hidden" disabled required><option value="">ปี พ.ศ.</option><?php $current_year_be = date("Y") + 543; ?><?php for ($i = $current_year_be; $i >= $current_year_be - 100; $i--): ?><option value="<?php echo $i; ?>" <?php echo ($user_dob_year == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option><?php endfor; ?></select>
                                        </div>
                                        <p class="error-message hidden text-error text-xs mt-1"></p>
                                    </div>
                                     <div class="form-control w-full sm:col-span-4"><div class="label py-1"><span class="label-text text-xs">เพศ</span></div>
                                        <input type="text" value="<?php echo htmlspecialchars($user['gender']); ?>" class="input input-sm input-bordered w-full view-mode-element" disabled />
                                        <select name="gender" class="select select-sm select-bordered w-full edit-mode-element hidden" disabled required><option value="ชาย" <?php echo $user['gender'] == 'ชาย' ? 'selected' : ''; ?>>ชาย</option><option value="หญิง" <?php echo $user['gender'] == 'หญิง' ? 'selected' : ''; ?>>หญิง</option></select>
                                        <p class="error-message hidden text-error text-xs mt-1"></p>
                                     </div>
                                </div></div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">เบอร์โทร</span></div><input type="tel" name="phone" value="<?php echo format_phone_number($user['phone_number']); ?>" class="input input-sm input-bordered w-full" disabled required maxlength="12" /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">เลขบัตรประชาชน</span></div><input type="text" name="national_id_display" value="<?php echo format_national_id($user['national_id']); ?>" class="input input-sm input-bordered w-full" disabled /></div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold text-base-content/80 mb-1 text-sm">ที่อยู่ปัจจุบัน</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="form-control w-full sm:col-span-2"><div class="label py-1"><span class="label-text text-xs">บ้านเลขที่/ที่อยู่</span></div><input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" class="input input-sm input-bordered w-full" disabled required oninput="this.value = this.value.replace(/[^ก-๙0-9\s.\-\/]/g, '')" /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">รหัสไปรษณีย์</span></div><input type="text" id="profile-zipcode" name="zipcode" value="<?php echo htmlspecialchars($user['zipcode']); ?>" class="input input-sm input-bordered w-full" disabled required /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">ตำบล/แขวง</span></div><input type="text" id="profile-subdistrict" name="subdistrict" value="<?php echo htmlspecialchars($user['subdistrict']); ?>" class="input input-sm input-bordered w-full" disabled required /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">อำเภอ/เขต</span></div><input type="text" id="profile-district" name="district" value="<?php echo htmlspecialchars($user['district']); ?>" class="input input-sm input-bordered w-full" disabled required /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">จังหวัด</span></div><input type="text" id="profile-province" name="province" value="<?php echo htmlspecialchars($user['province']); ?>" class="input input-sm input-bordered w-full" disabled required /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                            </div>
                        </div>
                        <?php if ($user['user_type'] === 'army'): ?>
                        <div id="profile-work-info">
                            <h4 class="font-semibold text-base-content/80 mb-1 text-sm">ข้อมูลการทำงาน</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <div class="form-control w-full">
                                    <div class="label py-1"><span class="label-text text-xs">สังกัด</span></div>
                                    <input type="text" name="work_department_display" value="<?php echo htmlspecialchars($user['work_department']); ?>" class="input input-sm input-bordered w-full" disabled />
                                </div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">ตำแหน่ง</span></div><input type="text" name="position" value="<?php echo htmlspecialchars($user['position']); ?>" class="input input-sm input-bordered w-full" disabled required /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">เลขบัตรข้าราชการ</span></div><input type="tel" name="official_id" value="<?php echo htmlspecialchars($user['official_id']); ?>" class="input input-sm input-bordered w-full" disabled maxlength="10" oninput="this.value = this.value.replace(/\D/g, '')" required /><p class="error-message hidden text-error text-xs mt-1"></p></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- [ADDED] Loading Modal -->
<dialog id="loading_modal" class="modal modal-middle">
    <div class="modal-box text-center">
        <span class="loading loading-spinner loading-lg text-primary"></span>
        <h3 class="font-bold text-lg mt-4">กำลังบันทึกข้อมูล...</h3>
        <p class="py-4">กรุณารอสักครู่</p>
    </div>
</dialog>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

