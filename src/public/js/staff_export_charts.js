// staff_export_charts.js - grafice pentru staff_export.php (foloseste Chart.js)
// Asigura-te ca ai <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> in pagina

document.addEventListener('DOMContentLoaded', function() {
    // Exemplu: datele pot fi generate din PHP (vezi staff_export.php pentru integrare reala)
    if (document.getElementById('wasteBarChart')) {
        var ctx = document.getElementById('wasteBarChart').getContext('2d');
        var wasteBarChart = new Chart(ctx, {
            type: 'bar',
            data: window.WASTE_BAR_DATA,
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Cantitate deseuri pe tip' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
    if (document.getElementById('wastePieChart')) {
        var ctx2 = document.getElementById('wastePieChart').getContext('2d');
        var wastePieChart = new Chart(ctx2, {
            type: 'pie',
            data: window.WASTE_PIE_DATA,
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Procent pe tip de deseu' }
                }
            }
        });
    }
});
