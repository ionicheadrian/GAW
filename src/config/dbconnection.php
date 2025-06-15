<?php
$host = 'localhost';        
$dbname = 'gwa';           
$username = 'root';         
$password = '';             
//ne conectam la baza de date
$connection = mysqli_connect($host, $username, $password, $dbname);
if (!$connection)
    die("Eroare la conectarea la baza de date: " . mysqli_connect_error());
//setam caracterele cu diacritice
mysqli_set_charset($connection, "utf8");
?>