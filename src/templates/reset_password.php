<?php
require_once '../config/config.php';
$user_info = get_user_info();
$is_staff = $user_info && in_array($user_info['role'], ['staff', 'admin']);

// Verificam daca utilizatorul este logat - MIDDLEWARE
if (!is_logged_in()) {
    $_SESSION['error_messages'] = ['Trebuie sa fiti autentificat pentru a accesa aceasta pagina!'];
    redirect('login.php');
}

$user_info = get_user_info();
$errors = [];
$success = '';

// Procesarea formularului de resetare parola
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validare parola curenta
    if (empty($current_password)) {
        $errors[] = "Parola curenta este obligatorie!";
    }
    
    // Validare parola noua
    if (empty($new_password)) {
        $errors[] = "Noua parola este obligatorie!";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "Parola trebuie sa aiba cel putin 8 caractere!";
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $errors[] = "Parola trebuie sa contina cel putin o litera mare!";
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $errors[] = "Parola trebuie sa contina cel putin o litera mica!";
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $errors[] = "Parola trebuie sa contina cel putin un numar!";
    } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $new_password)) {
        $errors[] = "Parola trebuie sa contina cel putin un caracter special!";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Parolele nu coincid!";
    }
    
    // Verificam parola curenta in baza de date
    if (empty($errors)) {
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = mysqli_prepare($connection, $query);
        
        if (!$stmt) {
            $errors[] = "Eroare la verificarea datelor!";
        } else {
            mysqli_stmt_bind_param($stmt, "i", $user_info['id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user_data = mysqli_fetch_assoc($result);
            
            if ($user_data && password_verify($current_password, $user_data['password'])) {
                // Parola curenta este corecta, actualizam cu cea noua
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                mysqli_stmt_close($stmt);
                
                $update_query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
                $update_stmt = mysqli_prepare($connection, $update_query);
                
                if (!$update_stmt) {
                    $errors[] = "Eroare la pregatirea actualizarii!";
                } else {
                    mysqli_stmt_bind_param($update_stmt, "si", $password_hash, $user_info['id']);
                    if (mysqli_stmt_execute($update_stmt)) {
                        $success = "Parola a fost schimbata cu succes!";
                        // Nu salvam old_data pentru ca nu vrem sa pastram parolele in formular
                    } else {
                        $errors[] = "Eroare la actualizarea parolei!";
                    }
                    mysqli_stmt_close($update_stmt);
                }
            } else {
                $errors[] = "Parola curenta nu este corecta!";
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoManager - Schimbare Parola</title>
    <link rel="stylesheet" href="../public/css/forgot_password.css" type="text/css">
</head>
<body>

    <div class="container">
        <div class="welcome">
            <div class="logo">🔑</div>
            <h1>Schimbare Parola</h1>
            <p>Actualizeaza-ti parola pentru a mentine contul in siguranta. Introdu parola curenta si apoi noua parola.</p>
        </div>
        
        <div class="form-area">
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
                    <p>✅ Parola ta este acum mai sigura!</p>
                </div>
            <?php endif; ?>
            
            <h2 class="form-title">Schimba Parola</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="current_password">Parola curenta *</label>
                    <div class="password-field">
                        <input type="password" id="current_password" name="current_password" required 
                               placeholder="Introduceti parola curenta">
                        <button type="button" class="password-toggle" onclick="togglePassword('current_password')">👁️</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="new_password">Parola noua *</label>
                    <div class="password-field">
                        <input type="password" id="new_password" name="new_password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password')">👁️</button>
                    </div>
                    
                    <!--verificare timp real parola-->
                    <div class="requirements">
                        <small>Parola trebuie sa contina:</small>
                        <div id="req-length" class="requirement invalid">
                            <span class="requirement-icon">✗</span>
                            <span>Minimum 8 caractere</span>
                        </div>
                        <div id="req-uppercase" class="requirement invalid">
                            <span class="requirement-icon">✗</span>
                            <span>O litera mare (A-Z)</span>
                        </div>
                        <div id="req-lowercase" class="requirement invalid">
                            <span class="requirement-icon">✗</span>
                            <span>O litera mica (a-z)</span>
                        </div>
                        <div id="req-number" class="requirement invalid">
                            <span class="requirement-icon">✗</span>
                            <span>Un numar (0-9)</span>
                        </div>
                        <div id="req-special" class="requirement invalid">
                            <span class="requirement-icon">✗</span>
                            <span>Un caracter special (!@#$%^&*)</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmare parola noua *</label>
                    <div class="password-field">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">👁️</button>
                    </div>
                    <div id="password-match" class="match-indicator">
                        <span id="match-text"></span>
                    </div>
                </div>
                
                <button type="submit" class="submit-button">🔐 Schimba Parola</button>
                
                <div class="footer-links">
                    <p><a href="settings.php">← Inapoi la Profil</a></p>
                    <p><small>💡 Recomandam sa schimbati parola periodic pentru siguranta maxima</small></p>
                </div>
            </form>
        </div>
    </div>

    <script src="../public/js/validatepass.js"></script>
</body>
</html>