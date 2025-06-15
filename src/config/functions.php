<?php
//tools 

//clear la input
function clean_input($data) {
    $data = trim($data);           
    $data = stripslashes($data);   
    $data = htmlspecialchars($data);
    return $data;
}

//verificarea emailul valid (test@test.com)
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function safe_escape($connection, $string) {
    return mysqli_real_escape_string($connection, $string);
}
//functie de verificarea statului sessiunii unui utilizator
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}
// info despre utiliator din sesiune gen id username full name email si role 
function get_user_info() {
    if (!is_logged_in()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'username' => $_SESSION['username'] ?? null
    ];
}

//functie pentru roluri
function has_role($required_role) {
    $user = get_user_info();
    return $user && $user['role'] === $required_role;
}
//functie pentru navigare de inceput
function redirect($url) {
    header("Location: $url");
    exit();
}
?>