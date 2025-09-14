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
            <div class="bg-base-100 w-64 min-h-full flex flex-col p-4">
                <!-- Sidebar Header -->
                <div class="mb-4">
                    <a href="dashboard.php" class="text-xl font-bold flex items-center gap-3 p-2">
                        <img src="/public/assets/images/CARPASS%20logo.png" alt="Logo" class="h-14 w-14">
                        <div>
                            <div class="font-bold text-base">บัตรผ่านยานพาหนะ</div>
                            <div class="text-xs text-base-content/70">ค่ายภาณุรังษี</div>
                        </div>
                    </a>
                </div>

                <!-- Navigation Menu -->
                <ul class="menu text-base flex-grow space-y-1">
                    <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-pie w-6"></i> ภาพรวม</a></li>
                    <li><a href="add_vehicle.php" class="<?php echo ($current_page == 'add_vehicle.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-circle-plus w-6"></i> เพิ่มยานพาหนะ</a></li>
                    <li><a href="costs.php" class="<?php echo ($current_page == 'costs.php') ? 'active' : ''; ?>"><i class="fa-solid fa-hand-holding-dollar w-6"></i> ค่าใช้จ่าย</a></li>
                    <li><a href="contact.php" class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>"><i class="fa-solid fa-address-book w-6"></i> ติดต่อ</a></li>
                </ul>

                <!-- Sidebar Footer -->
                <div class="mt-auto">
                    <div class="divider my-2"></div>
                    <ul class="menu text-base space-y-1">
                        <li><a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-pen w-6"></i> ข้อมูลส่วนตัว</a></li>
                        <li><a href="../../../controllers/user/logout/logout.php" class="text-error hover:bg-error/10"><i class="fa-solid fa-right-from-bracket w-6"></i> ออกจากระบบ</a></li>
                    </ul>
                    <footer class="text-center p-4 text-base-content/50">
                        <p class="text-xs">Developed by<br>ร.ท.พรหมินทร์ อินทมาตย์</p>
                    </footer>
                </div>
            </div>
        </aside>
    </div> <!-- End of Drawer -->

    <!-- ========= MODALS (Centralized) ========= -->
    
    <!-- [MODIFIED] Image Zoom Modal - Redesigned for fullscreen effect -->
    <dialog id="image_zoom_modal" class="modal">
        <div class="modal-box w-screen h-screen max-w-none max-h-none p-4 bg-transparent shadow-none flex justify-center items-center">
            <div class="relative inline-block">
                <img id="zoomed_image" src="" alt="ขยายรูปภาพ" class="rounded-lg max-h-[95vh] max-w-[95vw] object-contain">
                <form method="dialog" class="absolute top-2 right-2 z-10">
                    <button class="btn btn-circle btn-sm bg-black/50 hover:bg-black/75 text-white border-none">✕</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop bg-black/70"><button>close</button></form>
    </dialog>

    <!-- Request Details Modal -->
    <dialog id="request_details_modal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl">
            <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2 z-20">✕</button></form>
            
            <!-- VIEW DETAILS SECTION -->
            <div id="modal-content-wrapper">
                <!-- Header -->
                <div class="p-4 -m-6 mb-6 rounded-t-box bg-base-200 text-center">
                    <div id="modal-status-badge" class="badge badge-lg"></div>
                    <h3 class="font-bold text-2xl mt-2" id="modal-license-plate"></h3>
                    <p class="text-base-content/70" id="modal-brand-model"></p>
                </div>

                <!-- Rejection Reason Box -->
                <div id="modal-rejection-reason-box" class="alert alert-error alert-soft hidden mb-4">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div>
                        <h3 class="font-bold">เหตุผลที่ไม่ผ่านการอนุมัติ</h3>
                        <p id="modal-rejection-reason-text" class="text-xs"></p>
                    </div>
                </div>
                
                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                    <!-- Main Info Column -->
                    <div class="md:col-span-3 space-y-4">
                        <div id="modal-card-info-list" class="p-4 bg-base-200 rounded-box text-sm space-y-2"></div>
                        <div id="modal-vehicle-info-list" class="p-4 bg-base-200 rounded-box text-sm space-y-2"></div>
                        <div id="modal-owner-info-list" class="p-4 bg-base-200 rounded-box text-sm space-y-2"></div>
                    </div>
                    <!-- Evidence Column -->
                    <div class="md:col-span-2 space-y-4">
                         <div class="p-4 bg-base-200 rounded-box">
                             <h4 class="font-semibold text-sm mb-2 text-center">รูปถ่ายหลักฐาน</h4>
                             <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="text-center"><p class="font-semibold mb-1">ทะเบียนรถ</p><div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24"><img id="modal-photo-reg" src="" class="max-w-full max-h-full object-contain cursor-zoom-in"></div></div>
                                <div class="text-center"><p class="font-semibold mb-1">ป้ายภาษี</p><div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24"><img id="modal-photo-tax" src="" class="max-w-full max-h-full object-contain cursor-zoom-in"></div></div>
                                <div class="text-center"><p class="font-semibold mb-1">ด้านหน้า</p><div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24"><img id="modal-photo-front" src="" class="max-w-full max-h-full object-contain cursor-zoom-in"></div></div>
                                <div class="text-center"><p class="font-semibold mb-1">ด้านหลัง</p><div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24"><img id="modal-photo-rear" src="" class="max-w-full max-h-full object-contain cursor-zoom-in"></div></div>
                            </div>
                         </div>
                        <div id="modal-qr-code-container" class="hidden text-center p-4 bg-base-200 rounded-box">
                             <h4 class="font-semibold text-sm mb-2">QR Code</h4>
                            <img id="modal-qr-code" src="" alt="QR Code" class="w-32 h-32 rounded-lg border bg-white p-1 mx-auto">
                        </div>
                    </div>
                </div>
                <!-- Action Buttons Footer -->
                <div class="modal-action mt-6 pt-4 border-t" id="modal-action-buttons">
                    <!-- Buttons will be dynamically inserted here by JS -->
                </div>
            </div>
            
            <!-- EDIT FORM SECTION -->
            <div id="modal-edit-form-wrapper" class="hidden">
                 <h3 class="font-bold text-lg mb-4">แก้ไขข้อมูลคำร้อง</h3>
                 <form action="../../../controllers/user/vehicle/edit_vehicle_process.php" method="POST" enctype="multipart/form-data" id="editVehicleForm" novalidate>
                    <input type="hidden" name="request_id" id="edit-request-id">
                    <div class="divider divider-start font-semibold">ข้อมูลยานพาหนะ</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control w-full"><div class="label"><span class="label-text">ยี่ห้อรถ</span></div><select name="vehicle_brand" id="edit-vehicle-brand" class="select select-sm select-bordered" required><?php if(isset($car_brands)) foreach ($car_brands as $brand): ?><option value="<?= htmlspecialchars($brand); ?>"><?= htmlspecialchars($brand); ?></option><?php endforeach; ?></select></div>
                        <div class="form-control w-full"><div class="label"><span class="label-text">รุ่นรถ</span></div><input type="text" name="vehicle_model" id="edit-vehicle-model" class="input input-sm input-bordered w-full" required /></div>
                        <div class="form-control w-full"><div class="label"><span class="label-text">สีรถ</span></div><input type="text" name="vehicle_color" id="edit-vehicle-color" class="input input-sm input-bordered w-full" required /></div>
                        <div class="form-control w-full"><div class="label"><span class="label-text">วันสิ้นอายุภาษี</span></div><div class="grid grid-cols-3 gap-2"><select name="tax_day" id="edit-tax-day" class="select select-sm select-bordered" required></select><select name="tax_month" id="edit-tax-month" class="select select-sm select-bordered" required></select><select name="tax_year" id="edit-tax-year" class="select select-sm select-bordered" required></select></div></div>
                    </div>
                    <div class="form-control w-full max-w-xs mt-4"><div class="label"><span class="label-text">เป็นรถของใคร?</span></div><select name="owner_type" id="edit-owner-type" class="select select-sm select-bordered" required><option value="self">รถชื่อตนเอง</option><option value="other">รถคนอื่น</option></select></div>
                    <div id="edit-other-owner-details" class="hidden mt-2"><div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border rounded-lg bg-base-200"><div class="form-control w-full"><div class="label"><span class="label-text">ชื่อ-สกุล เจ้าของ</span></div><input type="text" name="other_owner_name" id="edit-other-owner-name" class="input input-sm input-bordered w-full" /></div><div class="form-control w-full"><div class="label"><span class="label-text">เกี่ยวข้องเป็น</span></div><input type="text" name="other_owner_relation" id="edit-other-owner-relation" class="input input-sm input-bordered w-full" /></div></div></div>
                    <div class="divider divider-start font-semibold mt-6">หลักฐาน (อัปโหลดใหม่เฉพาะที่ต้องการเปลี่ยน)</div>
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control"><label class="label"><span class="label-text">สำเนาทะเบียนรถ</span></label><input type="file" name="reg_copy_upload" class="file-input file-input-bordered file-input-sm w-full" accept=".jpg, .jpeg, .png"></div>
                        <div class="form-control"><label class="label"><span class="label-text">ป้ายภาษี</span></label><input type="file" name="tax_sticker_upload" class="file-input file-input-bordered file-input-sm w-full" accept=".jpg, .jpeg, .png"></div>
                        <div class="form-control"><label class="label"><span class="label-text">รูปถ่ายรถด้านหน้า</span></label><input type="file" name="front_view_upload" class="file-input file-input-bordered file-input-sm w-full" accept=".jpg, .jpeg, .png"></div>
                        <div class="form-control"><label class="label"><span class="label-text">รูปถ่ายรถด้านหลัง</span></label><input type="file" name="rear_view_upload" class="file-input file-input-bordered file-input-sm w-full" accept=".jpg, .jpeg, .png"></div>
                    </div>
                    <div class="modal-action mt-6">
                        <button type="button" id="cancel-edit-btn" class="btn btn-sm btn-ghost">ยกเลิก</button>
                        <button type="submit" class="btn btn-sm btn-primary">บันทึกการแก้ไข</button>
                    </div>
                 </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <!-- Delete Confirmation Modal -->
    <dialog id="delete_confirm_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-error"><i class="fa-solid fa-triangle-exclamation mr-2"></i>ยืนยันการลบคำร้อง</h3>
            <p class="py-4">คุณแน่ใจหรือไม่ว่าต้องการลบคำร้องนี้? การกระทำนี้ไม่สามารถย้อนกลับได้</p>
            <div class="modal-action">
                <form method="dialog"><button class="btn btn-sm">ยกเลิก</button></form>
                <form id="deleteRequestForm" action="../../../controllers/user/vehicle/delete_vehicle_process.php" method="POST">
                    <input type="hidden" name="request_id" id="delete-request-id">
                    <button type="submit" class="btn btn-sm btn-error">ยืนยันการลบ</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
    
    <!-- Loading Modal -->
    <dialog id="loading_modal" class="modal modal-middle">
        <div class="modal-box text-center">
            <span class="loading loading-spinner loading-lg text-primary"></span>
            <h3 class="font-bold text-lg mt-4">กรุณารอสักครู่...</h3>
        </div>
    </dialog>
    
    <!-- Global Alert/Toast Container -->
    <div id="alert-container" class="toast toast-top toast-center z-50"></div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/JQL.min.js"></script>
    <script src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/typeahead.bundle.js"></script>
    <script src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.js"></script>
    <script src="/public/assets/js/script.js?v=<?php echo time(); ?>"></script>

</body>
</html>


