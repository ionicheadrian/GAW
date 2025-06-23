<?php
require_once '../config/config.php';   
if (is_logged_in()) {
    redirect('report.php');
}

$errors = [];
$old_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean_input($_POST['full_name'] ?? '');
    $username = clean_input($_POST['username'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $old_data = $_POST;
    
    //validarile
    if (empty($full_name)) {
        $errors[] = "Numele este obligatoriu!";
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
    
    if (empty($password)) {
        $errors[] = "Parola este obligatorie!";
    } elseif (strlen($password) < 8) {
        $errors[] = "Parola trebuie sa aiba cel putin 8 caractere!";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Parola trebuie sa contina cel putin o litera mare!";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Parola trebuie sa contina cel putin o litera mica!";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Parola trebuie sa contina cel putin un numar!";
    } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        $errors[] = "Parola trebuie sa contina cel putin un caracter special!";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Parolele nu coincid!";
    }
    
    //verificari daca nu avem erori
    if (empty($errors)) {
        //verificam emailul cu statementuri
        //statementurile pre definite sunt bune impotriva SQL injeciton
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($connection, $query);
        if (!$stmt) {
            $errors[] = "Eroare la verificarea datelor!";
        } else {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) > 0) {
                $errors[] = "Acest email este deja inregistrat!";
            }
            mysqli_stmt_close($stmt);
        }
        //verif pentru user
        if (empty($errors)) {
            $query = "SELECT id FROM users WHERE username = ?";
            $stmt = mysqli_prepare($connection, $query);
            if (!$stmt) {
                $errors[] = "Eroare la verificarea datelor!";
            } else {
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if (mysqli_num_rows($result) > 0) {
                    $errors[] = "Acest username este deja folosit!";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    //register pentur utilizator
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, full_name, email, password, role, created_at) VALUES (?, ?, ?, ?, 'citizen', NOW())";
        $stmt = mysqli_prepare($connection, $query);
        if (!$stmt) {
            $errors[] = "Eroare la pregatirea inregistrarii!";
        } else {
            mysqli_stmt_bind_param($stmt, "ssss", $username, $full_name, $email, $password_hash);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "Cont creat cu succes! Te poti autentifica acum.";
                mysqli_stmt_close($stmt);
                redirect('login.php');
            } else {
                $errors[] = "Eroare la crearea contului: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoManager - Inregistrare</title>
    <link rel="stylesheet" href="../public/css/register.css" type="text/css">
</head>
<body>
    <div class="container">
        <div class="welcome">
            <div class="logo">🌱</div>
            <h1>EcoManager</h1>
            <p>Alatura-te comunitatii pentru un oras mai curat! Creeaza-ti contul si incepe sa contribui la un mediu mai sanatos.</p>
        </div>
        
        <div class="form-area">
            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <h2 class="form-title">Creeaza cont nou</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="full_name">Nume complet</label>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?= htmlspecialchars($old_data['full_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required 
                           value="<?= htmlspecialchars($old_data['username'] ?? '') ?>">
                    <small>Doar litere, cifre si underscore. Minim 3 caractere.</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= htmlspecialchars($old_data['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Parola</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">👁️</button>
                    </div>
                    <!--requirementurile in timp real-->
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
                    <label for="confirm_password">Confirma parola</label>
                    <div class="password-field">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">👁️</button>
                    </div>
                    <div id="password-match" class="match-indicator">
                        <span id="match-text"></span>
                    </div>
                </div>
                
                <button type="submit" class="submit-button">Creeaza contul</button>
                
                <div class="footer-links">
                    <p>Ai deja cont? <a href="login.php">Autentifica-te aici</a></p>
                    <p><small>Prin inregistrare acceptati <a href="#">Termenii si Conditiile</a></small></p>
                </div>
            </form>
        </div>
    </div>
<script src="../public/js/login.js"></script>
    
</body>
</html>