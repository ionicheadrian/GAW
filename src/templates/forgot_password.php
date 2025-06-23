<?php
require_once '../config/config.php';      //!!!
if (is_logged_in()) { //verifiacarea sesiunii
    redirect('login.php');
}
//init
$errors = [];
$success = '';
$old_data = [];

//data checking sa verificam daca datele sunt nule sau nu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = '';
    if (isset($_POST['email'])) {
        $email = clean_input($_POST['email']);
    }

    $new_password = '';
    if (isset($_POST['new_password'])) {
        $new_password = $_POST['new_password'];
    }

    $confirm_password = '';
    if (isset($_POST['confirm_password'])) {
        $confirm_password = $_POST['confirm_password'];
    }

    $old_data = $_POST;
    
    //verificam daca exista datele de baza 
    // gen email sau daca (formatul email ului este bun)
    if (empty($email)) {
        $errors[] = "Email-ul este obligatoriu!";
    } elseif (!is_valid_email($email)) {
        $errors[] = "Formatul email-ului nu este valid!";
    }
    
    //human error/ error handlin enorm de mult
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
    
    // verificam daca exista sau nu emailul in bd
    if (empty($errors)) {
        //incepem cu queryul pentru verificarea emailului dupa id si fullname
        // e statement pt ca ne protejeaza de sql injections
        $query = "SELECT id, full_name FROM users WHERE email = ?";
        $stmt = mysqli_prepare($connection, $query); 
        if (!$stmt) {
            $errors[] = "Eroare la verificarea datelor!";
        } else {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            
            if ($user) {
                //actualizam parola in caz ca exista user cu acel email 
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                mysqli_stmt_close($stmt);
                //again sa nu avem sql injections nici in parola
                $query = "UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?";
                $stmt = mysqli_prepare($connection, $query);
                if (!$stmt) {
                    $errors[] = "Eroare la pregatirea actualizarii!";
                } else {
                    mysqli_stmt_bind_param($stmt, "ss", $password_hash, $email);
                    if (mysqli_stmt_execute($stmt)) {
                        $success = "Parola a fost resetata cu succes pentru " . $user['full_name'] . "! Poti sa te autentifici acum.";
                        $old_data = [];//curatam formularu
                    } else {
                        $errors[] = "Eroare la actualizarea parolei!";
                    }
                    mysqli_stmt_close($stmt);
                }
            } else { //navem useri cu acest email
                $errors[] = "Nu exista niciun cont inregistrat cu acest email!";
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
    <title>EcoManager - Resetare Parola</title>
    <link rel="stylesheet" href="../public/css/forgot_password.css" type="text/css">
</head>
<body>
    <script src="...scripts/validatepass.js"></script>
    <div class="container">
        <div class="welcome">
            <div class="logo">🔑</div>
            <h1>Resetare Parola</h1>
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
</body>
</html>