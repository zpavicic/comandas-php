<?php
/**
 * Capa de acceso a datos (PDO singleton).
 * Ofrece: pdo() para obtener la conexión y ejecutar consultas seguras.
 */
function pdo(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $config = require __DIR__ . '/../config/config.php';
  $pdo = new PDO(
    $config['db']['dsn'],
    $config['db']['user'],
    $config['db']['pass'],
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
  // Guardamos/operamos en UTC para evitar problemas de cambio de hora.
  $pdo->exec("SET time_zone = '+00:00'");
  return $pdo;
}

/**
 * at(): helper para consultas preparadas con retorno rápido.
 * @param string $sql SQL con placeholders
 * @param array $params parámetros nombrados o posicionales
 * @return PDOStatement resultado ya ejecutado
 */
function at(string $sql, array $params = []): PDOStatement {
  $stmt = pdo()->prepare($sql);
  $stmt->execute($params);
  return $stmt;
}
