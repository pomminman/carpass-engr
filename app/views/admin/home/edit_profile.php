<?php
// app/views/admin/home/edit_profile.php
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Page content -->
<main id="edit-profile-page" class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-4">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-user-pen text-primary"></i> แก้ไขข้อมูลส่วนตัว</h1>
            <p class="text-slate-500">จัดการข้อมูลและรหัสผ่านของคุณ</p>
        </div>
    </div>

    <div class="card bg-base-100 shadow-lg max-w-2xl mx-auto">
        <div class="card-body p-4 md:p-6">
            <form id="editProfileForm" action="../../../controllers/admin/admins/edit_profile_process.php" method="POST" novalidate>
                <div class="space-y-4">
                    <!-- Personal Info -->
                    <div>
                        <h3 class="font-semibold text-lg border-b pb-1 mb-3">ข้อมูลทั่วไป</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="form-control">
                                <label class="label py-1"><span class="label-text">คำนำหน้า <span class="text-error">*</span></span></label>
                                <input type="text" name="title" placeholder="เช่น ร.ท." class="input input-sm input-bordered" required value="<?php echo htmlspecialchars($admin_info['title']); ?>">
                                <p class="error-message hidden text-xs text-error mt-1"></p>
                            </div>
                            <div class="form-control">
                                <label class="label py-1"><span class="label-text">ชื่อจริง <span class="text-error">*</span></span></label>
                                <input type="text" name="firstname" placeholder="เช่น พรหมินทร์" class="input input-sm input-bordered" required value="<?php echo htmlspecialchars($admin_info['firstname']); ?>">
                                <p class="error-message hidden text-xs text-error mt-1"></p>
                            </div>
                            <div class="form-control">
                                <label class="label py-1"><span class="label-text">นามสกุล <span class="text-error">*</span></span></label>
                                <input type="text" name="lastname" placeholder="เช่น อินทมาตย์" class="input input-sm input-bordered" required value="<?php echo htmlspecialchars($admin_info['lastname']); ?>">
                                <p class="error-message hidden text-xs text-error mt-1"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Account Info -->
                    <div>
                        <h3 class="font-semibold text-lg border-b pb-1 mb-3">ข้อมูลบัญชี</h3>
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text">ชื่อผู้ใช้ (Username) <span class="text-error">*</span></span></label>
                            <input type="text" name="username" placeholder="ห้ามซ้ำกับคนอื่น" class="input input-sm input-bordered" required value="<?php echo htmlspecialchars($admin_info['username']); ?>">
                            <p class="error-message hidden text-xs text-error mt-1"></p>
                        </div>
                    </div>
                    
                    <!-- Password Change -->
                    <div>
                        <h3 class="font-semibold text-lg border-b pb-1 mb-3">เปลี่ยนรหัสผ่าน</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label py-1"><span class="label-text">รหัสผ่านใหม่</span></label>
                                <input type="password" name="new_password" placeholder="กรอกเพื่อเปลี่ยน (อย่างน้อย 6 ตัวอักษร)" class="input input-sm input-bordered">
                                <p class="error-message hidden text-xs text-error mt-1"></p>
                            </div>
                            <div class="form-control">
                                <label class="label py-1"><span class="label-text">ยืนยันรหัสผ่านใหม่</span></label>
                                <input type="password" name="confirm_password" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" class="input input-sm input-bordered">
                                <p class="error-message hidden text-xs text-error mt-1"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-actions justify-center mt-6">
                    <button type="submit" id="submit-btn" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
