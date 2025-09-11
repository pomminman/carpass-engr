<?php
// app/views/user/layouts/footer.php
// ส่วนท้ายของเว็บไซต์ (Footer), Modals และ Scripts สำหรับผู้ใช้งาน
if (isset($conn)) {
    $conn->close();
}
?>
            <!-- Footer -->
            <footer class="bg-base-200 text-base-content shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] p-1 text-center z-40">
                <p class="text-[10px] sm:text-xs whitespace-nowrap">Developed by ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ/กยข.กช.)</p>
            </footer>
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

    <!-- Modals -->
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
    <dialog id="vehicleDetailModal" class="modal">
        <div class="modal-box w-11/12 max-w-7xl">
            <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2 text-xl bg-base-200/50 hover:bg-base-200/80 z-10">✕</button></form>
            <div id="modal-status-header" class="p-4 rounded-t-lg text-center">
                <h3 class="font-bold text-lg" id="modal-status-text"></h3>
                <p class="text-xs" id="modal-status-reason"></p>
            </div>
            <div class="card bg-base-100 rounded-t-none">
                 <div class="card-body p-4">
                     <div id="modal-pickup-date-info" class="hidden"></div>
                     <div class="card bg-base-200 shadow-inner mb-4">
                        <div class="card-body p-3 text-center">
                            <p class="text-xs text-slate-500">รหัสคำร้อง</p>
                            <p class="text-lg font-semibold" id="modal-search-id"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div id="modal-vehicle-info"></div>
                            <div id="modal-owner-info" class="mt-4"></div>
                            <div id="modal-qr-code" class="mt-4"></div>
                        </div>
                        <div id="modal-evidence-photos"></div>
                    </div>
                 </div>
            </div>
            <div class="modal-action bg-base-200 p-2 rounded-b-lg -m-4 mt-4" id="modal-action-buttons"></div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
    <dialog id="editVehicleModal" class="modal"><div class="modal-box w-11/12 max-w-7xl"><form method="dialog"><button id="close-edit-modal-btn" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form><h3 class="font-bold text-lg">แก้ไขข้อมูลคำร้อง</h3><div class="py-4"><form action="../../../controllers/user/vehicle/edit_vehicle_process.php" method="POST" enctype="multipart/form-data" id="editVehicleForm" novalidate><input type="hidden" name="request_id" id="edit-request-id"><input type="hidden" name="user_key" id="edit-user-key"><input type="hidden" name="request_key" id="edit-request-key"><div class="divider divider-start font-semibold">ข้อมูลยานพาหนะ</div><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"><div class="form-control w-full"><div class="label"><span class="label-text">ประเภทรถ</span></div><div id="edit-vehicle-type" class="input input-sm input-bordered input-disabled flex items-center"></div></div><div class="form-control w-full"><div class="label"><span class="label-text">ยี่ห้อรถ</span></div><select name="vehicle_brand" id="edit-vehicle-brand" class="select select-sm select-bordered" required><?php if(isset($car_brands)) foreach ($car_brands as $brand): ?><option value="<?php echo htmlspecialchars($brand); ?>"><?php echo htmlspecialchars($brand); ?></option><?php endforeach; ?></select><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">รุ่นรถ</span></div><input type="text" name="vehicle_model" id="edit-vehicle-model" class="input input-sm input-bordered w-full" required /><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">สีรถ</span></div><input type="text" name="vehicle_color" id="edit-vehicle-color" class="input input-sm input-bordered w-full" required /><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">เลขทะเบียนรถ</span></div><div id="edit-license-plate" class="input input-sm input-bordered input-disabled flex items-center"></div></div><div class="form-control w-full"><div class="label"><span class="label-text">จังหวัด</span></div><div id="edit-license-province" class="input input-sm input-bordered input-disabled flex items-center"></div></div><div class="form-control w-full lg:col-span-2"><div class="label"><span class="label-text">วันสิ้นอายุภาษี</span></div><div class="grid grid-cols-3 gap-2"><select name="tax_day" id="edit-tax-day" class="select select-sm select-bordered" required></select><select name="tax_month" id="edit-tax-month" class="select select-sm select-bordered" required></select><select name="tax_year" id="edit-tax-year" class="select select-sm select-bordered" required></select></div><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">เป็นรถของใคร?</span></div><select name="owner_type" id="edit-owner-type" class="select select-sm select-bordered" required><option value="self">รถชื่อตนเอง</option><option value="other">รถคนอื่น</option></select><p class="error-message hidden"></p></div></div><div id="edit-other-owner-details" class="hidden mt-4"><div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border rounded-lg bg-base-200"><div class="form-control w-full"><div class="label"><span class="label-text">ชื่อ-สกุล เจ้าของ</span></div><input type="text" name="other_owner_name" id="edit-other-owner-name" class="input input-sm input-bordered w-full" /><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">เกี่ยวข้องเป็น</span></div><input type="text" name="other_owner_relation" id="edit-other-owner-relation" class="input input-sm input-bordered w-full" /><p class="error-message hidden"></p></div></div></div><div class="divider divider-start font-semibold mt-8">หลักฐานรูปถ่าย (อัปโหลดใหม่เฉพาะที่ต้องการเปลี่ยน)</div><div class="grid grid-cols-1 lg:grid-cols-2 gap-6"><div class="form-control"><label class="block font-medium mb-2">สำเนาทะเบียนรถ</label><img id="edit-reg-copy-preview" src="" class="w-full h-40 object-contain rounded-lg border p-2 mb-2"><input type="file" name="reg_copy_upload" id="edit-reg-copy-upload" class="file-input file-input-bordered file-input-sm" accept=".jpg, .jpeg, .png"><p class="error-message hidden"></p></div><div class="form-control"><label class="block font-medium mb-2">ป้ายภาษี</label><img id="edit-tax-sticker-preview" src="" class="w-full h-40 object-contain rounded-lg border p-2 mb-2"><input type="file" name="tax_sticker_upload" id="edit-tax-sticker-upload" class="file-input file-input-bordered file-input-sm" accept=".jpg, .jpeg, .png"><p class="error-message hidden"></p></div><div class="form-control"><label class="block font-medium mb-2">รูปถ่ายรถด้านหน้า</label><img id="edit-front-view-preview" src="" class="w-full h-40 object-contain rounded-lg border p-2 mb-2"><input type="file" name="front_view_upload" id="edit-front-view-upload" class="file-input file-input-bordered file-input-sm" accept=".jpg, .jpeg, .png"><p class="error-message hidden"></p></div><div class="form-control"><label class="block font-medium mb-2">รูปถ่ายรถด้านหลัง</label><img id="edit-rear-view-preview" src="" class="w-full h-40 object-contain rounded-lg border p-2 mb-2"><input type="file" name="rear_view_upload" id="edit-rear-view-upload" class="file-input file-input-bordered file-input-sm" accept=".jpg, .jpeg, .png"><p class="error-message hidden"></p></div></div><div class="modal-action mt-6"><button type="button" id="cancel-edit-vehicle-btn" class="btn btn-sm btn-ghost">ยกเลิก</button><button type="submit" class="btn btn-success btn-sm">ยืนยันการแก้ไข</button></div></form></div></div></dialog>
    <dialog id="resetConfirmModal" class="modal"><div class="modal-box"><h3 class="font-bold text-lg">ยืนยันการล้างข้อมูล</h3><p class="py-4">คุณแน่ใจหรือไม่ว่าต้องการล้างข้อมูลในฟอร์มทั้งหมด?</p><div class="modal-action"><form method="dialog"><button class="btn btn-sm">ยกเลิก</button></form><button id="confirm-reset-btn" class="btn btn-error btn-sm">ยืนยัน</button></div></div><form method="dialog" class="modal-backdrop"><button>close</button></form></dialog>
    <dialog id="resetCheckConfirmModal" class="modal"><div class="modal-box"><h3 class="font-bold text-lg">ยืนยันการล้างข้อมูล</h3><p class="py-4">คุณแน่ใจหรือไม่ว่าต้องการล้างข้อมูลในขั้นตอนนี้?</p><div class="modal-action"><form method="dialog"><button class="btn btn-sm">ยกเลิก</button></form><button id="confirm-reset-check-btn" class="btn btn-error btn-sm">ยืนยัน</button></div></div><form method="dialog" class="modal-backdrop"><button>close</button></form></dialog>
    <dialog id="loadingModal" class="modal modal-middle"><div class="modal-box text-center"><span class="loading loading-spinner loading-lg text-primary"></span><h3 class="font-bold text-lg mt-4">กรุณารอสักครู่</h3><p class="py-4">ระบบกำลังบันทึกข้อมูล...<br>กรุณาอย่าปิดหรือรีเฟรชหน้านี้</p></div></dialog>
    <dialog id="duplicateVehicleModal" class="modal"><div class="modal-box"><div class="alert alert-warning alert-soft"><i class="fa-solid fa-triangle-exclamation text-2xl"></i><div><h3 class="font-bold text-lg">ข้อมูลซ้ำซ้อน</h3><p class="py-2 text-sm" id="duplicateVehicleMessage"></p></div></div><div class="modal-action justify-center"><form method="dialog"><button class="btn btn-warning btn-outline btn-sm">รับทราบ</button></form></div></div><form method="dialog" class="modal-backdrop"><button>close</button></form></dialog>
    <dialog id="addVehicleConfirmModal" class="modal">
        <div class="modal-box w-11/12 max-w-7xl">
            <h3 class="font-bold text-lg">โปรดตรวจสอบข้อมูลยานพาหนะ</h3>
            <div id="add-vehicle-summary-content" class="py-4 space-y-4 text-sm"></div>
            <div class="modal-action">
              <form method="dialog"><button class="btn btn-sm">แก้ไข</button></form>
              <button id="final-add-vehicle-submit-btn" class="btn btn-sm btn-success">ยืนยันและส่งข้อมูล</button>
            </div>
        </div>
    </dialog>
    <dialog id="deleteConfirmModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-error">ยืนยันการลบคำร้อง</h3>
            <p class="py-4">คุณแน่ใจหรือไม่ว่าต้องการลบคำร้องนี้? การกระทำนี้ไม่สามารถย้อนกลับได้ และข้อมูลทั้งหมดจะถูกลบอย่างถาวร</p>
            <div class="modal-action">
                <form method="dialog"><button class="btn btn-sm">ยกเลิก</button></form>
                <form id="deleteRequestForm" action="../../../controllers/user/vehicle/delete_vehicle_process.php" method="POST" onsubmit="document.getElementById('loadingModal').showModal()">
                    <input type="hidden" name="request_id" id="delete-request-id">
                    <button id="confirm-delete-btn" type="submit" class="btn btn-sm btn-error">ยืนยันการลบ</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <div id="alert-container" class="toast toast-top toast-center z-50"></div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/JQL.min.js"></script>
    <script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/typeahead.bundle.js"></script>
    <script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.js"></script>
    <script>
        const userDob = <?php echo isset($user['dob']) && $user['dob'] ? json_encode(['day' => (int)date('d', strtotime($user['dob'])), 'month' => (int)date('m', strtotime($user['dob'])), 'year' => (int)date('Y', strtotime($user['dob'])) + 543]) : 'null'; ?>;
        const currentUserId = <?php echo json_encode($user_id); ?>;

        (function() {
            // --- 1. General Helper Functions ---
            function showAlert(message, type = 'info') {
                const alertContainer = document.getElementById('alert-container');
                if (!alertContainer) return;
                const alertId = `alert-${Date.now()}`;
                const alertElement = document.createElement('div');
                alertElement.id = alertId;
                let icon = ''; let alertClass = '';
                if (type === 'error') { icon = '<i class="fa-solid fa-circle-xmark"></i>'; alertClass = 'alert-error'; } 
                else if (type === 'success') { icon = '<i class="fa-solid fa-circle-check"></i>'; alertClass = 'alert-success'; }
                else if (type === 'info') { icon = '<i class="fa-solid fa-circle-info"></i>'; alertClass = 'alert-info'; }
                alertElement.className = `alert ${alertClass} alert-soft shadow-lg`;
                alertElement.innerHTML = `<div class="flex items-center">${icon}<span class="ml-2 text-xs sm:text-sm whitespace-nowrap">${message}</span></div>`;
                alertContainer.appendChild(alertElement);
                setTimeout(() => {
                    const existingAlert = document.getElementById(alertId);
                    if (existingAlert) {
                        existingAlert.style.transition = 'opacity 0.3s ease';
                        existingAlert.style.opacity = '0';
                        setTimeout(() => existingAlert.remove(), 300);
                    }
                }, 3000);
            }

            function formatInput(input, pattern) {
                if(!input) return;
                const numbers = input.value.replace(/\D/g, '');
                let result = '';
                let patternIndex = 0;
                let numbersIndex = 0;
                while (patternIndex < pattern.length && numbersIndex < numbers.length) {
                    if (pattern[patternIndex] === '-') {
                        result += '-';
                        patternIndex++;
                    } else {
                        result += numbers[numbersIndex];
                        patternIndex++;
                        numbersIndex++;
                    }
                }
                input.value = result;
            }

            function setupImagePreview(inputId, previewId, containerId = null) {
                const inputElement = document.getElementById(inputId);
                if (!inputElement) return;
                const previewElement = document.getElementById(previewId);
                const containerElement = containerId ? document.getElementById(containerId) : null;

                if (previewElement) {
                    const originalSrc = previewElement.src;
                    if (containerElement) {
                        containerElement.classList.add('cursor-pointer');
                        containerElement.onclick = () => zoomImage(previewElement.src);
                    }
                    inputElement.addEventListener('change', function(event) {
                        const file = event.target.files[0];
                        if (file) {
                            if (containerElement) containerElement.onclick = null;
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                previewElement.src = e.target.result;
                                if (containerElement) containerElement.onclick = () => zoomImage(e.target.result);
                            };
                            reader.readAsDataURL(file);
                        } else {
                            previewElement.src = originalSrc;
                            if (containerElement) containerElement.onclick = () => zoomImage(originalSrc);
                        }
                    });
                }
            }

            window.zoomImage = function(src) {
                const modal = document.getElementById('imageZoomModal');
                const image = document.getElementById('zoomed-image');
                if(modal && image) {
                    image.src = src;
                    modal.showModal();
                }
            };

            function showError(element, message) {
                const parent = element.closest('.form-control');
                if (!parent) return;
                const errorElement = parent.querySelector('.error-message');
                if (errorElement) { errorElement.textContent = message; errorElement.classList.remove('hidden'); }
                element.classList.add('input-error', 'select-error');
            }

            function clearError(element) {
                const parent = element.closest('.form-control');
                if (!parent) return;
                const errorElement = parent.querySelector('.error-message');
                if (errorElement) { errorElement.textContent = ''; errorElement.classList.add('hidden'); }
                element.classList.remove('input-error', 'select-error');
            }
            
            // --- 2. Page-Specific Initializers ---
            window.App = {
                currentDetailData: null,
                initDashboardPage: function() {
                    const page = document.getElementById('dashboard-section');
                    if (!page) return;
                    
                    function formatDateToThai(dateString) { if (!dateString || dateString.split('-').length < 3) return '-'; const months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."]; const date = new Date(dateString); return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear() + 543}`; }
                
                    window.openDetailModal = function(cardElement) {
                        App.currentDetailData = JSON.parse(JSON.stringify(cardElement.dataset));
                        const modal = document.getElementById('vehicleDetailModal');
                        const data = App.currentDetailData;
                        const translateCardType = (type) => (type === 'internal' ? 'ภายใน' : (type === 'external' ? 'ภายนอก' : '-'));

                        if (data.requestId) {
                            fetch('../../../controllers/user/activity/log_view_action.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ request_id: data.requestId }) }).catch(console.error);
                        }
                        const statusHeader = modal.querySelector('#modal-status-header');
                        statusHeader.className = `p-4 rounded-t-lg text-center ${data.statusClass.replace('badge-', 'bg-')} text-white`;
                        modal.querySelector('#modal-status-text').textContent = data.statusText;
                        modal.querySelector('#modal-status-reason').textContent = (data.status === 'rejected' && data.rejectionReason) ? `เหตุผล: ${data.rejectionReason}` : '';
                        const pickupDateInfoEl = modal.querySelector('#modal-pickup-date-info');
                        pickupDateInfoEl.innerHTML = ''; pickupDateInfoEl.classList.add('hidden');
                        if (data.status === 'approved' && data.cardPickupDate) {
                            pickupDateInfoEl.innerHTML = `<div class="alert alert-info alert-soft mb-4 text-center"><h4 class="font-bold">กำหนดการรับบัตร</h4><p class="text-lg font-semibold">${formatDateToThai(data.cardPickupDate)}</p></div>`;
                            pickupDateInfoEl.classList.remove('hidden');
                        }
                        modal.querySelector('#modal-search-id').textContent = data.searchId || '-';
                        const ownerDetailsHTML = data.ownerType === 'other' ? `<div class="grid grid-cols-2 gap-1"><div>ชื่อเจ้าของ</div><div>${data.otherOwnerName}</div><div>เกี่ยวข้องเป็น</div><div>${data.otherOwnerRelation}</div></div>` : '';
                        
                        let vehicleInfoHTML = `<h3 class="font-semibold mb-2">ข้อมูลยานพาหนะ</h3><div class="text-xs space-y-1 p-2 bg-base-200 rounded-md"><div class="grid grid-cols-2 gap-1">
                            <div>ประเภท</div><div>${data.type}</div>
                            <div>ยี่ห้อ/รุ่น</div><div>${data.brand} / ${data.model}</div>
                            <div>สี</div><div>${data.color}</div>
                            <div>ทะเบียน</div><div>${data.plate} ${data.province}</div>
                            <div>สิ้นอายุภาษี</div><div>${formatDateToThai(data.taxExpiry)}</div>
                            <div>ประเภทบัตร</div><div class="font-semibold">${translateCardType(data.cardType)}</div>
                            </div></div>`;
                        modal.querySelector('#modal-vehicle-info').innerHTML = vehicleInfoHTML;

                        modal.querySelector('#modal-owner-info').innerHTML = `<h3 class="font-semibold mb-2">ความเป็นเจ้าของ</h3><div class="text-xs space-y-1 p-2 bg-base-200 rounded-md"><div class="grid grid-cols-2 gap-1"><div>สถานะ</div><div>${data.ownerType === 'self' ? 'รถชื่อตนเอง' : 'รถคนอื่น'}</div></div>${ownerDetailsHTML}</div>`;
                        const basePath = `/public/uploads/${data.userKey}/vehicle/${data.requestKey}/`;
                        modal.querySelector('#modal-evidence-photos').innerHTML = `<h3 class="font-semibold mb-2">หลักฐาน</h3><div class="grid grid-cols-2 gap-2 text-xs"><div class="text-center"><p class="font-semibold mb-1">ทะเบียนรถ</p><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-24"><img src="${basePath}${data.imgRegFilename}" class="max-w-full max-h-full object-contain cursor-pointer" onclick="zoomImage(this.src)"></div></div><div class="text-center"><p class="font-semibold mb-1">ป้ายภาษี</p><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-24"><img src="${basePath}${data.imgTaxFilename}" class="max-w-full max-h-full object-contain cursor-pointer" onclick="zoomImage(this.src)"></div></div><div class="text-center"><p class="font-semibold mb-1">ด้านหน้า</p><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-24"><img src="${basePath}${data.imgFrontFilename}" class="max-w-full max-h-full object-contain cursor-pointer" onclick="zoomImage(this.src)"></div></div><div class="text-center"><p class="font-semibold mb-1">ด้านหลัง</p><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-24"><img src="${basePath}${data.imgRearFilename}" class="max-w-full max-h-full object-contain cursor-pointer" onclick="zoomImage(this.src)"></div></div></div>`;
                        const qrCodeEl = modal.querySelector('#modal-qr-code');
                        qrCodeEl.innerHTML = '';
                        if (data.status === 'approved' && data.requestKey) {
                            const qrCodeImageUrl = `/public/qr/${data.requestKey}.png`;
                            qrCodeEl.innerHTML = `<h3 class="font-semibold mb-2">ข้อมูลบัตรผ่าน</h3><div class="text-xs space-y-1 p-2 bg-base-200 rounded-md"><div class="flex flex-col items-center"><img src="${qrCodeImageUrl}" alt="QR Code" class="w-28 h-28 rounded-lg border bg-white p-1"></div><div class="grid grid-cols-2 gap-1 mt-2"><div>เลขที่บัตร</div><div>${data.cardNumber || '-'}</div><div>ผู้อนุมัติ</div><div>${data.adminName}</div><div>วันหมดอายุ</div><div class="font-semibold">${formatDateToThai(data.cardExpiry)}</div></div></div>`;
                        }
                        const modalActionButtons = modal.querySelector('#modal-action-buttons');
                        let actionButtonsHTML = '<form method="dialog"><button class="btn btn-ghost btn-sm">ปิด</button></form>';
                        
                        if (data.status === 'pending' || data.status === 'rejected') {
                            actionButtonsHTML = `<button class="btn btn-error btn-sm" onclick='App.confirmDeleteRequest(${data.requestId})'><i class="fa-solid fa-trash-can"></i> ลบคำร้อง</button>` + actionButtonsHTML;
                            actionButtonsHTML = `<button class="btn btn-warning btn-sm" onclick='App.openEditModal()'>แก้ไขคำร้อง</button>` + actionButtonsHTML;
                        }

                        if (data.canRenew === 'true') {
                            actionButtonsHTML = `<a href="renew_vehicle.php?vehicle_id=${data.vehicleId}" class="btn btn-success btn-sm"><i class="fa-solid fa-calendar-check mr-2"></i>ต่ออายุบัตร</a>` + actionButtonsHTML;
                        }

                        modalActionButtons.innerHTML = actionButtonsHTML;
                        modal.showModal();
                    }
                    
                    const statFilters = page.querySelectorAll('.stat-filter'); 
                    const vehicleCards = page.querySelectorAll('.vehicle-card'); 
                    const noFilterResults = page.querySelector('#no-filter-results');
                    function filterCards(filterValue) { 
                        let visibleCount = 0; 
                        vehicleCards.forEach(card => { 
                            let cardStatus = card.dataset.status;
                            if(card.querySelector('.badge-neutral')) cardStatus = 'expired';
                            if (filterValue === 'all' || filterValue === cardStatus) { 
                                card.style.display = 'block'; visibleCount++; 
                            } else { 
                                card.style.display = 'none'; 
                            } 
                        }); 
                        if (noFilterResults) {
                            noFilterResults.classList.toggle('hidden', !(visibleCount === 0 && filterValue !== 'all'));
                        }
                    }
                    function updateActiveFilter(filterValue) {
                        const colorMap = {
                            all: 'ring-blue-500',
                            approved: 'ring-green-500',
                            pending: 'ring-yellow-500',
                            rejected: 'ring-red-500'
                        };
                        statFilters.forEach(f => {
                            Object.values(colorMap).forEach(colorClass => f.classList.remove(colorClass));
                            f.classList.remove('ring-2');
                            if (f.dataset.filter === filterValue) {
                                f.classList.add('ring-2', colorMap[filterValue]);
                            }
                        });
                    }
                    updateActiveFilter('all');
                    statFilters.forEach(filter => { filter.addEventListener('click', () => { const filterValue = filter.dataset.filter; updateActiveFilter(filterValue); filterCards(filterValue); }); });
                },

                confirmDeleteRequest: function(requestId) {
                    const modal = document.getElementById('deleteConfirmModal');
                    if (modal) {
                        document.getElementById('delete-request-id').value = requestId;
                        modal.showModal();
                    }
                },

                openEditModal: function() {
                    if (!App.currentDetailData) return;
                    const data = App.currentDetailData;
                    document.getElementById('vehicleDetailModal').close(); 
                    const modal = document.getElementById('editVehicleModal');
                    if (!modal) return;
                    const basePath = `/public/uploads/${data.userKey}/vehicle/${data.requestKey}/`;
                    
                    modal.querySelector('#edit-request-id').value = data.requestId;
                    modal.querySelector('#edit-user-key').value = data.userKey;
                    modal.querySelector('#edit-request-key').value = data.requestKey;

                    // Display as text, not input
                    modal.querySelector('#edit-vehicle-type').textContent = data.type;
                    modal.querySelector('#edit-license-plate').textContent = data.plate;
                    modal.querySelector('#edit-license-province').textContent = data.province;
                    
                    // Set values for editable fields
                    modal.querySelector('#edit-vehicle-brand').value = data.brand;
                    modal.querySelector('#edit-vehicle-model').value = data.model;
                    modal.querySelector('#edit-vehicle-color').value = data.color;
                    
                    const taxDate = new Date(data.taxExpiry);
                    modal.querySelector('#edit-tax-day').value = taxDate.getDate();
                    modal.querySelector('#edit-tax-month').value = taxDate.getMonth() + 1;
                    modal.querySelector('#edit-tax-year').value = taxDate.getFullYear() + 543;
                    
                    // Handle owner type and details
                    const ownerTypeSelect = modal.querySelector('#edit-owner-type');
                    ownerTypeSelect.value = data.ownerType;
                    ownerTypeSelect.dispatchEvent(new Event('change')); // Trigger change to show/hide section
                    if (data.ownerType === 'other') { 
                        modal.querySelector('#edit-other-owner-name').value = data.otherOwnerName; 
                        modal.querySelector('#edit-other-owner-relation').value = data.otherOwnerRelation; 
                    }

                    // Set image previews
                    modal.querySelector('#edit-reg-copy-preview').src = `${basePath}${data.imgRegFilename}`; 
                    modal.querySelector('#edit-tax-sticker-preview').src = `${basePath}${data.imgTaxFilename}`; 
                    modal.querySelector('#edit-front-view-preview').src = `${basePath}${data.imgFrontFilename}`; 
                    modal.querySelector('#edit-rear-view-preview').src = `${basePath}${data.imgRearFilename}`;
                    
                    modal.showModal();
                },
                
                initAddVehiclePage: function() {
                    const form = document.getElementById('addVehicleForm');
                    if (!form) return;

                    const checkSection = form.querySelector('#vehicle-check-section');
                    const detailsSection = form.querySelector('#vehicle-details-section');
                    const checkBtn = form.querySelector('#check-vehicle-btn');
                    const clearCheckBtn = form.querySelector('#clear-check-btn');
                    const resetFormBtn = form.querySelector('#reset-form-btn');
                    const checkVehicleTypeField = form.querySelector('#check-vehicle-type');
                    const checkLicensePlateField = form.querySelector('#check-license-plate');
                    const checkProvinceField = form.querySelector('#check-license-province');
                    const resetCheckConfirmModal = document.getElementById('resetCheckConfirmModal');
                    const confirmResetCheckBtn = document.getElementById('confirm-reset-check-btn');
                    const resetFormConfirmModal = document.getElementById('resetConfirmModal');
                    const confirmResetBtn = document.getElementById('confirm-reset-btn');
                    const submitBtn = form.querySelector('#submit-request-btn');
                    const addVehicleConfirmModal = document.getElementById('addVehicleConfirmModal');
                    const finalSubmitBtn = document.getElementById('final-add-vehicle-submit-btn');

                    function validateField(field) {
                        let isValid = true;
                        const value = field.value.trim();
                        clearError(field);

                        if (field.hasAttribute('required') && !field.disabled) {
                             if (field.type === 'checkbox' && !field.checked) { showError(field, 'กรุณายอมรับเงื่อนไข'); isValid = false; } 
                             else if (field.type === 'file' && field.files.length === 0) { showError(field, 'กรุณาอัปโหลดไฟล์'); isValid = false; }
                             else if (field.tagName === 'SELECT' && value === '') { showError(field, 'กรุณาเลือกข้อมูล'); isValid = false; } 
                             else if (!['SELECT', 'CHECKBOX', 'FILE'].includes(field.tagName) && value === '') { showError(field, 'กรุณากรอกข้อมูล'); isValid = false; }
                        }
                        if(isValid && field.type === 'file' && field.files.length > 0){
                            const file = field.files[0];
                            const maxSize = 5 * 1024 * 1024;
                            if (file.size > maxSize) { showError(field, 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB'); field.value = ''; isValid = false; }
                        }
                        return isValid;
                    }

                    function populateAddVehicleConfirmModal() {
                        const summaryContent = document.getElementById('add-vehicle-summary-content');
                        const formData = new FormData(form);
                        const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
                        let ownerInfo = `<strong>ความเป็นเจ้าของ:</strong> ${formData.get('owner_type') === 'self' ? 'รถชื่อตนเอง' : 'รถคนอื่น'}`;
                        if (formData.get('owner_type') === 'other') {
                            ownerInfo += `<br><strong>ชื่อเจ้าของ:</strong> ${formData.get('other_owner_name') || '-'}<br><strong>เกี่ยวข้องเป็น:</strong> ${formData.get('other_owner_relation') || '-'}`;
                        }
                        const taxDate = `${formData.get('tax_day')} ${months[formData.get('tax_month')-1]} ${formData.get('tax_year')}`;

                        let html = `
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="font-bold text-base-content/70 text-sm uppercase tracking-wider mb-2">ข้อมูลยานพาหนะ</h4>
                                    <div class="p-4 bg-base-200 rounded-lg space-y-2 text-sm">
                                        <div class="flex justify-between"><span>ประเภท:</span> <span class="font-semibold">${formData.get('vehicle_type')}</span></div>
                                        <div class="flex justify-between"><span>ทะเบียน:</span> <span class="font-semibold">${formData.get('license_plate')} ${formData.get('license_province')}</span></div>
                                        <div class="flex justify-between"><span>ยี่ห้อ/รุ่น:</span> <span class="font-semibold">${formData.get('vehicle_brand')} / ${formData.get('vehicle_model')}</span></div>
                                        <div class="flex justify-between"><span>สี:</span> <span class="font-semibold">${formData.get('vehicle_color')}</span></div>
                                        <div class="flex justify-between"><span>วันสิ้นภาษี:</span> <span class="font-semibold">${taxDate}</span></div>
                                    </div>
                                    <h4 class="font-bold text-base-content/70 text-sm uppercase tracking-wider mb-2 mt-4">ข้อมูลความเป็นเจ้าของ</h4>
                                    <div class="p-4 bg-base-200 rounded-lg space-y-2 text-sm">${ownerInfo.replace(/<br>/g, '<div class="flex justify-between">').replace(/:/g, ':</span> <span class="font-semibold">')}</span></div>
                                </div>
                                <div>
                                    <h4 class="font-bold text-base-content/70 text-sm uppercase tracking-wider mb-2">หลักฐาน</h4>
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div class="text-center"><p class="font-semibold mb-1">ทะเบียนรถ</p><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-32"><img src="${document.getElementById('reg-copy-preview').src}" class="max-w-full max-h-full object-contain"></div></div>
                                        <div class="text-center"><p class="font-semibold mb-1">ป้ายภาษี</p><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-32"><img src="${document.getElementById('tax-sticker-preview').src}" class="max-w-full max-h-full object-contain"></div></div>
                                        <div class="text-center"><p class="font-semibold mb-1">ด้านหน้า</p><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-32"><img src="${document.getElementById('front-view-preview').src}" class="max-w-full max-h-full object-contain"></div></div>
                                        <div class="text-center"><p class="font-semibold mb-1">ด้านหลัง</p><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-32"><img src="${document.getElementById('rear-view-preview').src}" class="max-w-full max-h-full object-contain"></div></div>
                                    </div>
                                </div>
                            </div>`;
                        summaryContent.innerHTML = html;
                    }
                    
                    detailsSection.querySelectorAll('[required]:not([disabled])').forEach(field => {
                        const eventType = (field.tagName === 'SELECT' || field.type === 'checkbox' || field.type === 'file') ? 'change' : 'input';
                        field.addEventListener(eventType, () => validateField(field));
                    });
                    
                    [checkVehicleTypeField, checkLicensePlateField, checkProvinceField].forEach(field => {
                        const event = field.tagName === 'INPUT' ? 'input' : 'change';
                        field.addEventListener(event, () => clearError(field));
                    });
                    
                    if(checkLicensePlateField) {
                        checkLicensePlateField.addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙0-9]/g, ''); });
                    }

                    if(checkBtn){
                        checkBtn.addEventListener('click', async () => {
                            let isCheckValid = true;
                            if (!checkVehicleTypeField.value) { showError(checkVehicleTypeField, 'กรุณาเลือกประเภทรถ'); isCheckValid = false; }
                            if (!checkLicensePlateField.value.trim()) { showError(checkLicensePlateField, 'กรุณากรอกเลขทะเบียนรถ'); isCheckValid = false; }
                            if (!checkProvinceField.value) { showError(checkProvinceField, 'กรุณาเลือกจังหวัด'); isCheckValid = false; }
                            if (!isCheckValid) return;
                            
                            const vehicleType = checkVehicleTypeField.value;
                            const licensePlate = checkLicensePlateField.value.trim();
                            const province = checkProvinceField.value;
                            
                            checkBtn.classList.add('loading'); checkBtn.disabled = true;
                            try {
                                const response = await fetch('../../../controllers/user/vehicle/check_vehicle.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ license_plate: licensePlate, province: province }) });
                                const result = await response.json();
                                if (result.exists) {
                                        const modal = document.getElementById('duplicateVehicleModal');
                                        modal.querySelector('#duplicateVehicleMessage').textContent = `ยานพาหนะทะเบียน ${licensePlate} ${province} ได้ถูกยื่นคำร้องในรอบปัจจุบันแล้ว และกำลังอยู่ในสถานะ "รออนุมัติ" หรือ "อนุมัติแล้ว"`;
                                        modal.showModal();
                                } else {
                                    showAlert('ยานพาหนะนี้สามารถยื่นคำร้องได้', 'success');
                                    checkSection.classList.add('hidden');
                                    // Set hidden input values
                                    form.querySelector('input[name="vehicle_type"]').value = vehicleType;
                                    form.querySelector('input[name="license_plate"]').value = licensePlate;
                                    form.querySelector('input[name="license_province"]').value = province;
                                    // Set display text
                                    document.getElementById('display-vehicle-type').textContent = vehicleType;
                                    document.getElementById('display-license-plate').textContent = licensePlate;
                                    document.getElementById('display-license-province').textContent = province;
                                    detailsSection.classList.remove('hidden');
                                }
                            } catch (error) { showAlert('เกิดข้อผิดพลาดในการตรวจสอบข้อมูล', 'error');
                            } finally { checkBtn.classList.remove('loading'); checkBtn.disabled = false; }
                        });
                    }
                    if(clearCheckBtn) { clearCheckBtn.addEventListener('click', () => { resetCheckConfirmModal.showModal(); }); }
                    if(confirmResetCheckBtn) { confirmResetCheckBtn.addEventListener('click', () => {
                        [checkVehicleTypeField, checkLicensePlateField, checkProvinceField].forEach(field => { field.value = ''; clearError(field); });
                        resetCheckConfirmModal.close();
                    });}
                    if(resetFormBtn) { resetFormBtn.addEventListener('click', () => { resetFormConfirmModal.showModal(); }); }
                    if(confirmResetBtn) { confirmResetBtn.addEventListener('click', () => {
                        form.reset();
                        form.querySelector('#reg-copy-preview').src = 'https://img5.pic.in.th/file/secure-sv1/registration.jpg';
                        form.querySelector('#tax-sticker-preview').src = 'https://img2.pic.in.th/pic/tax_sticker.jpg';
                        form.querySelector('#front-view-preview').src = 'https://img2.pic.in.th/pic/front_view.png';
                        form.querySelector('#rear-view-preview').src = 'https://img5.pic.in.th/file/secure-sv1/rear_view.png';
                        detailsSection.classList.add('hidden');
                        checkSection.classList.remove('hidden');
                        [checkVehicleTypeField, checkLicensePlateField, checkProvinceField].forEach(field => { field.value = ''; clearError(field); });
                        form.querySelectorAll('.error-message').forEach(el => el.classList.add('hidden'));
                        form.querySelectorAll('.input-error, .select-error').forEach(el => el.classList.remove('input-error', 'select-error'));
                        resetFormConfirmModal.close();
                    });}
                    
                    if(submitBtn) {
                        submitBtn.addEventListener('click', (event) => {
                            event.preventDefault();
                            let isFormValid = true;
                            form.querySelectorAll('#vehicle-details-section [required]:not([disabled])').forEach(field => {
                                if (!validateField(field)) isFormValid = false;
                            });
                            if (isFormValid) {
                                populateAddVehicleConfirmModal();
                                addVehicleConfirmModal.showModal();
                            } else {
                                showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error');
                            }
                        });
                    }

                    if(finalSubmitBtn){
                        finalSubmitBtn.addEventListener('click', () => {
                            addVehicleConfirmModal.close();
                            document.getElementById('loadingModal').showModal();
                            form.submit();
                        });
                    }

                    const ownerTypeSelect = form.querySelector('[name="owner_type"]'); 
                    if (ownerTypeSelect) {
                        const otherOwnerDetails = form.querySelector('#other-owner-details'); 
                        ownerTypeSelect.addEventListener('change', function() { 
                            const requiredInputs = otherOwnerDetails.querySelectorAll('input'); 
                            if (this.value === 'other') { otherOwnerDetails.classList.remove('hidden'); requiredInputs.forEach(input => input.setAttribute('required', '')); } 
                            else { otherOwnerDetails.classList.add('hidden'); requiredInputs.forEach(input => { input.removeAttribute('required'); input.value = ''; clearError(input); }); } 
                        });
                    }
                    const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
                    const daySelect = form.querySelector('[name="tax_day"]'); 
                    const monthSelect = form.querySelector('[name="tax_month"]'); 
                    const yearSelect = form.querySelector('[name="tax_year"]'); 
                    if(daySelect && monthSelect && yearSelect) {
                        daySelect.innerHTML = '<option disabled selected value="">วัน</option>';
                        monthSelect.innerHTML = '<option disabled selected value="">เดือน</option>';
                        yearSelect.innerHTML = '<option disabled selected value="">ปี (พ.ศ.)</option>';
                        for (let i = 1; i <= 31; i++) { daySelect.innerHTML += `<option value="${i}">${i}</option>`; } 
                        months.forEach((month, i) => { monthSelect.innerHTML += `<option value="${i + 1}">${month}</option>`; }); 
                        const currentYearBE = new Date().getFullYear() + 543;
                        for (let i = currentYearBE; i <= currentYearBE + 10; i++) { yearSelect.innerHTML += `<option value="${i}">${i}</option>`; }
                    }

                    setupImagePreview('reg-copy-upload', 'reg-copy-preview', 'reg-copy-container');
                    setupImagePreview('tax-sticker-upload', 'tax-sticker-preview', 'tax-sticker-container');
                    setupImagePreview('front-view-upload', 'front-view-preview', 'front-view-container');
                    setupImagePreview('rear-view-upload', 'rear-view-preview', 'rear-view-container');

                    const modelInput = form.querySelector('[name="vehicle_model"]');
                    const colorInput = form.querySelector('[name="vehicle_color"]');
                    if(modelInput) modelInput.addEventListener('input', function() { this.value = this.value.toUpperCase().replace(/[^A-Z0-9\s-]/g, ''); }); 
                    if(colorInput) colorInput.addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙]/g, ''); }); 
                },
                
                initEditVehicleModal: function() {
                    const modal = document.getElementById('editVehicleModal');
                    if (!modal) return;
                    
                    const form = document.getElementById('editVehicleForm');
                    const cancelBtn = document.getElementById('cancel-edit-vehicle-btn');
                    const closeBtn = document.getElementById('close-edit-modal-btn');
                    const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
                    const daySelect = form.querySelector('#edit-tax-day');
                    const monthSelect = form.querySelector('#edit-tax-month');
                    const yearSelect = form.querySelector('#edit-tax-year');
                    const ownerTypeSelect = form.querySelector('#edit-owner-type');
                    const otherOwnerDetails = form.querySelector('#edit-other-owner-details');

                    daySelect.innerHTML = ''; monthSelect.innerHTML = ''; yearSelect.innerHTML = '';
                    for (let i = 1; i <= 31; i++) daySelect.innerHTML += `<option value="${i}">${i}</option>`;
                    months.forEach((month, i) => { monthSelect.innerHTML += `<option value="${i + 1}">${month}</option>`; });
                    const currentYearBE = new Date().getFullYear() + 543;
                    for (let i = currentYearBE; i <= currentYearBE + 10; i++) { yearSelect.innerHTML += `<option value="${i}">${i}</option>`; }

                    setupImagePreview('edit-reg-copy-upload', 'edit-reg-copy-preview');
                    setupImagePreview('edit-tax-sticker-upload', 'edit-tax-sticker-preview');
                    setupImagePreview('edit-front-view-upload', 'edit-front-view-preview');
                    setupImagePreview('edit-rear-view-upload', 'edit-rear-view-preview');
                    
                    if (ownerTypeSelect && otherOwnerDetails) {
                        ownerTypeSelect.addEventListener('change', function() {
                            const requiredInputs = otherOwnerDetails.querySelectorAll('input');
                            const isOther = this.value === 'other';
                            otherOwnerDetails.classList.toggle('hidden', !isOther);
                            requiredInputs.forEach(input => {
                                if (isOther) {
                                    input.setAttribute('required', '');
                                } else {
                                    input.removeAttribute('required');
                                    input.value = '';
                                    clearError(input);
                                }
                            });
                        });
                    }

                    function validateEditField(field) {
                         let isValid = true; const value = field.value.trim(); clearError(field);
                         if (field.hasAttribute('required')) {
                            if (field.tagName === 'SELECT' && value === '') { showError(field, 'กรุณาเลือกข้อมูล'); isValid = false; }
                            else if(field.tagName !== 'SELECT' && value === ''){ showError(field, 'กรุณากรอกข้อมูล'); isValid = false;}
                         }
                         return isValid;
                    }
                    
                    form.querySelectorAll('input, select').forEach(field => {
                        const eventType = (field.tagName === 'SELECT' || field.type === 'file') ? 'change' : 'input';
                        field.addEventListener(eventType, () => validateEditField(field));
                    });

                    form.addEventListener('submit', function(event){
                        event.preventDefault();
                        let isFormValid = true;
                        form.querySelectorAll('[required]').forEach(field => {
                            if(!validateEditField(field)) isFormValid = false;
                        });
                        if(isFormValid) {
                           document.getElementById('loadingModal').showModal();
                           form.submit();
                        } else {
                           showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error');
                        }
                    });

                    const cancelAndReopen = () => {
                        modal.close();
                        const detailModal = document.getElementById('vehicleDetailModal');
                        if (detailModal && App.currentDetailData) {
                            const cardElementLike = document.createElement('div');
                            for(const key in App.currentDetailData) {
                                cardElementLike.dataset[key] = App.currentDetailData[key];
                            }
                            window.openDetailModal(cardElementLike);
                        }
                    };

                    cancelBtn.addEventListener('click', cancelAndReopen);
                    closeBtn.addEventListener('click', cancelAndReopen);
                },

                initProfilePage: function() {
                    const page = document.getElementById('profile-section');
                    if (!page) return;

                    const profileForm = document.getElementById('profileForm');
                    const editBtn = document.getElementById('edit-profile-btn');
                    const saveBtn = document.getElementById('save-profile-btn');
                    const cancelBtn = document.getElementById('cancel-edit-btn');
                    const formInputs = profileForm.querySelectorAll('input:not([type=file]), select, textarea');
                    const fileInput = document.getElementById('profile-photo-upload');
                    const photoGuidance = document.getElementById('photo-guidance');
                    const daySelectP = document.getElementById('profile-dob-day'); 
                    const monthSelectP = document.getElementById('profile-dob-month'); 
                    const yearSelectP = document.getElementById('profile-dob-year');
                    const nationalIdInput = document.getElementById('profile-national-id');
                    const phoneInput = profileForm.querySelector('[name="phone"]');
                    const originalPhotoSrc = document.getElementById('profile-photo-preview').src;
                    const profilePhotoContainer = document.getElementById('profile-photo-container');
                    const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];

                    let initialFormValues = {};
                    formInputs.forEach(input => { initialFormValues[input.name] = input.value; });
                    
                    let isAddressPluginActive = false;
                    let isFileSizeValid = true;

                    const validateProfileField = (field) => {
                        let isValid = true; const value = field.value.trim(); clearError(field);
                        if (field.type === 'file' && field.files.length > 0) {
                            const file = field.files[0];
                            const maxSize = 5 * 1024 * 1024;
                            if (file.size > maxSize) { showError(field, 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB'); isFileSizeValid = false; isValid = false; } 
                            else { isFileSizeValid = true; }
                        } else if (field.hasAttribute('required')) {
                            if (field.tagName === 'SELECT' && value === '') { showError(field, 'กรุณาเลือกข้อมูล'); isValid = false; } 
                            else if (field.tagName === 'INPUT' && !['checkbox', 'file', 'radio'].includes(field.type) && value === '') { showError(field, 'กรุณากรอกข้อมูล'); isValid = false; }
                        }
                        if (isValid) {
                            if (field.name === 'title' && value === 'other' && profileForm.querySelector('[name="title_other"]').value.trim() === '') { showError(profileForm.querySelector('[name="title_other"]'), 'กรุณาระบุคำนำหน้า'); isValid = false; }
                            else if (field.name === 'phone' && value.replace(/\D/g, '').length !== 10) { showError(field, 'กรุณากรอกเบอร์โทรศัพท์ 10 หลัก'); isValid = false;}
                            else if (field.name === 'official_id' && value.length > 0 && value.length !== 10) { showError(field, 'กรุณากรอกเลขบัตรให้ครบ 10 หลัก'); isValid = false;}
                        }
                        return isValid;
                    }

                    const toggleEditMode = (isEditing) => {
                        formInputs.forEach(input => {
                            if (input.name === 'national_id_display' || input.name === 'work_department_display' || input.name === 'work_department') return;
                            if (isEditing) { input.removeAttribute('disabled'); input.classList.remove('input-disabled', 'select-disabled'); } 
                            else { input.setAttribute('disabled', true); input.classList.add('input-disabled', 'select-disabled'); }
                        });
                        if(isEditing) {
                            profilePhotoContainer.classList.remove('cursor-pointer');
                            profilePhotoContainer.removeAttribute('onclick');
                            if (!isAddressPluginActive) { $.Thailand({ $district: $('#profile-subdistrict'), $amphoe: $('#profile-district'), $province: $('#profile-province'), $zipcode: $('#profile-zipcode') }); isAddressPluginActive = true; }
                        } else {
                            profilePhotoContainer.classList.add('cursor-pointer');
                            profilePhotoContainer.setAttribute('onclick', `zoomImage('${originalPhotoSrc}')`);
                        }
                        fileInput.classList.toggle('hidden', !isEditing);
                        photoGuidance.classList.toggle('hidden', !isEditing);
                        editBtn.classList.toggle('hidden', isEditing);
                        saveBtn.classList.toggle('hidden', !isEditing);
                        cancelBtn.classList.toggle('hidden', !isEditing);
                    };

                    editBtn.addEventListener('click', (e) => { e.preventDefault(); toggleEditMode(true); });
                    
                    cancelBtn.addEventListener('click', () => {
                        formInputs.forEach(input => { input.value = initialFormValues[input.name]; clearError(input); });
                        document.getElementById('profile-photo-preview').src = originalPhotoSrc;
                        fileInput.value = '';
                        isFileSizeValid = true;
                        if(userDob) { daySelectP.value = userDob.day; monthSelectP.value = userDob.month; yearSelectP.value = userDob.year; }
                        formatInput(phoneInput, 'xxx-xxx-xxxx');
                        formatInput(nationalIdInput, 'x-xxxx-xxxxx-xx-x');
                        toggleEditMode(false);
                    });

                    saveBtn.addEventListener('click', async (e) => {
                        e.preventDefault();
                        if (!isFileSizeValid) { showAlert('ไฟล์รูปภาพมีขนาดใหญ่เกิน 5 MB กรุณาเลือกไฟล์ใหม่', 'error'); return; }
                        const phoneValue = phoneInput.value.replace(/\D/g, '');
                        let isPhoneValid = true;
                        if (phoneValue !== initialFormValues['phone'].replace(/\D/g, '')) {
                            if (phoneValue.length === 10) {
                                try {
                                    const response = await fetch('../../../controllers/user/register/check_user.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({ phone: phoneValue, user_id: currentUserId })
                                    });
                                    const result = await response.json();
                                    if (result.phoneExists) { showError(phoneInput, 'เบอร์โทรศัพท์นี้มีผู้ใช้อื่นลงทะเบียนแล้ว'); isPhoneValid = false; } 
                                    else { clearError(phoneInput); }
                                } catch (error) { showAlert('เกิดข้อผิดพลาดในการตรวจสอบเบอร์โทร', 'error'); isPhoneValid = false; }
                            }
                        }
                        if (!isPhoneValid) return;
                        let isFormValid = true;
                        profileForm.querySelectorAll('input:not([disabled]), select:not([disabled])').forEach(field => { if (!validateProfileField(field)) { isFormValid = false; } });
                        if (isFormValid) { document.getElementById('loadingModal').showModal(); profileForm.submit(); } 
                        else { showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error'); }
                    });
                    
                    fileInput.addEventListener('change', function(event) {
                        const file = event.target.files[0];
                        const previewElement = document.getElementById('profile-photo-preview');
                        if (file) {
                            const maxSize = 5 * 1024 * 1024;
                            clearError(this);
                            if (file.size > maxSize) {
                                showError(this, 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB');
                                showAlert('ไฟล์รูปภาพมีขนาดใหญ่เกิน 5 MB', 'error');
                                this.value = '';
                                previewElement.src = originalPhotoSrc;
                                isFileSizeValid = false;
                            } else {
                                isFileSizeValid = true;
                                const reader = new FileReader();
                                reader.onload = (e) => { previewElement.src = e.target.result; };
                                reader.readAsDataURL(file);
                            }
                        } else { isFileSizeValid = true; clearError(this); previewElement.src = originalPhotoSrc; }
                    });
                    
                    if (daySelectP && monthSelectP && yearSelectP) {
                        daySelectP.innerHTML = '<option disabled value="">วัน</option>'; monthSelectP.innerHTML = '<option disabled value="">เดือน</option>'; yearSelectP.innerHTML = '<option disabled value="">ปี (พ.ศ.)</option>';
                        for (let i = 1; i <= 31; i++) { daySelectP.innerHTML += `<option value="${i}">${i}</option>`; }
                        months.forEach((month, i) => { monthSelectP.innerHTML += `<option value="${i + 1}">${month}</option>`; });
                        const currentYearBE_profile = new Date().getFullYear() + 543;
                        for (let i = currentYearBE_profile; i >= currentYearBE_profile - 100; i--) { yearSelectP.innerHTML += `<option value="${i}">${i}</option>`; }
                        if(userDob) { daySelectP.value = userDob.day; monthSelectP.value = userDob.month; yearSelectP.value = userDob.year; }
                    }
                    
                    formInputs.forEach(field => { if(field.type !== 'file') { const eventType = (field.tagName === 'SELECT' || field.type === 'checkbox') ? 'change' : 'input'; field.addEventListener(eventType, () => validateProfileField(field)); } });
                    document.getElementById('profile-title').addEventListener('change', function() {
                        const otherInput = document.getElementById('profile-title-other');
                        otherInput.classList.toggle('hidden', this.value !== 'other');
                        if(this.value === 'other') otherInput.setAttribute('required', '');
                        else { otherInput.removeAttribute('required'); clearError(otherInput); }
                    });
                    
                    profileForm.querySelector('[name="firstname"]').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙\s]/g, ''); });
                    profileForm.querySelector('[name="lastname"]').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙\s]/g, ''); });
                    profileForm.querySelector('[name="title_other"]').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙\s.()]/g, ''); });
                    profileForm.querySelector('[name="address"]').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙0-9\s.\-\/]/g, ''); });
                    phoneInput.addEventListener('input', function() { formatInput(this, 'xxx-xxx-xxxx'); });
                    const officialIdInput = profileForm.querySelector('[name="official_id"]');
                    if (officialIdInput) officialIdInput.addEventListener('input', function() { this.value = this.value.replace(/\D/g, ''); });
                    formatInput(phoneInput, 'xxx-xxx-xxxx');
                    formatInput(nationalIdInput, 'x-xxxx-xxxxx-xx-x');
                },

                initGeneral: function() {
                    const imageZoomModal = document.getElementById('imageZoomModal');
                    if (imageZoomModal) {
                        imageZoomModal.addEventListener('click', function(e) {
                            const imageContainer = document.getElementById('zoomed-image-container');
                            if (imageContainer && !imageContainer.contains(e.target)) {
                                imageZoomModal.close();
                            }
                        });
                    }

                    <?php
                    if (isset($_SESSION['request_status']) && isset($_SESSION['request_message'])) {
                        echo "showAlert('" . addslashes($_SESSION['request_message']) . "', '" . addslashes($_SESSION['request_status']) . "');";
                        unset($_SESSION['request_status'], $_SESSION['request_message']);
                    }
                    ?>
                }
            };

            // --- 3. Main Execution ---
            document.addEventListener('DOMContentLoaded', function () {
                App.initGeneral();
                App.initDashboardPage();
                App.initAddVehiclePage();
                App.initProfilePage();
                App.initEditVehicleModal();
            });
        })();
    </script>
</body>
</html>

