<?php
session_start();

// Eliminar cookies
if (isset($_COOKIE['remember_token'])) {
  setcookie('remember_token', '', time() - 3600, '/');
}
if (isset($_COOKIE['remember_user'])) {
  setcookie('remember_user', '', time() - 3600, '/');
}

// Destruir sesión
$_SESSION = array();
session_destroy();

// Redirigir al login
header('Location: ../login.php');
exit;