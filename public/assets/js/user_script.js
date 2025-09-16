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
            this.initW3ModalFunctionality(); 
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
            // ... (dashboard logic remains unchanged)
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
           // ... (profile logic remains unchanged)
        }
    };

    App.init();
});

