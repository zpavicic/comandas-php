<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/queries.php';

require_user();
$order_id = i($_GET['order_id'] ?? 0);
if ($order_id<=0) { http_response_code(422); exit('order_id requerido'); }

$o = at("SELECT o.*, rt.label AS mesa, u.name AS garzon
         FROM orders o
         JOIN restaurant_tables rt ON rt.id=o.table_id
         JOIN users u ON u.id=o.waiter_id
         WHERE o.id=:id", [':id'=>$order_id])->fetch();
if (!$o) { http_response_code(404); exit('Pedido no existe'); }

$items = at("SELECT oi.*, p.name
             FROM order_items oi
             JOIN products p ON p.id=oi.product_id
             WHERE oi.order_id=:o AND oi.status!='canceled'", [':o'=>$order_id])->fetchAll();

$tot = compute_totals($order_id);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Boleta #<?=$order_id?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Tu hoja de estilos -->
  <link rel="stylesheet" href="../styles.css">
</head>
<body>
<main class="ticket">
  <h1>VALHALLA · Boleta</h1>
  <div class="ticket-meta">
    Nº Pedido: <b>#<?=$order_id?></b> · Mesa: <b><?=h($o['mesa'])?></b> · Garzón: <b><?=h($o['garzon'])?></b>
  </div>

  <table class="table">
    <thead><tr><th>Producto</th><th class="right">Cant</th><th class="right">Precio</th></tr></thead>
    <tbody>
      <?php foreach($items as $it): ?>
        <tr>
          <td>
            <?=h($it['name'])?>
            <?php if($it['notes']): ?><div class="note"><i><?=h($it['notes'])?></i></div><?php endif; ?>
            <?php
              $mods = at("SELECT * FROM item_modifiers WHERE order_item_id=:i", [':i'=>$it['id']])->fetchAll();
              foreach($mods as $m){
                echo '<div class="pill">'.h($m['description']).' '.number_format($m['price_delta'],0,',','.').'</div> ';
              }
            ?>
          </td>
          <td class="right"><?=$it['qty']?></td>
          <td class="right">$<?=number_format($it['unit_price']*$it['qty'],0,',','.')?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="2" class="right">Subtotal</td><td class="right">$<?=number_format($tot['subtotal'],0,',','.')?></td></tr>
      <tr><td colspan="2" class="right">Extras</td><td class="right">$<?=number_format($tot['total_modifiers'],0,',','.')?></td></tr>
      <tr><td colspan="2" class="right total">TOTAL</td><td class="right total">$<?=number_format($tot['total'],0,',','.')?></td></tr>
    </tfoot>
  </table>
</main>
</body>
</html>
