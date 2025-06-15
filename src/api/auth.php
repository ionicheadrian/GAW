<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
session_start();

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true);

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $pass  = $input['password'] ?? '';
    if (!$email) { http_response_code(400); echo json_encode(['error'=>'Email invalid']); exit; }

    $stmt = $db->prepare('SELECT id,password,role,full_name FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u && password_verify($pass, $u['password'])) {
        $_SESSION['user'] = ['id'=>$u['id'],'role'=>$u['role'],'name'=>$u['full_name']];
        echo json_encode(['ok'=>true]);
    } else {
        http_response_code(401);
        echo json_encode(['error'=>'Email sau parolă incorecte']);
    }
    exit;
}

if ($action === 'signup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($input['full_name'] ?? '');
    $email= filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $pass = $input['password'] ?? '';
    if (!$name || !$email || strlen($pass)<6) {
        http_response_code(400);
        echo json_encode(['error'=>'Date invalide']);
        exit;
    }
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users(full_name,email,password) VALUES(?,?,?)');
    try {
        $stmt->execute([$name,$email,$hash]);
        echo json_encode(['ok'=>true]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(['error'=>'Email deja folosit']);
    }
    exit;
}

http_response_code(404);
echo json_encode(['error'=>'Acțiune necunoscută']);
