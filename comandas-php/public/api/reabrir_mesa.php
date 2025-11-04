<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';

$u = require_user(['waiter','admin']);
$payload = json_decode(file_get_contents('php://input'), true);
$table_id = i($payload['table_id'] ?? 0);

if ($table_id <= 0) {
  json_fail('table_id requerido');
}

try {
  at("UPDATE restaurant_tables SET status='free' WHERE id = :id", [':id' => $table_id]);
  
  json_ok(['table_id' => $table_id, 'status' => 'free']);
  
} catch (Throwable $e) {
  json_fail('Error: ' . $e->getMessage(), 500);
}