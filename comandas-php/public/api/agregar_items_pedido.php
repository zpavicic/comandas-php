<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/queries.php';

$u = require_user(['waiter','admin']);
$payload = json_decode(file_get_contents('php://input'), true);

$table_id = i($payload['table_id'] ?? 0);
$order_id = i($payload['order_id'] ?? 0);
$items = $payload['items'] ?? [];

if ($table_id <= 0 || !$items) {
  json_fail('Datos incompletos');
}

try {
  pdo()->beginTransaction();
  
  // Si no hay order_id, crear nuevo pedido
  if ($order_id <= 0) {
    at("INSERT INTO orders (table_id, waiter_id) VALUES (:t, :w)", [
      ':t' => $table_id,
      ':w' => $u['id']
    ]);
    $order_id = (int)pdo()->lastInsertId();
    
    // Marcar mesa en servicio
    at("UPDATE restaurant_tables SET status='in_service' WHERE id=:id", [':id' => $table_id]);
    
    // Registrar historial
    at("INSERT INTO order_status_history (order_id, status, set_by_user_id) 
        VALUES (:o, 'awaiting_confirmation', :u)", [
      ':o' => $order_id,
      ':u' => $u['id']
    ]);
  } else {
    // Verificar que la orden existe y pertenece a la mesa
    $order = at("SELECT id FROM orders WHERE id=:id AND table_id=:tid AND status NOT IN ('closed','canceled')", [
      ':id' => $order_id,
      ':tid' => $table_id
    ])->fetch();
    
    if (!$order) {
      pdo()->rollBack();
      json_fail('Orden no encontrada o ya cerrada');
    }
  }
  
  // Insertar items
  $insItem = pdo()->prepare("
    INSERT INTO order_items (order_id, product_id, area, qty, unit_price, notes)
    VALUES (:o, :p, NULL, :q, NULL, :n)
  ");
  
  $insMod = pdo()->prepare("
    INSERT INTO item_modifiers (order_item_id, description, price_delta)
    VALUES (:oi, :d, :pd)
  ");
  
  foreach ($items as $it) {
    $insItem->execute([
      ':o' => $order_id,
      ':p' => i($it['product_id'] ?? 0),
      ':q' => max(1, i($it['qty'] ?? 1)),
      ':n' => s($it['notes'] ?? '')
    ]);
    
    $item_id = (int)pdo()->lastInsertId();
    
    // Modificadores
    foreach ($it['modifiers'] ?? [] as $m) {
      $desc = s($m['description'] ?? '');
      $price = (float)($m['price_delta'] ?? 0);
      if ($desc === '') continue;
      
      $insMod->execute([
        ':oi' => $item_id,
        ':d' => $desc,
        ':pd' => $price
      ]);
    }
  }
  
  pdo()->commit();
  
  json_ok([
    'order_id' => $order_id,
    'items_added' => count($items)
  ]);
  
} catch (Throwable $e) {
  pdo()->rollBack();
  json_fail('Error al agregar items: ' . $e->getMessage(), 500);
}