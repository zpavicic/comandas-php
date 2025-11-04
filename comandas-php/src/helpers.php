<?php
/**
 * Utilidades comunes para respuestas JSON, sanitización y tiempos.
 */

/** json_ok(): responde JSON estándar de éxito */
function json_ok($data = [], int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE);
  exit;
}

/** json_fail(): responde JSON estándar de error */
function json_fail(string $msg, int $code = 400, $extra = null): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false,'error'=>$msg,'extra'=>$extra], JSON_UNESCAPED_UNICODE);
  exit;
}

/** i(): convierte valor a int de forma segura */
function i($v): int { return (int)($v ?? 0); }

/** s(): limpia strings básicos */
function s($v): string { return trim((string)($v ?? '')); }

/** now_utc(): devuelve datetime actual en UTC (string) */
function now_utc(): string { return gmdate('Y-m-d H:i:s'); }

/** h(): escapar para HTML */
function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
