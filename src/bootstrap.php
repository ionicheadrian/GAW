<?php
session_start();
require_once __DIR__.'/config/database.php';
spl_autoload_register(function($cls){
  $path = __DIR__.'/'.str_replace('\\','/',$cls).'.php';
  if (file_exists($path)) require $path;
});
function jsonInput(): array {
  return json_decode(file_get_contents('php://input'),true) ?? [];
}
// practic porneste sesiunea, incarca configuratia, autoload si face conexiunea la bd