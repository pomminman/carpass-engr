/**
 * admin_core.js
 * Contains shared functionalities for the entire admin panel.
 * This includes alerts, global search, table sorting, and the request details modal logic.
 */
document.addEventListener('DOMContentLoaded', function () {

    const App = {
        showAlert: function(message, type = 'success') {
            const container = document.getElementById('alert-container');
            if (!container) return;
            const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', info: 'fa-circle-info', warning: 'fa-triangle-exclamation' };
            const iconClass = icons[type] || 'fa-circle-info';
            const alertEl = document.createElement('div');
            alertEl.className = `alert alert-${type} alert-soft shadow-lg`;
            alertEl.innerHTML = `<div class="flex items-center"><i class="fa-solid ${iconClass}"></i><span class="ml-2 text-xs sm:text-sm">${message}</span></div>`;
            container.appendChild(alertEl);
            setTimeout(() => {
                alertEl.style.transition = 'opacity 0.3s ease';
                alertEl.style.opacity = '0';
                setTimeout(() => alertEl.remove(), 300);
            }, 5000);
        },

        initGlobalHelpers: function() {
            const flashMessage = document.body.dataset.flashMessage;
            const flashStatus = document.body.dataset.flashStatus;
            if (flashMessage && flashStatus) {
                this.showAlert(flashMessage, flashStatus);
            }
        },

        initGlobalSearch: function() {
            const searchInput = document.getElementById('searchInput');
            if(searchInput) {
                searchInput.addEventListener('input', () => {
                    const activeTable = document.querySelector('table.table');
                    if (!activeTable) return;
                    const searchTerm = searchInput.value.toLowerCase();
                    const rows = activeTable.querySelectorAll('tbody tr');
                    let found = false;
                    rows.forEach(row => {
                        const noResultsRow = row.querySelector('td[colspan]');
                        if (noResultsRow) return;
                        const isVisible = row.textContent.toLowerCase().includes(searchTerm);
                        row.style.display = isVisible ? '' : 'none';
                        if (isVisible) found = true;
                    });
                    const noResultsRow = activeTable.querySelector('#no-results-row, #no-search-results-row');
                    if(noResultsRow) noResultsRow.style.display = found ? 'none' : '';
                });
            }
        },

        initGlobalTableSorting: function() {
            document.querySelectorAll('th[data-sort-by]').forEach(header => {
                header.addEventListener('click', () => {
                    const table = header.closest('table');
                    if (!table) return;
                    const tbody = table.querySelector('tbody');
                    if (!tbody) return;
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const sortKey = header.dataset.sortBy;
                    const isAsc = header.classList.contains('sort-asc');
                    const direction = isAsc ? -1 : 1;
                    const sortedRows = rows.sort((a, b) => {
                        const isAMsgRow = a.querySelector('td[colspan]');
                        const isBMsgRow = b.querySelector('td[colspan]');
                        if (isAMsgRow && isBMsgRow) return 0;
                        if (isAMsgRow) return 1;
                        if (isBMsgRow) return -1;
                        const aCell = a.querySelector(`[data-cell="${sortKey}"]`);
                        const bCell = b.querySelector(`[data-cell="${sortKey}"]`);
                        if (!aCell || !bCell) return 0;
                        const aVal = aCell.dataset.sortValue || aCell.textContent.trim().toLowerCase();
                        const bVal = bCell.dataset.sortValue || bCell.textContent.trim().toLowerCase();
                        if (aVal > bVal) return 1 * direction;
                        if (aVal < bVal) return -1 * direction;
                        return 0;
                    });
                    table.querySelectorAll('th[data-sort-by]').forEach(th => {
                        th.classList.remove('sort-asc', 'sort-desc');
                        const icon = th.querySelector('i');
                        if(icon) icon.className = 'fa-solid fa-sort';
                    });
                    const headerIcon = header.querySelector('i');
                    if(headerIcon) {
                        header.classList.toggle('sort-asc', !isAsc);
                        header.classList.toggle('sort-desc', isAsc);
                        headerIcon.className = `fa-solid ${!isAsc ? 'fa-sort-up' : 'fa-sort-down'}`;
                    }
                    sortedRows.forEach(row => tbody.appendChild(row));
                });
            });
        },
        
        populateDetailsModal: function(data) {
            const detailsModal = document.getElementById('details_modal');
            if (!detailsModal) return;

            const orDash = (text) => text || '-';
            const placeholderUrl = 'https://placehold.co/400x300/e2e8f0/475569?text=No+Image';
            const setText = (selector, text) => { const el = detailsModal.querySelector(selector); if (el) el.textContent = orDash(text); };
            const setHtml = (selector, html) => { const el = detailsModal.querySelector(selector); if (el) el.innerHTML = html; };
            const setImageSource = (linkSelector, imgSelector, basePath, filename, caption) => {
                const link = detailsModal.querySelector(linkSelector);
                const img = detailsModal.querySelector(imgSelector);
                if (!link || !img) return;
                link.dataset.caption = caption;
                if (filename && basePath) {
                    const src = basePath + filename;
                    img.src = src;
                    link.href = src;
                } else {
                    img.src = placeholderUrl;
                    link.href = placeholderUrl;
                    link.dataset.caption = caption + " (ไม่มีรูปภาพ)";
                }
            };
            const formatThaiDate = (d) => !d || d.startsWith('0000') ? '-' : new Date(d).toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' });
            
            const licensePlateFull = `${orDash(data.license_plate)} ${orDash(data.vehicle_province)}`;
            setText('#modal-header-license', licensePlateFull);
            setText('#modal-header-vehicle', `${orDash(data.brand)} / ${orDash(data.model)}`);
            const userFullName = `${orDash(data.user_title)} ${orDash(data.user_firstname)} ${orDash(data.user_lastname)}`;
            setText('#modal-user-name', userFullName);
            setText('#modal-user-phone', orDash(data.phone_number));
            setText('#modal-user-nid', orDash(data.national_id));
            const fullAddress = data.address ? `${data.address} ต.${data.subdistrict} อ.${data.district} จ.${data.user_province} ${data.zipcode}` : null;
            setText('#modal-user-address', orDash(fullAddress));
            setImageSource('#modal-user-photo-link', '#modal-user-photo', `/public/uploads/${data.user_key}/profile/`, data.photo_profile, `รูปถ่ายหน้าตรง: ${userFullName}`);
            const userTypeBadgeHtml = data.user_type === 'army' ? `<div class="badge badge-outline badge-sm h-auto">กำลังพล ทบ.</div>` : `<div class="badge badge-outline badge-sm h-auto">บุคคลภายนอก</div>`;
            setHtml('#modal-user-type-badge', userTypeBadgeHtml);
            const workInfo = detailsModal.querySelector('#modal-work-info-container');
            if (data.user_type === 'army') {
                setText('#modal-user-department', orDash(data.work_department));
                setText('#modal-user-position', orDash(data.position));
                setText('#modal-user-official-id', orDash(data.official_id));
                workInfo.classList.remove('hidden');
            } else { workInfo.classList.add('hidden'); }
            const creatorContainer = detailsModal.querySelector('#modal-creator-info-container');
            if (data.created_by_admin_id) {
                const creatorName = `${orDash(data.creator_title)} ${orDash(data.creator_firstname)} ${orDash(data.creator_lastname)}`;
                setText('#modal-creator-name', creatorName);
                creatorContainer.classList.remove('hidden');
            } else { creatorContainer.classList.add('hidden'); }
            setText('#modal-vehicle-type', orDash(data.vehicle_type));
            setText('#modal-card-type', data.card_type ? (data.card_type === 'internal' ? 'ภายใน' : 'ภายนอก') : '-');
            setText('#modal-vehicle-color', orDash(data.color));
            setText('#modal-tax-expiry', formatThaiDate(data.tax_expiry_date));
            let ownerDetails = '-';
            if (data.owner_type) { ownerDetails = data.owner_type === 'self' ? 'รถชื่อตนเอง' : `รถผู้อื่น (${orDash(data.other_owner_name)}, เกี่ยวข้องเป็น ${orDash(data.other_owner_relation)})`; }
            setText('#modal-owner-details', ownerDetails);
            const evidenceBasePath = data.user_key && data.request_key ? `/public/uploads/${data.user_key}/vehicle/${data.request_key}/` : null;
            setImageSource('a[data-base-caption="สำเนาทะเบียนรถ"]', '#modal-evidence-reg', evidenceBasePath, data.photo_reg_copy, `สำเนาทะเบียนรถ: ${licensePlateFull}`);
            setImageSource('a[data-base-caption="ป้ายภาษี"]', '#modal-evidence-tax', evidenceBasePath, data.photo_tax_sticker, `ป้ายภาษี: ${licensePlateFull}`);
            setImageSource('a[data-base-caption="รูปถ่ายด้านหน้า"]', '#modal-evidence-front', evidenceBasePath, data.photo_front, `รูปถ่ายด้านหน้า: ${licensePlateFull}`);
            setImageSource('a[data-base-caption="รูปถ่ายด้านหลัง"]', '#modal-evidence-rear', evidenceBasePath, data.photo_rear, `รูปถ่ายด้านหลัง: ${licensePlateFull}`);

            const rejectionContainer = detailsModal.querySelector('#modal-rejection-info-container');
            const qrContainer = detailsModal.querySelector('#modal-qrcode-container');
            const actionButtonsContainer = detailsModal.querySelector('#modal-action-buttons');
            rejectionContainer.classList.add('hidden');
            qrContainer.classList.add('hidden');
            actionButtonsContainer.innerHTML = '';
            let statusBadgeHtml = '';

            if (data.status === 'approved' && data.card_expiry && (new Date() > new Date(data.card_expiry))) {
                statusBadgeHtml = `<div class="badge badge-neutral">หมดอายุ</div>`;
                const qrImg = detailsModal.querySelector('#modal-qrcode-img');
                if (qrImg && data.qr_code_path) { qrImg.src = `/public/qr/${data.qr_code_path}`; qrContainer.classList.remove('hidden'); }
            } else {
                switch (data.status) {
                    case 'approved':
                        statusBadgeHtml = `<div class="badge badge-success">อนุมัติแล้ว</div>`;
                        const qrImg = detailsModal.querySelector('#modal-qrcode-img');
                        if (qrImg && data.qr_code_path) { qrImg.src = `/public/qr/${data.qr_code_path}`; qrContainer.classList.remove('hidden'); }
                        break;
                    case 'pending':
                        statusBadgeHtml = `<div class="badge badge-warning">รออนุมัติ</div>`;
                        actionButtonsContainer.innerHTML = `<button id="modal-reject-btn" class="btn btn-sm btn-error">ปฏิเสธ</button><button id="modal-approve-btn" class="btn btn-sm btn-success">อนุมัติคำร้อง</button>`;
                        break;
                    case 'rejected':
                        statusBadgeHtml = `<div class="badge badge-error">ไม่ผ่าน</div>`;
                        setText('#modal-rejection-reason', data.rejection_reason);
                        rejectionContainer.classList.remove('hidden');
                        actionButtonsContainer.innerHTML = `<button id="modal-approve-btn" class="btn btn-sm btn-success">อนุมัติคำร้องอีกครั้ง</button>`;
                        break;
                }
            }
            setHtml('#modal-header-status-badge', statusBadgeHtml);
        },

        initRequestManagement: function() {
            const detailsModal = document.getElementById('details_modal');
            const loadingModal = document.getElementById('loading_modal');
            const rejectModal = document.getElementById('reject_reason_modal');
            const resultModal = document.getElementById('result_modal');
            const confirmationModal = document.getElementById('confirmation_modal');
            if (!detailsModal) return;
            let currentRequestId = null, currentAction = null, currentReason = '';
            
            const openDetailsModal = async (requestId) => {
                currentRequestId = requestId;
                loadingModal.showModal();
                try {
                    fetch('/app/controllers/admin/activity/log_admin_process.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ request_id: requestId })
                    }).then(res => res.json()).then(result => {
                        if (result.success) {
                            const row = document.querySelector(`tr[data-request-id="${requestId}"]`);
                            const notification = row ? row.querySelector('.notification-badge') : null;
                            if (notification) notification.remove();
                        }
                    });
                } catch(e) { console.error("Logging failed", e); }
                try {
                    const response = await fetch(`/app/controllers/admin/requests/check_requests.php?action=get_details&id=${requestId}`);
                    const result = await response.json();
                    if (result.success) {
                        this.populateDetailsModal(result.data);
                        detailsModal.showModal();
                    } else { throw new Error(result.message); }
                } catch (error) { this.showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                } finally { loadingModal.close(); }
            };

            const showConfirmationModal = (action, reason = '') => {
                currentAction = action; currentReason = reason;
                const title = confirmationModal.querySelector('#confirmation-modal-title');
                const text = confirmationModal.querySelector('#confirmation-modal-text');
                const confirmBtn = confirmationModal.querySelector('#confirm-action-btn');
                if (action === 'approve') {
                    title.textContent = 'ยืนยันการอนุมัติคำร้อง';
                    text.textContent = 'คุณแน่ใจหรือไม่ว่าต้องการอนุมัติคำร้องนี้?';
                    confirmBtn.className = 'btn btn-sm btn-success';
                } else {
                    title.textContent = 'ยืนยันการปฏิเสธคำร้อง';
                    text.innerHTML = `คุณแน่ใจหรือไม่ว่าต้องการปฏิเสธคำร้องนี้ด้วยเหตุผล:<br><strong class="whitespace-pre-wrap">${reason}</strong>`;
                    confirmBtn.className = 'btn btn-sm btn-error';
                }
                confirmationModal.showModal();
            };

            const processRequest = async () => {
                loadingModal.showModal();
                try {
                    const response = await fetch(`/app/controllers/admin/requests/check_requests.php?action=process_request`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ request_id: currentRequestId, action: currentAction, reason: currentReason })
                    });
                    const result = await response.json();
                    if (result.success) {
                        detailsModal.close();
                        if(rejectModal) rejectModal.close();
                        populateResultModal(result);
                    } else { throw new Error(result.message); }
                } catch (error) { this.showAlert(error.message || 'เกิดข้อผิดพลาด', 'error');
                } finally { loadingModal.close(); }
            };
            
            const populateResultModal = (result) => {
                const data = result.request_data;
                const setText = (selector, text) => { resultModal.querySelector(selector).textContent = text || '-'; };
                
                setText('#result-modal-user-name', `${data.user_title} ${data.user_firstname} ${data.user_lastname}`);
                setText('#result-modal-user-address', `${data.address} ต.${data.subdistrict} อ.${data.district} จ.${data.user_province} ${data.zipcode}`);
                setText('#result-modal-license', `${data.license_plate} ${data.vehicle_province}`);
                setText('#result-modal-vehicle', `${data.brand} / ${data.model}`);
                
                const title = resultModal.querySelector('#result-modal-title');
                const subtitle = resultModal.querySelector('#result-modal-subtitle');
                const output = resultModal.querySelector('#result-modal-output');
                const header = resultModal.querySelector('#result-modal-header');
                if (result.qr_code_url) {
                    title.innerHTML = `<i class="fa-solid fa-check-circle text-success mr-2"></i>อนุมัติคำร้องสำเร็จ`;
                    subtitle.textContent = `รหัสคำร้อง: ${data.search_id}`;
                    output.innerHTML = `<div class="card bg-base-100 border shadow-sm"><div class="card-body p-3 items-center"><h4 class="card-title text-base font-semibold">QR Code สำหรับบัตรผ่าน</h4><img src="${result.qr_code_url}" alt="QR Code" class="mt-2 border-4 border-base-300 p-1 rounded-lg w-40 h-40"></div></div>`;
                    header.className = "flex justify-between items-start gap-4 p-4 bg-success/10";
                } else if (data.rejection_reason) {
                    title.innerHTML = `<i class="fa-solid fa-times-circle text-error mr-2"></i>ปฏิเสธคำร้องเรียบร้อยแล้ว`;
                    subtitle.textContent = `รหัสคำร้อง: ${data.search_id}`;
                    output.innerHTML = `<div class="card bg-base-100 border shadow-sm"><div class="card-body p-3"><h4 class="card-title text-base font-semibold">เหตุผลที่ปฏิเสธ</h4><p class="text-sm mt-2 whitespace-pre-wrap">${data.rejection_reason}</p></div></div>`;
                    header.className = "flex justify-between items-start gap-4 p-4 bg-error/10";
                }
                resultModal.showModal();
            };
            
            detailsModal.addEventListener('click', (e) => {
                const galleryLink = e.target.closest('.modal-gallery-item, #modal-user-photo-link');
                if (galleryLink) {
                    e.preventDefault(); e.stopPropagation();
                    const allClickableImages = detailsModal.querySelectorAll('.modal-gallery-item, #modal-user-photo-link');
                    const slides = Array.from(allClickableImages).map(el => ({ src: el.href, caption: el.dataset.caption }));
                    const modalBox = detailsModal.querySelector('.modal-box');
                    if (modalBox) modalBox.style.opacity = '0';
                    Fancybox.show(slides, {
                        startIndex: Array.from(allClickableImages).indexOf(galleryLink),
                        animation: "fade", parentEl: detailsModal,
                        Toolbar: { display: { left: ["infobar"], middle: ["zoomIn", "zoomOut", "toggle1to1", "rotateCCW", "rotateCW", "flipX", "flipY"], right: ["slideshow", "thumbs", "close"] } },
                        on: { close: () => { if (modalBox) modalBox.style.opacity = '1'; } }
                    });
                    return;
                }
                if (e.target.id === 'modal-approve-btn') showConfirmationModal('approve');
                if (e.target.id === 'modal-reject-btn') { detailsModal.close(); rejectModal.showModal(); }
            });

            document.body.addEventListener('click', (e) => {
                const inspectButton = e.target.closest('.inspect-btn');
                if (inspectButton) { openDetailsModal(inspectButton.dataset.id); }
            });

            if(rejectModal) {
                const rejectForm = document.getElementById('rejectReasonForm');
                if(rejectForm) {
                    rejectForm.addEventListener('submit', (e) => {
                        e.preventDefault();
                        const reason = document.getElementById('rejection_reason_text').value;
                        if (reason.trim()) {
                            rejectModal.close();
                            showConfirmationModal('reject', reason);
                        } else { this.showAlert('กรุณาระบุเหตุผล', 'error'); }
                    });
                }
            }
            if(confirmationModal) { document.getElementById('confirm-action-btn').addEventListener('click', () => { confirmationModal.close(); processRequest(); }); }
            if(resultModal) {
                document.getElementById('result-modal-close-btn').addEventListener('click', () => { resultModal.close(); window.location.reload(); });
                document.getElementById('result-modal-close-btn-x').addEventListener('click', () => { resultModal.close(); window.location.reload(); });
            }
        }
    };

    window.App = App;
    App.initGlobalHelpers();
    App.initGlobalSearch();
    App.initGlobalTableSorting();
    
    if (document.getElementById('dashboard-page') || document.getElementById('manage-requests-page') || document.getElementById('view-user-page')) {
        App.initRequestManagement();
    }
});

