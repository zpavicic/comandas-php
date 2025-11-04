<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/queries.php';

$u = require_user(['waiter','admin']);

$payload = json_decode(file_get_contents('php://input'), true);
$table_id = i($payload['table_id'] ?? 0);
$items = $payload['items'] ?? [];
if ($table_id<=0 || !$items) json_fail('Datos incompletos');

try{
  $order_id = create_order($table_id, (int)$u['id'], $items);
  json_ok(['order_id'=>$order_id]);
}catch(Throwable $e){
  json_fail('No se pudo crear el pedido', 500, $e->getMessage());
}
