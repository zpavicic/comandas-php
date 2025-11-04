<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';

$u = require_user(['admin']);

try{
  // Productos más vendidos
  $productos_vendidos = at("
    SELECT 
      p.name,
      p.area,
      COUNT(oi.id) as cantidad_vendida,
      SUM(oi.qty) as unidades_vendidas,
      SUM(oi.qty * oi.unit_price) as ingresos
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    JOIN orders o ON o.id = oi.order_id
    WHERE oi.status != 'canceled' 
      AND DATE(o.created_at) = CURDATE()
    GROUP BY p.id, p.name, p.area
    ORDER BY unidades_vendidas DESC
    LIMIT 10
  ")->fetchAll();
  
  // Ventas por hora del día
  $ventas_por_hora = at("
    SELECT 
      HOUR(o.created_at) as hora,
      COUNT(DISTINCT o.id) as pedidos,
      SUM(oi.qty * oi.unit_price) as total_ventas
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    WHERE DATE(o.created_at) = CURDATE()
      AND oi.status != 'canceled'
    GROUP BY HOUR(o.created_at)
    ORDER BY hora
  ")->fetchAll();
  
  // Estadísticas generales del día
  $stats_dia = at("
    SELECT 
      COUNT(DISTINCT o.id) as total_pedidos,
      COUNT(DISTINCT CASE WHEN o.status = 'closed' THEN o.id END) as pedidos_completados,
      COUNT(DISTINCT CASE WHEN o.status NOT IN ('closed','canceled') THEN o.id END) as pedidos_activos,
      COALESCE(SUM(CASE WHEN oi.status != 'canceled' THEN oi.qty * oi.unit_price END), 0) as ingresos_total,
      COUNT(DISTINCT o.table_id) as mesas_utilizadas,
      AVG(TIMESTAMPDIFF(MINUTE, o.created_at, o.updated_at)) as tiempo_promedio
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE DATE(o.created_at) = CURDATE()
  ")->fetch();
  
  // Distribución por área (Bar vs Cocina)
  $dist_area = at("
    SELECT 
      oi.area,
      COUNT(oi.id) as cantidad,
      SUM(oi.qty * oi.unit_price) as ingresos
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE DATE(o.created_at) = CURDATE()
      AND oi.status != 'canceled'
    GROUP BY oi.area
  ")->fetchAll();
  
  // Mesas más usadas
  $mesas_usadas = at("
    SELECT 
      rt.label,
      COUNT(DISTINCT o.id) as veces_utilizada,
      SUM(oi.qty * oi.unit_price) as ingresos
    FROM restaurant_tables rt
    JOIN orders o ON o.table_id = rt.id
    JOIN order_items oi ON oi.order_id = o.id
    WHERE DATE(o.created_at) = CURDATE()
      AND oi.status != 'canceled'
    GROUP BY rt.id, rt.label
    ORDER BY veces_utilizada DESC
    LIMIT 5
  ")->fetchAll();
  
  json_ok([
    'productos_vendidos' => $productos_vendidos,
    'ventas_por_hora' => $ventas_por_hora,
    'stats_dia' => $stats_dia,
    'distribucion_area' => $dist_area,
    'mesas_usadas' => $mesas_usadas
  ]);
}catch(Throwable $e){
  json_fail($e->getMessage(), 500);
}