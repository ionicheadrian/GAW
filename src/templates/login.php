<?php
require_once '../config/config.php';       // !!!!
if (is_logged_in()) {//verificam daca utilizatoru este logat
    redirect('report.php');
}

//initializarea datelor
$errors = [];
$success = '';
$old_data = [];

// verificam daca in sesiune exista

//erori
if (isset($_SESSION['error_messages'])) {
    $errors = $_SESSION['error_messages'];
}

//mesaje de succes (ok login, signup de succes, reset password etc)
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
}

//date vechi din formular
if (isset($_SESSION['form_data'])) {
    $old_data = $_SESSION['form_data'];
}

//odata ce le am preluat le stergem pt ca este imp sa apara o data
// altfel utilizatoru o sa vada DOAR primul mesaj (SPER EXEMPLU)  de eroare
unset($_SESSION['error_messages'],$_SESSION['success_message'],$_SESSION['form_data']);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoManager - Login</title>
    <link rel="stylesheet" href="../public/css/login.css" type="text/css">
</head>
<body>
    
    <div class="container">
        <div class="welcome">
            <div class="logo">🌱</div>
            <h1>EcoManager</h1>
            <p>Sistemul tau inteligent pentru gestionarea deseurilor urbane. Contribuie la un oras mai curat!</p>
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
                </div>
            <?php endif; ?>
            <h2 class="form-title">Bun venit inapoi!</h2>
            <form method="POST" action="login_process.php">
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
                </div>
                <button type="submit" class="submit-button">Intra in cont</button>
                <div class="footer-links">
                    <p>Nu ai cont? <a href="register.php">Inregistreaza-te aici</a></p>
                    <a href="forgot_password.php">Ai uitat parola?</a>
                </div>
            </form>
        </div>
    </div>
    <script src="../public/js/login.js"></script>
</body>
</html>