<?php
// app/views/admin/layouts/footer.php
// This is the shared footer for all admin pages.
if (isset($conn)) {
    $conn->close();
}
?>
    </div> <!-- Close main flex container -->
    <!-- Footer -->
    <footer class="fixed bottom-0 left-0 right-0 bg-base-200 text-base-content shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] p-1 text-center z-40">
        <p class="text-[10px] sm:text-xs whitespace-nowrap">Developed by ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ/กยข.กช.)</p>
    </footer>
    
    <!-- Modals -->
    <dialog id="inspectModal" class="modal">
        <div class="modal-box max-w-5xl">
            <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2 text-xl bg-base-200/50 hover:bg-base-200/80">✕</button></form>
            <h3 class="font-bold text-lg" id="modal-title-inspect">รายละเอียดคำร้อง: <span></span></h3>
            <div id="modal-body-inspect" class="py-4 space-y-4"><div class="text-center"><span class="loading loading-spinner loading-lg"></span></div></div>
            <div class="modal-action" id="modal-action-inspect"><form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form></div>
             <div id="rejection-section" class="hidden mt-4 p-4 border-t">
                <h4 class="font-bold mb-2">กรุณาระบุเหตุผลที่ไม่ผ่านการอนุมัติ:</h4>
                <div class="form-control">
                    <textarea id="rejection-reason" class="textarea textarea-bordered w-full" rows="2" placeholder="เช่น เอกสารไม่ชัดเจน, ข้อมูลไม่ถูกต้อง..."></textarea>
                    <p id="rejection-error-msg" class="text-error text-xs mt-1 hidden">กรุณาระบุเหตุผล</p>
                </div>
                <div class="flex justify-end gap-2 mt-2">
                    <button id="cancel-reject-btn" class="btn btn-sm btn-ghost">ยกเลิก</button>
                    <button id="confirm-reject-btn" class="btn btn-sm btn-error">ยืนยันการปฏิเสธ</button>
                </div>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
    <dialog id="imageZoomModal" class="modal">
        <div class="modal-box w-11/12 max-w-5xl p-0 bg-transparent shadow-none flex justify-center items-center">
            <div id="zoomed-image-container"><img id="zoomed-image" src="" alt="ขยายรูปภาพ" class="rounded-lg"><form method="dialog"><button class="btn btn-circle absolute right-2 top-2 bg-black/25 hover:bg-black/50 text-white border-none text-xl z-10">✕</button></form></div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
    <dialog id="confirmActionModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg" id="confirm-title"></h3><p class="py-4" id="confirm-message"></p>
            <div class="modal-action"><button id="confirm-cancel-btn" class="btn btn-sm">ยกเลิก</button><button id="confirm-ok-btn" class="btn btn-sm"></button></div>
        </div>
    </dialog>
    <dialog id="exportModal" class="modal">
        <div class="modal-box">
            <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form>
            <h3 class="font-bold text-lg mb-4">เลือกรูปแบบการ Export</h3>
            <div class="space-y-3">
                <div class="p-4 border rounded-lg hover:bg-base-200 transition-colors duration-200"><label class="flex items-center justify-between cursor-pointer"><div class="flex items-center gap-3"><i class="fa-solid fa-table-list text-xl text-primary w-6 text-center"></i><div><span class="font-semibold">ข้อมูลตามตารางที่แสดง</span><span class="text-xs text-slate-500 block">Export 7 คอลัมน์หลักที่แสดงผล</span></div></div><input type="radio" name="export_type" class="radio radio-primary" value="table_view" checked/></label></div>
                <div class="p-4 border rounded-lg hover:bg-base-200 transition-colors duration-200"><label class="flex items-center justify-between cursor-pointer"><div class="flex items-center gap-3"><i class="fa-solid fa-file-lines text-xl text-primary w-6 text-center"></i><div><span class="font-semibold">ข้อมูลทั้งหมด</span><span class="text-xs text-slate-500 block">รวมข้อมูลผู้สมัครและยานพาหนะ</span></div></div><input type="radio" name="export_type" class="radio radio-primary" value="all"/></label></div>
                <div class="p-4 border rounded-lg hover:bg-base-200 transition-colors duration-200"><label class="flex items-center justify-between cursor-pointer"><div class="flex items-center gap-3"><i class="fa-solid fa-user text-xl text-info w-6 text-center"></i><div><span class="font-semibold">เฉพาะข้อมูลผู้สมัคร</span><span class="text-xs text-slate-500 block">ข้อมูลส่วนตัวและที่อยู่</span></div></div><input type="radio" name="export_type" class="radio radio-primary" value="users"/></label></div>
                <div class="p-4 border rounded-lg hover:bg-base-200 transition-colors duration-200"><label class="flex items-center justify-between cursor-pointer"><div class="flex items-center gap-3"><i class="fa-solid fa-car text-xl text-accent w-6 text-center"></i><div><span class="font-semibold">เฉพาะข้อมูลยานพาหนะ</span><span class="text-xs text-slate-500 block">ข้อมูลทะเบียนและรายละเอียดรถ</span></div></div><input type="radio" name="export_type" class="radio radio-primary" value="vehicles"/></label></div>
                 <div class="p-4 border rounded-lg hover:bg-base-200 transition-colors duration-200"><label class="flex items-center justify-between cursor-pointer"><div class="flex items-center gap-3"><i class="fa-solid fa-tasks text-xl text-warning w-6 text-center"></i><div><span class="font-semibold">กำหนดข้อมูลเอง</span><span class="text-xs text-slate-500 block">เลือกคอลัมน์ที่ต้องการ Export</span></div></div><input type="radio" name="export_type" class="radio radio-primary" value="custom"/></label></div>
            </div>
            <div id="custom-columns-section" class="hidden mt-4 pt-4 border-t max-h-60 overflow-y-auto">
                 <div class="flex justify-between items-center mb-2"><h4 class="font-semibold text-sm">เลือกคอลัมน์ที่ต้องการ:</h4><button id="deselect-all-custom" class="btn btn-xs btn-ghost">ยกเลิกทั้งหมด</button></div>
                <div id="columns-checkboxes" class="grid grid-cols-2 gap-2 text-sm"></div>
            </div>
            <div class="modal-action"><form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form><button id="generateExportBtn" class="btn btn-success btn-sm">สร้างและดาวน์โหลด</button></div>
        </div>
    </dialog>
     <dialog id="add_admin_modal" class="modal">
        <div class="modal-box">
            <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form>
            <h3 class="font-bold text-lg"><i class="fa-solid fa-user-plus"></i> เพิ่มเจ้าหน้าที่ใหม่</h3>
            <form action="../../../controllers/admin/register/process_register.php" method="POST" class="mt-4 space-y-3">
                <input type="text" name="title" placeholder="คำนำหน้า (เช่น นาย, นางสาว)" class="input input-sm input-bordered w-full" required>
                <input type="text" name="firstname" placeholder="ชื่อจริง" class="input input-sm input-bordered w-full" required>
                <input type="text" name="lastname" placeholder="นามสกุล" class="input input-sm input-bordered w-full" required>
                <input type="text" name="department" placeholder="สังกัด" class="input input-sm input-bordered w-full" required>
                <input type="text" name="username" placeholder="Username (สำหรับเข้าระบบ)" class="input input-sm input-bordered w-full" required>
                <input type="password" name="password" placeholder="Password" class="input input-sm input-bordered w-full" required>
                <select name="role" class="select select-sm select-bordered w-full" required>
                    <option disabled selected>เลือกระดับสิทธิ์</option>
                    <option value="admin">Admin</option>
                    <?php if (isset($admin_info) && $admin_info['role'] === 'superadmin'): ?>
                    <option value="superadmin">Superadmin</option>
                    <?php endif; ?>
                </select>
                <select name="view_permission" class="select select-sm select-bordered w-full" required>
                    <option disabled selected>เลือกสิทธิ์การเข้าถึง</option>
                    <option value="0">เฉพาะสังกัดตนเอง</option>
                    <option value="1">ดูได้ทุกสังกัด</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm w-full mt-4">เพิ่มเจ้าหน้าที่</button>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
    <div id="alert-container" class="toast toast-top toast-center z-50"></div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            const icon = type === 'success' ? '<i class="fa-solid fa-circle-check mr-2"></i>' : '<i class="fa-solid fa-circle-xmark mr-2"></i>';
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${alertClass} alert-soft shadow-lg`;
            alertDiv.innerHTML = `<div>${icon}<span>${message}</span></div>`;
            alertContainer.appendChild(alertDiv);
            setTimeout(() => { alertDiv.remove(); }, 3000);
        }

        window.zoomImage = function(src) {
            document.getElementById('zoomed-image').src = src;
            document.getElementById('imageZoomModal').showModal();
        }

        const imageZoomModal = document.getElementById('imageZoomModal');
        if (imageZoomModal) {
            imageZoomModal.addEventListener('click', function(e) {
                const imageContainer = document.getElementById('zoomed-image-container');
                if (imageContainer && !imageContainer.contains(e.target)) {
                    imageZoomModal.close();
                }
            });
        }
        
        if (document.getElementById('requestsTable')) {
            const searchInput = document.getElementById('searchInput');
            const table = document.getElementById('requestsTable');
            const tableBody = table.querySelector('tbody');
            const allRows = Array.from(tableBody.querySelectorAll('tr[data-request-id]'));
            const noResultsRow = document.getElementById('no-results-row');
            const statusFilters = document.querySelectorAll('.status-filter');
            let currentFilter = 'all';

            if (statusFilters.length > 0) {
                 const activeFilter = document.querySelector('.status-filter.active');
                 if(activeFilter) currentFilter = activeFilter.dataset.filter;
            } else {
                currentFilter = 'pending';
            }

            function filterAndSearch() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                let visibleCount = 0;
                allRows.forEach(row => {
                    const statusMatch = currentFilter === 'all' || row.dataset.status === currentFilter;
                    const searchMatch = row.textContent.toLowerCase().includes(searchTerm);
                    const isVisible = statusMatch && searchMatch;
                    row.style.display = isVisible ? '' : 'none';
                    if (isVisible) visibleCount++;
                });
                if(noResultsRow) noResultsRow.style.display = visibleCount > 0 ? 'none' : 'table-row';
            }

            if(searchInput) searchInput.addEventListener('input', filterAndSearch);
            statusFilters.forEach(filter => {
                filter.addEventListener('click', function(e) {
                    e.preventDefault();
                    statusFilters.forEach(f => f.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.dataset.filter;
                    filterAndSearch();
                });
            });

            const headers = table.querySelectorAll('th[data-sort-by]');
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const isAsc = header.classList.contains('sort-asc');
                    const direction = isAsc ? -1 : 1;
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                    headers.forEach(h => { h.classList.remove('sort-asc', 'sort-desc'); h.querySelector('i').className = 'fa-solid fa-sort'; });
                    header.classList.toggle('sort-asc', !isAsc);
                    header.classList.toggle('sort-desc', isAsc);
                    header.querySelector('i').className = !isAsc ? 'fa-solid fa-sort-up' : 'fa-solid fa-sort-down';
                    const rows = Array.from(tableBody.querySelectorAll('tr[data-request-id]'));
                    rows.sort((rowA, rowB) => {
                        let valA = rowA.children[columnIndex].textContent.trim();
                        let valB = rowB.children[columnIndex].textContent.trim();
                        return valA.toString().localeCompare(valB.toString(), undefined, {numeric: true}) * direction;
                    });
                    rows.forEach(row => tableBody.appendChild(row));
                });
            });

            const inspectModal = document.getElementById('inspectModal');
            const modalTitle = document.getElementById('modal-title-inspect').querySelector('span');
            const modalBody = document.getElementById('modal-body-inspect');
            const modalActions = document.getElementById('modal-action-inspect');
            const rejectionSection = document.getElementById('rejection-section');
            const confirmModal = document.getElementById('confirmActionModal');

            tableBody.addEventListener('click', function(e) {
                const targetButton = e.target.closest('.inspect-btn');
                if (targetButton) {
                    openInspectModal(targetButton.dataset.id);
                }
            });

            async function openInspectModal(requestId) {
                inspectModal.showModal();
                inspectModal.dataset.currentRequestId = requestId;
                modalTitle.textContent = '';
                modalBody.innerHTML = '<div class="text-center"><span class="loading loading-spinner loading-lg"></span></div>';
                modalActions.innerHTML = '<form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>';
                rejectionSection.classList.add('hidden');
                document.getElementById('rejection-reason').value = '';

                try {
                    const response = await fetch(`../../../controllers/admin/requests/get_request_details.php?id=${requestId}`);
                    const result = await response.json();
                    if (result.success) renderModalContent(result.data);
                    else modalBody.innerHTML = `<div class="text-center text-error">${result.message}</div>`;
                } catch (error) {
                    modalBody.innerHTML = `<div class="text-center text-error">เกิดข้อผิดพลาดในการดึงข้อมูล</div>`;
                }
            }
            
            function formatThaiDate(dateString) {
                if (!dateString || dateString === '0000-00-00') return '-';
                const date = new Date(dateString);
                const thaiMonths = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
                return `${date.getDate()} ${thaiMonths[date.getMonth()]} ${date.getFullYear() + 543}`;
            }

            function renderModalContent(data) {
                modalTitle.textContent = data.search_id;
                const userTypeThai = data.user_type === 'army' ? 'ข้าราชการ/ลูกจ้าง/พนักงานราชการ ทบ.' : 'บุคคลภายนอก';
                const ownerTypeThai = data.owner_type === 'self' ? 'รถชื่อตนเอง' : 'รถคนอื่น';
                let historySection = '';
                if (data.status === 'pending' && data.edit_status == 1 && data.rejection_reason) {
                    historySection = `<div role="alert" class="alert alert-warning alert-soft mb-4"><i class="fa-solid fa-clock-rotate-left text-lg"></i><div><h3 class="font-bold">คำร้องนี้เคยถูกส่งกลับไปแก้ไข</h3><div class="text-xs">เหตุผลครั้งก่อน: ${data.rejection_reason}</div></div></div>`;
                }
                let profileImageSrc = 'https://placehold.co/200x200/CCCCCC/FFFFFF?text=No+Img';
                if (data.user_key && data.photo_profile) {
                    profileImageSrc = `/public/uploads/${data.user_key}/profile/${data.photo_profile}`;
                }
                const addressParts = [data.address, data.subdistrict, data.district, data.user_province, data.zipcode].filter(Boolean);
                const fullAddress = addressParts.join(', ') || '-';

                const userDetails = `<h3 class="font-semibold text-base mb-2 uppercase tracking-wider text-slate-500">ข้อมูลผู้ยื่น</h3><div class="flex flex-col items-center"><div class="w-24 h-24 bg-base-300 rounded-lg p-1 mb-4"><img src="${profileImageSrc}" class="w-full h-full object-contain rounded-md cursor-pointer" onclick="zoomImage('${profileImageSrc}')" onerror="this.onerror=null;this.src='https://placehold.co/200x200/CCCCCC/FFFFFF?text=Error';"/></div><div class="text-center"><div class="font-bold">${data.user_title}${data.user_firstname} ${data.user_lastname}</div><div class="text-sm text-slate-500">${userTypeThai}</div></div><div class="divider my-2"></div><div class="w-full space-y-2 text-sm text-left"><div class="grid grid-cols-3 gap-2"><span class="text-slate-500 col-span-1">เบอร์โทร:</span><span class="font-semibold col-span-2">${data.phone_number || '-'}</span></div><div class="grid grid-cols-3 gap-2"><span class="text-slate-500 col-span-1">เลขบัตรฯ:</span><span class="font-semibold col-span-2">${data.national_id || '-'}</span></div><div class="grid grid-cols-3 gap-2"><span class="text-slate-500 col-span-1">วันเกิด:</span><span class="font-semibold col-span-2">${formatThaiDate(data.dob)}</span></div><div class="grid grid-cols-3 gap-2"><span class="text-slate-500 col-span-1">ที่อยู่:</span><span class="font-semibold col-span-2">${fullAddress}</span></div>${data.user_type === 'army' ? `<div class="divider my-1"></div><div class="grid grid-cols-3 gap-2"><span class="text-slate-500 col-span-1">สังกัด:</span><span class="font-semibold col-span-2">${data.work_department || '-'}</span></div><div class="grid grid-cols-3 gap-2"><span class="text-slate-500 col-span-1">ตำแหน่ง:</span><span class="font-semibold col-span-2">${data.position || '-'}</span></div><div class="grid grid-cols-3 gap-2"><span class="text-slate-500 col-span-1">เลข ขรก.:</span><span class="font-semibold col-span-2">${data.official_id || '-'}</span></div>` : ''}</div></div>`;
                const vehicleDetails = `<h3 class="font-semibold text-base mb-2 uppercase tracking-wider text-slate-500">ข้อมูลยานพาหนะ</h3><div class="space-y-3 text-sm"><div><div class="text-xs text-slate-500">ทะเบียน</div><div class="font-bold text-xl bg-base-300 text-center p-2 rounded-md">${data.license_plate} ${data.province}</div></div><div><div class="text-xs text-slate-500">ประเภท</div><div class="font-semibold">${data.vehicle_type}</div></div><div><div class="text-xs text-slate-500">ยี่ห้อ / รุ่น</div><div class="font-semibold">${data.brand} / ${data.model}</div></div><div><div class="text-xs text-slate-500">สี</div><div class="font-semibold">${data.color}</div></div><div><div class="text-xs text-slate-500">วันสิ้นภาษี</div><div class="font-semibold">${formatThaiDate(data.tax_expiry_date)}</div></div><div><div class="text-xs text-slate-500">ความเป็นเจ้าของ</div><div class="font-semibold">${ownerTypeThai} ${data.owner_type === 'other' ? `(${data.other_owner_name}, ${data.other_owner_relation})` : ''}</div></div><div><div class="text-xs text-slate-500">วันที่นัดรับบัตร</div><div class="font-semibold text-blue-600">${formatThaiDate(data.card_pickup_date)}</div></div></div>`;
                const vehicleImageBasePath = `/public/uploads/${data.user_key}/vehicle/${data.request_key}/`;
                const imageSection = `<h3 class="font-semibold text-base mb-2 uppercase tracking-wider text-slate-500">หลักฐาน</h3><div class="grid grid-cols-2 gap-2"><div class="text-center"><div class="bg-base-300 rounded-lg p-1 flex items-center justify-center h-28"><img src="${vehicleImageBasePath}${data.photo_reg_copy}" class="w-full h-full object-contain rounded-md cursor-pointer" onclick="zoomImage(this.src)"></div><p class="text-xs font-semibold mt-1">ทะเบียนรถ</p></div><div class="text-center"><div class="bg-base-300 rounded-lg p-1 flex items-center justify-center h-28"><img src="${vehicleImageBasePath}${data.photo_tax_sticker}" class="w-full h-full object-contain rounded-md cursor-pointer" onclick="zoomImage(this.src)"></div><p class="text-xs font-semibold mt-1">ป้ายภาษี</p></div><div class="text-center"><div class="bg-base-300 rounded-lg p-1 flex items-center justify-center h-28"><img src="${vehicleImageBasePath}${data.photo_front}" class="w-full h-full object-contain rounded-md cursor-pointer" onclick="zoomImage(this.src)"></div><p class="text-xs font-semibold mt-1">ด้านหน้า</p></div><div class="text-center"><div class="bg-base-300 rounded-lg p-1 flex items-center justify-center h-28"><img src="${vehicleImageBasePath}${data.photo_rear}" class="w-full h-full object-contain rounded-md cursor-pointer" onclick="zoomImage(this.src)"></div><p class="text-xs font-semibold mt-1">ด้านหลัง</p></div></div>`;
                const qrCodeSection = `<div id="qr-code-result" class="hidden mt-4"><div class="card bg-success/10 border-success border shadow-inner"><div class="card-body p-4 items-center text-center"><h3 class="card-title text-base text-success"><i class="fa-solid fa-check-circle mr-2"></i>อนุมัติสำเร็จ</h3><img id="qr-code-image" src="" class="w-32 h-32 rounded-lg p-1 mt-2 bg-white"><p class="text-xs text-slate-500 mt-2">QR Code สำหรับบัตรผ่านถูกสร้างเรียบร้อยแล้ว</p></div></div></div>`;
                modalBody.innerHTML = `<div class="grid grid-cols-1 md:grid-cols-3 gap-4">${historySection}<div class="p-4 rounded-lg bg-base-200">${userDetails}</div><div class="p-4 rounded-lg bg-base-200">${vehicleDetails}</div><div class="p-4 rounded-lg bg-base-200">${imageSection}</div></div>${qrCodeSection}`;
                
                modalActions.innerHTML = '<form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>';
                if (data.status === 'pending') {
                     modalActions.innerHTML = `<button id="reject-btn" class="btn btn-sm btn-error">ไม่ผ่าน</button><button id="approve-btn" class="btn btn-sm btn-success">อนุมัติ</button>` + modalActions.innerHTML;
                }
            }
            
            async function processRequest(requestId, action, reason = null) {
                const payload = { request_id: requestId, action: action, reason: reason };
                try {
                    const response = await fetch(`../../../controllers/admin/requests/process_request.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();
                    if (result.success) {
                        showAlert(result.message, 'success');
                        const row = document.querySelector(`tr[data-request-id="${requestId}"]`);
                        if(row) row.remove();
                        
                        const pendingStatEl = document.getElementById('stat-pending');
                        if(pendingStatEl) {
                             let pendingCount = parseInt(pendingStatEl.textContent.replace(/,/g, ''), 10);
                            pendingStatEl.textContent = (pendingCount > 0 ? pendingCount - 1 : 0).toLocaleString('en-US');
                        }

                        if (action === 'approve') {
                             const approvedStatEl = document.getElementById('stat-approved-total');
                            if(approvedStatEl) {
                                let approvedCount = parseInt(approvedStatEl.textContent.replace(/,/g, ''), 10);
                                approvedStatEl.textContent = (approvedCount + 1).toLocaleString('en-US');
                            }
                            document.getElementById('qr-code-image').src = result.qr_code_url;
                            document.getElementById('qr-code-result').classList.remove('hidden');
                            document.getElementById('modal-action-inspect').innerHTML = '<form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>';
                            document.getElementById('rejection-section').classList.add('hidden');
                        } else {
                            inspectModal.close();
                        }
                    } else {
                        showAlert(result.message, 'error');
                    }
                } catch (error) {
                     showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                }
            }

            function showConfirmModal(title, message, btnClass, callback) {
                const confirmModal = document.getElementById('confirmActionModal');
                confirmModal.querySelector('#confirm-title').textContent = title;
                confirmModal.querySelector('#confirm-message').textContent = message;
                const okBtn = confirmModal.querySelector('#confirm-ok-btn');
                okBtn.className = `btn btn-sm ${btnClass}`;
                okBtn.textContent = 'ยืนยัน';
                const newOkBtn = okBtn.cloneNode(true);
                okBtn.parentNode.replaceChild(newOkBtn, okBtn);
                newOkBtn.addEventListener('click', () => {
                    callback();
                    confirmModal.close();
                });
                confirmModal.querySelector('#confirm-cancel-btn').onclick = () => confirmModal.close();
                confirmModal.showModal();
            }

            inspectModal.addEventListener('click', function(e){
                const requestId = inspectModal.dataset.currentRequestId;
                if (e.target.id === 'approve-btn') {
                    showConfirmModal('อนุมัติคำร้อง', 'คุณต้องการยืนยันการอนุมัติคำร้องนี้ใช่หรือไม่?', 'btn-success', () => processRequest(requestId, 'approve'));
                } else if (e.target.id === 'reject-btn') {
                    rejectionSection.classList.remove('hidden');
                    modalActions.style.display = 'none';
                }
            });

            document.getElementById('cancel-reject-btn').addEventListener('click', () => {
                rejectionSection.classList.add('hidden');
                modalActions.style.display = '';
            });

            document.getElementById('confirm-reject-btn').addEventListener('click', () => {
                const reasonInput = document.getElementById('rejection-reason');
                const reason = reasonInput.value.trim();
                if(!reason){
                    reasonInput.classList.add('textarea-error');
                    document.getElementById('rejection-error-msg').classList.remove('hidden');
                    return;
                }
                const requestId = inspectModal.dataset.currentRequestId;
                processRequest(requestId, 'reject', reason);
            });
        }

        const userAdminTable = document.querySelector('#usersTable, #adminsTable');
        if (userAdminTable) {
             const searchInput = userAdminTable.closest('.card-body').querySelector('input[type="text"]');
             if (searchInput) {
                 searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    const tableBody = userAdminTable.querySelector('tbody');
                    const allRows = tableBody.querySelectorAll('tr');
                    const noResultsRow = tableBody.querySelector('#no-results-row');
                    let visibleCount = 0;
                    allRows.forEach(row => {
                        if (row.id === 'no-results-row') return;
                        const isVisible = row.textContent.toLowerCase().includes(searchTerm);
                        row.style.display = isVisible ? '' : 'none';
                        if (isVisible) visibleCount++;
                    });
                    if(noResultsRow) noResultsRow.style.display = visibleCount > 0 ? 'none' : 'table-row';
                });
             }
        }
    });
    </script>
</body>
</html>

