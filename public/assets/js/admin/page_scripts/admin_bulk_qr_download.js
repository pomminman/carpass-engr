// public/assets/js/admin/page_scripts/admin_bulk_qr_download.js
document.addEventListener('DOMContentLoaded', function () {
    const bulkQrModal = document.getElementById('bulk_qr_modal');
    if (!bulkQrModal) return;

    const App = window.App || {};
    const bulkQrForm = document.getElementById('bulkQrForm');
    const bulkQrSelect = $('#bulk-qr-search-select');
    const downloadAllBtn = document.getElementById('download-all-qr-btn');

    // Initialize Select2
    bulkQrSelect.select2({
        dropdownParent: $(bulkQrModal),
        placeholder: 'ค้นหาและเลือกคำร้องที่อนุมัติแล้ว...',
        width: '100%',
        ajax: {
            url: '../../../controllers/admin/requests/check_requests.php?action=search_approved_requests',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data.items };
            },
            cache: true
        }
    });

    // Fix focus issue with DaisyUI modal
    bulkQrSelect.on('select2:open', () => {
        setTimeout(() => {
            const searchInput = document.querySelector('.select2-search__field');
            if (searchInput) {
                 searchInput.focus();
            }
        }, 50);
    });
    
    // Handle submission for selected items
    bulkQrForm.addEventListener('submit', function(e) {
        const selectedIds = bulkQrSelect.val();
        if (!selectedIds || selectedIds.length === 0) {
            e.preventDefault();
            App.showAlert('กรุณาเลือกอย่างน้อย 1 คำร้อง', 'error');
        } else {
            // Close the modal after a short delay to allow form submission
            setTimeout(() => bulkQrModal.close(), 500);
        }
    });

    // Handle "Download All" button click
    downloadAllBtn.addEventListener('click', function() {
        // Create a hidden input to signify 'download all'
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'download_all';
        hiddenInput.value = 'true';
        bulkQrForm.appendChild(hiddenInput);
        
        // Submit the form
        bulkQrForm.submit();
        
        // Clean up by removing the hidden input after submission
        setTimeout(() => {
            bulkQrForm.removeChild(hiddenInput);
            bulkQrModal.close();
        }, 500);
    });


    // Clear selection when modal is closed
    const modalObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === "open" && !bulkQrModal.hasAttribute("open")) {
                 bulkQrSelect.val(null).trigger('change');
            }
        });
    });
    modalObserver.observe(bulkQrModal, { attributes: true });

});

