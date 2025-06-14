<?php
// configura PDO → SQLite, cu PRAGMA pentru foreign keys
try {
  $db = new PDO('sqlite:' . __DIR__ . '/../../data/eco_db.sqlite');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $db->exec('PRAGMA foreign_keys = ON;');
} catch (PDOException $e) {
  die('Database connection failed: ' . $e->getMessage());
}
