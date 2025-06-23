<?php
require_once '../config/config.php';
$user_info = get_user_info();
$is_staff = $user_info && in_array($user_info['role'], ['staff', 'admin']);


if (!is_logged_in()) {
    redirect('login.php');
}

$user_info = get_user_info();
if (!in_array($user_info['role'], ['staff', 'admin'])) {
    $_SESSION['error_messages'] = ['Nu aveti permisiuni pentru a accesa aceasta pagina! Doar staff-ul si administratorii pot accesa dashboard-ul.'];
    redirect('report.php'); 
}

$stats = [];

$active_reports_query = "SELECT COUNT(*) as count FROM reports WHERE status IN ('new', 'in_progress')";
$active_reports_result = mysqli_query($connection, $active_reports_query);
$stats['active_reports'] = mysqli_fetch_assoc($active_reports_result)['count'];

$overflow_alerts_query = "SELECT COUNT(*) as count FROM reports WHERE status = 'new' AND (report_type = 'overflow_alert' OR priority = 'high')";
$overflow_alerts_result = mysqli_query($connection, $overflow_alerts_query);
$stats['overflow_alerts'] = mysqli_fetch_assoc($overflow_alerts_result)['count'];

$today_deposits_query = "SELECT COUNT(*) as count, SUM(quantity_kg) as total_kg FROM waste_deposits WHERE DATE(deposit_date) = CURDATE()";
$today_deposits_result = mysqli_query($connection, $today_deposits_query);
$today_deposits = mysqli_fetch_assoc($today_deposits_result);
$stats['today_deposits'] = $today_deposits['count'];
$stats['today_kg'] = $today_deposits['total_kg'] ?? 0;

$attention_locations_query = "SELECT COUNT(*) as count FROM locations 
    WHERE location_type = 'collection_point' AND is_active = TRUE AND (
        (capacity_menajer > 0 AND current_menajer / capacity_menajer >= 0.8) OR
        (capacity_hartie > 0 AND current_hartie / capacity_hartie >= 0.8) OR
        (capacity_plastic > 0 AND current_plastic / capacity_plastic >= 0.8)
    )";
$attention_locations_result = mysqli_query($connection, $attention_locations_query);
$stats['attention_locations'] = mysqli_fetch_assoc($attention_locations_result)['count'];

$recent_alerts_query = "SELECT r.*, l.name as location_name FROM reports r 
    LEFT JOIN locations l ON r.location_id = l.id 
    WHERE (r.report_type = 'overflow_alert' AND r.status = 'new')
       OR (r.priority = 'high' AND r.status = 'new')
    ORDER BY r.created_at DESC LIMIT 5";
$recent_alerts_result = mysqli_query($connection, $recent_alerts_query);
$recent_alerts = [];
while ($row = mysqli_fetch_assoc($recent_alerts_result)) {
    $recent_alerts[] = $row;
}

$problematic_locations_query = "SELECT 
    l.id, l.name, l.address,
    l.current_menajer, l.capacity_menajer,
    l.current_hartie, l.capacity_hartie,
    l.current_plastic, l.capacity_plastic,
    GREATEST(
        CASE WHEN l.capacity_menajer > 0 THEN l.current_menajer / l.capacity_menajer ELSE 0 END,
        CASE WHEN l.capacity_hartie > 0 THEN l.current_hartie / l.capacity_hartie ELSE 0 END,
        CASE WHEN l.capacity_plastic > 0 THEN l.current_plastic / l.capacity_plastic ELSE 0 END
    ) as max_fill_percentage
    FROM locations l 
    WHERE l.location_type = 'collection_point' AND l.is_active = TRUE
    ORDER BY max_fill_percentage DESC 
    LIMIT 8";
$problematic_locations_result = mysqli_query($connection, $problematic_locations_query);
$problematic_locations = [];
while ($row = mysqli_fetch_assoc($problematic_locations_result)) {
    $problematic_locations[] = $row;
}

$recent_problems_query = "SELECT r.*, u.full_name as reporter_name, l.name as location_name, wc.type as waste_type 
    FROM reports r 
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN locations l ON r.location_id = l.id 
    LEFT JOIN waste_categories wc ON r.waste_category_id = wc.id
    WHERE r.auto_generated = FALSE AND r.status IN ('new', 'in_progress')
    ORDER BY r.created_at DESC LIMIT 5";
$recent_problems_result = mysqli_query($connection, $recent_problems_query);
$recent_problems = [];
while ($row = mysqli_fetch_assoc($recent_problems_result)) {
    $recent_problems[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoManager - Dashboard Staff</title>
    <link rel="stylesheet" href="../public/css/navbar.css">
    <link rel="stylesheet" href="../public/css/staff_dashboard.css">
</head>
<body>
<nav>
        <ul class="nav-links">
            <?php if ($is_staff): ?>
                <li><a class="pagini" href="home.php">🏠 Home </a></li>
                <li><a class="pagini" href="dashboard_staff.php">📊 Dashboard</a></li>
                <li><a class="pagini" href="staff_reports.php">📋 Rapoartele</a></li>
                <li><a class="pagini" href="staff_locations.php">📍 Management Locatii</a></li>
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
                    <a href="logout.php" onclick="return confirm('Sigur doriti sa va delogati?')">🚪 Delogare</a>
                </div>
            </div>
        </div>
        
        <button class="mobile-menu" aria-label="Toggle navigation menu" aria-expanded="false">☰</button>
    </nav>

    <main class="main-content">
        <div class="dashboard-header">
            <h1>🛠️ Dashboard Staff</h1>
            <p>Monitorizarea si gestionarea sistemului de colectare deseuri</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card urgent">
                <div class="stat-icon">🚨</div>
                <div class="stat-content">
                    <h3><?= $stats['overflow_alerts'] ?></h3>
                    <p>Alerte Urgente</p>
                    <small>Containere pline</small>
                </div>
                <a href="staff_reports.php?filter=overflow" class="stat-action">Vezi toate</a>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">⚠️</div>
                <div class="stat-content">
                    <h3><?= $stats['attention_locations'] ?></h3>
                    <p>Locatii Atentie</p>
                    <small>Peste 80% capacitate</small>
                </div>
                <a href="staff_locations.php?filter=attention" class="stat-action">Verifică</a>
            </div>

            <div class="stat-card active">
                <div class="stat-icon">📋</div>
                <div class="stat-content">
                    <h3><?= $stats['active_reports'] ?></h3>
                    <p>Rapoarte Active</p>
                    <small>Noi si in progres</small>
                </div>
                <a href="staff_reports.php" class="stat-action">Gestionează</a>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">♻️</div>
                <div class="stat-content">
                    <h3><?= $stats['today_deposits'] ?></h3>
                    <p>Depozitari Astazi</p>
                    <small><?= number_format($stats['today_kg'], 1) ?>kg total</small>
                </div>
            </div>
        </div>

        <div class="main-grid">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>🚨 Alerte Urgente</h2>
                    <span class="badge urgent"><?= count($recent_alerts) ?></span>
                </div>
                <div class="alerts-list">
                    <?php if (!empty($recent_alerts)): ?>
                        <?php foreach ($recent_alerts as $alert): ?>
                            <div class="alert-item">
                                <div class="alert-icon">🚨</div>
                                <div class="alert-content">
                                    <h4><?= htmlspecialchars($alert['title']) ?></h4>
                                    <p>📍 <?= htmlspecialchars($alert['location_name']) ?></p>
                                    <small><?= date('d.m.Y H:i', strtotime($alert['created_at'])) ?></small>
                                </div>
                                <div class="alert-actions">
                                    <a href="staff_reports.php?id=<?= $alert['id'] ?>" class="btn btn-sm btn-primary">Detalii</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <div class="no-data-icon">✅</div>
                            <p>Nu sunt alerte urgente!</p>
                            <small>Toate containerele functioneaza normal</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>📍 Locații Prioritare</h2>
                    <a href="staff_locations.php" class="section-action">Vezi toate</a>
                </div>
                <div class="locations-grid">
                    <?php foreach ($problematic_locations as $location): ?>
                        <div class="location-card <?= $location['max_fill_percentage'] >= 1 ? 'full' : ($location['max_fill_percentage'] >= 0.8 ? 'warning' : 'normal') ?>">
                            <div class="location-header">
                                <h4><?= htmlspecialchars($location['name']) ?></h4>
                                <span class="fill-percentage"><?= number_format($location['max_fill_percentage'] * 100, 1) ?>%</span>
                            </div>
                            <div class="location-address">
                                <small><?= htmlspecialchars($location['address']) ?></small>
                            </div>
                            <div class="capacity-overview">
                                <div class="capacity-item">
                                    <span class="capacity-label">🗑️</span>
                                    <div class="capacity-bar">
                                        <div class="capacity-fill" style="width: <?= $location['capacity_menajer'] > 0 ? min(($location['current_menajer'] / $location['capacity_menajer']) * 100, 100) : 0 ?>%"></div>
                                    </div>
                                    <span class="capacity-text"><?= number_format($location['current_menajer'], 1) ?>/<?= number_format($location['capacity_menajer'], 1) ?>kg</span>
                                </div>
                                <div class="capacity-item">
                                    <span class="capacity-label">📄</span>
                                    <div class="capacity-bar">
                                        <div class="capacity-fill" style="width: <?= $location['capacity_hartie'] > 0 ? min(($location['current_hartie'] / $location['capacity_hartie']) * 100, 100) : 0 ?>%"></div>
                                    </div>
                                    <span class="capacity-text"><?= number_format($location['current_hartie'], 1) ?>/<?= number_format($location['capacity_hartie'], 1) ?>kg</span>
                                </div>
                                <div class="capacity-item">
                                    <span class="capacity-label">🛍️</span>
                                    <div class="capacity-bar">
                                        <div class="capacity-fill" style="width: <?= $location['capacity_plastic'] > 0 ? min(($location['current_plastic'] / $location['capacity_plastic']) * 100, 100) : 0 ?>%"></div>
                                    </div>
                                    <span class="capacity-text"><?= number_format($location['current_plastic'], 1) ?>/<?= number_format($location['capacity_plastic'], 1) ?>kg</span>
                                </div>
                            </div>
                            <div class="location-actions">
                                <a href="staff_locations.php?action=empty&id=<?= $location['id'] ?>" class="btn btn-xs btn-primary">Goleste</a>
                                <a href="staff_locations.php?id=<?= $location['id'] ?>" class="btn btn-xs btn-secondary">Detalii</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="dashboard-section full-width">
            <div class="section-header">
                <h2>📋 Rapoarte Recente de Probleme</h2>
                <a href="staff_reports.php?filter=problems" class="section-action">Vezi toate</a>
            </div>
            <div class="reports-table">
                <?php if (!empty($recent_problems)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Titlu</th>
                                <th>Reporter</th>
                                <th>Locatie</th>
                                <th>Prioritate</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Actiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_problems as $problem): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($problem['title']) ?></strong>
                                        <br><small><?= htmlspecialchars(substr($problem['description'], 0, 60)) ?>...</small>
                                    </td>
                                    <td><?= htmlspecialchars($problem['reporter_name']) ?></td>
                                    <td><?= htmlspecialchars($problem['location_name']) ?></td>
                                    <td>
                                        <span class="priority priority-<?= $problem['priority'] ?>">
                                            <?= ucfirst($problem['priority']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status status-<?= $problem['status'] ?>">
                                            <?php
                                            $status_text = [
                                                'new' => 'Nou',
                                                'in_progress' => 'In progres',
                                                'resolved' => 'Rezolvat'
                                            ];
                                            echo $status_text[$problem['status']] ?? 'Necunoscut';
                                            ?>
                                        </span>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($problem['created_at'])) ?></td>
                                    <td>
                                        <a href="staff_reports.php?id=<?= $problem['id'] ?>" class="btn btn-xs btn-primary">Vezi</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <div class="no-data-icon">✅</div>
                        <p>Nu sunt rapoarte active de probleme!</p>
                        <small>Toate problemele au fost rezolvate</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script src="../public/js/navbar.js"></script>
    <script src="../public/js/staff_dashboard.js"></script>
</body>
</html>