<?php
// app/views/user/register/register.php

// --- [Security] เริ่มต้น Session ---
session_start();

// --- [แก้ไข] เรียกใช้ไฟล์ตั้งค่าฐานข้อมูล ---
require_once '../../../models/db_config.php';

// --- ส่วนเชื่อมต่อฐานข้อมูลเพื่อดึงรายชื่อสังกัด ---
$conn_dept = new mysqli($servername, $username, $password, $dbname);
if ($conn_dept->connect_error) {
    die("Connection failed: " . $conn_dept->connect_error);
}
$conn_dept->set_charset("utf8");

// ดึงข้อมูลสังกัดทั้งหมดมาเก็บใน array
$departments = [];
// เรียงตาม display_order ก่อน แล้วตามด้วยชื่อ (สำหรับรายการที่เพิ่มมาใหม่)
$sql_dept = "SELECT name FROM departments ORDER BY display_order ASC, name ASC";
$result_dept = $conn_dept->query($sql_dept);
if ($result_dept->num_rows > 0) {
    while($row = $result_dept->fetch_assoc()) {
        $departments[] = $row;
    }
}
$conn_dept->close(); // ปิดการเชื่อมต่อหลังดึงข้อมูลเสร็จ
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>ระบบยื่นคำร้องขอบัตรผ่านยานพาหนะ เข้า-ออก ค่ายภาณุรังษี</title>

    <link rel="icon" type="image/png" href="/public/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/public/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/public/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/public/assets/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="carpass engrdept" />
    <link rel="manifest" href="/public/assets/favicon/site.webmanifest" />

    <!-- [NEW] Toastify.js Library -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Local CSS -->
    <link rel="stylesheet" href="/lib/daisyui@4.12.10/dist/full.min.css" type="text/css" />
    <link rel="stylesheet" href="/lib/jquery.Thailand/dist/jquery.Thailand.min.css">
    <link rel="stylesheet" href="/lib/google-fonts-prompt/prompt.css">
    <link rel="stylesheet" href="/lib/fontawesome-free-7.0.1-web/css/all.min.css">

    <!-- Local JS -->
    <script src="/lib/tailwindcss/tailwindcss.js"></script>

    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f0f2f5; }
        .error-message { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; }
        .alert-soft {
            border-width: 1px;
        }
        .alert-error.alert-soft {
            background-color: #fee2e2;
            border-color: #fca5a5;
            color: #b91c1c;
        }
        .alert-success.alert-soft {
            background-color: #dcfce7;
            border-color: #86efac;
            color: #166534;
        }
        .alert-info.alert-soft {
            background-color: #e0f2fe;
            border-color: #7dd3fc;
            color: #0369a1;
        }
        /* [เพิ่ม] สไตล์สำหรับ dropdown ของ jquery.Thailand.js ให้มี scrollbar */
        .twitter-typeahead .tt-menu {
            max-height: 250px;
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
</head>
<body class="flex flex-col min-h-screen">
    
    <header class="w-full navbar bg-base-100 shadow-md z-30 sticky top-0">
        <div class="container mx-auto">
            <div class="flex-1 px-2 mx-2">
                <a class="text-base font-bold flex items-center gap-2">
                    <img src="https://img2.pic.in.th/pic/CARPASS-logo11af8574a9cc9906.png" alt="Logo" class="h-16 w-16">
                    <div>
                        <span class="whitespace-nowrap text-sm sm:text-base">ระบบยื่นคำร้องขอบัตรผ่านยานพาหนะ</span>
                        <span class="text-xs font-normal text-gray-500 block">เข้า-ออก ค่ายภาณุรังษี</span>
                    </div>
                </a>
            </div>
        </div>
    </header>
    <main class="flex-grow p-6 pb-24 max-w-4xl mx-auto w-full">
        <div class="card bg-base-100 shadow-2xl border border-base-300/50 sm:rounded-2xl mx-auto">
            <div class="card-body p-6 md:p-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-4">
                    <h2 class="card-title">สมัครใช้งานระบบ</h2>
                    <div class="flex gap-2">
                        <button id="reset-form-btn" type="button" class="btn btn-sm btn-ghost"><i class="fa-solid fa-eraser"></i> ล้างข้อมูล</button>
                        <a href="../login/login.php" class="btn btn-sm"><i class="fa-solid fa-house"></i> กลับหน้าหลัก</a>
                    </div>
                </div>
                <?php
                if (empty($_SESSION['csrf_token'])) {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }
                ?>
                <form action="../../../controllers/user/register/register_process.php" method="POST" id="personalInfoForm" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" id="user_type_hidden" name="user_type" value="">
                    
                    <div id="initial-info-section" class="space-y-4 mb-6">
                        <div id="user-type-selection" class="space-y-4 p-4 border border-base-300 rounded-lg">
                            <label class="label"><span class="label-text font-semibold">เลือกประเภทผู้สมัคร</span></label>
                            <label class="card card-compact bg-primary/5 border border-primary cursor-pointer">
                                <div class="card-body">
                                    <div class="flex justify-between items-center">
                                        <h3 class="card-title text-primary text-base">ข้าราชการ/ลูกจ้าง/พนักงานราชการ ทบ.</h3>
                                        <input type="radio" name="user_type_radio" class="radio radio-primary" value="army" />
                                    </div>
                                </div>
                            </label>
                            <label class="card card-compact bg-primary/5 border border-primary cursor-pointer">
                                <div class="card-body">
                                    <div class="flex justify-between items-center">
                                        <h3 class="card-title text-primary text-base">บุคคลภายนอก</h3>
                                        <input type="radio" name="user_type_radio" class="radio radio-primary" value="external" />
                                    </div>
                                    <div class="mt-2 text-left text-xs p-3 rounded-lg bg-sky-50 border border-sky-200 text-sky-800">
                                        <div class="flex items-start gap-2">
                                            <i class="fa-solid fa-circle-info mt-0.5 text-sky-500"></i>
                                            <div>
                                                <span class="font-semibold">ตัวอย่างผู้สมัคร:</span>
                                                <ul class="list-disc list-inside pl-2 mt-1">
                                                    <li>บุคคลทั่วไป</li>
                                                    <li>ข้าราชการเกษียณอายุ</li>
                                                    <li>ข้าราชการพลเรือน</li>
                                                    <li>ข้าราชการเหล่าทัพอื่น</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        
                        <div id="verification-section" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 p-4 border border-base-300 rounded-lg">
                            <div class="form-control w-full">
                                <div class="label"><span class="label-text">เบอร์โทรศัพท์</span></div>
                                <label class="input input-sm input-bordered flex items-center gap-2">
                                    <i class="fa-solid fa-phone w-4 h-4 opacity-70"></i>
                                    <input type="tel" id="verify-phone" placeholder="000-000-0000" class="grow" maxlength="12" />
                                </label>
                                <p class="error-message hidden"></p>
                            </div>
                            <div class="form-control w-full">
                                <div class="label"><span class="label-text">เลขบัตรประชาชน</span></div>
                                <label class="input input-sm input-bordered flex items-center gap-2">
                                    <i class="fa-solid fa-id-card w-4 h-4 opacity-70"></i>
                                    <input type="tel" id="verify-nid" placeholder="0-0000-00000-00-0" class="grow" maxlength="17" />
                                </label>
                                <p class="error-message hidden"></p>
                            </div>
                             <div class="sm:col-span-2 text-center text-xs text-gray-500 mt-2 h-4">
                                 <span id="verification-status">ระบบจะตรวจสอบข้อมูลโดยอัตโนมัติเมื่อกรอกครบ</span>
                             </div>
                        </div>
                    </div>
                    
                    <div id="main-form-content" class="hidden">
                        <!-- Layout ใหม่ -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-1">
                                <div class="border border-base-300 rounded-lg p-4 w-full h-full flex flex-col form-control">
                                    <label class="block font-medium mb-2 text-center">รูปถ่ายหน้าตรง</label>
                                    <div class="flex justify-center bg-base-200 p-2 rounded-lg border">
                                        <img id="photo-preview" src="/public/assets/images/profile_example.png" alt="ตัวอย่างรูปถ่ายหน้าตรง" class="w-full max-h-48 rounded-lg object-contain" onerror="this.onerror=null;this.src='https://placehold.co/400x248/CCCCCC/FFFFFF?text=Example';">
                                    </div>
                                    <div class="mt-2 text-xs p-2 rounded-lg bg-blue-50 border border-blue-200 text-blue-800">
                                        <ul class="list-disc list-inside">
                                            <li>รูปถ่ายหน้าตรง คมชัด</li>
                                            <li>ไฟล์ .jpg, .jpeg, .png เท่านั้น</li>
                                            <li>ไฟล์ขนาดไม่เกิน 5 MB</li>
                                        </ul>
                                    </div>
                                    <input type="file" id="photo-upload" name="photo_upload" class="file-input file-input-sm file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png" required>
                                    <p class="error-message hidden"></p>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <div class="divider divider-start text-lg font-semibold">ข้อมูลส่วนตัว</div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="form-control w-full sm:col-span-2">
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="form-control w-full">
                                                <div class="label"><span class="label-text">คำนำหน้า</span></div>
                                                <select id="title" name="title" class="select select-sm select-bordered w-full" required>
                                                     <option disabled selected value="">เลือก</option>
                                                     <option value="นาย">นาย</option>
                                                     <option value="นาง">นาง</option>
                                                     <option value="นางสาว">น.ส.</option>
                                                     <option value="พล.อ.">พล.อ.</option>
                                                     <option value="พล.อ.หญิง">พล.อ.หญิง</option>
                                                     <option value="พล.ท.">พล.ท.</option>
                                                     <option value="พล.ท.หญิง">พล.ท.หญิง</option>
                                                     <option value="พล.ต.">พล.ต.</option>
                                                     <option value="พล.ต.หญิง">พล.ต.หญิง</option>
                                                     <option value="พ.อ.">พ.อ.</option>
                                                     <option value="พ.อ.หญิง">พ.อ.หญิง</option>
                                                     <option value="พ.ท.">พ.ท.</option>
                                                     <option value="พ.ท.หญิง">พ.ท.หญิง</option>
                                                     <option value="พ.ต.">พ.ต.</option>
                                                     <option value="พ.ต.หญิง">พ.ต.หญิง</option>
                                                     <option value="ร.อ.">ร.อ.</option>
                                                     <option value="ร.อ.หญิง">ร.อ.หญิง</option>
                                                     <option value="ร.ท.">ร.ท.</option>
                                                     <option value="ร.ท.หญิง">ร.ท.หญิง</option>
                                                     <option value="ร.ต.">ร.ต.</option>
                                                     <option value="ร.ต.หญิง">ร.ต.หญิง</option>
                                                     <option value="จ.ส.อ.">จ.ส.อ.</option>
                                                     <option value="จ.ส.อ.หญิง">จ.ส.อ.หญิง</option>
                                                     <option value="จ.ส.ท.">จ.ส.ท.</option>
                                                     <option value="จ.ส.ท.หญิง">จ.ส.ท.หญิง</option>
                                                     <option value="จ.ส.ต.">จ.ส.ต.</option>
                                                     <option value="จ.ส.ต.หญิง">จ.ส.ต.หญิง</option>
                                                     <option value="ส.อ.">ส.อ.</option>
                                                     <option value="ส.อ.หญิง">ส.อ.หญิง</option>
                                                     <option value="ส.ท.">ส.ท.</option>
                                                     <option value="ส.ท.หญิง">ส.ท.หญิง</option>
                                                     <option value="ส.ต.">ส.ต.</option>
                                                     <option value="ส.ต.หญิง">ส.ต.หญิง</option>
                                                     <option value="พลทหาร">พลทหาร</option>
                                                     <option value="other">อื่นๆ</option>
                                                </select>
                                                <input type="text" id="title-other" name="title_other" placeholder="ระบุ" class="input input-sm input-bordered w-full mt-2 hidden" />
                                                <p class="error-message hidden"></p>
                                            </div>
                                            <div class="form-control w-full">
                                                <div class="label"><span class="label-text">ชื่อจริง</span></div>
                                                <input type="text" id="firstname" name="firstname" placeholder="กรอกชื่อจริง" class="input input-sm input-bordered w-full" required />
                                                 <p class="error-message hidden"></p>
                                            </div>
                                            <div class="form-control w-full">
                                                <div class="label"><span class="label-text">นามสกุล</span></div>
                                                <input type="text" id="lastname" name="lastname" placeholder="กรอกนามสกุล" class="input input-sm input-bordered w-full" required />
                                                 <p class="error-message hidden"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-control w-full sm:col-span-2">
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                                            <div class="form-control w-full sm:col-span-9">
                                                <div class="label"><span class="label-text">วันเดือนปีเกิด</span></div>
                                                <div class="grid grid-cols-3 gap-2">
                                                    <select id="dob-day" name="dob_day" class="select select-sm select-bordered" required><option disabled selected value="">วัน</option></select>
                                                    <select id="dob-month" name="dob_month" class="select select-sm select-bordered" required><option disabled selected value="">เดือน</option></select>
                                                    <select id="dob-year" name="dob_year" class="select select-sm select-bordered" required><option disabled selected value="">ปี</option></select>
                                                </div>
                                                <p class="error-message hidden"></p>
                                            </div>
                                            <div class="form-control w-full sm:col-span-3">
                                                <div class="label"><span class="label-text">เพศ</span></div>
                                                <select id="gender" name="gender" class="select select-sm select-bordered w-full" required>
                                                    <option disabled selected value="">เลือกเพศ</option>
                                                    <option value="ชาย">ชาย</option>
                                                    <option value="หญิง">หญิง</option>
                                                </select>
                                                <p class="error-message hidden"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-control w-full">
                                        <div class="label"><span class="label-text">เบอร์โทร</span></div>
                                        <input type="tel" id="form-phone" name="form_phone" class="input input-sm input-bordered w-full input-disabled" readonly />
                                    </div>
                                    <div class="form-control w-full">
                                        <div class="label"><span class="label-text">เลขบัตรประชาชน</span></div>
                                        <input type="tel" id="personal-id" name="personal_id" class="input input-sm input-bordered w-full input-disabled" readonly />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="work-info-section" class="hidden w-full mt-6">
                            <div class="divider divider-start text-lg font-semibold">ข้อมูลการทำงาน</div>
                             <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                                 <div class="form-control w-full">
                                     <div class="label"><span class="label-text">สังกัด</span></div>
                                     <select id="work-department" name="work_department" class="select select-sm select-bordered w-full">
                                         <option disabled selected value="">เลือกสังกัด</option>
                                         <?php foreach ($departments as $dept): ?>
                                             <option value="<?php echo htmlspecialchars($dept['name']); ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                         <?php endforeach; ?>
                                         <option value="other">อื่นๆ</option>
                                     </select>
                                     <input type="text" id="work-department-other" name="work_department_other" placeholder="ระบุสังกัด" class="input input-sm input-bordered w-full mt-2 hidden" />
                                     <p class="error-message hidden"></p>
                                  </div>
                                 <div class="form-control w-full">
                                     <div class="label"><span class="label-text">ตำแหน่ง</span></div>
                                     <input type="text" id="position" name="position" placeholder="เช่น นายทหาร" class="input input-sm input-bordered w-full" />
                                     <p class="error-message hidden"></p>
                                  </div>
                                 <div class="form-control w-full">
                                     <div class="label"><span class="label-text">เลขบัตรประจำตัวข้าราชการ</span></div>
                                     <input type="tel" id="official-id" name="official_id" inputmode="numeric" maxlength="10" placeholder="กรอกเลขบัตร 10 หลัก" class="input input-sm input-bordered w-full" />
                                     <p class="error-message hidden"></p>
                                  </div>
                            </div>
                        </div>

                        <div id="address-divider" class="divider divider-start text-lg font-semibold mt-6">ที่อยู่ปัจจุบัน</div>
                        <div class="space-y-4 mb-6">
                            <div class="form-control w-full">
                                <div class="label"><span class="label-text">บ้านเลขที่/ที่อยู่</span></div>
                                <input type="text" id="address" name="address" placeholder="บ้านเลขที่, หมู่, ซอย, ถนน" class="input input-sm input-bordered w-full" required />
                                 <p class="error-message hidden"></p>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="form-control w-full">
                                     <div class="label"><span class="label-text">รหัสไปรษณีย์</span></div>
                                     <input type="text" id="zipcode" name="zipcode" class="input input-sm input-bordered w-full" placeholder="พิมพ์เพื่อค้นหา..." required />
                                     <p class="error-message hidden"></p>
                                </div>
                                <div class="form-control w-full">
                                     <div class="label"><span class="label-text">ตำบล/แขวง</span></div>
                                     <input type="text" id="subdistrict" name="subdistrict" class="input input-sm input-bordered w-full" placeholder="พิมพ์เพื่อค้นหา..." required />
                                     <p class="error-message hidden"></p>
                                </div>
                                <div class="form-control w-full">
                                     <div class="label"><span class="label-text">อำเภอ/เขต</span></div>
                                     <input type="text" id="district" name="district" class="input input-sm input-bordered w-full" placeholder="พิมพ์เพื่อค้นหา..." required />
                                     <p class="error-message hidden"></p>
                                </div>
                                <div class="form-control w-full">
                                     <div class="label"><span class="label-text">จังหวัด</span></div>
                                     <input type="text" id="province" name="province" class="input input-sm input-bordered w-full" placeholder="พิมพ์เพื่อค้นหา..." required />
                                     <p class="error-message hidden"></p>
                                </div>
                            </div>
                        </div>

                        <div class="sm:col-span-2 mt-6">
                            <div class="flex justify-center">
                                <div class="form-control w-full max-w-md">
                                    <label class="label cursor-pointer justify-start gap-4">
                                    <input type="checkbox" id="confirm-terms" name="confirm_terms" class="checkbox checkbox-primary" required />
                                    <span class="label-text font-semibold">ยอมรับข้อตกลงและเงื่อนไข</span>
                                    </label>
                                    <div class="text-[11px] sm:text-xs text-base-content/70 pl-10">
                                        <ul class="list-disc list-inside">
                                            <li>ยืนยันข้อมูลถูกต้องและยอมรับเงื่อนไข</li>
                                            <li>ยินยอมให้ตรวจสอบข้อมูล</li>
                                            <li>ตรวจสอบข้อมูลแล้ว</li>
                                        </ul>
                                    </div>
                                    <p class="error-message hidden pl-10"></p>
                                </div>
                            </div>

                            <div class="card-actions justify-center mt-6">
                                <button type="button" id="review-btn" class="btn btn-sm btn-success">
                                    ยืนยันการสมัคร
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="fixed bottom-0 left-0 right-0 bg-base-200 text-base-content shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] p-1 text-center z-40">
        <p class="text-[10px] sm:text-xs whitespace-nowrap">Developed by ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ/กยข.กช.)</p>
    </footer>

    <dialog id="resetConfirmModal" class="modal modal-middle">
      <div class="modal-box">
        <h3 class="font-bold text-lg">ยืนยันการล้างข้อมูล</h3>
        <p class="py-4">คุณแน่ใจหรือไม่ว่าต้องการล้างข้อมูลที่กรอกไว้ทั้งหมด?</p>
        <div class="modal-action">
          <form method="dialog">
            <button class="btn btn-sm">ยกเลิก</button>
          </form>
          <button id="confirm-reset-btn" class="btn btn-sm btn-error">ยืนยัน</button>
        </div>
      </div>
       <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
    <!-- [แก้ไข] เปลี่ยนคลาสของ Modal เพื่อให้แสดงผลกึ่งกลางบนทุกอุปกรณ์ -->
    <dialog id="confirmModal" class="modal modal-middle">
      <div class="modal-box w-11/12 max-w-4xl">
        <h3 class="font-bold text-lg">โปรดตรวจสอบข้อมูลของท่าน</h3>
        <div id="summary-content" class="py-4 space-y-4 text-sm"></div>
        <div class="modal-action">
          <form method="dialog">
            <button class="btn btn-sm">แก้ไข</button>
          </form>
          <button id="final-submit-btn" class="btn btn-sm btn-success">ยืนยันและส่งข้อมูล</button>
        </div>
      </div>
    </dialog>
    <dialog id="loadingModal" class="modal modal-middle">
        <div class="modal-box text-center">
            <span class="loading loading-spinner loading-lg text-primary"></span>
            <h3 class="font-bold text-lg mt-4">กรุณารอสักครู่</h3>
            <p class="py-4">ระบบกำลังบันทึกข้อมูลการสมัครของท่าน<br>กรุณาอย่าปิดหรือรีเฟรชหน้านี้</p>
        </div>
    </dialog>

    <script src="/lib/jquery/jquery-3.7.1.min.js"></script>
    <script src="/lib/jquery.Thailand/dependencies/JQL.min.js"></script>
    <script src="/lib/jquery.Thailand/dependencies/typeahead.bundle.js"></script>
    <script src="/lib/jquery.Thailand/dist/jquery.Thailand.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- ส่วนประกาศตัวแปร ---
            const userTypeRadios = document.querySelectorAll('input[name="user_type_radio"]');
            const userTypeHiddenInput = document.getElementById('user_type_hidden');
            const verificationSection = document.getElementById('verification-section');
            const verifyPhoneInput = document.getElementById('verify-phone');
            const verifyNidInput = document.getElementById('verify-nid');
            const mainFormContent = document.getElementById('main-form-content');
            const workInfoSection = document.getElementById('work-info-section');
            const personalInfoForm = document.getElementById('personalInfoForm');
            const resetFormBtn = document.getElementById('reset-form-btn');
            const resetConfirmModal = document.getElementById('resetConfirmModal');
            const confirmResetBtn = document.getElementById('confirm-reset-btn');
            const reviewBtn = document.getElementById('review-btn');
            const confirmModal = document.getElementById('confirmModal');
            const finalSubmitBtn = document.getElementById('final-submit-btn');
            const loadingModal = document.getElementById('loadingModal');
            let selectedUserType = '';
            let isVerified = false;
            let phoneStatus = { isChecked: false, isAvailable: false };
            let nidStatus = { isChecked: false, isAvailable: false };
            let isVerifying = false;

            // --- [แก้ไข] ฟังก์ชันสำหรับเพิ่ม/ลบ attribute 'required' ---
            function setRequiredAttributes(sectionId, required) {
                const section = document.getElementById(sectionId);
                if (section) {
                    const fields = section.querySelectorAll('input:not([type=hidden]), select');
                    fields.forEach(field => {
                        if (!field.name.endsWith('_other')) {
                            if (required) {
                                field.setAttribute('required', '');
                            } else {
                                field.removeAttribute('required');
                                clearFeedback(field);
                            }
                        }
                    });
                }
            }
            
            // --- การทำงานของปุ่มล้างข้อมูล ---
            resetFormBtn.addEventListener('click', () => resetConfirmModal.showModal());
            confirmResetBtn.addEventListener('click', () => {
                personalInfoForm.reset();
                mainFormContent.classList.add('hidden');
                verificationSection.classList.add('hidden');
                workInfoSection.classList.add('hidden');
                setRequiredAttributes('work-info-section', false);
                userTypeHiddenInput.value = '';
                userTypeRadios.forEach(radio => {
                    const parentContainer = radio.closest('.card');
                    parentContainer.classList.remove('hidden');
                    radio.disabled = false;
                    radio.checked = false;
                });
                
                personalInfoForm.querySelectorAll('input, select').forEach(field => clearFeedback(field));
                clearFeedback(verifyPhoneInput);
                clearFeedback(verifyNidInput);
                
                isVerified = false;
                selectedUserType = '';
                phoneStatus = { isChecked: false, isAvailable: false };
                nidStatus = { isChecked: false, isAvailable: false };
                document.getElementById('verification-status').textContent = 'ระบบจะตรวจสอบข้อมูลโดยอัตโนมัติเมื่อกรอกครบ';

                resetConfirmModal.close();
            });

            // --- การทำงานเมื่อเลือกประเภทผู้สมัคร ---
            userTypeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    selectedUserType = this.value;
                    verificationSection.classList.remove('hidden');
                });
            });

            // --- ฟังก์ชันตรวจสอบอัตโนมัติ ---
            async function verifyField(fieldType) {
                if (isVerifying) return;

                const phoneRaw = verifyPhoneInput.value.replace(/\D/g, '');
                const nidRaw = verifyNidInput.value.replace(/\D/g, '');
                const statusEl = document.getElementById('verification-status');
                
                let payload = {};
                let fieldToCheck;
                let statusObject;
                let inputElement;
                let successMessage = '';

                if (fieldType === 'phone' && phoneRaw.length === 10) {
                    payload = { phone: phoneRaw };
                    fieldToCheck = 'phoneExists';
                    statusObject = phoneStatus;
                    inputElement = verifyPhoneInput;
                    successMessage = 'เบอร์โทรศัพท์นี้สามารถใช้งานได้';
                } else if (fieldType === 'nid' && nidRaw.length === 13) {
                    payload = { nid: nidRaw };
                    fieldToCheck = 'nidExists';
                    statusObject = nidStatus;
                    inputElement = verifyNidInput;
                    successMessage = 'เลขบัตรประชาชนนี้สามารถใช้งานได้';
                } else {
                    return;
                }

                isVerifying = true;
                statusEl.innerHTML = '<span class="loading loading-spinner loading-xs"></span> กำลังตรวจสอบข้อมูล...';
                
                const result = await checkDatabase(payload);
                
                isVerifying = false;
                statusEl.textContent = 'ระบบจะตรวจสอบข้อมูลโดยอัตโนมัติเมื่อกรอกครบ';

                if (result && result.error) { return; }

                statusObject.isChecked = true;
                if (result[fieldToCheck]) {
                    statusObject.isAvailable = false;
                    showError(inputElement, fieldToCheck === 'phoneExists' ? 'เบอร์โทรศัพท์นี้มีอยู่ในระบบแล้ว' : 'เลขบัตรประชาชนนี้มีอยู่ในระบบแล้ว');
                } else {
                    statusObject.isAvailable = true;
                    showSuccess(inputElement, successMessage);
                    showAlert(successMessage, 'success');
                }

                if (phoneStatus.isChecked && phoneStatus.isAvailable && nidStatus.isChecked && nidStatus.isAvailable) {
                    isVerified = true;
                    verificationSection.classList.add('hidden');
                    mainFormContent.classList.remove('hidden');
                    document.getElementById('form-phone').value = verifyPhoneInput.value;
                    document.getElementById('personal-id').value = verifyNidInput.value;
                    userTypeHiddenInput.value = selectedUserType;
                    userTypeRadios.forEach(radio => {
                        const parentContainer = radio.closest('.card');
                        if (radio.value !== selectedUserType) {
                            parentContainer.classList.add('hidden');
                        }
                        radio.disabled = true;
                    });
                    
                    if(selectedUserType === 'army') {
                        workInfoSection.classList.remove('hidden');
                        setRequiredAttributes('work-info-section', true);
                        document.getElementById('address-divider').textContent = 'ที่อยู่ปัจจุบัน';
                    } else {
                        workInfoSection.classList.add('hidden');
                        setRequiredAttributes('work-info-section', false);
                        document.getElementById('address-divider').textContent = 'ที่อยู่ปัจจุบัน';
                    }

                    initializeMainFormListeners();
                }
            }

            verifyPhoneInput.addEventListener('input', () => {
                formatInput(verifyPhoneInput, 'xxx-xxx-xxxx');
                clearFeedback(verifyPhoneInput);
                phoneStatus = { isChecked: false, isAvailable: false };
                if (verifyPhoneInput.value.replace(/\D/g, '').length === 10) {
                    verifyField('phone');
                }
            });

            verifyNidInput.addEventListener('input', () => {
                formatInput(verifyNidInput, 'x-xxxx-xxxxx-xx-x');
                clearFeedback(verifyNidInput);
                nidStatus = { isChecked: false, isAvailable: false };
                if (verifyNidInput.value.replace(/\D/g, '').length === 13) {
                    verifyField('nid');
                }
            });

            // --- การทำงานของปุ่ม "ยืนยันการสมัคร" ---
            reviewBtn.addEventListener('click', function() {
                if (!isVerified) {
                    showAlert('โปรดกรอกและตรวจสอบข้อมูลเบอร์โทรและเลขบัตรประชาชนให้ถูกต้องก่อน', 'error');
                    return;
                }
                
                let isFormValid = true;
                
                const fieldsToValidate = personalInfoForm.querySelectorAll('input, select, textarea');
                fieldsToValidate.forEach(field => {
                    if (field.offsetParent !== null) {
                        if (!validateField(field)) {
                            isFormValid = false;
                        }
                    }
                });

                if (isFormValid) {
                    populateConfirmModal();
                    confirmModal.showModal();
                } else {
                    showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error');
                    const firstErrorField = personalInfoForm.querySelector('.input-error, .select-error, .file-input-error');
                     if (firstErrorField) {
                         firstErrorField.focus();
                         firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                     }
                }
            });

            // --- การทำงานของปุ่ม "ยืนยันและส่งข้อมูล" (ใน Modal) ---
            finalSubmitBtn.addEventListener('click', () => {
                loadingModal.showModal();
                personalInfoForm.submit();
            });

            // --- ฟังก์ชันสำหรับตรวจสอบความถูกต้องของแต่ละช่องข้อมูล ---
            function validateField(field) {
                if (field.offsetParent === null) return true; 
                
                let isValid = true;
                const value = field.value.trim();
                clearFeedback(field); 

                if (field.hasAttribute('required')) {
                    if (field.type === 'checkbox' && !field.checked) {
                        showError(field, 'กรุณายอมรับเงื่อนไข');
                        isValid = false;
                    } else if (field.type === 'file' && field.files.length === 0) {
                        showError(field, 'กรุณาอัปโหลดไฟล์');
                        isValid = false;
                    } else if (field.type === 'file' && field.files.length > 0) {
                        const file = field.files[0];
                        const maxSize = 5 * 1024 * 1024; // 5 MB
                        if (file.size > maxSize) {
                            showError(field, 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB');
                            isValid = false;
                        }
                    } else if (field.tagName === 'SELECT' && value === '') {
                        showError(field, 'กรุณาเลือกข้อมูล');
                        isValid = false;
                    } else if (field.tagName !== 'SELECT' && field.type !== 'checkbox' && field.type !== 'file' && value === '') {
                         showError(field, 'กรุณากรอกข้อมูล');
                         isValid = false;
                    }
                }

                if (isValid) {
                    if (field.id === 'title' && value === 'other' && document.getElementById('title-other').value.trim() === '') {
                        showError(document.getElementById('title-other'), 'กรุณาระบุคำนำหน้า');
                        isValid = false;
                    } else if (field.id === 'work-department' && value === 'other' && document.getElementById('work-department-other').value.trim() === '') {
                        showError(document.getElementById('work-department-other'), 'กรุณาระบุสังกัด');
                        isValid = false;
                    } 
                    else if (field.id === 'official-id' && value.length > 0 && value.length !== 10) {
                        showError(field, 'กรุณากรอกเลขบัตรให้ครบ 10 หลัก');
                        isValid = false;
                    }
                }

                return isValid;
            }

            // --- [แก้ไข] ฟังก์ชันสำหรับแสดงข้อความและสไตล์เมื่อเกิดข้อผิดพลาด ---
            function showError(element, message) {
                const parent = element.closest('.form-control');
                if (!parent) return;

                const feedbackElement = parent.querySelector('.error-message');
                if (feedbackElement) {
                    feedbackElement.textContent = message;
                    feedbackElement.classList.remove('hidden', 'text-green-600');
                    feedbackElement.classList.add('text-red-500'); 
                }
                
                const target = element.closest('label.input') || element;
                target.classList.remove('input-success');
                if (element.type === 'file') {
                     target.classList.add('file-input-error');
                } else {
                     target.classList.add('input-error', 'select-error');
                }
            }

            // --- [เพิ่ม] ฟังก์ชันสำหรับแสดงข้อความสำเร็จ ---
            function showSuccess(element, message) {
                const parent = element.closest('.form-control');
                if (!parent) return;

                const feedbackElement = parent.querySelector('.error-message');
                if (feedbackElement) {
                    feedbackElement.textContent = message;
                    feedbackElement.classList.remove('hidden', 'text-red-500');
                    feedbackElement.classList.add('text-green-600');
                }
                
                const target = element.closest('label.input') || element;
                target.classList.remove('input-error', 'select-error', 'file-input-error');
                target.classList.add('input-success');
            }


            // --- [แก้ไข] ฟังก์ชันสำหรับล้างข้อความและสไตล์ข้อผิดพลาด/สำเร็จ ---
            function clearFeedback(element) {
                const parent = element.closest('.form-control');
                 if (!parent) return;

                const errorElement = parent.querySelector('.error-message');
                if (errorElement) {
                    errorElement.textContent = '';
                    errorElement.classList.add('hidden');
                    errorElement.classList.remove('text-red-500', 'text-green-600');
                }

                const target = element.closest('label.input') || element;
                target.classList.remove('input-error', 'select-error', 'file-input-error', 'input-success');
            }
            
            // --- ฟังก์ชันที่รวบรวม Listener และการตั้งค่าต่างๆ ---
            function initializeMainFormListeners() {
                mainFormContent.querySelectorAll('input, select').forEach(field => {
                    const eventType = (field.tagName === 'SELECT' || field.type === 'checkbox' || field.type === 'file') ? 'change' : 'input';
                    field.addEventListener(eventType, () => validateField(field));
                });

                setupImagePreview('photo-upload', 'photo-preview');
                
                const daySelect = document.getElementById('dob-day');
                const monthSelect = document.getElementById('dob-month');
                const yearSelect = document.getElementById('dob-year');
                const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
                daySelect.innerHTML = '<option disabled selected value="">วัน</option>';
                monthSelect.innerHTML = '<option disabled selected value="">เดือน</option>';
                yearSelect.innerHTML = '<option disabled selected value="">ปี (พ.ศ.)</option>';
                for (let i = 1; i <= 31; i++) { daySelect.innerHTML += `<option value="${i}">${i}</option>`; }
                months.forEach((month, i) => { monthSelect.innerHTML += `<option value="${i + 1}">${month}</option>`; });
                const currentYearBE = new Date().getFullYear() + 543;
                for (let i = currentYearBE; i >= currentYearBE - 100; i--) { yearSelect.innerHTML += `<option value="${i}">${i}</option>`; }
                
                function setupOtherOption(selectId, otherInputId) {
                    const select = document.getElementById(selectId);
                    const otherInput = document.getElementById(otherInputId);
                    if (select && otherInput) {
                        select.addEventListener('change', function() {
                            otherInput.classList.toggle('hidden', this.value !== 'other');
                            if (this.value === 'other') {
                                otherInput.setAttribute('required', '');
                            } else {
                                otherInput.removeAttribute('required');
                                otherInput.value = '';
                                clearFeedback(otherInput);
                            }
                        });
                    }
                }
                setupOtherOption('title', 'title-other');
                setupOtherOption('work-department', 'work-department-other');

                document.getElementById('firstname').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙\s]/g, ''); });
                document.getElementById('lastname').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙\s]/g, ''); });
                document.getElementById('title-other').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙\s.()]/g, ''); });
                document.getElementById('address').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙0-9\s.\-\/]/g, ''); });
                document.getElementById('work-department-other').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙0-9\s.\-()]/g, ''); });
                document.getElementById('official-id').addEventListener('input', function() { this.value = this.value.replace(/\D/g, ''); });

                $.Thailand({
                    $district: $('#subdistrict'),
                    $amphoe: $('#district'),
                    $province: $('#province'),
                    $zipcode: $('#zipcode'),
                    onDataFill: function(data){
                        ['subdistrict', 'district', 'province', 'zipcode'].forEach(id => validateField(document.getElementById(id)));
                    }
                });
            }

            // --- ฟังก์ชันสำหรับแสดง Alert (Toast) ---
            function showAlert(message, type = 'info') {
                const colors = {
                    success: "linear-gradient(to right, #00b09b, #96c93d)",
                    error: "linear-gradient(to right, #ff5f6d, #ffc371)",
                    info: "linear-gradient(to right, #2193b0, #6dd5ed)",
                    warning: "linear-gradient(to right, #f39c12, #f1c40f)"
                };

                Toastify({
                    text: message,
                    duration: 3000,
                    newWindow: true,
                    close: true,
                    gravity: "top", // `top` or `bottom`
                    position: "center", // `left`, `center` or `right`
                    stopOnFocus: true, // Prevents dismissing of toast on hover
                    style: {
                        background: colors[type] || colors['info'],
                    },
                    onClick: function(){} // Callback after click
                }).showToast();
            }

            // --- ฟังก์ชันสำหรับส่งข้อมูลไปตรวจสอบกับฐานข้อมูล ---
            async function checkDatabase(payload) {
                try {
                    const response = await fetch('../../../controllers/user/register/check_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload), 
                    });
                    if (!response.ok) {
                        throw new Error('Network response was not ok.');
                    }
                    return await response.json();
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้', 'error');
                    return { error: 'Connection failed' };
                }
            }

            // --- ฟังก์ชันสำหรับจัดรูปแบบการกรอกข้อมูล (ใส่ขีด "-") ---
            function formatInput(input, pattern) {
                const numbers = input.value.replace(/\D/g, '');
                let result = '';
                let patternIndex = 0;
                let numbersIndex = 0;
                while(patternIndex < pattern.length && numbersIndex < numbers.length) {
                    if (pattern[patternIndex] === '-') {
                        result += '-';
                        patternIndex++;
                    } else {
                        result += numbers[numbersIndex];
                        patternIndex++;
                        numbersIndex++;
                    }
                }
                input.value = result;
            }

            // --- ฟังก์ชันสำหรับแสดงภาพตัวอย่าง (Preview) ---
            function setupImagePreview(inputId, previewId) {
                const inputElement = document.getElementById(inputId);
                if(inputElement) {
                    inputElement.addEventListener('change', function(event) {
                        const file = event.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                document.getElementById(previewId).src = e.target.result;
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                }
            }

            // --- [แก้ไข] ฟังก์ชันสำหรับสร้างเนื้อหาสรุปข้อมูลใน Modal ยืนยัน (ปรับ Layout สำหรับ Desktop) ---
            function populateConfirmModal() {
                const summaryContent = document.getElementById('summary-content');
                const formData = new FormData(personalInfoForm);
                let html = '<div class="grid grid-cols-1 md:grid-cols-3 gap-4">';

                // Column 1: Profile Picture
                html += `<div class="md:col-span-1"><img src="${document.getElementById('photo-preview').src}" class="w-full rounded-lg border"><p class="text-xs font-semibold text-center mt-1">รูปถ่ายหน้าตรง</p></div>`;

                // Column 2: Details
                html += '<div class="md:col-span-2 space-y-3">';

                // Personal Info
                html += '<div>';
                html += '<div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">ข้อมูลส่วนตัว</div>';
                // ใช้ space-y สำหรับ mobile, และ grid สำหรับ desktop (md:)
                html += '<div class="p-2 bg-base-200 rounded-md space-y-2 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-4 md:gap-y-1 text-xs">';
                let title = formData.get('title');
                if (title === 'other') {
                    title = formData.get('title_other');
                }
                const dob = `${formData.get('dob_day')} ${document.getElementById('dob-month').options[document.getElementById('dob-month').selectedIndex].text} ${formData.get('dob_year')}`;
                
                // ชื่อ-สกุล (เต็มความกว้างบน desktop)
                html += `<div class="md:col-span-2"><strong>ชื่อ-สกุล:</strong> ${title || ''} ${formData.get('firstname') || ''} ${formData.get('lastname') || ''}</div>`;
                // วันเดือนปีเกิด & เพศ
                html += `<div><strong>วันเดือนปีเกิด:</strong> ${dob}</div>`;
                html += `<div><strong>เพศ:</strong> ${formData.get('gender') || '-'}</div>`;
                // เบอร์โทร & เลขบัตร
                html += `<div><strong>เบอร์โทร:</strong> ${verifyPhoneInput.value || '-'}</div>`;
                html += `<div><strong>เลขบัตร:</strong> ${verifyNidInput.value || '-'}</div>`;
                html += '</div></div>';
                
                // Work Info
                if (selectedUserType === 'army') {
                    html += '<div>';
                    html += '<div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">ข้อมูลการทำงาน</div>';
                    // ใช้ space-y สำหรับ mobile, และ grid สำหรับ desktop (md:)
                    html += '<div class="p-2 bg-base-200 rounded-md space-y-2 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-4 md:gap-y-1 text-xs">';
                    let workDept = formData.get('work_department');
                    if (workDept === 'other') {
                        workDept = formData.get('work_department_other');
                    }
                    // สังกัด & ตำแหน่ง
                    html += `<div><strong>สังกัด:</strong> ${workDept || '-'}</div>`;
                    html += `<div><strong>ตำแหน่ง:</strong> ${formData.get('position') || 'ไม่ได้ระบุ'}</div>`;
                    // เลขบัตร ขรก. (เต็มความกว้างบน desktop)
                    html += `<div class="md:col-span-2"><strong>เลขบัตร ขรก.:</strong> ${formData.get('official_id') || 'ไม่ได้ระบุ'}</div>`;
                    html += '</div></div>';
                }

                // Address Info (คงรูปแบบเดิม)
                html += '<div>';
                html += '<div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">ที่อยู่ปัจจุบัน</div>';
                html += '<div class="p-2 bg-base-200 rounded-md grid grid-cols-2 gap-x-4 gap-y-1 text-xs">';
                html += `<div class="col-span-2"><strong>ที่อยู่:</strong> ${formData.get('address') || '-'}</div>`;
                html += `<div><strong>ตำบล/แขวง:</strong> ${formData.get('subdistrict') || '-'}</div>`;
                html += `<div><strong>อำเภอ/เขต:</strong> ${formData.get('district') || '-'}</div>`;
                html += `<div><strong>จังหวัด:</strong> ${formData.get('province') || '-'}</div>`;
                html += `<div><strong>รหัสไปรษณีย์:</strong> ${formData.get('zipcode') || '-'}</div>`;
                html += '</div></div>';

                html += '</div>'; // Close column 2
                html += '</div>'; // Close grid
                summaryContent.innerHTML = html;
            }
        });
    </script>
</body>
</html>

