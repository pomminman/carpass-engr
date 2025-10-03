document.addEventListener('DOMContentLoaded', function () {
    const bulkModal = document.getElementById('bulk_payment_modal');
    if (!bulkModal) return;

    const App = window.App || {};
    const bulkForm = document.getElementById('bulkPaymentForm');
    const bulkSelect = $('#bulk-request-search-select');
    const loadingModal = document.getElementById('loading_modal');

    // Initialize Select2 for bulk request selection
    bulkSelect.select2({
        dropdownParent: $(bulkModal),
        placeholder: 'ค้นหาและเลือกคำร้อง...',
        width: '100%',
        ajax: {
            url: '../../../controllers/admin/requests/check_requests.php?action=search_payable_requests',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                // No need for a separate department filter, just send the search term
                return {
                    q: params.term
                };
            },
            processResults: function (data) {
                return { results: data.items };
            },
            cache: true
        }
    });

    // Force focus onto the search field when the dropdown opens to fix conflict with DaisyUI modal
    bulkSelect.on('select2:open', () => {
        setTimeout(() => {
            const searchInput = document.querySelector('.select2-search__field');
            if (searchInput) {
                searchInput.focus();
            }
        }, 50); // A small delay is necessary
    });

    bulkForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const selectedIds = bulkSelect.val();
        if (!selectedIds || selectedIds.length === 0) {
            App.showAlert('กรุณาเลือกอย่างน้อย 1 คำร้อง', 'error');
            return;
        }

        if (!confirm(`คุณต้องการยืนยันการชำระเงินและรับบัตรสำหรับ ${selectedIds.length} รายการที่เลือกใช่หรือไม่?`)) {
            return;
        }

        loadingModal.showModal();

        const payload = {
            request_ids: selectedIds,
            amount: document.getElementById('bulk-payment-amount').value,
            method: document.getElementById('bulk-payment-method').value,
            notes: document.getElementById('bulk-payment-notes').value,
        };

        try {
            const response = await fetch('../../../controllers/admin/requests/check_requests.php?action=process_bulk_payment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            
            if (result.success) {
                bulkModal.close();
                bulkSelect.val(null).trigger('change');
                App.showAlert(result.message || 'ดำเนินการสำเร็จ', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(result.message || 'เกิดข้อผิดพลาดในการประมวลผล');
            }
        } catch (error) {
            App.showAlert(error.message, 'error');
        } finally {
            if (loadingModal.hasAttribute('open')) {
                loadingModal.close();
            }
        }
    });

    // Clear selection when modal is closed
    const modalObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === "open" && !bulkModal.hasAttribute("open")) {
                 bulkSelect.val(null).trigger('change');
            }
        });
    });
    modalObserver.observe(bulkModal, { attributes: true });

});

