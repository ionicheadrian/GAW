<?php
require_once '../config/config.php';
$user_info = get_user_info();
$is_staff = $user_info && in_array($user_info['role'], ['staff', 'admin']);

if (!is_logged_in()) {
    redirect('login.php');
}

$user_info = get_user_info();
if (!in_array($user_info['role'], ['staff', 'admin'])) {
    $_SESSION['error_messages'] = ['Nu aveti permisiuni pentru a accesa aceasta pagina! Doar staff-ul si administratorii pot accesa management-ul locatiilor.'];
    redirect('report.php');
}

$errors = [];
$success = '';

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_messages'])) {
    $errors = $_SESSION['error_messages'];
    unset($_SESSION['error_messages']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'empty_containers') {
        $location_id = (int)($_POST['location_id'] ?? 0);
        $empty_types = $_POST['empty_types'] ?? [];
        
        if ($location_id > 0 && !empty($empty_types)) {
            $update_fields = [];
            $log_entries = [];
            
            if (in_array('menajer', $empty_types)) {
                $update_fields[] = "current_menajer = 0";
                $log_entries[] = "menajer";
            }
            if (in_array('hartie', $empty_types)) {
                $update_fields[] = "current_hartie = 0";
                $log_entries[] = "hartie";
            }
            if (in_array('plastic', $empty_types)) {
                $update_fields[] = "current_plastic = 0";
                $log_entries[] = "plastic";
            }
            
            if (!empty($update_fields)) {
                $update_query = "UPDATE locations SET " . implode(', ', $update_fields) . " WHERE id = ?";
                $update_stmt = mysqli_prepare($connection, $update_query);
                
                if ($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, "i", $location_id);
                    if (mysqli_stmt_execute($update_stmt)) {
                        $_SESSION['success_message'] = "Containerele au fost golite cu succes pentru tipurile: " . implode(', ', $log_entries);
                        
                        // Marcăm rapoartele automate ca rezolvate
                        $resolve_reports_query = "UPDATE reports SET status = 'resolved', resolved_at = NOW(), assigned_to = ? 
                                                WHERE location_id = ? AND report_type = 'overflow_alert' AND status != 'resolved'";
                        $resolve_stmt = mysqli_prepare($connection, $resolve_reports_query);
                        if ($resolve_stmt) {
                            mysqli_stmt_bind_param($resolve_stmt, "ii", $user_info['id'], $location_id);
                            mysqli_stmt_execute($resolve_stmt);
                            mysqli_stmt_close($resolve_stmt);
                        }
                    } else {
                        $_SESSION['error_messages'] = ["Eroare la golirea containerelor!"];
                    }
                    mysqli_stmt_close($update_stmt);
                } else {
                    $_SESSION['error_messages'] = ["Eroare la pregatirea actualizarii!"];
                }
            }
        } else {
            $_SESSION['error_messages'] = ["Date invalide pentru golirea containerelor!"];
        }
        
        redirect('staff_locations.php' . ($_GET ? '?' . http_build_query($_GET) : ''));
    }
    
    elseif ($_POST['action'] === 'update_capacity') {
        $location_id = (int)($_POST['location_id'] ?? 0);
        $new_capacity_menajer = floatval($_POST['capacity_menajer'] ?? 0);
        $new_capacity_hartie = floatval($_POST['capacity_hartie'] ?? 0);
        $new_capacity_plastic = floatval($_POST['capacity_plastic'] ?? 0);
        
        if ($location_id > 0) {
            $update_query = "UPDATE locations SET capacity_menajer = ?, capacity_hartie = ?, capacity_plastic = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($connection, $update_query);
            
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, "dddi", $new_capacity_menajer, $new_capacity_hartie, $new_capacity_plastic, $location_id);
                if (mysqli_stmt_execute($update_stmt)) {
                    $_SESSION['success_message'] = "Capacitatile au fost actualizate cu succes!";
                } else {
                    $_SESSION['error_messages'] = ["Eroare la actualizarea capacitatilor!"];
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $_SESSION['error_messages'] = ["Eroare la pregatirea actualizarii capacitatilor!"];
            }
        } else {
            $_SESSION['error_messages'] = ["Date invalide pentru actualizarea capacitatilor!"];
        }
        
        redirect('staff_locations.php' . ($_GET ? '?' . http_build_query($_GET) : ''));
    }
    
    elseif ($_POST['action'] === 'toggle_status') {
        $location_id = (int)($_POST['location_id'] ?? 0);
        $new_status = $_POST['is_active'] === '1' ? 1 : 0;
        
        if ($location_id > 0) {
            $update_query = "UPDATE locations SET is_active = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($connection, $update_query);
            
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, "ii", $new_status, $location_id);
                if (mysqli_stmt_execute($update_stmt)) {
                    $status_text = $new_status ? 'activata' : 'dezactivata';
                    $_SESSION['success_message'] = "Locatia a fost $status_text cu succes!";
                } else {
                    $_SESSION['error_messages'] = ["Eroare la schimbarea statusului locatiei!"];
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $_SESSION['error_messages'] = ["Eroare la pregatirea actualizarii statusului!"];
            }
        } else {
            $_SESSION['error_messages'] = ["Date invalide pentru schimbarea statusului!"];
        }
        
        redirect('staff_locations.php' . ($_GET ? '?' . http_build_query($_GET) : ''));
    }
}

$filter = $_GET['filter'] ?? 'all';
$search = clean_input($_GET['search'] ?? '');

$where_conditions = ["location_type = 'collection_point'"];
$params = [];
$param_types = '';

if ($filter === 'attention') {
    $where_conditions[] = "(
        (capacity_menajer > 0 AND current_menajer / capacity_menajer >= 0.8) OR
        (capacity_hartie > 0 AND current_hartie / capacity_hartie >= 0.8) OR
        (capacity_plastic > 0 AND current_plastic / capacity_plastic >= 0.8)
    )";
} elseif ($filter === 'full') {
    $where_conditions[] = "(
        (capacity_menajer > 0 AND current_menajer >= capacity_menajer) OR
        (capacity_hartie > 0 AND current_hartie >= capacity_hartie) OR
        (capacity_plastic > 0 AND current_plastic >= capacity_plastic)
    )";
} elseif ($filter === 'active') {
    $where_conditions[] = "is_active = TRUE";
} elseif ($filter === 'inactive') {
    $where_conditions[] = "is_active = FALSE";
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR address LIKE ? OR neighborhood LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

$locations_query = "SELECT l.*,
    GREATEST(
        CASE WHEN l.capacity_menajer > 0 THEN l.current_menajer / l.capacity_menajer ELSE 0 END,
        CASE WHEN l.capacity_hartie > 0 THEN l.current_hartie / l.capacity_hartie ELSE 0 END,
        CASE WHEN l.capacity_plastic > 0 THEN l.current_plastic / l.capacity_plastic ELSE 0 END
    ) as max_fill_percentage,
    (SELECT COUNT(*) FROM reports r WHERE r.location_id = l.id AND r.status IN ('new', 'in_progress')) as active_reports_count
    FROM locations l 
    $where_clause
    ORDER BY 
        CASE WHEN l.is_active = FALSE THEN 1 ELSE 0 END,
        max_fill_percentage DESC,
        l.name ASC";

$locations_stmt = mysqli_prepare($connection, $locations_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($locations_stmt, $param_types, ...$params);
}
mysqli_stmt_execute($locations_stmt);
$locations_result = mysqli_stmt_get_result($locations_stmt);
$locations = [];
while ($row = mysqli_fetch_assoc($locations_result)) {
    $locations[] = $row;
}
mysqli_stmt_close($locations_stmt);

$stats_query = "SELECT 
    COUNT(*) as total_locations,
    SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_locations,
    SUM(CASE WHEN 
        (capacity_menajer > 0 AND current_menajer >= capacity_menajer) OR
        (capacity_hartie > 0 AND current_hartie >= capacity_hartie) OR
        (capacity_plastic > 0 AND current_plastic >= capacity_plastic)
        THEN 1 ELSE 0 END) as full_locations,
    SUM(CASE WHEN 
        (capacity_menajer > 0 AND current_menajer / capacity_menajer >= 0.8) OR
        (capacity_hartie > 0 AND current_hartie / capacity_hartie >= 0.8) OR
        (capacity_plastic > 0 AND current_plastic / capacity_plastic >= 0.8)
        THEN 1 ELSE 0 END) as attention_locations
    FROM locations WHERE location_type = 'collection_point'";
$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoManager - Management Locatii Staff</title>
    <link rel="stylesheet" href="../public/css/navbar.css">
    <link rel="stylesheet" href="../public/css/staff_dashboard.css">
    <link rel="stylesheet" href="../public/css/staff_locations.css">
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
            <h1>📍 Management Locatii</h1>
            <p>Gestioneaza punctele de colectare si containerele</p>
        </div>

        <div class="quick-stats">
            <div class="quick-stat total">
                <span class="stat-number"><?= $stats['total_locations'] ?></span>
                <span class="stat-label">Total Locatii</span>
            </div>
            <div class="quick-stat active">
                <span class="stat-number"><?= $stats['active_locations'] ?></span>
                <span class="stat-label">Active</span>
            </div>
            <div class="quick-stat warning">
                <span class="stat-number"><?= $stats['attention_locations'] ?></span>
                <span class="stat-label">Necesita Atentie</span>
            </div>
            <div class="quick-stat urgent">
                <span class="stat-number"><?= $stats['full_locations'] ?></span>
                <span class="stat-label">Pline</span>
            </div>
        </div>

        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label for="filter">Filtreaza:</label>
                    <select id="filter" name="filter">
                        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Toate Locatiile</option>
                        <option value="full" <?= $filter === 'full' ? 'selected' : '' ?>>🚨 Locatii Pline</option>
                        <option value="attention" <?= $filter === 'attention' ? 'selected' : '' ?>>⚠️ Necesita Atentie (80%+)</option>
                        <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>✅ Active</option>
                        <option value="inactive" <?= $filter === 'inactive' ? 'selected' : '' ?>>❌ Dezactivate</option>
                    </select>
                </div>

                <div class="filter-group search-group">
                    <label for="search">Cautare:</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nume, adresa, cartier...">
                </div>

                <button type="submit" class="btn btn-primary">🔍 Filtreaza</button>
                <a href="staff_locations.php" class="btn btn-secondary">🔄 Reset</a>
            </form>
        </div>

        <div class="locations-container">
            <?php if (!empty($locations)): ?>
                <?php foreach ($locations as $location): ?>
                    <div class="location-card <?= !$location['is_active'] ? 'inactive' : '' ?> <?= $location['max_fill_percentage'] >= 1 ? 'full' : ($location['max_fill_percentage'] >= 0.8 ? 'warning' : 'normal') ?>">
                        <div class="location-header">
                            <div class="location-title">
                                <h3><?= htmlspecialchars($location['name']) ?></h3>
                                <div class="location-meta">
                                    <span class="location-status">
                                        <?= $location['is_active'] ? '✅ Activa' : '❌ Dezactivata' ?>
                                    </span>
                                    <span class="fill-percentage"><?= number_format($location['max_fill_percentage'] * 100, 1) ?>% umplere max</span>
                                    <?php if ($location['active_reports_count'] > 0): ?>
                                        <span class="reports-badge"><?= $location['active_reports_count'] ?> raport<?= $location['active_reports_count'] > 1 ? 'e' : '' ?> activ<?= $location['active_reports_count'] > 1 ? 'e' : '' ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="location-content">
                            <div class="location-info">
                                <p><strong>📍 Adresa:</strong> <?= htmlspecialchars($location['address']) ?></p>
                                <p><strong>🏘️ Cartier:</strong> <?= htmlspecialchars($location['neighborhood']) ?></p>
                                <p><strong>📅 Creat:</strong> <?= date('d.m.Y', strtotime($location['created_at'])) ?></p>
                            </div>

                            <div class="capacity-overview">
                                <h4>📊 Starea Containerelor</h4>
                                <div class="capacity-grid">
                                    <div class="capacity-item">
                                        <div class="capacity-header">
                                            <span class="capacity-label">🗑️ Menajer</span>
                                            <span class="capacity-text"><?= number_format($location['current_menajer'], 1) ?>/<?= number_format($location['capacity_menajer'], 1) ?>kg</span>
                                        </div>
                                        <div class="capacity-bar">
                                            <div class="capacity-fill" style="width: <?= $location['capacity_menajer'] > 0 ? min(($location['current_menajer'] / $location['capacity_menajer']) * 100, 100) : 0 ?>%"></div>
                                        </div>
                                    </div>

                                    <div class="capacity-item">
                                        <div class="capacity-header">
                                            <span class="capacity-label">📄 Hartie</span>
                                            <span class="capacity-text"><?= number_format($location['current_hartie'], 1) ?>/<?= number_format($location['capacity_hartie'], 1) ?>kg</span>
                                        </div>
                                        <div class="capacity-bar">
                                            <div class="capacity-fill" style="width: <?= $location['capacity_hartie'] > 0 ? min(($location['current_hartie'] / $location['capacity_hartie']) * 100, 100) : 0 ?>%"></div>
                                        </div>
                                    </div>

                                    <div class="capacity-item">
                                        <div class="capacity-header">
                                            <span class="capacity-label">🛍️ Plastic</span>
                                            <span class="capacity-text"><?= number_format($location['current_plastic'], 1) ?>/<?= number_format($location['capacity_plastic'], 1) ?>kg</span>
                                        </div>
                                        <div class="capacity-bar">
                                            <div class="capacity-fill" style="width: <?= $location['capacity_plastic'] > 0 ? min(($location['current_plastic'] / $location['capacity_plastic']) * 100, 100) : 0 ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="location-actions">
                            <div class="action-section">
                                <h5>🗑️ Golire Containere</h5>
                                <form method="POST" class="empty-form">
                                    <input type="hidden" name="action" value="empty_containers">
                                    <input type="hidden" name="location_id" value="<?= $location['id'] ?>">
                                    
                                    <div class="checkbox-group">
                                        <label class="checkbox-item">
                                            <input type="checkbox" name="empty_types[]" value="menajer" 
                                                   <?= $location['current_menajer'] > 0 ? '' : 'disabled' ?>>
                                            <span>🗑️ Menajer (<?= number_format($location['current_menajer'], 1) ?>kg)</span>
                                        </label>
                                        <label class="checkbox-item">
                                            <input type="checkbox" name="empty_types[]" value="hartie"
                                                   <?= $location['current_hartie'] > 0 ? '' : 'disabled' ?>>
                                            <span>📄 Hartie (<?= number_format($location['current_hartie'], 1) ?>kg)</span>
                                        </label>
                                        <label class="checkbox-item">
                                            <input type="checkbox" name="empty_types[]" value="plastic"
                                                   <?= $location['current_plastic'] > 0 ? '' : 'disabled' ?>>
                                            <span>🛍️ Plastic (<?= number_format($location['current_plastic'], 1) ?>kg)</span>
                                        </label>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-sm btn-primary" 
                                            onclick="return confirm('Confirmati golirea containerelor selectate?')">
                                        🗑️ Golește Selectate
                                    </button>
                                </form>
                            </div>

                            <div class="action-section">
                                <h5>⚙️ Actualizare Capacitati</h5>
                                <form method="POST" class="capacity-form">
                                    <input type="hidden" name="action" value="update_capacity">
                                    <input type="hidden" name="location_id" value="<?= $location['id'] ?>">
                                    
                                    <div class="capacity-inputs">
                                        <div class="input-group">
                                            <label>🗑️ Menajer (kg):</label>
                                            <input type="number" step="0.1" min="0" max="1000" 
                                                   name="capacity_menajer" value="<?= $location['capacity_menajer'] ?>">
                                        </div>
                                        <div class="input-group">
                                            <label>📄 Hartie (kg):</label>
                                            <input type="number" step="0.1" min="0" max="1000" 
                                                   name="capacity_hartie" value="<?= $location['capacity_hartie'] ?>">
                                        </div>
                                        <div class="input-group">
                                            <label>🛍️ Plastic (kg):</label>
                                            <input type="number" step="0.1" min="0" max="1000" 
                                                   name="capacity_plastic" value="<?= $location['capacity_plastic'] ?>">
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-sm btn-secondary"
                                            onclick="return confirm('Confirmati actualizarea capacitatilor?')">
                                        ⚙️ Actualizeaza
                                    </button>
                                </form>
                            </div>

                            <div class="action-section">
                                <h5><?= $location['is_active'] ? '❌ Dezactivare' : '✅ Activare' ?></h5>
                                <form method="POST" class="status-form">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="location_id" value="<?= $location['id'] ?>">
                                    <input type="hidden" name="is_active" value="<?= $location['is_active'] ? '0' : '1' ?>">
                                    
                                    <button type="submit" class="btn btn-sm <?= $location['is_active'] ? 'btn-danger' : 'btn-success' ?>"
                                            onclick="return confirm('Confirmati <?= $location['is_active'] ? 'dezactivarea' : 'activarea' ?> acestei locatii?')">
                                        <?= $location['is_active'] ? '❌ Dezactiveaza' : '✅ Activeaza' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-locations">
                    <div class="no-locations-icon">📍</div>
                    <h3>Nu sunt locatii</h3>
                    <p>Nu au fost gasite locatii cu filtrele selectate.</p>
                    <a href="staff_locations.php" class="btn btn-primary">Vezi toate locatiile</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../public/js/staff_locations.js"></script>
    <script src="../public/js/navbar.js"></script>
</body>
</html>