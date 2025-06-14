<?php
// forgot_password.php - Varianta simplă

require_once 'config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$success = '';
$old_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $old_data = $_POST;
    
    // Validări de bază
    if (empty($email)) {
        $errors[] = "Email-ul este obligatoriu!";
    } elseif (!is_valid_email($email)) {
        $errors[] = "Formatul email-ului nu este valid!";
    }
    
    if (empty($new_password)) {
        $errors[] = "Noua parolă este obligatorie!";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "Parola trebuie să aibă cel puțin 8 caractere!";
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $errors[] = "Parola trebuie să conțină cel puțin o literă mare!";
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $errors[] = "Parola trebuie să conțină cel puțin o literă mică!";
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $errors[] = "Parola trebuie să conțină cel puțin un număr!";
    } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $new_password)) {
        $errors[] = "Parola trebuie să conțină cel puțin un caracter special!";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Parolele nu coincid!";
    }
    
    // Verifică dacă email-ul există și actualizează parola
    if (empty($errors)) {
        try {
            // Verifică dacă email-ul există
            $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Actualizează parola
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?");
                
                if ($stmt->execute([$password_hash, $email])) {
                    $success = "Parola a fost resetată cu succes pentru " . $user['full_name'] . "! Poți să te autentifici acum.";
                    $old_data = []; // Curăță formularul
                } else {
                    $errors[] = "Eroare la actualizarea parolei!";
                }
            } else {
                $errors[] = "Nu există niciun cont înregistrat cu acest email!";
            }
        } catch (PDOException $e) {
            $errors[] = "Eroare la resetarea parolei. Încearcă din nou!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoManager - Resetare Parolă</title>
    <link rel="stylesheet" href="styles/login.css" type="text/css">
</head>
<body>
    <div class="container">
        <div class="welcome">
            <div class="logo">🔑</div>
            <h1>Resetare Parolă</h1>
            <p>Introduce email-ul tau si o parola noua pentru a-ti reseta contul EcoManager.</p>
        </div>
        
        <div class="form-area">
            <div class="warning-box">
                <h4> Test pana la resetare cu email</h4>
            </div>
            
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
                    <p><a href="login.php">Mergi la autentificare →</a></p>
                </div>
            <?php endif; ?>
            
            <h2 class="form-title">Reseteaza Parola</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email-ul contului</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= htmlspecialchars($old_data['email'] ?? '') ?>"
                           placeholder="numele@email.com">
                </div>
                
                <div class="form-group">
                    <label for="new_password">Parola noua</label>
                    <div class="password-field">
                        <input type="password" id="new_password" name="new_password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password')">👁️</button>
                    </div>
                    
                    <!--verificare timp real parola-->
                    <div class="requirements">
                        <small>Parola trebuie să conțină:</small>
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
                            <span>O litera mică (a-z)</span>
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
                    <label for="confirm_password">Confirma parola noua</label>
                    <div class="password-field">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">👁️</button>
                    </div>
                    <div id="password-match" class="match-indicator">
                        <span id="match-text"></span>
                    </div>
                </div>
                
                <button type="submit" class="submit-button">Reseteaza Parola</button>
                
                <div class="footer-links">
                    <p><a href="login.php">← Inapoi la autentificare</a></p>
                    <p>Nu ai cont? <a href="register.php">Inregistreaza-te aici</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="scripts/validatepass.js"></script>
</body>
</html>