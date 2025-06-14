<?php
//pornim sesiunea 
session_start();
$host = 'localhost';        
$dbname = 'gwa';           
$username = 'root';         
$password = '';             

//ne conectam la baza de date
$connection = mysqli_connect($host, $username, $password, $dbname);
if (!$connection)
    die("Eroare la conectarea la baza de date: " . mysqli_connect_error());
mysqli_set_charset($connection, "utf8");//setam caracterele cu diacricitce
//
// FUNCTII UTILE PENTRU LOGIN REGISTER FORGOT ETC 
//

function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}
function redirect($url) {
    header("Location: $url");
    exit();
}
function safe_escape($connection, $string) {
    return mysqli_real_escape_string($connection, $string);
}
?>