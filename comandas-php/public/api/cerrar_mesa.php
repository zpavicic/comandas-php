<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/queries.php';

require_user(['waiter','admin']);
$order_id = i($_POST['order_id'] ?? ($_GET['order_id'] ?? 0));
if ($order_id<=0) json_fail('order_id requerido');

try{
  $rid = close_order_and_issue_receipt($order_id);
  json_ok(['receipt_id'=>$rid]);
}catch(Throwable $e){
  json_fail($e->getMessage(), 400);
}
