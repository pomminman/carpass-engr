<?php
// app/views/admin/home/add_request.php
require_once __DIR__ . '/../layouts/header.php';

// 1. Get user ID from URL and validate it
$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
if (!$user_id) {
    // Set a flash message and redirect if no valid user ID
    $_SESSION['flash_message'] = "โปรดเลือกผู้ใช้งานก่อนสร้างคำร้อง";
    $_SESSION['flash_status'] = "error";
    header("Location: manage_requests.php");
    exit;
}

// 2. Fetch user data
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
if ($user_result->num_rows !== 1) {
    $_SESSION['flash_message'] = "ไม่พบข้อมูลผู้ใช้งานที่เลือก";
    $_SESSION['flash_status'] = "error";
    header("Location: manage_requests.php");
    exit;
}
$user = $user_result->fetch_assoc();
$stmt_user->close();

// 3. Fetch data for dropdowns (Provinces, Car Brands)
$provinces = ['กระบี่', 'กรุงเทพมหานคร', 'กาญจนบุรี', 'กาฬสินธุ์', 'กำแพงเพชร', 'ขอนแก่น', 'จันทบุรี', 'ฉะเชิงเทรา', 'ชลบุรี', 'ชัยนาท', 'ชัยภูมิ', 'ชุมพร', 'เชียงราย', 'เชียงใหม่', 'ตรัง', 'ตราด', 'ตาก', 'นครนายก', 'นครปฐม', 'นครพนม', 'นครราชสีมา', 'นครศรีธรรมราช', 'นครสวรรค์', 'นนทบุรี', 'นราธิวาส', 'น่าน', 'บึงกาฬ', 'บุรีรัมย์', 'ปทุมธานี', 'ประจวบคีรีขันธ์', 'ปราจีนบุรี', 'ปัตตานี', 'พระนครศรีอยุธยา', 'พะเยา', 'พังงา', 'พัทลุง', 'พิจิตร', 'พิษณุโลก', 'เพชรบุรี', 'เพชรบูรณ์', 'แพร่', 'ภูเก็ต', 'มหาสารคาม', 'มุกดาหาร', 'แม่ฮ่องสอน', 'ยโสธร', 'ยะลา', 'ร้อยเอ็ด', 'ระนอง', 'ระยอง', 'ราชบุรี', 'ลพบุรี', 'ลำปาง', 'ลำพูน', 'เลย', 'ศรีสะเกษ', 'สกลนคร', 'สงขลา', 'สตูล', 'สมุทรปราการ', 'สมุทรสงคราม', 'สมุทรสาคร', 'สระแก้ว', 'สระบุรี', 'สิงห์บุรี', 'สุโขทัย', 'สุพรรณบุรี', 'สุราษฎร์ธานี', 'สุรินทร์', 'หนองคาย', 'หนองบัวลำภู', 'อ่างทอง', 'อำนาจเจริญ', 'อุดรธานี', 'อุตรดิตถ์', 'อุทัยธานี', 'อุบลราชธานี'];
$car_brands = [];
$sql_brands = "SELECT name FROM car_brands ORDER BY display_order ASC, name ASC";
$result_brands = $conn->query($sql_brands);
if ($result_brands && $result_brands->num_rows > 0) {
    while($row = $result_brands->fetch_assoc()) {
        $car_brands[] = $row['name'];
    }
}
$user_type_thai = $user['user_type'] === 'army' ? 'กำลังพล ทบ.' : 'บุคคลภายนอก';

?>

<!-- Page content -->
<main id="add-request-page" class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-4">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">
                <i class="fa-solid fa-file-circle-plus text-primary"></i> เพิ่มคำร้องโดยเจ้าหน้าที่
            </h1>
            <p class="text-slate-500">สร้างคำร้องขอบัตรผ่านยานพาหนะใหม่สำหรับผู้ใช้งานที่เลือก</p>
        </div>
        <div>
            <a href="manage_requests.php" class="btn btn-sm btn-ghost">
                <i class="fa-solid fa-arrow-left"></i> กลับไปหน้าจัดการคำร้อง
            </a>
        </div>
    </div>

    <!-- User Information Header -->
    <div class="card bg-base-100 shadow mb-6">
        <div class="card-body p-4">
            <div class="flex items-center gap-4">
                <div class="avatar">
                    <div class="w-16 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                        <img src="/public/uploads/<?php echo htmlspecialchars($user['user_key']); ?>/profile/<?php echo htmlspecialchars($user['photo_profile']); ?>" onerror="this.onerror=null;this.src='https://placehold.co/100x100/e2e8f0/475569?text=No+Img';" />
                    </div>
                </div>
                <div>
                    <h2 class="font-bold text-lg"><?php echo htmlspecialchars($user['title'] . ' ' . $user['firstname'] . ' ' . $user['lastname']); ?></h2>
                    <p class="text-sm text-slate-500">ประเภท: <?php echo $user_type_thai; ?> | ID: <?php echo $user['id']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Request Form -->
    <form action="../../../controllers/admin/requests/add_request_process.php" method="POST" enctype="multipart/form-data" id="addRequestForm" novalidate>
        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column: Vehicle and Owner Info -->
                    <div class="space-y-4">
                        <div class="divider divider-start font-semibold m-0">ข้อมูลยานพาหนะ</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div class="form-control w-full">
                                <label class="label py-1"><span class="label-text">ประเภทรถ</span></label>
                                <select name="vehicle_type" class="select select-sm select-bordered" required>
                                    <option disabled selected value="">เลือกประเภท</option>
                                    <option value="รถยนต์">รถยนต์</option>
                                    <option value="รถจักรยานยนต์">รถจักรยานยนต์</option>
                                </select>
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                            <div class="form-control w-full">
                                <label class="label py-1"><span class="label-text">ยี่ห้อรถ</span></label>
                                <select name="vehicle_brand" class="select select-sm select-bordered" required>
                                    <option disabled selected value="">เลือกยี่ห้อ</option>
                                    <?php foreach ($car_brands as $brand): ?>
                                        <option value="<?php echo htmlspecialchars($brand); ?>"><?php echo htmlspecialchars($brand); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div class="form-control w-full">
                                <label class="label py-1"><span class="label-text">เลขทะเบียนรถ</span></label>
                                <input type="text" name="license_plate" placeholder="เช่น 1กข1234" class="input input-sm input-bordered w-full" required>
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                            <div class="form-control w-full">
                                <label class="label py-1"><span class="label-text">จังหวัดทะเบียนรถ</span></label>
                                <select name="license_province" class="select select-sm select-bordered" required>
                                    <option disabled selected value="">เลือกจังหวัด</option>
                                    <?php foreach ($provinces as $province): ?>
                                        <option value="<?php echo htmlspecialchars($province); ?>"><?php echo htmlspecialchars($province); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div class="form-control w-full">
                                <label class="label py-1"><span class="label-text">รุ่นรถ (ภาษาอังกฤษ)</span></label>
                                <input type="text" name="vehicle_model" placeholder="เช่น COROLLA" class="input input-sm input-bordered w-full" required>
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                            <div class="form-control w-full">
                                <label class="label py-1"><span class="label-text">สีรถ</span></label>
                                <input type="text" name="vehicle_color" placeholder="เช่น ดำ" class="input input-sm input-bordered w-full" required>
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                        </div>
                         <div class="divider divider-start font-semibold m-0">ข้อมูลการครอบครอง</div>
                        <div class="form-control w-full">
                            <label class="label py-1"><span class="label-text">วันสิ้นอายุภาษีรถ</span></label>
                            <div class="grid grid-cols-3 gap-2">
                                <select name="tax_day" class="select select-sm select-bordered" required></select>
                                <select name="tax_month" class="select select-sm select-bordered" required></select>
                                <select name="tax_year" class="select select-sm select-bordered" required></select>
                            </div>
                            <p class="error-message hidden text-error text-xs mt-1"></p>
                        </div>
                         <div class="form-control w-full">
                            <label class="label py-1"><span class="label-text">เป็นรถของใคร?</span></label>
                            <select name="owner_type" class="select select-sm select-bordered" required>
                                <option disabled selected value="">กรุณาเลือก</option>
                                <option value="self">รถชื่อตนเอง</option>
                                <option value="other">รถคนอื่น</option>
                            </select>
                             <p class="error-message hidden text-error text-xs mt-1"></p>
                        </div>
                        <div id="other-owner-details" class="hidden space-y-2 p-3 bg-base-200 rounded-box">
                            <div role="alert" class="alert alert-warning alert-soft text-xs p-2 justify-start text-left">
                                <span><i class="fa-solid fa-triangle-exclamation"></i> <b>คำเตือน:</b> หากยานพาหนะเกิดปัญหาใดๆ ผู้ยื่นคำร้องจะต้องเป็นผู้รับผิดชอบ</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="form-control w-full">
                                    <label class="label py-1"><span class="label-text">ชื่อ-สกุล เจ้าของรถ</span></label>
                                    <input type="text" name="other_owner_name" placeholder="เช่น นายสมชาย ใจดี" class="input input-sm input-bordered w-full">
                                    <p class="error-message hidden text-error text-xs mt-1"></p>
                                </div>
                                <div class="form-control w-full">
                                    <label class="label py-1"><span class="label-text">เกี่ยวข้องเป็น</span></label>
                                    <input type="text" name="other_owner_relation" placeholder="เช่น บิดา, มารดา, เพื่อน" class="input input-sm input-bordered w-full">
                                    <p class="error-message hidden text-error text-xs mt-1"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Right Column: Evidence Uploads -->
                    <div class="space-y-4">
                        <div class="divider divider-start font-semibold m-0">หลักฐานประกอบ</div>
                        <div role="alert" class="alert alert-info alert-soft text-xs p-2 justify-start">
                            <span><i class="fa-solid fa-circle-info"></i> โปรดตรวจสอบความคมชัดของรูปถ่าย (.jpg, .png) และขนาดไฟล์ต้องไม่เกิน 5 MB</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-control w-full">
                                <label class="block font-medium mb-1 text-center text-xs">สำเนาทะเบียนรถ</label>
                                <a href="/public/assets/images/registration.jpg" data-fancybox="evidence-gallery" data-caption="สำเนาทะเบียนรถ">
                                    <div class="flex justify-center bg-base-200 p-2 rounded-box border overflow-hidden h-28">
                                        <img id="reg-copy-preview" src="/public/assets/images/registration.jpg" alt="สำเนาทะเบียนรถ" class="w-full h-full object-contain cursor-pointer">
                                    </div>
                                </a>
                                <input type="file" id="reg_copy_upload" name="reg_copy_upload" class="file-input file-input-sm file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png" required>
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                             <div class="form-control w-full">
                                <label class="block font-medium mb-1 text-center text-xs">ป้ายภาษี (ป้ายวงกลม)</label>
                                <a href="/public/assets/images/tax_sticker.jpg" data-fancybox="evidence-gallery" data-caption="ป้ายภาษี">
                                    <div class="flex justify-center bg-base-200 p-2 rounded-box border overflow-hidden h-28">
                                        <img id="tax-sticker-preview" src="/public/assets/images/tax_sticker.jpg" alt="ป้ายภาษี" class="w-full h-full object-contain cursor-pointer">
                                    </div>
                                </a>
                                <input type="file" id="tax_sticker_upload" name="tax_sticker_upload" class="file-input file-input-sm file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png" required>
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                             <div class="form-control w-full">
                                <label class="block font-medium mb-1 text-center text-xs">รูปถ่ายรถด้านหน้า</label>
                                <a href="/public/assets/images/front_view.png" data-fancybox="evidence-gallery" data-caption="รูปถ่ายรถด้านหน้า">
                                    <div class="flex justify-center bg-base-200 p-2 rounded-box border overflow-hidden h-28">
                                        <img id="front-view-preview" src="/public/assets/images/front_view.png" alt="รูปถ่ายรถด้านหน้า" class="w-full h-full object-contain cursor-pointer">
                                    </div>
                                </a>
                                <input type="file" id="front_view_upload" name="front_view_upload" class="file-input file-input-sm file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png" required>
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                             <div class="form-control w-full">
                                <label class="block font-medium mb-1 text-center text-xs">รูปถ่ายรถด้านหลัง</label>
                                <a href="/public/assets/images/rear_view.png" data-fancybox="evidence-gallery" data-caption="รูปถ่ายรถด้านหลัง">
                                    <div class="flex justify-center bg-base-200 p-2 rounded-box border overflow-hidden h-28">
                                        <img id="rear-view-preview" src="/public/assets/images/rear_view.png" alt="รูปถ่ายรถด้านหลัง" class="w-full h-full object-contain cursor-pointer">
                                    </div>
                                </a>
                                <input type="file" id="rear_view_upload" name="rear_view_upload" class="file-input file-input-sm file-input-bordered w-full mt-2" accept=".jpg, .jpeg, .png" required>
                                <p class="error-message hidden text-error text-xs mt-1"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="divider mt-6"></div>
                <div class="card-actions justify-center">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-save"></i> บันทึกและสร้างคำร้อง
                    </button>
                </div>
            </div>
        </div>
    </form>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

