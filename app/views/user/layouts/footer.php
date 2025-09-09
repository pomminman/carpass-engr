<?php
// app/views/user/layouts/footer.php
// ส่วนท้ายของเว็บไซต์ (Footer), Modals และ Scripts สำหรับผู้ใช้งาน
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
        <div class="modal-box max-w-3xl">
            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2 text-xl bg-base-200/50 hover:bg-base-200/80 z-10">✕</button>
            </form>
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
    <dialog id="editVehicleModal" class="modal"><div class="modal-box max-w-4xl"><form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form><h3 class="font-bold text-lg">แก้ไขข้อมูลคำร้อง</h3><div class="py-4"><form action="../../../controllers/user/vehicle/edit_vehicle_process.php" method="POST" enctype="multipart/form-data" id="editVehicleForm" novalidate><input type="hidden" name="request_id" id="edit-request-id"><input type="hidden" name="user_key" id="edit-user-key"><input type="hidden" name="request_key" id="edit-request-key"><div class="divider divider-start font-semibold">ข้อมูลยานพาหนะ</div><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"><div class="form-control w-full"><div class="label"><span class="label-text">ประเภทรถ</span></div><select name="vehicle_type" id="edit-vehicle-type" class="select select-bordered select-sm" required><option value="รถยนต์">รถยนต์</option><option value="รถจักรยานยนต์">รถจักรยานยนต์</option></select><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">ยี่ห้อรถ</span></div><select name="vehicle_brand" id="edit-vehicle-brand" class="select select-bordered select-sm" required><?php if(isset($car_brands)) foreach ($car_brands as $brand): ?><option value="<?php echo htmlspecialchars($brand); ?>"><?php echo htmlspecialchars($brand); ?></option><?php endforeach; ?></select><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">รุ่นรถ</span></div><input type="text" name="vehicle_model" id="edit-vehicle-model" class="input input-bordered input-sm w-full" required /><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">สีรถ</span></div><input type="text" name="vehicle_color" id="edit-vehicle-color" class="input input-bordered input-sm w-full" required /><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">เลขทะเบียนรถ</span></div><input type="text" name="license_plate" id="edit-license-plate" class="input input-bordered input-sm w-full" required /><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">จังหวัด</span></div><select name="license_province" id="edit-license-province" class="select select-bordered select-sm" required><?php if(isset($provinces)) foreach ($provinces as $province): ?><option value="<?php echo htmlspecialchars($province); ?>"><?php echo htmlspecialchars($province); ?></option><?php endforeach; ?></select><p class="error-message hidden"></p></div><div class="form-control w-full lg:col-span-2"><div class="label"><span class="label-text">วันสิ้นอายุภาษี</span></div><div class="grid grid-cols-3 gap-2"><select name="tax_day" id="edit-tax-day" class="select select-bordered select-sm" required></select><select name="tax_month" id="edit-tax-month" class="select select-bordered select-sm" required></select><select name="tax_year" id="edit-tax-year" class="select select-bordered select-sm" required></select></div><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">เป็นรถของใคร?</span></div><select name="owner_type" id="edit-owner-type" class="select select-bordered select-sm" required><option value="self">รถชื่อตนเอง</option><option value="other">รถคนอื่น</option></select><p class="error-message hidden"></p></div></div><div id="edit-other-owner-details" class="hidden mt-4"><div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border rounded-lg bg-base-200"><div class="form-control w-full"><div class="label"><span class="label-text">ชื่อ-สกุล เจ้าของ</span></div><input type="text" name="other_owner_name" id="edit-other-owner-name" class="input input-bordered input-sm w-full" /><p class="error-message hidden"></p></div><div class="form-control w-full"><div class="label"><span class="label-text">เกี่ยวข้องเป็น</span></div><input type="text" name="other_owner_relation" id="edit-other-owner-relation" class="input input-bordered input-sm w-full" /><p class="error-message hidden"></p></div></div></div><div class="divider divider-start font-semibold mt-8">หลักฐานรูปถ่าย (อัปโหลดใหม่เฉพาะที่ต้องการเปลี่ยน)</div><div class="grid grid-cols-1 lg:grid-cols-2 gap-6"><div class="form-control"><label class="block font-medium mb-2">สำเนาทะเบียนรถ</label><img id="edit-reg-copy-preview" src="" class="w-full h-40 object-contain rounded-lg border p-2 mb-2"><input type="file" name="reg_copy_upload" id="edit-reg-copy-upload" class="file-input file-input-bordered file-input-sm" accept=".jpg, .jpeg, .png"><p class="error-message hidden"></p></div><div class="form-control"><label class="block font-medium mb-2">ป้ายภาษี</label><img id="edit-tax-sticker-preview" src="" class="w-full h-40 object-contain rounded-lg border p-2 mb-2"><input type="file" name="tax_sticker_upload" id="edit-tax-sticker-upload" class="file-input file-input-bordered file-input-sm" accept=".jpg, .jpeg, .png"><p class="error-message hidden"></p></div><div class="form-control"><label class="block font-medium mb-2">รูปถ่ายรถด้านหน้า</label><img id="edit-front-view-preview" src="" class="w-full h-40 object-contain rounded-lg border p-2 mb-2"><input type="file" name="front_view_upload" id="edit-front-view-upload" class="file-input file-input-bordered file-input-sm" accept=".jpg, .jpeg, .png"><p class="error-message hidden"></p></div><div class="form-control"><label class="block font-medium mb-2">รูปถ่ายรถด้านหลัง</label><img id="edit-rear-view-preview" src="" class="w-full h-40 object-contain rounded-lg border p-2 mb-2"><input type="file" name="rear_view_upload" id="edit-rear-view-upload" class="file-input file-input-bordered file-input-sm" accept=".jpg, .jpeg, .png"><p class="error-message hidden"></p></div></div><div class="modal-action mt-6"><button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('editVehicleModal').close()">ยกเลิก</button><button type="submit" class="btn btn-success btn-sm">ยืนยันการแก้ไข</button></div></form></div></div></dialog>
    <dialog id="resetConfirmModal" class="modal"><div class="modal-box"><h3 class="font-bold text-lg">ยืนยันการล้างข้อมูล</h3><p class="py-4">คุณแน่ใจหรือไม่ว่าต้องการล้างข้อมูลในฟอร์มทั้งหมด?</p><div class="modal-action"><button class="btn btn-sm" onclick="document.getElementById('resetConfirmModal').close()">ยกเลิก</button><button id="confirm-reset-btn" class="btn btn-error btn-sm">ยืนยัน</button></div></div><form method="dialog" class="modal-backdrop"><button>close</button></form></dialog>
    <dialog id="loadingModal" class="modal modal-middle"><div class="modal-box text-center"><span class="loading loading-spinner loading-lg text-primary"></span><h3 class="font-bold text-lg mt-4">กรุณารอสักครู่</h3><p class="py-4">ระบบกำลังบันทึกข้อมูล...<br>กรุณาอย่าปิดหรือรีเฟรชหน้านี้</p></div></dialog>
    <dialog id="duplicateVehicleModal" class="modal"><div class="modal-box"><div class="alert alert-warning alert-soft"><i class="fa-solid fa-triangle-exclamation text-2xl"></i><div><h3 class="font-bold text-lg">ข้อมูลซ้ำซ้อน</h3><p class="py-2 text-sm" id="duplicateVehicleMessage"></p></div></div><div class="modal-action justify-center"><form method="dialog"><button class="btn btn-warning btn-outline btn-sm">รับทราบ</button></form></div></div><form method="dialog" class="modal-backdrop"><button>close</button></form></dialog>
    <dialog id="addVehicleConfirmModal" class="modal modal-middle">
        <div class="modal-box w-11/12 max-w-3xl">
            <h3 class="font-bold text-lg">โปรดตรวจสอบข้อมูลยานพาหนะ</h3>
            <div id="add-vehicle-summary-content" class="py-4 space-y-4 text-sm"></div>
            <div class="modal-action">
              <form method="dialog">
                <button class="btn btn-sm">แก้ไข</button>
              </form>
              <button id="final-add-vehicle-submit-btn" class="btn btn-sm btn-success">ยืนยันและส่งข้อมูล</button>
            </div>
        </div>
    </dialog>

    <div id="alert-container" class="toast toast-top toast-center z-50"></div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/JQL.min.js"></script>
    <script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/typeahead.bundle.js"></script>
    <script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.js"></script>
    <script>
        // Store user data from PHP for JS if it exists
        const userDob = <?php echo isset($user['dob']) ? json_encode(['day' => (int)date('d', strtotime($user['dob'])), 'month' => (int)date('m', strtotime($user['dob'])), 'year' => (int)date('Y', strtotime($user['dob'])) + 543]) : 'null'; ?>;
        const currentUserId = <?php echo json_encode($user_id); ?>;

        document.addEventListener('DOMContentLoaded', function () {
            
            // --- Helper Functions (used across pages) ---
            const alertContainer = document.getElementById('alert-container');
            function showAlert(message, type = 'info') {
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
                const previewElement = document.getElementById(previewId);
                const containerElement = containerId ? document.getElementById(containerId) : null;
                if (!inputElement || !previewElement) return;

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

            window.zoomImage = function(src) {
                document.getElementById('zoomed-image').src = src;
                document.getElementById('imageZoomModal').showModal();
            };

            const imageZoomModal = document.getElementById('imageZoomModal');
            if (imageZoomModal) {
                imageZoomModal.addEventListener('click', function(e) {
                    if (!e.target.closest('#zoomed-image-container')) {
                        imageZoomModal.close();
                    }
                });
            }

            function showError(element, message) {
                const parent = element.closest('.form-control');
                if (!parent) return;
                const errorElement = parent.querySelector('.error-message');
                if (errorElement) {
                    errorElement.textContent = message;
                    errorElement.classList.remove('hidden');
                }
                element.classList.add('input-error', 'select-error');
            }

            function clearError(element) {
                const parent = element.closest('.form-control');
                if (!parent) return;
                const errorElement = parent.querySelector('.error-message');
                if (errorElement) {
                    errorElement.textContent = '';
                    errorElement.classList.add('hidden');
                }
                element.classList.remove('input-error', 'select-error');
            }

            function validateField(field) {
                let isValid = true;
                const value = field.value.trim();
                clearError(field);

                if (field.type === 'file') {
                    if (field.files.length > 0) {
                        const file = field.files[0];
                        const maxSize = 5 * 1024 * 1024; // 5 MB
                        if (file.size > maxSize) {
                            showError(field, 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB');
                            field.value = '';
                            isValid = false;
                        }
                    } 
                    else if (field.hasAttribute('required')) {
                        showError(field, 'กรุณาอัปโหลดไฟล์');
                        isValid = false;
                    }
                } else if (field.hasAttribute('required')) {
                    if (field.type === 'checkbox' && !field.checked) {
                        showError(field, 'กรุณายอมรับเงื่อนไข');
                        isValid = false;
                    } else if (field.tagName === 'SELECT' && value === '') {
                        showError(field, 'กรุณาเลือกข้อมูล');
                        isValid = false;
                    } else if (field.tagName !== 'SELECT' && field.type !== 'checkbox' && value === '') {
                        showError(field, 'กรุณากรอกข้อมูล');
                        isValid = false;
                    }
                }
                return isValid;
            }
            
            <?php
                if (isset($_SESSION['request_status']) && isset($_SESSION['request_message'])) {
                    echo "showAlert('" . addslashes($_SESSION['request_message']) . "', '" . addslashes($_SESSION['request_status']) . "');";
                    unset($_SESSION['request_status'], $_SESSION['request_message']);
                }
                if (isset($_SESSION['login_success_message'])) {
                    echo "showAlert('" . addslashes($_SESSION['login_success_message']) . "', 'success');";
                    unset($_SESSION['login_success_message']);
                }
            ?>

            // --- Dashboard Page Logic ---
            if (document.getElementById('dashboard-section')) {
                function formatDateToThai(dateString) { if (!dateString || dateString.split('-').length < 3) return '-'; const months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."]; const date = new Date(dateString); return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear() + 543}`; }
                
                window.openDetailModal = function(cardElement) {
                    const modal = document.getElementById('vehicleDetailModal');
                    const data = cardElement.dataset;

                    if (data.requestId) {
                        fetch('../../../controllers/user/activity/log_view_action.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ request_id: data.requestId }) }).catch(error => console.error('Error logging view action:', error));
                    }
                    
                    const statusHeader = modal.querySelector('#modal-status-header');
                    const statusTextEl = modal.querySelector('#modal-status-text');
                    const statusReasonEl = modal.querySelector('#modal-status-reason');
                    statusHeader.className = `p-4 rounded-t-lg text-center`;
                    statusHeader.classList.add(data.statusClass.replace('badge-', 'bg-'), 'text-white');
                    statusTextEl.textContent = data.statusText;
                    statusReasonEl.textContent = '';
                    if(data.status === 'rejected' && data.rejectionReason) {
                        statusReasonEl.textContent = `เหตุผล: ${data.rejectionReason}`;
                    }
                    
                    const pickupDateInfoEl = modal.querySelector('#modal-pickup-date-info');
                    pickupDateInfoEl.innerHTML = '';
                    pickupDateInfoEl.classList.add('hidden');
                    if (data.status === 'approved' && data.cardPickupDate) {
                        pickupDateInfoEl.innerHTML = `
                            <div class="alert alert-info alert-soft mb-4 flex flex-col items-center text-center">
                                <div class="flex items-center gap-2"><i class="fa-solid fa-calendar-check text-lg"></i><h4 class="font-bold">กำหนดการรับบัตร</h4></div>
                                <p class="text-lg font-semibold leading-tight">${formatDateToThai(data.cardPickupDate)}</p>
                                <p class="text-xs text-slate-500 leading-tight">กรุณาติดต่อรับบัตรและชำระเงินในวันและเวลาราชการ</p>
                            </div>`;
                        pickupDateInfoEl.classList.remove('hidden');
                    }

                    modal.querySelector('#modal-search-id').textContent = data.searchId || '-';

                    const vehicleInfoEl = modal.querySelector('#modal-vehicle-info');
                    const ownerInfoEl = modal.querySelector('#modal-owner-info');
                    const ownerDetailsHTML = data.ownerType === 'other' ? `<div class="grid grid-cols-2 gap-1"><div class="text-slate-500">ชื่อเจ้าของ</div><div>${data.otherOwnerName}</div><div class="text-slate-500">เกี่ยวข้องเป็น</div><div>${data.otherOwnerRelation}</div></div>` : '';
                    vehicleInfoEl.innerHTML = `<h3 class="font-semibold text-base mb-2"><i class="fa-solid fa-car-side opacity-70 mr-2"></i>ข้อมูลยานพาหนะ</h3><div class="text-xs space-y-1 p-2 bg-base-200 rounded-md"><div class="grid grid-cols-2 gap-1"><div class="text-slate-500">ประเภท</div><div>${data.type}</div></div><div class="grid grid-cols-2 gap-1"><div class="text-slate-500">ยี่ห้อ/รุ่น</div><div>${data.brand} / ${data.model}</div></div><div class="grid grid-cols-2 gap-1"><div class="text-slate-500">สี</div><div>${data.color}</div></div><div class="grid grid-cols-2 gap-1"><div class="text-slate-500">ทะเบียน</div><div>${data.plate} ${data.province}</div></div><div class="grid grid-cols-2 gap-1"><div class="text-slate-500">สิ้นอายุภาษี</div><div>${formatDateToThai(data.taxExpiry)}</div></div></div>`;
                    ownerInfoEl.innerHTML = `<h3 class="font-semibold text-base mb-2"><i class="fa-solid fa-user-check opacity-70 mr-2"></i>ความเป็นเจ้าของ</h3><div class="text-xs space-y-1 p-2 bg-base-200 rounded-md"><div class="grid grid-cols-2 gap-1"><div class="text-slate-500">สถานะ</div><div>${data.ownerType === 'self' ? 'รถชื่อตนเอง' : 'รถคนอื่น'}</div></div>${ownerDetailsHTML}</div>`;

                    const evidencePhotosEl = modal.querySelector('#modal-evidence-photos');
                    const basePath = `/public/uploads/${data.userKey}/vehicle/${data.requestKey}/`;
                    evidencePhotosEl.innerHTML = `<h3 class="font-semibold text-base mb-2"><i class="fa-solid fa-images opacity-70 mr-2"></i>หลักฐาน</h3><div class="grid grid-cols-2 gap-2 text-xs"><div class="text-center"><p class="font-semibold mb-1">ทะเบียนรถ</p><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-24"><img src="${basePath}${data.imgRegFilename}" class="max-w-full max-h-full object-contain cursor-pointer" onclick="zoomImage(this.src)"></div></div><div class="text-center"><p class="font-semibold mb-1">ป้ายภาษี</p><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-24"><img src="${basePath}${data.imgTaxFilename}" class="max-w-full max-h-full object-contain cursor-pointer" onclick="zoomImage(this.src)"></div></div><div class="text-center"><p class="font-semibold mb-1">ด้านหน้า</p><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-24"><img src="${basePath}${data.imgFrontFilename}" class="max-w-full max-h-full object-contain cursor-pointer" onclick="zoomImage(this.src)"></div></div><div class="text-center"><p class="font-semibold mb-1">ด้านหลัง</p><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-24"><img src="${basePath}${data.imgRearFilename}" class="max-w-full max-h-full object-contain cursor-pointer" onclick="zoomImage(this.src)"></div></div></div>`;
                    
                    const qrCodeEl = modal.querySelector('#modal-qr-code');
                    qrCodeEl.innerHTML = '';
                    if (data.status === 'approved' && data.requestKey) {
                        const qrCodeImageUrl = `/public/qr/${data.requestKey}.png`;
                        const translateCardType = (type) => (type === 'internal' ? 'ภายใน' : (type === 'external' ? 'ภายนอก' : '-'));
                        qrCodeEl.innerHTML = `<h3 class="font-semibold text-base mb-2"><i class="fa-solid fa-qrcode opacity-70 mr-2"></i>ข้อมูลบัตรผ่าน</h3><div class="text-xs space-y-1 p-2 bg-base-200 rounded-md"><div class="flex flex-col items-center"><img src="${qrCodeImageUrl}" alt="QR Code" class="w-28 h-28 rounded-lg border bg-white p-1"></div><div class="grid grid-cols-2 gap-1 mt-2"><div class="text-slate-500">เลขที่บัตร</div><div>${data.cardNumber || '-'}</div><div class="text-slate-500">ประเภทบัตร</div><div>${translateCardType(data.cardType)}</div><div class="text-slate-500">ผู้อนุมัติ</div><div>${data.adminName}</div><div class="text-slate-500">วันหมดอายุ</div><div class="font-semibold">${formatDateToThai(data.cardExpiry)}</div></div></div>`;
                    }

                    const modalActionButtons = modal.querySelector('#modal-action-buttons');
                    let actionButtonsHTML = '<form method="dialog"><button class="btn btn-ghost btn-sm">ปิด</button></form>';
                    if (data.status === 'pending' || data.status === 'rejected') {
                        const dataString = JSON.stringify(data).replace(/"/g, '&quot;');
                        actionButtonsHTML = `<button class="btn btn-warning btn-sm" onclick='openEditModal(${dataString})'>แก้ไขคำร้อง</button>` + actionButtonsHTML;
                    }
                    modalActionButtons.innerHTML = actionButtonsHTML;
                    modal.showModal();
                }
                
                window.openEditModal = function(data) {
                    document.getElementById('vehicleDetailModal').close(); 
                    const modal = document.getElementById('editVehicleModal');
                    const basePath = `/public/uploads/${data.userKey}/vehicle/${data.requestKey}/`;
                    document.getElementById('edit-request-id').value = data.requestId;
                    document.getElementById('edit-user-key').value = data.userKey;
                    document.getElementById('edit-request-key').value = data.requestKey;
                    document.getElementById('edit-vehicle-type').value = data.type;
                    document.getElementById('edit-vehicle-brand').value = data.brand;
                    document.getElementById('edit-vehicle-model').value = data.model;
                    document.getElementById('edit-vehicle-color').value = data.color;
                    document.getElementById('edit-license-plate').value = data.plate;
                    document.getElementById('edit-license-province').value = data.province;
                    document.getElementById('edit-owner-type').value = data.ownerType;
                    const taxDate = new Date(data.taxExpiry);
                    document.getElementById('edit-tax-day').value = taxDate.getDate();
                    document.getElementById('edit-tax-month').value = taxDate.getMonth() + 1;
                    document.getElementById('edit-tax-year').value = taxDate.getFullYear() + 543;
                    const otherOwnerSection = document.getElementById('edit-other-owner-details');
                    if (data.ownerType === 'other') { otherOwnerSection.classList.remove('hidden'); document.getElementById('edit-other-owner-name').value = data.otherOwnerName; document.getElementById('edit-other-owner-relation').value = data.otherOwnerRelation; } else { otherOwnerSection.classList.add('hidden'); }
                    document.getElementById('edit-reg-copy-preview').src = `${basePath}${data.imgRegFilename}`; 
                    document.getElementById('edit-tax-sticker-preview').src = `${basePath}${data.imgTaxFilename}`; 
                    document.getElementById('edit-front-view-preview').src = `${basePath}${data.imgFrontFilename}`; 
                    document.getElementById('edit-rear-view-preview').src = `${basePath}${data.imgRearFilename}`;
                    modal.showModal();
                }
            
                const statFilters = document.querySelectorAll('.stat-filter'); const vehicleCards = document.querySelectorAll('.vehicle-card'); const noFilterResults = document.getElementById('no-filter-results');
                function filterCards(filterValue) { let visibleCount = 0; vehicleCards.forEach(card => { if (filterValue === 'all' || filterValue === card.dataset.status) { card.style.display = 'block'; visibleCount++; } else { card.style.display = 'none'; } }); if (visibleCount === 0 && filterValue !== 'all') { noFilterResults.classList.remove('hidden'); } else { noFilterResults.classList.add('hidden'); } }
                function updateActiveFilter(filterValue) { statFilters.forEach(f => { f.classList.remove('ring-2', 'ring-primary'); if (f.dataset.filter === filterValue) { f.classList.add('ring-2', 'ring-primary'); } }); }
                updateActiveFilter('all');
                statFilters.forEach(filter => { filter.addEventListener('click', () => { const filterValue = filter.dataset.filter; updateActiveFilter(filterValue); filterCards(filterValue); }); });
            }

            // --- Edit Modal Logic ---
            if (document.getElementById('editVehicleForm')) {
                const editVehicleForm = document.getElementById('editVehicleForm');
                const ownerTypeSelect = editVehicleForm.querySelector('[name="owner_type"]'); 
                const otherOwnerDetails = editVehicleForm.querySelector('#edit-other-owner-details'); 
                
                ownerTypeSelect.addEventListener('change', function() { 
                    const requiredInputs = otherOwnerDetails.querySelectorAll('input'); 
                    if (this.value === 'other') { 
                        otherOwnerDetails.classList.remove('hidden'); 
                        requiredInputs.forEach(input => input.setAttribute('required', '')); 
                    } else { 
                        otherOwnerDetails.classList.add('hidden'); 
                        requiredInputs.forEach(input => { 
                            input.removeAttribute('required'); 
                            input.value = ''; 
                            clearError(input); 
                        }); 
                    } 
                });
                
                const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
                const daySelect = editVehicleForm.querySelector('[name="tax_day"]'); 
                const monthSelect = editVehicleForm.querySelector('[name="tax_month"]'); 
                const yearSelect = editVehicleForm.querySelector('[name="tax_year"]'); 
                daySelect.innerHTML = '<option disabled selected value="">วัน</option>';
                monthSelect.innerHTML = '<option disabled selected value="">เดือน</option>';
                yearSelect.innerHTML = '<option disabled selected value="">ปี (พ.ศ.)</option>';
                for (let i = 1; i <= 31; i++) { daySelect.innerHTML += `<option value="${i}">${i}</option>`; } 
                months.forEach((month, i) => { monthSelect.innerHTML += `<option value="${i + 1}">${month}</option>`; }); 
                const currentYearBE = new Date().getFullYear() + 543;
                for (let i = currentYearBE; i <= currentYearBE + 10; i++) { yearSelect.innerHTML += `<option value="${i}">${i}</option>`; }
                
                setupImagePreview('edit-reg-copy-upload', 'edit-reg-copy-preview');
                setupImagePreview('edit-tax-sticker-upload', 'edit-tax-sticker-preview');
                setupImagePreview('edit-front-view-upload', 'edit-front-view-preview');
                setupImagePreview('edit-rear-view-upload', 'edit-rear-view-preview');

                const modelInput = editVehicleForm.querySelector('[name="vehicle_model"]');
                const colorInput = editVehicleForm.querySelector('[name="vehicle_color"]');
                const plateInput = editVehicleForm.querySelector('[name="license_plate"]');
                if(modelInput) modelInput.addEventListener('input', function() { this.value = this.value.toUpperCase().replace(/[^A-Z0-9\s-]/g, ''); }); 
                if(colorInput) colorInput.addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙]/g, ''); }); 
                if(plateInput) plateInput.addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙0-9\s]/g, ''); });
            }

            // --- Add Vehicle Page Logic ---
            if (document.getElementById('addVehicleForm')) {
                const addVehicleForm = document.getElementById('addVehicleForm');
                const addVehicleConfirmModal = document.getElementById('addVehicleConfirmModal');
                const finalAddVehicleSubmitBtn = document.getElementById('final-add-vehicle-submit-btn');
                const ownerTypeSelect = document.getElementById('owner-type'); 
                const otherOwnerDetails = document.getElementById('other-owner-details'); 
                
                ownerTypeSelect.addEventListener('change', function() { if (this.value === 'other') { otherOwnerDetails.classList.remove('hidden'); otherOwnerDetails.querySelectorAll('input').forEach(input => input.setAttribute('required', '')); } else { otherOwnerDetails.classList.add('hidden'); otherOwnerDetails.querySelectorAll('input').forEach(input => { input.removeAttribute('required'); clearError(input); }); } });
                
                setupImagePreview('reg-copy-upload', 'reg-copy-preview', 'reg-copy-container');
                setupImagePreview('tax-sticker-upload', 'tax-sticker-preview', 'tax-sticker-container');
                setupImagePreview('front-view-upload', 'front-view-preview', 'front-view-container');
                setupImagePreview('rear-view-upload', 'rear-view-preview', 'rear-view-container');

                const daySelect = document.getElementById('tax-day'); const monthSelect = document.getElementById('tax-month'); const yearSelect = document.getElementById('tax-year'); const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"]; for (let i = 1; i <= 31; i++) { daySelect.innerHTML += `<option value="${i}">${i}</option>`; } months.forEach((month, i) => { monthSelect.innerHTML += `<option value="${i + 1}">${month}</option>`; }); const currentYearBE = new Date().getFullYear() + 543; for (let i = currentYearBE; i <= currentYearBE + 10; i++) { yearSelect.innerHTML += `<option value="${i}">${i}</option>`; }
                document.getElementById('vehicle-model').addEventListener('input', function() { this.value = this.value.toUpperCase().replace(/[^A-Z0-9\s-]/g, ''); }); document.getElementById('vehicle-color').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙]/g, ''); }); document.getElementById('license-plate').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙0-9\s]/g, ''); });
                const resetButton = document.getElementById('reset-form-btn'); const resetConfirmModal = document.getElementById('resetConfirmModal'); const confirmResetBtn = document.getElementById('confirm-reset-btn'); if (resetButton) { resetButton.addEventListener('click', function() { resetConfirmModal.showModal(); }); } if (confirmResetBtn) { confirmResetBtn.addEventListener('click', function() { addVehicleForm.reset(); document.getElementById('reg-copy-preview').src = 'https://img5.pic.in.th/file/secure-sv1/registration.jpg'; document.getElementById('tax-sticker-preview').src = 'https://img2.pic.in.th/pic/tax_sticker.jpg'; document.getElementById('front-view-preview').src = 'https://img2.pic.in.th/pic/front_view.png'; document.getElementById('rear-view-preview').src = 'https://img5.pic.in.th/file/secure-sv1/rear_view.png'; document.getElementById('other-owner-details').classList.add('hidden'); addVehicleForm.querySelectorAll('.error-message').forEach(el => el.classList.add('hidden')); addVehicleForm.querySelectorAll('.input-error, .select-error').forEach(el => el.classList.remove('input-error', 'select-error')); resetConfirmModal.close(); }); }

                function calculatePickupDate(startDate = new Date()) {
                    const holidays = ['2025-01-01', '2025-02-12', '2025-04-07', '2025-04-14', '2025-04-15', '2025-04-16', '2025-05-01', '2025-05-05', '2025-05-12', '2025-06-03', '2025-07-11', '2025-07-28', '2025-08-12', '2025-10-13', '2025-10-23', '2025-12-05', '2025-12-10', '2025-12-31', '2026-01-01', '2026-03-02', '2026-04-06', '2026-04-13', '2026-04-14', '2026-04-15', '2026-05-01', '2026-05-04', '2026-06-01', '2026-06-03', '2026-07-28', '2026-07-29', '2026-08-12', '2026-10-13', '2026-10-23', '2026-12-07', '2026-12-10', '2026-12-31'];
                    let workingDays = 0;
                    let currentDate = new Date(startDate);
                    while (workingDays < 15) {
                        currentDate.setDate(currentDate.getDate() + 1);
                        const dayOfWeek = currentDate.getDay();
                        const dateString = currentDate.toISOString().slice(0, 10);
                        if (dayOfWeek !== 0 && dayOfWeek !== 6 && !holidays.includes(dateString)) {
                            workingDays++;
                        }
                    }
                    return currentDate;
                }
                function formatDateToThai(dateString) { if (!dateString || dateString.split('-').length < 3) return '-'; const months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."]; const date = new Date(dateString); return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear() + 543}`; }

                function populateAddVehicleConfirmModal() {
                    const summaryContent = document.getElementById('add-vehicle-summary-content');
                    const formData = new FormData(addVehicleForm);
                    const pickupDate = calculatePickupDate();
                    let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
                    html += `<div class="md:col-span-1"><div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">หลักฐานรูปถ่าย</div><div class="grid grid-cols-2 gap-2"><div class="text-center"><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-24"><img src="${document.getElementById('reg-copy-preview').src}" class="max-w-full max-h-full object-contain"></div><p class="text-[10px] font-semibold mt-1">สำเนาทะเบียนรถ</p></div><div class="text-center"><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-24"><img src="${document.getElementById('tax-sticker-preview').src}" class="max-w-full max-h-full object-contain"></div><p class="text-[10px] font-semibold mt-1">ป้ายภาษี</p></div><div class="text-center"><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-24"><img src="${document.getElementById('front-view-preview').src}" class="max-w-full max-h-full object-contain"></div><p class="text-[10px] font-semibold mt-1">รูปถ่ายด้านหน้า</p></div><div class="text-center"><div class="flex justify-center bg-base-200 p-2 rounded-lg border h-24"><img src="${document.getElementById('rear-view-preview').src}" class="max-w-full max-h-full object-contain"></div><p class="text-[10px] font-semibold mt-1">รูปถ่ายด้านหลัง</p></div></div></div>`;
                    html += '<div class="md:col-span-1 space-y-3">';
                    const taxDate = `${formData.get('tax_day')} ${addVehicleForm.querySelector('[name="tax_month"]').options[addVehicleForm.querySelector('[name="tax_month"]').selectedIndex].text} ${formData.get('tax_year')}`;
                    html += `<div><div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">ข้อมูลยานพาหนะ</div><div class="p-2 bg-base-200 rounded-md grid grid-cols-2 gap-x-4 gap-y-1 text-xs"><div><strong>ประเภท:</strong> ${formData.get('vehicle_type') || '-'}</div><div><strong>ยี่ห้อ:</strong> ${formData.get('vehicle_brand') || '-'}</div><div><strong>รุ่น:</strong> ${formData.get('vehicle_model') || '-'}</div><div><strong>สี:</strong> ${formData.get('vehicle_color') || '-'}</div><div class="col-span-2"><strong>เลขทะเบียน:</strong> ${formData.get('license_plate') || '-'} ${formData.get('license_province') || '-'}</div><div class="col-span-2"><strong>วันสิ้นอายุภาษี:</strong> ${taxDate}</div></div></div>`;
                    const ownerType = formData.get('owner_type');
                    html += '<div><div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">ข้อมูลเจ้าของ</div><div class="p-2 bg-base-200 rounded-md grid grid-cols-1 gap-y-1 text-xs">';
                    if (ownerType === 'self') { html += `<div><strong>ความเป็นเจ้าของ:</strong> รถชื่อตนเอง</div>`; } else { html += `<div><strong>ความเป็นเจ้าของ:</strong> รถคนอื่น</div><div><strong>ชื่อ-สกุล เจ้าของ:</strong> ${formData.get('other_owner_name') || '-'}</div><div><strong>เกี่ยวข้องเป็น:</strong> ${formData.get('other_owner_relation') || '-'}</div>`; }
                    html += '</div></div>';
                    html += `<div><div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">กำหนดการ</div><div class="p-2 bg-blue-100 border border-blue-200 rounded-md text-center"><span class="text-xs text-blue-800">วันที่คาดว่าจะได้รับบัตร</span><p class="font-bold text-blue-900">${formatDateToThai(pickupDate.toISOString().slice(0, 10))}</p></div></div>`;
                    html += '</div></div>';
                    summaryContent.innerHTML = html;
                }

                addVehicleForm.addEventListener('submit', function(event) { event.preventDefault(); let isFormValid = true; addVehicleForm.querySelectorAll('[required]').forEach(field => { if (!validateField(field)) isFormValid = false; }); if (isFormValid) { populateAddVehicleConfirmModal(); addVehicleConfirmModal.showModal(); } else { showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error'); } });
                finalAddVehicleSubmitBtn.addEventListener('click', async () => { addVehicleConfirmModal.close(); const submitButton = addVehicleForm.querySelector('button[type="submit"]'); const originalButtonContent = submitButton.innerHTML; submitButton.innerHTML = '<span class="loading loading-spinner loading-sm"></span> กำลังตรวจสอบ...'; submitButton.disabled = true; const licensePlate = document.getElementById('license-plate').value; const province = document.getElementById('license-province').value; try { const checkResponse = await fetch('../../../controllers/user/vehicle/check_vehicle.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ license_plate: licensePlate, province: province, request_id: 0 }) }); const checkResult = await checkResponse.json(); if (checkResult.exists) { document.getElementById('duplicateVehicleMessage').textContent = `ทะเบียนรถ ${licensePlate} จังหวัด ${province} มีข้อมูลอยู่ในระบบแล้ว`; document.getElementById('duplicateVehicleModal').showModal(); submitButton.innerHTML = originalButtonContent; submitButton.disabled = false; } else { document.getElementById('loadingModal').showModal(); setTimeout(() => { addVehicleForm.submit(); }, 100); } } catch (error) { showAlert('เกิดข้อผิดพลาดในการตรวจสอบข้อมูล', 'error'); submitButton.innerHTML = originalButtonContent; submitButton.disabled = false; } });
                addVehicleForm.querySelectorAll('input, select').forEach(field => { const eventType = (field.tagName === 'SELECT' || field.type === 'checkbox' || field.type === 'file') ? 'change' : 'input'; field.addEventListener(eventType, () => validateField(field)); });
            }

            // --- Profile Page Logic ---
            if (document.getElementById('profileForm')) {
                 const profileSection = document.getElementById('profile-section');
                const editBtn = document.getElementById('edit-profile-btn');
                const saveBtn = document.getElementById('save-profile-btn');
                const cancelBtn = document.getElementById('cancel-edit-btn');
                const profileForm = document.getElementById('profileForm');
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

                let initialFormValues = {};
                formInputs.forEach(input => { initialFormValues[input.name] = input.value; });
                
                let isAddressPluginActive = false;
                let isFileSizeValid = true;

                const validateProfileField = (field) => {
                    let isValid = true; const value = field.value.trim(); clearError(field);
                    
                    if (field.type === 'file' && field.files.length > 0) {
                        const file = field.files[0];
                        const maxSize = 5 * 1024 * 1024;
                        if (file.size > maxSize) {
                            showError(field, 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB');
                            isFileSizeValid = false;
                            isValid = false;
                        } else {
                            isFileSizeValid = true;
                        }
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
                        if (isEditing) { input.removeAttribute('disabled'); input.classList.remove('input-disabled'); } else { input.setAttribute('disabled', true); input.classList.add('input-disabled'); }
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
                cancelBtn.addEventListener('click', () => { formInputs.forEach(input => { input.value = initialFormValues[input.name]; clearError(input); }); document.getElementById('profile-photo-preview').src = originalPhotoSrc; fileInput.value = ''; isFileSizeValid = true; daySelectP.value = userDob.day; monthSelectP.value = userDob.month; yearSelectP.value = userDob.year; formatInput(phoneInput, 'xxx-xxx-xxxx'); formatInput(nationalIdInput, 'x-xxxx-xxxxx-xx-x'); toggleEditMode(false); });
                saveBtn.addEventListener('click', async (e) => { e.preventDefault(); if (!isFileSizeValid) { showAlert('ไฟล์รูปภาพมีขนาดใหญ่เกิน 5 MB กรุณาเลือกไฟล์ใหม่', 'error'); return; } const phoneValue = phoneInput.value.replace(/\D/g, ''); let isPhoneValid = true;
                    if (phoneValue !== initialFormValues['phone'].replace(/\D/g, '')) {
                        if (phoneValue.length === 10) {
                            try {
                                const response = await fetch('../../../controllers/user/register/check_user.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ phone: phoneValue, user_id: currentUserId }) });
                                const result = await response.json();
                                if (result.phoneExists) { showError(phoneInput, 'เบอร์โทรศัพท์นี้มีผู้ใช้อื่นลงทะเบียนแล้ว'); isPhoneValid = false; } else { clearError(phoneInput); }
                            } catch (error) { showAlert('เกิดข้อผิดพลาดในการตรวจสอบเบอร์โทร', 'error'); isPhoneValid = false; }
                        }
                    }
                    if (!isPhoneValid) return;
                    let isFormValid = true;
                    profileForm.querySelectorAll('input:not([disabled]), select:not([disabled])').forEach(field => { if (!validateProfileField(field)) { isFormValid = false; } });
                    if (isFormValid) { document.getElementById('loadingModal').showModal(); profileForm.submit(); } else { showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error'); }
                });
                
                setupImagePreview('profile-photo-upload', 'profile-photo-preview');

                const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
                daySelectP.innerHTML = '<option disabled value="">วัน</option>'; monthSelectP.innerHTML = '<option disabled value="">เดือน</option>'; yearSelectP.innerHTML = '<option disabled value="">ปี (พ.ศ.)</option>';
                for (let i = 1; i <= 31; i++) { daySelectP.innerHTML += `<option value="${i}">${i}</option>`; }
                months.forEach((month, i) => { monthSelectP.innerHTML += `<option value="${i + 1}">${month}</option>`; });
                const currentYearBE_profile = new Date().getFullYear() + 543;
                for (let i = currentYearBE_profile; i >= currentYearBE_profile - 100; i--) { yearSelectP.innerHTML += `<option value="${i}">${i}</option>`; }
                daySelectP.value = userDob.day; monthSelectP.value = userDob.month; yearSelectP.value = userDob.year;

                formInputs.forEach(field => { if(field.type !== 'file') { const eventType = (field.tagName === 'SELECT' || field.type === 'checkbox') ? 'change' : 'input'; field.addEventListener(eventType, () => validateProfileField(field)); } });
                document.getElementById('profile-title').addEventListener('change', function() { const otherInput = document.getElementById('profile-title-other'); otherInput.classList.toggle('hidden', this.value !== 'other'); if(this.value === 'other') otherInput.setAttribute('required', ''); else { otherInput.removeAttribute('required'); clearError(otherInput); } });
                ['profile-subdistrict', 'profile-district', 'profile-province', 'profile-zipcode'].forEach(id => { const input = document.getElementById(id); if(input) { input.addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙0-9\s]/g, ''); }); } });
                profileForm.querySelector('[name="firstname"]').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙\s]/g, ''); });
                profileForm.querySelector('[name="lastname"]').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙\s]/g, ''); });
                profileForm.querySelector('[name="title_other"]').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙\s.()]/g, ''); });
                profileForm.querySelector('[name="address"]').addEventListener('input', function() { this.value = this.value.replace(/[^ก-๙0-9\s.\-\/]/g, ''); });
                phoneInput.addEventListener('input', function() { formatInput(this, 'xxx-xxx-xxxx'); });
                const officialIdInput = profileForm.querySelector('[name="official_id"]');
                if (officialIdInput) officialIdInput.addEventListener('input', function() { this.value = this.value.replace(/\D/g, ''); });
                formatInput(phoneInput, 'xxx-xxx-xxxx');
                formatInput(nationalIdInput, 'x-xxxx-xxxxx-xx-x');
            }

        });
    </script>
</body>
</html>