<?php
session_start();
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../login.php');
  exit;
}

$email = s($_POST['email'] ?? '');
$password = s($_POST['password'] ?? '');

if (!$email || !$password) {
  $_SESSION['login_error'] = 'Por favor ingresa email y contraseña';
  header('Location: ../login.php');
  exit;
}

try {
  // Buscar usuario por email
  $user = at("SELECT * FROM users WHERE email=:email AND active=1", [':email' => $email])->fetch();
  
  if (!$user) {
    $_SESSION['login_error'] = 'Credenciales incorrectas';
    header('Location: ../login.php');
    exit;
  }
  
  // Verificar contraseña
  // Si password_hash es NULL o vacío, verificar contra texto plano (modo desarrollo)
  // Si tiene hash, usar password_verify
  $passwordValid = false;
  
  if (empty($user['password_hash'])) {
    // Modo desarrollo: comparar directamente con "password"
    $passwordValid = ($password === 'password');
  } else {
    // Modo producción: verificar hash
    $passwordValid = password_verify($password, $user['password_hash']);
  }
  
  if (!$passwordValid) {
    $_SESSION['login_error'] = 'Credenciales incorrectas';
    header('Location: ../login.php');
    exit;
  }
  
  // Login exitoso: guardar datos en sesión
  $_SESSION['user_id'] = $user['id'];
  $_SESSION['user_name'] = $user['name'];
  $_SESSION['user_role'] = $user['role'];
  $_SESSION['user_email'] = $user['email'];
  
  // Si marcó "recordar sesión", crear cookie de larga duración
  if (isset($_POST['remember']) && $_POST['remember']) {
    // Cookie por 30 días
    $cookie_duration = 60 * 60 * 24 * 30;
    
    // Generar token único
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    
    // Guardar token en la base de datos
    at("UPDATE users SET remember_token=:token WHERE id=:id", [
      ':token' => $token_hash,
      ':id' => $user['id']
    ]);
    
    // Establecer cookies
    setcookie('remember_token', $token, time() + $cookie_duration, '/', '', false, true);
    setcookie('remember_user', $user['id'], time() + $cookie_duration, '/', '', false, true);
  }
  
  // Redirigir según rol
  switch($user['role']) {
    case 'waiter':
      header('Location: ../index.php');
      break;
    case 'bar':
      header('Location: ../barra.php');
      break;
    case 'kitchen':
      header('Location: ../cocina.php');
      break;
    case 'admin':
      header('Location: ../admin.php');
      break;
    default:
      $_SESSION['login_error'] = 'Rol de usuario no válido';
      session_destroy();
      header('Location: ../login.php');
  }
  exit;
  
} catch (Throwable $e) {
  $_SESSION['login_error'] = 'Error al procesar el login. Intenta nuevamente.';
  header('Location: ../login.php');
  exit;
}