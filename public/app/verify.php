<?php
// public/app/verify.php

// --- 1. เรียกใช้ไฟล์กำหนดค่าฐานข้อมูล ---
require_once '../../app/models/db_config.php';

// --- ฟังก์ชันสำหรับแปลงวันที่เป็นภาษาไทย ---
function format_thai_date($datetime) {
    if (empty($datetime)) return '-';
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
$image_base_path = '';

// --- 3. เชื่อมต่อและค้นหาข้อมูล ---
if ($request_key) {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
    $conn->set_charset("utf8");

    // [แก้ไข] ใช้ Query เดียวที่ครอบคลุมทุกสถานะและ JOIN ตาราง vehicles
    $sql = "
        SELECT 
            vr.status, vr.card_number, vr.card_expiry, vr.approved_at, vr.card_type,
            vr.photo_front, vr.photo_rear,
            v.vehicle_type, v.brand, v.model, v.color, v.license_plate, v.province,
            COALESCE(aud.title, u.title) as title,
            COALESCE(aud.firstname, u.firstname) as firstname,
            COALESCE(aud.lastname, u.lastname) as lastname,
            u.user_key, 
            vr.search_id 
        FROM vehicle_requests AS vr 
        JOIN users AS u ON vr.user_id = u.id
        JOIN vehicles AS v ON vr.vehicle_id = v.id
        LEFT JOIN approved_user_data AS aud ON vr.id = aud.request_id
        WHERE vr.request_key = ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $request_key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $found = true;
        $data = $result->fetch_assoc();
        $image_base_path = '/public/uploads/' . htmlspecialchars($data['user_key']) . '/vehicle/' . htmlspecialchars($request_key) . '/';
    }
    $stmt->close();
    $conn->close();
}

// --- 4. ตรรกะสำหรับตรวจสอบสถานะและข้อมูลบัตร ---
$display_status = ''; $status_class = ''; $status_reason = ''; $card_type_thai = ''; $is_active_card = false;

if ($found) {
    $is_expired = !empty($data['card_expiry']) && (new DateTime() > new DateTime($data['card_expiry']));

    switch ($data['status']) {
        case 'approved':
            if ($is_expired) {
                $display_status = 'ไม่สามารถใช้งานได้';
                $status_class = 'bg-error text-error-content';
                $status_reason = 'เนื่องจากบัตรหมดอายุ';
            } else {
                $display_status = 'อนุมัติ (ใช้งานได้)';
                $status_class = 'bg-success text-success-content';
                $is_active_card = true;
            }
            break;
        case 'rejected': $display_status = 'ไม่ผ่าน (ไม่สามารถใช้งานได้)'; $status_class = 'bg-error text-error-content'; break;
        case 'pending': $display_status = 'รออนุมัติ'; $status_class = 'bg-warning text-warning-content'; break;
        default: $display_status = 'สถานะไม่ถูกต้อง'; $status_class = 'bg-gray-400 text-gray-800';
    }

    if ($data['card_type'] === 'internal') $card_type_thai = 'ภายใน';
    elseif ($data['card_type'] === 'external') $card_type_thai = 'ภายนอก';
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบข้อมูลบัตรผ่าน</title>

    <link rel="icon" type="image/png" href="/public/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/public/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/public/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/public/assets/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="carpass engrdept" />
    <link rel="manifest" href="/public/assets/favicon/site.webmanifest" />

    <!-- Local CSS -->
    <link rel="stylesheet" href="/lib/daisyui@4.12.10/dist/full.min.css" type="text/css" />
    <link rel="stylesheet" href="/lib/google-fonts-prompt/prompt.css">
    <link rel="stylesheet" href="/lib/fontawesome-free-7.0.1-web/css/all.min.css">

    <!-- Local JS -->
    <script src="/lib/tailwindcss/tailwindcss.js"></script>

    <style> body { font-family: 'Prompt', sans-serif; background-color: #f0f2f5; } #zoomed-image { max-height: 85vh; } </style>
</head>
<body>
    <div class="container mx-auto max-w-lg p-2 sm:p-4 min-h-screen flex flex-col justify-center">
        <header class="text-center mb-4">
            <img src="/public/assets/images/CARPASS%20logo.png" alt="Logo" class="h-20 sm:h-24 w-auto mx-auto">
            <h1 class="text-xl font-bold mt-2">ระบบตรวจสอบข้อมูลบัตรผ่านยานพาหนะ</h1>
            <p class="text-sm text-gray-500">ค่ายภาณุรังษี</p>
        </header>
        <main>
            <?php if ($found): ?>
                <div class="card bg-base-100 shadow-xl border border-base-300/50">
                    <div class="card-body p-4 sm:p-6">
                        <div class="text-center p-3 rounded-lg <?php echo $status_class; ?>">
                            <div class="font-bold text-lg">สถานะ: <?php echo $display_status; ?></div>
                            <?php if ($status_reason): ?><div class="text-xs opacity-90"><?php echo $status_reason; ?></div><?php endif; ?>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mt-4">
                            <div class="space-y-4">
                                <h3 class="font-semibold text-center text-sm uppercase tracking-wider text-slate-500">ยานพาหนะ</h3>
                                <div class="grid grid-cols-2 gap-2">
                                    <div><div class="bg-base-200 rounded-lg p-1 flex items-center justify-center h-28"><img src="<?php echo $image_base_path . htmlspecialchars($data['photo_front']); ?>" class="max-w-full max-h-full object-contain rounded-md cursor-pointer" alt="รูปถ่ายรถด้านหน้า" onclick="zoomImage(this.src)"></div><p class="text-xs text-center mt-1 text-gray-500">ด้านหน้า</p></div>
                                    <div><div class="bg-base-200 rounded-lg p-1 flex items-center justify-center h-28"><img src="<?php echo $image_base_path . htmlspecialchars($data['photo_rear']); ?>" class="max-w-full max-h-full object-contain rounded-md cursor-pointer" alt="รูปถ่ายรถด้านหลัง" onclick="zoomImage(this.src)"></div><p class="text-xs text-center mt-1 text-gray-500">ด้านหลัง</p></div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div><div class="text-xs text-slate-500 text-center">เจ้าของบัตร</div><div class="text-center font-bold text-lg"><?php echo htmlspecialchars($data['title'] . $data['firstname'] . ' ' . $data['lastname']); ?></div></div>
                                <div class="divider my-1"></div>
                                <div class="text-sm space-y-1">
                                    <div class="grid grid-cols-2 gap-1"><div class="text-slate-500">ทะเบียน:</div><div class="font-semibold col-span-1"><?php echo htmlspecialchars($data['license_plate'] . ' ' . $data['province']); ?></div></div>
                                    <div class="grid grid-cols-2 gap-1"><div class="text-slate-500">ประเภท:</div><div class="col-span-1"><?php echo htmlspecialchars($data['vehicle_type']); ?></div></div>
                                    <div class="grid grid-cols-2 gap-1"><div class="text-slate-500">ยี่ห้อ/รุ่น:</div><div class="col-span-1"><?php echo htmlspecialchars($data['brand'] . ' / ' . $data['model']); ?></div></div>
                                    <div class="grid grid-cols-2 gap-1"><div class="text-slate-500">สี:</div><div class="col-span-1"><?php echo htmlspecialchars($data['color']); ?></div></div>
                                </div>
                                
                                <?php if ($data['status'] === 'approved'): ?>
                                <div class="divider my-1"></div>
                                <div class="text-sm space-y-1">
                                    <div class="grid grid-cols-2 gap-1"><div class="text-slate-500">รหัสคำร้อง:</div><div class="font-semibold col-span-1"><?php echo htmlspecialchars($data['search_id'] ?: '-'); ?></div></div>
                                    <div class="grid grid-cols-2 gap-1"><div class="text-slate-500">เลขที่บัตร:</div><div class="font-semibold col-span-1"><?php echo htmlspecialchars($data['card_number'] ?: '-'); ?></div></div>
                                    <div class="grid grid-cols-2 gap-1"><div class="text-slate-500">ประเภทบัตร:</div><div class="col-span-1"><?php echo ($is_active_card && !empty($card_type_thai)) ? htmlspecialchars($card_type_thai) : '-'; ?></div></div>
                                    <div class="grid grid-cols-2 gap-1"><div class="text-slate-500">วันอนุมัติ:</div><div class="col-span-1"><?php echo format_thai_date($data['approved_at']); ?></div></div>
                                    <div class="grid grid-cols-2 gap-1"><div class="text-slate-500">วันหมดอายุ:</div><div class="font-semibold col-span-1"><?php echo format_thai_date($data['card_expiry']); ?></div></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card bg-base-100 shadow-xl border border-base-300/50"><div class="card-body p-4 sm:p-6 items-center text-center"><i class="fa-solid fa-circle-xmark text-6xl text-error mb-4"></i><h2 class="card-title text-2xl">ไม่พบข้อมูล</h2><p class="text-gray-600">ไม่พบข้อมูลสำหรับรหัสอ้างอิงนี้ในระบบ<br>กรุณาตรวจสอบความถูกต้องของ QR Code</p></div></div>
            <?php endif; ?>
        </main>
        <footer class="text-center text-slate-500 mt-8"><p class="text-xs">Developed by กยข.กช.</p><p class="text-xs">ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ)</p></footer>
    </div>
    <dialog id="imageZoomModal" class="modal">
        <div class="modal-box w-11/12 max-w-5xl p-0 bg-transparent shadow-none flex justify-center items-center">
            <div id="zoomed-image-container" class="relative">
                <img id="zoomed-image" src="" alt="ขยายรูปภาพ" class="rounded-lg">
                <form method="dialog">
                    <button class="btn btn-circle absolute right-2 top-2 bg-black/25 hover:bg-black/50 text-white border-none text-xl z-10">✕</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
    <script>
        function zoomImage(src) { document.getElementById('zoomed-image').src = src; document.getElementById('imageZoomModal').showModal(); }
        document.addEventListener('DOMContentLoaded', function() {
            const imageZoomModal = document.getElementById('imageZoomModal');
            if (imageZoomModal) {
                imageZoomModal.addEventListener('click', function(e) {
                    const imageContainer = document.getElementById('zoomed-image-container');
                    if (imageContainer && !imageContainer.contains(e.target)) {
                        imageZoomModal.close();
                    }
                });
            }
        });
    </script>
</body>
</html>

