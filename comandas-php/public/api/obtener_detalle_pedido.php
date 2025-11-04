<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/queries.php';

$u = require_user(['waiter','admin']);
$order_id = i($_GET['order_id'] ?? 0);
if ($order_id<=0) json_fail('order_id requerido');

try{
  // Obtener items del pedido
  $items = at("
    SELECT 
      oi.id,
      oi.product_id,
      oi.area,
      oi.qty,
      oi.unit_price,
      oi.notes,
      oi.status,
      p.name as product_name
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = :oid AND oi.status != 'canceled'
    ORDER BY oi.id
  ", [':oid'=>$order_id])->fetchAll();
  
  // Obtener modificadores para cada item
  foreach($items as &$item){
    $mods = at("
      SELECT description, price_delta 
      FROM item_modifiers 
      WHERE order_item_id = :iid
    ", [':iid'=>$item['id']])->fetchAll();
    $item['modifiers'] = $mods;
  }
  
  // Calcular total
  $total = 0;
  foreach($items as $item){
    $total += ($item['qty'] * $item['unit_price']);
    foreach($item['modifiers'] as $mod){
      $total += $mod['price_delta'];
    }
  }
  
  json_ok([
    'items' => $items,
    'total' => $total
  ]);
}catch(Throwable $e){
  json_fail($e->getMessage(), 400);
}