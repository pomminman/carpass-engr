document.addEventListener('DOMContentLoaded', function () {
    
    /**
     * Main application object for all user-facing functionalities.
     */
    const App = {
        
        //======================================================================
        // INITIALIZATION
        //======================================================================

        /**
         * Initializes all necessary components based on the current page.
         */
        init: function() {
            this.initGlobalHelpers();
            this.initW3ModalFunctionality(); // Initialize modal functionality globally
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
         * Initializes global functionalities like flash messages.
         */
        initGlobalHelpers: function() {
            const flashMessage = document.body.dataset.flashMessage;
            const flashStatus = document.body.dataset.flashStatus;
            if (flashMessage && flashStatus) {
                this.showAlert(flashMessage, flashStatus);
            }
        },
        
        //======================================================================
        // SHARED HELPER & VALIDATION METHODS
        //======================================================================

        showAlert: function(message, type = 'success') {
            const container = document.getElementById('alert-container');
            if (!container) return;
            const icons = { success: 'fa-solid fa-circle-check', error: 'fa-solid fa-circle-xmark', info: 'fa-solid fa-circle-info', warning: 'fa-solid fa-triangle-exclamation' };
            const alertEl = document.createElement('div');
            alertEl.className = `alert alert-${type} alert-soft shadow-lg`;
            alertEl.innerHTML = `<div class="flex items-center"><i class="${icons[type]}"></i><span class="ml-2 text-xs sm:text-sm">${message}</span></div>`;
            container.appendChild(alertEl);
            setTimeout(() => {
                alertEl.style.transition = 'opacity 0.3s ease';
                alertEl.style.opacity = '0';
                setTimeout(() => alertEl.remove(), 300);
            }, 3000);
        },

        initW3ModalFunctionality: function() {
            const w3Modal = document.getElementById('w3-image-modal');
            if (!w3Modal) return; 

            const w3ModalClose = w3Modal.querySelector('.w3-modal-close');
            
            if (w3ModalClose) {
                w3ModalClose.onclick = () => w3Modal.style.display = "none";
            }
            w3Modal.onclick = (event) => {
                if (event.target === w3Modal) {
                    w3Modal.style.display = "none";
                }
            }
        },

        formatThaiDate: function(dateString) {
            if (!dateString || dateString.startsWith('0000-00-00')) return '-';
            try {
                const date = new Date(dateString);
                const thaiMonths = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
                return `${date.getDate()} ${thaiMonths[date.getMonth()]} ${date.getFullYear() + 543}`;
            } catch (e) { return '-'; }
        },

        formatThaiDateTime: function(dateTimeString) {
            if (!dateTimeString || dateTimeString.startsWith('0000-00-00')) return '-';
            try {
                const date = new Date(dateTimeString);
                const thaiDate = this.formatThaiDate(dateTimeString);
                const time = date.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
                return `${thaiDate}, ${time} น.`;
            } catch (e) { return '-'; }
        },
        
        setupImagePreview: function(inputId, previewId) {
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
        },

        showError: function(element, message) {
            const parent = element.closest('.form-control');
            const errorEl = parent?.querySelector('.error-message');
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
            }
            if (element.type === 'file') element.classList.add('file-input-error');
            else if (element.tagName === 'SELECT') element.classList.add('select-error');
            else element.classList.add('input-error');
        },

        clearError: function(element) {
            const parent = element.closest('.form-control');
            const errorEl = parent?.querySelector('.error-message');
            if (errorEl) {
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
            }
            element.classList.remove('input-error', 'select-error', 'file-input-error');
        },
        
        validateField: function(field) {
            let isValid = true;
            const value = field.value.trim();
            this.clearError(field);
            
            if (field.hasAttribute('required')) {
                if (field.type === 'checkbox') {
                    if (!field.checked) {
                        this.showError(field, 'กรุณายอมรับเงื่อนไข');
                        isValid = false;
                    }
                } else if (field.type === 'file') {
                    if (field.files.length === 0) {
                        // This check is specifically for add_vehicle page's required file inputs
                        if (field.id !== 'profile-photo-upload') {
                            this.showError(field, 'กรุณาแนบไฟล์');
                            isValid = false;
                        }
                    } else if (field.files[0].size > 5 * 1024 * 1024) {
                        this.showError(field, 'ขนาดไฟล์ต้องไม่เกิน 5 MB');
                        isValid = false;
                    }
                } else if (value === '') {
                    this.showError(field, 'กรุณากรอกข้อมูล');
                    isValid = false;
                }
            } else if (field.type === 'file' && field.files.length > 0) {
                 if (field.files[0].size > 5 * 1024 * 1024) {
                    this.showError(field, 'ขนาดไฟล์ต้องไม่เกิน 5 MB');
                    isValid = false;
                }
            }


            if (isValid && field.id === 'check-license-plate' && value !== '') {
                if (!(/[ก-๙]/.test(value) && /[0-9]/.test(value))) {
                    this.showError(field, 'ต้องมีทั้งตัวอักษรไทยและตัวเลข');
                    isValid = false;
                }
            }
            
            if (isValid && field.name === 'official_id' && value !== '' && value.length !== 10) {
                 this.showError(field, 'กรุณากรอกเลขบัตรให้ครบ 10 หลัก');
                 isValid = false;
            }

            return isValid;
        },

        //======================================================================
        // PAGE-SPECIFIC INITIALIZERS
        //======================================================================

        initDashboard: function() {
            const detailsModalEl = document.getElementById('request_details_modal');
            if (!detailsModalEl) return;

            const elements = {
                deleteModal: document.getElementById('delete_confirm_modal'),
                loadingModal: document.getElementById('loading_modal'),
                vehicleCards: document.querySelectorAll('.vehicle-card'),
                statFilters: document.querySelectorAll('.stat-filter'),
                searchInput: document.getElementById('search-input'),
                vehicleGrid: document.getElementById('vehicle-grid'),
                noResultsMessage: document.getElementById('no-results-message'),
                editForm: detailsModalEl.querySelector('#editVehicleForm'),
            };

            let currentCardData = null;

            const createInfoRow = (label, value) => {
                if (value === null || value === undefined || value === '-') return '';
                return `<div class="flex justify-between items-start gap-2"><span class="text-base-content/70 flex-shrink-0">${label}:</span><span class="font-semibold text-right break-words">${value}</span></div>`;
            };

            const populateDateSelects = (daySelect, monthSelect, yearSelect, selectedDate) => {
                daySelect.innerHTML = '<option value="">วัน</option>';
                monthSelect.innerHTML = '<option value="">เดือน</option>';
                yearSelect.innerHTML = '<option value="">ปี</option>';
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
            
            const openDetailsModal = (card) => {
                try {
                    currentCardData = card.dataset;
                    const data = currentCardData;
                    const basePath = `/public/uploads/${data.userKey}/vehicle/${data.requestKey}/`;

                    const queryAndSet = (selector, content, isHtml = false) => {
                        const el = detailsModalEl.querySelector(selector);
                        if (el) isHtml ? el.innerHTML = content : el.textContent = content;
                    };
                    
                    const cardStatusContainer = detailsModalEl.querySelector('#modal-card-status');
                    if (cardStatusContainer) {
                        cardStatusContainer.innerHTML = `
                            <div class="p-2 rounded-lg inline-flex items-center justify-center gap-2 text-sm font-semibold ${data.statusBadgeBg}">
                                <i class="${data.statusIcon}"></i>
                                <span>${data.statusText}</span>
                            </div>
                        `;
                    }

                    queryAndSet('#modal-license-plate', `${data.licensePlate} ${data.province}`);
                    queryAndSet('#modal-brand-model', `${data.brand} / ${data.model}`);
                    
                    detailsModalEl.querySelector('#modal-rejection-reason-box').classList.toggle('hidden', !(data.statusKey === 'rejected' && data.rejectionReason));
                    queryAndSet('#modal-rejection-reason-text', data.rejectionReason || '');

                    let ownerHtml = createInfoRow('ความเป็นเจ้าของ', data.ownerType === 'self' ? 'รถชื่อตนเอง' : 'รถคนอื่น');
                    if (data.ownerType === 'other') {
                        ownerHtml += createInfoRow('ชื่อเจ้าของ', data.otherOwnerName || '-');
                        ownerHtml += createInfoRow('เกี่ยวข้องเป็น', data.otherOwnerRelation || '-');
                    }

                    queryAndSet('#modal-vehicle-info-list', createInfoRow('ประเภท', data.vehicleType) + createInfoRow('สี', data.color), true);
                    queryAndSet('#modal-owner-info-list', ownerHtml, true);
                    
                    let cardInfoHtml = createInfoRow('รหัสคำร้อง', data.searchId);
                    cardInfoHtml += createInfoRow('วันยื่นคำร้อง', this.formatThaiDateTime(data.createdAt));

                    if (data.statusKey === 'approved' || data.statusKey === 'expired') {
                        cardInfoHtml += createInfoRow('วันอนุมัติ', this.formatThaiDateTime(data.approvedAt));
                        cardInfoHtml += createInfoRow('วันหมดอายุ', this.formatThaiDate(data.cardExpiry));
                    }
                     queryAndSet('#modal-card-info-list', cardInfoHtml, true);
                    
                    const cardNumberBox = detailsModalEl.querySelector('#modal-card-number-box');
                    const isCardNumberVisible = data.statusKey === 'approved' || data.statusKey === 'expired';
                    cardNumberBox.classList.toggle('hidden', !isCardNumberVisible);
                    if(isCardNumberVisible) {
                         cardNumberBox.querySelector('span').textContent = data.cardNumber || '-';
                         cardNumberBox.className = `card text-center p-2 ${data.statusKey === 'approved' ? 'bg-success text-success-content' : 'bg-base-300'}`;
                    }

                    queryAndSet('#modal-evidence-gallery', `
                        <div class="text-center"><p class="font-semibold mb-1 text-sm">ทะเบียนรถ</p><div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24"><img src="${basePath + data.photoReg}" class="max-w-full max-h-full object-contain" alt="สำเนาทะเบียนรถ"></div></div>
                        <div class="text-center"><p class="font-semibold mb-1 text-sm">ป้ายภาษี</p><div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24"><img src="${basePath + data.photoTax}" class="max-w-full max-h-full object-contain" alt="ป้ายภาษี"></div></div>
                        <div class="text-center"><p class="font-semibold mb-1 text-sm">ด้านหน้า</p><div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24"><img src="${basePath + data.photoFront}" class="max-w-full max-h-full object-contain" alt="รูปถ่ายด้านหน้า"></div></div>
                        <div class="text-center"><p class="font-semibold mb-1 text-sm">ด้านหลัง</p><div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24"><img src="${basePath + data.photoRear}" class="max-w-full max-h-full object-contain" alt="รูปถ่ายด้านหลัง"></div></div>
                    `, true);

                    let buttonsHtml = '';
                    if (data.canRenew === 'true') buttonsHtml += `<a href="add_vehicle.php?renew_id=${data.vehicleId}" class="btn btn-sm btn-success"><i class="fa-solid fa-calendar-check"></i>ต่ออายุบัตร</a>`;
                    if (data.statusKey === 'pending' || data.statusKey === 'rejected') buttonsHtml += `<button id="modal-edit-btn" class="btn btn-sm btn-warning"><i class="fa-solid fa-pencil"></i>แก้ไข</button>`;
                    if (data.statusKey !== 'approved') buttonsHtml += `<button id="modal-delete-btn" class="btn btn-sm btn-error"><i class="fa-solid fa-trash-can"></i>ลบ</button>`;
                    queryAndSet('#modal-action-buttons', `<div class="flex-grow"></div>${buttonsHtml}`, true);

                    detailsModalEl.querySelector('#modal-content-wrapper').classList.remove('hidden');
                    detailsModalEl.querySelector('#modal-edit-form-wrapper').classList.add('hidden');
                    detailsModalEl.showModal();

                } catch (error) {
                    console.error("Error opening details modal:", error);
                    this.showAlert('เกิดข้อผิดพลาดในการแสดงข้อมูล', 'error');
                }
            };

            const switchToEditMode = () => {
                const data = currentCardData;
                const editForm = elements.editForm;
                const basePath = `/public/uploads/${data.userKey}/vehicle/${data.requestKey}/`;
                
                editForm.querySelector('#edit-request-id').value = data.requestId;
                editForm.querySelector('#edit-vehicle-brand').value = data.brand;
                editForm.querySelector('#edit-vehicle-model').value = data.model;
                editForm.querySelector('#edit-vehicle-color').value = data.color;
                
                const taxDate = data.taxExpiry ? new Date(data.taxExpiry) : null;
                populateDateSelects(editForm.querySelector('#edit-tax-day'), editForm.querySelector('#edit-tax-month'), editForm.querySelector('#edit-tax-year'), taxDate);
                
                const ownerSelect = editForm.querySelector('#edit-owner-type');
                ownerSelect.value = data.ownerType;
                if (data.ownerType === 'other') {
                    editForm.querySelector('#edit-other-owner-name').value = data.otherOwnerName;
                    editForm.querySelector('#edit-other-owner-relation').value = data.otherOwnerRelation;
                }
                ownerSelect.dispatchEvent(new Event('change'));

                editForm.querySelector('#edit-reg-copy-preview').src = basePath + data.photoReg;
                editForm.querySelector('#edit-tax-sticker-preview').src = basePath + data.photoTax;
                editForm.querySelector('#edit-front-view-preview').src = basePath + data.photoFront;
                editForm.querySelector('#edit-rear-view-preview').src = basePath + data.photoRear;

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

                if (elements.noResultsMessage) {
                    elements.noResultsMessage.style.display = (visibleCount === 0 && elements.vehicleCards.length > 0) ? 'block' : 'none';
                }
            };
            
            elements.vehicleCards.forEach(card => card.addEventListener('click', () => openDetailsModal(card)));
            elements.statFilters.forEach(filter => filter.addEventListener('click', () => {
                elements.statFilters.forEach(f => f.classList.remove('active', 'ring-2', 'ring-primary'));
                filter.classList.add('active', 'ring-2', 'ring-primary');
                filterAndSearchCards();
            }));
            
            elements.searchInput.addEventListener('input', filterAndSearchCards);
            document.querySelector('.stat-filter[data-filter="all"]')?.click();

            detailsModalEl.addEventListener('click', (e) => {
                if (e.target.id === 'modal-edit-btn') switchToEditMode();
                if (e.target.id === 'modal-delete-btn' && elements.deleteModal) {
                    elements.deleteModal.querySelector('#delete-request-id').value = currentCardData.requestId;
                    elements.deleteModal.showModal();
                }
                if (e.target.id === 'cancel-edit-btn') {
                    detailsModalEl.querySelector('#modal-edit-form-wrapper').classList.add('hidden');
                    detailsModalEl.querySelector('#modal-content-wrapper').classList.remove('hidden');
                }
            });

            elements.editForm.addEventListener('submit', (e) => {
                let isAllValid = Array.from(elements.editForm.querySelectorAll('[required]')).every(field => field.offsetParent === null || this.validateField(field, elements.editForm));
                if (!isAllValid) {
                    e.preventDefault();
                    this.showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error');
                } else {
                    if (elements.loadingModal) elements.loadingModal.showModal();
                }
            });

            elements.editForm.querySelectorAll('input, select').forEach(field => {
                const eventType = (field.tagName === 'SELECT' || field.type === 'file') ? 'change' : 'input';
                field.addEventListener(eventType, () => this.validateField(field, elements.editForm));
            });

            elements.editForm.querySelector('#edit-owner-type').addEventListener('change', e => {
                const otherDetails = elements.editForm.querySelector('#edit-other-owner-details');
                otherDetails.classList.toggle('hidden', e.target.value !== 'other');
                otherDetails.querySelectorAll('input').forEach(input => {
                     e.target.value === 'other' ? input.setAttribute('required', '') : input.removeAttribute('required');
                });
            });
            
            if (elements.deleteModal) {
                elements.deleteModal.querySelector('#deleteRequestForm').addEventListener('submit', () => {
                    elements.deleteModal.close();
                    if (elements.loadingModal) elements.loadingModal.showModal();
                });
            }

            ['edit-reg-copy-upload', 'edit-tax-sticker-upload', 'edit-front-view-upload', 'edit-rear-view-upload'].forEach((id, index) => {
                this.setupImagePreview(id, ['edit-reg-copy-preview', 'edit-tax-sticker-preview', 'edit-front-view-preview', 'edit-rear-view-preview'][index]);
            });
        },

        initAddVehiclePage: function() {
            const form = document.getElementById('addVehicleForm');
            if (!form) return;

            const elements = {
                checkSection: document.getElementById('vehicle-check-section'),
                detailsSection: document.getElementById('vehicle-details-section'),
                checkBtn: document.getElementById('check-vehicle-btn'),
                submitBtn: document.getElementById('submit-request-btn'),
                backBtn: document.getElementById('back-to-step1-btn'),
                step1Indicator: document.getElementById('step1-indicator'),
                step2Indicator: document.getElementById('step2-indicator'),
                loadingModal: document.getElementById('loading_modal'),
                w3Modal: document.getElementById('w3-image-modal'),
                w3ModalImg: document.getElementById('w3-modal-img'),
                w3ModalCaption: document.getElementById('w3-modal-caption'),
                typeIcon: document.getElementById('display-vehicle-type-icon')
            };

            const setStep = (stepNumber) => {
                const activeClasses = ['bg-primary', 'text-primary-content', 'border-primary'];
                const inactiveClasses = ['bg-base-200', 'text-base-content/60', 'border-base-300'];

                elements.step1Indicator.classList.remove(...activeClasses, ...inactiveClasses);
                elements.step2Indicator.classList.remove(...activeClasses, ...inactiveClasses);

                if (stepNumber === 1) {
                    elements.step1Indicator.classList.add(...activeClasses);
                    elements.step2Indicator.classList.add(...inactiveClasses);
                } else {
                    elements.step1Indicator.classList.add(...inactiveClasses);
                    elements.step2Indicator.classList.add(...activeClasses);
                }
                
                elements.checkSection.classList.toggle('hidden', stepNumber !== 1);
                elements.detailsSection.classList.toggle('hidden', stepNumber !== 2);
            };
            
            const updateVehicleIcon = (vehicleType) => {
                if (elements.typeIcon) {
                    if (vehicleType === 'รถยนต์') {
                        elements.typeIcon.className = 'fa-solid fa-car-side text-3xl opacity-80';
                    } else if (vehicleType === 'รถจักรยานยนต์') {
                        elements.typeIcon.className = 'fa-solid fa-motorcycle text-3xl opacity-80';
                    }
                }
            };

            elements.checkBtn.addEventListener('click', async () => {
                const fieldsToCheck = [ document.getElementById('check-vehicle-type'), document.getElementById('check-license-plate'), document.getElementById('check-license-province') ];
                if (!fieldsToCheck.map(f => this.validateField(f)).every(v => v)) return;

                elements.loadingModal.showModal();
                try {
                    const response = await fetch('../../../controllers/user/vehicle/check_vehicle.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ license_plate: fieldsToCheck[1].value, province: fieldsToCheck[2].value }),
                    });
                    const result = await response.json();
                    elements.loadingModal.close();

                    if (result.exists) {
                        this.showAlert('ยานพาหนะนี้มีคำร้องอยู่ในระบบสำหรับรอบปัจจุบันแล้ว', 'error');
                    } else {
                        const vehicleType = fieldsToCheck[0].value;
                        setStep(2);
                        updateVehicleIcon(vehicleType);
                        document.getElementById('display-vehicle-type').textContent = vehicleType;
                        document.getElementById('display-license-plate').textContent = fieldsToCheck[1].value;
                        document.getElementById('display-license-province').textContent = fieldsToCheck[2].value;
                        form.querySelector('input[name="vehicle_type"]').value = vehicleType;
                        form.querySelector('input[name="license_plate"]').value = fieldsToCheck[1].value;
                        form.querySelector('input[name="license_province"]').value = fieldsToCheck[2].value;
                    }
                } catch (error) {
                    elements.loadingModal.close();
                    this.showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                }
            });

            elements.submitBtn.addEventListener('click', () => {
                let isAllValid = true;
                form.querySelectorAll('[required]').forEach(field => {
                     if (field.offsetParent !== null && !this.validateField(field)) isAllValid = false;
                });

                const dateFields = [form.querySelector('select[name="tax_day"]'), form.querySelector('select[name="tax_month"]'), form.querySelector('select[name="tax_year"]')];
                if(dateFields[0].offsetParent !== null) {
                    dateFields.forEach(field => {
                        if (!field.value) {
                            isAllValid = false;
                            this.showError(field, 'กรุณาเลือก');
                        } else {
                            this.clearError(field);
                        }
                    });
                }

                if (isAllValid) {
                    elements.loadingModal.showModal();
                    form.submit();
                } else {
                    this.showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error');
                }
            });

            elements.backBtn.addEventListener('click', () => setStep(1));
            
            const taxDayEl = form.querySelector('select[name="tax_day"]'), taxMonthEl = form.querySelector('select[name="tax_month"]'), taxYearEl = form.querySelector('select[name="tax_year"]');
            const populateDateSelects = (dayEl, monthEl, yearEl, selectedDate) => {
                dayEl.innerHTML = '<option value="">วัน</option>'; monthEl.innerHTML = '<option value="">เดือน</option>'; yearEl.innerHTML = '<option value="">ปี (พ.ศ.)</option>';
                for (let i = 1; i <= 31; i++) dayEl.add(new Option(i, i));
                const months = ["มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
                months.forEach((m, i) => monthEl.add(new Option(m, i + 1)));
                const currentYearBE = new Date().getFullYear() + 543;
                for (let i = currentYearBE; i <= currentYearBE + 10; i++) yearEl.add(new Option(i, i));
                if (selectedDate) {
                    const d = new Date(selectedDate);
                    dayEl.value = d.getDate(); monthEl.value = d.getMonth() + 1; yearEl.value = d.getFullYear() + 543;
                }
            };
            populateDateSelects(taxDayEl, taxMonthEl, taxYearEl, form.dataset.renewalTaxDate || null);
            
            this.setupImagePreview('reg_copy_upload', 'reg-copy-preview');
            this.setupImagePreview('tax_sticker_upload', 'tax-sticker-preview');
            this.setupImagePreview('front_view_upload', 'front-view-preview');
            this.setupImagePreview('rear_view_upload', 'rear-view-preview');
            
            const ownerSelect = form.querySelector('select[name="owner_type"]');
            ownerSelect.addEventListener('change', () => {
                const otherDetails = document.getElementById('other-owner-details');
                const isOther = ownerSelect.value === 'other';
                otherDetails.classList.toggle('hidden', !isOther);
                otherDetails.querySelectorAll('input').forEach(input => {
                    isOther ? input.setAttribute('required', '') : input.removeAttribute('required');
                    if(!isOther) this.clearError(input);
                });
            });
            if(ownerSelect.value) ownerSelect.dispatchEvent(new Event('change'));

            form.querySelectorAll('[required]').forEach(field => {
                const eventType = (field.tagName === 'SELECT' || field.type === 'checkbox' || field.type === 'file') ? 'change' : 'input';
                field.addEventListener(eventType, () => this.validateField(field));
            });
            
            form.querySelectorAll('.example-image').forEach(img => {
                img.onclick = () => {
                    if(elements.w3Modal) {
                        elements.w3Modal.style.display = "flex";
                        elements.w3ModalImg.src = img.src;
                        elements.w3ModalCaption.innerHTML = img.alt;
                    }
                }
            });

            if (form.dataset.isRenewal === 'true') {
                setStep(2);
                const vehicleType = document.getElementById('display-vehicle-type').textContent;
                updateVehicleIcon(vehicleType);
            } else {
                setStep(1);
            }
        },
        
        initProfilePage: function() {
            const form = document.getElementById('profileForm');
            if (!form) return;

            const elements = {
                editBtn: document.getElementById('edit-profile-btn'),
                saveBtn: document.getElementById('save-profile-btn'),
                cancelBtn: document.getElementById('cancel-edit-btn'),
                photoUpload: document.getElementById('profile-photo-upload'),
                photoGuidance: document.getElementById('photo-guidance'),
                photoContainer: document.getElementById('profile-photo-container'),
                titleSelect: document.getElementById('profile-title'),
                titleOtherInput: document.getElementById('profile-title-other'),
                phoneInput: form.querySelector('input[name="phone"]'),
                nidInput: document.getElementById('profile-national-id'),
                w3Modal: document.getElementById('w3-image-modal'),
                w3ModalImg: document.getElementById('w3-modal-img'),
                w3ModalCaption: document.getElementById('w3-modal-caption'),
            };
            
            const formatInput = (input, patterns) => {
                const numbers = input.value.replace(/\D/g, '');
                let result = '';
                let patternIndex = 0;
                let numbersIndex = 0;
                while (patternIndex < patterns.length && numbersIndex < numbers.length) {
                    if (patterns[patternIndex] === '-') {
                        result += '-';
                        patternIndex++;
                    } else {
                        result += numbers[numbersIndex];
                        patternIndex++;
                        numbersIndex++;
                    }
                }
                input.value = result;
            };

            const setEditMode = (isEditing) => {
                elements.editBtn.classList.toggle('hidden', isEditing);
                elements.saveBtn.classList.toggle('hidden', !isEditing);
                elements.cancelBtn.classList.toggle('hidden', !isEditing);
                
                form.classList.toggle('form-view-mode', !isEditing);
                form.classList.toggle('form-edit-mode', isEditing);
                
                form.querySelectorAll('.view-mode-element').forEach(el => el.classList.toggle('hidden', isEditing));
                form.querySelectorAll('.edit-mode-element').forEach(el => el.classList.toggle('hidden', !isEditing));

                elements.photoUpload.classList.toggle('hidden', !isEditing);
                elements.photoGuidance.classList.toggle('hidden', !isEditing);
                elements.photoContainer.classList.toggle('cursor-pointer', true);

                const nonEditable = ['national_id_display', 'work_department_display'];
                form.querySelectorAll('input:not([type=hidden]), select').forEach(field => {
                    if (!nonEditable.includes(field.name)) {
                        field.disabled = !isEditing;
                    }
                });
                
                $('#profile-zipcode, #profile-subdistrict, #profile-district, #profile-province').each(function() {
                     $(this).prop('disabled', !isEditing).parent().find('.tt-input').prop('disabled', !isEditing);
                });

                elements.titleSelect.dispatchEvent(new Event('change'));
                
                if (isEditing) {
                    formatInput(elements.phoneInput, 'xxx-xxx-xxxx');
                    formatInput(elements.nidInput, 'x-xxxx-xxxxx-xx-x');
                }
            };

            elements.editBtn.addEventListener('click', () => setEditMode(true));
            elements.cancelBtn.addEventListener('click', () => location.reload());
            
            elements.saveBtn.addEventListener('click', (e) => {
                e.preventDefault();
                let isAllValid = true;
                
                form.querySelectorAll('[required]').forEach(field => {
                    if (field.offsetParent !== null && !this.validateField(field)) {
                        isAllValid = false;
                    }
                });
                
                if (elements.photoUpload.files.length > 0 && !this.validateField(elements.photoUpload)) {
                    isAllValid = false;
                }

                const dobDay = form.querySelector('#profile-dob-day');
                const dobMonth = form.querySelector('#profile-dob-month');
                const dobYear = form.querySelector('#profile-dob-year');
                if (dobDay.offsetParent !== null) { 
                    if (!dobDay.value || !dobMonth.value || !dobYear.value) {
                        this.showError(dobDay.closest('.grid').parentElement, 'กรุณาเลือกวันเดือนปีเกิดให้ครบถ้วน');
                        isAllValid = false;
                    } else {
                        this.clearError(dobDay.closest('.grid').parentElement);
                    }
                }

                if (isAllValid) {
                    form.submit();
                } else {
                    this.showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error');
                }
            });
            
            elements.photoContainer.addEventListener('click', () => {
                if (form.classList.contains('form-edit-mode')) {
                    elements.photoUpload.click();
                } else {
                     if(elements.w3Modal) {
                        elements.w3Modal.style.display = "flex";
                        elements.w3ModalImg.src = elements.photoContainer.querySelector('img').src;
                        elements.w3ModalCaption.innerHTML = elements.photoContainer.querySelector('img').alt;
                    }
                }
            });

            this.setupImagePreview('profile-photo-upload', 'profile-photo-preview');

            elements.titleSelect.addEventListener('change', function() {
                const isOther = this.value === 'other';
                elements.titleOtherInput.classList.toggle('hidden', !isOther);
                elements.titleOtherInput.disabled = this.disabled || !isOther;
                if (isOther && !this.disabled) {
                    elements.titleOtherInput.setAttribute('required', '');
                } else {
                    elements.titleOtherInput.removeAttribute('required');
                }
            });
            
            $.Thailand({
                $zipcode: $('#profile-zipcode'),
                $district: $('#profile-subdistrict'), 
                $amphoe: $('#profile-district'),
                $province: $('#profile-province'),
            });


            elements.phoneInput.addEventListener('input', () => formatInput(elements.phoneInput, 'xxx-xxx-xxxx'));
            elements.nidInput.addEventListener('input', () => formatInput(elements.nidInput, 'x-xxxx-xxxxx-xx-x'));

            form.querySelectorAll('input, select').forEach(field => {
                const eventType = (field.tagName === 'SELECT' || field.type === 'file' || field.type === 'checkbox') ? 'change' : 'input';
                field.addEventListener(eventType, () => {
                    if (form.classList.contains('form-edit-mode')) {
                        this.validateField(field);
                    }
                });
            });

            setEditMode(false);
        }
    };

    App.init();
});

