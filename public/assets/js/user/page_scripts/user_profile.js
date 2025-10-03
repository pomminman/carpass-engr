// public/assets/js/user/page_scripts/user_profile.js
CarPassApp.registerPageScript('profile', function(app) {
    const form = document.getElementById('profileForm');
    if (!form) return;

    const elements = {
        editBtn: document.getElementById('edit-profile-btn'),
        saveBtn: document.getElementById('save-profile-btn'),
        cancelBtn: document.getElementById('cancel-edit-btn'),
        photoUpload: document.getElementById('profile-photo-upload'),
        photoContainer: document.getElementById('profile-photo-container'),
        titleSelect: document.getElementById('profile-title'),
        titleOtherInput: document.getElementById('profile-title-other'),
        // [ADDED] Reference to the loading modal
        loadingModal: document.getElementById('loading_modal'),
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
        document.getElementById('photo-guidance').classList.toggle('hidden', !isEditing);

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
    };

    elements.editBtn.addEventListener('click', () => setEditMode(true));
    elements.cancelBtn.addEventListener('click', () => location.reload());
    
    elements.saveBtn.addEventListener('click', (e) => {
        e.preventDefault();
        let isAllValid = true;
        
        form.querySelectorAll('[required]').forEach(field => {
            if (field.offsetParent !== null && !app.validateField(field)) {
                isAllValid = false;
            }
        });
        
        if (elements.photoUpload.files.length > 0 && !app.validateField(elements.photoUpload)) {
            isAllValid = false;
        }

        const dobDay = form.querySelector('#profile-dob-day');
        if (dobDay.offsetParent !== null) { 
            if (!dobDay.value || !form.querySelector('#profile-dob-month').value || !form.querySelector('#profile-dob-year').value) {
                app.showError(dobDay, 'กรุณาเลือกวันเดือนปีเกิดให้ครบถ้วน');
                isAllValid = false;
            } else {
                app.clearError(dobDay);
            }
        }

        const officialIdField = form.querySelector('input[name="official_id"]');
        if (officialIdField && officialIdField.offsetParent !== null) {
            if (!app.validateField(officialIdField)) {
                isAllValid = false;
            }
        }

        if (isAllValid) {
            // [MODIFIED] Show loading modal before submitting the form
            if (elements.loadingModal) {
                elements.loadingModal.showModal();
            }
            form.submit();
        } else {
            app.showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error');
        }
    });
    
    elements.photoContainer.querySelector('a').addEventListener('click', (e) => {
        if (form.classList.contains('form-edit-mode')) {
            e.preventDefault(); 
            elements.photoUpload.click();
        }
    });

    app.setupImagePreview('profile-photo-upload', 'profile-photo-preview');
    
    elements.titleSelect.addEventListener('change', function() {
        const isOther = this.value === 'other';
        const isEditing = !this.disabled;
        const otherInput = elements.titleOtherInput;
        otherInput.classList.toggle('hidden', !isEditing || !isOther);
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

    form.querySelectorAll('input, select').forEach(field => {
        const eventType = (field.tagName === 'SELECT' || ['file', 'checkbox'].includes(field.type)) ? 'change' : 'input';
        field.addEventListener(eventType, () => {
            if (form.classList.contains('form-edit-mode')) {
                app.validateField(field);
            }
        });
    });

    setEditMode(false);
});

