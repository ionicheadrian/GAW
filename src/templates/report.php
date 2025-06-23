<?php
require_once '../config/config.php';
$user_info = get_user_info();
$is_staff = $user_info && in_array($user_info['role'], ['staff', 'admin']);


if (!is_logged_in()) {
    redirect('login.php');
}

$user_info = get_user_info();
$errors = [];
$success = '';
$old_data = [];

$locations_query = "SELECT id, name, address, latitude, longitude, capacity_menajer, capacity_hartie, capacity_plastic, 
                          current_menajer, current_hartie, current_plastic 
                   FROM locations 
                   WHERE location_type = 'collection_point' AND is_active = TRUE 
                   ORDER BY name";
$locations_result = mysqli_query($connection, $locations_query);
$locations = [];
if ($locations_result) {
    while ($row = mysqli_fetch_assoc($locations_result)) {
        $locations[] = $row;
    }
}

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_messages'])) {
    $errors = $_SESSION['error_messages'];
    unset($_SESSION['error_messages']);
}

if (isset($_SESSION['form_data'])) {
    $old_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'deposit_waste') {
        $location_id = (int)($_POST['location_id'] ?? 0);
        $waste_category = (int)($_POST['waste_category'] ?? 0);
        $quantity = floatval($_POST['quantity'] ?? 0);
        $notes = clean_input($_POST['notes'] ?? '');
        
        $old_data = $_POST;
        
        // Validari pentru depozitare
        if ($location_id <= 0) {
            $errors[] = "Va rugam sa selectati o locatie de colectare!";
        }
        
        if ($waste_category <= 0) {
            $errors[] = "Va rugam sa selectati tipul de deseuri!";
        }
        
        if ($quantity <= 0) {
            $errors[] = "Cantitatea trebuie sa fie mai mare decat 0!";
        } elseif ($quantity > 50) {
            $errors[] = "Cantitatea pare prea mare! Maximum 50kg per depozitare.";
        }
        
        if (empty($errors)) {
            $location_query = "SELECT * FROM locations WHERE id = ? AND location_type = 'collection_point' AND is_active = TRUE";
            $location_stmt = mysqli_prepare($connection, $location_query);
            
            if ($location_stmt) {
                mysqli_stmt_bind_param($location_stmt, "i", $location_id);
                mysqli_stmt_execute($location_stmt);
                $location_result = mysqli_stmt_get_result($location_stmt);
                $location = mysqli_fetch_assoc($location_result);
                mysqli_stmt_close($location_stmt);
                
                if (!$location) {
                    $errors[] = "Locatia selectata nu este valida sau nu este activa!";
                } else {
                    $category_map = [
                        1 => ['current' => 'current_menajer', 'capacity' => 'capacity_menajer', 'name' => 'menajer'],
                        2 => ['current' => 'current_hartie', 'capacity' => 'capacity_hartie', 'name' => 'hartie'],
                        3 => ['current' => 'current_plastic', 'capacity' => 'capacity_plastic', 'name' => 'plastic']
                    ];
                    
                    if (isset($category_map[$waste_category])) {
                        $current_field = $category_map[$waste_category]['current'];
                        $capacity_field = $category_map[$waste_category]['capacity'];
                        $waste_name = $category_map[$waste_category]['name'];
                        
                        $current_amount = floatval($location[$current_field]);
                        $capacity_amount = floatval($location[$capacity_field]);
                        
                        if ($capacity_amount > 0 && $current_amount >= $capacity_amount) {
                            $errors[] = "Containerul pentru deseuri de tip '$waste_name' este deja plin la aceasta locatie! Va rugam sa alegeti o alta locatie sau sa raportati problema prin tab-ul de raportare.";
                        }
                        elseif ($capacity_amount > 0 && ($current_amount + $quantity) > $capacity_amount) {
                            $available_space = $capacity_amount - $current_amount;
                            $errors[] = "Cantitatea introdusa (" . number_format($quantity, 1) . "kg) depaseste spatiul disponibil (" . number_format($available_space, 1) . "kg) pentru deseuri de tip '$waste_name' la aceasta locatie!";
                        }
                    }
                }
            } else {
                $errors[] = "Eroare la verificarea locatiei!";
            }
        }
        
        if (empty($errors)) {
            mysqli_autocommit($connection, FALSE);
            
            try {
                // 1. Inseram depozitarea in tabelul waste_deposits (AUTO-VERIFICATA)
                $deposit_query = "INSERT INTO waste_deposits (user_id, location_id, waste_category_id, quantity_kg, notes, deposit_date, verified_by, verified_at) VALUES (?, ?, ?, ?, ?, NOW(), ?, NOW())";
                $deposit_stmt = mysqli_prepare($connection, $deposit_query);
                
                if (!$deposit_stmt) {
                    throw new Exception("Eroare la pregatirea inserarii depozitarii!");
                }
                
                mysqli_stmt_bind_param($deposit_stmt, "iiidsi", $user_info['id'], $location_id, $waste_category, $quantity, $notes, $user_info['id']);
                
                if (!mysqli_stmt_execute($deposit_stmt)) {
                    throw new Exception("Eroare la inregistrarea depozitarii!");
                }
                mysqli_stmt_close($deposit_stmt);
                $category_map = [
                    1 => 'current_menajer',  // menajer
                    2 => 'current_hartie',   // hartie  
                    3 => 'current_plastic'   // plastic
                ];
                
                $column_name = $category_map[$waste_category] ?? 'current_menajer';
                
                $update_query = "UPDATE locations SET $column_name = $column_name + ? WHERE id = ?";
                $update_stmt = mysqli_prepare($connection, $update_query);
                
                if (!$update_stmt) {
                    throw new Exception("Eroare la pregatirea actualizarii locatiei!");
                }
                
                mysqli_stmt_bind_param($update_stmt, "di", $quantity, $location_id);
                
                if (!mysqli_stmt_execute($update_stmt)) {
                    throw new Exception("Eroare la actualizarea locatiei!");
                }
                mysqli_stmt_close($update_stmt);
                mysqli_commit($connection);
                mysqli_autocommit($connection, TRUE);
                
                $_SESSION['success_message'] = "Depozitarea a fost inregistrata si confirmata cu succes! Multumim pentru contributia la reciclare. 🌱";
                redirect('report.php');
                
            } catch (Exception $e) {
                mysqli_rollback($connection);
                mysqli_autocommit($connection, TRUE);
                $errors[] = $e->getMessage();
            }
        }
    }
    
    elseif ($_POST['action'] === 'report_problem') {
        $title = clean_input($_POST['title'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $location_id = (int)($_POST['location_id_problem'] ?? 0);
        $priority = $_POST['priority'] ?? 'medium';
        $old_data = $_POST;
        if (empty($title)) {
            $errors[] = "Titlul raportului este obligatoriu!";
        } elseif (strlen($title) < 5) {
            $errors[] = "Titlul trebuie sa aiba cel putin 5 caractere!";
        }
        if (empty($description)) {
            $errors[] = "Descrierea este obligatorie!";
        } elseif (strlen($description) < 10) {
            $errors[] = "Descrierea trebuie sa aiba cel putin 10 caractere!";
        }
        if ($location_id <= 0) {
            $errors[] = "Va rugam sa selectati o locatie!";
        }

        if (empty($errors)) {
            $loc = null;
            foreach ($locations as $l) {
                if ($l['id'] == $location_id) {
                    $loc = $l;
                    break;
                }
            }
            if (!$loc) {
                $errors[] = "Locatia selectata nu exista!";
            } else {
                $latitude = $loc['latitude'] ?? null;
                $longitude = $loc['longitude'] ?? null;
                $address = $loc['address'] ?? '';
                $report_query = "INSERT INTO reports (user_id, location_id, waste_category_id, title, description, latitude, longitude, status, priority, report_type, created_at) VALUES (?, ?, NULL, ?, ?, ?, ?, 'new', ?, 'problem', NOW())";
                $report_stmt = mysqli_prepare($connection, $report_query);
                if (!$report_stmt) {
                    $errors[] = "Eroare la pregatirea inserarii raportului!";
                } else {
                    mysqli_stmt_bind_param($report_stmt, "iissdds", 
                        $user_info['id'], 
                        $location_id, 
                        $title, 
                        $description, 
                        $latitude, 
                        $longitude, 
                        $priority
                    );
                    if (mysqli_stmt_execute($report_stmt)) {
                        $_SESSION['success_message'] = "Raportul problemei a fost inregistrat cu succes! Va multumim pentru sesizare.";
                        mysqli_stmt_close($report_stmt);
                        redirect('report.php');
                    } else {
                        $errors[] = "Eroare la inregistrarea raportului: " . mysqli_stmt_error($report_stmt);
                        mysqli_stmt_close($report_stmt);
                    }
                }
            }
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['error_messages'] = $errors;
        $_SESSION['form_data'] = $old_data;
        redirect('report.php');
    }
}

$categories_query = "SELECT id, type, description FROM waste_categories ORDER BY type";
$categories_result = mysqli_query($connection, $categories_query);
$categories = [];
if ($categories_result) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row;
    }
}

if (empty($categories)) {
    $insert_categories = [
        ['menajer', 'Deseuri menajere generale'],
        ['hartie', 'Hartie si carton'],
        ['plastic', 'Materiale plastice']
    ];
    
    foreach ($insert_categories as $cat) {
        $insert_query = "INSERT INTO waste_categories (type, description, created_at) VALUES (?, ?, NOW())";
        $insert_stmt = mysqli_prepare($connection, $insert_query);
        if ($insert_stmt) {
            mysqli_stmt_bind_param($insert_stmt, "ss", $cat[0], $cat[1]);
            mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
        }
    }
    
    // Reincarcam categoriile
    $categories_result = mysqli_query($connection, $categories_query);
    if ($categories_result) {
        while ($row = mysqli_fetch_assoc($categories_result)) {
            $categories[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoManager - Depozitare & Raportare</title>
    <link rel="stylesheet" href="../public/css/navbar.css">
    <link rel="stylesheet" href="../public/css/report.css">
</head>
<body>
    <nav>
        <ul class="nav-links">
            <?php if ($is_staff): ?>
                <!-- Linkuri pentru staff/admin -->
                <li><a class="pagini" href="home.php">🏠 Home</a></li>
                <li><a class="pagini" href="report.php">♻️ Depozitare</a></li>
                <li><a class="pagini" href="locations.php">🗺️ Locatii</a></li>
                <li><a class="pagini" href="simulator.php">🔬 Simulator</a></li>
                <li><a class="pagini" href="dashboard_staff.php">📊 Dashboard</a></li>
            <?php else: ?>
                <!-- Linkuri pentru cetățeni -->
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
                    <a href="settings.php">⚙️ Setări Cont</a>
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

        <div class="tab-navigation">
            <button class="tab-btn active" onclick="switchTab('deposit')">♻️ Depozitare Deseuri</button>
            <button class="tab-btn" onclick="switchTab('problem')">⚠️ Raportare Probleme</button>
        </div>

        <div class="containers-wrapper">
            <div id="deposit-tab" class="tab-content active">
                <div class="container form-container-wrapper">
                    <div class="container-header">
                        <h2>♻️ Depozitare Deseuri</h2>
                        <p>Raporteaza ca ai depus deseuri la un punct de colectare</p>
                    </div>

                    <div class="form-container">
                        <form method="POST" class="report-form">
                            <input type="hidden" name="action" value="deposit_waste">
                            
                            <div class="form-content">
                                <div class="form-group">
                                    <label for="location_id">Punct de colectare *</label>
                                    <select id="location_id" name="location_id" required onchange="updateCapacityInfo()">
                                        <option value="">Selecteaza punctul de colectare...</option>
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?= $location['id'] ?>" 
                                                    data-menajer="<?= $location['current_menajer'] ?>/<?= $location['capacity_menajer'] ?>"
                                                    data-hartie="<?= $location['current_hartie'] ?>/<?= $location['capacity_hartie'] ?>"
                                                    data-plastic="<?= $location['current_plastic'] ?>/<?= $location['capacity_plastic'] ?>"
                                                    <?= (($old_data['location_id'] ?? '') == $location['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($location['name']) ?> - <?= htmlspecialchars($location['address']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="capacity-info" class="capacity-info" style="display: none;">
                                    <h4>📊 Starea actuala:</h4>
                                    <div class="capacity-bars">
                                        <div class="capacity-item">
                                            <span class="capacity-label">🗑️ Menajer:</span>
                                            <div class="capacity-bar">
                                                <div class="capacity-fill" id="menajer-fill"></div>
                                            </div>
                                            <span class="capacity-text" id="menajer-text">0/0 kg</span>
                                        </div>
                                        <div class="capacity-item">
                                            <span class="capacity-label">📄 Hartie:</span>
                                            <div class="capacity-bar">
                                                <div class="capacity-fill" id="hartie-fill"></div>
                                            </div>
                                            <span class="capacity-text" id="hartie-text">0/0 kg</span>
                                        </div>
                                        <div class="capacity-item">
                                            <span class="capacity-label">🛍️ Plastic:</span>
                                            <div class="capacity-bar">
                                                <div class="capacity-fill" id="plastic-fill"></div>
                                            </div>
                                            <span class="capacity-text" id="plastic-text">0/0 kg</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="waste_category_deposit">Tipul deseurilor *</label>
                                        <select id="waste_category_deposit" name="waste_category" required>
                                            <option value="">Selecteaza tipul...</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>" 
                                                        <?= (($old_data['waste_category'] ?? '') == $category['id']) ? 'selected' : '' ?>>
                                                    <?= ucfirst($category['type']) ?> - <?= htmlspecialchars($category['description']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="quantity">Cantitatea (kg) *</label>
                                        <input type="number" step="0.1" min="0.1" max="50" id="quantity" name="quantity" required 
                                               value="<?= htmlspecialchars($old_data['quantity'] ?? '') ?>"
                                               placeholder="Ex: 2.5">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="notes">Observatii (opțional)</label>
                                    <textarea id="notes" name="notes" rows="2"
                                              placeholder="Orice detalii suplimentare..."><?= htmlspecialchars($old_data['notes'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="form-actions compact">
                                <button type="submit" class="btn btn-primary">
                                    ✅ Inregistreaza Depozitarea
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    🔄 Reseteaza
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="problem-tab" class="tab-content">
                <div class="container form-container-wrapper">
                    <div class="container-header">
                        <h2>⚠️ Raportare Probleme</h2>
                        <p>Raporteaza probleme specifice sau locatii unde s-a adunat gunoi</p>
                    </div>

                    <div class="form-container">
                        <form method="POST" class="report-form">
                            <input type="hidden" name="action" value="report_problem">
                            <div class="form-content">
                                <div class="form-group">
                                    <label for="title">Titlu problema *</label>
                                    <input type="text" id="title" name="title" required value="<?= htmlspecialchars($old_data['title'] ?? '') ?>" placeholder="Ex: Gunoi neridicat la Copou">
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="priority">Prioritatea</label>
                                        <select id="priority" name="priority">
                                            <option value="low" <?= (($old_data['priority'] ?? '') == 'low') ? 'selected' : '' ?>>Scazuta</option>
                                            <option value="medium" <?= (($old_data['priority'] ?? '') == 'medium') ? 'selected' : '' ?>>Medie</option>
                                            <option value="high" <?= (($old_data['priority'] ?? '') == 'high') ? 'selected' : '' ?>>Ridicata</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="location_id_problem">Locatie *</label>
                                        <select id="location_id_problem" name="location_id_problem" required>
                                            <option value="">Selecteaza locatia...</option>
                                            <?php foreach ($locations as $loc): ?>
                                                <option value="<?= $loc['id'] ?>" <?= (($old_data['location_id_problem'] ?? '') == $loc['id']) ? 'selected' : '' ?>><?= htmlspecialchars($loc['name']) ?> - <?= htmlspecialchars($loc['address']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="description">Descrierea problemei *</label>
                                    <textarea id="description" name="description" required rows="2" placeholder="Descrie detaliat problema: cantitatea aproximativa, tipul deseurilor..."><?= htmlspecialchars($old_data['description'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="form-actions compact">
                                <button type="submit" class="btn btn-primary">Trimite Raportul</button>
                                <button type="reset" class="btn btn-secondary">Reseteaza</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="container recent-container-wrapper">
                <div class="container-header">
                    <h2>📊 Activitatea Ta</h2>
                    <p>Depozitari si rapoarte recente</p>
                </div>

                <div class="recent-reports">
                    <div class="activity-tabs">
                        <button class="activity-tab-btn active" onclick="switchActivityTab('deposits')">♻️ Depozitari</button>
                        <button class="activity-tab-btn" onclick="switchActivityTab('reports')">⚠️ Rapoarte</button>
                    </div>

                    <div id="deposits-activity" class="activity-content active">
                        <div class="reports-list">
                            <?php
                            $deposits_query = "SELECT wd.*, l.name as location_name, wc.type as waste_type 
                                             FROM waste_deposits wd 
                                             LEFT JOIN locations l ON wd.location_id = l.id 
                                             LEFT JOIN waste_categories wc ON wd.waste_category_id = wc.id 
                                             WHERE wd.user_id = ? 
                                             ORDER BY wd.deposit_date DESC LIMIT 10";
                            $deposits_stmt = mysqli_prepare($connection, $deposits_query);
                            
                            if ($deposits_stmt) {
                                mysqli_stmt_bind_param($deposits_stmt, "i", $user_info['id']);
                                mysqli_stmt_execute($deposits_stmt);
                                $deposits_result = mysqli_stmt_get_result($deposits_stmt);
                                
                                if (mysqli_num_rows($deposits_result) > 0):
                                    while ($deposit = mysqli_fetch_assoc($deposits_result)):
                            ?>
                                    <div class="report-item deposit-item">
                                        <div class="report-info">
                                            <h4>♻️ Depozitare <?= ucfirst($deposit['waste_type'] ?? 'General') ?></h4>
                                            <p>
                                                <span class="location-name">📍 <?= htmlspecialchars($deposit['location_name']) ?></span>
                                                •
                                                <span class="quantity"><?= number_format($deposit['quantity_kg'], 1) ?> kg</span>
                                                •
                                                <span class="date"><?= date('d.m.Y H:i', strtotime($deposit['deposit_date'])) ?></span>
                                            </p>
                                            <?php if (!empty($deposit['notes'])): ?>
                                                <p class="notes">💬 <?= htmlspecialchars($deposit['notes']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="report-status">
                                            <span class="status status-verified">✅ Confirmat</span>
                                        </div>
                                    </div>
                            <?php
                                    endwhile;
                                else:
                            ?>
                                <div class="no-reports">
                                    <div class="no-reports-icon">♻️</div>
                                    <h3>Inca nu ai depozitari</h3>
                                    <p>Incepe sa depui deseuri la punctele de colectare pentru un mediu mai curat!</p>
                                </div>
                            <?php
                                endif;
                                mysqli_stmt_close($deposits_stmt);
                            }
                            ?>
                        </div>
                    </div>

                    <div id="reports-activity" class="activity-content">
                        <div class="reports-list">
                            <?php
                            $reports_query = "SELECT r.id, r.title, r.status, r.priority, r.created_at, r.report_type, wc.type as waste_type 
                                            FROM reports r 
                                            LEFT JOIN waste_categories wc ON r.waste_category_id = wc.id 
                                            WHERE r.user_id = ? AND r.auto_generated = FALSE 
                                            ORDER BY r.created_at DESC LIMIT 10";
                            $reports_stmt = mysqli_prepare($connection, $reports_query);
                            
                            if ($reports_stmt) {
                                mysqli_stmt_bind_param($reports_stmt, "i", $user_info['id']);
                                mysqli_stmt_execute($reports_stmt);
                                $reports_result = mysqli_stmt_get_result($reports_stmt);
                                
                                if (mysqli_num_rows($reports_result) > 0):
                                    while ($report = mysqli_fetch_assoc($reports_result)):
                            ?>
                                    <div class="report-item">
                                        <div class="report-info">
                                            <h4><?= htmlspecialchars($report['title']) ?></h4>
                                            <p>
                                                <span class="waste-type"><?= ucfirst($report['waste_type'] ?? 'General') ?></span>
                                                •
                                                <span class="priority priority-<?= $report['priority'] ?>">
                                                    <?= ucfirst($report['priority']) ?>
                                                </span>
                                                •
                                                <span class="date"><?= date('d.m.Y H:i', strtotime($report['created_at'])) ?></span>
                                            </p>
                                        </div>
                                        <div class="report-status">
                                            <span class="status status-<?= $report['status'] ?>">
                                                <?php
                                                $status_text = [
                                                    'new' => 'Nou',
                                                    'in_progress' => 'In progres',
                                                    'resolved' => 'Rezolvat'
                                                ];
                                                echo $status_text[$report['status']] ?? 'Necunoscut';
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                            <?php
                                    endwhile;
                                else:
                            ?>
                                <div class="no-reports">
                                    <div class="no-reports-icon">⚠️</div>
                                    <h3>Inca nu ai rapoarte</h3>
                                    <p>Raporteaza probleme pentru a contribui la un oras mai curat!</p>
                                </div>
                            <?php
                                endif;
                                mysqli_stmt_close($reports_stmt);
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../public/js/report.js"></script>
    <script src="../public/js/navbar.js"></script>
</body>
</html>