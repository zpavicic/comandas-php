<?php
/**
 * Configuración de la app.
 */
return [
  'db' => [
    'dsn'  => 'mysql:host=127.0.0.1;dbname=comandas;charset=utf8mb4',
    'user' => 'root',
    'pass' => ''
  ],
  'app' => [
    // Zona horaria para convertir a local al mostrar (guardamos en UTC)
    'timezone' => 'America/Santiago',
    // ID por defecto para simular sesión en desarrollo
    'dev_default_waiter_id' => 1
  ]
];
