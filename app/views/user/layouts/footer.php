<?php
// app/views/user/layouts/footer.php
// ส่วนท้ายของเว็บไซต์ (Footer), Modals และ Scripts สำหรับผู้ใช้งาน

// [แก้ไข] เป็นจุดเดียวที่ปิดการเชื่อมต่อฐานข้อมูล
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
            <footer class="text-center text-base-content/70 p-4"><p class="text-xs">Developed by กยข.กช.</p><p class="text-xs">ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ)</p></footer>
        </div>
        <div class="drawer-side z-50">
            <label for="my-drawer-3" aria-label="close sidebar" class="drawer-overlay"></label>
            <ul class="menu p-4 w-64 min-h-full bg-base-100" id="mobile-menu">
                <li class="mb-4"><a class="text-lg font-bold flex items-center gap-2"><img src="/public/assets/images/CARPASS%20logo.png" alt="Logo" class="h-8 w-8" onerror="this.onerror=null;this.src='https://placehold.co/32x32/CCCCCC/FFFFFF?text=L';"> ระบบยื่นคำร้อง</a></li>
                <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-pie w-4"></i> ภาพรวม</a></li>
                <li><a href="add_vehicle.php" class="<?php echo ($current_page == 'add_vehicle.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-circle-plus w-4"></i> เพิ่มยานพาหนะ</a></li>
                <li><a href="costs.php" class="<?php echo ($current_page == 'costs.php') ? 'active' : ''; ?>"><i class="fa-solid fa-hand-holding-dollar w-4"></i> ค่าใช้จ่าย</a></li>
                <li><a href="contact.php" class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>"><i class="fa-solid fa-address-book w-4"></i> ติดต่อ</a></li>
                <li><a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-pen w-4"></i> ข้อมูลส่วนตัว</a></li>
                <div class="divider"></div>
                <li><a href="../../../controllers/user/logout/logout.php"><i class="fa-solid fa-right-from-bracket w-4"></i> ออกจากระบบ</a></li>
            </ul>
        </div>
    </div>

    <!-- Modals and Scripts go here -->
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/JQL.min.js"></script>
    <script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/typeahead.bundle.js"></script>
    <script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.js"></script>
    <script>
        // All JavaScript code for user pages
    </script>
</body>
</html>

