<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';

$u = require_user(['admin']);
$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$table_id = i($payload['table_id'] ?? 0);
$nuevo_estado = s($payload['nuevo_estado'] ?? '');

if($table_id<=0) json_fail('table_id requerido');
if(!in_array($nuevo_estado, ['free','in_service','closed'])) json_fail('Estado inválido');

try{
  at("UPDATE restaurant_tables SET status = :estado WHERE id = :id", [
    ':estado' => $nuevo_estado,
    ':id' => $table_id
  ]);
  
  json_ok(['message' => 'Estado de mesa actualizado']);
}catch(Throwable $e){
  json_fail($e->getMessage(), 400);
}