<?php
// app/views/admin/components/edit_user_modal.php
// This component contains the form for editing a user's details.
// It is included by view_user.php and uses variables defined there ($user, $departments, etc.).

// Pre-calculate date parts for the form
$user_dob_day = '';
$user_dob_month_num = '';
$user_dob_year_be = '';
if (!empty($user['dob']) && $user['dob'] !== '0000-00-00') {
    $dob_parts = explode('-', $user['dob']);
    $user_dob_year_be = isset($dob_parts[0]) ? (int)$dob_parts[0] + 543 : '';
    $user_dob_month_num = isset($dob_parts[1]) ? ltrim($dob_parts[1], '0') : '';
    $user_dob_day = isset($dob_parts[2]) ? ltrim($dob_parts[2], '0') : '';
}
$months = ["มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];

$standard_titles = ["นาย", "นาง", "นางสาว", "พล.อ.", "พล.อ.หญิง", "พล.ท.", "พล.ท.หญิง", "พล.ต.", "พล.ต.หญิง", "พ.อ.", "พ.อ.หญิง", "พ.ท.", "พ.ท.หญิง", "พ.ต.", "พ.ต.หญิง", "ร.อ.", "ร.อ.หญิง", "ร.ท.", "ร.ท.หญิง", "ร.ต.", "ร.ต.หญิง", "จ.ส.อ.", "จ.ส.อ.หญิง", "จ.ส.ท.", "จ.ส.ท.หญิง", "จ.ส.ต.", "จ.ส.ต.หญิง", "ส.อ.", "ส.อ.หญิง", "ส.ท.", "ส.ท.หญิง", "ส.ต.", "ส.ต.หญิง", "พลทหาร"];
$is_other_title = !in_array($user['title'], $standard_titles);
?>

<dialog id="edit_user_modal" class="modal modal-fade">
    <div class="modal-box w-11/12 max-w-4xl">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
        </form>
        <h3 class="font-bold text-lg">แก้ไขข้อมูลผู้ใช้งาน</h3>
        <p class="text-sm text-base-content/60">คุณกำลังแก้ไขข้อมูลของ: <?php echo htmlspecialchars($user['title'] . $user['firstname'] . '  ' . $user['lastname']); ?></p>
        
        <form id="editUserFormInModal" action="../../../controllers/admin/users/edit_user_process.php" method="POST" enctype="multipart/form-data" class="mt-4" novalidate>
            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Photo Column -->
                <div class="md:col-span-1">
                    <div class="form-control items-center p-4 border rounded-lg h-full">
                        <label class="label"><span class="label-text font-semibold">รูปถ่ายหน้าตรง</span></label>
                        <div class="avatar w-full max-w-xs">
                            <div class="w-full rounded-lg ring ring-primary ring-offset-base-100 ring-offset-2">
                                <img id="modal-photo-preview" src="<?php echo !empty($user['photo_profile']) ? '/public/uploads/' . htmlspecialchars($user['user_key']) . '/profile/' . htmlspecialchars($user['photo_profile']) : 'https://placehold.co/300x300/e2e8f0/475569?text=No+Image'; ?>" />
                            </div>
                        </div>
                        <input type="file" name="photo_upload" id="modal-photo-upload" class="file-input file-input-bordered file-input-sm w-full max-w-xs mt-4" accept="image/jpeg,image/png">
                        <p class="text-xs text-slate-500 mt-1">เลือกไฟล์ใหม่เพื่อเปลี่ยนแปลง</p>
                    </div>
                </div>

                <!-- Details Column -->
                <div class="md:col-span-2 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control w-full sm:col-span-2"><div class="grid grid-cols-3 gap-2">
                            <div class="form-control w-full"><label class="label py-1"><span class="label-text">คำนำหน้า</span></label>
                                <select name="title" class="select select-sm select-bordered w-full" required>
                                <?php foreach ($standard_titles as $title): ?>
                                    <option value="<?php echo $title; ?>" <?php echo ($user['title'] == $title) ? 'selected' : ''; ?>><?php echo $title; ?></option>
                                <?php endforeach; ?>
                                <option value="other" <?php echo $is_other_title ? 'selected' : ''; ?>>อื่นๆ</option>
                                </select>
                                <input type="text" name="title_other" placeholder="ระบุ" class="input input-sm input-bordered w-full mt-2 <?php echo $is_other_title ? '' : 'hidden'; ?>" value="<?php echo $is_other_title ? htmlspecialchars($user['title']) : ''; ?>" />
                            </div>
                            <div class="form-control w-full"><label class="label py-1"><span class="label-text">ชื่อจริง</span></label><input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" class="input input-sm input-bordered w-full" required /></div>
                            <div class="form-control w-full"><label class="label py-1"><span class="label-text">นามสกุล</span></label><input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" class="input input-sm input-bordered w-full" required /></div>
                        </div></div>

                        <div class="form-control w-full sm:col-span-2"><div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                            <div class="form-control w-full sm:col-span-8"><label class="label py-1"><span class="label-text">วันเดือนปีเกิด</span></label><div class="grid grid-cols-3 gap-2">
                                <select name="dob_day" class="select select-sm select-bordered w-full"><option value="">วัน</option><?php for($i=1; $i<=31; $i++) { echo "<option value='$i'" . ($user_dob_day == $i ? ' selected' : '') . ">$i</option>"; } ?></select>
                                <select name="dob_month" class="select select-sm select-bordered w-full"><option value="">เดือน</option><?php foreach($months as $i => $m) { echo "<option value='" . ($i+1) . "'" . ($user_dob_month_num == ($i+1) ? ' selected' : '') . ">$m</option>"; } ?></select>
                                <select name="dob_year" class="select select-sm select-bordered w-full"><option value="">ปี (พ.ศ.)</option><?php $current_year = date("Y")+543; for($i=$current_year-17; $i>=$current_year-100; $i--) { echo "<option value='$i'" . ($user_dob_year_be == $i ? ' selected' : '') . ">$i</option>"; } ?></select>
                            </div></div>
                            <div class="form-control w-full sm:col-span-4"><label class="label py-1"><span class="label-text">เพศ</span></label><select name="gender" class="select select-sm select-bordered w-full"><option value="">เลือก</option><option value="ชาย" <?php echo ($user['gender'] == 'ชาย' ? 'selected' : ''); ?>>ชาย</option><option value="หญิง" <?php echo ($user['gender'] == 'หญิง' ? 'selected' : ''); ?>>หญิง</option></select></div>
                        </div></div>

                        <div class="form-control w-full"><label class="label py-1"><span class="label-text">เบอร์โทรศัพท์</span></label><input type="tel" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" class="input input-sm input-bordered w-full" /></div>
                        <div class="form-control w-full"><label class="label py-1"><span class="label-text">เลขบัตรประชาชน</span></label><input type="tel" name="national_id" value="<?php echo htmlspecialchars($user['national_id']); ?>" class="input input-sm input-bordered w-full" /></div>
                    </div>
                    
                    <div class="divider text-sm">ที่อยู่ปัจจุบัน</div>
                     <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control w-full sm:col-span-2"><label class="label py-1"><span class="label-text">บ้านเลขที่/ที่อยู่</span></label><input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" class="input input-sm input-bordered w-full" /></div>
                        <div class="form-control w-full"><label class="label py-1"><span class="label-text">รหัสไปรษณีย์</span></label><input type="text" name="zipcode" value="<?php echo htmlspecialchars($user['zipcode']); ?>" class="input input-sm input-bordered w-full" /></div>
                        <div class="form-control w-full"><label class="label py-1"><span class="label-text">ตำบล/แขวง</span></label><input type="text" name="subdistrict" value="<?php echo htmlspecialchars($user['subdistrict']); ?>" class="input input-sm input-bordered w-full" /></div>
                        <div class="form-control w-full"><label class="label py-1"><span class="label-text">อำเภอ/เขต</span></label><input type="text" name="district" value="<?php echo htmlspecialchars($user['district']); ?>" class="input input-sm input-bordered w-full" /></div>
                        <div class="form-control w-full"><label class="label py-1"><span class="label-text">จังหวัด</span></label><input type="text" name="province" value="<?php echo htmlspecialchars($user['province']); ?>" class="input input-sm input-bordered w-full" /></div>
                     </div>

                    <?php if ($user['user_type'] === 'army'): ?>
                        <div class="divider text-sm">ข้อมูลการทำงาน</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control w-full"><label class="label py-1"><span class="label-text">สังกัด</span></label>
                                <select name="work_department" class="select select-sm select-bordered w-full">
                                    <option value="">เลือกสังกัด</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['name']); ?>" <?php echo ($user['work_department'] == $dept['name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                             <div class="form-control w-full"><label class="label py-1"><span class="label-text">ตำแหน่ง</span></label><input type="text" name="position" value="<?php echo htmlspecialchars($user['position']); ?>" class="input input-sm input-bordered w-full" /></div>
                             <div class="form-control w-full sm:col-span-2"><label class="label py-1"><span class="label-text">เลขบัตร ขรก.</span></label><input type="tel" name="official_id" value="<?php echo htmlspecialchars($user['official_id']); ?>" class="input input-sm input-bordered w-full" /></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="modal-action mt-6">
                <form method="dialog"><button class="btn btn-sm btn-ghost">ยกเลิก</button></form>
                <button type="submit" class="btn btn-sm btn-primary">บันทึกการเปลี่ยนแปลง</button>
            </div>
        </form>
    </div>
</dialog>
