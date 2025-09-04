<?php
// เริ่ม session
session_start();

// [เพิ่ม] ตรวจสอบข้อความแจ้งเตือนจาก session
$request_status = $_SESSION['request_status'] ?? null;
$request_message = $_SESSION['request_message'] ?? null;
unset($_SESSION['request_status'], $_SESSION['request_message']);

// [แก้ไข] เพิ่มการตรวจสอบข้อความแจ้งเตือน "เข้าสู่ระบบสำเร็จ"
if (isset($_SESSION['login_success_message'])) {
    $request_status = 'success';
    $request_message = $_SESSION['login_success_message'];
    unset($_SESSION['login_success_message']);
}

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือยัง และมี user_id ใน session หรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

// --- เรียกใช้ไฟล์ตั้งค่าฐานข้อมูล ---
require_once '../../../models/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    session_destroy();
    header("Location: ../login/login.php");
    exit;
}
$conn->set_charset("utf8");

// ดึงข้อมูลผู้ใช้จากฐานข้อมูลโดยใช้ user_id จาก session
$user_id = $_SESSION['user_id'];
$user = null;
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows === 1) {
    $user = $result_user->fetch_assoc();
} else {
    session_destroy();
    header("Location: ../login/login.php");
    exit;
}
$stmt_user->close();

$title = $user['title'];
$firstname = $user['firstname'];
$lastname = $user['lastname'];
$user_type_eng = $user['user_type'];
$user_photo_path = !empty($user['photo_profile']) ? "../../../../public/uploads/user_photos/" . htmlspecialchars($user['photo_profile']) : 'https://img2.pic.in.th/pic/c278d705-c4b2-4a6f-aa50-942b88e801d4.png';


// แปลง user_type เป็นภาษาไทย
$user_type_thai = '';
switch ($user_type_eng) {
    case 'army': $user_type_thai = 'ข้าราชการ/ลูกจ้าง/พนักงานราชการ ทบ.'; break;
    case 'external': $user_type_thai = 'บุคคลภายนอก'; break;
}

// --- ดึงข้อมูลสถิติ ---
$stats = ['total' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0];
$sql_stats = "SELECT status, COUNT(*) as count FROM vehicle_requests WHERE user_id = ? GROUP BY status";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("i", $user_id);
$stmt_stats->execute();
$result_stats = $stmt_stats->get_result();
while ($row = $result_stats->fetch_assoc()) {
    if (isset($stats[$row['status']])) {
        $stats[$row['status']] = $row['count'];
    }
    $stats['total'] += $row['count'];
}
$stmt_stats->close();

// --- ดึงข้อมูลยานพาหนะ/คำร้อง ---
$vehicle_requests = [];
$sql_vehicles = "SELECT vr.*, a.firstname as admin_firstname, a.lastname as admin_lastname FROM vehicle_requests vr LEFT JOIN admins a ON vr.approved_by_id = a.id WHERE vr.user_id = ? ORDER BY vr.created_at DESC";
$stmt_vehicles = $conn->prepare($sql_vehicles);
$stmt_vehicles->bind_param("i", $user_id);
$stmt_vehicles->execute();
$result_vehicles = $stmt_vehicles->get_result();
if ($result_vehicles->num_rows > 0) {
    while($row = $result_vehicles->fetch_assoc()) {
        $vehicle_requests[] = $row;
    }
}
$stmt_vehicles->close();

// --- ดึงข้อมูลยี่ห้อรถและสังกัดจากฐานข้อมูล ---
$car_brands = [];
$sql_brands = "SELECT name FROM car_brands ORDER BY display_order ASC, name ASC";
$result_brands = $conn->query($sql_brands);
if ($result_brands->num_rows > 0) {
    while($row = $result_brands->fetch_assoc()) {
        $car_brands[] = $row['name'];
    }
}

$departments = [];
$sql_dept = "SELECT name FROM departments ORDER BY display_order ASC, name ASC";
$result_dept = $conn->query($sql_dept);
if ($result_dept->num_rows > 0) {
    while($row = $result_dept->fetch_assoc()) {
        $departments[] = $row['name'];
    }
}

// --- รายชื่อจังหวัดในประเทศไทย ---
$provinces = [
    'กระบี่', 'กรุงเทพมหานคร', 'กาญจนบุรี', 'กาฬสินธุ์', 'กำแพงเพชร', 'ขอนแก่น', 'จันทบุรี', 'ฉะเชิงเทรา',
    'ชลบุรี', 'ชัยนาท', 'ชัยภูมิ', 'ชุมพร', 'เชียงราย', 'เชียงใหม่', 'ตรัง', 'ตราด', 'ตาก', 'นครนายก',
    'นครปฐม', 'นครพนม', 'นครราชสีมา', 'นครศรีธรรมราช', 'นครสวรรค์', 'นนทบุรี', 'นราธิวาส', 'น่าน',
    'บึงกาฬ', 'บุรีรัมย์', 'ปทุมธานี', 'ประจวบคีรีขันธ์', 'ปราจีนบุรี', 'ปัตตานี', 'พระนครศรีอยุธยา',
    'พะเยา', 'พังงา', 'พัทลุง', 'พิจิตร', 'พิษณุโลก', 'เพชรบุรี', 'เพชรบูรณ์', 'แพร่', 'ภูเก็ต',
    'มหาสารคาม', 'มุกดาหาร', 'แม่ฮ่องสอน', 'ยโสธร', 'ยะลา', 'ร้อยเอ็ด', 'ระนอง', 'ระยอง', 'ราชบุรี',
    'ลพบุรี', 'ลำปาง', 'ลำพูน', 'เลย', 'ศรีสะเกษ', 'สกลนคร', 'สงขลา', 'สตูล', 'สมุทรปราการ',
    'สมุทรสงคราม', 'สมุทรสาคร', 'สระแก้ว', 'สระบุรี', 'สิงห์บุรี', 'สุโขทัย', 'สุพรรณบุรี', 'สุราษฎร์ธานี',
    'สุรินทร์', 'หนองคาย', 'หนองบัวลำภู', 'อ่างทอง', 'อำนาจเจริญ', 'อุดรธานี', 'อุตรดิตถ์', 'อุทัยธานี', 'อุบลราชานี'
];

$conn->close();
?>
<!DOCTYPE html>
<html lang="th" data-theme="light" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>หน้าหลัก - ระบบยื่นคำร้อง</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body { font-family: 'Prompt', sans-serif; }
        .menu a.active, .menu li > a:hover {
            background-color: #e0e7ff; /* indigo-100 */
            color: #4f46e5; /* indigo-600 */
        }
        .error-message { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; }
        .alert-soft { border-width: 1px; }
        .alert-error.alert-soft { background-color: #fee2e2; border-color: #fca5a5; color: #b91c1c; }
        .alert-success.alert-soft { background-color: #dcfce7; border-color: #86efac; color: #166534; }
        .alert-info.alert-soft { background-color: #e0f2fe; border-color: #7dd3fc; color: #0369a1; }
        .alert-warning.alert-soft { background-color: #fef9c3; border-color: #fde047; color: #a16207; }
        .input-disabled, .select-disabled, .textarea-disabled, input[type=text][disabled] {
            background-color: #f3f4f6 !important;
            border-color: #e5e7eb !important;
            cursor: not-allowed;
            color: #374151;
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="drawer">
        <input id="my-drawer-3" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col min-h-screen">
            <!-- Navbar ด้านบน -->
            <div class="w-full navbar bg-base-100 shadow-md z-30">
                <div class="flex-1 px-2 mx-2">
                    <a class="text-base font-bold flex items-center gap-2">
                        <img src="https://img2.pic.in.th/pic/CARPASS-logo11af8574a9cc9906.png" alt="Logo" class="h-16 w-16" onerror="this.onerror=null;this.src='https://placehold.co/64x64/CCCCCC/FFFFFF?text=L';">
                        <div>
                            <span class="whitespace-nowrap text-sm sm:text-base">ระบบยื่นคำร้องขอบัตรผ่านยานพาหนะ</span>
                            <span class="text-xs font-normal text-gray-500 block">เข้า-ออก ค่ายภาณุรังษี</span>
                        </div>
                    </a>
                </div>
                <div class="flex-none hidden lg:flex items-center">
                    <ul class="menu menu-horizontal gap-1" id="desktop-menu">
                        <!-- เมนูสำหรับหน้าจอคอมพิวเตอร์ -->
                        <li><a href="#overview-section" class="active"><i class="fa-solid fa-chart-pie w-4"></i> ภาพรวม</a></li>
                        <li><a href="#add-vehicle-section"><i class="fa-solid fa-file-circle-plus w-4"></i> เพิ่มยานพาหนะ/ยื่นคำร้อง</a></li>
                        <li><a href="#profile-section"><i class="fa-solid fa-user-pen w-4"></i> ข้อมูลส่วนตัว</a></li>
                    </ul>
                    <div class="divider lg:divider-horizontal mx-2"></div>
                    <a href="../../../controllers/user/logout/logout.php" class="btn btn-ghost btn-sm">
                        <i class="fa-solid fa-right-from-bracket w-4"></i>
                        ออกจากระบบ
                    </a>
                </div>
                <div class="flex-none lg:hidden">
                    <label for="my-drawer-3" aria-label="open sidebar" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </label>
                </div>
            </div>

            <!-- Main Content -->
            <main class="flex-grow container mx-auto max-w-4xl p-4">
                <h1 class="text-xl sm:text-2xl font-bold mb-1">ยินดีต้อนรับ, <?php echo htmlspecialchars($title . ' ' . $firstname . ' ' . $lastname); ?></h1>
                <p class="text-xs sm:text-sm text-gray-500">(ประเภทผู้สมัคร: <?php echo htmlspecialchars($user_type_thai); ?>)</p>
                <?php if ($user['user_type'] === 'army' && !empty($user['work_department'])): ?>
                <p class="text-xs sm:text-sm text-gray-500 mb-6">(สังกัด: <?php echo htmlspecialchars($user['work_department']); ?>)</p>
                <?php else: ?>
                <p class="mb-6"></p> <!-- Add margin bottom even if there's no department -->
                <?php endif; ?>

                <!-- ส่วนที่ 1: ภาพรวม -->
                <div id="overview-section" class="main-section">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="card bg-base-100 shadow-lg cursor-pointer hover:shadow-xl transition-shadow duration-200 stat-filter" data-filter="all">
                            <div class="card-body p-3 sm:p-4">
                                <div class="flex items-center">
                                    <div class="p-2 bg-blue-100 rounded-full"><i class="fa-solid fa-file-alt text-md sm:text-lg text-blue-600"></i></div>
                                    <div class="ml-2 sm:ml-3"><p class="text-xs text-gray-500">ทั้งหมด</p><p class="text-lg sm:text-xl font-bold"><?php echo $stats['total']; ?></p></div>
                                </div>
                            </div>
                        </div>
                        <div class="card bg-base-100 shadow-lg cursor-pointer hover:shadow-xl transition-shadow duration-200 stat-filter" data-filter="approved">
                            <div class="card-body p-3 sm:p-4">
                                <div class="flex items-center">
                                    <div class="p-2 bg-green-100 rounded-full"><i class="fa-solid fa-check-circle text-md sm:text-lg text-green-600"></i></div>
                                    <div class="ml-2 sm:ml-3"><p class="text-xs text-gray-500">อนุมัติ</p><p class="text-lg sm:text-xl font-bold"><?php echo $stats['approved']; ?></p></div>
                                </div>
                            </div>
                        </div>
                        <div class="card bg-base-100 shadow-lg cursor-pointer hover:shadow-xl transition-shadow duration-200 stat-filter" data-filter="pending">
                            <div class="card-body p-3 sm:p-4">
                                <div class="flex items-center">
                                    <div class="p-2 bg-yellow-100 rounded-full"><i class="fa-solid fa-clock text-md sm:text-lg text-yellow-600"></i></div>
                                    <div class="ml-2 sm:ml-3"><p class="text-xs text-gray-500">รออนุมัติ</p><p class="text-lg sm:text-xl font-bold"><?php echo $stats['pending']; ?></p></div>
                                </div>
                            </div>
                        </div>
                        <div class="card bg-base-100 shadow-lg cursor-pointer hover:shadow-xl transition-shadow duration-200 stat-filter" data-filter="rejected">
                            <div class="card-body p-3 sm:p-4">
                                <div class="flex items-center">
                                    <div class="p-2 bg-red-100 rounded-full"><i class="fa-solid fa-circle-xmark text-md sm:text-lg text-red-600"></i></div>
                                    <div class="ml-2 sm:ml-3"><p class="text-xs text-gray-500">ไม่ผ่าน</p><p class="text-lg sm:text-xl font-bold"><?php echo $stats['rejected']; ?></p></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-base-100 shadow-lg">
                        <div class="card-body">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <h2 class="card-title text-base sm:text-xl flex items-center gap-2"><i class="fa-solid fa-car-side"></i> ภาพรวมยานพาหนะ/คำร้องของคุณ</h2>
                                <a href="#add-vehicle-section" class="btn btn-primary btn-sm add-vehicle-shortcut-btn">
                                    <i class="fa-solid fa-plus"></i> เพิ่มยานพาหนะ/ยื่นคำร้อง
                                </a>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4" id="vehicle-list-container">
                                <?php if (empty($vehicle_requests)): ?>
                                    <div class="col-span-full text-center p-8 text-gray-500"><i class="fa-solid fa-folder-open fa-3x mb-4"></i><p>ยังไม่พบข้อมูลคำร้อง</p><p class="text-xs mt-1">คลิกเมนู "เพิ่มยานพาหนะ/ยื่นคำร้อง" เพื่อเริ่มใช้งาน</p></div>
                                <?php else: ?>
                                    <div id="no-filter-results" class="col-span-full text-center p-8 text-gray-500 hidden"><i class="fa-solid fa-magnifying-glass fa-3x mb-4"></i><p>ไม่พบข้อมูลตามสถานะที่เลือก</p></div>
                                    <?php foreach ($vehicle_requests as $request): ?>
                                        <?php
                                            $status_text = ''; $status_class = ''; $card_bg_class = '';
                                            switch ($request['status']) {
                                                case 'approved': $status_text = 'อนุมัติแล้ว'; $status_class = 'badge-success'; $card_bg_class = 'bg-green-100 text-green-900'; break;
                                                case 'pending': $status_text = 'รออนุมัติ'; $status_class = 'badge-warning'; $card_bg_class = 'bg-yellow-100 text-yellow-900'; break;
                                                case 'rejected': $status_text = 'ไม่ผ่าน'; $status_class = 'badge-error'; $card_bg_class = 'bg-red-100 text-red-900'; break;
                                            }
                                            $admin_name = ($request['admin_firstname'] && $request['admin_lastname']) ? $request['admin_firstname'] . ' ' . $request['admin_lastname'] : '-';
                                        ?>
                                        <div class="card card-compact shadow-md <?php echo $card_bg_class; ?> cursor-pointer hover:shadow-xl transition-shadow duration-200 vehicle-card"
                                            onclick="openDetailModal(this)"
                                            data-request-id="<?php echo htmlspecialchars($request['id']); ?>" data-type="<?php echo htmlspecialchars($request['vehicle_type']); ?>" data-brand="<?php echo htmlspecialchars($request['brand']); ?>" data-model="<?php echo htmlspecialchars($request['model']); ?>" data-color="<?php echo htmlspecialchars($request['color']); ?>" data-plate="<?php echo htmlspecialchars($request['license_plate']); ?>" data-province="<?php echo htmlspecialchars($request['province']); ?>" data-tax-expiry="<?php echo htmlspecialchars($request['tax_expiry_date']); ?>" data-owner-type="<?php echo htmlspecialchars($request['owner_type']); ?>" data-other-owner-name="<?php echo htmlspecialchars($request['other_owner_name'] ?? '-'); ?>" data-other-owner-relation="<?php echo htmlspecialchars($request['other_owner_relation'] ?? '-'); ?>" data-status-text="<?php echo $status_text; ?>" data-status="<?php echo htmlspecialchars($request['status']); ?>" data-status-class="<?php echo $status_class; ?>" data-card-number="<?php echo htmlspecialchars($request['card_number'] ?? '-'); ?>" data-admin-name="<?php echo htmlspecialchars($admin_name); ?>" data-img-reg="../../../../public/uploads/vehicle/registration/<?php echo htmlspecialchars($request['photo_reg_copy']); ?>" data-img-tax="../../../../public/uploads/vehicle/tax_sticker/<?php echo htmlspecialchars($request['photo_tax_sticker']); ?>" data-img-front="../../../../public/uploads/vehicle/front_view/<?php echo htmlspecialchars($request['photo_front']); ?>" data-img-rear="../../../../public/uploads/vehicle/rear_view/<?php echo htmlspecialchars($request['photo_rear']); ?>"
                                            data-card-pickup-date="<?php echo htmlspecialchars($request['card_pickup_date'] ?? ''); ?>"
                                            data-card-type="<?php echo htmlspecialchars($request['card_type'] ?? ''); ?>"
                                            data-card-expiry-year="<?php echo htmlspecialchars($request['card_expiry_year'] ?? ''); ?>"
                                            data-rejection-reason="<?php echo htmlspecialchars($request['rejection_reason'] ?? ''); ?>"
                                            data-request-key="<?php echo htmlspecialchars($request['request_key']); ?>"
                                            data-search-id="<?php echo htmlspecialchars($request['search_id'] ?? ''); ?>">
                                            <div class="card-body p-3 flex flex-col justify-between">
                                                <div>
                                                    <div class="font-bold text-sm flex items-center gap-2"><?php if ($request['vehicle_type'] == 'รถยนต์'): ?><i class="fa-solid fa-car"></i> รถยนต์<?php else: ?><i class="fa-solid fa-motorcycle"></i> รถจักรยานยนต์<?php endif; ?></div>
                                                    <div class="mt-1"><p class="text-lg font-bold leading-tight"><?php echo htmlspecialchars($request['license_plate']); ?></p><p class="text-xs text-gray-600"><?php echo htmlspecialchars($request['province']); ?></p></div>
                                                </div>
                                                <div class="flex justify-between items-end mt-2">
                                                    <div><div class="text-xs">เลขที่บัตร</div><div class="font-semibold text-xs"><?php echo htmlspecialchars($request['card_number'] ?? '-'); ?></div></div>
                                                    <div class="badge <?php echo $status_class; ?> text-white font-semibold"><?php echo $status_text; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Add Vehicle Form -->
                <div id="add-vehicle-section" class="main-section hidden">
                    <form action="../../../controllers/user/vehicle/add_vehicle_process.php" method="POST" enctype="multipart/form-data" id="addVehicleForm" novalidate>
                        <div class="card bg-base-100 shadow-lg">
                            <div class="card-body">
                                <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
                                     <h2 class="card-title text-xl flex items-center gap-2"><i class="fa-solid fa-file-circle-plus"></i> เพิ่มยานพาหนะ/ยื่นคำร้อง</h2>
                                     <a href="#overview-section" class="btn btn-sm btn-ghost overview-shortcut-btn"><i class="fa-solid fa-arrow-left"></i> กลับไปหน้าภาพรวม</a>
                                </div>
                                <div class="divider divider-start font-semibold">ข้อมูลยานพาหนะ</div>
                                <div role="alert" class="alert alert-error alert-soft mb-4">
                                    <div class="flex items-center justify-start text-left">
                                        <i class="fa-solid fa-ban text-lg mr-2"></i>
                                        <span class="text-xs">
                                            <b class="font-bold">ไม่รับพิจารณารถป้ายแดง</b> (โปรดรอจนได้รับป้ายทะเบียนขาว)
                                        </span>
                                    </div>
                                </div>
                                <div role="alert" class="alert alert-info alert-soft mb-4">
                                    <div class="text-left">
                                        <ul class="list-disc list-inside text-xs">
                                            <li>โปรดตรวจสอบข้อมูลและเอกสารทั้งหมดให้ถูกต้องเพื่อความรวดเร็วในการอนุมัติ</li>
                                        </ul>
                                    </div>
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
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
                                    <div class="border border-base-300 rounded-lg p-4"><label class="block font-medium mb-2 text-center">สำเนาทะเบียนรถ</label><div class="form-control w-full mx-auto"><div class="flex justify-center bg-base-200 p-2 rounded-lg border"><img id="reg-copy-preview" src="https://img5.pic.in.th/file/secure-sv1/registration.jpg" alt="ตัวอย่างสำเนาทะเบียนรถ" class="w-full max-h-48 rounded-lg object-contain" onerror="this.onerror=null;this.src='https://placehold.co/400x248/CCCCCC/FFFFFF?text=Example';"></div><input type="file" name="reg_copy_upload" id="reg-copy-upload" class="file-input file-input-bordered file-input-sm w-full mt-2" accept=".jpg, .jpeg, .png" required><p class="error-message hidden"></p></div></div>
                                    <div class="border border-base-300 rounded-lg p-4"><label class="block font-medium mb-2 text-center">ป้ายภาษีรถยนต์ (ป้ายวงกลม)</label><div class="form-control w-full mx-auto"><div class="flex justify-center bg-base-200 p-2 rounded-lg border"><img id="tax-sticker-preview" src="https://img2.pic.in.th/pic/tax_sticker.jpg" alt="ตัวอย่างป้ายภาษี" class="w-full max-h-48 rounded-lg object-contain" onerror="this.onerror=null;this.src='https://placehold.co/400x248/CCCCCC/FFFFFF?text=Example';"></div><input type="file" name="tax_sticker_upload" id="tax-sticker-upload" class="file-input file-input-bordered file-input-sm w-full mt-2" accept=".jpg, .jpeg, .png" required><p class="error-message hidden"></p></div></div>
                                    <div class="border border-base-300 rounded-lg p-4"><label class="block font-medium mb-2 text-center">รูปถ่ายรถด้านหน้า</label><div class="form-control w-full mx-auto"><div class="flex justify-center bg-base-200 p-2 rounded-lg border"><img id="front-view-preview" src="https://img2.pic.in.th/pic/front_view.png" alt="ตัวอย่างรูปถ่ายรถด้านหน้า" class="w-full max-h-48 rounded-lg object-contain" onerror="this.onerror=null;this.src='https://placehold.co/400x248/CCCCCC/FFFFFF?text=Example';"></div><input type="file" name="front_view_upload" id="front-view-upload" class="file-input file-input-bordered file-input-sm w-full mt-2" accept=".jpg, .jpeg, .png" required><p class="error-message hidden"></p></div></div>
                                    <div class="border border-base-300 rounded-lg p-4"><label class="block font-medium mb-2 text-center">รูปถ่ายรถด้านหลัง</label><div class="form-control w-full mx-auto"><div class="flex justify-center bg-base-200 p-2 rounded-lg border"><img id="rear-view-preview" src="https://img5.pic.in.th/file/secure-sv1/rear_view.png" alt="ตัวอย่างรูปถ่ายรถด้านหลัง" class="w-full max-h-48 rounded-lg object-contain" onerror="this.onerror=null;this.src='https://placehold.co/400x248/CCCCCC/FFFFFF?text=Example';"></div><input type="file" name="rear_view_upload" id="rear-view-upload" class="file-input file-input-bordered file-input-sm w-full mt-2" accept=".jpg, .jpeg, .png" required><p class="error-message hidden"></p></div></div>
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
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- [ปรับปรุง] ส่วนที่ 3: ข้อมูลส่วนตัว -->
                <div id="profile-section" class="main-section hidden">
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
                                        <div class="border border-base-300 rounded-lg p-4 w-full h-full flex flex-col form-control">
                                            <label class="block font-medium mb-2 text-center">รูปถ่ายหน้าตรง</label>
                                            <div class="flex justify-center bg-base-200 p-2 rounded-lg border">
                                                <img id="profile-photo-preview" src="<?php echo $user_photo_path; ?>" alt="รูปโปรไฟล์" class="w-full max-h-48 rounded-lg object-contain" onerror="this.onerror=null;this.src='https://placehold.co/192x192/CCCCCC/FFFFFF?text=Profile';">
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
                                                $user_dob_year = $dob_parts[0] + 543;
                                                $user_dob_month = (int)$dob_parts[1];
                                                $user_dob_day = (int)$dob_parts[2];
                                            ?>
                                            <div class="form-control w-full sm:col-span-2">
                                                <div class="grid grid-cols-3 gap-2">
                                                    <div class="form-control w-full">
                                                        <div class="label"><span class="label-text">คำนำหน้า</span></div>
                                                        <select id="profile-title" name="title" class="select select-sm select-bordered w-full input-disabled" disabled required>
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
                                                        <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" class="input input-sm input-bordered w-full input-disabled" disabled required />
                                                        <p class="error-message hidden"></p>
                                                    </div>
                                                    <div class="form-control w-full">
                                                        <div class="label"><span class="label-text">นามสกุล</span></div>
                                                        <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" class="input input-sm input-bordered w-full input-disabled" disabled required />
                                                        <p class="error-message hidden"></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-control w-full sm:col-span-2">
                                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                                                    <div class="form-control w-full sm:col-span-9">
                                                        <div class="label"><span class="label-text">วันเดือนปีเกิด</span></div>
                                                        <div class="grid grid-cols-3 gap-2">
                                                            <select id="profile-dob-day" name="dob_day" class="select select-sm select-bordered input-disabled" disabled required></select>
                                                            <select id="profile-dob-month" name="dob_month" class="select select-sm select-bordered input-disabled" disabled required></select>
                                                            <select id="profile-dob-year" name="dob_year" class="select select-sm select-bordered input-disabled" disabled required></select>
                                                        </div>
                                                        <p class="error-message hidden"></p>
                                                    </div>
                                                     <div class="form-control w-full sm:col-span-3">
                                                        <div class="label"><span class="label-text">เพศ</span></div>
                                                        <select name="gender" class="select select-sm select-bordered w-full input-disabled" disabled required>
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
                                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone_number']); ?>" class="input input-sm input-bordered w-full input-disabled" disabled required maxlength="12" />
                                                <p class="error-message hidden"></p>
                                            </div>
                                            <div class="form-control w-full">
                                                <div class="label"><span class="label-text">เลขบัตรประชาชน</span></div>
                                                <input type="text" id="profile-national-id" name="national_id_display" value="<?php echo htmlspecialchars($user['national_id']); ?>" class="input input-sm input-bordered w-full input-disabled" disabled maxlength="17"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="divider divider-start font-semibold mt-6">ที่อยู่ปัจจุบัน</div>
                                 <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="form-control w-full sm:col-span-2 md:col-span-4"><div class="label"><span class="label-text">บ้านเลขที่/ที่อยู่</span></div><input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" class="input input-sm input-bordered w-full input-disabled" disabled required /><p class="error-message hidden"></p></div>
                                    <div class="form-control w-full"><div class="label"><span class="label-text">ตำบล/แขวง</span></div><input type="text" id="profile-subdistrict" name="subdistrict" value="<?php echo htmlspecialchars($user['subdistrict']); ?>" class="input input-sm input-bordered w-full input-disabled" disabled required /><p class="error-message hidden"></p></div>
                                    <div class="form-control w-full"><div class="label"><span class="label-text">อำเภอ/เขต</span></div><input type="text" id="profile-district" name="district" value="<?php echo htmlspecialchars($user['district']); ?>" class="input input-sm input-bordered w-full input-disabled" disabled required /><p class="error-message hidden"></p></div>
                                    <div class="form-control w-full"><div class="label"><span class="label-text">จังหวัด</span></div><input type="text" id="profile-province" name="province" value="<?php echo htmlspecialchars($user['province']); ?>" class="input input-sm input-bordered w-full input-disabled" disabled required /><p class="error-message hidden"></p></div>
                                    <div class="form-control w-full"><div class="label"><span class="label-text">รหัสไปรษณีย์</span></div><input type="text" id="profile-zipcode" name="zipcode" value="<?php echo htmlspecialchars($user['zipcode']); ?>" class="input input-sm input-bordered w-full input-disabled" disabled required /><p class="error-message hidden"></p></div>
                                </div>
                                <?php if ($user['user_type'] === 'army'): ?>
                                <div id="profile-work-info" class="mt-6">
                                    <div class="divider divider-start font-semibold">ข้อมูลการทำงาน</div>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <?php $is_other_dept = !in_array($user['work_department'], $departments); ?>
                                        <div class="form-control w-full">
                                            <div class="label"><span class="label-text">สังกัด</span></div>
                                            <select name="work_department" class="select select-sm select-bordered w-full input-disabled" disabled required>
                                                <option disabled selected value="">เลือกสังกัด</option>
                                                <?php foreach ($departments as $dept): ?>
                                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($user['work_department'] == $dept) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept); ?></option>
                                                <?php endforeach; ?>
                                                <option value="other" <?php echo $is_other_dept ? 'selected' : ''; ?>>อื่นๆ</option>
                                            </select>
                                            <input type="text" name="work_department_other" placeholder="ระบุสังกัด" class="input input-sm input-bordered w-full mt-2 <?php echo !$is_other_dept ? 'hidden' : ''; ?>" value="<?php echo $is_other_dept ? htmlspecialchars($user['work_department']) : ''; ?>" disabled/>
                                            <p class="error-message hidden"></p>
                                        </div>
                                        <div class="form-control w-full"><div class="label"><span class="label-text">ตำแหน่ง</span></div><input type="text" name="position" value="<?php echo htmlspecialchars($user['position']); ?>" class="input input-sm input-bordered w-full input-disabled" disabled required /><p class="error-message hidden"></p></div>
                                        <div class="form-control w-full"><div class="label"><span class="label-text">เลขบัตรข้าราชการ</span></div><input type="tel" name="official_id" value="<?php echo htmlspecialchars($user['official_id']); ?>" class="input input-sm input-bordered w-full input-disabled" disabled maxlength="10" /><p class="error-message hidden"></p></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>

            </main>
            <footer class="text-center text-base-content/70 p-4"><p class="text-xs">Developed by กยข.กช.</p><p class="text-xs">ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ)</p></footer>
        </div>
        <div class="drawer-side z-50">
            <label for="my-drawer-3" aria-label="close sidebar" class="drawer-overlay"></label>
            <ul class="menu p-4 w-64 min-h-full bg-base-100" id="mobile-menu">
                <li class="mb-4"><a class="text-lg font-bold flex items-center gap-2"><img src="https://img2.pic.in.th/pic/CARPASS-logo11af8574a9cc9906.png" alt="Logo" class="h-8 w-8" onerror="this.onerror=null;this.src='https://placehold.co/32x32/CCCCCC/FFFFFF?text=L';"> ระบบยื่นคำร้อง</a></li>
                <li><a href="#overview-section" class="active"><i class="fa-solid fa-chart-pie w-4"></i> ภาพรวม</a></li>
                <li><a href="#add-vehicle-section"><i class="fa-solid fa-file-circle-plus w-4"></i> เพิ่มยานพาหนะ/ยื่นคำร้อง</a></li>
                <li><a href="#profile-section"><i class="fa-solid fa-user-pen w-4"></i> ข้อมูลส่วนตัว</a></li>
                <div class="divider"></div>
                <li><a href="../../../controllers/user/logout/logout.php"><i class="fa-solid fa-right-from-bracket w-4"></i> ออกจากระบบ</a></li>
            </ul>
        </div>
    </div>

    <!-- Modals -->
    <dialog id="exampleImageModal" class="modal"><div class="modal-box"><img id="example-image" src="" alt="ตัวอย่าง" class="w-full h-auto rounded-lg"><div class="modal-action"><form method="dialog"><button class="btn btn-sm">ปิด</button></form></div></div><form method="dialog" class="modal-backdrop"><button>close</button></form></dialog>
    <dialog id="vehicleDetailModal" class="modal"><div class="modal-box max-w-3xl"><div class="flex justify-between items-center"><h3 class="font-bold text-lg" id="modal-title">รายละเอียดคำร้อง</h3><form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form></div><div id="modal-content" class="py-4"></div><div class="modal-action" id="modal-action-buttons"></div></div><form method="dialog" class="modal-backdrop"><button>close</button></form></dialog>
    <dialog id="editVehicleModal" class="modal"><div class="modal-box max-w-4xl"><form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form><h3 class="font-bold text-lg">แก้ไขข้อมูลคำร้อง</h3><div class="py-4"><form action="../../../controllers/user/vehicle/edit_vehicle_process.php" method="POST" enctype="multipart/form-data" id="editVehicleForm" novalidate><input type="hidden" name="request_id" id="edit-request-id"><div class="divider divider-start font-semibold">ข้อมูลยานพาหนะ</div><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"><div class="form-control w-full"><div class="label"><span class="label-text">ประเภทรถ</span></div><select name="vehicle_type" id="edit-vehicle-type" class="select select-bordered select-sm" required><option value="รถยนต์">รถยนต์</option><option value="รถจักรยานยนต์">รถจักรยานยนต์</option></select><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">ยี่ห้อรถ</span></div><select name="vehicle_brand" id="edit-vehicle-brand" class="select select-bordered select-sm" required><?php foreach ($car_brands as $brand): ?><option value="<?php echo htmlspecialchars($brand); ?>"><?php echo htmlspecialchars($brand); ?></option><?php endforeach; ?></select><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">รุ่นรถ</span></div><input type="text" name="vehicle_model" id="edit-vehicle-model" class="input input-bordered input-sm w-full" required /><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">สีรถ</span></div><input type="text" name="vehicle_color" id="edit-vehicle-color" class="input input-bordered input-sm w-full" required /><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">เลขทะเบียนรถ</span></div><input type="text" name="license_plate" id="edit-license-plate" class="input input-bordered input-sm w-full" required /><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">จังหวัด</span></div><select name="license_province" id="edit-license-province" class="select select-bordered select-sm" required><?php foreach ($provinces as $province): ?><option value="<?php echo htmlspecialchars($province); ?>"><?php echo htmlspecialchars($province); ?></option><?php endforeach; ?></select><p class="error-message hidden"></p></div><div class="form-control w-full lg:col-span-2"><div class="label"><span class="label-text">วันสิ้นอายุภาษี</span></div><div class="grid grid-cols-3 gap-2"><select name="tax_day" id="edit-tax-day" class="select select-bordered select-sm" required></select><select name="tax_month" id="edit-tax-month" class="select select-bordered select-sm" required></select><select name="tax_year" id="edit-tax-year" class="select select-bordered select-sm" required></select></div><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">เป็นรถของใคร?</span></div><select name="owner_type" id="edit-owner-type" class="select select-bordered select-sm" required><option value="self">รถชื่อตนเอง</option><option value="other">รถคนอื่น</option></select><p class="error-message hidden"></p></div></div><div id="edit-other-owner-details" class="hidden mt-4"><div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border rounded-lg bg-base-200"><div class="form-control w-full"><div class="label"><span class="label-text">ชื่อ-สกุล เจ้าของ</span></div><input type="text" name="other_owner_name" id="edit-other-owner-name" class="input input-bordered input-sm w-full" /><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">เกี่ยวข้องเป็น</span></div><input type="text" name="other_owner_relation" id="edit-other-owner-relation" class="input input-bordered input-sm w-full" /><p class="error-message hidden"></p></div></div></div><div class="divider divider-start font-semibold mt-8">หลักฐานรูปถ่าย (อัปโหลดใหม่เฉพาะที่ต้องการเปลี่ยน)</div><div class="grid grid-cols-1 lg:grid-cols-2 gap-6"><div class="form-control"><label class="block font-medium mb-2">สำเนาทะเบียนรถ</label><img id="edit-reg-copy-preview" src="" class="w-full h-40 object-contain rounded-lg border p-2 mb-2"><input type="file" name="reg_copy_upload" id="edit-reg-copy-upload" class="file-input file-input-bordered file-input-sm" accept=".jpg, .jpeg, .png"><p class="error-message hidden"></p></div><div class="form-control"><label class="block font-medium mb-2">ป้ายภาษี</label><img id="edit-tax-sticker-preview" src="" class="w-full h-40 object-contain rounded-lg border p-2 mb-2"><input type="file" name="tax_sticker_upload" id="edit-tax-sticker-upload" class="file-input file-input-bordered file-input-sm" accept=".jpg, .jpeg, .png"><p class="error-message hidden"></p></div><div class="form-control"><label class="block font-medium mb-2">รูปถ่ายรถด้านหน้า</label><img id="edit-front-view-preview" src="" class="w-full h-40 object-contain rounded-lg border p-2 mb-2"><input type="file" name="front_view_upload" id="edit-front-view-upload" class="file-input file-input-bordered file-input-sm" accept=".jpg, .jpeg, .png"><p class="error-message hidden"></p></div><div class="form-control"><label class="block font-medium mb-2">รูปถ่ายรถด้านหลัง</label><img id="edit-rear-view-preview" src="" class="w-full h-40 object-contain rounded-lg border p-2 mb-2"><input type="file" name="rear_view_upload" id="edit-rear-view-upload" class="file-input file-input-bordered file-input-sm" accept=".jpg, .jpeg, .png"><p class="error-message hidden"></p></div></div><div class="modal-action mt-6"><button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('editVehicleModal').close()">ยกเลิก</button><button type="submit" class="btn btn-success btn-sm">ยืนยันการแก้ไข</button></div></form></div></div></dialog>
    <dialog id="resetConfirmModal" class="modal"><div class="modal-box"><h3 class="font-bold text-lg">ยืนยันการล้างข้อมูล</h3><p class="py-4">คุณแน่ใจหรือไม่ว่าต้องการล้างข้อมูลในฟอร์มทั้งหมด?</p><div class="modal-action"><button class="btn btn-sm" onclick="document.getElementById('resetConfirmModal').close()">ยกเลิก</button><button id="confirm-reset-btn" class="btn btn-error btn-sm">ยืนยัน</button></div></div><form method="dialog" class="modal-backdrop"><button>close</button></form></dialog>
    <dialog id="loadingModal" class="modal modal-middle"><div class="modal-box text-center"><span class="loading loading-spinner loading-lg text-primary"></span><h3 class="font-bold text-lg mt-4">กรุณารอสักครู่</h3><p class="py-4">ระบบกำลังบันทึกข้อมูล...<br>กรุณาอย่าปิดหรือรีเฟรชหน้านี้</p></div></dialog>
    <dialog id="duplicateVehicleModal" class="modal"><div class="modal-box"><div class="alert alert-warning alert-soft"><i class="fa-solid fa-triangle-exclamation text-2xl"></i><div><h3 class="font-bold text-lg">ข้อมูลซ้ำซ้อน</h3><p class="py-2 text-sm" id="duplicateVehicleMessage"></p></div></div><div class="modal-action justify-center"><form method="dialog"><button class="btn btn-warning btn-outline btn-sm">รับทราบ</button></form></div></div><form method="dialog" class="modal-backdrop"><button>close</button></form></dialog>
    <dialog id="addVehicleConfirmModal" class="modal modal-middle">
        <div class="modal-box w-11/12 max-w-3xl">
            <h3 class="font-bold text-lg">โปรดตรวจสอบข้อมูลยานพาหนะ</h3>
            <div id="add-vehicle-summary-content" class="py-4 space-y-4 text-sm"></div>
            <div class="modal-action">
              <form method="dialog">
                <button class="btn btn-sm">แก้ไข</button>
              </form>
              <button id="final-add-vehicle-submit-btn" class="btn btn-sm btn-success">ยืนยันและส่งข้อมูล</button>
            </div>
        </div>
    </dialog>

    <div id="alert-container" class="toast toast-top toast-center z-50"></div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator/qrcode.js"></script>
    <script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/JQL.min.js"></script>
    <script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/typeahead.bundle.js"></script>
    <script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.js"></script>
    <script>
        // Store user data from PHP for JS
        const userDob = {
            day: <?php echo $user_dob_day; ?>,
            month: <?php echo $user_dob_month; ?>,
            year: <?php echo $user_dob_year; ?>
        };
        const currentUserId = <?php echo json_encode($user_id); ?>;

        document.addEventListener('DOMContentLoaded', function () {
            
            const alertContainer = document.getElementById('alert-container');

            function showAlert(message, type = 'info') {
                const alertId = `alert-${Date.now()}`;
                const alertElement = document.createElement('div');
                alertElement.id = alertId;
                
                let icon = ''; let alertClass = '';
                if (type === 'error') { icon = '<i class="fa-solid fa-circle-xmark"></i>'; alertClass = 'alert-error'; } 
                else if (type === 'success') { icon = '<i class="fa-solid fa-circle-check"></i>'; alertClass = 'alert-success'; }
                else if (type === 'info') { icon = '<i class="fa-solid fa-circle-info"></i>'; alertClass = 'alert-info'; }
                
                alertElement.className = `alert ${alertClass} alert-soft shadow-lg`;
                alertElement.innerHTML = `<div class="flex items-center">${icon}<span class="ml-2 text-xs sm:text-sm whitespace-nowrap">${message}</span></div>`;
                alertContainer.appendChild(alertElement);
                setTimeout(() => {
                    const existingAlert = document.getElementById(alertId);
                    if (existingAlert) {
                        existingAlert.style.transition = 'opacity 0.3s ease';
                        existingAlert.style.opacity = '0';
                        setTimeout(() => existingAlert.remove(), 300);
                    }
                }, 3000);
            }

            const requestStatus = "<?php echo $request_status; ?>";
            const requestMessage = "<?php echo $request_message; ?>";
            if (requestStatus && requestMessage) {
                showAlert(requestMessage, requestStatus);
            }

            function formatInput(input, pattern) {
                const numbers = input.value.replace(/\D/g, '');
                let result = '';
                let patternIndex = 0;
                let numbersIndex = 0;
                while (patternIndex < pattern.length && numbersIndex < numbers.length) {
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

            // --- Main Dashboard Logic ---
            function formatDateToThai(dateString) { if (!dateString || dateString.split('-').length < 3) return '-'; const months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."]; const date = new Date(dateString); return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear() + 543}`; }
            window.openDetailModal = function(cardElement) {
                const modal = document.getElementById('vehicleDetailModal');
                const modalTitle = modal.querySelector('#modal-title');
                const modalContent = modal.querySelector('#modal-content');
                const modalActionButtons = modal.querySelector('#modal-action-buttons');

                const requestId = cardElement.dataset.requestId;
                if (requestId) {
                    fetch('../../../controllers/user/activity/log_view_action.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ request_id: requestId }) }).catch(error => console.error('Error logging view action:', error));
                }
                const data = cardElement.dataset;
                modalTitle.innerHTML = `<div class="flex flex-col items-start sm:flex-row sm:justify-between sm:items-center w-full gap-2"><span>รายละเอียดคำร้อง: ${data.plate}</span><span class="badge ${data.statusClass} text-white font-semibold">${data.statusText}</span></div>`;

                const isApproved = data.status === 'approved';

                const translateCardType = (type) => {
                    if (!isApproved || !type) return '-';
                    if (type === 'internal') return 'ภายใน';
                    if (type === 'external') return 'ภายนอก';
                    return '-';
                };

                let rejectionReasonHTML = '';
                if (data.status === 'rejected' && data.rejectionReason) {
                    rejectionReasonHTML = `
                        <div role="alert" class="alert alert-error alert-soft">
                            <div class="flex items-start">
                                <i class="fa-solid fa-circle-xmark text-lg mr-2 mt-1"></i>
                                <div>
                                    <h3 class="font-bold">เหตุผลที่ไม่ผ่านการอนุมัติ</h3>
                                    <div class="text-xs">${data.rejectionReason}</div>
                                </div>
                            </div>
                        </div>`;
                }
                
                let qrCodeHTML = '';
                if (isApproved && data.requestKey) {
                    const verifyUrl = `${window.location.origin}/public/app/verify.php?key=${data.requestKey}`;
                    const qr = qrcode(0, 'L');
                    qr.addData(verifyUrl);
                    qr.make();
                    const qrCodeImage = qr.createDataURL(4);

                    qrCodeHTML = `
                        <div>
                            <h3 class="card-title text-base mb-2"><i class="fa-solid fa-qrcode mr-2"></i> QR Code ข้อมูลบัตรผ่าน</h3>
                            <div class="card bg-base-100 shadow-inner">
                                <div class="card-body p-4 items-center text-center">
                                    <img src="${qrCodeImage}" alt="QR Code" class="w-32 h-32 rounded-lg border">
                                </div>
                            </div>
                        </div>`;
                }


                const ownerDetailsHTML = data.ownerType === 'other' ? `<div><div class="text-xs text-base-content/70">ชื่อ-สกุล เจ้าของ</div><div class="font-semibold">${data.otherOwnerName}</div></div><div><div class="text-xs text-base-content/70">เกี่ยวข้องเป็น</div><div class="font-semibold">${data.otherOwnerRelation}</div></div>` : '';
                
                modalContent.innerHTML = `
                    <div class="space-y-4">
                        ${rejectionReasonHTML}
                        
                        <div class="card bg-base-200 shadow-inner">
                            <div class="card-body p-4">
                                <h3 class="card-title text-base flex items-center gap-2"><i class="fa-solid fa-id-card"></i> ข้อมูลบัตรผ่าน</h3>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-2 text-sm mt-2">
                                    <div>
                                        <div class="text-xs text-base-content/70">รหัสคำร้อง</div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold font-mono">${data.searchId || '-'}</span>
                                            ${data.searchId ? `<button id="copy-search-id-btn" class="btn btn-xs btn-ghost p-1" title="คัดลอกรหัสคำร้อง"><i class="fa-solid fa-copy"></i></button>` : ''}
                                        </div>
                                    </div>
                                    <div><div class="text-xs text-base-content/70">ประเภทบัตร</div><div class="font-semibold">${translateCardType(data.cardType)}</div></div>
                                    <div><div class="text-xs text-base-content/70">เลขที่บัตร</div><div class="font-semibold">${data.cardNumber}</div></div>
                                    <div><div class="text-xs text-base-content/70">ผู้อนุมัติ</div><div class="font-semibold">${data.adminName}</div></div>
                                    <div><div class="text-xs text-base-content/70">วันที่คาดว่าจะได้รับบัตร</div><div class="font-semibold">${formatDateToThai(data.cardPickupDate)}</div></div>
                                    <div><div class="text-xs text-base-content/70">หมดอายุสิ้นปี (พ.ศ.)</div><div class="font-semibold">${isApproved ? (data.cardExpiryYear || '-') : '-'}</div></div>
                                </div>
                            </div>
                        </div>

                        ${qrCodeHTML}

                        <div class="card bg-base-200 shadow-inner">
                            <div class="card-body p-4">
                                <h3 class="card-title text-base flex items-center gap-2"><i class="fa-solid fa-car-side"></i> ข้อมูลยานพาหนะ</h3>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-2 text-sm mt-2">
                                    <div><div class="text-xs text-base-content/70">เลขทะเบียน</div><div class="font-semibold">${data.plate} ${data.province}</div></div>
                                    <div><div class="text-xs text-base-content/70">ประเภท</div><div class="font-semibold">${data.type}</div></div>
                                    <div><div class="text-xs text-base-content/70">ยี่ห้อ</div><div class="font-semibold">${data.brand}</div></div>
                                    <div><div class="text-xs text-base-content/70">รุ่น</div><div class="font-semibold">${data.model}</div></div>
                                    <div><div class="text-xs text-base-content/70">สี</div><div class="font-semibold">${data.color}</div></div>
                                    <div><div class="text-xs text-base-content/70">วันสิ้นอายุภาษี</div><div class="font-semibold">${formatDateToThai(data.taxExpiry)}</div></div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-base-200 shadow-inner">
                            <div class="card-body p-4">
                                <h3 class="card-title text-base flex items-center gap-2"><i class="fa-solid fa-address-card"></i> ข้อมูลเจ้าของ</h3>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm mt-2">
                                    <div><div class="text-xs text-base-content/70">ความเป็นเจ้าของ</div><div class="font-semibold">${data.ownerType === 'self' ? 'รถชื่อตนเอง' : 'รถคนอื่น'}</div></div>
                                    ${ownerDetailsHTML}
                                </div>
                            </div>
                        </div>

                        <div class="card bg-base-200 shadow-inner">
                            <div class="card-body p-4">
                                <h3 class="card-title text-base flex items-center gap-2"><i class="fa-solid fa-images"></i> รูปภาพหลักฐาน</h3>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs mt-2">
                                    <div class="text-center"><p class="font-semibold mb-1">ทะเบียนรถ</p><img src="${data.imgReg}" class="w-full h-24 object-cover rounded-md border cursor-pointer hover:scale-105 transition-transform" onclick="openExampleModal('${data.imgReg}')"></div>
                                    <div class="text-center"><p class="font-semibold mb-1">ป้ายภาษี</p><img src="${data.imgTax}" class="w-full h-24 object-cover rounded-md border cursor-pointer hover:scale-105 transition-transform" onclick="openExampleModal('${data.imgTax}')"></div>
                                    <div class="text-center"><p class="font-semibold mb-1">ด้านหน้า</p><img src="${data.imgFront}" class="w-full h-24 object-cover rounded-md border cursor-pointer hover:scale-105 transition-transform" onclick="openExampleModal('${data.imgFront}')"></div>
                                    <div class="text-center"><p class="font-semibold mb-1">ด้านหลัง</p><img src="${data.imgRear}" class="w-full h-24 object-cover rounded-md border cursor-pointer hover:scale-105 transition-transform" onclick="openExampleModal('${data.imgRear}')"></div>
                                </div>
                            </div>
                        </div>
                    </div>`;

                const copyBtn = modalContent.querySelector('#copy-search-id-btn');
                if (copyBtn) {
                    copyBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const textToCopy = data.searchId;
                        const tempTextarea = document.createElement('textarea');
                        tempTextarea.value = textToCopy;
                        document.body.appendChild(tempTextarea);
                        tempTextarea.select();
                        try {
                            document.execCommand('copy');
                            showAlert('คัดลอกรหัสคำร้องแล้ว!', 'success');
                        } catch (err) {
                            console.error('Failed to copy text: ', err);
                            showAlert('ไม่สามารถคัดลอกได้', 'error');
                        }
                        document.body.removeChild(tempTextarea);
                    });
                }

                let actionButtonsHTML = '<form method="dialog"><button class="btn btn-ghost btn-sm">ปิด</button></form>';
                if (data.status === 'pending' || data.status === 'rejected') {
                    const dataString = JSON.stringify(data).replace(/"/g, '&quot;');
                    actionButtonsHTML = `<button class="btn btn-warning btn-sm" onclick='openEditModal(${dataString})'>แก้ไขคำร้อง</button>` + actionButtonsHTML;
                }
                modalActionButtons.innerHTML = actionButtonsHTML;
                modal.showModal();
            }
            window.openExampleModal = function(imageUrl) { const modal = document.getElementById('exampleImageModal'); document.getElementById('example-image').src = imageUrl; modal.showModal(); }
            window.openEditModal = function(data) {
                document.getElementById('vehicleDetailModal').close(); 
                const modal = document.getElementById('editVehicleModal');
                document.getElementById('edit-request-id').value = data.requestId;
                document.getElementById('edit-vehicle-type').value = data.type;
                document.getElementById('edit-vehicle-brand').value = data.brand;
                document.getElementById('edit-vehicle-model').value = data.model;
                document.getElementById('edit-vehicle-color').value = data.color;
                document.getElementById('edit-license-plate').value = data.plate;
                document.getElementById('edit-license-province').value = data.province;
                document.getElementById('edit-owner-type').value = data.ownerType;
                const taxDate = new Date(data.taxExpiry);
                document.getElementById('edit-tax-day').value = taxDate.getDate();
                document.getElementById('edit-tax-month').value = taxDate.getMonth() + 1;
                document.getElementById('edit-tax-year').value = taxDate.getFullYear() + 543;
                const otherOwnerSection = document.getElementById('edit-other-owner-details');
                if (data.ownerType === 'other') { otherOwnerSection.classList.remove('hidden'); document.getElementById('edit-other-owner-name').value = data.otherOwnerName; document.getElementById('edit-other-owner-relation').value = data.otherOwnerRelation; } else { otherOwnerSection.classList.add('hidden'); }
                document.getElementById('edit-reg-copy-preview').src = data.imgReg; document.getElementById('edit-tax-sticker-preview').src = data.imgTax; document.getElementById('edit-front-view-preview').src = data.imgFront; document.getElementById('edit-rear-view-preview').src = data.imgRear;
                modal.showModal();
            }
            
            // --- Navigation & Filtering Logic ---
            const statFilters = document.querySelectorAll('.stat-filter'); const vehicleCards = document.querySelectorAll('.vehicle-card'); const menuLinks = document.querySelectorAll('#desktop-menu a, #mobile-menu a'); const drawerCheckbox = document.getElementById('my-drawer-3'); const mainSections = document.querySelectorAll('.main-section'); const noFilterResults = document.getElementById('no-filter-results');
            function setActiveMenu(targetId) { menuLinks.forEach(link => { link.classList.remove('active'); if (link.getAttribute('href') === `#${targetId}`) { link.classList.add('active'); } }); }
            
            function showMainSection(targetId, updateHistory = true) { 
                mainSections.forEach(section => { if (section.id === targetId) { section.classList.remove('hidden'); } else { section.classList.add('hidden'); } }); 
                setActiveMenu(targetId); 
                if (drawerCheckbox.checked) { drawerCheckbox.checked = false; } 
                if(updateHistory && window.location.hash !== `#${targetId}`){ 
                    window.history.pushState(null, null, `#${targetId}`); 
                } 
            }

            menuLinks.forEach(link => { link.addEventListener('click', function(e) { const href = this.getAttribute('href'); if (href.startsWith('#') && document.getElementById(href.substring(1))?.classList.contains('main-section')) { e.preventDefault(); showMainSection(href.substring(1)); } }); });
            
            document.querySelectorAll('.add-vehicle-shortcut-btn, .overview-shortcut-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    showMainSection(targetId);
                });
            });

            function filterCards(filterValue) { let visibleCount = 0; vehicleCards.forEach(card => { if (filterValue === 'all' || filterValue === card.dataset.status) { card.style.display = 'block'; visibleCount++; } else { card.style.display = 'none'; } }); if (visibleCount === 0 && filterValue !== 'all') { noFilterResults.classList.remove('hidden'); } else { noFilterResults.classList.add('hidden'); } }
            function updateActiveFilter(filterValue) { statFilters.forEach(f => { f.classList.remove('ring-2', 'ring-primary'); if (f.dataset.filter === filterValue) { f.classList.add('ring-2', 'ring-primary'); } }); }
            updateActiveFilter('all');
            statFilters.forEach(filter => { filter.addEventListener('click', () => { const filterValue = filter.dataset.filter; updateActiveFilter(filterValue); filterCards(filterValue); }); });

            if (window.location.hash) { 
                const targetId = window.location.hash.substring(1); 
                if(document.getElementById(targetId)) { 
                    showMainSection(targetId, false); 
                } else { 
                    showMainSection('overview-section', false); 
                } 
            } else { 
                showMainSection('overview-section', false); 
            }
            
            // --- Shared Validation & Form Functions ---
            function showError(element, message) { const parent = element.closest('.form-control'); const errorElement = parent.querySelector('.error-message'); if (errorElement) { errorElement.textContent = message; errorElement.classList.remove('hidden'); } const target = element.closest('label.input') || element; target.classList.add('input-error', 'select-error'); element.focus(); }
            function clearError(element) { const parent = element.closest('.form-control'); const errorElement = parent.querySelector('.error-message'); if (errorElement) { errorElement.textContent = ''; errorElement.classList.add('hidden'); } const target = element.closest('label.input') || element; target.classList.remove('input-error', 'select-error'); }
            function setupImagePreview(inputId, previewId) { const inputElement = document.getElementById(inputId); if(inputElement) { inputElement.addEventListener('change', function(event) { const file = event.target.files[0]; if (file) { const reader = new FileReader(); reader.onload = (e) => { document.getElementById(previewId).src = e.target.result; }; reader.readAsDataURL(file); } }); } }

            // --- Add Vehicle Form Logic ---
            const addVehicleForm = document.getElementById('addVehicleForm');
            const addVehicleConfirmModal = document.getElementById('addVehicleConfirmModal');
            const finalAddVehicleSubmitBtn = document.getElementById('final-add-vehicle-submit-btn');
            const ownerTypeSelect = document.getElementById('owner-type'); 
            const otherOwnerDetails = document.getElementById('other-owner-details'); 
            
            ownerTypeSelect.addEventListener('change', function() { if (this.value === 'other') { otherOwnerDetails.classList.remove('hidden'); otherOwnerDetails.querySelectorAll('input').forEach(input => input.setAttribute('required', '')); } else { otherOwnerDetails.classList.add('hidden'); otherOwnerDetails.querySelectorAll('input').forEach(input => { input.removeAttribute('required'); clearError(input); }); } });
            setupImagePreview('reg-copy-upload', 'reg-copy-preview'); setupImagePreview('tax-sticker-upload', 'tax-sticker-preview'); setupImagePreview('front-view-upload', 'front-view-preview'); setupImagePreview('rear-view-upload', 'rear-view-preview');
            const daySelect = document.getElementById('tax-day'); const monthSelect = document.getElementById('tax-month'); const yearSelect = document.getElementById('tax-year'); const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"]; for (let i = 1; i <= 31; i++) { daySelect.innerHTML += `<option value="${i}">${i}</option>`; } months.forEach((month, i) => { monthSelect.innerHTML += `<option value="${i + 1}">${month}</option>`; }); const currentYearBE = new Date().getFullYear() + 543; for (let i = currentYearBE; i <= currentYearBE + 10; i++) { yearSelect.innerHTML += `<option value="${i}">${i}</option>`; }
            document.getElementById('vehicle-model').addEventListener('input', function() { this.value = this.value.toUpperCase().replace(/[^A-Z0-9\s-]/g, ''); }); document.getElementById('vehicle-color').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙]/g, ''); }); document.getElementById('license-plate').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙0-9\s]/g, ''); });
            const resetButton = document.getElementById('reset-form-btn'); const resetConfirmModal = document.getElementById('resetConfirmModal'); const confirmResetBtn = document.getElementById('confirm-reset-btn'); if (resetButton) { resetButton.addEventListener('click', function() { resetConfirmModal.showModal(); }); } if (confirmResetBtn) { confirmResetBtn.addEventListener('click', function() { addVehicleForm.reset(); document.getElementById('reg-copy-preview').src = 'https://img5.pic.in.th/file/secure-sv1/registration.jpg'; document.getElementById('tax-sticker-preview').src = 'https://img2.pic.in.th/pic/tax_sticker.jpg'; document.getElementById('front-view-preview').src = 'https://img2.pic.in.th/pic/front_view.png'; document.getElementById('rear-view-preview').src = 'https://img5.pic.in.th/file/secure-sv1/rear_view.png'; otherOwnerDetails.classList.add('hidden'); addVehicleForm.querySelectorAll('.error-message').forEach(el => el.classList.add('hidden')); addVehicleForm.querySelectorAll('.input-error, .select-error').forEach(el => el.classList.remove('input-error', 'select-error')); resetConfirmModal.close(); }); }
            
             // --- [เพิ่ม] ฟังก์ชันคำนวณวันรับบัตรสำหรับแสดงใน Modal ---
            function calculatePickupDate(startDate = new Date()) {
                const holidays = [
                    // --- ปี 2568 ---
                    '2025-01-01', // วันขึ้นปีใหม่
                    '2025-02-12', // วันมาฆบูชา
                    '2025-04-07', // วันหยุดชดเชยวันจักรี
                    '2025-04-14', // วันสงกรานต์
                    '2025-04-15', // วันสงกรานต์
                    '2025-04-16', // วันหยุดชดเชยวันสงกรานต์
                    '2025-05-01', // วันแรงงานแห่งชาติ
                    '2025-05-05', // วันหยุดชดเชยวันฉัตรมงคล
                    '2025-05-12', // วันหยุดชดเชยวันวิสาขบูชา
                    '2025-06-03', // วันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าฯ พระบรมราชินี
                    '2025-07-11', // วันอาสาฬหบูชา
                    '2025-07-28', // วันเฉลิมพระชนมพรรษาพระบาทสมเด็จพระวชิรเกล้าเจ้าอยู่หัว
                    '2025-08-12', // วันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าสิริกิติ์ พระบรมราชินีนาถ พระบรมราชชนนีพันปีหลวง
                    '2025-10-13', // วันคล้ายวันสวรรคต พระบาทสมเด็จพระบรมชนกาธิเบศร มหาภูมิพลอดุลยเดชมหาราช บรมนาถบพิตร
                    '2025-10-23', // วันปิยมหาราช
                    '2025-12-05', // วันคล้ายวันพระบรมราชสมภพ พระบาทสมเด็จพระบรมชนกาธิเบศร มหาภูมิพลอดุลยเดชมหาราช บรมนาถบพิตร
                    '2025-12-10', // วันรัฐธรรมนูญ
                    '2025-12-31', // วันสิ้นปี

                    // --- ปี 2569 ---
                    '2026-01-01', // วันขึ้นปีใหม่
                    '2026-03-02', // วันมาฆบูชา
                    '2026-04-06', // วันจักรี
                    '2026-04-13', // วันสงกรานต์
                    '2026-04-14', // วันสงกรานต์
                    '2026-04-15', // วันสงกรานต์
                    '2026-05-01', // วันแรงงานแห่งชาติ
                    '2026-05-04', // วันฉัตรมงคล
                    '2026-06-01', // วันหยุดชดเชยวันวิสาขบูชา
                    '2026-06-03', // วันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าฯ พระบรมราชินี
                    '2026-07-28', // วันเฉลิมพระชนมพรรษาพระบาทสมเด็จพระวชิรเกล้าเจ้าอยู่หัว
                    '2026-07-29', // วันอาสาฬหบูชา
                    '2026-08-12', // วันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าสิริกิติ์ พระบรมราชินีนาถ พระบรมราชชนนีพันปีหลวง
                    '2026-10-13', // วันคล้ายวันสวรรคต พระบาทสมเด็จพระบรมชนกาธิเบศร มหาภูมิพลอดุลยเดชมหาราช บรมนาถบพิตร
                    '2026-10-23', // วันปิยมหาราช
                    '2026-12-07', // วันหยุดชดเชยวันคล้ายวันพระบรมราชสมภพของรัชกาลที่ 9
                    '2026-12-10', // วันรัฐธรรมนูญ
                    '2026-12-31', // วันสิ้นปี

                    // --- ปี 2570 ---
                    '2027-01-01', // วันขึ้นปีใหม่
                    '2027-02-19', // วันมาฆบูชา
                    '2027-04-06', // วันจักรี
                    '2027-04-13', // วันสงกรานต์
                    '2027-04-14', // วันสงกรานต์
                    '2027-04-15', // วันสงกรานต์
                    '2027-05-03', // วันหยุดชดเชยวันแรงงานแห่งชาติ
                    '2027-05-04', // วันฉัตรมงคล
                    '2027-05-19', // วันวิสาขบูชา
                    '2027-06-03', // วันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าฯ พระบรมราชินี
                    '2027-07-19', // วันหยุดชดเชยวันอาสาฬหบูชา
                    '2027-07-28', // วันเฉลิมพระชนมพรรษาพระบาทสมเด็จพระวชิรเกล้าเจ้าอยู่หัว
                    '2027-08-12', // วันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าสิริกิติ์ พระบรมราชินีนาถ พระบรมราชชนนีพันปีหลวง
                    '2027-10-13', // วันคล้ายวันสวรรคต พระบาทสมเด็จพระบรมชนกาธิเบศร มหาภูมิพลอดุลยเดชมหาราช บรมนาถบพิตร
                    '2027-10-25', // วันหยุดชดเชยวันปิยมหาราช
                    '2027-12-06', // วันหยุดชดเชยวันคล้ายวันพระบรมราชสมภพของรัชกาลที่ 9
                    '2027-12-10', // วันรัฐธรรมนูญ
                    '2027-12-31', // วันสิ้นปี

                    // --- ปี 2571 ---
                    '2028-01-03', // วันหยุดชดเชยวันขึ้นปีใหม่
                    '2028-02-08', // วันมาฆบูชา
                    '2028-04-06', // วันจักรี
                    '2028-04-13', // วันสงกรานต์
                    '2028-04-14', // วันสงกรานต์
                    '2028-04-17', // วันหยุดชดเชยวันสงกรานต์
                    '2028-05-01', // วันแรงงานแห่งชาติ
                    '2028-05-04', // วันฉัตรมงคล
                    '2028-05-08', // วันหยุดชดเชยวันวิสาขบูชา
                    '2028-06-05', // วันหยุดชดเชยวันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าฯ พระบรมราชินี
                    '2028-07-06', // วันอาสาฬหบูชา
                    '2028-07-28', // วันเฉลิมพระชนมพรรษาพระบาทสมเด็จพระวชิรเกล้าเจ้าอยู่หัว
                    '2028-08-14', // วันหยุดชดเชยวันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าสิริกิติ์ พระบรมราชินีนาถ พระบรมราชชนนีพันปีหลวง
                    '2028-10-13', // วันคล้ายวันสวรรคต พระบาทสมเด็จพระบรมชนกาธิเบศร มหาภูมิพลอดุลยเดชมหาราช บรมนาถบพิตร
                    '2028-10-23', // วันปิยมหาราช
                    '2028-12-05', // วันคล้ายวันพระบรมราชสมภพ พระบาทสมเด็จพระบรมชนกาธิเบศร มหาภูมิพลอดุลยเดชมหาราช บรมนาถบพิตร
                    '2028-12-11', // วันหยุดชดเชยวันรัฐธรรมนูญ
                ];
                let workingDays = 0;
                let currentDate = new Date(startDate);

                while (workingDays < 15) {
                    currentDate.setDate(currentDate.getDate() + 1);
                    const dayOfWeek = currentDate.getDay();
                    const dateString = currentDate.toISOString().slice(0, 10);

                    if (dayOfWeek !== 0 && dayOfWeek !== 6 && !holidays.includes(dateString)) {
                        workingDays++;
                    }
                }
                return currentDate;
            }

            function populateAddVehicleConfirmModal() {
                const summaryContent = document.getElementById('add-vehicle-summary-content');
                const formData = new FormData(addVehicleForm);
                const pickupDate = calculatePickupDate();

                let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';

                html += `<div class="md:col-span-1">
                            <div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">หลักฐานรูปถ่าย</div>
                            <div class="grid grid-cols-2 gap-2 p-2 bg-base-200 rounded-md">
                                <div><img src="${document.getElementById('reg-copy-preview').src}" class="w-full h-24 object-cover rounded-lg border"><p class="text-[10px] font-semibold text-center mt-1">สำเนาทะเบียนรถ</p></div>
                                <div><img src="${document.getElementById('tax-sticker-preview').src}" class="w-full h-24 object-cover rounded-lg border"><p class="text-[10px] font-semibold text-center mt-1">ป้ายภาษี</p></div>
                                <div><img src="${document.getElementById('front-view-preview').src}" class="w-full h-24 object-cover rounded-lg border"><p class="text-[10px] font-semibold text-center mt-1">รูปถ่ายด้านหน้า</p></div>
                                <div><img src="${document.getElementById('rear-view-preview').src}" class="w-full h-24 object-cover rounded-lg border"><p class="text-[10px] font-semibold text-center mt-1">รูปถ่ายด้านหลัง</p></div>
                            </div>
                         </div>`;

                html += '<div class="md:col-span-1 space-y-3">';
                const taxDate = `${formData.get('tax_day')} ${document.getElementById('tax-month').options[document.getElementById('tax-month').selectedIndex].text} ${formData.get('tax_year')}`;
                html += `<div>
                            <div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">ข้อมูลยานพาหนะ</div>
                            <div class="p-2 bg-base-200 rounded-md grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                                <div><strong>ประเภท:</strong> ${formData.get('vehicle_type') || '-'}</div>
                                <div><strong>ยี่ห้อ:</strong> ${formData.get('vehicle_brand') || '-'}</div>
                                <div><strong>รุ่น:</strong> ${formData.get('vehicle_model') || '-'}</div>
                                <div><strong>สี:</strong> ${formData.get('vehicle_color') || '-'}</div>
                                <div class="col-span-2"><strong>เลขทะเบียน:</strong> ${formData.get('license_plate') || '-'} ${formData.get('license_province') || '-'}</div>
                                <div class="col-span-2"><strong>วันสิ้นอายุภาษี:</strong> ${taxDate}</div>
                            </div>
                        </div>`;

                const ownerType = formData.get('owner_type');
                html += '<div>';
                html += '<div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">ข้อมูลเจ้าของ</div>';
                html += '<div class="p-2 bg-base-200 rounded-md grid grid-cols-1 gap-y-1 text-xs">';
                if (ownerType === 'self') {
                    html += `<div><strong>ความเป็นเจ้าของ:</strong> รถชื่อตนเอง</div>`;
                } else {
                    html += `<div><strong>ความเป็นเจ้าของ:</strong> รถคนอื่น</div>`;
                    html += `<div><strong>ชื่อ-สกุล เจ้าของ:</strong> ${formData.get('other_owner_name') || '-'}</div>`;
                    html += `<div><strong>เกี่ยวข้องเป็น:</strong> ${formData.get('other_owner_relation') || '-'}</div>`;
                }
                html += '</div></div>';

                html += `<div>
                            <div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">กำหนดการ</div>
                            <div class="p-2 bg-blue-100 border border-blue-200 rounded-md text-center">
                                <span class="text-xs text-blue-800">วันที่คาดว่าจะได้รับบัตร</span>
                                <p class="font-bold text-blue-900">${formatDateToThai(pickupDate.toISOString().slice(0, 10))}</p>
                            </div>
                        </div>`;
                
                html += '</div>';
                html += '</div>';
                summaryContent.innerHTML = html;
            }

            addVehicleForm.addEventListener('submit', function(event) {
                event.preventDefault(); 
                let isFormValid = true; 
                addVehicleForm.querySelectorAll('[required]').forEach(field => { 
                    if (!validateField(addVehicleForm, field)) isFormValid = false; 
                }); 
                
                if (isFormValid) {
                    populateAddVehicleConfirmModal();
                    addVehicleConfirmModal.showModal();
                } else { 
                    showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error'); 
                } 
            });

            finalAddVehicleSubmitBtn.addEventListener('click', async () => {
                addVehicleConfirmModal.close();
                const submitButton = addVehicleForm.querySelector('button[type="submit"]'); 
                const originalButtonContent = submitButton.innerHTML; 
                submitButton.innerHTML = '<span class="loading loading-spinner loading-sm"></span> กำลังตรวจสอบ...'; 
                submitButton.disabled = true; 
                
                const licensePlate = document.getElementById('license-plate').value; 
                const province = document.getElementById('license-province').value; 
                
                try { 
                    const checkResponse = await fetch('../../../controllers/user/vehicle/check_vehicle.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ license_plate: licensePlate, province: province, request_id: 0 }) }); 
                    const checkResult = await checkResponse.json(); 
                    
                    if (checkResult.exists) { 
                        document.getElementById('duplicateVehicleMessage').textContent = `ทะเบียนรถ ${licensePlate} จังหวัด ${province} มีข้อมูลอยู่ในระบบแล้ว`; 
                        document.getElementById('duplicateVehicleModal').showModal(); 
                        submitButton.innerHTML = originalButtonContent; 
                        submitButton.disabled = false; 
                    } else { 
                        document.getElementById('loadingModal').showModal(); 
                        setTimeout(() => { addVehicleForm.submit(); }, 100); 
                    } 
                } catch (error) { 
                    showAlert('เกิดข้อผิดพลาดในการตรวจสอบข้อมูล', 'error'); 
                    submitButton.innerHTML = originalButtonContent; 
                    submitButton.disabled = false; 
                }
            });

            addVehicleForm.querySelectorAll('input, select').forEach(field => {
                const eventType = (field.tagName === 'SELECT' || field.type === 'checkbox' || field.type === 'file') ? 'change' : 'input';
                field.addEventListener(eventType, () => validateField(addVehicleForm, field));
            });
            function validateField(form, field) {
                let isValid = true;
                const value = field.value.trim();
                clearError(field); // Clear previous errors first

                if (field.type === 'file') {
                    // Check for file size if a file is selected
                    if (field.files.length > 0) {
                        const file = field.files[0];
                        const maxSize = 5 * 1024 * 1024; // 5 MB
                        if (file.size > maxSize) {
                            showError(field, 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB');
                            field.value = ''; // Clear the invalid file selection
                            isValid = false;
                        }
                    } 
                    // Check for required file if no file is selected
                    else if (field.hasAttribute('required')) {
                        showError(field, 'กรุณาอัปโหลดไฟล์');
                        isValid = false;
                    }
                } else if (field.hasAttribute('required')) {
                    if (field.type === 'checkbox' && !field.checked) {
                        showError(field, 'กรุณายอมรับเงื่อนไข');
                        isValid = false;
                    } else if (field.tagName === 'SELECT' && value === '') {
                        showError(field, 'กรุณาเลือกข้อมูล');
                        isValid = false;
                    } else if (field.tagName !== 'SELECT' && field.type !== 'checkbox' && value === '') {
                        showError(field, 'กรุณากรอกข้อมูล');
                        isValid = false;
                    }
                }
                return isValid;
            }

            
            // --- Edit Vehicle Form Logic ---
            const editVehicleForm = document.getElementById('editVehicleForm');
            setupImagePreview('edit-reg-copy-upload', 'edit-reg-copy-preview'); setupImagePreview('edit-tax-sticker-upload', 'edit-tax-sticker-preview'); setupImagePreview('edit-front-view-upload', 'edit-front-view-preview'); setupImagePreview('edit-rear-view-upload', 'edit-rear-view-preview');
            const editOwnerTypeSelect = document.getElementById('edit-owner-type'); const editOtherOwnerDetails = document.getElementById('edit-other-owner-details'); editOwnerTypeSelect.addEventListener('change', function() { const requiredInputs = editOtherOwnerDetails.querySelectorAll('input'); if (this.value === 'other') { editOtherOwnerDetails.classList.remove('hidden'); requiredInputs.forEach(input => input.setAttribute('required', '')); } else { editOtherOwnerDetails.classList.add('hidden'); requiredInputs.forEach(input => { input.removeAttribute('required'); input.value = ''; clearError(input); }); } });
            const editDaySelect = document.getElementById('edit-tax-day'); const editMonthSelect = document.getElementById('edit-tax-month'); const editYearSelect = document.getElementById('edit-tax-year'); for (let i = 1; i <= 31; i++) { editDaySelect.innerHTML += `<option value="${i}">${i}</option>`; } months.forEach((month, i) => { editMonthSelect.innerHTML += `<option value="${i + 1}">${month}</option>`; }); for (let i = currentYearBE; i <= currentYearBE + 10; i++) { editYearSelect.innerHTML += `<option value="${i}">${i}</option>`; }
            document.getElementById('edit-vehicle-model').addEventListener('input', function() { this.value = this.value.toUpperCase().replace(/[^A-Z0-9\s-]/g, ''); }); document.getElementById('edit-vehicle-color').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙]/g, ''); }); document.getElementById('edit-license-plate').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙0-9\s]/g, ''); });
            
            // --- [แก้ไข] Validation สำหรับไฟล์ในฟอร์มแก้ไข ---
            const editFileInputs = editVehicleForm.querySelectorAll('input[type="file"]');
            editFileInputs.forEach(input => {
                input.addEventListener('change', () => {
                    clearError(input);
                    if (input.files.length > 0) {
                        const file = input.files[0];
                        const maxSize = 5 * 1024 * 1024; // 5 MB
                        if (file.size > maxSize) {
                            showError(input, 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB');
                        }
                    }
                });
            });

            editVehicleForm.querySelectorAll('[required]').forEach(field => { const eventType = (field.tagName === 'SELECT' || field.type === 'checkbox' || field.type === 'file') ? 'change' : 'input'; field.addEventListener(eventType, () => validateField(editVehicleForm, field)); });
            
            editVehicleForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                let isFormValid = true;
                let hasOversizedFiles = false;

                // ตรวจสอบขนาดไฟล์ทั้งหมดก่อน
                editFileInputs.forEach(input => {
                    if (input.files.length > 0) {
                        const file = input.files[0];
                        const maxSize = 5 * 1024 * 1024; // 5 MB
                        if (file.size > maxSize) {
                            showError(input, 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB');
                            hasOversizedFiles = true;
                            isFormValid = false;
                        }
                    }
                });

                // ตรวจสอบฟิลด์ที่จำเป็นอื่นๆ
                editVehicleForm.querySelectorAll('[required]').forEach(field => {
                    if (!validateField(editVehicleForm, field)) {
                        isFormValid = false;
                    }
                });

                // --- จัดการผลการตรวจสอบ ---
                if (!isFormValid) {
                    if (hasOversizedFiles) {
                         // showAlert('ไม่สามารถบันทึกได้: มีไฟล์รูปภาพขนาดใหญ่เกิน 5 MB', 'error');
                    } else {
                        showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error');
                    }
                    return; // หยุดการทำงานทันที
                }

                // --- ถ้าข้อมูลทั้งหมดถูกต้อง, ดำเนินการต่อไป ---
                const submitButton = editVehicleForm.querySelector('button[type="submit"]');
                const originalButtonContent = submitButton.innerHTML;
                submitButton.innerHTML = '<span class="loading loading-spinner loading-sm"></span> กำลังตรวจสอบ...';
                submitButton.disabled = true;
                const licensePlate = document.getElementById('edit-license-plate').value;
                const province = document.getElementById('edit-license-province').value;
                const requestId = document.getElementById('edit-request-id').value;
                try {
                    const checkResponse = await fetch('../../../controllers/user/vehicle/check_vehicle.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ license_plate: licensePlate, province: province, request_id: requestId })
                    });
                    const checkResult = await checkResponse.json();
                    if (checkResult.exists) {
                        document.getElementById('duplicateVehicleMessage').textContent = `ทะเบียนรถ ${licensePlate} จังหวัด ${province} มีข้อมูลอยู่ในระบบแล้ว`;
                        document.getElementById('duplicateVehicleModal').showModal();
                        submitButton.innerHTML = originalButtonContent;
                        submitButton.disabled = false;
                    } else {
                        document.getElementById('loadingModal').showModal();
                        setTimeout(() => {
                            editVehicleForm.submit();
                        }, 100);
                    }
                } catch (error) {
                    showAlert('เกิดข้อผิดพลาดในการตรวจสอบข้อมูล', 'error');
                    submitButton.innerHTML = originalButtonContent;
                    submitButton.disabled = false;
                }
            });


            // --- Profile Page Logic ---
            const profileSection = document.getElementById('profile-section');
            if (profileSection) {
                const editBtn = document.getElementById('edit-profile-btn');
                const saveBtn = document.getElementById('save-profile-btn');
                const cancelBtn = document.getElementById('cancel-edit-btn');
                const profileForm = document.getElementById('profileForm');
                const formInputs = profileForm.querySelectorAll('input:not([type=file]), select, textarea');
                const fileInput = document.getElementById('profile-photo-upload');
                const photoGuidance = document.getElementById('photo-guidance');
                const daySelectP = document.getElementById('profile-dob-day'); 
                const monthSelectP = document.getElementById('profile-dob-month'); 
                const yearSelectP = document.getElementById('profile-dob-year');
                const nationalIdInput = document.getElementById('profile-national-id');
                const phoneInput = profileForm.querySelector('[name="phone"]');
                const originalPhotoSrc = document.getElementById('profile-photo-preview').src;

                let initialFormValues = {};
                formInputs.forEach(input => { initialFormValues[input.name] = input.value; });
                
                let isAddressPluginActive = false;
                let isFileSizeValid = true;

                const validateProfileField = (field) => {
                    let isValid = true; const value = field.value.trim(); clearError(field);
                    
                    if (field.type === 'file' && field.files.length > 0) {
                        const file = field.files[0];
                        const maxSize = 5 * 1024 * 1024; // 5 MB
                        if (file.size > maxSize) {
                            showError(field, 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB');
                            isFileSizeValid = false;
                            isValid = false;
                        } else {
                            isFileSizeValid = true;
                        }
                    } else if (field.hasAttribute('required')) {
                         if (field.tagName === 'SELECT' && value === '') { showError(field, 'กรุณาเลือกข้อมูล'); isValid = false; } 
                         else if (field.tagName === 'INPUT' && !['checkbox', 'file', 'radio'].includes(field.type) && value === '') { showError(field, 'กรุณากรอกข้อมูล'); isValid = false; }
                    }

                    if (isValid) {
                        if (field.name === 'title' && value === 'other' && profileForm.querySelector('[name="title_other"]').value.trim() === '') { showError(profileForm.querySelector('[name="title_other"]'), 'กรุณาระบุคำนำหน้า'); isValid = false; }
                        else if (field.name === 'work_department' && value === 'other' && profileForm.querySelector('[name="work_department_other"]').value.trim() === '') { showError(profileForm.querySelector('[name="work_department_other"]'), 'กรุณาระบุสังกัด'); isValid = false; }
                        else if (field.name === 'phone' && value.replace(/\D/g, '').length !== 10) { showError(field, 'กรุณากรอกเบอร์โทรศัพท์ 10 หลัก'); isValid = false;}
                        else if (field.name === 'official_id' && value.length > 0 && value.length !== 10) { showError(field, 'กรุณากรอกเลขบัตรให้ครบ 10 หลัก'); isValid = false;}
                    }
                    return isValid;
                }

                const toggleEditMode = (isEditing) => {
                    formInputs.forEach(input => {
                        if (input.name === 'national_id_display') return;
                        if (isEditing) { 
                            input.removeAttribute('disabled'); 
                            input.classList.remove('input-disabled');
                        } else { 
                            input.setAttribute('disabled', true); 
                            input.classList.add('input-disabled');
                        }
                    });

                    if(isEditing) {
                        if (!isAddressPluginActive) {
                            $.Thailand({ 
                                $district: $('#profile-subdistrict'), 
                                $amphoe: $('#profile-district'), 
                                $province: $('#profile-province'), 
                                $zipcode: $('#profile-zipcode') 
                            });
                            isAddressPluginActive = true;
                        }
                    }
                    fileInput.classList.toggle('hidden', !isEditing);
                    photoGuidance.classList.toggle('hidden', !isEditing);
                    editBtn.classList.toggle('hidden', isEditing);
                    saveBtn.classList.toggle('hidden', !isEditing);
                    cancelBtn.classList.toggle('hidden', !isEditing);
                };

                editBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    toggleEditMode(true);
                });
                
                cancelBtn.addEventListener('click', () => {
                    formInputs.forEach(input => { 
                        input.value = initialFormValues[input.name];
                        clearError(input);
                    });
                    document.getElementById('profile-photo-preview').src = originalPhotoSrc;
                    fileInput.value = '';
                    isFileSizeValid = true;
                    daySelectP.value = userDob.day; monthSelectP.value = userDob.month; yearSelectP.value = userDob.year;
                    formatInput(phoneInput, 'xxx-xxx-xxxx');
                    formatInput(nationalIdInput, 'x-xxxx-xxxxx-xx-x');
                    toggleEditMode(false);
                });

                saveBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    
                    if (!isFileSizeValid) {
                        showAlert('ไฟล์รูปภาพมีขนาดใหญ่เกิน 5 MB กรุณาเลือกไฟล์ใหม่', 'error');
                        return;
                    }

                    const phoneValue = phoneInput.value.replace(/\D/g, '');
                    let isPhoneValid = true;

                    if (phoneValue !== initialFormValues['phone'].replace(/\D/g, '')) {
                        if (phoneValue.length === 10) {
                            try {
                                const response = await fetch('../../../controllers/user/register/check_user.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ phone: phoneValue, user_id: currentUserId })
                                });
                                const result = await response.json();
                                if (result.phoneExists) {
                                    showError(phoneInput, 'เบอร์โทรศัพท์นี้มีผู้ใช้อื่นลงทะเบียนแล้ว');
                                    isPhoneValid = false;
                                } else {
                                    clearError(phoneInput);
                                }
                            } catch (error) {
                                showAlert('เกิดข้อผิดพลาดในการตรวจสอบเบอร์โทร', 'error');
                                isPhoneValid = false;
                            }
                        }
                    }

                    if (!isPhoneValid) return;

                    let isFormValid = true;
                    profileForm.querySelectorAll('input, select').forEach(field => {
                        if (!validateProfileField(field)) {
                            isFormValid = false;
                        }
                    });

                    if (isFormValid) {
                        document.getElementById('loadingModal').showModal();
                        profileForm.submit();
                    } else {
                        showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error');
                    }
                });
                
                fileInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const maxSize = 5 * 1024 * 1024;
                        clearError(this);
                        
                        if (file.size > maxSize) {
                            showError(this, 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB');
                            showAlert('ไฟล์รูปภาพมีขนาดใหญ่เกิน 5 MB', 'error');
                            this.value = ''; 
                            document.getElementById('profile-photo-preview').src = originalPhotoSrc;
                            isFileSizeValid = false;
                        } else {
                            isFileSizeValid = true;
                            setupImagePreview('profile-photo-upload', 'profile-photo-preview');
                        }
                    } else {
                        isFileSizeValid = true;
                        clearError(this);
                    }
                });

                daySelectP.innerHTML = '<option disabled value="">วัน</option>'; monthSelectP.innerHTML = '<option disabled value="">เดือน</option>'; yearSelectP.innerHTML = '<option disabled value="">ปี (พ.ศ.)</option>';
                for (let i = 1; i <= 31; i++) { daySelectP.innerHTML += `<option value="${i}">${i}</option>`; }
                months.forEach((month, i) => { monthSelectP.innerHTML += `<option value="${i + 1}">${month}</option>`; });
                const currentYearBE_profile = new Date().getFullYear() + 543;
                for (let i = currentYearBE_profile; i >= currentYearBE_profile - 100; i--) { yearSelectP.innerHTML += `<option value="${i}">${i}</option>`; }
                daySelectP.value = userDob.day; monthSelectP.value = userDob.month; yearSelectP.value = userDob.year;

                formInputs.forEach(field => {
                    if(field.type !== 'file') {
                        const eventType = (field.tagName === 'SELECT' || field.type === 'checkbox') ? 'change' : 'input';
                        field.addEventListener(eventType, () => validateProfileField(field));
                    }
                });

                document.getElementById('profile-title').addEventListener('change', function() {
                    const otherInput = document.getElementById('profile-title-other');
                    otherInput.classList.toggle('hidden', this.value !== 'other');
                     if(this.value === 'other') otherInput.setAttribute('required', '');
                     else { otherInput.removeAttribute('required'); clearError(otherInput); }
                });

                const workDeptSelect = profileForm.querySelector('[name="work_department"]');
                if(workDeptSelect) {
                    workDeptSelect.addEventListener('change', function() {
                        const otherInput = profileForm.querySelector('[name="work_department_other"]');
                        otherInput.classList.toggle('hidden', this.value !== 'other');
                         if(this.value === 'other') otherInput.setAttribute('required', '');
                         else { otherInput.removeAttribute('required'); clearError(otherInput); }
                    });
                }
                
                profileForm.querySelector('[name="firstname"]').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙\s]/g, ''); });
                profileForm.querySelector('[name="lastname"]').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙\s]/g, ''); });
                profileForm.querySelector('[name="title_other"]').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙\s.()]/g, ''); });
                profileForm.querySelector('[name="address"]').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙0-9\s.\-\/]/g, ''); });
                const positionInput = profileForm.querySelector('[name="position"]');
                if (positionInput) positionInput.addEventListener('input', function() {  });
                const workDeptOtherInput = profileForm.querySelector('[name="work_department_other"]');
                if (workDeptOtherInput) workDeptOtherInput.addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙0-9\s.\-()]/g, ''); });
                
                phoneInput.addEventListener('input', function() {
                    formatInput(this, 'xxx-xxx-xxxx');
                });
                
                const officialIdInput = profileForm.querySelector('[name="official_id"]');
                if (officialIdInput) officialIdInput.addEventListener('input', function() { this.value = this.value.replace(/\D/g, ''); });
                
                // Format initial values
                formatInput(phoneInput, 'xxx-xxx-xxxx');
                formatInput(nationalIdInput, 'x-xxxx-xxxxx-xx-x');
            }
        });
    </script>
</body>
</html>

