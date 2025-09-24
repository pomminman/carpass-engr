<?php
// app/views/admin/home/manage_users.php
require_once __DIR__ . '/../layouts/header.php';

// Get selected user type from URL
$selected_user_type = $_GET['type'] ?? 'all';
$where_clause = '';
$params = [];
$types = '';
if ($selected_user_type !== 'all') {
    $where_clause = 'WHERE user_type = ?';
    $params[] = $selected_user_type;
    $types .= 's';
}

// Data fetching for users table
$users = [];
$sql_users = "SELECT id, title, firstname, lastname, user_type, phone_number, national_id, work_department, created_at FROM users $where_clause ORDER BY created_at DESC";
$stmt_users = $conn->prepare($sql_users);
if (!empty($params)) {
    $stmt_users->bind_param($types, ...$params);
}
$stmt_users->execute();
$result_users = $stmt_users->get_result();
if ($result_users->num_rows > 0) {
    while($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
}
$stmt_users->close();
?>

<!-- Page content -->
<main id="manage-users-page" class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
    <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-users-cog text-primary"></i> จัดการผู้ใช้งาน</h1>
    <p class="text-slate-500 mb-6">ดูและจัดการข้อมูลผู้ใช้งานในระบบ</p>
    
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="tabs tabs-boxed">
                    <a href="?type=all" class="tab tab-sm sm:tab-md <?php echo ($selected_user_type == 'all' ? 'tab-active' : ''); ?>">ทั้งหมด</a>
                    <a href="?type=army" class="tab tab-sm sm:tab-md <?php echo ($selected_user_type == 'army' ? 'tab-active' : ''); ?>">กำลังพล ทบ.</a>
                    <a href="?type=external" class="tab tab-sm sm:tab-md <?php echo ($selected_user_type == 'external' ? 'tab-active' : ''); ?>">บุคคลภายนอก</a>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                   <input type="text" id="searchInput" placeholder="ค้นหา..." class="input input-sm input-bordered w-full sm:w-auto">
                </div>
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="table table-sm" id="usersTable">
                     <thead class="bg-slate-50">
                        <tr>
                            <th class="md:hidden">การกระทำ</th>
                            <th data-sort-by="name">ชื่อ-นามสกุล <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="type">ประเภท <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="phone">เบอร์โทรศัพท์ <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="nid">เลขบัตรประชาชน <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="department">สังกัด <i class="fa-solid fa-sort"></i></th>
                            <th data-sort-by="date" class="sort-desc">วันที่สมัคร <i class="fa-solid fa-sort-down"></i></th>
                            <th class="hidden md:table-cell">การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr id="no-results-row"><td colspan="8" class="text-center text-slate-500 py-4">ไม่พบข้อมูลผู้ใช้งาน</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap md:hidden">
                                    <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-xs btn-info"><i class="fa-solid fa-search"></i> ตรวจสอบ</a>
                                </td>
                                <td data-cell="name" class="font-semibold whitespace-nowrap"><?php echo htmlspecialchars($user['title'] . ' ' . $user['firstname'] . ' ' . $user['lastname']); ?></td>
                                <td data-cell="type" class="whitespace-nowrap">
                                    <?php if ($user['user_type'] === 'army'): ?>
                                        <div class="badge badge-success badge-outline gap-2"><i class="fa-solid fa-shield-halved"></i>กำลังพล ทบ.</div>
                                    <?php else: ?>
                                        <div class="badge badge-info badge-outline gap-2"><i class="fa-solid fa-user-group"></i>บุคคลภายนอก</div>
                                    <?php endif; ?>
                                </td>
                                <td data-cell="phone" class="whitespace-nowrap"><?php echo htmlspecialchars($user['phone_number'] ?: '-'); ?></td>
                                <td data-cell="nid" class="whitespace-nowrap"><?php echo htmlspecialchars($user['national_id'] ?: '-'); ?></td>
                                <td data-cell="department" class="whitespace-nowrap"><?php echo htmlspecialchars($user['work_department'] ?? '-'); ?></td>
                                <td data-cell="date" class="whitespace-nowrap" data-sort-value="<?php echo strtotime($user['created_at']); ?>"><?php echo format_thai_datetime($user['created_at']); ?></td>
                                <td class="whitespace-nowrap hidden md:table-cell">
                                    <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-xs btn-info"><i class="fa-solid fa-search"></i> ตรวจสอบ</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <tr id="no-search-results-row" class="hidden"><td colspan="8" class="text-center text-slate-500 py-4">ไม่พบข้อมูลผู้ใช้ที่ค้นหา</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
