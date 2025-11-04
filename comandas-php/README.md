🍺 Sistema de Comandas Valhalla - Versión 2.0
Sistema completo de gestión de comandas para restaurantes/bares con flujo operativo real.

✨ Nuevas Características v2.0
🧑‍💼 Panel del Garzón (index.php)
Vista de mesas libres: Grid visual con mesas disponibles
Mesas activas: Seguimiento en tiempo real de cada mesa
Gestión por mesa:
Iniciar nueva mesa (crea pedido automáticamente)
Agregar items al pedido existente
Ver estado de cada item (en preparación/listo)
Recoger items listos (marca como recogidos)
Finalizar orden y cerrar mesa
Estados visuales:
🟢 Mesa libre
🟡 Mesa en servicio
⚫ Mesa cerrada
Indicadores en tiempo real:
Items pendientes de preparación
Items listos para recoger (con efecto pulsante)
Items ya entregados
Tiempo transcurrido
🍺 Panel Bar (barra.php)
Cola de pedidos de bebidas
Sistema de prioridades
Estados: Confirmar → En Proceso → Listo
Auto-refresh cada 5 segundos
Estadísticas en tiempo real
🍔 Panel Cocina (cocina.php)
Cola de pedidos de alimentos
Sistema de urgencias: Marca pedidos >10 minutos como URGENTES
Animación pulsante para prioridades
Contador de tiempo transcurrido
Auto-refresh cada 5 segundos
👨‍💼 Panel Admin (admin.php)
Dashboard completo con estadísticas del día:
Total de pedidos
Pedidos activos
Pedidos cerrados
Ventas totales del día
Gestión de mesas:
Ver estado de todas las mesas
Reabrir mesas cerradas
Historial de órdenes:
Ver todas las órdenes del día
Acceso a boletas generadas
Ver detalles de órdenes activas
Catálogo de productos por área
Accesos rápidos a todas las secciones
🔐 Sistema de Autenticación
Login funcional con validación
Remember Me: Sesión persistente por 30 días
Redirección automática según rol
Cierre de sesión seguro
📋 Flujo Operativo
Flujo del Garzón
Iniciar Mesa:
Selecciona mesa libre
Agrega items al pedido
Sistema crea orden automáticamente
Agregar más items:
Abre detalle de mesa activa
Botón "Agregar más items"
Los nuevos items se suman a la orden existente
Monitoreo:
Ver estado de cada item en tiempo real
Alertas visuales cuando items están listos
Contador de items pendientes
Recoger items:
Cuando items están listos (✅)
Botón "Recoger Items Listos"
Marca automáticamente todos los listos como recogidos
Finalizar:
Cuando todos los items están entregados
Genera boleta automáticamente
Cierra mesa
Imprime cuenta
Flujo Bar/Cocina
Recibe pedido (estado: ⏳ Esperando confirmación)
Confirma pedido (✓ Confirmado)
Marca en proceso (🔄 En preparación)
Marca listo (✅ LISTO)
Garzón recoge (🍽️ Recogido)
Flujo Admin
Monitorea todas las operaciones
Revisa estadísticas en tiempo real
Accede a boletas e historial
Gestiona mesas cerradas
🗂️ Estructura de Archivos
comandas-php/
├── config/
│   └── config.php
├── public/
│   ├── login.php                    # ✨ Login con Remember Me
│   ├── index.php                    # ✨ Panel Garzón (refactorizado)
│   ├── barra.php                    # Panel Bar
│   ├── cocina.php                   # Panel Cocina
│   ├── admin.php                    # ✨ Dashboard Admin completo
│   └── api/
│       ├── login.php
│       ├── logout.php
│       ├── agregar_items_pedido.php # ✨ NUEVO: Agregar items
│       ├── obtener_detalle_pedido.php # ✨ NUEVO: Detalle orden
│       ├── recoger_listos.php       # ✨ NUEVO: Marcar recogidos
│       ├── finalizar_orden.php      # ✨ NUEVO: Cerrar mesa
│       ├── reabrir_mesa.php         # ✨ NUEVO: Reabrir mesa
│       ├── cola.php
│       ├── confirmar_item.php
│       ├── marcar_en_proceso.php
│       ├── marcar_listo.php
│       └── imprimir_boleta.php
├── src/
│   ├── auth.php                     # ✨ Con Remember Me
│   ├── db.php
│   ├── helpers.php
│   └── queries.php
├── bd.sql
├── update_passwords.sql             # ✨ Agregar contraseñas
└── optimizations.sql                # ✨ NUEVO: Optimizaciones BD
🚀 Instalación
1. Clonar repositorio
bash
git clone https://github.com/TU_USUARIO/comandas-php.git
cd comandas-php
2. Configurar base de datos
bash
# Crear BD y estructura
mysql -u root -p < bd.sql

# Agregar contraseñas y token column
mysql -u root -p < update_passwords.sql

# Aplicar optimizaciones
mysql -u root -p < optimizations.sql
3. Configurar conexión
Edita config/config.php:

php
'db' => [
  'dsn'  => 'mysql:host=127.0.0.1;dbname=comandas;charset=utf8mb4',
  'user' => 'tu_usuario',
  'pass' => 'tu_contraseña'
]
4. Permisos
bash
chmod -R 755 public/
5. Acceder
http://localhost/comandas-php/public/login.php
👥 Usuarios de Prueba
Email	Contraseña	Rol	Acceso
ana@example.com	password	waiter	Panel Garzón
barra@example.com	password	bar	Cola Bar
cocina@example.com	password	kitchen	Cola Cocina
admin@example.com	password	admin	Dashboard Admin
🎨 Características Visuales
Fuente: Montserrat (Google Fonts)
Tema: Dark con acentos dorados
Framework: Bootstrap 5.3.2
Responsive: Optimizado para tablets y móviles
Animaciones:
Pulsos para items urgentes
Transiciones suaves
Efectos hover
Glow para items listos
📊 Vistas y Procedimientos SQL
Vistas Disponibles
kitchen_queue: Cola de cocina
bar_queue: Cola de bar
daily_stats: Estadísticas diarias
pending_items_summary: Resumen de pendientes
active_tables_summary: Resumen de mesas activas
Funciones
avg_preparation_time(area): Tiempo promedio de preparación
Procedimientos
cleanup_old_closed_tables(): Limpieza automática de mesas
🔒 Seguridad
Contraseñas hasheadas con bcrypt
Sesiones seguras con tokens SHA-256
Cookies HttpOnly
Validación de roles en cada endpoint
Prepared statements (PDO)
Protección CSRF mediante validaciones
📱 Tecnologías
Backend: PHP 8+
Base de Datos: MySQL 5.7+ / MariaDB
Frontend: HTML5, CSS3, JavaScript (Vanilla)
Framework CSS: Bootstrap 5.3.2
Fuentes: Google Fonts (Montserrat)
🔄 Auto-refresh
Panel Garzón: 15 segundos (si no hay modales abiertos)
Bar/Cocina: 5 segundos
Admin: 30 segundos
📄 Licencia
MIT License - Ver archivo LICENSE

🤝 Contribuir
Fork del proyecto
Crear rama: git checkout -b feature/nueva-funcionalidad
Commit: git commit -m 'feat: agregar funcionalidad'
Push: git push origin feature/nueva-funcionalidad
Pull Request
📞 Soporte
Para reportar bugs o solicitar funcionalidades, crear un issue en GitHub.

Versión 2.0 - Sistema completo con flujo operativo real Desarrollado con ❤️ para Valhalla

