<?php
/**
 * Consultas de negocio para el sistema de comandas.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * get_tables(): lista de mesas
 */
function get_tables(): array {
  return at("SELECT * FROM restaurant_tables ORDER BY id")->fetchAll();
}

/**
 * get_products_by_area(): productos agrupados por área (bar/kitchen)
 */
function get_products_by_area(): array {
  $rows = at("SELECT * FROM products WHERE active=1 ORDER BY area,name")->fetchAll();
  $out = ['bar'=>[], 'kitchen'=>[]];
  foreach ($rows as $r) $out[$r['area']][] = $r;
  return $out;
}

/**
 * create_order(): crea un pedido con sus ítems y modificadores.
 * Parámetros:
 * - $table_id: id de la mesa
 * - $waiter_id: id del garzón
 * - $items: arreglo de ítems, cada uno con:
 *   product_id, qty, notes, modifiers[] (description, price_delta)
 * Regresa: id del pedido creado.
 */
function create_order(int $table_id, int $waiter_id, array $items): int {
  pdo()->beginTransaction();
  try {
    // 1) Crear cabecera del pedido (editable 3 min via trigger)
    at("INSERT INTO orders (table_id, waiter_id) VALUES (:t,:w)", [
      ':t'=>$table_id, ':w'=>$waiter_id
    ]);
    $order_id = (int)pdo()->lastInsertId();

    // 2) Marcar mesa en servicio
    at("UPDATE restaurant_tables SET status='in_service' WHERE id=:id", [':id'=>$table_id]);

    // 3) Insertar ítems
    $insItem = pdo()->prepare("
      INSERT INTO order_items (order_id, product_id, area, qty, unit_price, notes)
      VALUES (:o,:p,NULL,:q,NULL,:n)
    ");
    $insMod  = pdo()->prepare("
      INSERT INTO item_modifiers (order_item_id, description, price_delta)
      VALUES (:oi,:d,:pd)
    ");

    foreach ($items as $it) {
      $insItem->execute([
        ':o'=>$order_id,
        ':p'=>i($it['product_id'] ?? 0),
        ':q'=>max(1, i($it['qty'] ?? 1)),
        ':n'=>s($it['notes'] ?? '')
      ]);
      $order_item_id = (int)pdo()->lastInsertId();

      // Modificadores opcionales
      foreach ($it['modifiers'] ?? [] as $m) {
        $d  = s($m['description'] ?? '');
        $pd = (float)($m['price_delta'] ?? 0);
        if ($d === '') continue;
        $insMod->execute([':oi'=>$order_item_id, ':d'=>$d, ':pd'=>$pd]);
      }
    }

    // 4) Registrar historial de estado inicial
    at("INSERT INTO order_status_history (order_id, status, set_by_user_id, set_at)
        VALUES (:o,'awaiting_confirmation',:u, NOW())", [':o'=>$order_id, ':u'=>$waiter_id]);

    pdo()->commit();
    return $order_id;
  } catch (Throwable $e) {
    pdo()->rollBack();
    throw $e;
  }
}

/**
 * can_edit_order(): indica si aún se puede editar (dentro de 3 minutos y sin confirmar)
 */
function can_edit_order(int $order_id): bool {
  $row = at("SELECT editable_until, status FROM orders WHERE id=:id", [':id'=>$order_id])->fetch();
  if (!$row) return false;
  if (in_array($row['status'], ['confirmed_bar','confirmed_kitchen','closed','canceled'], true)) return false;
  return (strtotime($row['editable_until']) > time());
}

/**
 * change_item_status(): cambia el estado de un ítem respetando flujo simple.
 * - $who: 'bar' o 'kitchen' o 'waiter' para validar permisos de área/rol.
 */
function change_item_status(int $item_id, string $new_status, string $who): void {
  // Consultar item, su área y estado actual
  $it = at("SELECT oi.*, o.table_id
            FROM order_items oi
            JOIN orders o ON o.id=oi.order_id
            WHERE oi.id=:id", [':id'=>$item_id])->fetch();
  if (!$it) throw new RuntimeException('Item no existe.');

  // Validar área cuando actúa barra/cocina
  if (in_array($who, ['bar','kitchen'], true) && $it['area'] !== $who) {
    throw new RuntimeException('No puedes operar ítems de otra área.');
  }

  // Transiciones permitidas simples
  $ok = [
    'awaiting_confirmation' => ['confirmed','canceled'],
    'confirmed'             => ['in_progress','canceled'],
    'in_progress'           => ['ready','canceled'],
    'ready'                 => ['picked_up'],
    'picked_up'             => ['served'],
  ];
  $curr = $it['status'];
  if (!isset($ok[$curr]) || !in_array($new_status, $ok[$curr], true)) {
    throw new RuntimeException("Transición no permitida: $curr → $new_status");
  }

  at("UPDATE order_items SET status=:s WHERE id=:id", [':s'=>$new_status, ':id'=>$item_id]);
}

/**
 * compute_totals(): calcula totales para una orden (subtotal, mods, total)
 */
function compute_totals(int $order_id): array {
  $rows = at("SELECT id, qty, unit_price
              FROM order_items WHERE order_id=:o AND status != 'canceled'", [':o'=>$order_id])->fetchAll();
  $subtotal = 0.0; $mods = 0.0;
  foreach ($rows as $r) {
    $subtotal += ($r['qty'] * $r['unit_price']);
    $m = at("SELECT COALESCE(SUM(price_delta),0) AS d FROM item_modifiers WHERE order_item_id=:i",
            [':i'=>$r['id']])->fetchColumn();
    $mods += (float)$m;
  }
  return [
    'subtotal' => round($subtotal, 2),
    'total_modifiers' => round($mods, 2),
    'discount' => 0.0,
    'total' => round($subtotal + $mods, 2)
  ];
}

/**
 * close_order_and_issue_receipt(): genera boleta y cierra mesa/pedido.
 */
function close_order_and_issue_receipt(int $order_id): int {
  pdo()->beginTransaction();
  try {
    $o = at("SELECT table_id FROM orders WHERE id=:id", [':id'=>$order_id])->fetch();
    if (!$o) throw new RuntimeException('Pedido no existe.');

    $t = compute_totals($order_id);

    at("INSERT INTO receipts (order_id, subtotal, total_modifiers, discount, total)
        VALUES (:o,:s,:m,:d,:tot)", [
      ':o'=>$order_id, ':s'=>$t['subtotal'], ':m'=>$t['total_modifiers'],
      ':d'=>$t['discount'], ':tot'=>$t['total']
    ]);

    at("UPDATE orders SET status='closed' WHERE id=:id", [':id'=>$order_id]);
    at("UPDATE restaurant_tables SET status='closed' WHERE id=:t", [':t'=>$o['table_id']]);

    pdo()->commit();
    return (int)pdo()->lastInsertId();
  } catch(Throwable $e) {
    pdo()->rollBack();
    throw $e;
  }
}

/**
 * queues(): devuelve filas para barra o cocina desde las vistas.
 */
function queues(string $area): array {
  if ($area === 'bar')    return at("SELECT * FROM bar_queue ORDER BY order_item_id ASC")->fetchAll();
  if ($area === 'kitchen')return at("SELECT * FROM kitchen_queue ORDER BY order_item_id ASC")->fetchAll();
  return [];
}
