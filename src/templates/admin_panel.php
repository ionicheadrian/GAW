<?php
require_once '../config/config.php';
$user_info = get_user_info();
$is_staff = $user_info && in_array($user_info['role'], ['staff', 'admin']);

if (!is_logged_in()) {
    redirect('login.php');
}

$user_info = get_user_info();
if ($user_info['role'] !== 'admin') {
    $_SESSION['error_messages'] = ['Accesul interzis! Doar administratorii pot accesa acest panel.'];
    redirect('dashboard_staff.php');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['ajax_action'] === 'update_user_role') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $new_role = $_POST['new_role'] ?? '';
        
        if ($user_id > 0 && in_array($new_role, ['citizen', 'staff', 'admin'])) {
            if ($user_id == $user_info['id']) {
                echo json_encode(['success' => false, 'message' => 'Nu poti sa iti schimbi propriul rol!']);
                exit;
            }
            
            $update_query = "UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?";
            $update_stmt = mysqli_prepare($connection, $update_query);
            
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, "si", $new_role, $user_id);
                if (mysqli_stmt_execute($update_stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Rolul utilizatorului a fost actualizat cu succes!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Eroare la actualizarea rolului!']);
                }
                mysqli_stmt_close($update_stmt);
            } else {
                echo json_encode(['success' => false, 'message' => 'Eroare la pregatirea actualizarii!']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Date invalide!']);
        }
        exit;
    }
    
    if ($_POST['ajax_action'] === 'delete_user') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        if ($user_id > 0) {
            if ($user_id == $user_info['id']) {
                echo json_encode(['success' => false, 'message' => 'Nu poti sa iti stergi propriul cont!']);
                exit;
            }
            
            $check_query = "SELECT 
                (SELECT COUNT(*) FROM reports WHERE user_id = ?) as reports_count,
                (SELECT COUNT(*) FROM waste_deposits WHERE user_id = ?) as deposits_count";
            $check_stmt = mysqli_prepare($connection, $check_query);
            
            if ($check_stmt) {
                mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $user_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                $counts = mysqli_fetch_assoc($check_result);
                mysqli_stmt_close($check_stmt);
                
                if ($counts['reports_count'] > 0 || $counts['deposits_count'] > 0) {
                    echo json_encode(['success' => false, 'message' => 'Nu se poate sterge utilizatorul! Are rapoarte sau depozitari asociate.']);
                    exit;
                }
                
                $delete_query = "DELETE FROM users WHERE id = ?";
                $delete_stmt = mysqli_prepare($connection, $delete_query);
                
                if ($delete_stmt) {
                    mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
                    if (mysqli_stmt_execute($delete_stmt)) {
                        echo json_encode(['success' => true, 'message' => 'Utilizatorul a fost sters cu succes!']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Eroare la stergerea utilizatorului!']);
                    }
                    mysqli_stmt_close($delete_stmt);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Eroare la pregatirea stergerii!']);
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID utilizator invalid!']);
        }
        exit;
    }
    
    if ($_POST['ajax_action'] === 'add_location') {
        $name = clean_input($_POST['name'] ?? '');
        $address = clean_input($_POST['address'] ?? '');
        $neighborhood = clean_input($_POST['neighborhood'] ?? '');
        $latitude = floatval($_POST['latitude'] ?? 0);
        $longitude = floatval($_POST['longitude'] ?? 0);
        $capacity_menajer = floatval($_POST['capacity_menajer'] ?? 0);
        $capacity_hartie = floatval($_POST['capacity_hartie'] ?? 0);
        $capacity_plastic = floatval($_POST['capacity_plastic'] ?? 0);
        
        if (empty($name) || empty($address) || $latitude == 0 || $longitude == 0) {
            echo json_encode(['success' => false, 'message' => 'Toate campurile obligatorii trebuie completate!']);
            exit;
        }
        
        $insert_query = "INSERT INTO locations (name, address, neighborhood, latitude, longitude, capacity_menajer, capacity_hartie, capacity_plastic, location_type, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'collection_point', TRUE, NOW())";
        $insert_stmt = mysqli_prepare($connection, $insert_query);
        
        if ($insert_stmt) {
            mysqli_stmt_bind_param($insert_stmt, "sssddddd", $name, $address, $neighborhood, $latitude, $longitude, $capacity_menajer, $capacity_hartie, $capacity_plastic);
            if (mysqli_stmt_execute($insert_stmt)) {
                echo json_encode(['success' => true, 'message' => 'Punctul de colectare a fost adaugat cu succes!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Eroare la adaugarea punctului de colectare!']);
            }
            mysqli_stmt_close($insert_stmt);
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la pregatirea inserarii!']);
        }
        exit;
    }
    
    if ($_POST['ajax_action'] === 'delete_location') {
        $location_id = (int)($_POST['location_id'] ?? 0);
        
        if ($location_id > 0) {
            $check_query = "SELECT 
                (SELECT COUNT(*) FROM waste_deposits WHERE location_id = ?) as deposits_count,
                (SELECT COUNT(*) FROM reports WHERE location_id = ? AND status != 'resolved') as active_reports_count";
            $check_stmt = mysqli_prepare($connection, $check_query);
            
            if ($check_stmt) {
                mysqli_stmt_bind_param($check_stmt, "ii", $location_id, $location_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                $counts = mysqli_fetch_assoc($check_result);
                mysqli_stmt_close($check_stmt);
                
                if ($counts['deposits_count'] > 0 || $counts['active_reports_count'] > 0) {
                    echo json_encode(['success' => false, 'message' => 'Nu se poate sterge locatia! Are depozitari sau rapoarte active asociate.']);
                    exit;
                }
                
                $delete_query = "DELETE FROM locations WHERE id = ?";
                $delete_stmt = mysqli_prepare($connection, $delete_query);
                
                if ($delete_stmt) {
                    mysqli_stmt_bind_param($delete_stmt, "i", $location_id);
                    if (mysqli_stmt_execute($delete_stmt)) {
                        echo json_encode(['success' => true, 'message' => 'Locatia a fost stearsa cu succes!']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Eroare la stergerea locatiei!']);
                    }
                    mysqli_stmt_close($delete_stmt);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Eroare la pregatirea stergerii!']);
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID locatie invalid!']);
        }
        exit;
    }
    
    if ($_POST['ajax_action'] === 'edit_location') {
        $location_id = (int)($_POST['location_id'] ?? 0);
        $name = clean_input($_POST['name'] ?? '');
        $address = clean_input($_POST['address'] ?? '');
        $neighborhood = clean_input($_POST['neighborhood'] ?? '');
        $latitude = floatval($_POST['latitude'] ?? 0);
        $longitude = floatval($_POST['longitude'] ?? 0);
        $capacity_menajer = floatval($_POST['capacity_menajer'] ?? 0);
        $capacity_hartie = floatval($_POST['capacity_hartie'] ?? 0);
        $capacity_plastic = floatval($_POST['capacity_plastic'] ?? 0);
        if ($location_id > 0 && !empty($name) && !empty($address) && $latitude != 0 && $longitude != 0) {
            $update_query = "UPDATE locations SET name=?, address=?, neighborhood=?, latitude=?, longitude=?, capacity_menajer=?, capacity_hartie=?, capacity_plastic=?, updated_at=NOW() WHERE id=?";
            $update_stmt = mysqli_prepare($connection, $update_query);
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, "sssdddddi", $name, $address, $neighborhood, $latitude, $longitude, $capacity_menajer, $capacity_hartie, $capacity_plastic, $location_id);
                if (mysqli_stmt_execute($update_stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Locatia a fost actualizata cu succes!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Eroare la actualizarea locatiei!']);
                }
                mysqli_stmt_close($update_stmt);
            } else {
                echo json_encode(['success' => false, 'message' => 'Eroare la pregatirea actualizarii!']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Date invalide pentru actualizare!']);
        }
        exit;
    }
}

$stats_query = "SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM users WHERE role = 'citizen') as citizens_count,
    (SELECT COUNT(*) FROM users WHERE role = 'staff') as staff_count,
    (SELECT COUNT(*) FROM users WHERE role = 'admin') as admin_count,
    (SELECT COUNT(*) FROM locations WHERE location_type = 'collection_point') as total_locations,
    (SELECT COUNT(*) FROM locations WHERE location_type = 'collection_point' AND is_active = TRUE) as active_locations,
    (SELECT COUNT(*) FROM reports WHERE status = 'new') as pending_reports,
    (SELECT COUNT(*) FROM waste_deposits WHERE DATE(deposit_date) = CURDATE()) as today_deposits";
$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

$recent_users_query = "SELECT id, username, full_name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 10";
$recent_users_result = mysqli_query($connection, $recent_users_query);
$recent_users = [];
while ($row = mysqli_fetch_assoc($recent_users_result)) {
    $recent_users[] = $row;
}

$locations_query = "SELECT * FROM locations WHERE location_type = 'collection_point' ORDER BY name";
$locations_result = mysqli_query($connection, $locations_query);
$locations = [];
while ($row = mysqli_fetch_assoc($locations_result)) {
    $locations[] = $row;
}

$users_query = "SELECT id, username, full_name, email, role, created_at FROM users ORDER BY created_at DESC";
$users_result = mysqli_query($connection, $users_query);
$all_users = [];
while ($row = mysqli_fetch_assoc($users_result)) {
    $all_users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoManager - Panel Administrare</title>
    <link rel="stylesheet" href="../public/css/navbar.css">
    <link rel="stylesheet" href="../public/css/staff_dashboard.css">
    <link rel="stylesheet" href="../public/css/admin_panel.css">
</head>
<body>
    <nav>
        <ul class="nav-links">
            <?php if ($is_staff): ?>
                <!-- Linkuri staff/admin -->
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
            
            <!-- Dropdown meniu -->
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
        
        <!--mobil -->
        <button class="mobile-menu" aria-label="Toggle navigation menu" aria-expanded="false">☰</button>
    </nav>

    <main class="main-content">
        <!-- msj -->
        <div id="messages-container">
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
        </div>

        <div class="dashboard-header">
            <h1>🔧 Panel de Administrare</h1>
            <p>Gestioneaza utilizatori, locatii si configurari de sistem</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card users">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3><?= $stats['total_users'] ?></h3>
                    <p>Total Utilizatori</p>
                    <small><?= $stats['citizens_count'] ?> cetateni, <?= $stats['staff_count'] ?> staff, <?= $stats['admin_count'] ?> admin</small>
                </div>
            </div>

            <div class="stat-card locations">
                <div class="stat-icon">📍</div>
                <div class="stat-content">
                    <h3><?= $stats['total_locations'] ?></h3>
                    <p>Puncte Colectare</p>
                    <small><?= $stats['active_locations'] ?> active</small>
                </div>
            </div>
        </div>

        <div class="admin-tabs">
            <button class="admin-tab-btn active" onclick="switchAdminTab('users')">👥 Utilizatori</button>
            <button class="admin-tab-btn" onclick="switchAdminTab('locations')">📍 Locatii</button>
        </div>

        <div id="users-tab" class="admin-tab-content active">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>👥 Gestionare Utilizatori</h2>
                    <span class="badge"><?= count($all_users) ?> total</span>
                </div>
                
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Utilizator</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Inregistrat</th>
                                <th>Actiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $user): ?>
                                <tr data-user-id="<?= $user['id'] ?>">
                                    <td><?= $user['id'] ?></td>
                                    <td>
                                        <div class="user-info">
                                            <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                                            <small>@<?= htmlspecialchars($user['username']) ?></small>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <select class="role-select" data-user-id="<?= $user['id'] ?>" 
                                                <?= $user['id'] == $user_info['id'] ? 'disabled' : '' ?> >
                                            <option value="citizen" <?= $user['role'] === 'citizen' ? 'selected' : '' ?>>Cetatean</option>
                                            <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($user['id'] != $user_info['id']): ?>
                                                <button class="btn btn-xs btn-danger delete-user-btn" 
                                                        data-user-id="<?= $user['id'] ?>"
                                                        data-user-name="<?= htmlspecialchars($user['full_name']) ?>">
                                                    🗑️ Sterge
                                                </button>
                                            <?php else: ?>
                                                <span class="current-user-label">Tu</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Locations -->
        <div id="locations-tab" class="admin-tab-content">
            <div class="locations-flex">
                <div class="card location-form-card">
                    <div class="card-header">
                        <span class="card-icon">➕</span>
                        <div>
                            <h3>Adauga Punct de Colectare</h3>
                            <p>Completeaza datele pentru un nou punct de colectare.</p>
                        </div>
                    </div>
                    <form id="addLocationForm" class="location-form card-body">
                        <div class="form-group">
                            <label for="location_name">Nume Punct *</label>
                            <input type="text" id="location_name" name="name" required placeholder="Ex: Punct Colectare Copou">
                        </div>
                        <div class="form-group">
                            <label for="location_neighborhood">Cartier</label>
                            <input type="text" id="location_neighborhood" name="neighborhood" placeholder="Ex: Copou">
                        </div>
                        <div class="form-group">
                            <label for="location_address">Adresa *</label>
                            <input type="text" id="location_address" name="address" required placeholder="Ex: Strada Copou nr. 15">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="location_latitude">Latitudine *</label>
                                <input type="number" step="any" id="location_latitude" name="latitude" required placeholder="47.1585">
                            </div>
                            <div class="form-group">
                                <label for="location_longitude">Longitudine *</label>
                                <input type="number" step="any" id="location_longitude" name="longitude" required placeholder="27.6014">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Capacitati (kg)</label>
                            <div class="capacity-inputs">
                                <div class="capacity-input-group">
                                    <label for="capacity_menajer">🗑️ Menajer</label>
                                    <input type="number" step="0.1" min="0" id="capacity_menajer" name="capacity_menajer" value="50">
                                </div>
                                <div class="capacity-input-group">
                                    <label for="capacity_hartie">📄 Hartie</label>
                                    <input type="number" step="0.1" min="0" id="capacity_hartie" name="capacity_hartie" value="30">
                                </div>
                                <div class="capacity-input-group">
                                    <label for="capacity_plastic">🛍️ Plastic</label>
                                    <input type="number" step="0.1" min="0" id="capacity_plastic" name="capacity_plastic" value="20">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">➕ Adauga Punct</button>
                    </form>
                </div>



                
                <div class="card location-list-card">
                    <div class="card-header">
                        <span class="card-icon">📍</span>
                        <div>
                            <h3>Puncte de Colectare Existente</h3>
                            <span class="badge"><?= count($locations) ?> total</span>
                        </div>
                    </div>
                    <div class="location-list card-body">
                        <?php foreach ($locations as $location): ?>
                        <div class="location-row" data-location-id="<?= $location['id'] ?>">
                            <div class="location-row-info">
                                <div class="location-title"><strong><?= htmlspecialchars($location['name']) ?></strong></div>
                                <div class="location-meta">
                                    <span><?= htmlspecialchars($location['address']) ?></span>
                                    <span><?= htmlspecialchars($location['neighborhood']) ?></span>
                                </div>
                                <div class="location-coords">
                                    <span>Lat: <?= htmlspecialchars($location['latitude']) ?></span>
                                    <span>Lng: <?= htmlspecialchars($location['longitude']) ?></span>
                                </div>
                                <div class="location-capacity">
                                    <span>🗑️ <?= number_format($location['capacity_menajer'], 1) ?> kg</span>
                                    <span>📄 <?= number_format($location['capacity_hartie'], 1) ?> kg</span>
                                    <span>🛍️ <?= number_format($location['capacity_plastic'], 1) ?> kg</span>
                                </div>
                            </div>
                            <button class="btn btn-xs btn-danger delete-location-btn" data-location-id="<?= $location['id'] ?>" data-location-name="<?= htmlspecialchars($location['name']) ?>">🗑️ Elimina</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../public/js/navbar.js"></script>
    <script src="../public/js/admin_panel.js"></script>
</body>
</html>