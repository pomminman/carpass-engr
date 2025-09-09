<?php
// app/views/admin/home/manage_users.php
require_once __DIR__ . '/../layouts/header.php';

// --- Data fetching specific to this page ---
$users = [];
$sql_users = "SELECT id, title, firstname, lastname, user_type, phone_number, national_id, work_department FROM users ORDER BY created_at DESC";
$result_users = $conn->query($sql_users);
if ($result_users->num_rows > 0) {
    while($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!-- Page content -->
<main class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
    <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-users-cog text-primary"></i> จัดการผู้ใช้งาน</h1>
    <p class="text-slate-500 mb-6">ดูและจัดการข้อมูลผู้ใช้งานในระบบ</p>
    
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            <div class="flex flex-col sm:flex-row justify-end items-start sm:items-center gap-4">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                   <input type="text" id="searchInput" placeholder="ค้นหา..." class="input input-sm input-bordered w-full sm:w-auto">
                </div>
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="table table-sm" id="usersTable">
                     <thead class="bg-slate-50">
                        <tr>
                            <th>ชื่อ-นามสกุล</th>
                            <th>ประเภท</th>
                            <th>เบอร์โทรศัพท์</th>
                            <th>เลขบัตรประชาชน</th>
                            <th>สังกัด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="5" class="text-center text-slate-500 py-4">ไม่พบข้อมูลผู้ใช้งาน</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="font-semibold whitespace-nowrap"><?php echo htmlspecialchars($user['title'] . $user['firstname'] . ' ' . $user['lastname']); ?></td>
                                <td class="whitespace-nowrap"><?php echo $user['user_type'] === 'army' ? 'กำลังพล' : 'บุคคลภายนอก'; ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($user['national_id']); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($user['work_department'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <tr id="no-results-row" class="hidden"><td colspan="5" class="text-center text-slate-500 py-4">ไม่พบข้อมูลผู้ใช้ที่ค้นหา</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
