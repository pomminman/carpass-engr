<?php
// public/app/verify.php

// --- 1. เรียกใช้ไฟล์กำหนดค่าฐานข้อมูล ---
require_once '../../app/models/db_config.php';

// --- [เพิ่ม] ฟังก์ชันสำหรับแปลงวันที่เป็นภาษาไทย ---
function format_thai_datetime($datetime) {
    if (empty($datetime)) {
        return '-';
    }
    try {
        $date = new DateTime($datetime);
        $months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
        $thai_year = (int)$date->format('Y') + 543;
        return $date->format('j ') . $months[$date->format('n') - 1] . ' ' . $thai_year;
    } catch (Exception $e) {
        return '-';
    }
}

// --- 2. รับค่า request_key จาก URL ---
$request_key = filter_input(INPUT_GET, 'key', FILTER_SANITIZE_STRING);
$data = null;
$found = false;

// --- 3. เชื่อมต่อและค้นหาข้อมูล ---
if ($request_key) {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    // [แก้ไข] เพิ่ม card_type ใน SQL query
    $sql = "SELECT
                vr.status, vr.card_number, vr.card_expiry_year, vr.approved_at, vr.vehicle_type, vr.card_type,
                vr.brand, vr.model, vr.color, vr.license_plate, vr.province,
                vr.photo_front, vr.photo_rear,
                u.title, u.firstname, u.lastname, u.phone_number, u.photo_profile
            FROM
                vehicle_requests AS vr
            JOIN
                users AS u ON vr.user_id = u.id
            WHERE
                vr.request_key = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $request_key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $found = true;
        $data = $result->fetch_assoc();
    }

    $stmt->close();
    $conn->close();
}

// --- 4. ตรรกะสำหรับตรวจสอบสถานะและข้อมูลบัตร ---
$display_status = '';
$status_class = '';
$status_reason = '';
$card_type_thai = '';
$is_active_card = false;
$current_year_be = (int)date("Y") + 543; // ใช้ปี พ.ศ. ในการคำนวณ

if ($found) {
    $is_expired = !empty($data['card_expiry_year']) && ((int)$data['card_expiry_year'] < $current_year_be);

    switch ($data['status']) {
        case 'approved':
            if ($is_expired) {
                $display_status = 'ไม่สามารถใช้งานได้';
                $status_class = 'bg-error text-error-content';
                $status_reason = 'เนื่องจากบัตรหมดอายุ';
            } else {
                $display_status = 'อนุมัติ (ใช้งานได้)';
                $status_class = 'bg-success text-success-content';
                $is_active_card = true; // ตั้งค่าสถานะบัตรใช้งานได้
            }
            break;
        case 'rejected':
            $display_status = 'ไม่ผ่าน (ไม่สามารถใช้งานได้)';
            $status_class = 'bg-error text-error-content';
            break;
        case 'pending':
            $display_status = 'รออนุมัติ';
            $status_class = 'bg-warning text-warning-content';
            break;
        default:
            $display_status = 'สถานะไม่ถูกต้อง';
            $status_class = 'bg-gray-400 text-gray-800';
    }

    // [เพิ่ม] แปลงประเภทบัตรเป็นภาษาไทย
    if ($data['card_type'] === 'internal') {
        $card_type_thai = 'ภายใน';
    } elseif ($data['card_type'] === 'external') {
        $card_type_thai = 'ภายนอก';
    }
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบข้อมูลบัตรผ่าน</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f0f2f5; }
        .info-label { font-size: 0.75rem; color: #6b7280; }
        .info-data { font-size: 1rem; font-weight: 500; color: #1f2937; }
    </style>
</head>
<body>
    <div class="container mx-auto max-w-lg p-2 sm:p-4 min-h-screen flex flex-col justify-center">

        <header class="text-center mb-4">
            <img src="https://img2.pic.in.th/pic/CARPASS-logo11af8574a9cc9906.png" alt="Logo" class="h-20 sm:h-24 w-auto mx-auto" onerror="this.onerror=null;this.src='https://placehold.co/150x150/CCCCCC/FFFFFF?text=Logo';">
            <h1 class="text-xl font-bold mt-2">ระบบตรวจสอบข้อมูลบัตรผ่านยานพาหนะ</h1>
            <p class="text-sm text-gray-500">ค่ายภาณุรังษี</p>
        </header>

        <main>
            <?php if ($found): ?>
                <div class="card bg-base-100 shadow-xl border border-base-300/50">
                    <div class="card-body p-4 sm:p-6">
                        <!-- สถานะบัตร -->
                        <div class="text-center p-3 rounded-lg <?php echo $status_class; ?>">
                            <div class="font-bold text-lg">สถานะ: <?php echo $display_status; ?></div>
                            <?php if ($status_reason): ?>
                                <div class="text-xs opacity-90"><?php echo $status_reason; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="divider text-sm">รายละเอียด</div>
                        
                        <!-- ข้อมูลบัตรผ่าน -->
                        <div class="p-3 bg-base-200 rounded-lg">
                            <h3 class="font-semibold mb-2 text-center">ข้อมูลบัตรผ่าน</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 sm:gap-x-4 text-center">
                                <div>
                                    <div class="info-label">ประเภทบัตร</div>
                                    <div class="info-data"><?php echo ($is_active_card && !empty($card_type_thai)) ? htmlspecialchars($card_type_thai) : '-'; ?></div>
                                </div>
                                <div>
                                    <div class="info-label">เลขที่บัตร</div>
                                    <div class="info-data"><?php echo htmlspecialchars($data['card_number'] ?: '-'); ?></div>
                                </div>
                                <div>
                                    <div class="info-label">วันที่อนุมัติ</div>
                                    <div class="info-data"><?php echo format_thai_datetime($data['approved_at']); ?></div>
                                </div>
                                <div>
                                    <div class="info-label">หมดอายุสิ้นปี (พ.ศ.)</div>
                                    <div class="info-data"><?php echo htmlspecialchars($data['card_expiry_year'] ?: '-'); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- ข้อมูลยานพาหนะ -->
                        <div class="mt-4 p-3 border border-base-300 rounded-lg">
                            <h3 class="font-semibold mb-2 text-center">ข้อมูลยานพาหนะ</h3>
                            <div class="grid grid-cols-2 gap-2 mb-4">
                                <div>
                                    <img src="../../../public/uploads/vehicle/front_view/<?php echo htmlspecialchars($data['photo_front']); ?>" class="w-full h-auto rounded-lg border" alt="รูปถ่ายรถด้านหน้า" onerror="this.onerror=null;this.src='https://placehold.co/200x150/CCCCCC/FFFFFF?text=No+Img';">
                                    <p class="text-xs text-center mt-1 text-gray-500">ด้านหน้า</p>
                                </div>
                                <div>
                                    <img src="../../../public/uploads/vehicle/rear_view/<?php echo htmlspecialchars($data['photo_rear']); ?>" class="w-full h-auto rounded-lg border" alt="รูปถ่ายรถด้านหลัง" onerror="this.onerror=null;this.src='https://placehold.co/200x150/CCCCCC/FFFFFF?text=No+Img';">
                                     <p class="text-xs text-center mt-1 text-gray-500">ด้านหลัง</p>
                                </div>
                            </div>
                            <div class="space-y-3 text-sm w-full">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <div class="info-label">ประเภท</div>
                                        <div class="info-data"><?php echo htmlspecialchars($data['vehicle_type']); ?></div>
                                    </div>
                                    <div>
                                        <div class="info-label">ยี่ห้อ</div>
                                        <div class="info-data"><?php echo htmlspecialchars($data['brand']); ?></div>
                                    </div>
                                    <div class="border-t border-base-300 col-span-2 my-1"></div>
                                    <div>
                                        <div class="info-label">สี</div>
                                        <div class="info-data"><?php echo htmlspecialchars($data['color']); ?></div>
                                    </div>
                                    <div>
                                        <div class="info-label">รุ่น</div>
                                        <div class="info-data"><?php echo htmlspecialchars($data['model']); ?></div>
                                    </div>
                                </div>
                                <div class="border-t border-base-300 col-span-2 my-1"></div>
                                <div>
                                    <div class="info-label">หมายเลขทะเบียน</div>
                                    <div class="info-data text-lg bg-gray-200 text-center p-1 rounded"><?php echo htmlspecialchars($data['license_plate'] . ' ' . $data['province']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- กรณีไม่พบข้อมูล -->
                <div class="card bg-base-100 shadow-xl border border-base-300/50">
                    <div class="card-body p-4 sm:p-6 items-center text-center">
                        <i class="fa-solid fa-circle-xmark text-6xl text-error mb-4"></i>
                        <h2 class="card-title text-2xl">ไม่พบข้อมูล</h2>
                        <p class="text-gray-600">ไม่พบข้อมูลสำหรับรหัสอ้างอิงนี้ในระบบ<br>กรุณาตรวจสอบความถูกต้องของ QR Code</p>
                    </div>
                </div>
            <?php endif; ?>
        </main>
        
        <footer class="text-center text-slate-500 mt-8">
            <p class="text-xs">Developed by กยข.กช.</p>
            <p class="text-xs">ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ)</p>
        </footer>
    </div>
</body>
</html>

