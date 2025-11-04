<?php
session_start();

// Si ya está logueado, redirigir según su rol
if (isset($_SESSION['user_id'])) {
  require_once __DIR__ . '/../src/db.php';
  $user = at("SELECT * FROM users WHERE id=:id AND active=1", [':id'=>$_SESSION['user_id']])->fetch();
  
  if ($user) {
    switch($user['role']) {
      case 'waiter':
        header('Location: index.php');
        exit;
      case 'bar':
        header('Location: barra.php');
        exit;
      case 'kitchen':
        header('Location: cocina.php');
        exit;
      case 'admin':
        header('Location: admin.php');
        exit;
    }
  }
}

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Valhalla - Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <style>
    :root {
      --gold: #d4af37;
      --black: #0b0b0b;
      --dark: #1a1a1a;
    }
    
    body {
      background: linear-gradient(135deg, var(--black) 0%, var(--dark) 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Montserrat', sans-serif;
    }
    
    .login-container {
      width: 100%;
      max-width: 420px;
      padding: 20px;
    }
    
    .login-card {
      background: rgba(17, 17, 17, 0.95);
      border: 1px solid rgba(212, 175, 55, 0.3);
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5),
                  0 0 0 1px rgba(212, 175, 55, 0.1);
      animation: fadeIn 0.5s ease-in-out;
    }
    
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .logo {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .logo h1 {
      color: var(--gold);
      font-weight: 700;
      font-size: 2rem;
      letter-spacing: 0.1em;
      margin: 0;
      text-shadow: 0 0 20px rgba(212, 175, 55, 0.3);
    }
    
    .logo p {
      color: #999;
      font-size: 0.9rem;
      margin-top: 5px;
    }
    
    .form-label {
      color: #cfcfcf;
      font-weight: 500;
      margin-bottom: 8px;
    }
    
    .form-control {
      background: rgba(15, 15, 15, 0.8);
      border: 1px solid rgba(212, 175, 55, 0.2);
      border-radius: 10px;
      padding: 12px 16px;
      color: #fff;
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      background: rgba(15, 15, 15, 0.9);
      border-color: var(--gold);
      color: #fff;
      box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.15);
    }
    
    .form-control::placeholder {
      color: #666;
    }
    
    .btn-login {
      background: transparent;
      border: 2px solid var(--gold);
      color: var(--gold);
      padding: 12px;
      border-radius: 10px;
      font-weight: 600;
      letter-spacing: 0.05em;
      transition: all 0.3s ease;
      width: 100%;
      margin-top: 10px;
    }
    
    .btn-login:hover {
      background: var(--gold);
      color: #000;
      transform: translateY(-2px);
      box-shadow: 0 5px 20px rgba(212, 175, 55, 0.4);
    }
    
    .alert {
      border-radius: 10px;
      border: none;
      background: rgba(220, 53, 69, 0.15);
      border-left: 3px solid #dc3545;
      color: #ff6b6b;
    }
    
    .demo-users {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid rgba(212, 175, 55, 0.2);
    }
    
    .demo-users h6 {
      color: var(--gold);
      font-size: 0.85rem;
      margin-bottom: 10px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    .demo-users small {
      color: #999;
      font-size: 0.8rem;
      display: block;
      line-height: 1.6;
    }
    
    .demo-user-btn {
      background: rgba(26, 26, 26, 0.6);
      border: 1px solid rgba(212, 175, 55, 0.2);
      color: #999;
      padding: 8px 12px;
      border-radius: 8px;
      font-size: 0.75rem;
      cursor: pointer;
      transition: all 0.3s;
      margin: 5px;
      display: inline-block;
    }
    
    .demo-user-btn:hover {
      border-color: var(--gold);
      color: var(--gold);
      background: rgba(212, 175, 55, 0.1);
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
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="logo">
        <h1>VALHALLA</h1>
        <p>Sistema de Comandas</p>
      </div>
      
      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
          <strong>Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      
      <form method="POST" action="api/login.php" id="loginForm">
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input 
            type="email" 
            class="form-control" 
            id="email" 
            name="email" 
            placeholder="usuario@ejemplo.com"
            required 
            autofocus
          >
        </div>
        
        <div class="mb-3">
          <label for="password" class="form-label">Contraseña</label>
          <input 
            type="password" 
            class="form-control" 
            id="password" 
            name="password" 
            placeholder="••••••••"
            required
          >
        </div>
        
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="remember" name="remember" style="border-color: rgba(212, 175, 55, 0.3);">
          <label class="form-check-label" for="remember" style="color: #999; font-size: 0.9rem;">
            Recordar mi sesión
          </label>
        </div>
        
        <button type="submit" class="btn btn-login">
          Iniciar Sesión
        </button>
      </form>
      
      <div class="demo-users">
        <h6>Usuarios de Prueba</h6>
        <div style="text-align:center;">
          <span class="demo-user-btn" onclick="autoLogin('ana@example.com','password')">👨‍💼 Garzón</span>
          <span class="demo-user-btn" onclick="autoLogin('barra@example.com','password')">🍺 Bar</span>
          <span class="demo-user-btn" onclick="autoLogin('cocina@example.com','password')">🍔 Cocina</span>
          <span class="demo-user-btn" onclick="autoLogin('admin@example.com','password')">👨‍💻 Admin</span>
        </div>
        <small style="margin-top:10px; text-align:center;">
          <em>Contraseña: password</em>
        </small>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
  // Función para auto-login con usuarios demo
  function autoLogin(email, password){
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
    document.getElementById('loginForm').submit();
  }
  
  // Mostrar error con SweetAlert2 si existe
  <?php if($error): ?>
  Swal.fire({
    icon: 'error',
    title: 'Error de Autenticación',
    text: '<?= addslashes($error) ?>',
    confirmButtonText: 'Intentar de nuevo'
  });
  <?php endif; ?>
  </script>
</body>
</html>