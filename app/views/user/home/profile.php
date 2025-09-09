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


$conn->close();

require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Main Content -->
<main class="flex-grow container mx-auto max-w-4xl p-4">
    <div id="profile-section" class="main-section">
        <div class="card bg-base-100 shadow-lg">
            <div class="card-body">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                     <h2 class="card-title text-xl flex items-center gap-2"><i class="fa-solid fa-user-pen"></i> ข้อมูลส่วนตัวของคุณ</h2>
                     <div id="profile-action-buttons" class="flex gap-2">
                         <button id="edit-profile-btn" class="btn btn-warning btn-sm"><i class="fa-solid fa-pencil"></i> แก้ไขข้อมูล</button>
                         <button id="save-profile-btn" class="btn btn-success btn-sm hidden"><i class="fa-solid fa-save"></i> บันทึกข้อมูล</button>
                         <button id="cancel-edit-btn" class="btn btn-ghost btn-sm hidden"><i class="fa-solid fa-times"></i> ยกเลิก</button>
                     </div>
                </div>
                <form id="profileForm" action="../../../controllers/user/profile/edit_profile_process.php" method="POST" enctype="multipart/form-data" novalidate>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-1">
                            <div class="form-control w-full">
                                <label class="block font-medium mb-2 text-center">รูปถ่ายหน้าตรง</label>
                                <div id="profile-photo-container" class="flex justify-center bg-base-200 p-2 rounded-lg border overflow-hidden cursor-pointer" onclick="zoomImage('<?php echo $user_photo_path; ?>')">
                                    <img id="profile-photo-preview" src="<?php echo $user_photo_path; ?>" alt="รูปโปรไฟล์" class="w-full max-h-48 object-contain" onerror="this.onerror=null;this.src='https://placehold.co/192x192/CCCCCC/FFFFFF?text=Profile';">
                                </div>
                                <div id="photo-guidance" class="mt-2 text-xs p-2 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 hidden">
                                    <ul class="list-disc list-inside">
                                        <li>รูปถ่ายหน้าตรง คมชัด</li>
                                        <li>ไฟล์ .jpg, .jpeg, .png เท่านั้น</li>
                                        <li>ไฟล์ขนาดไม่เกิน 5 MB</li>
                                    </ul>
                                </div>
                                <input type="file" id="profile-photo-upload" name="photo_upload" class="file-input file-input-sm file-input-bordered w-full mt-2 hidden" accept=".jpg, .jpeg, .png">
                                <p class="error-message hidden"></p>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <div class="divider divider-start font-semibold">ข้อมูลส่วนตัว</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <?php
                                    $dob_parts = explode('-', $user['dob']);
                                    $user_dob_year = isset($dob_parts[0]) ? (int)$dob_parts[0] + 543 : '';
                                    $user_dob_month = $dob_parts[1] ?? '';
                                    $user_dob_day = $dob_parts[2] ?? '';
                                ?>
                                <div class="form-control w-full sm:col-span-2">
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="form-control w-full">
                                            <div class="label"><span class="label-text">คำนำหน้า</span></div>
                                            <select id="profile-title" name="title" class="select select-sm select-bordered w-full" disabled required>
                                                <option disabled value="">เลือกคำนำหน้า</option>
                                                <?php $titles = ["นาย", "นาง", "นางสาว", "พล.อ.", "พล.อ.หญิง", "พล.ท.", "พล.ท.หญิง", "พล.ต.", "พล.ต.หญิง", "พ.อ.", "พ.อ.หญิง", "พ.ท.", "พ.ท.หญิง", "พ.ต.", "พ.ต.หญิง", "ร.อ.", "ร.อ.หญิง", "ร.ท.", "ร.ท.หญิง", "ร.ต.", "ร.ต.หญิง", "จ.ส.อ.", "จ.ส.อ.หญิง", "จ.ส.ท.", "จ.ส.ท.หญิง", "จ.ส.ต.", "จ.ส.ต.หญิง", "ส.อ.", "ส.อ.หญิง", "ส.ท.", "ส.ท.หญิง", "ส.ต.", "ส.ต.หญิง", "พลทหาร"];
                                                $is_other_title = !in_array($user['title'], $titles);
                                                foreach($titles as $t) { echo "<option value='$t'" . ($user['title'] == $t ? ' selected' : '') . ">$t</option>"; }
                                                ?>
                                                <option value="other" <?php echo $is_other_title ? 'selected' : ''; ?>>อื่นๆ</option>
                                            </select>
                                            <input type="text" id="profile-title-other" name="title_other" placeholder="ระบุคำนำหน้า" class="input input-sm input-bordered w-full mt-2 <?php echo !$is_other_title ? 'hidden' : ''; ?>" value="<?php echo $is_other_title ? htmlspecialchars($user['title']) : ''; ?>" disabled/>
                                            <p class="error-message hidden"></p>
                                        </div>
                                        <div class="form-control w-full">
                                            <div class="label"><span class="label-text">ชื่อจริง</span></div>
                                            <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" class="input input-sm input-bordered w-full" disabled required />
                                            <p class="error-message hidden"></p>
                                        </div>
                                        <div class="form-control w-full">
                                            <div class="label"><span class="label-text">นามสกุล</span></div>
                                            <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" class="input input-sm input-bordered w-full" disabled required />
                                            <p class="error-message hidden"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-control w-full sm:col-span-2">
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                                        <div class="form-control w-full sm:col-span-9">
                                            <div class="label"><span class="label-text">วันเดือนปีเกิด</span></div>
                                            <div class="grid grid-cols-3 gap-2">
                                                <select id="profile-dob-day" name="dob_day" class="select select-sm select-bordered" disabled required></select>
                                                <select id="profile-dob-month" name="dob_month" class="select select-sm select-bordered" disabled required></select>
                                                <select id="profile-dob-year" name="dob_year" class="select select-sm select-bordered" disabled required></select>
                                            </div>
                                            <p class="error-message hidden"></p>
                                        </div>
                                         <div class="form-control w-full sm:col-span-3">
                                            <div class="label"><span class="label-text">เพศ</span></div>
                                            <select name="gender" class="select select-sm select-bordered w-full" disabled required>
                                                <option disabled value="">เลือกเพศ</option>
                                                <option value="ชาย" <?php echo $user['gender'] == 'ชาย' ? 'selected' : ''; ?>>ชาย</option>
                                                <option value="หญิง" <?php echo $user['gender'] == 'หญิง' ? 'selected' : ''; ?>>หญิง</option>
                                            </select>
                                            <p class="error-message hidden"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-control w-full">
                                    <div class="label"><span class="label-text">เบอร์โทร</span></div>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone_number']); ?>" class="input input-sm input-bordered w-full" disabled required maxlength="12" />
                                    <p class="error-message hidden"></p>
                                </div>
                                <div class="form-control w-full">
                                    <div class="label"><span class="label-text">เลขบัตรประชาชน</span></div>
                                    <input type="text" id="profile-national-id" name="national_id_display" value="<?php echo htmlspecialchars($user['national_id']); ?>" class="input input-sm input-bordered w-full" disabled maxlength="17"/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="divider divider-start font-semibold mt-6">ที่อยู่ปัจจุบัน</div>
                     <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="form-control w-full sm:col-span-2 md:col-span-4"><div class="label"><span class="label-text">บ้านเลขที่/ที่อยู่</span></div><input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" class="input input-sm input-bordered w-full" disabled required /><p class="error-message hidden"></p></div>
                        <div class="form-control w-full"><div class="label"><span class="label-text">ตำบล/แขวง</span></div><input type="text" id="profile-subdistrict" name="subdistrict" value="<?php echo htmlspecialchars($user['subdistrict']); ?>" class="input input-sm input-bordered w-full" disabled required /><p class="error-message hidden"></p></div>
                        <div class="form-control w-full"><div class="label"><span class="label-text">อำเภอ/เขต</span></div><input type="text" id="profile-district" name="district" value="<?php echo htmlspecialchars($user['district']); ?>" class="input input-sm input-bordered w-full" disabled required /><p class="error-message hidden"></p></div>
                        <div class="form-control w-full"><div class="label"><span class="label-text">จังหวัด</span></div><input type="text" id="profile-province" name="province" value="<?php echo htmlspecialchars($user['province']); ?>" class="input input-sm input-bordered w-full" disabled required /><p class="error-message hidden"></p></div>
                        <div class="form-control w-full"><div class="label"><span class="label-text">รหัสไปรษณีย์</span></div><input type="text" id="profile-zipcode" name="zipcode" value="<?php echo htmlspecialchars($user['zipcode']); ?>" class="input input-sm input-bordered w-full" disabled required /><p class="error-message hidden"></p></div>
                    </div>
                    <?php if ($user['user_type'] === 'army'): ?>
                    <div id="profile-work-info" class="mt-6">
                        <div class="divider divider-start font-semibold">ข้อมูลการทำงาน</div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="form-control w-full">
                                <div class="label"><span class="label-text">สังกัด</span></div>
                                <input type="text" name="work_department_display" value="<?php echo htmlspecialchars($user['work_department']); ?>" class="input input-sm input-bordered w-full" disabled />
                                <input type="hidden" name="work_department" value="<?php echo htmlspecialchars($user['work_department']); ?>" />
                            </div>
                            <div class="form-control w-full"><div class="label"><span class="label-text">ตำแหน่ง</span></div><input type="text" name="position" value="<?php echo htmlspecialchars($user['position']); ?>" class="input input-sm input-bordered w-full" disabled required /><p class="error-message hidden"></p></div>
                            <div class="form-control w-full"><div class="label"><span class="label-text">เลขบัตรข้าราชการ</span></div><input type="tel" name="official_id" value="<?php echo htmlspecialchars($user['official_id']); ?>" class="input input-sm input-bordered w-full" disabled maxlength="10" /><p class="error-message hidden"></p></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

