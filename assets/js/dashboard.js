function showExpenseModal() {
    document.getElementById('expenseModal').style.display = 'block';
}

function closeExpenseModal() {
    document.getElementById('expenseModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('expenseModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

function exportToExcel() {
    window.location.href = 'exports/export_dashboard.php';
}

// Auto-refresh dashboard data every 30 seconds
setInterval(function() {
    // You can implement AJAX refresh here if needed
}, 30000);
