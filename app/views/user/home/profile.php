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

require_once __DIR__ . '/../layouts/header.php';
?>
<!-- [UPDATED] Custom Styles for Profile Page Inputs -->
<style>
    /* 1. View Mode: All inputs are gray, with uniform dark text */
    #profileForm.form-view-mode .input,
    #profileForm.form-view-mode .select {
        background-color: #f3f4f6; /* bg-slate-100 */
        border-color: #e5e7eb; /* border-slate-200 */
        color: #1f2937 !important; /* text-slate-800 */
        opacity: 1 !important; /* Ensure full opacity */
        -webkit-text-fill-color: #1f2937 !important; /* Override browser default for disabled text */
    }

    /* 2. Edit Mode: Editable inputs are white with a consistent gray border */
    #profileForm.form-edit-mode .input:not(:disabled),
    #profileForm.form-edit-mode .select:not(:disabled),
    #profileForm.form-edit-mode .tt-input:not(:disabled) { /* Added .tt-input for jquery.Thailand.js fields */
        background-color: #ffffff !important; /* bg-white with important to override library style */
        color: #1f2937; /* text-slate-800 */
        border-color: #d1d5db; /* border-slate-300 */
    }
    
    /* 3. Edit Mode: Non-editable inputs remain gray for clear distinction */
    #profileForm.form-edit-mode .input:disabled,
    #profileForm.form-edit-mode .select:disabled {
        background-color: #f3f4f6; /* bg-slate-100 */
        color: #4b5563; /* text-slate-600 */
        border-color: #e5e7eb; /* border-slate-200 */
        opacity: 1; /* Override default disabled opacity for better readability */
    }
</style>

<!-- Main Content for Profile -->
<div id="profile-section" class="space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">ข้อมูลส่วนตัว</h1>
            <p class="text-sm sm:text-base text-base-content/70">จัดการข้อมูลส่วนตัวและที่อยู่ของคุณ</p>
        </div>
        <div id="profile-action-buttons" class="flex gap-2 w-full sm:w-auto">
             <button id="edit-profile-btn" class="btn btn-warning w-1/2 sm:w-auto"><i class="fa-solid fa-pencil"></i> แก้ไขข้อมูล</button>
             <button id="save-profile-btn" class="btn btn-success hidden w-1/2 sm:w-auto"><i class="fa-solid fa-save"></i> บันทึกข้อมูล</button>
             <button id="cancel-edit-btn" class="btn btn-ghost hidden w-1/2 sm:w-auto"><i class="fa-solid fa-times"></i> ยกเลิก</button>
        </div>
    </div>
    
    <form id="profileForm" action="../../../controllers/user/profile/edit_profile_process.php" method="POST" enctype="multipart/form-data" novalidate>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Profile Picture Column -->
                    <div class="lg:col-span-1 flex flex-col items-center">
                        <div class="form-control w-full max-w-[200px]">
                            <label class="block font-medium mb-1 text-center text-sm">รูปถ่ายหน้าตรง</label>
                            <div id="profile-photo-container" class="flex justify-center bg-base-200 p-2 rounded-box border overflow-hidden w-full aspect-square">
                                <img id="profile-photo-preview" src="<?php echo $user_photo_path; ?>" alt="รูปโปรไฟล์" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='https://placehold.co/300x300/e2e8f0/475569?text=Profile';">
                            </div>
                            <div id="photo-guidance" class="mt-2 text-xs p-2 rounded-box bg-info alert-soft hidden">
                                <ul class="list-disc list-inside"><li>ไฟล์ .jpg, .png ไม่เกิน 5 MB</li></ul>
                            </div>
                            <input type="file" id="profile-photo-upload" name="photo_upload" class="file-input file-input-bordered w-full mt-2 hidden" accept=".jpg, .jpeg, .png">
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
                                    <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">คำนำหน้า</span></div>
                                        <input type="text" name="title_display" value="<?php echo htmlspecialchars($user['title']); ?>" class="input input-bordered w-full view-mode-element" disabled />
                                        <select id="profile-title" name="title" class="select select-bordered w-full edit-mode-element hidden" disabled required><?php $titles = ["นาย", "นาง", "นางสาว", "พล.อ.", "พล.อ.หญิง", "พล.ท.", "พล.ท.หญิง", "พล.ต.", "พล.ต.หญิง", "พ.อ.", "พ.อ.หญิง", "พ.ท.", "พ.ท.หญิง", "พ.ต.", "พ.ต.หญิง", "ร.อ.", "ร.อ.หญิง", "ร.ท.", "ร.ท.หญิง", "ร.ต.", "ร.ต.หญิง", "จ.ส.อ.", "จ.ส.อ.หญิง", "จ.ส.ท.", "จ.ส.ท.หญิง", "จ.ส.ต.", "จ.ส.ต.หญิง", "ส.อ.", "ส.อ.หญิง", "ส.ท.", "ส.ท.หญิง", "ส.ต.", "ส.ต.หญิง", "พลทหาร"]; $is_other_title = !in_array($user['title'], $titles); foreach($titles as $t) { echo "<option value='$t'" . ($user['title'] == $t ? ' selected' : '') . ">$t</option>"; } ?><option value="other" <?php echo $is_other_title ? 'selected' : ''; ?>>อื่นๆ</option></select>
                                        <input type="text" id="profile-title-other" name="title_other" placeholder="ระบุ" class="input input-bordered w-full mt-2 <?php echo !$is_other_title ? 'hidden' : ''; ?>" value="<?php echo $is_other_title ? htmlspecialchars($user['title']) : ''; ?>" disabled/>
                                    </div>
                                    <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">ชื่อจริง</span></div><input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" class="input input-bordered w-full" disabled required /></div>
                                    <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">นามสกุล</span></div><input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" class="input input-bordered w-full" disabled required /></div>
                                </div></div>
                                <div class="form-control w-full sm:col-span-2"><div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                    <div class="form-control w-full sm:col-span-8">
                                        <div class="label py-1"><span class="label-text text-xs">วันเดือนปีเกิด</span></div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <!-- View Mode Inputs -->
                                            <input type="text" name="dob_day_display" value="<?php echo $user_dob_day; ?>" class="input input-bordered w-full view-mode-element" disabled />
                                            <input type="text" name="dob_month_display" value="<?php echo $user_dob_month_text; ?>" class="input input-bordered w-full view-mode-element" disabled />
                                            <input type="text" name="dob_year_display" value="<?php echo $user_dob_year; ?>" class="input input-bordered w-full view-mode-element" disabled />
                                            <!-- Edit Mode Selects -->
                                            <select id="profile-dob-day" name="dob_day" class="select select-bordered w-full edit-mode-element hidden" disabled required>
                                                <option value="">วัน</option>
                                                <?php for ($i = 1; $i <= 31; $i++): ?>
                                                    <option value="<?php echo $i; ?>" <?php echo ($user_dob_day == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                            <select id="profile-dob-month" name="dob_month" class="select select-bordered w-full edit-mode-element hidden" disabled required>
                                                <option value="">เดือน</option>
                                                <?php foreach ($months as $index => $month): ?>
                                                    <option value="<?php echo $index + 1; ?>" <?php echo ($user_dob_month_num == ($index + 1)) ? 'selected' : ''; ?>><?php echo $month; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select id="profile-dob-year" name="dob_year" class="select select-bordered w-full edit-mode-element hidden" disabled required>
                                                 <option value="">ปี พ.ศ.</option>
                                                <?php $current_year_be = date("Y") + 543; ?>
                                                <?php for ($i = $current_year_be; $i >= $current_year_be - 100; $i--): ?>
                                                    <option value="<?php echo $i; ?>" <?php echo ($user_dob_year == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                     <div class="form-control w-full sm:col-span-4"><div class="label py-1"><span class="label-text text-xs">เพศ</span></div>
                                        <input type="text" name="gender_display" value="<?php echo htmlspecialchars($user['gender']); ?>" class="input input-bordered w-full view-mode-element" disabled />
                                        <select name="gender" class="select select-bordered w-full edit-mode-element hidden" disabled required><option value="ชาย" <?php echo $user['gender'] == 'ชาย' ? 'selected' : ''; ?>>ชาย</option><option value="หญิง" <?php echo $user['gender'] == 'หญิง' ? 'selected' : ''; ?>>หญิง</option></select>
                                     </div>
                                </div></div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">เบอร์โทร</span></div><input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone_number']); ?>" class="input input-bordered w-full" disabled required maxlength="12" /></div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">เลขบัตรประชาชน</span></div><input type="text" id="profile-national-id" name="national_id_display" value="<?php echo htmlspecialchars($user['national_id']); ?>" class="input input-bordered w-full" disabled /></div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold text-base-content/80 mb-1 text-sm">ที่อยู่ปัจจุบัน</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="form-control w-full sm:col-span-2"><div class="label py-1"><span class="label-text text-xs">บ้านเลขที่/ที่อยู่</span></div><input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" class="input input-bordered w-full" disabled required /></div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">รหัสไปรษณีย์</span></div><input type="text" id="profile-zipcode" name="zipcode" value="<?php echo htmlspecialchars($user['zipcode']); ?>" class="input input-bordered w-full" disabled required /></div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">ตำบล/แขวง</span></div><input type="text" id="profile-subdistrict" name="subdistrict" value="<?php echo htmlspecialchars($user['subdistrict']); ?>" class="input input-bordered w-full" disabled required /></div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">อำเภอ/เขต</span></div><input type="text" id="profile-district" name="district" value="<?php echo htmlspecialchars($user['district']); ?>" class="input input-bordered w-full" disabled required /></div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">จังหวัด</span></div><input type="text" id="profile-province" name="province" value="<?php echo htmlspecialchars($user['province']); ?>" class="input input-bordered w-full" disabled required /></div>
                            </div>
                        </div>
                        <?php if ($user['user_type'] === 'army'): ?>
                        <div id="profile-work-info">
                            <h4 class="font-semibold text-base-content/80 mb-1 text-sm">ข้อมูลการทำงาน</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <div class="form-control w-full">
                                    <div class="label py-1"><span class="label-text text-xs">สังกัด</span></div>
                                    <input type="text" name="work_department_display" value="<?php echo htmlspecialchars($user['work_department']); ?>" class="input input-bordered w-full" disabled />
                                </div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">ตำแหน่ง</span></div><input type="text" name="position" value="<?php echo htmlspecialchars($user['position']); ?>" class="input input-bordered w-full" disabled required /></div>
                                <div class="form-control w-full"><div class="label py-1"><span class="label-text text-xs">เลขบัตรข้าราชการ</span></div><input type="tel" name="official_id" value="<?php echo htmlspecialchars($user['official_id']); ?>" class="input input-bordered w-full" disabled maxlength="10" /></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- [UPDATED] Script to handle form mode class switching and jquery.Thailand.js -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('profileForm');
    const editBtn = document.getElementById('edit-profile-btn');
    const cancelBtn = document.getElementById('cancel-edit-btn');
    const saveBtn = document.getElementById('save-profile-btn');

    const viewModeElements = form.querySelectorAll('.view-mode-element');
    const editModeElements = form.querySelectorAll('.edit-mode-element');

    // Initialize jquery.Thailand.js
    $.Thailand({
        $zipcode: $('#profile-zipcode'),
        $district: $('#profile-subdistrict'), 
        $amphoe: $('#profile-district'),
        $province: $('#profile-province'),
    });

    const setEditMode = (isEditing) => {
        if (isEditing) {
            form.classList.remove('form-view-mode');
            form.classList.add('form-edit-mode');
            viewModeElements.forEach(el => el.classList.add('hidden'));
            editModeElements.forEach(el => el.classList.remove('hidden'));
        } else {
            form.classList.remove('form-edit-mode');
            form.classList.add('form-view-mode');
            viewModeElements.forEach(el => el.classList.remove('hidden'));
            editModeElements.forEach(el => el.classList.add('hidden'));
        }

        editBtn.classList.toggle('hidden', isEditing);
        saveBtn.classList.toggle('hidden', !isEditing);
        cancelBtn.classList.toggle('hidden', !isEditing);

        const allFields = form.querySelectorAll('input:not([type=hidden]), select');
        const nonEditable = ['national_id_display', 'work_department_display'];
        
        allFields.forEach(field => {
            const fieldName = field.getAttribute('name');
            if (nonEditable.includes(fieldName)) {
                field.disabled = true;
            } else if (!field.classList.contains('view-mode-element')) {
                field.disabled = !isEditing;
            }
        });
        
        // [NEW] Specifically handle the jquery.Thailand.js fields
        $('#profile-zipcode, #profile-subdistrict, #profile-district, #profile-province').each(function() {
            // The typeahead plugin might create a separate visible input. We target both.
            $(this).prop('disabled', !isEditing).parent().find('.tt-input').prop('disabled', !isEditing);
        });

        document.getElementById('profile-title').dispatchEvent(new Event('change'));
    };
    
    setEditMode(false);

    editBtn.addEventListener('click', () => {
        setEditMode(true);
    });

    cancelBtn.addEventListener('click', () => {
        location.reload();
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

