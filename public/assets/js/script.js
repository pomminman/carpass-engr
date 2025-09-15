document.addEventListener('DOMContentLoaded', function () {
    
    /**
     * Main application object.
     */
    const App = {
        /**
         * Initializes all functionalities.
         */
        init: function() {
            this.initGlobalHelpers();
            if (document.getElementById('vehicle-grid')) {
                this.initDashboard();
            }
            if (document.getElementById('add-vehicle-section')) {
                this.initAddVehiclePage();
            }
             if (document.getElementById('profile-section')) {
                this.initProfilePage();
            }
        },

        /**
         * Initializes global helper functions and listeners.
         */
        initGlobalHelpers: function() {
            window.showAlert = (message, type = 'success') => {
                const container = document.getElementById('alert-container');
                if (!container) return;
                
                const icons = {
                    success: 'fa-solid fa-circle-check',
                    error: 'fa-solid fa-circle-xmark',
                    info: 'fa-solid fa-circle-info',
                    warning: 'fa-solid fa-triangle-exclamation',
                };
                
                const alertEl = document.createElement('div');
                alertEl.className = `alert alert-${type} alert-soft shadow-lg`;
                alertEl.innerHTML = `<div class="flex items-center"><i class="${icons[type]}"></i><span class="ml-2 text-xs sm:text-sm">${message}</span></div>`;
                container.appendChild(alertEl);

                setTimeout(() => {
                    alertEl.style.transition = 'opacity 0.3s ease';
                    alertEl.style.opacity = '0';
                    setTimeout(() => alertEl.remove(), 300);
                }, 3000);
            };

            // Automatically show flash messages from PHP sessions
            const flashMessage = document.body.dataset.flashMessage;
            const flashStatus = document.body.dataset.flashStatus;
            if (flashMessage && flashStatus) {
                showAlert(flashMessage, flashStatus);
            }
        },

        /**
         * Initializes functionalities for the dashboard page.
         */
        initDashboard: function() {
            const detailsModalEl = document.getElementById('request_details_modal');
            if (!detailsModalEl) return;
            const deleteModalEl = document.getElementById('delete_confirm_modal');
            const loadingModalEl = document.getElementById('loading_modal');
            const vehicleCards = document.querySelectorAll('.vehicle-card');
            const statFilters = document.querySelectorAll('.stat-filter');
            const noResultsMessage = document.getElementById('no-results-message');
            const searchInput = document.getElementById('search-input');
            
            let currentCardData = null;

            const formatThaiDate = (dateString) => {
                if (!dateString || dateString === '0000-00-00 00:00:00' || dateString === '0000-00-00') return '-';
                try {
                    const date = new Date(dateString);
                    const thaiMonths = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
                    return `${date.getDate()} ${thaiMonths[date.getMonth()]} ${date.getFullYear() + 543}`;
                } catch (e) { return '-'; }
            };

            const formatThaiDateTime = (dateTimeString) => {
                if (!dateTimeString || dateTimeString === '0000-00-00 00:00:00') return '-';
                try {
                    const date = new Date(dateTimeString);
                    const thaiDate = formatThaiDate(dateTimeString);
                    const time = date.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
                    return `${thaiDate}, ${time} น.`;
                } catch (e) { return '-'; }
            };

            const populateDateSelects = (daySelect, monthSelect, yearSelect, selectedDate) => {
                daySelect.innerHTML = ''; monthSelect.innerHTML = ''; yearSelect.innerHTML = '';
                for (let i = 1; i <= 31; i++) daySelect.add(new Option(i, i));
                const months = ["มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
                months.forEach((m, i) => monthSelect.add(new Option(m, i + 1)));
                const currentYearBE = new Date().getFullYear() + 543;
                for (let i = currentYearBE; i <= currentYearBE + 10; i++) yearSelect.add(new Option(i, i));
                if (selectedDate) {
                    daySelect.value = selectedDate.getDate();
                    monthSelect.value = selectedDate.getMonth() + 1;
                    yearSelect.value = selectedDate.getFullYear() + 543;
                }
            };
            
            const createInfoRow = (label, value) => {
                if (!value || value === '-') return '';
                return `<div class="flex justify-between items-start gap-4">
                            <span class="text-base-content/70 flex-shrink-0">${label}:</span>
                            <span class="font-semibold text-right break-words">${value}</span>
                        </div>`;
            };
            
            const openDetailsModal = (card) => {
                currentCardData = card.dataset;
                const data = currentCardData;
                const basePath = `/public/uploads/${data.userKey}/vehicle/${data.requestKey}/`;
                const qrPath = `/public/qr/${data.requestKey}.png`;

                detailsModalEl.querySelector('#modal-status-badge').className = `badge badge-lg ${data.statusClass}`;
                detailsModalEl.querySelector('#modal-status-badge').innerHTML = `<i class="${data.statusIcon} mr-2"></i> ${data.statusText}`;
                detailsModalEl.querySelector('#modal-license-plate').textContent = `${data.licensePlate} ${data.province}`;
                detailsModalEl.querySelector('#modal-brand-model').textContent = `${data.brand} / ${data.model}`;

                const rejectionBox = detailsModalEl.querySelector('#modal-rejection-reason-box');
                rejectionBox.classList.toggle('hidden', !(data.statusKey === 'rejected' && data.rejectionReason));
                if (data.statusKey === 'rejected' && data.rejectionReason) {
                    detailsModalEl.querySelector('#modal-rejection-reason-text').textContent = data.rejectionReason;
                }
                
                const vehicleInfoList = detailsModalEl.querySelector('#modal-vehicle-info-list');
                vehicleInfoList.innerHTML = createInfoRow('ประเภท', data.vehicleType) +
                                          createInfoRow('สี', data.color) +
                                          createInfoRow('วันสิ้นอายุภาษี', formatThaiDate(data.taxExpiry));

                const ownerInfoList = detailsModalEl.querySelector('#modal-owner-info-list');
                let ownerHtml = createInfoRow('สถานะ', data.ownerType === 'self' ? 'รถชื่อตนเอง' : 'รถคนอื่น');
                if (data.ownerType === 'other') {
                    ownerHtml += createInfoRow('ชื่อเจ้าของ', data.otherOwnerName || '-');
                    ownerHtml += createInfoRow('เกี่ยวข้องเป็น', data.otherOwnerRelation || '-');
                }
                ownerInfoList.innerHTML = ownerHtml;
                
                const cardInfoList = detailsModalEl.querySelector('#modal-card-info-list');
                const cardTypeThai = data.cardType === 'internal' ? 'ภายใน' : (data.cardType === 'external' ? 'ภายนอก' : '-');
                let cardHtml = createInfoRow('รหัสคำร้อง', data.searchId);
                if (data.cardType) cardHtml += createInfoRow('ประเภทบัตร', cardTypeThai);
                cardHtml += createInfoRow('วันยื่นคำร้อง', formatThaiDateTime(data.createdAt));
                if (data.createdAt.substring(0, 19) !== data.updatedAt.substring(0, 19)) {
                    cardHtml += createInfoRow('แก้ไขล่าสุด', formatThaiDateTime(data.updatedAt));
                }
                if (data.statusKey === 'approved' || data.statusKey === 'expired') {
                    cardHtml += createInfoRow('เลขที่บัตร', data.cardNumber);
                    cardHtml += createInfoRow('ผู้อนุมัติ', data.approvedBy);
                    cardHtml += createInfoRow('วันอนุมัติ', formatThaiDateTime(data.approvedAt));
                    cardHtml += createInfoRow('วันหมดอายุ', formatThaiDate(data.cardExpiry));
                }
                cardInfoList.innerHTML = cardHtml;
                
                const qrContainer = detailsModalEl.querySelector('#modal-qr-code-container');
                qrContainer.classList.toggle('hidden', data.statusKey !== 'approved' && data.statusKey !== 'expired');
                if (data.statusKey === 'approved' || data.statusKey === 'expired') {
                    qrContainer.querySelector('img').src = qrPath;
                }

                detailsModalEl.querySelector('#modal-photo-reg').src = basePath + data.photoReg;
                detailsModalEl.querySelector('#modal-photo-tax').src = basePath + data.photoTax;
                detailsModalEl.querySelector('#modal-photo-front').src = basePath + data.photoFront;
                detailsModalEl.querySelector('#modal-photo-rear').src = basePath + data.photoRear;

                const actionContainer = detailsModalEl.querySelector('#modal-action-buttons');
                actionContainer.innerHTML = '';
                actionContainer.innerHTML += '<div class="flex-grow"></div>';
                if (data.canRenew === 'true') {
                    actionContainer.innerHTML += `<a href="add_vehicle.php?renew_id=${data.vehicleId}" class="btn btn-sm btn-success"><i class="fa-solid fa-calendar-check"></i>ต่ออายุบัตร</a>`;
                }
                if (data.statusKey === 'pending' || data.statusKey === 'rejected') {
                    actionContainer.innerHTML += `<button id="modal-edit-btn" class="btn btn-sm btn-warning"><i class="fa-solid fa-pencil"></i>แก้ไข</button>`;
                }
                 if (data.statusKey !== 'approved') {
                    actionContainer.innerHTML += `<button id="modal-delete-btn" class="btn btn-sm btn-error"><i class="fa-solid fa-trash-can"></i>ลบ</button>`;
                }
                actionContainer.innerHTML += `<form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>`;


                detailsModalEl.querySelector('#modal-content-wrapper').classList.remove('hidden');
                detailsModalEl.querySelector('#modal-edit-form-wrapper').classList.add('hidden');
                
                detailsModalEl.showModal();
            };

            const switchToEditMode = () => {
                const data = currentCardData;
                const editForm = detailsModalEl.querySelector('#editVehicleForm');
                
                editForm.querySelector('#edit-request-id').value = data.requestId;
                editForm.querySelector('#edit-vehicle-brand').value = data.brand;
                editForm.querySelector('#edit-vehicle-model').value = data.model;
                editForm.querySelector('#edit-vehicle-color').value = data.color;
                
                const taxDate = data.taxExpiry ? new Date(data.taxExpiry) : null;
                populateDateSelects(
                    editForm.querySelector('#edit-tax-day'),
                    editForm.querySelector('#edit-tax-month'),
                    editForm.querySelector('#edit-tax-year'),
                    taxDate
                );
                
                const ownerSelect = editForm.querySelector('#edit-owner-type');
                ownerSelect.value = data.ownerType;
                if (data.ownerType === 'other') {
                    editForm.querySelector('#edit-other-owner-name').value = data.otherOwnerName;
                    editForm.querySelector('#edit-other-owner-relation').value = data.otherOwnerRelation;
                }
                ownerSelect.dispatchEvent(new Event('change'));

                detailsModalEl.querySelector('#modal-content-wrapper').classList.add('hidden');
                detailsModalEl.querySelector('#modal-edit-form-wrapper').classList.remove('hidden');
            };

            const filterAndSearchCards = () => {
                const filterKey = document.querySelector('.stat-filter.active')?.dataset.filter || 'all';
                const searchTerm = searchInput.value.toLowerCase().trim();
                let visibleCount = 0;

                vehicleCards.forEach(card => {
                    const cardStatus = card.dataset.statusKey;
                    const cardText = card.textContent.toLowerCase();
                    const isStatusVisible = (filterKey === 'all' || cardStatus === filterKey);
                    const isSearchVisible = (searchTerm === '' || cardText.includes(searchTerm));
                    const isVisible = isStatusVisible && isSearchVisible;
                    
                    card.style.display = isVisible ? 'flex' : 'none';
                    if (isVisible) visibleCount++;
                });

                if (noResultsMessage) {
                    noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
                }
            };

            // --- Event Listeners ---
            vehicleCards.forEach(card => card.addEventListener('click', () => openDetailsModal(card)));
            
            statFilters.forEach(filter => {
                filter.addEventListener('click', () => {
                    statFilters.forEach(f => f.classList.remove('active', 'ring-2', 'ring-primary', 'ring-offset-base-100', 'ring-offset-2'));
                    filter.classList.add('active', 'ring-2', 'ring-primary', 'ring-offset-base-100', 'ring-offset-2');
                    filterAndSearchCards();
                });
            });
            
            if (searchInput) {
                searchInput.addEventListener('input', filterAndSearchCards);
            }

            const defaultFilter = document.querySelector('.stat-filter[data-filter="all"]');
            if (defaultFilter) defaultFilter.click();

            detailsModalEl.addEventListener('click', (e) => {
                if (e.target.id === 'modal-edit-btn') {
                    switchToEditMode();
                }
                if (e.target.id === 'modal-delete-btn') {
                    deleteModalEl.querySelector('#delete-request-id').value = currentCardData.requestId;
                    deleteModalEl.showModal();
                }
                if (e.target.id === 'cancel-edit-btn') {
                    detailsModalEl.querySelector('#modal-edit-form-wrapper').classList.add('hidden');
                    detailsModalEl.querySelector('#modal-content-wrapper').classList.remove('hidden');
                }
            });

            const editForm = detailsModalEl.querySelector('#editVehicleForm');
            if(editForm) {
                editForm.addEventListener('submit', () => loadingModalEl.showModal());
                
                const editOwnerSelect = editForm.querySelector('#edit-owner-type');
                if (editOwnerSelect) {
                    editOwnerSelect.addEventListener('change', e => {
                        const otherDetails = editForm.querySelector('#edit-other-owner-details');
                        otherDetails.classList.toggle('hidden', e.target.value !== 'other');
                        otherDetails.querySelectorAll('input').forEach(input => {
                             e.target.value === 'other' ? input.setAttribute('required', '') : input.removeAttribute('required');
                        });
                    });
                }
            }
            
            const deleteForm = deleteModalEl.querySelector('#deleteRequestForm');
            if(deleteForm) {
                deleteForm.addEventListener('submit', () => {
                    deleteModalEl.close();
                    loadingModalEl.showModal();
                });
            }
        },

        initAddVehiclePage: function() {
            const form = document.getElementById('addVehicleForm');
            if (!form) return;

            // --- Elements ---
            const checkSection = document.getElementById('vehicle-check-section');
            const detailsSection = document.getElementById('vehicle-details-section');
            const checkBtn = document.getElementById('check-vehicle-btn');
            const submitBtn = document.getElementById('submit-request-btn');
            const resetBtn = document.getElementById('reset-form-btn');
            const resetConfirmModal = document.getElementById('resetConfirmModal');
            const confirmResetBtn = document.getElementById('confirm-reset-btn');
            const loadingModal = document.getElementById('loading_modal');

            // --- Functions ---
            const showError = (element, message) => {
                const parent = element.closest('.form-control');
                const errorEl = parent?.querySelector('.error-message');
                if (errorEl) {
                    errorEl.textContent = message;
                    errorEl.classList.remove('hidden');
                }
                element.classList.add('input-error', 'select-error');
            };

            const clearError = (element) => {
                const parent = element.closest('.form-control');
                const errorEl = parent?.querySelector('.error-message');
                if (errorEl) {
                    errorEl.textContent = '';
                    errorEl.classList.add('hidden');
                }
                element.classList.remove('input-error', 'select-error');
            };
            
            const validateField = (field) => {
                let isValid = true;
                clearError(field);

                if (field.hasAttribute('required') && (field.type !== 'file' && field.type !== 'checkbox') && !field.value.trim()) {
                    showError(field, 'กรุณากรอกข้อมูล');
                    isValid = false;
                }
                
                if (field.type === 'file' && field.hasAttribute('required')) {
                    if (field.files.length === 0) {
                        showError(field, 'กรุณาแนบไฟล์');
                        isValid = false;
                    } else {
                        const file = field.files[0];
                        const maxSize = 5 * 1024 * 1024; // 5 MB
                        if (file.size > maxSize) {
                            showError(field, 'ขนาดไฟล์ต้องไม่เกิน 5 MB');
                            isValid = false;
                        }
                    }
                }

                if (field.type === 'checkbox' && field.hasAttribute('required') && !field.checked) {
                    const errorEl = field.closest('.form-control').querySelector('.error-message');
                    if (errorEl) {
                        errorEl.textContent = 'กรุณายอมรับเงื่อนไข';
                        errorEl.classList.remove('hidden');
                    }
                    isValid = false;
                }

                return isValid;
            };

            const populateDateSelects = (daySelect, monthSelect, yearSelect, selectedDate) => {
                daySelect.innerHTML = '<option value="">วัน</option>';
                monthSelect.innerHTML = '<option value="">เดือน</option>';
                yearSelect.innerHTML = '<option value="">ปี (พ.ศ.)</option>';
                for (let i = 1; i <= 31; i++) daySelect.add(new Option(i, i));
                const months = ["มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
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
            
            const setupImagePreview = (inputId, previewId) => {
                const input = document.getElementById(inputId);
                const preview = document.getElementById(previewId);
                if(input && preview){
                    input.addEventListener('change', function(event) {
                        const file = event.target.files[0];
                        if (file) {
                            preview.src = URL.createObjectURL(file);
                        }
                    });
                }
            };

            // --- Event Listeners ---
            checkBtn.addEventListener('click', async () => {
                const type = document.getElementById('check-vehicle-type');
                const plate = document.getElementById('check-license-plate');
                const province = document.getElementById('check-license-province');
                
                let isFormValid = true;
                [type, plate, province].forEach(field => {
                    if (!validateField(field)) {
                        isFormValid = false;
                    }
                });
                
                if (!isFormValid) return;

                loadingModal.showModal();
                try {
                    const response = await fetch('../../../controllers/user/vehicle/check_vehicle.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            license_plate: plate.value,
                            province: province.value,
                        }),
                    });
                    const result = await response.json();
                    loadingModal.close();

                    if (result.exists) {
                        showAlert('ยานพาหนะนี้มีคำร้องอยู่ในระบบแล้ว', 'error');
                    } else {
                        checkSection.classList.add('hidden');
                        detailsSection.classList.remove('hidden');
                        document.getElementById('display-vehicle-type').textContent = type.value;
                        document.getElementById('display-license-plate').textContent = plate.value;
                        document.getElementById('display-license-province').textContent = province.value;
                        form.querySelector('input[name="vehicle_type"]').value = type.value;
                        form.querySelector('input[name="license_plate"]').value = plate.value;
                        form.querySelector('input[name="license_province"]').value = province.value;
                    }
                } catch (error) {
                    loadingModal.close();
                    showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                }
            });

            submitBtn.addEventListener('click', () => {
                let isAllValid = true;
                form.querySelectorAll('[required]').forEach(field => {
                     if (field.offsetParent !== null && !validateField(field)) { 
                        isAllValid = false;
                    }
                });

                if (isAllValid) {
                    loadingModal.showModal();
                    form.submit();
                } else {
                    showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error');
                }
            });

            resetBtn.addEventListener('click', () => resetConfirmModal.showModal());
            
            confirmResetBtn.addEventListener('click', () => {
                 checkSection.classList.remove('hidden');
                 detailsSection.classList.add('hidden');
                 form.reset();
                 form.querySelectorAll('.error-message').forEach(el => el.classList.add('hidden'));
                 form.querySelectorAll('.input-error, .select-error').forEach(el => el.classList.remove('input-error', 'select-error'));
                 document.getElementById('reg-copy-preview').src = "/public/assets/images/registration.jpg";
                 document.getElementById('tax-sticker-preview').src = "/public/assets/images/tax_sticker.jpg";
                 document.getElementById('front-view-preview').src = "/public/assets/images/front_view.png";
                 document.getElementById('rear-view-preview').src = "/public/assets/images/rear_view.png";
                 resetConfirmModal.close();
            });

            // --- Initial Setup ---
            const taxDay = form.querySelector('select[name="tax_day"]');
            const taxMonth = form.querySelector('select[name="tax_month"]');
            const taxYear = form.querySelector('select[name="tax_year"]');
            const taxExpiryDate = form.dataset.renewalTaxDate || null;
            populateDateSelects(taxDay, taxMonth, taxYear, taxExpiryDate);
            
            setupImagePreview('reg_copy_upload', 'reg-copy-preview');
            setupImagePreview('tax_sticker_upload', 'tax-sticker-preview');
            setupImagePreview('front_view_upload', 'front-view-preview');
            setupImagePreview('rear_view_upload', 'rear-view-preview');
            
            const ownerSelect = form.querySelector('select[name="owner_type"]');
            ownerSelect.addEventListener('change', function() {
                const otherDetails = document.getElementById('other-owner-details');
                const isOther = this.value === 'other';
                otherDetails.classList.toggle('hidden', !isOther);
                otherDetails.querySelectorAll('input').forEach(input => {
                    isOther ? input.setAttribute('required', '') : input.removeAttribute('required');
                });
            });
            
             detailsSection.querySelectorAll('[required]').forEach(field => {
                const eventType = (field.tagName === 'SELECT' || field.type === 'file' || field.type === 'checkbox') ? 'change' : 'input';
                field.addEventListener(eventType, () => validateField(field));
            });
             checkSection.querySelectorAll('[required]').forEach(field => {
                const eventType = (field.tagName === 'SELECT') ? 'change' : 'input';
                field.addEventListener(eventType, () => validateField(field));
            });
        },
        
        initProfilePage: function() {
            const form = document.getElementById('profileForm');
            if(!form) return;

            const editBtn = document.getElementById('edit-profile-btn');
            const saveBtn = document.getElementById('save-profile-btn');
            const cancelBtn = document.getElementById('cancel-edit-btn');
            
            const photoUpload = document.getElementById('profile-photo-upload');
            const photoGuidance = document.getElementById('photo-guidance');
            const photoContainer = document.getElementById('profile-photo-container');

            const setEditMode = (isEditing) => {
                editBtn.classList.toggle('hidden', isEditing);
                saveBtn.classList.toggle('hidden', !isEditing);
                cancelBtn.classList.toggle('hidden', !isEditing);

                photoUpload.classList.toggle('hidden', !isEditing);
                photoGuidance.classList.toggle('hidden', !isEditing);
                photoContainer.classList.toggle('cursor-pointer', isEditing);

                const allFields = form.querySelectorAll('input:not([type=hidden]), select');
                const nonEditable = ['national_id_display', 'work_department_display'];

                allFields.forEach(field => {
                    const fieldName = field.getAttribute('name');
                    
                    if (nonEditable.includes(fieldName)) {
                        field.disabled = true;
                    } else {
                        field.disabled = !isEditing;
                    }
                });
                
                document.getElementById('profile-title').dispatchEvent(new Event('change'));

                $.Thailand.each($('#profile-zipcode, #profile-subdistrict, #profile-district, #profile-province'), function (i, e) {
                    $(e).prop('disabled', !isEditing);
                });
            };
            
            editBtn.addEventListener('click', () => setEditMode(true));
            
            cancelBtn.addEventListener('click', () => {
                location.reload(); 
            });
            
            saveBtn.addEventListener('click', () => {
                form.submit();
            });

            photoContainer.addEventListener('click', () => {
                if (!saveBtn.classList.contains('hidden')) {
                     photoUpload.click();
                }
            });
            
            photoUpload.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        document.getElementById('profile-photo-preview').src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            const setupOtherOption = (selectId, otherInputId) => {
                const select = document.getElementById(selectId);
                const otherInput = document.getElementById(otherInputId);
                if (select && otherInput) {
                    select.addEventListener('change', function() {
                        const isOther = this.value === 'other';
                        const isEditing = !this.disabled;
                        otherInput.classList.toggle('hidden', !isOther);
                        otherInput.disabled = !isEditing || !isOther;
                        
                        if (isOther && isEditing) {
                            otherInput.setAttribute('required', '');
                        } else {
                            otherInput.removeAttribute('required');
                        }
                    });
                }
            };
            setupOtherOption('profile-title', 'profile-title-other');

            $.Thailand({
                $zipcode: $('#profile-zipcode'),
                $district: $('#profile-subdistrict'), 
                $amphoe: $('#profile-district'),
                $province: $('#profile-province'),
            });
            
            const phoneInput = form.querySelector('input[name="phone"]');
            const formatPhone = () => {
                 const numbers = phoneInput.value.replace(/\D/g, '');
                 let result = '';
                 if (numbers.length > 3) result += numbers.substring(0,3) + '-';
                 if (numbers.length > 6) result += numbers.substring(3,6) + '-';
                 result += numbers.substring(6,10);
                 phoneInput.value = result;
            };
            phoneInput.addEventListener('input', formatPhone);
            formatPhone();

            const nidInput = document.getElementById('profile-national-id');
            const formatNid = () => {
                const numbers = nidInput.value.replace(/\D/g, '');
                let result = '';
                if(numbers.length > 1) result += numbers.substring(0,1) + '-';
                if(numbers.length > 5) result += numbers.substring(1,5) + '-';
                if(numbers.length > 10) result += numbers.substring(5,10) + '-';
                if(numbers.length > 12) result += numbers.substring(10,12) + '-';
                result += numbers.substring(12,13);
                nidInput.value = result;
            };
            nidInput.addEventListener('input', formatNid);
            formatNid();
        }
    };

    // Run the app
    App.init();
});

