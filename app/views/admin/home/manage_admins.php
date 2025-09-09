<?php
// app/views/admin/home/manage_admins.php
require_once __DIR__ . '/../layouts/header.php';

// --- Check permission for this specific page ---
if (!in_array($admin_info['role'], ['admin', 'superadmin'])) {
    header("Location: dashboard.php");
    exit;
}

// --- Data fetching specific to this page ---
$admins = [];
$sql_admins = "SELECT id, title, firstname, lastname, department, role, view_permission FROM admins ORDER BY created_at DESC";
$result_admins = $conn->query($sql_admins);
if ($result_admins->num_rows > 0) {
    while($row = $result_admins->fetch_assoc()) {
        $admins[] = $row;
    }
}
?>

<!-- Page content -->
<main class="flex-1 p-4 md:p-6 lg:p-8 pb-24">
    <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fa-solid fa-user-shield text-primary"></i> จัดการเจ้าหน้าที่</h1>
    <p class="text-slate-500 mb-6">เพิ่ม ลบ และแก้ไขข้อมูลเจ้าหน้าที่ในระบบ</p>
    
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
             <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="card-title text-base"><i class="fa-solid fa-users"></i> รายชื่อเจ้าหน้าที่</h2>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="text" id="searchInput" placeholder="ค้นหา..." class="input input-sm input-bordered w-full sm:w-auto">
                    <?php if (in_array($admin_info['role'], ['admin', 'superadmin'])): ?>
                    <button class="btn btn-primary btn-sm" onclick="add_admin_modal.showModal()"><i class="fa-solid fa-user-plus"></i> เพิ่มเจ้าหน้าที่</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="overflow-x-auto mt-4">
                <table class="table table-sm" id="adminsTable">
                    <thead class="bg-slate-50">
                        <tr>
                            <th>ชื่อ-นามสกุล</th>
                            <th>สังกัด</th>
                            <th>ระดับสิทธิ์</th>
                            <th>สิทธิ์เข้าถึง</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="font-semibold"><?php echo htmlspecialchars($admin['title'] . $admin['firstname'] . ' ' . $admin['lastname']); ?></td>
                            <td><?php echo htmlspecialchars($admin['department']); ?></td>
                            <td><div class="badge <?php echo $admin['role'] === 'superadmin' ? 'badge-secondary' : 'badge-primary'; ?>"><?php echo ucfirst(htmlspecialchars($admin['role'])); ?></div></td>
                            <td><?php echo $admin['view_permission'] == 1 ? 'ทุกสังกัด' : 'เฉพาะสังกัด'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr id="no-results-row" class="hidden"><td colspan="4" class="text-center text-slate-500 py-4">ไม่พบข้อมูลเจ้าหน้าที่ที่ค้นหา</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
