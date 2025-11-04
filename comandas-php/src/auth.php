<?php
/**
 * Gestión de sesión y autenticación
 */
session_start();
require_once __DIR__ . '/db.php';

// Verificar si hay cookie de "recordar sesión"
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
  $token = $_COOKIE['remember_token'];
  $user_id = (int)$_COOKIE['remember_user'];
  $token_hash = hash('sha256', $token);
  
  // Buscar usuario con ese token
  $user = at("SELECT * FROM users WHERE id=:id AND remember_token=:token AND active=1", [
    ':id' => $user_id,
    ':token' => $token_hash
  ])->fetch();
  
  if ($user) {
    // Restaurar sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email'] = $user['email'];
  } else {
    // Token inválido, eliminar cookies
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('remember_user', '', time() - 3600, '/');
  }
}

/**
 * current_user(): devuelve el registro del usuario en sesión.
 */
function current_user(): ?array {
  $uid = (int)($_SESSION['user_id'] ?? 0);
  if ($uid <= 0) return null;
  $row = at("SELECT * FROM users WHERE id=:id AND active=1", [':id'=>$uid])->fetch();
  return $row ?: null;
}

/**
 * require_user(): detiene la ejecución si no hay usuario o rol inválido.
 * @param array|null $roles lista de roles permitidos (o null para cualquiera)
 */
function require_user(?array $roles = null): array {
  $u = current_user();
  
  if (!$u) {
    // No hay usuario logueado, redirigir al login
    header('Location: login.php');
    exit;
  }
  
  if ($roles && !in_array($u['role'], $roles, true)) {
    // Usuario logueado pero sin permisos para esta página
    http_response_code(403);
    echo '<!doctype html>
    <html lang="es">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Acceso Denegado</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-dark text-white">
      <div class="container py-5">
        <div class="row justify-content-center">
          <div class="col-md-6 text-center">
            <h1 class="display-1">403</h1>
            <h2 class="mb-4">Acceso Denegado</h2>
            <p class="lead">No tienes permisos para acceder a esta página.</p>
            <a href="api/logout.php" class="btn btn-warning mt-3">Cerrar Sesión</a>
          </div>
        </div>
      </div>
    </body>
    </html>';
    exit;
  }
  
  return $u;
}

/**
 * is_logged_in(): verifica si hay un usuario en sesión
 */
function is_logged_in(): bool {
  return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * redirect_by_role(): redirige al usuario según su rol
 */
function redirect_by_role(array $user): void {
  switch($user['role']) {
    case 'waiter':
      header('Location: index.php');
      break;
    case 'bar':
      header('Location: barra.php');
      break;
    case 'kitchen':
      header('Location: cocina.php');
      break;
    case 'admin':
      header('Location: admin.php');
      break;
  }
  exit;
}