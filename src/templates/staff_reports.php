<?php
require_once '../config/config.php';
$user_info = get_user_info();
$is_staff = $user_info && in_array($user_info['role'], ['staff', 'admin']);

if (!is_logged_in()) {
    redirect('login.php');
}

$user_info = get_user_info();
if (!in_array($user_info['role'], ['staff', 'admin'])) {
    $_SESSION['error_messages'] = ['Nu aveti permisiuni pentru a accesa aceasta pagina! Doar staff-ul si administratorii pot accesa gestionarea rapoartelor.'];
    redirect('report.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $report_id = (int)($_POST['report_id'] ?? 0);
        $new_status = $_POST['status'] ?? '';
        $assigned_to = $_POST['assigned_to'] ?? null;
        $notes = clean_input($_POST['notes'] ?? '');
        
        if ($report_id > 0 && in_array($new_status, ['new', 'in_progress', 'resolved'])) {
            $update_query = "UPDATE reports SET status = ?, assigned_to = ?, updated_at = NOW() WHERE id = ?";
            
            if ($new_status === 'resolved') {
                $update_query = "UPDATE reports SET status = ?, assigned_to = ?, resolved_at = NOW(), updated_at = NOW() WHERE id = ?";
            }
            
            $update_stmt = mysqli_prepare($connection, $update_query);
            if ($update_stmt) {
                $assigned_to_param = !empty($assigned_to) ? (int)$assigned_to : null;
                mysqli_stmt_bind_param($update_stmt, "sii", $new_status, $assigned_to_param, $report_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $success = "Raportul a fost actualizat cu succes!";
                } else {
                    $errors[] = "Eroare la actualizarea raportului!";
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $errors[] = "Eroare la pregatirea actualizarii!";
            }
        } else {
            $errors[] = "Date invalide pentru actualizare!";
        }
    }
}

$filter = $_GET['filter'] ?? 'all';
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$type_filter = $_GET['type'] ?? '';
$search = clean_input($_GET['search'] ?? '');

$where_conditions = [];
$params = [];
$param_types = '';

if ($filter === 'overflow') {
    $where_conditions[] = "r.report_type = 'overflow_alert'";
} elseif ($filter === 'problems') {
    $where_conditions[] = "r.auto_generated = FALSE";
} elseif ($filter === 'my') {
    $where_conditions[] = "r.assigned_to = ?";
    $params[] = $user_info['id'];
    $param_types .= 'i';
}

if (!empty($status_filter)) {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($priority_filter)) {
    $where_conditions[] = "r.priority = ?";
    $params[] = $priority_filter;
    $param_types .= 's';
}

if (!empty($type_filter)) {
    $where_conditions[] = "r.report_type = ?";
    $params[] = $type_filter;
    $param_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(r.title LIKE ? OR r.description LIKE ? OR l.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$reports_query = "SELECT r.*, 
    u.full_name as reporter_name, 
    l.name as location_name, 
    l.address as location_address,
    wc.type as waste_type,
    staff.full_name as assigned_name
    FROM reports r 
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN locations l ON r.location_id = l.id 
    LEFT JOIN waste_categories wc ON r.waste_category_id = wc.id
    LEFT JOIN users staff ON r.assigned_to = staff.id
    $where_clause
    ORDER BY 
        CASE WHEN r.status = 'new' THEN 1 
             WHEN r.status = 'in_progress' THEN 2 
             ELSE 3 END,
        r.priority = 'high' DESC,
        r.priority = 'medium' DESC,
        r.created_at DESC";

$reports_stmt = mysqli_prepare($connection, $reports_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($reports_stmt, $param_types, ...$params);
}
mysqli_stmt_execute($reports_stmt);
$reports_result = mysqli_stmt_get_result($reports_stmt);
$reports = [];
while ($row = mysqli_fetch_assoc($reports_result)) {
    $reports[] = $row;
}
mysqli_stmt_close($reports_stmt);

// Lista staff pentru assignment
$staff_query = "SELECT id, full_name FROM users WHERE role IN ('staff', 'admin') ORDER BY full_name";
$staff_result = mysqli_query($connection, $staff_query);
$staff_members = [];
while ($row = mysqli_fetch_assoc($staff_result)) {
    $staff_members[] = $row;
}

$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as progress_count,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
    SUM(CASE WHEN report_type = 'overflow_alert' AND status = 'new' THEN 1 ELSE 0 END) as urgent_count
    FROM reports";
$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoManager - Gestionare Rapoarte Staff</title>
    <link rel="stylesheet" href="../public/css/navbar.css">
    <link rel="stylesheet" href="../public/css/staff_dashboard.css">
    <link rel="stylesheet" href="../public/css/staff_reports.css">
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
        <?php if (!empty($errors)): ?>
            <div class="message error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="message success">
                <p><?= htmlspecialchars($success) ?></p>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1>📋 Gestionare Rapoarte</h1>
            <p>Monitorizeaza si gestioneaza toate rapoartele din sistem</p>
        </div>

        <div class="quick-stats">
            <div class="quick-stat urgent">
                <span class="stat-number"><?= $stats['urgent_count'] ?></span>
                <span class="stat-label">Urgente</span>
            </div>
            <div class="quick-stat new">
                <span class="stat-number"><?= $stats['new_count'] ?></span>
                <span class="stat-label">Noi</span>
            </div>
            <div class="quick-stat progress">
                <span class="stat-number"><?= $stats['progress_count'] ?></span>
                <span class="stat-label">In Progres</span>
            </div>
            <div class="quick-stat resolved">
                <span class="stat-number"><?= $stats['resolved_count'] ?></span>
                <span class="stat-label">Rezolvate</span>
            </div>
            <div class="quick-stat total">
                <span class="stat-number"><?= $stats['total'] ?></span>
                <span class="stat-label">Total</span>
            </div>
        </div>

        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label for="filter">Tip Rapoarte:</label>
                    <select id="filter" name="filter">
                        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Toate</option>
                        <option value="overflow" <?= $filter === 'overflow' ? 'selected' : '' ?>>🚨 Alerte Overflow</option>
                        <option value="problems" <?= $filter === 'problems' ? 'selected' : '' ?>>⚠️ Probleme Raportate</option>
                        <option value="my" <?= $filter === 'my' ? 'selected' : '' ?>>📝 Atribuite Mie</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="">Toate</option>
                        <option value="new" <?= $status_filter === 'new' ? 'selected' : '' ?>>Nou</option>
                        <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>In Progres</option>
                        <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Rezolvat</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="priority">Prioritate:</label>
                    <select id="priority" name="priority">
                        <option value="">Toate</option>
                        <option value="high" <?= $priority_filter === 'high' ? 'selected' : '' ?>>Ridicata</option>
                        <option value="medium" <?= $priority_filter === 'medium' ? 'selected' : '' ?>>Medie</option>
                        <option value="low" <?= $priority_filter === 'low' ? 'selected' : '' ?>>Scazuta</option>
                    </select>
                </div>

                <div class="filter-group search-group">
                    <label for="search">Cautare:</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Titlu, descriere, locatie...">
                </div>

                <button type="submit" class="btn btn-primary">🔍 Filtreaza</button>
                <a href="staff_reports.php" class="btn btn-secondary">🔄 Reset</a>
            </form>
        </div>

        <div class="reports-container">
            <?php if (!empty($reports)): ?>
                <?php foreach ($reports as $report): ?>
                    <div class="report-card <?= $report['status'] ?> <?= $report['priority'] ?> <?= $report['report_type'] === 'overflow_alert' ? 'urgent-alert' : '' ?>">
                        <div class="report-header">
                            <div class="report-title">
                                <h3><?= htmlspecialchars($report['title']) ?></h3>
                                <div class="report-meta">
                                    <span class="report-type">
                                        <?= $report['report_type'] === 'overflow_alert' ? '🚨 Alert Automat' : '👤 Raport Manual' ?>
                                    </span>
                                    <span class="report-date"><?= date('d.m.Y H:i', strtotime($report['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="report-badges">
                                <span class="priority priority-<?= $report['priority'] ?>"><?= ucfirst($report['priority']) ?></span>
                                <span class="status status-<?= $report['status'] ?>">
                                    <?php
                                    $status_text = [
                                        'new' => 'Nou',
                                        'in_progress' => 'In Progres',
                                        'resolved' => 'Rezolvat'
                                    ];
                                    echo $status_text[$report['status']] ?? 'Necunoscut';
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div class="report-content">
                            <div class="report-description">
                                <p><?= htmlspecialchars($report['description']) ?></p>
                            </div>
                            
                            <div class="report-details">
                                <?php if ($report['reporter_name']): ?>
                                    <div class="detail-item">
                                        <strong>Reporter:</strong> <?= htmlspecialchars($report['reporter_name']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="detail-item">
                                    <strong>Locatie:</strong> <?= htmlspecialchars($report['location_name']) ?>
                                </div>
                                
                                <?php if ($report['waste_type']): ?>
                                    <div class="detail-item">
                                        <strong>Tip Deseuri:</strong> <?= ucfirst($report['waste_type']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($report['assigned_name']): ?>
                                    <div class="detail-item">
                                        <strong>Atribuit:</strong> <?= htmlspecialchars($report['assigned_name']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($report['resolved_at']): ?>
                                    <div class="detail-item">
                                        <strong>Rezolvat la:</strong> <?= date('d.m.Y H:i', strtotime($report['resolved_at'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($report['status'] !== 'resolved'): ?>
                            <div class="report-actions">
                                <form method="POST" class="update-form">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                    
                                    <div class="action-group">
                                        <select name="status" required>
                                            <option value="">Schimba Status</option>
                                            <option value="new" <?= $report['status'] === 'new' ? 'selected' : '' ?>>Nou</option>
                                            <option value="in_progress" <?= $report['status'] === 'in_progress' ? 'selected' : '' ?>>In Progres</option>
                                            <option value="resolved">Rezolvat</option>
                                        </select>
                                        
                                        <select name="assigned_to">
                                            <option value="">Atribuie Staff</option>
                                            <?php foreach ($staff_members as $staff): ?>
                                                <option value="<?= $staff['id'] ?>" 
                                                        <?= $report['assigned_to'] == $staff['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($staff['full_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        
                                        <button type="submit" class="btn btn-sm btn-primary">Actualizeaza</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-reports">
                    <div class="no-reports-icon">📋</div>
                    <h3>Nu sunt rapoarte</h3>
                    <p>Nu au fost gasite rapoarte cu filtrele selectate.</p>
                    <a href="staff_reports.php" class="btn btn-primary">Vezi toate rapoartele</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="../public/js/navbar.js"></script>
    <script src="../public/js/staff_reports.js"></script>
</body>
</html>