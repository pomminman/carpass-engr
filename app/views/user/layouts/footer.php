<?php
// app/views/user/layouts/footer.php
// Close DB connection if it exists
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
            </main> <!-- End of Main Content Area -->
        </div> <!-- End of Drawer Content -->

        <!-- Sidebar Section -->
        <aside class="drawer-side z-40 lg:z-auto">
            <label for="my-drawer-2" aria-label="close sidebar" class="drawer-overlay"></label> 
            <div class="bg-base-100 w-52 min-h-full flex flex-col py-4 shadow-lg">
                <!-- Sidebar Header -->
                <div class="mb-4">
                    <a href="dashboard.php" class="text-xl font-bold flex items-center gap-2 pl-4">
                        <img src="/public/assets/images/CARPASS%20logo.png" alt="Logo" class="h-12 w-12">
                        <div>
                            <div class="font-bold text-sm">บัตรผ่านยานพาหนะ</div>
                            <div class="text-xs text-base-content/70">ค่ายภาณุรังษี</div>
                        </div>
                    </a>
                </div>

                <!-- Navigation Menu -->
                <ul class="menu flex-grow space-y-1">
                    <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?> hover:bg-base-200"><i class="fa-solid fa-chart-pie w-6"></i> ภาพรวม</a></li>
                    <li><a href="add_vehicle.php" class="<?php echo ($current_page == 'add_vehicle.php') ? 'active' : ''; ?> hover:bg-base-200"><i class="fa-solid fa-file-circle-plus w-6"></i> เพิ่มยานพาหนะ</a></li>
                    <li><a href="costs.php" class="<?php echo ($current_page == 'costs.php') ? 'active' : ''; ?> hover:bg-base-200"><i class="fa-solid fa-hand-holding-dollar w-6"></i> ค่าธรรมเนียม</a></li>
                    <li><a href="contact.php" class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?> hover:bg-base-200"><i class="fa-solid fa-address-book w-6"></i> ติดต่อ</a></li>
                </ul>

                <!-- Sidebar Footer -->
                <div class="mt-auto">
                    <div class="divider my-2 px-2"></div>
                    <ul class="menu space-y-1">
                        <li><a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?> hover:bg-base-200"><i class="fa-solid fa-user-pen w-6"></i> ข้อมูลส่วนตัว</a></li>
                        <li><a href="../../../controllers/user/logout/logout.php" class="text-error hover:bg-error/10"><i class="fa-solid fa-right-from-bracket w-6"></i> ออกจากระบบ</a></li>
                    </ul>
                    <footer class="text-center p-4 text-base-content/50">
                        <p class="text-[10px]">Developed by กยข.กช. <br>ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ)</p>
                    </footer>
                </div>
            </div>
        </aside>
    </div> <!-- End of Drawer -->

    <!-- Global Alert/Toast Container -->
    <div id="alert-container" class="toast toast-top toast-center z-50"></div>
    
    <!-- Scripts -->
    <script src="/lib/jquery.Thailand/dependencies/JQL.min.js"></script>
    <script src="/lib/jquery.Thailand/dependencies/typeahead.bundle.js"></script>
    <script src="/lib/jquery.Thailand/dist/jquery.Thailand.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/photoswipe/5.4.3/photoswipe.umd.min.js"></script>
    <script src="/public/assets/js/user_script.js"></script>

</body>
</html>
