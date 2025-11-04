<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';

$u = require_user(['admin']);
$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$nombre = s($payload['nombre'] ?? '');
$area = s($payload['area'] ?? '');
$precio = floatval($payload['precio'] ?? 0);

if(empty($nombre)) json_fail('Nombre requerido');
if(!in_array($area, ['bar','kitchen'])) json_fail('Área inválida (bar o kitchen)');
if($precio <= 0) json_fail('Precio debe ser mayor a 0');

try{
  at("INSERT INTO products (name, area, base_price, active) VALUES (:n, :a, :p, 1)", [
    ':n' => $nombre,
    ':a' => $area,
    ':p' => $precio
  ]);
  
  $product_id = (int)pdo()->lastInsertId();
  
  json_ok([
    'product_id' => $product_id,
    'message' => 'Producto agregado correctamente'
  ]);
}catch(Throwable $e){
  json_fail($e->getMessage(), 400);
}