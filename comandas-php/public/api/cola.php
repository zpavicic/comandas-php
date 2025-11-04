<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/queries.php';

$area = $_GET['area'] ?? 'bar';
if(!in_array($area, ['bar','kitchen'], true)) json_fail('Área inválida', 422);
require_user();
json_ok(queues($area));
