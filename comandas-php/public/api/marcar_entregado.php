<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/queries.php';

$u = require_user(['waiter','admin']);
$payload = array_merge($_POST, json_decode(file_get_contents('php://input'), true) ?? []);
$item_id = i($payload['item_id'] ?? 0);
if ($item_id<=0) json_fail('item_id requerido');

try{
  change_item_status($item_id, 'picked_up', 'waiter');
  change_item_status($item_id, 'served', 'waiter');
  json_ok(['item_id'=>$item_id, 'status'=>'served']);
}catch(Throwable $e){
  json_fail($e->getMessage(), 400);
}
