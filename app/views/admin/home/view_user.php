<?php
// app/views/admin/home/view_user.php
require_once __DIR__ . '/../layouts/header.php';

// 1. Get and validate user ID from URL
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id) {
    header("Location: manage_users.php");
    exit;
}

// 2. Fetch user's personal information AND creator admin's name
$user_sql = "SELECT 
                u.*, 
                a.title AS creator_title, 
                a.firstname AS creator_firstname, 
                a.lastname AS creator_lastname 
             FROM users u
             LEFT JOIN admins a ON u.created_by_admin_id = a.id
             WHERE u.id = ?";

$stmt_user = $conn->prepare($user_sql);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
if ($user_result->num_rows !== 1) {
    header("Location: manage_users.php");
    exit;
}
$user = $user_result->fetch_assoc();
$stmt_user->close();

// 3. Fetch all vehicle requests for this user
$requests = [];
$requests_sql = "SELECT 
                    vr.id, vr.search_id, vr.status, vr.created_at,
                    v.license_plate, v.province, v.vehicle_type
                 FROM vehicle_requests vr
                 JOIN vehicles v ON vr.vehicle_id = v.id
                 WHERE vr.user_id = ?
                 ORDER BY vr.created_at DESC";
$stmt_requests = $conn->prepare($requests_sql);
$stmt_requests->bind_param("i", $user_id);
$stmt_requests->execute();
$requests_result = $stmt_requests->get_result();
if ($requests_result->num_rows > 0) {
    while($row = $requests_result->fetch_assoc()) {
        $requests[] = $row;
    }
}
$stmt_requests->close();

// Helper for user type text
$user_type_thai = $user['user_type'] === 'army' ? 'ข้าราชการ/ลูกจ้าง/พนักงานราชการ ทบ.' : 'บุคคลภายนอก';

// Helper function to format value or return a dash
function format_value($value, $default = '-') {
    return (isset($value) && trim($value) !== '') ? htmlspecialchars($value) : $default;
}
?>

<!-- Page content -->
<main id="view-user-page" class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-4">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">
                <i class="fa-solid fa-user-check text-primary"></i> ข้อมูลผู้ใช้งาน
            </h1>
            <p class="text-slate-500">รายละเอียดข้อมูลส่วนตัวและยานพาหนะที่เกี่ยวข้อง</p>
        </div>
        <div class="flex items-center gap-2">
             <button id="edit-user-btn" class="btn btn-sm btn-warning">
                <i class="fa-solid fa-pencil"></i> แก้ไขข้อมูล
            </button>
            <a href="manage_users.php" class="btn btn-sm btn-ghost">
                <i class="fa-solid fa-arrow-left"></i> กลับไปหน้าจัดการผู้ใช้
            </a>
        </div>
    </div>

    <!-- User Profile Card -->
    <div class="card bg-base-100 shadow-lg mb-6">
        <div class="card-body">
            <div class="flex items-start gap-6">
                <div class="avatar flex-shrink-0">
                    <div class="w-28 rounded-lg">
                        <?php if (!empty($user['photo_profile'])): ?>
                            <img src="/public/uploads/<?php echo htmlspecialchars($user['user_key']); ?>/profile/<?php echo htmlspecialchars($user['photo_profile']); ?>" />
                        <?php else: ?>
                            <img src="https://placehold.co/300x300/e2e8f0/475569?text=No+Image" />
                        <?php endif; ?>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3 text-sm flex-grow">
                    <div><strong>ชื่อ-สกุล:</strong> <span class="font-semibold"><?php echo format_value($user['title'] . $user['firstname'] . '  ' . $user['lastname']); ?></span></div>
                    <div><strong>ประเภท:</strong> <span><?php echo $user_type_thai; ?></span></div>
                    <div><strong>เบอร์โทร:</strong> <span><?php echo format_value($user['phone_number']); ?></span></div>
                    <div><strong>เลขบัตรประชาชน:</strong> <span><?php echo format_value($user['national_id']); ?></span></div>
                    <div class="col-span-full"><strong>ที่อยู่:</strong> <span><?php echo format_value("{$user['address']} ต.{$user['subdistrict']} อ.{$user['district']} จ.{$user['province']} {$user['zipcode']}"); ?></span></div>
                    <?php if ($user['user_type'] === 'army'): ?>
                        <div><strong>สังกัด:</strong> <span><?php echo format_value($user['work_department']); ?></span></div>
                        <div><strong>ตำแหน่ง:</strong> <span><?php echo format_value($user['position']); ?></span></div>
                        <div><strong>เลขบัตร ขรก.:</strong> <span><?php echo format_value($user['official_id']); ?></span></div>
                    <?php endif; ?>

                    <?php if (!empty($user['created_by_admin_id'])): ?>
                        <div class="col-span-full mt-2 pt-2 border-t border-base-200">
                            <strong>สร้างโดยเจ้าหน้าที่:</strong> 
                            <span class="font-semibold text-info">
                                <?php echo htmlspecialchars(format_value($user['creator_title'] . $user['creator_firstname'] . '  ' . $user['creator_lastname'])); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Vehicle Requests Table (omitted for brevity as it's unchanged) -->
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            <h2 class="card-title text-lg"><i class="fa-solid fa-car"></i> รายการยานพาหนะทั้งหมด</h2>
            <div class="overflow-x-auto mt-4">
                <table class="table table-sm" id="requestsTable">
                    <thead class="bg-slate-50">
                        <tr>
                            <th>รหัสคำร้อง</th>
                            <th>ทะเบียนรถ</th>
                            <th>ประเภทรถ</th>
                            <th>วันที่ยื่น</th>
                            <th>สถานะ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr><td colspan="6" class="text-center text-slate-500 py-4">ไม่พบข้อมูลคำร้องสำหรับผู้ใช้งานนี้</td></tr>
                        <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="font-semibold whitespace-nowrap"><?php echo htmlspecialchars($req['search_id']); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['license_plate'] . ' ' . $req['province']); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($req['vehicle_type']); ?></td>
                                <td class="whitespace-nowrap"><?php echo format_thai_datetime($req['created_at']); ?></td>
                                <td>
                                    <?php
                                        $status_badge = '';
                                        switch ($req['status']) {
                                            case 'pending': $status_badge = '<div class="badge badge-warning">รออนุมัติ</div>'; break;
                                            case 'approved': $status_badge = '<div class="badge badge-success">อนุมัติแล้ว</div>'; break;
                                            case 'rejected': $status_badge = '<div class="badge badge-error">ไม่ผ่าน</div>'; break;
                                        }
                                        echo $status_badge;
                                    ?>
                                </td>
                                <td class="whitespace-nowrap">
                                    <button class="btn btn-xs btn-primary inspect-btn" data-id="<?php echo $req['id']; ?>">
                                        <span><i class="fa-solid fa-search mr-1"></i>ดูรายละเอียด</span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?php
// Re-include the edit modal logic from previous steps
require_once __DIR__ . '/../components/edit_user_modal.php'; 
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

