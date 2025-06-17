<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['user'])) { http_response_code(401); exit; }

$sim = [];
for ($i=0;$i<7;$i++) { // simulam date de colkectare a deseurilor pentru 7 zile consecutive, iar mai jos generam o data pentru fiecare zi
    $date = date('Y-m-d', strtotime("+$i days"));
    $sim[] = [
      'date'=>$date,
      'menajer'=>round(rand(50,150),1),
      'hartie'=>round(rand(30,100),1),
      'plastic'=>round(rand(20,80),1),
      'sticla'=>round(rand(10,50),1)
    ];
}
echo json_encode($sim);
