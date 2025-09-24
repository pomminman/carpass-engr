<?php
// app/views/admin/home/add_user.php
require_once __DIR__ . '/../layouts/header.php';

// --- Check permission ---
if (!in_array($admin_info['role'], ['admin', 'superadmin'])) {
    header("Location: dashboard.php");
    exit;
}

// --- Data fetching for departments dropdown ---
$departments = [];
$sql_dept = "SELECT name FROM departments ORDER BY display_order ASC, name ASC";
$result_dept = $conn->query($sql_dept);
if ($result_dept->num_rows > 0) {
    while($row = $result_dept->fetch_assoc()) {
        $departments[] = $row['name'];
    }
}
// Corrected list of titles
$standard_titles = ["นาย", "นาง", "นางสาว", "พล.อ.", "พล.อ.หญิง", "พล.ท.", "พล.ท.หญิง", "พล.ต.", "พล.ต.หญิง", "พ.อ.", "พ.อ.หญิง", "พ.ท.", "พ.ท.หญิง", "พ.ต.", "พ.ต.หญิง", "ร.อ.", "ร.อ.หญิง", "ร.ท.", "ร.ท.หญิง", "ร.ต.", "ร.ต.หญิง", "จ.ส.อ.", "จ.ส.อ.หญิง", "จ.ส.ท.", "จ.ส.ท.หญิง", "จ.ส.ต.", "จ.ส.ต.หญิง", "ส.อ.", "ส.อ.หญิง", "ส.ท.", "ส.ท.หญิง", "ส.ต.", "ส.ต.หญิง", "พลทหาร"];
?>

<!-- Page content -->
<main id="add-user-page" class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-4">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-user-plus text-primary"></i> เพิ่มผู้ใช้งานใหม่</h1>
            <p class="text-slate-500">สร้างบัญชีผู้ใช้ในระบบโดยเจ้าหน้าที่</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="manage_users.php" class="btn btn-sm btn-ghost">
                <i class="fa-solid fa-arrow-left"></i> กลับไปหน้าจัดการผู้ใช้
            </a>
        </div>
    </div>

    <div class="card bg-base-100 shadow-lg">
        <div class="card-body p-4 md:p-6">
            <form id="addUserForm" action="../../../controllers/admin/users/add_user_process.php" method="POST" enctype="multipart/form-data" novalidate>
                
                <!-- User Type Selection -->
                <div id="user-type-selection" class="p-4 border border-base-300 rounded-lg">
                    <label class="label pt-0"><span class="label-text font-semibold">เลือกประเภทผู้สมัคร</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="card card-compact bg-primary/5 border border-primary cursor-pointer hover:bg-primary/10 transition-colors">
                            <div class="card-body !p-3 !flex-row justify-between items-center">
                                <h3 class="card-title text-primary text-sm sm:text-base">ข้าราชการ/ลูกจ้าง/พนักงานราชการ ทบ.</h3>
                                <input type="radio" name="user_type" class="radio radio-primary" value="army" required />
                            </div>
                        </label>
                        <label class="card card-compact bg-primary/5 border border-primary cursor-pointer hover:bg-primary/10 transition-colors">
                            <div class="card-body !p-3 !flex-row justify-between items-center">
                                <h3 class="card-title text-primary text-sm sm:text-base">บุคคลภายนอก</h3>
                                <input type="radio" name="user_type" class="radio radio-primary" value="external" required />
                            </div>
                        </label>
                    </div>
                     <p class="error-message hidden text-xs text-error mt-2"></p>
                </div>

                <!-- Form Content (Initially hidden) -->
                <div id="main-form-content" class="hidden mt-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Photo Upload Column -->
                        <div class="md:col-span-1">
                            <div class="form-control">
                                <label class="block font-medium mb-2 text-center">รูปถ่ายหน้าตรง (ถ้ามี)</label>
                                <div class="flex justify-center bg-base-200 p-2 rounded-lg border">
                                    <img id="photo-preview" src="/public/assets/images/profile_example.png" alt="ตัวอย่างรูปถ่ายหน้าตรง" class="w-full max-h-48 rounded-lg object-contain" onerror="this.onerror=null;this.src='https://placehold.co/400x248/CCCCCC/FFFFFF?text=Example';">
                                </div>
                                <div class="mt-2 text-xs p-2 rounded-lg bg-blue-50 border border-blue-200 text-blue-800">
                                    <ul class="list-disc list-inside">
                                        <li>ไฟล์ .jpg, .jpeg, .png</li>
                                        <li>ขนาดไม่เกิน 5 MB</li>
                                    </ul>
                                </div>
                                <input type="file" id="photo-upload" name="photo_upload" class="file-input file-input-sm file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png">
                                <p class="error-message hidden text-xs text-error mt-1"></p>
                            </div>
                        </div>

                        <!-- Info Column -->
                        <div class="md:col-span-2 space-y-4">
                            <div>
                                <h3 class="font-semibold text-lg border-b pb-1 mb-3">ข้อมูลส่วนตัว</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="form-control w-full sm:col-span-2">
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="form-control w-full">
                                                <div class="label py-1"><span class="label-text">คำนำหน้า <span class="text-error">*</span></span></div>
                                                <select name="title" class="select select-sm select-bordered w-full" required>
                                                    <option disabled selected value="">เลือก</option>
                                                    <?php 
                                                        $user_titles = ["นาย", "นาง", "นางสาว", "พล.อ.", "พล.อ.หญิง", "พล.ท.", "พล.ท.หญิง", "พล.ต.", "พล.ต.หญิง", "พ.อ.", "พ.อ.หญิง", "พ.ท.", "พ.ท.หญิง", "พ.ต.", "พ.ต.หญิง", "ร.อ.", "ร.อ.หญิง", "ร.ท.", "ร.ท.หญิง", "ร.ต.", "ร.ต.หญิง", "จ.ส.อ.", "จ.ส.อ.หญิง", "จ.ส.ท.", "จ.ส.ท.หญิง", "จ.ส.ต.", "จ.ส.ต.หญิง", "ส.อ.", "ส.อ.หญิง", "ส.ท.", "ส.ท.หญิง", "ส.ต.", "ส.ต.หญิง", "พลทหาร"];
                                                        foreach($user_titles as $t) {
                                                            echo "<option value=\"$t\">$t</option>";
                                                        }
                                                    ?>
                                                    <option value="other">อื่นๆ</option>
                                                </select>
                                                <input type="text" name="title_other" placeholder="ระบุ" class="input input-sm input-bordered w-full mt-2 hidden" />
                                                <p class="error-message hidden text-xs text-error mt-1"></p>
                                            </div>
                                            <div class="form-control w-full">
                                                <div class="label py-1"><span class="label-text">ชื่อจริง <span class="text-error">*</span></span></div>
                                                <input type="text" name="firstname" placeholder="กรอกชื่อจริง" class="input input-sm input-bordered w-full" required />
                                                <p class="error-message hidden text-xs text-error mt-1"></p>
                                            </div>
                                            <div class="form-control w-full">
                                                <div class="label py-1"><span class="label-text">นามสกุล <span class="text-error">*</span></span></div>
                                                <input type="text" name="lastname" placeholder="กรอกนามสกุล" class="input input-sm input-bordered w-full" required />
                                                <p class="error-message hidden text-xs text-error mt-1"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-control w-full sm:col-span-2">
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                                            <div class="form-control w-full sm:col-span-8">
                                                <div class="label py-1"><span class="label-text">วันเดือนปีเกิด</span></div>
                                                <div class="grid grid-cols-3 gap-2">
                                                    <select name="dob_day" class="select select-sm select-bordered"><option disabled selected value="">วัน</option></select>
                                                    <select name="dob_month" class="select select-sm select-bordered"><option disabled selected value="">เดือน</option></select>
                                                    <select name="dob_year" class="select select-sm select-bordered"><option disabled selected value="">ปี พ.ศ.</option></select>
                                                </div>
                                            </div>
                                            <div class="form-control w-full sm:col-span-4">
                                                <div class="label py-1"><span class="label-text">เพศ <span class="text-error">*</span></span></div>
                                                <select name="gender" class="select select-sm select-bordered w-full" required>
                                                    <option disabled selected value="">เลือกเพศ</option>
                                                    <option value="ชาย">ชาย</option>
                                                    <option value="หญิง">หญิง</option>
                                                </select>
                                                <p class="error-message hidden text-xs text-error mt-1"></p>
                                            </div>
                                        </div>
                                    </div>
                                     <div class="form-control">
                                        <div class="label py-1"><span class="label-text">เบอร์โทรศัพท์</span></div>
                                        <input type="tel" name="phone_number" class="input input-sm input-bordered" placeholder="081-234-5678" />
                                         <p class="error-message hidden text-xs text-error mt-1"></p>
                                    </div>
                                    <div class="form-control">
                                        <div class="label py-1"><span class="label-text">เลขบัตรประชาชน</span></div>
                                        <input type="tel" name="national_id" class="input input-sm input-bordered" placeholder="1-2345-67890-12-3" />
                                         <p class="error-message hidden text-xs text-error mt-1"></p>
                                    </div>
                                </div>
                            </div>

                            <div id="work-info-section" class="hidden w-full">
                                <h3 class="font-semibold text-lg border-b pb-1 mb-3">ข้อมูลการทำงาน</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="form-control w-full">
                                        <div class="label py-1"><span class="label-text">สังกัด <span class="text-error">*</span></span></div>
                                        <select name="work_department" class="select select-sm select-bordered w-full">
                                            <option disabled selected value="">เลือกสังกัด</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                            <?php endforeach; ?>
                                            <option value="other">อื่นๆ</option>
                                        </select>
                                        <input type="text" name="work_department_other" placeholder="ระบุสังกัด" class="input input-sm input-bordered w-full mt-2 hidden" />
                                        <p class="error-message hidden text-xs text-error mt-1"></p>
                                    </div>
                                    <div class="form-control w-full">
                                        <div class="label py-1"><span class="label-text">ตำแหน่ง</span></div>
                                        <input type="text" name="position" placeholder="เช่น นายทหาร" class="input input-sm input-bordered w-full" />
                                    </div>
                                    <div class="form-control w-full">
                                        <div class="label py-1"><span class="label-text">เลขบัตรประจำตัวข้าราชการ</span></div>
                                        <input type="tel" name="official_id" placeholder="10 หลัก (ถ้ามี)" class="input input-sm input-bordered w-full" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 id="address-header" class="font-semibold text-lg border-b pb-1 mb-3">ที่อยู่ปัจจุบัน</h3>
                        <div class="space-y-4">
                            <div class="form-control w-full">
                                <div class="label py-1"><span class="label-text">บ้านเลขที่/ที่อยู่</span></div>
                                <input type="text" name="address" placeholder="บ้านเลขที่, หมู่, ซอย, ถนน" class="input input-sm input-bordered w-full" />
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="form-control w-full">
                                     <div class="label py-1"><span class="label-text">รหัสไปรษณีย์</span></div>
                                     <input type="text" name="zipcode" class="input input-sm input-bordered w-full" placeholder="ค้นหา..." />
                                </div>
                                <div class="form-control w-full">
                                     <div class="label py-1"><span class="label-text">ตำบล/แขวง</span></div>
                                     <input type="text" name="subdistrict" class="input input-sm input-bordered w-full" placeholder="ค้นหา..." />
                                </div>
                                <div class="form-control w-full">
                                     <div class="label py-1"><span class="label-text">อำเภอ/เขต</span></div>
                                     <input type="text" name="district" class="input input-sm input-bordered w-full" placeholder="ค้นหา..." />
                                </div>
                                <div class="form-control w-full">
                                     <div class="label py-1"><span class="label-text">จังหวัด</span></div>
                                     <input type="text" name="province" class="input input-sm input-bordered w-full" placeholder="ค้นหา..." />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-actions justify-center mt-4">
                        <button type="submit" id="submit-btn" class="btn btn-primary" disabled>
                            <i class="fa-solid fa-save"></i> บันทึกข้อมูลผู้ใช้งาน
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

