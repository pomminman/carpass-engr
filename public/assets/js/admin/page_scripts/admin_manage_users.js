/**
 * admin_manage_users.js
 * Contains page-specific JavaScript for the Manage Users page.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('manage-users-page')) return;

    const App = window.App || {};
    const addUserModal = document.getElementById('modal_add_user');
    const form = document.getElementById('modalAddUserForm');
    const addUserBtn = document.getElementById('add-user-modal-btn');
    
    if (!form || !addUserModal || !addUserBtn) return;

    let isThailandJsInitialized = false;

    // This listener will now handle initializing the address lookup
    addUserBtn.addEventListener('click', function() {
        // The onclick attribute in HTML will open the modal.
        // This listener will fire right after, allowing us to initialize the script.
        if (!isThailandJsInitialized) {
            $.Thailand.setup({
                database: '/lib/jquery.Thailand/database/db.json'
            });
            $.Thailand({
                $district: $(form).find('input[name="subdistrict"]'),
                $amphoe: $(form).find('input[name="district"]'),
                $province: $(form).find('input[name="province"]'),
                $zipcode: $(form).find('input[name="zipcode"]'),
            });
            isThailandJsInitialized = true;
        }
    });

    const elements = {
        userType: form.querySelector('#modal-user-type'),
        workInfoSection: form.querySelector('#modal-work-info-section'),
        workDeptSelect: form.querySelector('select[name="work_department"]'),
        title: form.querySelector('#modal-title'),
        titleOther: form.querySelector('#modal-title-other'),
        phoneInput: form.querySelector('#modal-form-phone'),
        nidInput: form.querySelector('#modal-personal-id'),
        photoInput: form.querySelector('input[name="photo_upload"]'),
        photoPreview: form.querySelector('#modal-photo-preview'),
        loadingModal: document.getElementById('loading_modal')
    };

    const showError = (el, message) => {
        const parent = el.closest('.form-control');
        const errorEl = parent?.querySelector('.error-message');
        if (errorEl) { errorEl.textContent = message; errorEl.classList.remove('hidden'); }
        el.classList.add('input-error', 'select-error');
    };
    
    const clearError = (el) => {
        const parent = el.closest('.form-control');
        const errorEl = parent?.querySelector('.error-message');
        if (errorEl) { errorEl.textContent = ''; errorEl.classList.add('hidden'); }
        el.classList.remove('input-error', 'select-error');
    };

    const formatInput = (input, patterns) => {
        const numbers = input.value.replace(/\D/g, '');
        let result = '', patternIndex = 0, numbersIndex = 0;
        while (patternIndex < patterns.length && numbersIndex < numbers.length) {
            if (patterns[patternIndex] === '-') { result += '-'; patternIndex++; } 
            else { result += numbers[numbersIndex]; patternIndex++; numbersIndex++; }
        }
        input.value = result;
    };

    elements.userType.addEventListener('change', (e) => {
        const isArmy = e.target.value === 'army';
        elements.workInfoSection.classList.toggle('hidden', !isArmy);
        if (isArmy) {
            elements.workDeptSelect.setAttribute('required', '');
        } else {
            elements.workDeptSelect.removeAttribute('required');
            clearError(elements.workDeptSelect); // Clear error if section is hidden
        }
    });
    
    elements.title.addEventListener('change', (e) => {
        const isOther = e.target.value === 'other';
        elements.titleOther.classList.toggle('hidden', !isOther);
        if (isOther) {
            elements.titleOther.setAttribute('required', '');
        } else {
            elements.titleOther.removeAttribute('required');
            clearError(elements.titleOther);
        }
    });

    const daySelect = form.querySelector('select[name="dob_day"]'), monthSelect = form.querySelector('select[name="dob_month"]'), yearSelect = form.querySelector('select[name="dob_year"]');
    const months = ["มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
    daySelect.innerHTML = '<option value="">วัน</option>'; monthSelect.innerHTML = '<option value="">เดือน</option>'; yearSelect.innerHTML = '<option value="">ปี (พ.ศ.)</option>';
    for (let i = 1; i <= 31; i++) { daySelect.innerHTML += `<option value="${i}">${i}</option>`; }
    months.forEach((m, i) => { monthSelect.innerHTML += `<option value="${i + 1}">${m}</option>`; });
    const currentYearBE = new Date().getFullYear() + 543;
    for (let i = currentYearBE-17; i >= currentYearBE - 100; i--) { yearSelect.innerHTML += `<option value="${i}">${i}</option>`; }

    elements.photoInput.addEventListener('change', (e) => {
        if (e.target.files && e.target.files[0]) { elements.photoPreview.src = URL.createObjectURL(e.target.files[0]); }
    });
    
    elements.phoneInput.addEventListener('input', () => formatInput(elements.phoneInput, 'xxx-xxx-xxxx'));
    elements.nidInput.addEventListener('input', () => formatInput(elements.nidInput, 'x-xxxx-xxxxx-xx-x'));

    form.addEventListener('submit', (e) => {
        let isAllValid = true;
        form.querySelectorAll('[required]').forEach(field => {
            if (field.offsetParent !== null) { // Only validate visible fields
                if (!field.value.trim()) {
                    isAllValid = false;
                    showError(field, 'กรุณากรอกข้อมูล');
                } else {
                    clearError(field);
                }
            }
        });
        
        if (!isAllValid) {
            e.preventDefault();
            App.showAlert('กรุณากรอกข้อมูลที่บังคับให้ครบถ้วน', 'error');
            const firstErrorField = form.querySelector('.input-error, .select-error');
            if (firstErrorField) {
                firstErrorField.focus();
            }
        } else {
            if(elements.loadingModal) elements.loadingModal.showModal();
        }
    });

    // Add listeners to clear errors on input/change
    form.querySelectorAll('[required]').forEach(field => {
        const eventType = (field.tagName === 'SELECT') ? 'change' : 'input';
        field.addEventListener(eventType, () => {
             if (field.value.trim()) {
                clearError(field);
            }
        });
    });
});
