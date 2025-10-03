// public/assets/js/admin/page_scripts/admin_edit_profile.js
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('editProfileForm');
    if (!form) return;

    const App = window.App || {};

    const showError = (input, message) => {
        const formControl = input.closest('.form-control');
        const errorElement = formControl.querySelector('.error-message');
        input.classList.add('input-error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
        }
    };

    const clearError = (input) => {
        const formControl = input.closest('.form-control');
        const errorElement = formControl.querySelector('.error-message');
        input.classList.remove('input-error');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.add('hidden');
        }
    };
    
    const validateForm = () => {
        let isValid = true;
        form.querySelectorAll('input[required]').forEach(input => {
            if (!input.value.trim()) {
                showError(input, 'กรุณากรอกข้อมูล');
                isValid = false;
            } else {
                clearError(input);
            }
        });

        const newPassword = form.querySelector('input[name="new_password"]');
        const confirmPassword = form.querySelector('input[name="confirm_password"]');

        clearError(newPassword);
        clearError(confirmPassword);

        if (newPassword.value) { // Only validate if new password is being set
            if (newPassword.value.length < 6) {
                showError(newPassword, 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
                isValid = false;
            }
            if (newPassword.value !== confirmPassword.value) {
                showError(confirmPassword, 'รหัสผ่านไม่ตรงกัน');
                isValid = false;
            }
        }
        
        return isValid;
    };

    form.addEventListener('submit', function(event) {
        if (!validateForm()) {
            event.preventDefault();
            App.showAlert('กรุณากรอกข้อมูลให้ถูกต้อง', 'error');
        }
    });

    // Add real-time validation listeners
    form.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', () => {
             // Clear specific error on input
             if (input.name === 'new_password' || input.name === 'confirm_password') {
                 clearError(form.querySelector('input[name="new_password"]'));
                 clearError(form.querySelector('input[name="confirm_password"]'));
             } else {
                 clearError(input);
             }
        });
    });
});
