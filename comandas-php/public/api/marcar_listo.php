<?php
session_start();
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];
$item_id = (int)($input['item_id'] ?? $_POST['item_id'] ?? $input['id'] ?? $_POST['id'] ?? 0);
$area    = ($input['area'] ?? $_POST['area'] ?? null);

if ($item_id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'item_id requerido']); exit; }

try {
  $it = at("SELECT id, area, status FROM order_items WHERE id=:id", [':id'=>$item_id])->fetch();
  if (!$it) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Ítem no encontrado']); exit; }
  if ($area && $area !== $it['area']) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Área no coincide con el ítem']); exit; }

  // Si aún está pendiente, encadenamos: confirmed -> in_progress
  if ($it['status'] === 'awaiting_confirmation') {
    at("UPDATE order_items SET status='confirmed', updated_at=NOW() WHERE id=:id", [':id'=>$item_id]);
    $it['status'] = 'confirmed';
  }
  if ($it['status'] === 'confirmed') {
    at("UPDATE order_items SET status='in_progress', updated_at=NOW() WHERE id=:id", [':id'=>$item_id]);
    $it['status'] = 'in_progress';
  }

  // Finalmente, listo
  $affected = at(
    "UPDATE order_items
       SET status='ready', updated_at=NOW()
     WHERE id=:id AND status IN ('in_progress','confirmed')",
    [':id'=>$item_id]
  )->rowCount();

  if ($affected === 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>"Transición no permitida"]);
    exit;
  }

  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Error de servidor']);
}