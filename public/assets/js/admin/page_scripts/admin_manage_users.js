// public/assets/js/admin/page_scripts/admin_manage_users.js
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('usersTable');
    const filterForm = document.getElementById('filterForm');

    // --- Table Sorting Logic ---
    if (table) {
        const headers = table.querySelectorAll('th[data-sort-by]');
        headers.forEach(header => {
            header.addEventListener('click', () => {
                const tbody = table.querySelector('tbody');
                // Exclude no-results rows from sorting
                const rows = Array.from(tbody.querySelectorAll('tr:not(#no-results-row)'));
                const sortBy = header.dataset.sortBy;
                let direction = 'asc';

                if (header.classList.contains('sort-asc')) {
                    direction = 'desc';
                }

                headers.forEach(h => {
                    h.classList.remove('sort-asc', 'sort-desc');
                    h.querySelector('i').className = 'fa-solid fa-sort';
                });

                header.classList.add(`sort-${direction}`);
                header.querySelector('i').className = `fa-solid fa-sort-${direction === 'asc' ? 'up' : 'down'}`;

                rows.sort((a, b) => {
                    const cellA = a.querySelector(`[data-cell="${sortBy}"]`);
                    const cellB = b.querySelector(`[data-cell="${sortBy}"]`);

                    let valA = cellA.dataset.sortValue || cellA.textContent.trim().toLowerCase();
                    let valB = cellB.dataset.sortValue || cellB.textContent.trim().toLowerCase();

                    if (!isNaN(valA) && !isNaN(valB)) {
                        valA = parseFloat(valA);
                        valB = parseFloat(valB);
                    }
                    
                    if (valA < valB) return direction === 'asc' ? -1 : 1;
                    if (valA > valB) return direction === 'asc' ? 1 : -1;
                    return 0;
                });
                
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    }

    // --- Auto-submit Filter Form Logic ---
    if (filterForm) {
        const inputs = filterForm.querySelectorAll('.filter-input');
        let debounceTimer;

        inputs.forEach(input => {
            const eventType = input.tagName.toLowerCase() === 'select' ? 'change' : 'input';
            
            input.addEventListener(eventType, () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    filterForm.submit();
                }, input.type === 'text' ? 400 : 0);
            });
        });
    }
});

