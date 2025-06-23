<?php
require_once '../config/config.php';   
if ($_SERVER['REQUEST_METHOD'] !== 'POST') 
    redirect('login.php');

$email = clean_input($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$err = [];
//verificam daca formul de login are email sau parola goala
if (empty($email)) {
    $err[] = "Email-ul este obligatoriu!";
} elseif (!is_valid_email($email)) 
    $err[] = "Formatul email-ului nu este valid!";

if (empty($password)) 
    $err[] = "Parola este obligatorie!";

//daca nu avem erori (in arrayul err)
// continuam cu verficarea in bd a emailului sau a parolei
if (empty($err)) {
    //facem deja quearyul ca sa nu avem probleme de sqlinjection
    $query = "SELECT id, username, full_name, email, password, role FROM users WHERE email = ?";
    $stmt = mysqli_prepare($connection, $query);
    if (!$stmt) {
        $err[] = "Eroare la pregatirea interogarii!";
    } else {
        mysqli_stmt_bind_param($stmt, "s", $email);
        //executam quaery-ul
        if (mysqli_stmt_execute($stmt)) {
            $rez = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($rez);
            if ($user && password_verify($password, $user['password'])) {
                //exista userul in bd si parola (hasuita) este corecta
                $_SESSION['user_id'] = $user['id'];         //punem in sesiune idul userului
                $_SESSION['user_name'] = $user['full_name']; //numele complet
                $_SESSION['user_email'] = $user['email']; //emailul
                $_SESSION['user_role'] = $user['role']; //rolul
                $_SESSION['username'] = $user['username'];  //usernameul
                mysqli_stmt_close($stmt);
                redirect('login.php');
            } else {
                $err[] = "Email sau parola incorecta!";
            }
        } else {
            $err[] = "Eroare la verificarea contului!";
        }
        mysqli_stmt_close($stmt);
    }
}
//trimitem erorile catre pagina de login
if (!empty($err)) {
    $_SESSION['error_messages'] = $err;
    $_SESSION['form_data'] = $_POST;
    redirect('login.php');
}
redirect('login.php');
?>