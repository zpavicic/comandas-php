<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/queries.php';
require_once __DIR__ . '/../src/helpers.php';

$u = require_user(['kitchen','admin']);
$items = queues('kitchen');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Valhalla · Cocina</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<style>
  :root{
    --gold:#d4af37; --black:#0b0b0b; --dark:#1a1a1a; --white:#fff; --muted:#999;
    --orange:#ff6b35;
  }
  body{
    margin:0;
    font-family:'Montserrat',sans-serif;
    background:var(--black);
    color:var(--white);
    min-height:100vh;
  }
  .navbar{
    background:var(--dark)!important;
    border-bottom:1px solid rgba(255,107,53,.2);
    padding:1rem 0;
  }
  .navbar-brand{
    color:var(--orange)!important;
    font-weight:700;
    letter-spacing:.08em;
    font-size:1.5rem;
  }
  .user-badge{
    background:rgba(255,107,53,.1);
    border:1px solid rgba(255,107,53,.3);
    border-radius:20px;
    padding:8px 16px;
    color:var(--orange);
    font-size:.85rem;
    font-weight:500;
  }
  .btn-logout{
    border:1px solid var(--orange);
    color:var(--orange);
    background:transparent;
    border-radius:8px;
    padding:6px 16px;
    font-weight:500;
    transition:all .3s;
  }
  .btn-logout:hover{
    background:var(--orange);
    color:#000;
  }
  .btn-nav{
    border:1px solid rgba(255,107,53,.3);
    color:var(--orange);
    background:transparent;
    border-radius:8px;
    padding:6px 12px;
    font-weight:500;
    transition:all .3s;
    text-decoration:none;
    font-size:.85rem;
  }
  .btn-nav:hover{
    background:rgba(255,107,53,.1);
    color:var(--orange);
    border-color:var(--orange);
  }
  .queue-header{
    background:linear-gradient(135deg,rgba(255,107,53,.15),rgba(255,107,53,.05));
    border:2px solid rgba(255,107,53,.3);
    border-radius:15px;
    padding:1.5rem;
    margin-bottom:1.5rem;
    text-align:center;
  }
  .queue-header h1{
    color:var(--orange);
    font-weight:700;
    margin:0;
    font-size:2rem;
  }
  .queue-header p{
    color:var(--muted);
    margin:.5rem 0 0 0;
    font-size:.9rem;
  }
  .queue-item{
    background:linear-gradient(135deg,rgba(17,17,17,.95),rgba(26,26,26,.95));
    border:1px solid rgba(255,107,53,.2);
    border-left:4px solid var(--orange);
    border-radius:12px;
    padding:1.5rem;
    margin-bottom:1rem;
    transition:all .3s;
    box-shadow:0 4px 15px rgba(0,0,0,.2);
  }
  .queue-item:hover{
    border-color:rgba(255,107,53,.5);
    transform:translateY(-3px);
    box-shadow:0 6px 25px rgba(0,0,0,.4);
  }
  .queue-main{
    color:var(--white);
    font-weight:600;
    margin-bottom:.8rem;
    font-size:1.1rem;
  }
  .queue-meta{
    color:var(--muted);
    font-size:.85rem;
    margin-bottom:.8rem;
    display:flex;
    gap:1rem;
    flex-wrap:wrap;
  }
  .queue-notes{
    background:rgba(255,107,53,.08);
    border-left:3px solid var(--orange);
    color:#ddd;
    font-style:italic;
    font-size:.9rem;
    padding:.8rem 1rem;
    margin-bottom:1rem;
    border-radius:6px;
  }
  .status-badge{
    display:inline-block;
    padding:.4em .8em;
    border-radius:6px;
    font-weight:600;
    font-size:.85rem;
    text-transform:uppercase;
    letter-spacing:.05em;
  }
  .status-awaiting_confirmation{background:rgba(255,193,7,.2);color:#ffc107;border:1px solid rgba(255,193,7,.4);}
  .status-confirmed{background:rgba(13,110,253,.2);color:#5ba3ff;border:1px solid rgba(13,110,253,.4);}
  .status-in_progress{background:rgba(220,53,69,.2);color:#ff6b6b;border:1px solid rgba(220,53,69,.4);}
  .status-ready{background:rgba(25,135,84,.2);color:#5ddb8e;border:1px solid rgba(25,135,84,.4);}
  .btn-action{
    border:2px solid var(--orange);
    color:var(--orange);
    background:transparent;
    border-radius:10px;
    padding:.6rem 1.2rem;
    font-weight:600;
    transition:all .3s;
    font-size:.9rem;
  }
  .btn-action:hover{
    background:var(--orange);
    color:#000;
    transform:translateY(-2px);
  }
  .btn-listo{
    border:2px solid #28a745;
    color:#5ddb8e;
    background:rgba(40,167,69,.1);
  }
  .btn-listo:hover{
    background:#28a745;
    color:#000;
  }
  .empty{
    text-align:center;
    padding:3rem;
    color:var(--muted);
    font-style:italic;
    font-size:1.1rem;
  }
  .stats{
    display:flex;
    gap:1rem;
    justify-content:center;
    flex-wrap:wrap;
    margin-top:1rem;
  }
  .stat-item{
    background:rgba(26,26,26,.6);
    border:1px solid rgba(255,107,53,.15);
    border-radius:10px;
    padding:.8rem 1.5rem;
    text-align:center;
  }
  .stat-number{
    font-size:1.8rem;
    font-weight:700;
    color:var(--orange);
  }
  .stat-label{
    color:var(--muted);
    font-size:.8rem;
    text-transform:uppercase;
  }
  .priority-high{
    border-left-color:#dc3545!important;
    animation:pulse 2s infinite;
  }
  @keyframes pulse{
    0%,100%{box-shadow:0 4px 15px rgba(0,0,0,.2)}
    50%{box-shadow:0 6px 25px rgba(220,53,69,.4)}
  }
  
  /* SweetAlert2 personalizado */
  .swal2-popup{
    background:rgba(17,17,17,.98)!important;
    border:1px solid rgba(255,107,53,.3)!important;
    color:#fff!important;
  }
  .swal2-title{
    color:var(--orange)!important;
  }
  .swal2-html-container{
    color:#ddd!important;
  }
  .swal2-confirm{
    background:var(--orange)!important;
    color:#000!important;
    font-weight:600!important;
  }
  .swal2-cancel{
    background:rgba(220,53,69,.2)!important;
    border:1px solid rgba(220,53,69,.4)!important;
    color:#ff6b6b!important;
  }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <span class="navbar-brand">🍔 VALHALLA · Cocina</span>
    <div class="d-flex align-items-center gap-3">
      <?php if($u['role'] === 'admin'): ?>
        <a href="index.php" class="btn-nav">👨‍💼 Garzón</a>
        <a href="barra.php" class="btn-nav">🍺 Bar</a>
        <a href="admin.php" class="btn-nav">📊 Admin</a>
      <?php endif; ?>
      <div class="user-badge">
        👤 <?=h($u['name'])?> <small>(<?=h($u['role'])?>)</small>
      </div>
      <a href="api/logout.php" class="btn btn-logout btn-sm">Cerrar Sesión</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="queue-header">
    <h1>🍔 Cola de Pedidos - COCINA</h1>
    <p>Gestiona los pedidos de alimentos en tiempo real</p>
    <div class="stats">
      <div class="stat-item">
        <div class="stat-number" id="totalItems"><?=count($items)?></div>
        <div class="stat-label">Total</div>
      </div>
      <div class="stat-item">
        <div class="stat-number" id="pendingItems">
          <?=count(array_filter($items, fn($i)=>$i['status']==='awaiting_confirmation'))?>
        </div>
        <div class="stat-label">Pendientes</div>
      </div>
      <div class="stat-item">
        <div class="stat-number" id="processingItems">
          <?=count(array_filter($items, fn($i)=>in_array($i['status'],['confirmed','in_progress'])))?>
        </div>
        <div class="stat-label">En Proceso</div>
      </div>
    </div>
  </div>

  <div id="queueContainer">
    <?php if(!$items): ?>
      <div class="empty">
        ✨ No hay pedidos pendientes<br>
        <small>Los nuevos pedidos aparecerán aquí automáticamente</small>
      </div>
    <?php else: ?>
      <?php foreach($items as $r): 
        // Marcar como prioritario si tiene más de 10 minutos
        $isPriority = (time() - strtotime($r['created_at'])) > 600;
      ?>
        <div class="queue-item <?=$isPriority?'priority-high':''?>" data-item-id="<?=$r['order_item_id']?>">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="queue-main">
              Mesa <?=h($r['table_label'])?> · <?=h($r['product'])?> × <?=$r['qty']?>
            </div>
            <?php if($isPriority): ?>
              <span class="badge bg-danger">⚠️ URGENTE</span>
            <?php endif; ?>
          </div>
          
          <div class="queue-meta">
            <span>📋 Pedido #<?=$r['order_id']?></span>
            <span>🕐 <?=date('H:i', strtotime($r['created_at']))?></span>
            <span>⏱️ Hace <?=floor((time()-strtotime($r['created_at']))/60)?> min</span>
            <span class="status-badge status-<?=$r['status']?>">
              <?php
                $status_names = [
                  'awaiting_confirmation' => 'Pendiente',
                  'confirmed' => 'En Proceso',
                  'in_progress' => 'En Proceso',
                  'ready' => 'Listo'
                ];
                echo $status_names[$r['status']] ?? h($r['status']);
              ?>
            </span>
          </div>

          <?php if($r['notes']): ?>
            <div class="queue-notes">
              💬 <strong>Nota:</strong> <?=h($r['notes'])?>
            </div>
          <?php endif; ?>

          <div class="d-flex gap-2 flex-wrap">
            <?php if(in_array($r['status'], ['awaiting_confirmation','confirmed'])): ?>
              <button class="btn btn-action" onclick="marcarEnProceso(<?=$r['order_item_id']?>)">
                🔄 Confirmar y Preparar
              </button>
            <?php endif; ?>
            
            <?php if(in_array($r['status'], ['awaiting_confirmation','confirmed','in_progress'])): ?>
              <button class="btn btn-action btn-listo" onclick="marcarListo(<?=$r['order_item_id']?>)">
                ✅ Marcar Listo
              </button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
async function postApi(url, payload) {
  const body = new URLSearchParams();
  for (const [k, v] of Object.entries(payload)) body.append(k, v);
  const res = await fetch(url, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body });
  let data; try { data = await res.json(); } catch { throw new Error(await res.text()); }
  if (!res.ok || data.ok === false) throw new Error(data.error || data.msg || 'Error');
  return data;
}

async function marcarEnProceso(itemId){
  try {
    await postApi('api/marcar_en_proceso.php', { item_id:String(itemId), area:'kitchen' });
    Swal.fire({icon:'success',title:'¡Confirmado!',text:'El pedido está ahora en preparación',timer:1500,showConfirmButton:false});
    setTimeout(()=>location.reload(),1500);
  } catch(e) {
    Swal.fire('Error','Error al confirmar: '+e.message,'error');
  }
}

async function marcarListo(itemId){
  try {
    await postApi('api/marcar_listo.php', { item_id:String(itemId), area:'kitchen' });
    Swal.fire({icon:'success',title:'¡Listo!',text:'El pedido está listo para recoger',timer:1500,showConfirmButton:false});
    setTimeout(()=>location.reload(),1500);
  } catch(e) {
    Swal.fire('Error','Error al marcar listo: '+e.message,'error');
  }
}

// Auto-refresh cada 5 s, salvo que haya un modal abierto
setInterval(() => {
  if (!document.querySelector('.swal2-container')) location.reload();
}, 5000);

// Aviso simple si hay urgentes
if (document.querySelectorAll('.priority-high').length > 0) {
  console.log('⚠️ Hay pedidos urgentes!');
}
</script>
</body>
</html>