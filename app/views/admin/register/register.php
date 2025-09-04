<?php
// --- app/views/admin/register/register.php (Rewritten with JavaScript Access Check) ---
session_start();

// ดึงข้อความแจ้งเตือน (ถ้ามี) สำหรับฟอร์มลงทะเบียนหลัก
$error_message = $_SESSION['register_error'] ?? null;
$success_message = $_SESSION['register_success'] ?? null;
unset($_SESSION['register_error'], $_SESSION['register_success']);

// เรียกใช้ไฟล์ db_config เพื่อดึงรายชื่อสังกัด
require_once '../../../models/db_config.php';
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    $departments = [];
} else {
    $conn->set_charset("utf8");
    $departments = [];
    $sql_dept = "SELECT name FROM departments ORDER BY display_order ASC, name ASC";
    $result_dept = $conn->query($sql_dept);
    if ($result_dept->num_rows > 0) {
        while($row = $result_dept->fetch_assoc()) {
            $departments[] = $row;
        }
    }
    $conn->close();
}

$titles = ["นาย", "นาง", "นางสาว", "พล.อ.", "พล.อ.หญิง", "พล.ท.", "พล.ท.หญิง", "พล.ต.", "พล.ต.หญิง", "พ.อ.", "พ.อ.หญิง", "พ.ท.", "พ.ท.หญิง", "พ.ต.", "พ.ต.หญิง", "ร.อ.", "ร.อ.หญิง", "ร.ท.", "ร.ท.หญิง", "ร.ต.", "ร.ต.หญิง", "จ.ส.อ.", "จ.ส.อ.หญิง", "จ.ส.ท.", "จ.ส.ท.หญิง", "จ.ส.ต.", "จ.ส.ต.หญิง", "ส.อ.", "ส.อ.หญิง", "ส.ท.", "ส.ท.หญิง", "ส.ต.", "ส.ต.หญิง", "พลทหาร"];
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin ระบบยื่นคำร้องขอบัตรผ่านยานพาหนะ เข้า-ออก ค่ายภาณุรังษี</title>

    <link rel="icon" type="image/png" href="/public/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/public/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/public/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/public/assets/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="carpass engrdept" />
    <link rel="manifest" href="/public/assets/favicon/site.webmanifest" />
    
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;700&display=swap" rel="stylesheet">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body { font-family: 'Prompt', sans-serif; }
    </style>
</head>
<body class="bg-slate-50">
    <div class="min-h-screen flex items-center justify-center p-4 sm:p-8">
        
        <!-- [ใหม่] ส่วนสำหรับกรอกรหัสเข้าใช้งาน -->
        <div id="access-section" class="w-full max-w-md">
            <div class="card bg-base-100 shadow-xl border border-base-300/50">
                <form id="access-form">
                    <div class="card-body">
                        <h1 class="card-title text-2xl mb-4">ยืนยันตัวตนก่อนเข้าใช้งาน</h1>
                        <p class="text-sm text-slate-500 mb-4">กรุณากรอกรหัสผ่านสำหรับเข้าถึงหน้าลงทะเบียนผู้ดูแลระบบ</p>
                        
                        <div id="access-error-msg" class="hidden alert alert-error mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>รหัสเข้าใช้งานไม่ถูกต้อง</span>
                        </div>

                        <div class="form-control">
                            <label class="label"><span class="label-text">รหัสเข้าใช้งาน</span></label>
                            <input type="password" id="access_code_input" placeholder="กรอกรหัส..." class="input input-bordered" required autofocus>
                        </div>
                        <div class="card-actions justify-end mt-6">
                            <a href="../login/login.php" class="btn btn-ghost">กลับหน้าล็อกอิน</a>
                            <button type="submit" class="btn btn-primary">เข้าใช้งาน</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- [ใหม่] ส่วนฟอร์มลงทะเบียน (ซ่อนไว้ก่อน) -->
        <div id="register-section" class="w-full max-w-3xl hidden">
            <div class="card bg-base-100 shadow-xl border border-base-300/50">
                <div class="card-body">
                    <h1 class="card-title text-2xl mb-4">สร้างบัญชีผู้ดูแลระบบ</h1>

                    <?php if ($error_message): ?>
                    <div role="alert" class="alert alert-error mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($success_message): ?>
                    <div role="alert" class="alert alert-success mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                    <?php endif; ?>

                    <form action="../../../controllers/admin/register/process_register.php" method="POST">
                        
                        <div class="divider divider-start font-semibold">ข้อมูลบัญชี</div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="form-control sm:col-span-1">
                                <label class="label"><span class="label-text">ชื่อผู้ใช้งาน (Username)</span></label>
                                <input type="text" name="username" placeholder="เช่น admin01" class="input input-sm input-bordered" required>
                            </div>
                            <div class="form-control sm:col-span-1">
                                <label class="label"><span class="label-text">รหัสผ่าน</span></label>
                                <input type="text" name="password" placeholder="กรอกรหัสผ่าน" class="input input-sm input-bordered" required>
                            </div>
                            <div class="form-control sm:col-span-1">
                                <label class="label"><span class="label-text">ยืนยันรหัสผ่าน</span></label>
                                <input type="text" name="confirm_password" placeholder="ยืนยันรหัสผ่าน" class="input input-sm input-bordered" required>
                            </div>
                        </div>

                        <div class="divider divider-start font-semibold mt-6">ข้อมูลส่วนตัว</div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text">คำนำหน้า</span></label>
                                <select name="title" class="select select-sm select-bordered w-full" id="title-select" required>
                                    <option disabled selected value="">เลือกคำนำหน้า</option>
                                    <?php foreach ($titles as $title): ?>
                                        <option value="<?php echo htmlspecialchars($title); ?>"><?php echo htmlspecialchars($title); ?></option>
                                    <?php endforeach; ?>
                                    <option value="other">อื่นๆ (โปรดระบุ)</option>
                                </select>
                                <input type="text" id="title-other" name="title_other" placeholder="ระบุคำนำหน้าใหม่" class="input input-sm input-bordered w-full mt-2 hidden" />
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">ชื่อจริง</span></label>
                                <input type="text" name="firstname" placeholder="ชื่อจริง" class="input input-sm input-bordered" required>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">นามสกุล</span></label>
                                <input type="text" name="lastname" placeholder="นามสกุล" class="input input-sm input-bordered" required>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">เบอร์โทร</span></label>
                                <input type="tel" name="phone_number" placeholder="08xxxxxxxx" class="input input-sm input-bordered">
                            </div>
                        </div>

                        <div class="divider divider-start font-semibold mt-6">ข้อมูลการทำงาน</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text">ตำแหน่ง</span></label>
                                <input type="text" name="position" placeholder="เช่น น.สารบรรณ" class="input input-sm input-bordered">
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">สังกัด</span></label>
                                <select name="department" class="select select-sm select-bordered w-full" id="department-select">
                                    <option disabled selected value="">เลือกสังกัด</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['name']); ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endforeach; ?>
                                    <option value="other">อื่นๆ (โปรดระบุ)</option>
                                </select>
                                <input type="text" id="department-other" name="department_other" placeholder="ระบุสังกัดใหม่" class="input input-sm input-bordered w-full mt-2 hidden" />
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">ระดับสิทธิ์ (Role)</span></label>
                                <select name="role" class="select select-sm select-bordered" required>
                                    <option value="viewer">Viewer</option>
                                    <option value="admin" selected>Admin</option>
                                    <option value="superadmin">Superadmin</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">สิทธิ์การเข้าถึงข้อมูล</span></label>
                                <div class="flex items-center gap-4 mt-2">
                                    <label class="label cursor-pointer gap-2">
                                        <input type="radio" name="view_permission" class="radio radio-sm" value="1" />
                                        <span class="label-text">ดูข้อมูลได้ทุกสังกัด</span> 
                                    </label>
                                    <label class="label cursor-pointer gap-2">
                                        <input type="radio" name="view_permission" class="radio radio-sm" value="0" checked/>
                                        <span class="label-text">เฉพาะสังกัดตนเอง</span> 
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="card-actions justify-end mt-8">
                            <button type="submit" class="btn btn-primary">สร้างบัญชี</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const accessSection = document.getElementById('access-section');
            const registerSection = document.getElementById('register-section');
            const accessForm = document.getElementById('access-form');
            const accessCodeInput = document.getElementById('access_code_input');
            const accessErrorMsg = document.getElementById('access-error-msg');
            
            const correctCode = '17395';

            accessForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const enteredCode = accessCodeInput.value;

                if (enteredCode === correctCode) {
                    accessSection.classList.add('hidden');
                    registerSection.classList.remove('hidden');
                    sessionStorage.setItem('adminRegisterAccess', 'granted');
                } else {
                    accessErrorMsg.classList.remove('hidden');
                    accessCodeInput.classList.add('input-error');
                }
            });

            if (sessionStorage.getItem('adminRegisterAccess') === 'granted') {
                accessSection.classList.add('hidden');
                registerSection.classList.remove('hidden');
            }

            function setupOtherOption(selectId, otherInputId) {
                const select = document.getElementById(selectId);
                const otherInput = document.getElementById(otherInputId);
                if(select && otherInput){
                    select.addEventListener('change', function() {
                        if (this.value === 'other') {
                            otherInput.classList.remove('hidden');
                            otherInput.setAttribute('required', '');
                        } else {
                            otherInput.classList.add('hidden');
                            otherInput.removeAttribute('required');
                        }
                    });
                }
            }
            setupOtherOption('department-select', 'department-other');
            setupOtherOption('title-select', 'title-other');
        });
    </script>
</body>
</html>

