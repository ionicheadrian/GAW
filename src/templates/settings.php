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


$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($connection, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_info['id']);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user_data = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($user_stmt);

if (!$user_data) {
    $_SESSION['error_messages'] = ['Eroare la incarcarea datelor utilizatorului!'];
    redirect('home.php');
}

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_messages'])) {
    $errors = $_SESSION['error_messages'];
    unset($_SESSION['error_messages']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean_input($_POST['full_name'] ?? '');
    $username = clean_input($_POST['username'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $address = clean_input($_POST['address'] ?? '');
    
    if (empty($full_name)) {
        $errors[] = "Numele complet este obligatoriu!";
    } elseif (strlen($full_name) < 2) {
        $errors[] = "Numele trebuie sa aiba cel putin 2 caractere!";
    }
    
    if (empty($username)) {
        $errors[] = "Username-ul este obligatoriu!";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username-ul trebuie sa aiba cel putin 3 caractere!";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username-ul poate contine doar litere, cifre si underscore!";
    }
    
    if (empty($email)) {
        $errors[] = "Email-ul este obligatoriu!";
    } elseif (!is_valid_email($email)) {
        $errors[] = "Formatul email-ului nu este valid!";
    }
    
    if (!empty($phone) && !preg_match('/^[0-9+\-\s()]+$/', $phone)) {
        $errors[] = "Formatul telefonului nu este valid!";
    }
    
    if (empty($errors)) {
        if ($username !== $user_data['username']) {
            $check_query = "SELECT id FROM users WHERE username = ? AND id != ?";
            $check_stmt = mysqli_prepare($connection, $check_query);
            if ($check_stmt) {
                mysqli_stmt_bind_param($check_stmt, "si", $username, $user_info['id']);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_result) > 0) {
                    $errors[] = "Acest username este deja folosit de alt utilizator!";
                }
                mysqli_stmt_close($check_stmt);
            }
        }
        
        if ($email !== $user_data['email']) {
            $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $check_stmt = mysqli_prepare($connection, $check_query);
            if ($check_stmt) {
                mysqli_stmt_bind_param($check_stmt, "si", $email, $user_info['id']);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_result) > 0) {
                    $errors[] = "Acest email este deja folosit de alt utilizator!";
                }
                mysqli_stmt_close($check_stmt);
            }
        }
    }
    
    if (empty($errors)) {
        $update_query = "UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = mysqli_prepare($connection, $update_query);
        
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "sssssi", $full_name, $username, $email, $phone, $address, $user_info['id']);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_email'] = $email;
                $_SESSION['username'] = $username;
                
                $_SESSION['success_message'] = "Profilul a fost actualizat cu succes!";
                mysqli_stmt_close($update_stmt);
                redirect('settings.php');
            } else {
                $errors[] = "Eroare la actualizarea profilului: " . mysqli_stmt_error($update_stmt);
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $errors[] = "Eroare la pregatirea actualizarii profilului!";
        }
    }
    if (!empty($errors)) {
        $_SESSION['error_messages'] = $errors;
        redirect('settings.php');
    }
}

$stats = [];
$deposits_query = "SELECT COUNT(*) as count, SUM(quantity_kg) as total_kg FROM waste_deposits WHERE user_id = ?";
$deposits_stmt = mysqli_prepare($connection, $deposits_query);
mysqli_stmt_bind_param($deposits_stmt, "i", $user_info['id']);
mysqli_stmt_execute($deposits_stmt);
$deposits_result = mysqli_stmt_get_result($deposits_stmt);
$deposits_data = mysqli_fetch_assoc($deposits_result);
$stats['total_deposits'] = $deposits_data['count'];
$stats['total_kg'] = $deposits_data['total_kg'] ?? 0;
mysqli_stmt_close($deposits_stmt);

$reports_query = "SELECT COUNT(*) as count FROM reports WHERE user_id = ? AND auto_generated = FALSE";
$reports_stmt = mysqli_prepare($connection, $reports_query);
mysqli_stmt_bind_param($reports_stmt, "i", $user_info['id']);
mysqli_stmt_execute($reports_stmt);
$reports_result = mysqli_stmt_get_result($reports_stmt);
$reports_data = mysqli_fetch_assoc($reports_result);
$stats['total_reports'] = $reports_data['count'];
mysqli_stmt_close($reports_stmt);

$created_date = new DateTime($user_data['created_at']);
$now = new DateTime();
$interval = $created_date->diff($now);
if ($interval->days > 365) {
    $account_age = $interval->y . " an" . ($interval->y > 1 ? "i" : "") . " si " . $interval->m . " lun" . ($interval->m != 1 ? "i" : "a");
} elseif ($interval->days > 30) {
    $account_age = $interval->m . " lun" . ($interval->m != 1 ? "i" : "a") . " si " . $interval->d . " zile";
} else {
    $account_age = $interval->days . " zile";
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoManager - Editare Profil</title>
    <link rel="stylesheet" href="../public/css/navbar.css">
    <link rel="stylesheet" href="../public/css/settings.css">
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
                <!-- Linkuri pentru cetateni -->
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
            <h1>👤 Profilul Meu</h1>
            <p>Gestioneaza informatiile personale si vezi statisticile contului</p>
        </div>

        <div class="profile-container">
            <div class="edit-section">
                <h2>✏️ Editare Informatii</h2>
                
                <form method="POST" class="profile-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">Nume complet *</label>
                            <input type="text" id="full_name" name="full_name" required 
                                   value="<?= htmlspecialchars($user_data['full_name']) ?>"
                                   placeholder="Ex: Ion Popescu">
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" required 
                                   value="<?= htmlspecialchars($user_data['username']) ?>"
                                   placeholder="Ex: ion_popescu">
                            <small>Doar litere, cifre si underscore. Minim 3 caractere.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?= htmlspecialchars($user_data['email']) ?>"
                                   placeholder="Ex: ion@example.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Telefon</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>"
                                   placeholder="Ex: 0712345678">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Adresa</label>
                        <textarea id="address" name="address" rows="3"
                                  placeholder="Ex: Strada Copou nr. 15, Iasi"><?= htmlspecialchars($user_data['address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            💾 Salveaza Modificarile
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            🔄 Reseteaza
                        </button>
                    </div>
                </form>
            </div>

            <div class="security-section">
                <h2>🔒 Securitate Cont</h2>
                
                <div class="security-options">
                    <div class="security-item">
                        <div class="security-info">
                            <h3>🔑 Schimba Parola</h3>
                            <p>Actualizeaza parola pentru a-ti mentine contul in siguranta</p>
                            <small>Ultima modificare: 
                                <?= date('d.m.Y H:i', strtotime($user_data['updated_at'])) ?>
                            </small>
                        </div>
                        <a href="reset_password.php" class="btn btn-warning">
                            🔄 Reseteaza Parola
                        </a>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-info">
                            <h3>📧 Verificare Email</h3>
                            <p>Email-ul tau curent: <strong><?= htmlspecialchars($user_data['email']) ?></strong></p>
                            <small>Status: ✅ Verificat</small>
                        </div>
                        <button class="btn btn-secondary" disabled>
                            ✅ Verificat
                        </button>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-info">
                            <h3>🗑️ Sterge Contul</h3>
                            <p>Stergerea contului este permanenta si nu poate fi anulata</p>
                            <small>⚠️ Toate datele vor fi pierdute definitiv</small>
                        </div>
                        <button class="btn btn-danger" onclick="confirmDeleteAccount()">
                            🗑️ Sterge Contul
                        </button>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </main>

    <script src="../public/js/navbar.js"></script>
    <script src="../public/js/settings.js"></script>
</body>
</html>