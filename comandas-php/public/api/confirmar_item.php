<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/queries.php';

$u = require_user(['bar','kitchen','admin']);
$payload = array_merge($_POST, json_decode(file_get_contents('php://input'), true) ?? []);
$item_id = i($payload['item_id'] ?? 0);
if ($item_id<=0) json_fail('item_id requerido');

try{
  $who = $u['role']==='admin' ? 'bar' : $u['role'];
  change_item_status($item_id, 'confirmed', $who);
  
  // Si es una solicitud web (no AJAX), redirigir de vuelta
  if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    $referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
    header('Location: ' . $referer);
    exit;
  }
  
  json_ok(['item_id'=>$item_id, 'status'=>'confirmed']);
}catch(Throwable $e){
  json_fail($e->getMessage(), 400);
}
