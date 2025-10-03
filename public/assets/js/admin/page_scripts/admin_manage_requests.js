// public/assets/js/admin/page_scripts/admin_manage_requests.js
document.addEventListener('DOMContentLoaded', function() {
    const addRequestModal = document.getElementById('add_request_modal');
    const selectUserForm = document.getElementById('selectUserForm');
    const userSelect = $('#user-search-select');
    const filterForm = document.getElementById('filterForm');

    // Initialize Select2 for the "Add Request" modal
    userSelect.select2({
        dropdownParent: $('#add_request_modal'),
        placeholder: 'คลิกเพื่อเลือก หรือพิมพ์เพื่อค้นหา...',
        ajax: {
            url: '../../../controllers/admin/requests/check_requests.php?action=search_users', 
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

    // Handle form submission to go to the add request page from modal
    if (selectUserForm) {
        selectUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const selectedUserId = userSelect.val();
            if (selectedUserId) {
                window.location.href = `add_request.php?user_id=${selectedUserId}`;
            } else {
                const App = window.App || {};
                App.showAlert('โปรดเลือกผู้ใช้งานที่ต้องการสร้างคำร้อง', 'error');
            }
        });
    }

    // Auto-submit Filter Form Logic
    if (filterForm) {
        const inputs = filterForm.querySelectorAll('.filter-input');
        let debounceTimer;

        inputs.forEach(input => {
            // Skip the date range picker input, it's handled separately
            if(input.id === 'date-range-filter') return;

            const eventType = input.tagName.toLowerCase() === 'select' ? 'change' : 'input';
            
            input.addEventListener(eventType, () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const pageInput = filterForm.querySelector('input[name="page"]');
                    if(pageInput) pageInput.value = 1;
                    filterForm.submit();
                }, input.type === 'text' ? 400 : 0);
            });
        });
    }
    
    // --- Litepicker for Date Range ---
    const dateRangeInput = document.getElementById('date-range-filter');
    if (dateRangeInput) {
        const startDateInput = document.getElementById('date_start_hidden');
        const endDateInput = document.getElementById('date_end_hidden');
        let isPickerInitialized = false; // Flag to prevent initial load loop

        const picker = new Litepicker({
            element: dateRangeInput,
            singleMode: false,
            autoApply: true,
            format: 'DD/MM/YYYY',
            separator: ' - ',
            lang: 'th-TH',
            plugins: ['mobilefriendly'],
            buttonText: {
                previousMonth: `<i class="fa-solid fa-chevron-left"></i>`,
                nextMonth: `<i class="fa-solid fa-chevron-right"></i>`,
                reset: 'ล้าง',
                apply: 'ตกลง',
            },
            setup: (picker) => {
                picker.on('selected', (date1, date2) => {
                    // [FIX] Only submit if the picker has been fully initialized and dates are actually selected
                    if (isPickerInitialized && date1 && date2) {
                        const newStartDate = date1.format('YYYY-MM-DD');
                        const newEndDate = date2.format('YYYY-MM-DD');
                        
                        // Submit only if the date range has actually changed
                        if (newStartDate !== startDateInput.value || newEndDate !== endDateInput.value) {
                            startDateInput.value = newStartDate;
                            endDateInput.value = newEndDate;
                            filterForm.submit();
                        }
                    }
                });
                 picker.on('clear:selection', () => {
                    if (isPickerInitialized) {
                        startDateInput.value = '';
                        endDateInput.value = '';
                        filterForm.submit();
                    }
                });
            },
        });

        // Set initial value if dates are present from URL, without triggering the 'selected' event immediately
        if (startDateInput.value && endDateInput.value) {
            picker.setDateRange(new Date(startDateInput.value), new Date(endDateInput.value));
        }
        
        // Now that initialization is complete, allow the 'selected' event to trigger form submissions
        isPickerInitialized = true;
    }
});

