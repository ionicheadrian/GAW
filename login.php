<?php
require_once 'config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = $_SESSION['error_messages'] ?? [];
$success = $_SESSION['success_message'] ?? '';
$old_data = $_SESSION['form_data'] ?? [];

unset($_SESSION['error_messages'], $_SESSION['success_message'], $_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoManager - Login</title>
    <link rel="stylesheet" href="styles/login.css" type="text/css">
</head>
<body>
    <div class="container">
        <div class="welcome">
            <div class="logo">🌱</div>
            <h1>EcoManager</h1>
            <p>Sistemul tau inteligent pentru gestionarea deseurilor urbane. Contribuie la un oraa mai curat!</p>
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
                        <button type="button" class="password-toggle" onclick="togglePassword()">👁️</button>
                    </div>
                </div>
                
                <button type="submit" class="submit-button">Intră în cont</button>
                
                <div class="footer-links">
                    <p>Nu ai cont? <a href="register.php">Inregistreaza-te aici</a></p>
                    <a href="forgot_password.php">Ai uitat parola?</a>
                </div>
            </form>
        </div>
    </div>

    <script src="scripts/validatepass.js"></script>
</body>
</html>