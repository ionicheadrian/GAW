<?php
require_once '../config/config.php';
$user_info = get_user_info();
$is_staff = $user_info && in_array($user_info['role'], ['staff', 'admin']);
if (!is_logged_in() || !in_array(get_user_info()['role'], ['staff', 'admin'])) {
    header('Location: login.php');
    exit;
}

$period = $_GET['period'] ?? 'month';
$period_sql = '';
$period_label = 'Luna curentă';

switch ($period) {
    case 'day':
        $period_sql = 'DATE(deposit_date) = CURDATE()';
        $period_label = 'Astăzi';
        break;
    case 'week':
        $period_sql = 'YEARWEEK(deposit_date, 1) = YEARWEEK(CURDATE(), 1)';
        $period_label = 'Săptămâna curentă';
        break;
    case 'month':
    default:
        $period_sql = 'YEAR(deposit_date) = YEAR(CURDATE()) AND MONTH(deposit_date) = MONTH(CURDATE())';
        $period_label = 'Luna curentă';
        break;
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="depozitari_' . $period . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Data', 'Utilizator', 'Locatie', 'Tip deseu', 'Cantitate (kg)', 'Observatii']);
    $query = "SELECT wd.deposit_date, u.full_name, l.name as location, wc.type as waste_type, wd.quantity_kg, wd.notes
              FROM waste_deposits wd
              LEFT JOIN users u ON wd.user_id = u.id
              LEFT JOIN locations l ON wd.location_id = l.id
              LEFT JOIN waste_categories wc ON wd.waste_category_id = wc.id
              WHERE $period_sql
              ORDER BY wd.deposit_date DESC";
    $result = mysqli_query($connection, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            date('d.m.Y H:i', strtotime($row['deposit_date'])),
            $row['full_name'],
            $row['location'],
            ucfirst(iconv('UTF-8', 'ASCII//TRANSLIT', $row['waste_type'])),
            number_format($row['quantity_kg'], 1),
            iconv('UTF-8', 'ASCII//TRANSLIT', $row['notes'])
        ]);
    }
    fclose($output);
    exit;
}
if (isset($_GET['export']) && $_GET['export'] === 'html') {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="depozitari_' . $period . '.html"');
    echo "<html><head><meta charset='UTF-8'><title>Export Depozitari</title></head><body>";
    echo "<h2>Depozitari - " . htmlspecialchars($period_label) . "</h2>";
    echo "<table border='1' cellpadding='4' cellspacing='0'>";
    echo "<tr><th>Data</th><th>Utilizator</th><th>Locatie</th><th>Tip deseu</th><th>Cantitate (kg)</th><th>Observatii</th></tr>";
    $query = "SELECT wd.deposit_date, u.full_name, l.name as location, wc.type as waste_type, wd.quantity_kg, wd.notes
              FROM waste_deposits wd
              LEFT JOIN users u ON wd.user_id = u.id
              LEFT JOIN locations l ON wd.location_id = l.id
              LEFT JOIN waste_categories wc ON wd.waste_category_id = wc.id
              WHERE $period_sql
              ORDER BY wd.deposit_date DESC";
    $result = mysqli_query($connection, $query);
    if (mysqli_num_rows($result) === 0) {
        echo "<tr><td colspan='6'>Nu exista depozitari pentru aceasta perioada.</td></tr>";
    } else {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . date('d.m.Y H:i', strtotime($row['deposit_date'])) . "</td>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['location']) . "</td>";
            echo "<td>" . ucfirst(iconv('UTF-8', 'ASCII//TRANSLIT', $row['waste_type'])) . "</td>";
            echo "<td>" . number_format($row['quantity_kg'], 1) . "</td>";
            echo "<td>" . htmlspecialchars(iconv('UTF-8', 'ASCII//TRANSLIT', $row['notes'])) . "</td>";
            echo "</tr>";
        }
    }
    echo "</table></body></html>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Export Depozitări - EcoManager</title>
    <link rel="stylesheet" href="../public/css/navbar.css">
    <link rel="stylesheet" href="../public/css/staff_export.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../public/js/staff_export_charts.js" defer></script>
</head>
<body>
<nav>
        <ul class="nav-links">
            <?php if ($is_staff): ?>
                <li><a class="pagini" href="home.php">🏠 Home</a></li>
                <li><a class="pagini" href="report.php">♻️ Depozitare</a></li>
                <li><a class="pagini" href="locations.php">🗺️ Locatii</a></li>
                <li><a class="pagini" href="simulator.php">🔬 Simulator</a></li>
                <li><a class="pagini" href="dashboard_staff.php">📊 Dashboard</a></li>
            <?php else: ?>

                <li><a class="pagini" href="home.php">🏠 Home</a></li>
                <li><a class="pagini" href="report.php">♻️ Depozitare</a></li>
                <li><a class="pagini" href="locations.php">🗺️ Locatii</a></li>
                <li><a class="pagini" href="simulator.php">🔬 Simulator</a></li>
            <?php endif; ?>
        </ul>
        
        <div class="userprofile">
            <img src="../public/images/user.jpg" class="userpic" alt="Profil utilizator">
            <span class="username">
                <?= htmlspecialchars($user_info['name'] ?? 'Utilizator') ?>
                <?php if ($is_staff): ?>
                    (<?= ucfirst($user_info['role']) ?>)
                <?php endif; ?>
            </span>
            
            <div class="profile-dropdown">
                <div class="dropdown-content">
                    <a href="profile.php">👤 Profil Personal</a>
                    <a href="settings.php">⚙️ Setari Cont</a>
                    <?php if ($user_info['role'] === 'admin'): ?>
                        <hr>
                        <a href="admin_panel.php">🔧 Panel Administrare</a>
                    <?php endif; ?>
                    <?php if ($is_staff): ?>
                        <hr>
                        <a href="staff_export.php">📊 Export Date</a>
                    <?php endif; ?>
                    <hr>
                    <a href="logout.php" onclick="return confirm('Sigur doriți să vă delogați?')">🚪 Delogare</a>
                </div>
            </div>
        </div>
        
        <button class="mobile-menu" aria-label="Toggle navigation menu" aria-expanded="false">☰</button>
    </nav>
<main class="main-content">
    <h1>Export Depozitari</h1>
    <form method="get" class="export-form">
        <label>Perioada:
            <select name="period">
                <option value="day" <?= $period === 'day' ? 'selected' : '' ?>>Astazi</option>
                <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Saptamana curenta</option>
                <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Luna curenta</option>
            </select>
        </label>
        <button type="submit">Afiseaza</button>
        <button type="submit" name="export" value="csv">Exporta CSV</button>
        <button type="submit" name="export" value="pdf" formaction="staff_export_pdf.php">Exporta PDF</button>
        <button type="submit" name="export" value="html">Exporta HTML</button>
    </form>
    <h2>Depozitari - <?= htmlspecialchars($period_label) ?></h2>
    <div class="export-table-wrapper">
    <table class="export-table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Utilizator</th>
                <th>Locatie</th>
                <th>Tip deseu</th>
                <th>Cantitate (kg)</th>
                <th>Observatii</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $query = "SELECT wd.deposit_date, u.full_name, l.name as location, wc.type as waste_type, wd.quantity_kg, wd.notes
                  FROM waste_deposits wd
                  LEFT JOIN users u ON wd.user_id = u.id
                  LEFT JOIN locations l ON wd.location_id = l.id
                  LEFT JOIN waste_categories wc ON wd.waste_category_id = wc.id
                  WHERE $period_sql
                  ORDER BY wd.deposit_date DESC";
        $result = mysqli_query($connection, $query);
        if (mysqli_num_rows($result) === 0): ?>
            <tr><td colspan="6">Nu exista depozitari pentru aceasta perioada.</td></tr>
        <?php else:
            while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= date('d.m.Y H:i', strtotime($row['deposit_date'])) ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['location']) ?></td>
                <td><?= ucfirst(iconv('UTF-8', 'ASCII//TRANSLIT', $row['waste_type'])) ?></td>
                <td><?= number_format($row['quantity_kg'], 1) ?></td>
                <td><?= htmlspecialchars(iconv('UTF-8', 'ASCII//TRANSLIT', $row['notes'])) ?></td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>
    <section class="charts-section">
        <div class="charts-header">Statistici grafice depozitari</div>
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-title">Cantitate pe tip de deseu</div>
                <canvas id="wasteBarChart" class="chart-canvas" width="400" height="260"></canvas>
            </div>
            <div class="chart-card">
                <div class="chart-title">Procent pe tip de deseu</div>
                <canvas id="wastePieChart" class="chart-canvas" width="400" height="260"></canvas>
            </div>
        </div>
    </section>
    <?php
    $waste_types = [];
    $waste_quantities = [];
    $query_chart = "SELECT wc.type as waste_type, SUM(wd.quantity_kg) as total_kg
        FROM waste_deposits wd
        LEFT JOIN waste_categories wc ON wd.waste_category_id = wc.id
        WHERE $period_sql
        GROUP BY wc.type
        ORDER BY wc.type";
    $result_chart = mysqli_query($connection, $query_chart);
    while ($row = mysqli_fetch_assoc($result_chart)) {
        $waste_types[] = ucfirst($row['waste_type']);
        $waste_quantities[] = (float)$row['total_kg'];
    }
    ?>
    <script>
    window.WASTE_BAR_DATA = {
        labels: <?= json_encode($waste_types) ?>,
        datasets: [{
            label: 'Cantitate (kg)',
            data: <?= json_encode($waste_quantities) ?>,
            backgroundColor: [
                '#4CAF50', '#66BB6A', '#81C784', '#388E3C', '#43A047', '#A5D6A7', '#C8E6C9', '#2E7D32'
            ],
            borderRadius: 6
        }]
    };
    window.WASTE_PIE_DATA = {
        labels: <?= json_encode($waste_types) ?>,
        datasets: [{
            data: <?= json_encode($waste_quantities) ?>,
            backgroundColor: [
                '#4CAF50', '#66BB6A', '#81C784', '#388E3C', '#43A047', '#A5D6A7', '#C8E6C9', '#2E7D32'
            ]
        }]
    };
    </script>
</main>
</body>
</html>
