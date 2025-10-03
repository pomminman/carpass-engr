// public/assets/js/user/page_scripts/user_dashboard.js
CarPassApp.registerPageScript('dashboard', function(app) {
    const detailsModalEl = document.getElementById('request_details_modal');
    if (!detailsModalEl) return;

    const elements = {
        deleteModal: document.getElementById('delete_confirm_modal'),
        loadingModal: document.getElementById('loading_modal'),
        statFilters: document.querySelectorAll('.stat-filter'),
        searchInput: document.getElementById('search-input'),
        vehicleGrid: document.getElementById('vehicle-grid'),
        noResultsMessage: document.getElementById('no-results-message'),
        noRequestsMessage: document.getElementById('no-requests-message'),
        gridLoader: document.getElementById('grid-loader'),
        editForm: detailsModalEl.querySelector('#editVehicleForm'),
        vehicleCards: [], // Will be populated by AJAX
    };

    let currentCardData = null;

    const loadVehicleCards = async () => {
        try {
            const response = await fetch('../../../controllers/user/vehicle/fetch_vehicle_cards_html.php');
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const html = await response.text();
            
            if(elements.gridLoader) elements.gridLoader.style.display = 'none';

            if (html.trim() === '') {
                if(elements.noRequestsMessage) elements.noRequestsMessage.classList.remove('hidden');
            } else {
                elements.vehicleGrid.insertAdjacentHTML('beforeend', html);
                elements.vehicleCards = elements.vehicleGrid.querySelectorAll('.vehicle-card');
            }
        } catch (error) {
            console.error("Fetch error:", error);
            if(elements.gridLoader) {
                elements.gridLoader.innerHTML = '<p class="text-error">ไม่สามารถโหลดข้อมูลคำร้องได้</p>';
            }
        }
    };

    const resetEditFormValidation = () => {
        const editForm = elements.editForm;
        editForm.querySelectorAll('.input-error, .select-error, .file-input-error').forEach(el => {
            el.classList.remove('input-error', 'select-error', 'file-input-error');
        });
        editForm.querySelectorAll('.error-message').forEach(el => {
            el.classList.add('hidden');
            el.textContent = '';
        });
    };

    const createInfoRow = (label, value) => {
        if (value === null || value === undefined || value === '') return '';
        return `<div class="flex justify-between items-start gap-2"><span class="text-base-content/70 flex-shrink-0">${label}:</span><span class="font-semibold text-right break-words">${value}</span></div>`;
    };

    const populateDateSelects = (daySelect, monthSelect, yearSelect, selectedDate) => {
        daySelect.innerHTML = '<option disabled selected value="">วัน</option>';
        monthSelect.innerHTML = '<option disabled selected value="">เดือน</option>';
        yearSelect.innerHTML = '<option disabled selected value="">ปี</option>';
        for (let i = 1; i <= 31; i++) daySelect.add(new Option(i, i));
        const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
        months.forEach((m, i) => monthSelect.add(new Option(m, i + 1)));
        const currentYearBE = new Date().getFullYear() + 543;
        for (let i = currentYearBE; i <= currentYearBE + 10; i++) yearSelect.add(new Option(i, i));
        if (selectedDate) {
            const d = new Date(selectedDate);
            daySelect.value = d.getDate();
            monthSelect.value = d.getMonth() + 1;
            yearSelect.value = d.getFullYear() + 543;
        }
    };
    
    const openDetailsModal = async (card) => {
        const requestId = card.dataset.requestId;
        const modalContent = detailsModalEl.querySelector('#modal-content-wrapper');
        const modalLoader = detailsModalEl.querySelector('#modal-loader');

        modalContent.classList.add('hidden');
        modalLoader.classList.remove('hidden');
        detailsModalEl.querySelector('#modal-edit-form-wrapper').classList.add('hidden');
        detailsModalEl.showModal();

        try {
            const response = await fetch(`../../../controllers/user/vehicle/get_request_details.php?id=${requestId}`);
            if (!response.ok) throw new Error('Network response was not ok.');
            const result = await response.json();
            if (!result.success) throw new Error(result.message || 'Failed to fetch data.');
            
            const data = result.data;
            currentCardData = data;

            const queryAndSet = (selector, content, isHtml = false) => {
                const el = detailsModalEl.querySelector(selector);
                if (el) isHtml ? el.innerHTML = content : el.textContent = content;
            };
            
            const is_expired = data.card_expiry && (new Date() > new Date(data.card_expiry));
            const status_key = (data.status === 'approved' && is_expired) ? 'expired' : data.status;
            
            const statusMap = {
                'approved': {icon: 'fa-solid fa-check-circle', text: 'อนุมัติแล้ว', badge_bg: 'bg-success/10 text-success'},
                'pending': {icon: 'fa-solid fa-clock', text: 'รออนุมัติ', badge_bg: 'bg-warning/10 text-warning'},
                'rejected': {icon: 'fa-solid fa-ban', text: 'ไม่ผ่าน', badge_bg: 'bg-error/10 text-error'},
                'expired': {icon: 'fa-solid fa-calendar-xmark', text: 'หมดอายุ', badge_bg: 'bg-base-300 text-base-content'}
            };
            const statusInfo = statusMap[status_key];
            queryAndSet('#modal-card-status', `<div class="p-2 rounded-lg inline-flex items-center justify-center gap-2 text-sm font-semibold ${statusInfo.badge_bg}"><i class="${statusInfo.icon}"></i><span>${statusInfo.text}</span></div>`, true);
            queryAndSet('#modal-license-plate', `${data.license_plate} ${data.vehicle_province}`);
            queryAndSet('#modal-brand-model', `${data.brand} / ${data.model}`);
            detailsModalEl.querySelector('#modal-rejection-reason-box').classList.toggle('hidden', !(status_key === 'rejected' && data.rejection_reason));
            queryAndSet('#modal-rejection-reason-text', data.rejection_reason || '');

            let ownerHtml = createInfoRow('ความเป็นเจ้าของ', data.owner_type === 'self' ? 'รถชื่อตนเอง' : 'รถคนอื่น');
            if (data.owner_type === 'other') {
                ownerHtml += createInfoRow('ชื่อเจ้าของ', data.other_owner_name || '-');
                ownerHtml += createInfoRow('เกี่ยวข้องเป็น', data.other_owner_relation || '-');
            }
            queryAndSet('#modal-vehicle-info-list', createInfoRow('ประเภท', data.vehicle_type) + createInfoRow('สี', data.color), true);
            queryAndSet('#modal-owner-info-list', ownerHtml, true);
            let cardInfoHtml = createInfoRow('รหัสคำร้อง', data.search_id);
            cardInfoHtml += createInfoRow('วันยื่นคำร้อง', app.formatThaiDateTime(data.created_at));
            if (status_key === 'approved' || status_key === 'expired') {
                cardInfoHtml += createInfoRow('วันอนุมัติ', app.formatThaiDateTime(data.approved_at));
                cardInfoHtml += createInfoRow('วันหมดอายุ', app.formatThaiDate(data.card_expiry));
            }
            queryAndSet('#modal-card-info-list', cardInfoHtml, true);
            const cardNumberBox = detailsModalEl.querySelector('#modal-card-number-box');
            const isCardNumberVisible = status_key === 'approved' || status_key === 'expired';
            cardNumberBox.classList.toggle('hidden', !isCardNumberVisible);
            if (isCardNumberVisible) {
                cardNumberBox.querySelector('span').textContent = data.card_number || '-';
                cardNumberBox.className = `card text-center p-2 ${status_key === 'approved' ? 'bg-success text-success-content' : 'bg-base-300'}`;
            }

            const basePath = `/public/uploads/${data.user_key}/vehicle/${data.request_key}/`;
            const galleryHTML = `
                <div class="text-center"><p class="font-semibold mb-1 text-sm">ทะเบียนรถ</p><a href="${basePath + data.photo_reg_copy}" class="modal-gallery-item" data-caption="สำเนาทะเบียนรถ: ${data.license_plate}"><div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24"><img src="${basePath + data.photo_reg_copy_thumb}" class="max-w-full max-h-full object-contain cursor-pointer" alt="สำเนาทะเบียนรถ"></div></a></div>
                <div class="text-center"><p class="font-semibold mb-1 text-sm">ป้ายภาษี</p><a href="${basePath + data.photo_tax_sticker}" class="modal-gallery-item" data-caption="ป้ายภาษี: ${data.license_plate}"><div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24"><img src="${basePath + data.photo_tax_sticker_thumb}" class="max-w-full max-h-full object-contain cursor-pointer" alt="ป้ายภาษี"></div></a></div>
                <div class="text-center"><p class="font-semibold mb-1 text-sm">ด้านหน้า</p><a href="${basePath + data.photo_front}" class="modal-gallery-item" data-caption="รูปถ่ายด้านหน้า: ${data.license_plate}"><div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24"><img src="${basePath + data.photo_front_thumb}" class="max-w-full max-h-full object-contain cursor-pointer" alt="รูปถ่ายด้านหน้า"></div></a></div>
                <div class="text-center"><p class="font-semibold mb-1 text-sm">ด้านหลัง</p><a href="${basePath + data.photo_rear}" class="modal-gallery-item" data-caption="รูปถ่ายด้านหลัง: ${data.license_plate}"><div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24"><img src="${basePath + data.photo_rear_thumb}" class="max-w-full max-h-full object-contain cursor-pointer" alt="รูปถ่ายด้านหลัง"></div></a></div>`;
            queryAndSet('#modal-evidence-gallery', galleryHTML, true);
            
            let buttonsHtml = '';
            if (data.can_renew === 'true') buttonsHtml += `<a href="add_vehicle.php?renew_id=${data.vehicle_id}" class="btn btn-sm btn-success"><i class="fa-solid fa-calendar-check"></i>ต่ออายุบัตร</a>`;
            if (status_key === 'pending' || status_key === 'rejected') buttonsHtml += `<button id="modal-edit-btn" class="btn btn-sm btn-warning"><i class="fa-solid fa-pencil"></i>แก้ไข</button>`;
            if (status_key !== 'approved') buttonsHtml += `<button id="modal-delete-btn" class="btn btn-sm btn-error"><i class="fa-solid fa-trash-can"></i>ลบ</button>`;
            queryAndSet('#modal-action-buttons', `<div class="flex-grow"></div>${buttonsHtml}`, true);

            modalLoader.classList.add('hidden');
            modalContent.classList.remove('hidden');

        } catch (error) {
            console.error("Error opening details modal:", error);
            app.showAlert('เกิดข้อผิดพลาดในการดึงข้อมูลคำร้อง', 'error');
            detailsModalEl.close();
        }
    };

    const switchToEditMode = () => {
        const data = currentCardData;
        if (!data) return;

        const editForm = elements.editForm;
        const basePath = `/public/uploads/${data.user_key}/vehicle/${data.request_key}/`;

        // Handle license plate editability
        const licenseSection = editForm.querySelector('#edit-license-section');
        const licensePlateInput = editForm.querySelector('#edit-license-plate');
        const licenseProvinceSelect = editForm.querySelector('#edit-license-province');
        const canEditLicenseHiddenInput = editForm.querySelector('#edit-can-edit-license');

        if (data.can_edit_license === 'true') {
            licenseSection.classList.remove('hidden');
            licensePlateInput.value = data.license_plate;
            licenseProvinceSelect.value = data.vehicle_province;
            licensePlateInput.setAttribute('required', '');
            licenseProvinceSelect.setAttribute('required', '');
            canEditLicenseHiddenInput.value = 'true';
        } else {
            licenseSection.classList.add('hidden');
            licensePlateInput.removeAttribute('required');
            licenseProvinceSelect.removeAttribute('required');
            canEditLicenseHiddenInput.value = 'false';
            app.clearError(licensePlateInput);
            app.clearError(licenseProvinceSelect);
        }

        editForm.querySelector('#edit-request-id').value = data.id;
        editForm.querySelector('#edit-vehicle-brand').value = data.brand;
        editForm.querySelector('#edit-vehicle-model').value = data.model;
        editForm.querySelector('#edit-vehicle-color').value = data.color;
        
        const taxDate = data.tax_expiry_date ? new Date(data.tax_expiry_date) : null;
        populateDateSelects(editForm.querySelector('#edit-tax-day'), editForm.querySelector('#edit-tax-month'), editForm.querySelector('#edit-tax-year'), taxDate);
        
        const ownerSelect = editForm.querySelector('#edit-owner-type');
        ownerSelect.value = data.owner_type;
        if (data.owner_type === 'other') {
            editForm.querySelector('#edit-other-owner-name').value = data.other_owner_name;
            editForm.querySelector('#edit-other-owner-relation').value = data.other_owner_relation;
        }
        ownerSelect.dispatchEvent(new Event('change'));

        const updateLink = (id, newSrc) => {
            const img = editForm.querySelector(id);
            img.src = newSrc;
            if (img.parentElement.tagName === 'A') img.parentElement.href = newSrc;
        };
        
        updateLink('#edit-reg-copy-preview', basePath + data.photo_reg_copy_thumb);
        updateLink('#edit-tax-sticker-preview', basePath + data.photo_tax_sticker_thumb);
        updateLink('#edit-front-view-preview', basePath + data.photo_front_thumb);
        updateLink('#edit-rear-view-preview', basePath + data.photo_rear_thumb);

        detailsModalEl.querySelector('#modal-content-wrapper').classList.add('hidden');
        detailsModalEl.querySelector('#modal-edit-form-wrapper').classList.remove('hidden');
    };

    const filterAndSearchCards = () => {
        const filterKey = document.querySelector('.stat-filter.active')?.dataset.filter || 'all';
        const searchTerm = elements.searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        elements.vehicleCards.forEach(card => {
            const isVisible = (filterKey === 'all' || card.dataset.statusKey === filterKey) && (searchTerm === '' || (card.textContent || '').toLowerCase().includes(searchTerm));
            card.style.display = isVisible ? 'flex' : 'none';
            if (isVisible) visibleCount++;
        });
        
        const hasCards = elements.vehicleCards.length > 0;
        if(elements.noResultsMessage) elements.noResultsMessage.style.display = (visibleCount === 0 && hasCards) ? 'block' : 'none';
    };
    
    elements.vehicleGrid.addEventListener('click', (e) => {
        const card = e.target.closest('.vehicle-card');
        if (card && !e.target.closest('a, button')) {
            openDetailsModal(card);
        }
    });

    elements.statFilters.forEach(filter => filter.addEventListener('click', () => {
        elements.statFilters.forEach(f => f.classList.remove('active', 'ring-2', 'ring-primary'));
        filter.classList.add('active', 'ring-2', 'ring-primary');
        filterAndSearchCards();
    }));
    
    elements.searchInput.addEventListener('input', filterAndSearchCards);

    detailsModalEl.addEventListener('click', (e) => {
        if (e.target.id === 'modal-edit-btn') switchToEditMode();
        if (e.target.id === 'modal-delete-btn' && elements.deleteModal) {
            elements.deleteModal.querySelector('#delete-request-id').value = currentCardData.id;
            elements.deleteModal.showModal();
        }
        if (e.target.id === 'cancel-edit-btn') {
            resetEditFormValidation();
            detailsModalEl.querySelector('#modal-edit-form-wrapper').classList.add('hidden');
            detailsModalEl.querySelector('#modal-content-wrapper').classList.remove('hidden');
        }
         const galleryLink = e.target.closest('.modal-gallery-item');
        if (galleryLink) {
            e.preventDefault();
            e.stopPropagation();
            const allClickableImages = Array.from(detailsModalEl.querySelectorAll('.modal-gallery-item'));
            const slides = allClickableImages.map(el => ({ src: el.href, caption: el.dataset.caption }));
            const startIndex = allClickableImages.indexOf(galleryLink);
            const modalBox = detailsModalEl.querySelector('.modal-box');
            if(modalBox) modalBox.style.opacity = '0';
            Fancybox.show(slides, {
                startIndex: startIndex,
                parentEl: detailsModalEl,
                on: { close: () => { if(modalBox) modalBox.style.opacity = '1'; } }
            });
        }
    });

    elements.editForm.addEventListener('submit', (e) => {
        let isAllValid = true;
        elements.editForm.querySelectorAll('input[required], select[required]').forEach(field => {
            if (field.offsetParent !== null) { // Check only visible fields
                if (!app.validateField(field)) {
                    isAllValid = false;
                }
            }
        });

        if (!isAllValid) {
            e.preventDefault();
            const firstErrorField = elements.editForm.querySelector('.input-error, .select-error, .file-input-error');
            if (firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } else {
            if (elements.loadingModal) elements.loadingModal.showModal();
        }
    });
    
    const editFormContainer = detailsModalEl.querySelector('#modal-edit-form-wrapper');
    const validationHandler = (event) => {
        const field = event.target;
        if (field.name && (field.tagName === 'INPUT' || field.tagName === 'SELECT')) {
            app.validateField(field);
        }
    };
    editFormContainer.addEventListener('input', validationHandler);
    editFormContainer.addEventListener('change', validationHandler);

    elements.editForm.querySelector('#edit-owner-type').addEventListener('change', e => {
        const otherDetails = elements.editForm.querySelector('#edit-other-owner-details');
        const inputs = otherDetails.querySelectorAll('input');
        const isOther = e.target.value === 'other';

        otherDetails.classList.toggle('hidden', !isOther);
        
        inputs.forEach(input => {
            if (isOther) {
                input.setAttribute('required', '');
                app.validateField(input); 
            } else {
                input.removeAttribute('required');
                app.clearError(input);
            }
        });
    });
    
    if (elements.deleteModal) {
        elements.deleteModal.querySelector('#deleteRequestForm').addEventListener('submit', () => {
            elements.deleteModal.close();
            if (elements.loadingModal) elements.loadingModal.showModal();
        });
    }

    ['edit-reg-copy-upload', 'edit-tax-sticker-upload', 'edit-front-view-upload', 'edit-rear-view-upload'].forEach((id, index) => {
        app.setupImagePreview(id, ['edit-reg-copy-preview', 'edit-tax-sticker-preview', 'edit-front-view-preview', 'edit-rear-view-preview'][index]);
    });

    loadVehicleCards();
});

