<?php
// Include configurația
require_once 'config/config.php';   

// Șterge toate variabilele din sesiune
$_SESSION = array();

// Distruge cookie-ul de sesiune dacă există
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// sterge sesiunea 
session_destroy();

face o sesiune noua
session_start();
$_SESSION['success_message'] = "Te-ai delogat cu succes!";
redirect('login.php');

// de dezbatut daca sa il trimita la login sau la home :P
// mai bine la login, ca sa nu aiba acces la home daca nu e logat
?>