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
         * Initializes global functionalities like flash messages and Fancybox.
         */
        initGlobalHelpers: function() {
            const flashMessage = document.body.dataset.flashMessage;
            const flashStatus = document.body.dataset.flashStatus;
            if (flashMessage && flashStatus) {
                this.showAlert(flashMessage, flashStatus);
            }
            // Initialize Fancybox for non-modal images
            Fancybox.bind("[data-fancybox]", {
                // Your custom options
            });
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
                const fancyboxLink = preview.closest('a'); // Get parent <a> tag for fancybox
                input.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const newSrc = URL.createObjectURL(file);
                        preview.src = newSrc;
                        if (fancyboxLink) {
                            fancyboxLink.href = newSrc; // Update href for fancybox
                        }
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
            
            if (element.name === 'dob_day' || element.name === 'dob_month' || element.name === 'dob_year') {
                const dobContainer = element.closest('.grid');
                if (dobContainer) {
                    dobContainer.querySelectorAll('select').forEach(sel => sel.classList.add('select-error'));
                }
                return;
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

             if (element.name === 'dob_day' || element.name === 'dob_month' || element.name === 'dob_year') {
                const dobContainer = element.closest('.grid');
                if (dobContainer) {
                    dobContainer.querySelectorAll('select').forEach(sel => sel.classList.remove('select-error'));
                }
                return;
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

                    // [START] ***** EDITED CODE *****
                    // Log the view action with proper response handling
                    fetch('../../../controllers/user/activity/log_user_process.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ request_id: data.requestId })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (!result.success) {
                            console.error('Failed to log view:', result.message);
                        }
                    })
                    .catch(error => console.error('Error logging view:', error));
                    // [END] ***** EDITED CODE *****

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
                    
                    const galleryHTML = `
                        <div class="text-center">
                            <p class="font-semibold mb-1 text-sm">ทะเบียนรถ</p>
                            <a href="${basePath + data.photoReg}" class="modal-gallery-item" data-caption="สำเนาทะเบียนรถ: ${data.licensePlate}">
                                <div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24">
                                    <img src="${basePath + data.photoReg}" class="max-w-full max-h-full object-contain cursor-pointer" alt="สำเนาทะเบียนรถ">
                                </div>
                            </a>
                        </div>
                        <div class="text-center">
                            <p class="font-semibold mb-1 text-sm">ป้ายภาษี</p>
                            <a href="${basePath + data.photoTax}" class="modal-gallery-item" data-caption="ป้ายภาษี: ${data.licensePlate}">
                                <div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24">
                                    <img src="${basePath + data.photoTax}" class="max-w-full max-h-full object-contain cursor-pointer" alt="ป้ายภาษี">
                                </div>
                            </a>
                        </div>
                        <div class="text-center">
                            <p class="font-semibold mb-1 text-sm">ด้านหน้า</p>
                            <a href="${basePath + data.photoFront}" class="modal-gallery-item" data-caption="รูปถ่ายด้านหน้า: ${data.licensePlate}">
                                <div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24">
                                    <img src="${basePath + data.photoFront}" class="max-w-full max-h-full object-contain cursor-pointer" alt="รูปถ่ายด้านหน้า">
                                </div>
                            </a>
                        </div>
                        <div class="text-center">
                            <p class="font-semibold mb-1 text-sm">ด้านหลัง</p>
                            <a href="${basePath + data.photoRear}" class="modal-gallery-item" data-caption="รูปถ่ายด้านหลัง: ${data.licensePlate}">
                                <div class="flex justify-center bg-base-100 p-2 rounded-lg border h-24">
                                    <img src="${basePath + data.photoRear}" class="max-w-full max-h-full object-contain cursor-pointer" alt="รูปถ่ายด้านหลัง">
                                </div>
                            </a>
                        </div>`;
                    queryAndSet('#modal-evidence-gallery', galleryHTML, true);
                    
                    const galleryItems = detailsModalEl.querySelectorAll('.modal-gallery-item');
                    
                    galleryItems.forEach(item => {
                        item.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();

                            const currentSlides = Array.from(detailsModalEl.querySelectorAll('.modal-gallery-item')).map(el => ({
                                src: el.href,
                                caption: el.dataset.caption
                            }));
                            
                            const startIndex = Array.from(detailsModalEl.querySelectorAll('.modal-gallery-item')).indexOf(item);
                            
                            detailsModalEl.close();

                            Fancybox.show(currentSlides, {
                                startIndex: startIndex,
                                on: {
                                    close: () => {
                                        setTimeout(() => {
                                            detailsModalEl.showModal();
                                        }, 150);
                                    },
                                }
                            });
                        });
                    });

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

                const updateLink = (id, newSrc) => {
                    const img = editForm.querySelector(id);
                    img.src = newSrc;
                    if (img.parentElement.tagName === 'A') {
                        img.parentElement.href = newSrc;
                    }
                };
                
                updateLink('#edit-reg-copy-preview', basePath + data.photoReg);
                updateLink('#edit-tax-sticker-preview', basePath + data.photoTax);
                updateLink('#edit-front-view-preview', basePath + data.photoFront);
                updateLink('#edit-rear-view-preview', basePath + data.photoRear);

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
            
            elements.vehicleCards.forEach(card => card.addEventListener('click', (e) => {
                if(e.target.closest('a[data-fancybox]') || e.target.closest('.modal-gallery-item')) return;
                openDetailsModal(card);
            }));
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
                    resetEditFormValidation();
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
                const inputs = otherDetails.querySelectorAll('input');
                const isOther = e.target.value === 'other';

                otherDetails.classList.toggle('hidden', !isOther);
                inputs.forEach(input => {
                    if (isOther) {
                        input.setAttribute('required', '');
                        this.validateField(input); 
                    } else {
                        input.removeAttribute('required');
                        this.clearError(input);
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
                reviewModal: document.getElementById('review_request_modal'),
                finalSubmitBtn: document.getElementById('final-submit-btn'),
                typeIcon: document.getElementById('display-vehicle-type-icon')
            };

            const populateRequestReviewModal = () => {
                const summaryContent = elements.reviewModal.querySelector('#summary-content');
                const formData = new FormData(form);
            
                const getSelectText = (name) => {
                    const select = form.querySelector(`select[name="${name}"]`);
                    return select ? select.options[select.selectedIndex].text : '-';
                };
            
                const vehicleType = document.getElementById('display-vehicle-type').textContent;
                const licensePlate = document.getElementById('display-license-plate').textContent;
                const licenseProvince = document.getElementById('display-license-province').textContent;
                const brand = getSelectText('vehicle_brand');
                const model = formData.get('vehicle_model');
                const color = formData.get('vehicle_color');
                const taxDate = `${formData.get('tax_day')} ${getSelectText('tax_month')} ${formData.get('tax_year')}`;
                const ownerType = getSelectText('owner_type');
                const otherOwnerName = formData.get('other_owner_name');
                const otherOwnerRelation = formData.get('other_owner_relation');
            
                const regCopySrc = document.getElementById('reg-copy-preview').src;
                const taxStickerSrc = document.getElementById('tax-sticker-preview').src;
                const frontViewSrc = document.getElementById('front-view-preview').src;
                const rearViewSrc = document.getElementById('rear-view-preview').src;
            
                let ownerHtml = `<div><strong>ความเป็นเจ้าของ:</strong> ${ownerType}</div>`;
                if (ownerType === 'รถคนอื่น') {
                    ownerHtml += `<div><strong>ชื่อเจ้าของ:</strong> ${otherOwnerName || '-'}</div>`;
                    ownerHtml += `<div><strong>เกี่ยวข้องเป็น:</strong> ${otherOwnerRelation || '-'}</div>`;
                }
            
                const html = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">ข้อมูลยานพาหนะ</div>
                                <div class="p-3 bg-base-200 rounded-box text-sm space-y-1">
                                    <div><strong>ประเภท:</strong> ${vehicleType}</div>
                                    <div><strong>ทะเบียน:</strong> ${licensePlate} ${licenseProvince}</div>
                                    <div><strong>ยี่ห้อ/รุ่น:</strong> ${brand} / ${model}</div>
                                    <div><strong>สี:</strong> ${color}</div>
                                    <div><strong>วันสิ้นอายุภาษี:</strong> ${taxDate}</div>
                                </div>
                            </div>
                            <div>
                                <div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">ข้อมูลเจ้าของ</div>
                                <div class="p-3 bg-base-200 rounded-box text-sm space-y-1">
                                   ${ownerHtml}
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">หลักฐานประกอบ</div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="card bg-base-200/50 p-2 border">
                                    <div class="flex items-center justify-center h-28 overflow-hidden rounded-md bg-white">
                                         <img src="${regCopySrc}" class="max-w-full max-h-full object-contain">
                                    </div>
                                    <p class="text-xs text-center font-semibold mt-1">สำเนาทะเบียนรถ</p>
                                </div>
                                <div class="card bg-base-200/50 p-2 border">
                                    <div class="flex items-center justify-center h-28 overflow-hidden rounded-md bg-white">
                                         <img src="${taxStickerSrc}" class="max-w-full max-h-full object-contain">
                                    </div>
                                    <p class="text-xs text-center font-semibold mt-1">ป้ายภาษี</p>
                                </div>
                                 <div class="card bg-base-200/50 p-2 border">
                                    <div class="flex items-center justify-center h-28 overflow-hidden rounded-md bg-white">
                                         <img src="${frontViewSrc}" class="max-w-full max-h-full object-contain">
                                    </div>
                                    <p class="text-xs text-center font-semibold mt-1">รูปถ่ายด้านหน้า</p>
                                </div>
                                 <div class="card bg-base-200/50 p-2 border">
                                    <div class="flex items-center justify-center h-28 overflow-hidden rounded-md bg-white">
                                         <img src="${rearViewSrc}" class="max-w-full max-h-full object-contain">
                                    </div>
                                    <p class="text-xs text-center font-semibold mt-1">รูปถ่ายด้านหลัง</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            
                summaryContent.innerHTML = html;
            };

            const setupDynamicImagePreview = (inputId, previewImgId, fancyboxLinkId) => {
                const input = document.getElementById(inputId);
                const previewImg = document.getElementById(previewImgId);
                const fancyboxLink = previewImg.closest('a');

                if (input && previewImg && fancyboxLink) {
                    const defaultSrc = previewImg.src;
                    const defaultHref = fancyboxLink.href;

                    input.addEventListener('change', (event) => {
                        const file = event.target.files[0];
                        if (file) {
                            const newSrc = URL.createObjectURL(file);
                            previewImg.src = newSrc;
                            fancyboxLink.href = newSrc;
                        } else {
                            previewImg.src = defaultSrc;
                            fancyboxLink.href = defaultHref;
                        }
                    });
                }
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
                    populateRequestReviewModal();
                    elements.reviewModal.showModal();
                } else {
                    this.showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error');
                }
            });

            elements.finalSubmitBtn.addEventListener('click', () => {
                elements.loadingModal.showModal();
                form.submit();
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
            
            setupDynamicImagePreview('reg_copy_upload', 'reg-copy-preview');
            setupDynamicImagePreview('tax_sticker_upload', 'tax-sticker-preview');
            setupDynamicImagePreview('front_view_upload', 'front-view-preview');
            setupDynamicImagePreview('rear_view_upload', 'rear-view-preview');
            
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
                nidInput: form.querySelector('input[name="national_id_display"]'),
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
                        this.showError(dobDay, 'กรุณาเลือกวันเดือนปีเกิดให้ครบถ้วน');
                        isAllValid = false;
                    } else {
                        this.clearError(dobDay);
                    }
                }

                const officialIdField = form.querySelector('input[name="official_id"]');
                if (officialIdField && officialIdField.offsetParent !== null) {
                    if (!this.validateField(officialIdField)) {
                        isAllValid = false;
                    }
                }

                if (isAllValid) {
                    form.submit();
                } else {
                    this.showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error');
                }
            });
            
            const photoLink = elements.photoContainer.querySelector('a');
            photoLink.addEventListener('click', (e) => {
                if (form.classList.contains('form-edit-mode')) {
                    e.preventDefault(); 
                    elements.photoUpload.click();
                }
            });

            this.setupImagePreview('profile-photo-upload', 'profile-photo-preview');
            
            elements.titleSelect.addEventListener('change', function() {
                const isOther = this.value === 'other';
                const isEditing = !this.disabled;
                const otherInput = elements.titleOtherInput;

                // [แก้ไข] แก้ไขตรรกะการซ่อน/แสดงผลของช่อง "คำนำหน้าอื่นๆ"
                if (isEditing) {
                    otherInput.classList.toggle('hidden', !isOther);
                } else {
                    otherInput.classList.add('hidden');
                }
                
                otherInput.disabled = !isEditing || !isOther;
                if (isOther && isEditing) {
                    otherInput.setAttribute('required', '');
                } else {
                    otherInput.removeAttribute('required');
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
