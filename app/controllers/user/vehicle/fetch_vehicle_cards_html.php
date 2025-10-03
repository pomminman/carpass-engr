<?php
// app/controllers/user/vehicle/fetch_vehicle_cards_html.php
session_start();
date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

require_once '../../../models/db_config.php';

// [MODIFIED] Helper function to format date/time with a 2-digit Thai year
function formatThaiDateTime($dateTimeString) {
    if (!$dateTimeString || strpos($dateTimeString, '0000-00-00') === 0) {
        return '-';
    }
    $date = new DateTime($dateTimeString);
    $day = $date->format('d');
    $month = $date->format('m');
    $yearBE = (int)$date->format('Y') + 543;
    $shortYear = substr((string)$yearBE, -2); // Get the last 2 digits of the Buddhist year
    $time = $date->format('H:i');
    return "$day/$month/$shortYear, $time"; // Changed format to DD/MM/YY
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    exit;
}
$conn->set_charset("utf8");

$user_id = $_SESSION['user_id'];

$sql = "SELECT 
            vr.id as request_id, vr.status, vr.card_expiry, vr.created_at,
            vr.card_number, vr.search_id,
            vr.photo_front_thumb, vr.photo_rear_thumb,
            v.license_plate, v.province, v.brand, v.model,
            u.user_key, vr.request_key
        FROM vehicle_requests vr
        JOIN users u ON vr.user_id = u.id
        JOIN vehicles v ON vr.vehicle_id = v.id
        WHERE vr.user_id = ?
        ORDER BY vr.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $is_expired = !empty($row['card_expiry']) && (new DateTime() > new DateTime($row['card_expiry']));
        $status_key = ($row['status'] === 'approved' && $is_expired) ? 'expired' : $row['status'];

        $status_map = [
            'approved' => ['icon' => 'fa-solid fa-check-circle', 'text' => 'อนุมัติแล้ว', 'badge_bg' => 'bg-success/10 text-success', 'border_color' => 'border-success'],
            'pending' => ['icon' => 'fa-solid fa-clock', 'text' => 'รออนุมัติ', 'badge_bg' => 'bg-warning/10 text-warning', 'border_color' => 'border-warning'],
            'rejected' => ['icon' => 'fa-solid fa-ban', 'text' => 'ไม่ผ่าน', 'badge_bg' => 'bg-error/10 text-error', 'border_color' => 'border-error'],
            'expired' => ['icon' => 'fa-solid fa-calendar-xmark', 'text' => 'หมดอายุ', 'badge_bg' => 'bg-base-300 text-base-content', 'border_color' => 'border-base-300']
        ];
        $status_info = $status_map[$status_key];
        $base_path = "/public/uploads/{$row['user_key']}/vehicle/{$row['request_key']}/";
        ?>
        <div class="card bg-base-100 shadow-md hover:shadow-xl transition-shadow duration-300 ease-in-out cursor-pointer vehicle-card overflow-hidden border-2 <?php echo $status_info['border_color']; ?>" 
             data-request-id="<?php echo $row['request_id']; ?>"
             data-status-key="<?php echo $status_key; ?>">
            
            <div class="grid grid-cols-2 gap-px bg-base-300">
                <figure class="bg-base-200">
                    <img src="<?php echo $base_path . htmlspecialchars($row['photo_front_thumb']); ?>" 
                         alt="รูปถ่ายด้านหน้า" 
                         class="w-full h-24 object-cover" 
                         loading="lazy"
                         onerror="this.onerror=null;this.src='https://placehold.co/200x150/e2e8f0/475569?text=Front';">
                </figure>
                <figure class="bg-base-200">
                     <img src="<?php echo $base_path . htmlspecialchars($row['photo_rear_thumb']); ?>" 
                         alt="รูปถ่ายด้านหลัง" 
                         class="w-full h-24 object-cover" 
                         loading="lazy"
                         onerror="this.onerror=null;this.src='https://placehold.co/200x150/e2e8f0/475569?text=Rear';">
                </figure>
            </div>

            <div class="card-body p-3">
                <div class="flex justify-between items-start gap-2">
                    <div>
                        <h2 class="card-title text-base font-bold leading-tight"><?php echo htmlspecialchars($row['license_plate']); ?></h2>
                        <p class="text-xs text-base-content/70"><?php echo htmlspecialchars($row['province']); ?></p>
                    </div>
                    <div class="p-2 rounded-lg inline-flex items-center gap-2 text-xs font-semibold <?php echo $status_info['badge_bg']; ?>">
                        <i class="<?php echo $status_info['icon']; ?>"></i>
                        <span class="whitespace-nowrap"><?php echo $status_info['text']; ?></span>
                    </div>
                </div>
                <p class="text-sm text-base-content/80 mt-1 truncate"><?php echo htmlspecialchars($row['brand'] . ' / ' . $row['model']); ?></p>
                
                <div class="text-xs mt-2 pt-2 border-t border-base-200 space-y-1">
                    <div class="flex justify-between">
                        <span class="text-base-content/60">รหัสคำร้อง:</span>
                        <span class="font-semibold text-base-content truncate"><?php echo htmlspecialchars($row['search_id'] ?? '-'); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/60">ยื่นคำร้อง:</span>
                        <span class="font-semibold text-base-content"><?php echo formatThaiDateTime($row['created_at']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/60">เลขที่บัตร:</span>
                        <span class="font-semibold text-base-content"><?php echo htmlspecialchars($row['card_number'] ?? '-'); ?></span>
                    </div>
                </div>
            </div>

        </div>
        <?php
    }
}
$conn->close();
?>

