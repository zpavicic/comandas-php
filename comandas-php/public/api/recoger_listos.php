<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/queries.php';

$u = require_user(['waiter','admin']);
$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$order_id = i($payload['order_id'] ?? 0);
if ($order_id<=0) json_fail('order_id requerido');

try{
  // Obtener todos los items listos
  $items = at("
    SELECT id FROM order_items 
    WHERE order_id = :oid AND status = 'ready'
  ", [':oid'=>$order_id])->fetchAll();
  
  $count = 0;
  foreach($items as $item){
    change_item_status($item['id'], 'picked_up', 'waiter');
    $count++;
  }
  
  json_ok([
    'items_recogidos' => $count
  ]);
}catch(Throwable $e){
  json_fail($e->getMessage(), 400);
}