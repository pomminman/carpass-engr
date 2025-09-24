document.addEventListener('DOMContentLoaded', function () {
    // --- Element Selections ---
    const form = document.getElementById('addUserForm');
    const userTypeRadios = document.querySelectorAll('input[name="user_type"]');
    const mainFormContent = document.getElementById('main-form-content');
    const workInfoSection = document.getElementById('work-info-section');
    const submitBtn = document.getElementById('submit-btn');
    const addressHeader = document.getElementById('address-header');
    const phoneInput = form.querySelector('input[name="phone_number"]');
    const nidInput = form.querySelector('input[name="national_id"]');

    // --- State Variables for Real-time Validation ---
    let phoneStatus = { isChecked: false, isAvailable: true };
    let nidStatus = { isChecked: false, isAvailable: true };
    let isChecking = false;

    // --- Helper Functions for Validation ---
    const showError = (input, message, isDuplicate = false) => {
        const formControl = input.closest('.form-control');
        const errorElement = formControl.querySelector('.error-message');
        if (isDuplicate) {
            errorElement.classList.add('text-error');
            errorElement.classList.remove('text-success');
            input.classList.add('input-error');
            input.classList.remove('input-success');
        } else {
             input.classList.add('input-error', 'select-error');
        }
       
        if (errorElement) {
            errorElement.innerHTML = message; // Use innerHTML to render icons
            errorElement.classList.remove('hidden');
        }
    };

    const showSuccess = (input, message) => {
        const formControl = input.closest('.form-control');
        const errorElement = formControl.querySelector('.error-message');
        input.classList.add('input-success');
        input.classList.remove('input-error');

        if (errorElement) {
            errorElement.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${message}`;
            errorElement.classList.remove('hidden', 'text-error');
            errorElement.classList.add('text-success');
        }
    }

    const clearError = (input) => {
        const formControl = input.closest('.form-control');
        const errorElement = formControl.querySelector('.error-message');
        input.classList.remove('input-error', 'select-error', 'input-success');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.add('hidden');
            errorElement.classList.remove('text-error', 'text-success');
        }
    };

    // --- Real-time Duplicate Check Function ---
    const checkDuplicate = async (field) => {
        if (isChecking) return;
        
        const valueRaw = field.value.replace(/\D/g, '');
        let payload = {};
        let statusObject = {};
        
        if (field.name === 'phone_number' && valueRaw.length === 10) {
            payload = { phone: valueRaw };
            statusObject = phoneStatus;
        } else if (field.name === 'national_id' && valueRaw.length === 13) {
            payload = { nid: valueRaw };
            statusObject = nidStatus;
        } else {
            // Reset status if input is cleared or incomplete
            if (field.name === 'phone_number') phoneStatus = { isChecked: false, isAvailable: true };
            if (field.name === 'national_id') nidStatus = { isChecked: false, isAvailable: true };
            clearError(field);
            return;
        }

        isChecking = true;
        const formControl = field.closest('.form-control');
        const errorElement = formControl.querySelector('.error-message');
        errorElement.innerHTML = '<span class="loading loading-spinner loading-xs"></span> กำลังตรวจสอบ...';
        errorElement.classList.remove('hidden', 'text-error', 'text-success');

        try {
            const response = await fetch('../../../controllers/admin/requests/check_requests.php?action=check_user_duplicate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            statusObject.isChecked = true;
            if (result.exists) {
                statusObject.isAvailable = false;
                showError(field, '<i class="fa-solid fa-circle-xmark"></i> มีอยู่แล้วในระบบ', true);
            } else {
                statusObject.isAvailable = true;
                showSuccess(field, 'ใช้งานได้');
            }
        } catch (error) {
            console.error('Check duplicate error:', error);
            showError(field, 'ไม่สามารถตรวจสอบได้', true);
            statusObject.isAvailable = false; // Assume unavailable on error
        } finally {
            isChecking = false;
        }
    };
    
    // --- Core Validation Logic ---
    const validateField = (field) => {
        const formControl = field.closest('.form-control');
        const value = field.value.trim();
        let isValid = true;
        
        if(field.name !== 'phone_number' && field.name !== 'national_id') {
            clearError(field);
        }
        
        if (field.hasAttribute('required')) {
            if (field.tagName === 'SELECT' && value === '') {
                showError(field, 'กรุณาเลือกข้อมูล');
                isValid = false;
            } else if (field.tagName !== 'SELECT' && value === '') {
                 showError(field, 'กรุณากรอกข้อมูล');
                 isValid = false;
            }
        }
        
        if(field.name === 'title' && value === 'other') {
            const otherInput = form.querySelector('input[name="title_other"]');
            if(otherInput.value.trim() === '') {
                 showError(otherInput, 'กรุณาระบุคำนำหน้า');
                 isValid = false;
            } else {
                 clearError(otherInput);
            }
        }
         if(field.name === 'work_department' && value === 'other') {
            const otherInput = form.querySelector('input[name="work_department_other"]');
            if(otherInput.value.trim() === '') {
                 showError(otherInput, 'กรุณาระบุสังกัด');
                 isValid = false;
            } else {
                 clearError(otherInput);
            }
        }
        return isValid;
    };

    // --- Input Formatting and Filtering ---
    const restrictToThai = (event) => {
        const input = event.target;
        input.value = input.value.replace(/[^ก-๙\s.()]/g, '');
    };
    
    const formatPhoneNumber = (event) => {
        const input = event.target;
        let numbers = input.value.replace(/\D/g, '').substring(0, 10);
        if (numbers.length > 6) {
            input.value = `${numbers.substring(0, 3)}-${numbers.substring(3, 6)}-${numbers.substring(6)}`;
        } else if (numbers.length > 3) {
            input.value = `${numbers.substring(0, 3)}-${numbers.substring(3)}`;
        } else {
            input.value = numbers;
        }
        checkDuplicate(input);
    };
    
    const formatNationalID = (event) => {
        const input = event.target;
        let numbers = input.value.replace(/\D/g, '').substring(0, 13);
        let formatted = numbers;
        if (numbers.length > 12) {
            formatted = `${numbers.substring(0, 1)}-${numbers.substring(1, 5)}-${numbers.substring(5, 10)}-${numbers.substring(10, 12)}-${numbers.substring(12)}`;
        } else if (numbers.length > 10) {
            formatted = `${numbers.substring(0, 1)}-${numbers.substring(1, 5)}-${numbers.substring(5, 10)}-${numbers.substring(10)}`;
        } else if (numbers.length > 5) {
            formatted = `${numbers.substring(0, 1)}-${numbers.substring(1, 5)}-${numbers.substring(5)}`;
        } else if (numbers.length > 1) {
            formatted = `${numbers.substring(0, 1)}-${numbers.substring(1)}`;
        }
        input.value = formatted;
        checkDuplicate(input);
    };
    
    // --- Event Listeners Setup ---
    userTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            mainFormContent.classList.remove('hidden');
            submitBtn.disabled = false;
            const workDeptSelect = workInfoSection.querySelector('select[name="work_department"]');
            if (this.value === 'army') {
                workInfoSection.classList.remove('hidden');
                workDeptSelect.setAttribute('required', 'required');
            } else {
                workInfoSection.classList.add('hidden');
                workDeptSelect.removeAttribute('required');
                clearError(workDeptSelect);
            }
        });
    });

    form.querySelectorAll('input[required], select[required]').forEach(field => {
        const eventType = field.tagName === 'SELECT' ? 'change' : 'input';
        field.addEventListener(eventType, () => validateField(field));
        if (field.name.endsWith('_other')) {
             field.addEventListener('input', () => validateField(field));
        }
    });

    form.querySelector('input[name="firstname"]').addEventListener('input', restrictToThai);
    form.querySelector('input[name="lastname"]').addEventListener('input', restrictToThai);
    form.querySelector('input[name="title_other"]').addEventListener('input', restrictToThai);
    form.querySelector('input[name="position"]').addEventListener('input', restrictToThai);
    form.querySelector('input[name="address"]').addEventListener('input', restrictToThai);
    
    phoneInput.addEventListener('input', formatPhoneNumber);
    nidInput.addEventListener('input', formatNationalID);
    form.querySelector('input[name="official_id"]').addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '').substring(0, 10);
    });

    // --- Form Submission Logic ---
    form.addEventListener('submit', function(e) {
        e.preventDefault(); 
        let isFormValid = true;

        const selectedType = form.querySelector('input[name="user_type"]:checked');
        const typeContainer = document.getElementById('user-type-selection');
        const typeErrorElement = typeContainer.querySelector('.error-message');
        
        if (!selectedType) {
            isFormValid = false;
            typeErrorElement.textContent = 'กรุณาเลือกประเภทผู้สมัคร';
            typeErrorElement.classList.remove('hidden');
        } else {
            typeErrorElement.textContent = '';
            typeErrorElement.classList.add('hidden');
        }
        
        if (!phoneStatus.isAvailable) {
            isFormValid = false;
            showError(phoneInput, '<i class="fa-solid fa-circle-xmark"></i> เบอร์โทรนี้มีอยู่แล้วในระบบ', true);
        }
         if (!nidStatus.isAvailable) {
            isFormValid = false;
            showError(nidInput, '<i class="fa-solid fa-circle-xmark"></i> เลขบัตรนี้มีอยู่แล้วในระบบ', true);
        }

        form.querySelectorAll('[required]').forEach(field => {
            if (field.type !== 'radio' && field.offsetParent !== null) {
                if (!validateField(field)) {
                    isFormValid = false;
                }
            }
        });

        if (isFormValid) {
            submitBtn.classList.add('btn-disabled', 'cursor-not-allowed');
            submitBtn.innerHTML = '<span class="loading loading-spinner"></span> กำลังบันทึก...';
            form.submit();
        } else {
            showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error');
            const firstErrorField = form.querySelector('.input-error, .select-error');
            if (firstErrorField) {
                firstErrorField.focus();
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    // --- Other Initializations ---
    const photoUpload = document.getElementById('photo-upload');
    const photoPreview = document.getElementById('photo-preview');
    if (photoUpload) {
        photoUpload.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => { photoPreview.src = e.target.result; };
                reader.readAsDataURL(file);
            }
        });
    }

    const daySelect = document.querySelector('select[name="dob_day"]');
    const monthSelect = document.querySelector('select[name="dob_month"]');
    const yearSelect = document.querySelector('select[name="dob_year"]');
    const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    for (let i = 1; i <= 31; i++) { daySelect.innerHTML += `<option value="${i}">${i}</option>`; }
    months.forEach((month, i) => { monthSelect.innerHTML += `<option value="${String(i + 1).padStart(2, '0')}">${month}</option>`; });
    const currentYearBE = new Date().getFullYear() + 543;
    for (let i = currentYearBE; i >= currentYearBE - 100; i--) { yearSelect.innerHTML += `<option value="${i}">${i}</option>`; }
    
    function setupOtherOption(selectName, otherInputName) {
        const select = document.querySelector(`select[name="${selectName}"]`);
        const otherInput = document.querySelector(`input[name="${otherInputName}"]`);
        if (select && otherInput) {
            select.addEventListener('change', function() {
                const isOther = this.value === 'other';
                otherInput.classList.toggle('hidden', !isOther);
                if(isOther) {
                    otherInput.setAttribute('required', 'required');
                } else {
                    otherInput.removeAttribute('required');
                    otherInput.value = '';
                    clearError(otherInput);
                }
                validateField(select);
            });
        }
    }
    setupOtherOption('title', 'title_other');
    setupOtherOption('work_department', 'work_department_other');

    if (typeof $ !== 'undefined') {
        $.Thailand({
            $district: $('input[name="subdistrict"]'),
            $amphoe: $('input[name="district"]'),
            $province: $('input[name="province"]'),
            $zipcode: $('input[name="zipcode"]'),
        });
    }

    function showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container');
        if (!alertContainer) return;
        const alertId = `alert-${Date.now()}`;
        const alertElement = document.createElement('div');
        alertElement.id = alertId;
        alertElement.className = `alert alert-${type} alert-soft shadow-lg`;
        let icon = '';
        if (type === 'success') icon = '<i class="fa-solid fa-circle-check"></i>';
        else if (type === 'error') icon = '<i class="fa-solid fa-circle-xmark"></i>';
        alertElement.innerHTML = `<div class="flex items-center">${icon}<span class="ml-2 text-xs sm:text-sm whitespace-nowrap">${message}</span></div>`;
        alertContainer.appendChild(alertElement);
        setTimeout(() => {
            const existingAlert = document.getElementById(alertId);
            if (existingAlert) {
                existingAlert.style.transition = 'opacity 0.3s ease';
                existingAlert.style.opacity = '0';
                setTimeout(() => existingAlert.remove(), 300);
            }
        }, 5000);
    }
});

