<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/queries.php';

$u = require_user(['waiter','admin']);
$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$order_id = i($payload['order_id'] ?? 0);
if ($order_id<=0) json_fail('order_id requerido');

try{
  // Marcar todos los items picked_up como served
  at("
    UPDATE order_items 
    SET status = 'served' 
    WHERE order_id = :oid AND status = 'picked_up'
  ", [':oid'=>$order_id]);
  
  // Cerrar orden y generar boleta
  close_order_and_issue_receipt($order_id);
  
  // Obtener totales
  $totals = compute_totals($order_id);
  
  json_ok([
    'total' => $totals['total']
  ]);
}catch(Throwable $e){
  json_fail($e->getMessage(), 400);
}