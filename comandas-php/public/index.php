<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/queries.php';
require_once __DIR__ . '/../src/helpers.php';

$u = require_user(['waiter','admin']);
$tables = get_tables();
$products = get_products_by_area();

// Obtener mesas activas con pedidos
$active_tables = at("
  SELECT 
    rt.id as table_id,
    rt.label as table_name,
    rt.status,
    o.id as order_id,
    o.created_at,
    COUNT(oi.id) as total_items,
    SUM(CASE WHEN oi.status IN ('awaiting_confirmation','confirmed','in_progress') THEN 1 ELSE 0 END) as pending_items,
    SUM(CASE WHEN oi.status = 'ready' THEN 1 ELSE 0 END) as ready_items,
    SUM(CASE WHEN oi.status IN ('picked_up','served') THEN 1 ELSE 0 END) as delivered_items
  FROM restaurant_tables rt
  LEFT JOIN orders o ON o.table_id = rt.id AND o.status NOT IN ('closed','canceled')
  LEFT JOIN order_items oi ON oi.order_id = o.id
  WHERE rt.status = 'in_service'
  GROUP BY rt.id, rt.label, rt.status, o.id, o.created_at
  ORDER BY o.created_at DESC
")->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Valhalla — Panel Garzón</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<style>
  :root{
    --gold:#d4af37; --black:#0b0b0b; --dark:#1a1a1a; --white:#fff; --muted:#999;
  }
  body{
    margin:0;
    font-family:'Montserrat',sans-serif;
    background:var(--black);
    color:var(--white);
  }
  .navbar{
    background:var(--dark)!important;
    border-bottom:1px solid rgba(212,175,55,.2);
    padding:1rem 0;
  }
  .navbar-brand{
    color:var(--gold)!important;
    font-weight:700;
    letter-spacing:.08em;
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
  .btn-logout{
    border:1px solid var(--gold);
    color:var(--gold);
    background:transparent;
    border-radius:8px;
    padding:6px 16px;
    font-weight:500;
    transition:all .3s;
  }
  .btn-logout:hover{
    background:var(--gold);
    color:#000;
  }
  .card{
    background:rgba(17,17,17,.95);
    border:1px solid rgba(212,175,55,.2);
    border-radius:15px;
    margin-bottom:20px;
    box-shadow:0 4px 20px rgba(0,0,0,.3);
  }
  .card-header{
    background:transparent;
    border-bottom:1px solid rgba(212,175,55,.2);
    padding:1rem 1.5rem;
  }
  .card-header h5{
    color:var(--gold);
    margin:0;
    font-weight:600;
  }
  .card-body{
    padding:1.5rem;
  }
  .table-card{
    background:linear-gradient(135deg,rgba(17,17,17,.95),rgba(26,26,26,.95));
    border:1px solid rgba(212,175,55,.2);
    border-radius:12px;
    padding:1.2rem;
    margin-bottom:1rem;
    cursor:pointer;
    transition:all .3s;
    position:relative;
  }
  .table-card:hover{
    border-color:rgba(212,175,55,.5);
    transform:translateY(-3px);
    box-shadow:0 6px 25px rgba(212,175,55,.2);
  }
  .table-card-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:.8rem;
  }
  .table-name{
    font-size:1.3rem;
    font-weight:700;
    color:var(--gold);
  }
  .table-status{
    display:flex;
    gap:.5rem;
    align-items:center;
  }
  .status-indicator{
    width:12px;
    height:12px;
    border-radius:50%;
    display:inline-block;
  }
  .status-free{background:#28a745;}
  .status-in_service{background:#ffc107;animation:pulse 2s infinite;}
  .status-closed{background:#6c757d;}
  @keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
  .table-stats{
    display:flex;
    gap:1rem;
    flex-wrap:wrap;
    font-size:.85rem;
    color:var(--muted);
  }
  .stat-item{
    display:flex;
    align-items:center;
    gap:.3rem;
  }
  .stat-badge{
    background:rgba(212,175,55,.1);
    border:1px solid rgba(212,175,55,.3);
    color:var(--gold);
    padding:.2rem .6rem;
    border-radius:6px;
    font-weight:600;
  }
  .ready-badge{
    background:rgba(40,167,69,.2);
    border-color:rgba(40,167,69,.4);
    color:#5ddb8e;
    animation:glow 2s infinite;
  }
  @keyframes glow{0%,100%{box-shadow:0 0 5px rgba(40,167,69,.3)}50%{box-shadow:0 0 15px rgba(40,167,69,.6)}}
  .free-tables{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(120px,1fr));
    gap:1rem;
  }
  .free-table-card{
    background:rgba(26,26,26,.8);
    border:2px solid rgba(212,175,55,.2);
    border-radius:12px;
    padding:1.5rem 1rem;
    text-align:center;
    cursor:pointer;
    transition:all .3s;
  }
  .free-table-card:hover{
    border-color:var(--gold);
    background:rgba(212,175,55,.1);
    transform:scale(1.05);
  }
  .free-table-card .icon{
    font-size:2rem;
    margin-bottom:.5rem;
  }
  .free-table-card .label{
    font-weight:600;
    color:var(--gold);
  }
  .empty{
    text-align:center;
    color:var(--muted);
    padding:2rem;
    font-style:italic;
  }
  
  /* Estilos personalizados para SweetAlert2 */
  .swal2-popup{
    background:rgba(17,17,17,.98)!important;
    border:1px solid rgba(212,175,55,.3)!important;
    color:#fff!important;
    border-radius:20px!important;
  }
  .swal2-title{
    color:var(--gold)!important;
    font-size:1.5rem!important;
    font-weight:700!important;
  }
  .swal2-html-container{
    color:#ddd!important;
  }
  .swal2-confirm{
    background:var(--gold)!important;
    color:#000!important;
    font-weight:600!important;
    border-radius:10px!important;
    padding:12px 30px!important;
    border:none!important;
  }
  .swal2-confirm:hover{
    background:#e6c245!important;
  }
  .swal2-cancel{
    background:rgba(220,53,69,.2)!important;
    border:1px solid rgba(220,53,69,.4)!important;
    color:#ff6b6b!important;
    border-radius:10px!important;
    padding:12px 30px!important;
  }
  .swal2-deny{
    background:rgba(40,167,69,.2)!important;
    border:1px solid rgba(40,167,69,.4)!important;
    color:#5ddb8e!important;
    border-radius:10px!important;
    padding:12px 30px!important;
  }
  .swal2-input, .swal2-select, .swal2-textarea{
    background:rgba(15,15,15,.9)!important;
    border:1px solid rgba(212,175,55,.3)!important;
    color:#fff!important;
    border-radius:10px!important;
    padding:12px 16px!important;
  }
  .swal2-input:focus, .swal2-select:focus, .swal2-textarea:focus{
    border-color:var(--gold)!important;
    box-shadow:0 0 0 .2rem rgba(212,175,55,.15)!important;
  }
  .item-row-swal{
    background:rgba(26,26,26,.8);
    border:1px solid rgba(212,175,55,.15);
    border-radius:8px;
    padding:1rem;
    margin-bottom:.8rem;
    text-align:left;
  }
  .item-status-badge{
    font-size:.75rem;
    padding:.3rem .6rem;
    border-radius:6px;
    font-weight:600;
    display:inline-block;
  }
  .status-awaiting_confirmation{background:rgba(255,193,7,.2);color:#ffc107;}
  .status-confirmed{background:rgba(13,110,253,.2);color:#5ba3ff;}
  .status-in_progress{background:rgba(220,53,69,.2);color:#ff6b6b;}
  .status-ready{background:rgba(40,167,69,.2);color:#5ddb8e;}
  .status-picked_up{background:rgba(111,66,193,.2);color:#b794f4;}
  .status-served{background:rgba(108,117,125,.2);color:#adb5bd;}
  
  /* Estilos mejorados para el modal de productos */
  .producto-item{
    background:rgba(26,26,26,.8);
    border:1px solid rgba(212,175,55,.2);
    border-radius:12px;
    padding:20px;
    margin-bottom:15px;
    transition:all .3s;
  }
  .producto-item:hover{
    border-color:rgba(212,175,55,.4);
    box-shadow:0 4px 15px rgba(0,0,0,.3);
  }
  .producto-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
    padding-bottom:10px;
    border-bottom:1px solid rgba(212,175,55,.1);
  }
  .producto-number{
    background:var(--gold);
    color:#000;
    width:30px;
    height:30px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    font-size:.9rem;
  }
  .btn-remove-producto{
    background:rgba(220,53,69,.2);
    border:1px solid rgba(220,53,69,.4);
    color:#ff6b6b;
    padding:6px 12px;
    border-radius:8px;
    cursor:pointer;
    font-size:.85rem;
    font-weight:500;
    transition:all .3s;
  }
  .btn-remove-producto:hover{
    background:rgba(220,53,69,.3);
    border-color:rgba(220,53,69,.6);
  }
  .btn-add-producto{
    background:rgba(40,167,69,.2);
    border:1px solid rgba(40,167,69,.4);
    color:#5ddb8e;
    padding:10px 20px;
    border-radius:10px;
    cursor:pointer;
    font-weight:600;
    margin-top:15px;
    display:inline-block;
    transition:all .3s;
  }
  .btn-add-producto:hover{
    background:rgba(40,167,69,.3);
    border-color:rgba(40,167,69,.6);
    transform:translateY(-2px);
  }
  .field-label{
    color:#999;
    font-size:.85rem;
    font-weight:500;
    margin-bottom:8px;
    display:block;
  }
  .input-group-custom{
    display:flex;
    gap:10px;
  }
  .input-group-custom .swal2-input,
  .input-group-custom .swal2-select{
    margin:0!important;
  }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <span class="navbar-brand">VALHALLA · Panel Garzón</span>
    <div class="d-flex align-items-center gap-3">
      <div class="user-badge">
        👤 <?=h($u['name'])?> <small>(<?=h($u['role'])?>)</small>
      </div>
      <a href="api/logout.php" class="btn btn-logout btn-sm">Cerrar Sesión</a>
    </div>
  </div>
</nav>

<div class="container-fluid py-4">
  <div class="row">
    <!-- Mesas Libres -->
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header">
          <h5>🪑 Mesas Disponibles</h5>
        </div>
        <div class="card-body">
          <div class="free-tables">
            <?php 
            $free_tables = array_filter($tables, fn($t) => $t['status'] === 'free');
            if(!$free_tables): ?>
              <div class="empty">No hay mesas disponibles</div>
            <?php else: ?>
              <?php foreach($free_tables as $t): ?>
                <div class="free-table-card" onclick="iniciarMesa(<?=$t['id']?>,'<?=h($t['label'])?>')">
                  <div class="icon">🪑</div>
                  <div class="label"><?=h($t['label'])?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Mesas Activas -->
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header">
          <h5>📋 Mesas en Servicio</h5>
        </div>
        <div class="card-body">
          <?php if(!$active_tables): ?>
            <div class="empty">No hay mesas activas. Selecciona una mesa libre para comenzar.</div>
          <?php else: ?>
            <?php foreach($active_tables as $table): ?>
              <div class="table-card" onclick="verDetalleMesa(<?=$table['order_id']?>,<?=$table['table_id']?>,'<?=h($table['table_name'])?>')">
                <div class="table-card-header">
                  <div class="table-name">
                    <span class="status-indicator status-<?=$table['status']?>"></span>
                    <?=h($table['table_name'])?>
                  </div>
                  <div>
                    <small class="text-muted">Pedido #<?=$table['order_id']?></small>
                  </div>
                </div>
                <div class="table-stats">
                  <div class="stat-item">
                    <span>🕐</span>
                    <span>Hace <?=floor((time()-strtotime($table['created_at']))/60)?> min</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-badge"><?=$table['total_items']?> items</span>
                  </div>
                  <?php if($table['pending_items'] > 0): ?>
                    <div class="stat-item">
                      <span class="stat-badge">⏳ <?=$table['pending_items']?> en preparación</span>
                    </div>
                  <?php endif; ?>
                  <?php if($table['ready_items'] > 0): ?>
                    <div class="stat-item">
                      <span class="stat-badge ready-badge">✅ <?=$table['ready_items']?> LISTOS</span>
                    </div>
                  <?php endif; ?>
                  <?php if($table['delivered_items'] > 0): ?>
                    <div class="stat-item">
                      <span class="stat-badge">🍽️ <?=$table['delivered_items']?> entregados</span>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const PRODUCTS = <?=json_encode($products, JSON_UNESCAPED_UNICODE)?>;
let currentOrderId = null;
let currentTableId = null;
let currentTableName = null;
let productoCounter = 0;

// Función para iniciar una mesa
function iniciarMesa(tableId, tableName){
  currentTableId = tableId;
  currentTableName = tableName;
  currentOrderId = null;
  
  mostrarModalAgregarProductos();
}

// Función para mostrar modal de agregar productos con mejor UX/UI
async function mostrarModalAgregarProductos(){
  productoCounter = 1;
  
  const {value: formValues} = await Swal.fire({
    title: `<i class="fas fa-plus-circle"></i> ${currentOrderId ? 'Agregar Más Productos' : 'Iniciar Pedido'}`,
    html: `
      <div style="text-align:left;">
        <div style="background:rgba(212,175,55,.1); padding:15px; border-radius:10px; margin-bottom:20px; border-left:3px solid var(--gold);">
          <strong style="color:var(--gold); font-size:1.1rem;">${currentTableName}</strong>
          <div style="color:#999; font-size:.85rem; margin-top:5px;">
            ${currentOrderId ? '📋 Pedido #' + currentOrderId : '✨ Nuevo Pedido'}
          </div>
        </div>
        
        <div id="productosContainer">
          ${crearFilaProducto(1)}
        </div>
        
        <div style="text-align:center; margin-top:20px;">
          <button type="button" onclick="agregarFilaProducto()" class="btn-add-producto">
            ➕ Agregar Otro Producto
          </button>
        </div>
      </div>
    `,
    showCancelButton: true,
    showCloseButton: true,
    confirmButtonText: currentOrderId ? '✅ Agregar al Pedido' : '🚀 Crear Pedido',
    cancelButtonText: '❌ Cancelar',
    width: '700px',
    customClass: {
      confirmButton: 'swal2-confirm',
      cancelButton: 'swal2-cancel'
    },
    didOpen: () => {
      // Agregar eventos para los selectores de productos
      actualizarEventos();
    },
    preConfirm: () => {
      const items = [];
      document.querySelectorAll('.producto-item').forEach(item => {
        const select = item.querySelector('.producto-select');
        const cantidad = item.querySelector('.cantidad-input');
        const notas = item.querySelector('.notas-input');
        
        if(select && select.value){
          items.push({
            product_id: parseInt(select.value),
            qty: parseInt(cantidad.value) || 1,
            notes: notas.value || '',
            modifiers: []
          });
        }
      });
      
      if(items.length === 0){
        Swal.showValidationMessage('⚠️ Debes seleccionar al menos un producto');
        return false;
      }
      
      return items;
    }
  });

  if(formValues){
    await enviarPedido(formValues);
  }
}

// Función para crear una fila de producto con mejor diseño
function crearFilaProducto(numero){
  return `
    <div class="producto-item">
      <div class="producto-header">
        <div class="producto-number">${numero}</div>
        ${numero > 1 ? '<button type="button" onclick="eliminarProducto(this)" class="btn-remove-producto">🗑️ Eliminar</button>' : ''}
      </div>
      
      <div style="margin-bottom:15px;">
        <label class="field-label">🍽️ Producto</label>
        <select class="swal2-select producto-select" style="width:100%; margin:0;">
          <option value="">Selecciona un producto...</option>
          <optgroup label="🍺 Bar">
            ${PRODUCTS.bar.map(p=>`<option value="${p.id}" data-price="${p.base_price}">${p.name} - $${formatPrice(p.base_price)}</option>`).join('')}
          </optgroup>
          <optgroup label="🍔 Cocina">
            ${PRODUCTS.kitchen.map(p=>`<option value="${p.id}" data-price="${p.base_price}">${p.name} - $${formatPrice(p.base_price)}</option>`).join('')}
          </optgroup>
        </select>
      </div>
      
      <div class="input-group-custom" style="margin-bottom:15px;">
        <div style="flex:1;">
          <label class="field-label">📊 Cantidad</label>
          <input type="number" class="swal2-input cantidad-input" placeholder="1" value="1" min="1" style="width:100%;">
        </div>
        <div style="flex:2;">
          <label class="field-label">💬 Notas</label>
          <input type="text" class="swal2-input notas-input" placeholder="Ej: sin cebolla, bien cocido..." style="width:100%;">
        </div>
      </div>
      
      <div class="precio-preview" style="text-align:right; color:var(--gold); font-weight:600; font-size:1.1rem; display:none;">
        Total: $<span class="precio-total">0</span>
      </div>
    </div>
  `;
}

// Función para agregar otra fila de producto
window.agregarFilaProducto = function(){
  productoCounter++;
  const container = document.getElementById('productosContainer');
  const div = document.createElement('div');
  div.innerHTML = crearFilaProducto(productoCounter);
  container.appendChild(div.firstElementChild);
  actualizarEventos();
}

// Función para eliminar producto
window.eliminarProducto = function(btn){
  Swal.fire({
    title: '¿Eliminar producto?',
    text: 'Esta acción no se puede deshacer',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if(result.isConfirmed){
      btn.closest('.producto-item').remove();
      // Renumerar productos
      document.querySelectorAll('.producto-number').forEach((elem, idx) => {
        elem.textContent = idx + 1;
      });
    }
  });
}

// Función para actualizar eventos de los selectores
function actualizarEventos(){
  document.querySelectorAll('.producto-select').forEach(select => {
    select.addEventListener('change', function(){
      const item = this.closest('.producto-item');
      const cantidadInput = item.querySelector('.cantidad-input');
      const precioPreview = item.querySelector('.precio-preview');
      const precioTotal = item.querySelector('.precio-total');
      
      if(this.value){
        const precio = parseFloat(this.options[this.selectedIndex].dataset.price);
        const cantidad = parseInt(cantidadInput.value) || 1;
        precioTotal.textContent = formatPrice(precio * cantidad);
        precioPreview.style.display = 'block';
      } else {
        precioPreview.style.display = 'none';
      }
    });
  });
  
  document.querySelectorAll('.cantidad-input').forEach(input => {
    input.addEventListener('input', function(){
      const item = this.closest('.producto-item');
      const select = item.querySelector('.producto-select');
      if(select.value){
        select.dispatchEvent(new Event('change'));
      }
    });
  });
}

// Función para formatear precio
function formatPrice(price){
  return Math.round(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Función para enviar el pedido
async function enviarPedido(items){
  try {
    const res = await fetch('api/agregar_items_pedido.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        table_id: currentTableId,
        order_id: currentOrderId || null,
        items: items
      })
    });
    
    const data = await res.json();
    
    if(!data.ok){
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: data.error,
        confirmButtonText: 'Entendido'
      });
      return;
    }
    
    await Swal.fire({
      icon: 'success',
      title: '¡Éxito!',
      text: currentOrderId ? 'Productos agregados correctamente' : 'Pedido creado correctamente',
      timer: 2000,
      showConfirmButton: false
    });
    
    setTimeout(() => location.reload(), 2000);
  } catch(e) {
    Swal.fire({
      icon: 'error',
      title: 'Error de Conexión',
      text: 'No se pudo procesar la solicitud: ' + e.message,
      confirmButtonText: 'Entendido'
    });
  }
}

// Función para ver detalle de una mesa
async function verDetalleMesa(orderId, tableId, tableName){
  currentOrderId = orderId;
  currentTableId = tableId;
  currentTableName = tableName;
  
  try {
    const res = await fetch(`api/obtener_detalle_pedido.php?order_id=${orderId}`);
    const data = await res.json();
    
    if(!data.ok){
      Swal.fire('Error', data.error, 'error');
      return;
    }
    
    mostrarDetalleOrden(data.data, tableName, orderId);
  } catch(e) {
    Swal.fire('Error', 'Error al obtener detalle: ' + e.message, 'error');
  }
}

// Función para mostrar el detalle de la orden
function mostrarDetalleOrden(data, tableName, orderId){
  const statusLabels = {
    'awaiting_confirmation': '⏳ Esperando Confirmación',
    'confirmed': '✓ Confirmado',
    'in_progress': '🔄 En Preparación',
    'ready': '✅ LISTO',
    'picked_up': '🍽️ Recogido',
    'served': '✔️ Entregado'
  };
  
  let itemsHtml = '';
  
  // Agrupar por área
  ['bar', 'kitchen'].forEach(area => {
    const areaItems = data.items.filter(i => i.area === area);
    if(areaItems.length === 0) return;
    
    const areaIcon = area === 'bar' ? '🍺' : '🍔';
    const areaName = area === 'bar' ? 'Bar' : 'Cocina';
    
    itemsHtml += `<h6 style="color:var(--gold); margin:20px 0 10px 0; text-align:left;">${areaIcon} ${areaName}</h6>`;
    
    areaItems.forEach(item => {
      itemsHtml += `
        <div class="item-row-swal">
          <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:8px;">
            <div>
              <strong style="color:#fff;">${item.product_name}</strong> × ${item.qty}
              ${item.notes ? `<div style="color:#999; font-size:0.85em; font-style:italic; margin-top:4px;">💬 ${item.notes}</div>` : ''}
            </div>
            <span class="item-status-badge status-${item.status}">
              ${statusLabels[item.status] || item.status}
            </span>
          </div>
          <div style="color:#999; font-size:0.8em;">
            Item #${item.id} · $${formatPrice(item.unit_price * item.qty)}
          </div>
        </div>
      `;
    });
  });
  
  // Resumen
  const pending = data.items.filter(i => ['awaiting_confirmation','confirmed','in_progress'].includes(i.status)).length;
  const ready = data.items.filter(i => i.status === 'ready').length;
  
  itemsHtml += `
    <div style="background:rgba(212,175,55,.1); border:1px solid rgba(212,175,55,.3); border-radius:10px; padding:20px; margin-top:20px;">
      <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:15px; text-align:center;">
        <div>
          <div style="font-size:1.8rem; font-weight:700; color:var(--gold);">${data.items.length}</div>
          <div style="font-size:0.75rem; color:#999;">Total Items</div>
        </div>
        <div>
          <div style="font-size:1.8rem; font-weight:700; color:#ffc107;">${pending}</div>
          <div style="font-size:0.75rem; color:#999;">En Preparación</div>
        </div>
        <div>
          <div style="font-size:1.8rem; font-weight:700; color:#5ddb8e;">${ready}</div>
          <div style="font-size:0.75rem; color:#999;">Listos</div>
        </div>
        <div>
          <div style="font-size:1.8rem; font-weight:700; color:var(--gold);">$${formatPrice(data.total)}</div>
          <div style="font-size:0.75rem; color:#999;">Total</div>
        </div>
      </div>
    </div>
  `;
  
  Swal.fire({
    title: `📋 ${tableName} - Pedido #${orderId}`,
    html: itemsHtml,
    width: '800px',
    showCancelButton: true,
    showDenyButton: true,
    showCloseButton: true,
    confirmButtonText: '💰 Finalizar y Cerrar',
    denyButtonText: '➕ Agregar Productos',
    cancelButtonText: ready > 0 ? `🍽️ Recoger ${ready} Listos` : '❌ Cerrar',
    cancelButtonColor: ready > 0 ? '#28a745' : '#6c757d',
    preConfirm: () => {
      return finalizarOrden();
    },
    preDeny: () => {
      agregarMasProductos();
      return false;
    }
  }).then((result) => {
    if(result.dismiss === Swal.DismissReason.cancel && ready > 0){
      recogerListos();
    }
  });
}

// Función para agregar más productos a una orden existente
async function agregarMasProductos(){
  mostrarModalAgregarProductos();
}

// Función para recoger items listos
async function recogerListos(){
  try {
    const res = await fetch('api/recoger_listos.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({order_id: currentOrderId})
    });
    
    const data = await res.json();
    
    if(!data.ok){
      Swal.fire('Error', data.error, 'error');
      return;
    }
    
    await Swal.fire({
      icon: 'success',
      title: '¡Items Recogidos!',
      text: `${data.data.items_recogidos} items han sido marcados como recogidos`,
      timer: 2000,
      showConfirmButton: false
    });
    
    // Recargar detalle
    const res2 = await fetch(`api/obtener_detalle_pedido.php?order_id=${currentOrderId}`);
    const data2 = await res2.json();
    if(data2.ok){
      setTimeout(() => mostrarDetalleOrden(data2.data, currentTableName, currentOrderId), 2000);
    }
  } catch(e) {
    Swal.fire('Error', 'Error al recoger items: ' + e.message, 'error');
  }
}

// Función para finalizar orden
async function finalizarOrden(){
  const confirm = await Swal.fire({
    title: '¿Finalizar Orden?',
    text: 'Se generará la cuenta final y se cerrará la mesa',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, Finalizar',
    cancelButtonText: 'Cancelar'
  });
  
  if(!confirm.isConfirmed) return false;
  
  try {
    const res = await fetch('api/finalizar_orden.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({order_id: currentOrderId})
    });
    
    const data = await res.json();
    
    if(!data.ok){
      Swal.fire('Error', data.error, 'error');
      return false;
    }
    
    await Swal.fire({
      icon: 'success',
      title: '¡Orden Finalizada!',
      html: `<p>Total: <strong style="color:var(--gold); font-size:1.5rem;">$${formatPrice(data.data.total)}</strong></p>`,
      confirmButtonText: 'Ver Boleta'
    });
    
    // Abrir boleta
    window.open(`api/imprimir_boleta.php?order_id=${currentOrderId}`, '_blank');
    
    setTimeout(() => location.reload(), 500);
    return false;
  } catch(e) {
    Swal.fire('Error', 'Error al finalizar orden: ' + e.message, 'error');
    return false;
  }
}

// Auto-refresh cada 15 segundos si no hay modales abiertos
setInterval(() => {
  if(!document.querySelector('.swal2-container')) {
    location.reload();
  }
}, 15000);
</script>
</body>
</html>