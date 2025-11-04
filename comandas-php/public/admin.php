<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/queries.php';
require_once __DIR__ . '/../src/helpers.php';

$u = require_user(['admin']);
$tables = get_tables();
$products = get_products_by_area();

// Obtener estadísticas básicas
$stats = at("
  SELECT 
    COUNT(DISTINCT o.id) as total_orders,
    COUNT(DISTINCT CASE WHEN o.status = 'closed' THEN o.id END) as closed_orders,
    COUNT(DISTINCT CASE WHEN o.status != 'closed' AND o.status != 'canceled' THEN o.id END) as active_orders,
    COALESCE(SUM(CASE WHEN oi.status != 'canceled' AND DATE(o.created_at) = CURDATE() THEN oi.qty * oi.unit_price END), 0) as ventas_dia
  FROM orders o
  LEFT JOIN order_items oi ON oi.order_id = o.id
  WHERE DATE(o.created_at) = CURDATE()
")->fetch();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Valhalla — Panel de Administración</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- SweetAlert2 -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    :root {
      --gold: #d4af37;
      --black: #0b0b0b;
      --dark: #1a1a1a;
    }
    
    body {
      background: var(--black);
      color: #fff;
      font-family: 'Montserrat', sans-serif;
    }
    
    .navbar {
      background: var(--dark) !important;
      border-bottom: 1px solid rgba(212, 175, 55, 0.2);
      padding:1rem 0;
    }
    
    .navbar-brand {
      color: var(--gold) !important;
      font-weight: 700;
      letter-spacing: 0.08em;
      font-size:1.3rem;
    }
    
    .user-badge{
      background:rgba(212,175,55,.1);
      border:1px solid rgba(212,175,55,.3);
      border-radius:20px;
      padding:8px 16px;
      color:var(--gold);
      font-size:.85rem;
      font-weight:500;
    }
    
    .stat-card {
      background: linear-gradient(135deg, rgba(17, 17, 17, 0.95), rgba(26, 26, 26, 0.95));
      border: 1px solid rgba(212, 175, 55, 0.2);
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
      transition:all .3s;
    }
    
    .stat-card:hover{
      transform:translateY(-3px);
      box-shadow:0 6px 25px rgba(212,175,55,.2);
    }
    
    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--gold);
      margin-bottom:.3rem;
    }
    
    .stat-label {
      color: #999;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    .stat-icon{
      font-size:2rem;
      opacity:.5;
      margin-bottom:.5rem;
    }
    
    .card {
      background: rgba(17, 17, 17, 0.95);
      border: 1px solid rgba(212, 175, 55, 0.2);
      border-radius: 15px;
      color: #fff;
      margin-bottom:20px;
    }
    
    .card-header {
      background: transparent;
      border-bottom: 1px solid rgba(212, 175, 55, 0.2);
      color: var(--gold);
      font-weight: 600;
      padding:1rem 1.5rem;
    }
    
    .card-body{
      padding:1.5rem;
    }
    
    .table {
      color: #fff;
    }
    
    .table thead th {
      border-bottom: 2px solid rgba(212, 175, 55, 0.3);
      color: var(--gold);
      font-weight: 600;
      font-size:.9rem;
    }
    
    .table tbody td {
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      vertical-align:middle;
    }
    
    .badge {
      padding: 0.5em 1em;
      border-radius: 10px;
      font-weight:600;
    }
    
    .btn-logout {
      border: 1px solid var(--gold);
      color: var(--gold);
      background: transparent;
      border-radius:8px;
      padding:6px 16px;
      font-weight:500;
      transition:all .3s;
    }
    
    .btn-logout:hover {
      background: var(--gold);
      color: #000;
    }
    
    .btn-action{
      border:2px solid var(--gold);
      color:var(--gold);
      background:transparent;
      border-radius:10px;
      padding:.5rem 1rem;
      font-weight:600;
      transition:all .3s;
      font-size:.85rem;
    }
    
    .btn-action:hover{
      background:var(--gold);
      color:#000;
      transform:translateY(-2px);
    }
    
    .btn-add{
      background:rgba(40,167,69,.2);
      border:2px solid rgba(40,167,69,.4);
      color:#5ddb8e;
    }
    
    .btn-add:hover{
      background:#28a745;
      color:#000;
    }
    
    .chart-container{
      position:relative;
      height:300px;
      margin:20px 0;
    }
    
    /* SweetAlert2 personalizado */
    .swal2-popup{
      background:rgba(17,17,17,.98)!important;
      border:1px solid rgba(212,175,55,.3)!important;
      color:#fff!important;
    }
    .swal2-title{
      color:var(--gold)!important;
    }
    .swal2-html-container{
      color:#ddd!important;
    }
    .swal2-confirm{
      background:var(--gold)!important;
      color:#000!important;
      font-weight:600!important;
    }
    .swal2-cancel{
      background:rgba(220,53,69,.2)!important;
      border:1px solid rgba(220,53,69,.4)!important;
      color:#ff6b6b!important;
    }
    .swal2-input, .swal2-select{
      background:rgba(15,15,15,.9)!important;
      border:1px solid rgba(212,175,55,.3)!important;
      color:#fff!important;
    }
    .swal2-input:focus, .swal2-select:focus{
      border-color:var(--gold)!important;
      box-shadow:0 0 0 .2rem rgba(212,175,55,.15)!important;
    }
    
    .table-hover tbody tr:hover{
      background:rgba(212,175,55,.05);
    }
    
    .quick-action-btn{
      background:rgba(26,26,26,.8);
      border:2px solid rgba(212,175,55,.2);
      color:var(--gold);
      padding:1rem;
      border-radius:12px;
      text-align:center;
      cursor:pointer;
      transition:all .3s;
      text-decoration:none;
      display:block;
    }
    
    .quick-action-btn:hover{
      border-color:var(--gold);
      background:rgba(212,175,55,.1);
      transform:scale(1.02);
      color:var(--gold);
    }
    
    .quick-action-btn .icon{
      font-size:2rem;
      display:block;
      margin-bottom:.5rem;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
      <span class="navbar-brand">👨‍💼 VALHALLA · Administración</span>
      <div class="d-flex align-items-center gap-3">
        <div class="user-badge">
          👤 <?= h($u['name']) ?> <small>(<?= h($u['role']) ?>)</small>
        </div>
        <a href="api/logout.php" class="btn btn-logout btn-sm">Cerrar Sesión</a>
      </div>
    </div>
  </nav>

  <div class="container-fluid py-4">
    <h2 class="mb-4" style="color: var(--gold);">📊 Dashboard - Panel de Control</h2>
    
    <!-- Estadísticas Principales -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="stat-card text-center">
          <div class="stat-icon">📋</div>
          <div class="stat-number"><?= $stats['total_orders'] ?></div>
          <div class="stat-label">Pedidos Hoy</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card text-center">
          <div class="stat-icon">⚡</div>
          <div class="stat-number"><?= $stats['active_orders'] ?></div>
          <div class="stat-label">Pedidos Activos</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card text-center">
          <div class="stat-icon">✅</div>
          <div class="stat-number"><?= $stats['closed_orders'] ?></div>
          <div class="stat-label">Completados</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card text-center">
          <div class="stat-icon">💰</div>
          <div class="stat-number">$<?= number_format($stats['ventas_dia'], 0, ',', '.') ?></div>
          <div class="stat-label">Ventas del Día</div>
        </div>
      </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-3 mb-4">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">📈 Productos Más Vendidos</h5>
          </div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="chartProductos"></canvas>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">⏰ Ventas por Hora</h5>
          </div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="chartHoras"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">🍺🍔 Distribución Bar vs Cocina</h5>
          </div>
          <div class="card-body">
            <div class="chart-container" style="height:250px;">
              <canvas id="chartArea"></canvas>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">🪑 Mesas Más Utilizadas</h5>
          </div>
          <div class="card-body">
            <div class="chart-container" style="height:250px;">
              <canvas id="chartMesas"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Gestión de Mesas -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">🪑 Gestión de Mesas</h5>
        <button class="btn btn-action btn-sm" onclick="refrescarMesas()">🔄 Actualizar</button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Mesa</th>
                <th>Estado Actual</th>
                <th>Última Actualización</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody id="tablaMesas">
              <?php foreach($tables as $t): ?>
                <tr data-table-id="<?=$t['id']?>">
                  <td><strong><?= h($t['label']) ?></strong></td>
                  <td>
                    <?php
                      $badges = [
                        'free' => ['success', '🟢 Libre'],
                        'in_service' => ['warning', '🟡 En Servicio'],
                        'closed' => ['secondary', '⚫ Cerrada']
                      ];
                      $badge = $badges[$t['status']] ?? ['secondary', $t['status']];
                    ?>
                    <span class="badge bg-<?= $badge[0] ?>">
                      <?= $badge[1] ?>
                    </span>
                  </td>
                  <td><?= date('H:i:s', strtotime($t['updated_at'])) ?></td>
                  <td>
                    <button class="btn btn-action btn-sm" onclick="cambiarEstadoMesa(<?=$t['id']?>,'<?=h($t['label'])?>')">
                      ✏️ Cambiar Estado
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Gestión de Productos -->
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">🍺 Productos - Bar</h5>
            <button class="btn btn-action btn-add btn-sm" onclick="agregarProducto('bar')">
              ➕ Nuevo
            </button>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Producto</th>
                    <th class="text-end">Precio</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($products['bar'] as $p): ?>
                    <tr>
                      <td><?= h($p['name']) ?></td>
                      <td class="text-end">$<?= number_format($p['base_price'], 0, ',', '.') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">🍔 Productos - Cocina</h5>
            <button class="btn btn-action btn-add btn-sm" onclick="agregarProducto('kitchen')">
              ➕ Nuevo
            </button>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Producto</th>
                    <th class="text-end">Precio</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($products['kitchen'] as $p): ?>
                    <tr>
                      <td><?= h($p['name']) ?></td>
                      <td class="text-end">$<?= number_format($p['base_price'], 0, ',', '.') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">🚀 Accesos Rápidos</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <a href="index.php" class="quick-action-btn">
              <span class="icon">🧑‍💼</span>
              <strong>Panel Garzón</strong>
            </a>
          </div>
          <div class="col-md-3">
            <a href="barra.php" class="quick-action-btn">
              <span class="icon">🍺</span>
              <strong>Cola Bar</strong>
            </a>
          </div>
          <div class="col-md-3">
            <a href="cocina.php" class="quick-action-btn">
              <span class="icon">🍔</span>
              <strong>Cola Cocina</strong>
            </a>
          </div>
          <div class="col-md-3">
            <div class="quick-action-btn" onclick="verEstadisticasCompletas()">
              <span class="icon">📊</span>
              <strong>Estadísticas</strong>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
  // Configuración global de Chart.js
  Chart.defaults.color = '#999';
  Chart.defaults.borderColor = 'rgba(212,175,55,0.1)';
  
  let chartProductos, chartHoras, chartArea, chartMesas;
  
  // Cargar estadísticas y crear gráficos
  async function cargarEstadisticas(){
    try {
      const res = await fetch('api/estadisticas_admin.php');
      const data = await res.json();
      
      if(!data.ok) {
        console.error('Error al cargar estadísticas:', data.error);
        return;
      }
      
      crearGraficoProductos(data.data.productos_vendidos);
      crearGraficoHoras(data.data.ventas_por_hora);
      crearGraficoArea(data.data.distribucion_area);
      crearGraficoMesas(data.data.mesas_usadas);
    } catch(e) {
      console.error('Error:', e);
    }
  }
  
  function crearGraficoProductos(datos){
    const ctx = document.getElementById('chartProductos').getContext('2d');
    if(chartProductos) chartProductos.destroy();
    
    chartProductos = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: datos.map(d => d.name),
        datasets: [{
          label: 'Unidades Vendidas',
          data: datos.map(d => d.unidades_vendidas),
          backgroundColor: 'rgba(212, 175, 55, 0.6)',
          borderColor: 'rgba(212, 175, 55, 1)',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  }
  
  function crearGraficoHoras(datos){
    const ctx = document.getElementById('chartHoras').getContext('2d');
    if(chartHoras) chartHoras.destroy();
    
    // Llenar horas faltantes con 0
    const horasCompletas = [];
    for(let h = 0; h < 24; h++){
      const encontrada = datos.find(d => parseInt(d.hora) === h);
      horasCompletas.push({
        hora: h,
        pedidos: encontrada ? parseInt(encontrada.pedidos) : 0,
        total_ventas: encontrada ? parseFloat(encontrada.total_ventas) : 0
      });
    }
    
    chartHoras = new Chart(ctx, {
      type: 'line',
      data: {
        labels: horasCompletas.map(d => d.hora + ':00'),
        datasets: [{
          label: 'Pedidos',
          data: horasCompletas.map(d => d.pedidos),
          borderColor: 'rgba(212, 175, 55, 1)',
          backgroundColor: 'rgba(212, 175, 55, 0.1)',
          borderWidth: 2,
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  }
  
  function crearGraficoArea(datos){
    const ctx = document.getElementById('chartArea').getContext('2d');
    if(chartArea) chartArea.destroy();
    
    const labels = {
      'bar': '🍺 Bar',
      'kitchen': '🍔 Cocina'
    };
    
    chartArea = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: datos.map(d => labels[d.area] || d.area),
        datasets: [{
          data: datos.map(d => d.ingresos),
          backgroundColor: [
            'rgba(212, 175, 55, 0.8)',
            'rgba(255, 107, 53, 0.8)'
          ],
          borderColor: [
            'rgba(212, 175, 55, 1)',
            'rgba(255, 107, 53, 1)'
          ],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  }
  
  function crearGraficoMesas(datos){
    const ctx = document.getElementById('chartMesas').getContext('2d');
    if(chartMesas) chartMesas.destroy();
    
    chartMesas = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: datos.map(d => d.label),
        datasets: [{
          label: 'Veces Utilizada',
          data: datos.map(d => d.veces_utilizada),
          backgroundColor: 'rgba(40, 167, 69, 0.6)',
          borderColor: 'rgba(40, 167, 69, 1)',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  }
  
  // Función para cambiar estado de mesa
  async function cambiarEstadoMesa(tableId, tableName){
    const { value: nuevoEstado } = await Swal.fire({
      title: `Cambiar Estado - ${tableName}`,
      input: 'select',
      inputOptions: {
        'free': '🟢 Libre',
        'in_service': '🟡 En Servicio',
        'closed': '⚫ Cerrada'
      },
      inputPlaceholder: 'Selecciona el nuevo estado',
      showCancelButton: true,
      confirmButtonText: 'Cambiar',
      cancelButtonText: 'Cancelar'
    });
    
    if(nuevoEstado){
      try {
        const res = await fetch('api/cambiar_estado_mesa.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            table_id: tableId,
            nuevo_estado: nuevoEstado
          })
        });
        
        const data = await res.json();
        
        if(!data.ok){
          Swal.fire('Error', data.error, 'error');
          return;
        }
        
        Swal.fire({
          icon: 'success',
          title: '¡Estado Actualizado!',
          text: 'El estado de la mesa ha sido cambiado',
          timer: 1500,
          showConfirmButton: false
        });
        
        setTimeout(() => location.reload(), 1500);
      } catch(e) {
        Swal.fire('Error', 'Error al cambiar estado: ' + e.message, 'error');
      }
    }
  }
  
  // Función para agregar producto
  async function agregarProducto(area){
    const areaName = area === 'bar' ? 'Bar' : 'Cocina';
    const areaIcon = area === 'bar' ? '🍺' : '🍔';
    
    const { value: formValues } = await Swal.fire({
      title: `${areaIcon} Agregar Producto - ${areaName}`,
      html: `
        <div style="text-align:left;">
          <div style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:5px; color:#999;">Nombre del Producto</label>
            <input id="swal-nombre" class="swal2-input" placeholder="Ej: Cerveza Artesanal" style="margin:0; width:100%;">
          </div>
          <div style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:5px; color:#999;">Precio</label>
            <input id="swal-precio" type="number" class="swal2-input" placeholder="Ej: 5500" min="0" step="100" style="margin:0; width:100%;">
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Agregar',
      cancelButtonText: 'Cancelar',
      focusConfirm: false,
      preConfirm: () => {
        const nombre = document.getElementById('swal-nombre').value;
        const precio = document.getElementById('swal-precio').value;
        
        if(!nombre || !precio){
          Swal.showValidationMessage('Todos los campos son requeridos');
          return false;
        }
        
        if(parseFloat(precio) <= 0){
          Swal.showValidationMessage('El precio debe ser mayor a 0');
          return false;
        }
        
        return { nombre, precio: parseFloat(precio) };
      }
    });
    
    if(formValues){
      try {
        const res = await fetch('api/agregar_producto.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            nombre: formValues.nombre,
            area: area,
            precio: formValues.precio
          })
        });
        
        const data = await res.json();
        
        if(!data.ok){
          Swal.fire('Error', data.error, 'error');
          return;
        }
        
        Swal.fire({
          icon: 'success',
          title: '¡Producto Agregado!',
          text: 'El producto ha sido agregado correctamente',
          timer: 1500,
          showConfirmButton: false
        });
        
        setTimeout(() => location.reload(), 1500);
      } catch(e) {
        Swal.fire('Error', 'Error al agregar producto: ' + e.message, 'error');
      }
    }
  }
  
  function refrescarMesas(){
    location.reload();
  }
  
  async function verEstadisticasCompletas(){
    try {
      const res = await fetch('api/estadisticas_admin.php');
      const data = await res.json();
      
      if(!data.ok) {
        Swal.fire('Error', data.error, 'error');
        return;
      }
      
      const stats = data.data.stats_dia;
      
      let html = `
        <div style="text-align:left;">
          <h6 style="color:var(--gold); margin-bottom:15px;">📊 Estadísticas Completas del Día</h6>
          <div style="background:rgba(26,26,26,.6); padding:15px; border-radius:10px; margin-bottom:10px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
              <div>
                <div style="color:#999; font-size:0.85em;">Total Pedidos</div>
                <div style="font-size:1.5em; color:var(--gold); font-weight:700;">${stats.total_pedidos}</div>
              </div>
              <div>
                <div style="color:#999; font-size:0.85em;">Completados</div>
                <div style="font-size:1.5em; color:#5ddb8e; font-weight:700;">${stats.pedidos_completados}</div>
              </div>
              <div>
                <div style="color:#999; font-size:0.85em;">Activos</div>
                <div style="font-size:1.5em; color:#ffc107; font-weight:700;">${stats.pedidos_activos}</div>
              </div>
              <div>
                <div style="color:#999; font-size:0.85em;">Mesas Usadas</div>
                <div style="font-size:1.5em; color:var(--gold); font-weight:700;">${stats.mesas_utilizadas}</div>
              </div>
            </div>
          </div>
          <div style="background:rgba(212,175,55,.1); padding:20px; border-radius:10px; text-align:center; border:1px solid rgba(212,175,55,.3);">
            <div style="color:#999; font-size:0.9em; margin-bottom:5px;">💰 Ingresos Totales del Día</div>
            <div style="font-size:2.5em; color:var(--gold); font-weight:700;">$${parseFloat(stats.ingresos_total).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".")}</div>
          </div>
        </div>
      `;
      
      Swal.fire({
        title: 'Estadísticas del Día',
        html: html,
        width: '600px',
        confirmButtonText: 'Cerrar'
      });
    } catch(e) {
      Swal.fire('Error', 'Error al cargar estadísticas: ' + e.message, 'error');
    }
  }
  
  // Cargar estadísticas al inicio
  document.addEventListener('DOMContentLoaded', () => {
    cargarEstadisticas();
  });
  
  // Auto-refresh cada 30 segundos
  setInterval(() => {
    if(!document.querySelector('.swal2-container')) {
      location.reload();
    }
  }, 30000);
  </script>
</body>
</html>