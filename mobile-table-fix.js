// Automatically add data-label attributes to table cells for mobile view
document.addEventListener('DOMContentLoaded', function() {
    const comparisonTable = document.querySelector('.comparison-table table');
    
    if (comparisonTable) {
        const headers = comparisonTable.querySelectorAll('thead th');
        const headerTexts = Array.from(headers).map(th => th.textContent.trim());
        
        const rows = comparisonTable.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (headerTexts[index]) {
                    cell.setAttribute('data-label', headerTexts[index]);
                }
            });
        });
    }
});
