<?php
// mis_pedidos.php - Página de pedidos del usuario
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php?redirect=mis_pedidos.php');
    exit();
}

require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Obtener pedidos del usuario (simulados por ahora)
$pedidos = [];
$stats = [
    'total_pedidos' => 0,
    'pedidos_completados' => 0,
    'pedidos_pendientes' => 0,
    'total_gastado' => 0.00
];

try {
    // Por ahora simularemos algunos pedidos de ejemplo
    // Cuando implementes el sistema de pedidos real, aquí harás la consulta real
    
    // Simulación de pedidos para demostrar la interfaz
    $pedidos_simulados = [
        [
            'id' => 1,
            'numero_pedido' => 'PED-2024-001',
            'fecha_pedido' => '2024-08-10 14:30:00',
            'estado' => 'entregado',
            'total' => 450.00,
            'items' => [
                ['nombre' => 'Playera Nike Original', 'cantidad' => 2, 'precio' => 150.00],
                ['nombre' => 'Jeans Levis 501', 'cantidad' => 1, 'precio' => 280.00]
            ]
        ],
        [
            'id' => 2,
            'numero_pedido' => 'PED-2024-002',
            'fecha_pedido' => '2024-08-14 09:15:00',
            'estado' => 'en_transito',
            'total' => 95.00,
            'items' => [
                ['nombre' => 'Cuaderno Universitario 100 hojas', 'cantidad' => 2, 'precio' => 25.00],
                ['nombre' => 'Set de Plumas BIC', 'cantidad' => 1, 'precio' => 45.00]
            ]
        ],
        [
            'id' => 3,
            'numero_pedido' => 'PED-2024-003',
            'fecha_pedido' => '2024-08-16 11:45:00',
            'estado' => 'procesando',
            'total' => 180.00,
            'items' => [
                ['nombre' => 'Piñata Tradicional', 'cantidad' => 1, 'precio' => 180.00]
            ]
        ]
    ];
    
    $pedidos = $pedidos_simulados;
    
    // Calcular estadísticas
    $stats['total_pedidos'] = count($pedidos);
    foreach ($pedidos as $pedido) {
        $stats['total_gastado'] += $pedido['total'];
        if ($pedido['estado'] === 'entregado') {
            $stats['pedidos_completados']++;
        } elseif (in_array($pedido['estado'], ['procesando', 'en_transito'])) {
            $stats['pedidos_pendientes']++;
        }
    }
    
} catch (Exception $e) {
    error_log("Error obteniendo pedidos: " . $e->getMessage());
}

// Función para obtener el estado legible
function obtenerEstadoLegible($estado) {
    $estados = [
        'pendiente' => ['texto' => 'Pendiente', 'clase' => 'warning', 'icono' => 'clock'],
        'procesando' => ['texto' => 'Procesando', 'clase' => 'info', 'icono' => 'cog'],
        'en_transito' => ['texto' => 'En Tránsito', 'clase' => 'primary', 'icono' => 'truck'],
        'entregado' => ['texto' => 'Entregado', 'clase' => 'success', 'icono' => 'check-circle'],
        'cancelado' => ['texto' => 'Cancelado', 'clase' => 'danger', 'icono' => 'times-circle']
    ];
    
    return $estados[$estado] ?? ['texto' => 'Desconocido', 'clase' => 'secondary', 'icono' => 'question'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Novedades Ashley</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .orders-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0 40px 0;
        }
        
        .orders-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            border-left: 5px solid #667eea;
        }
        
        .orders-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            transform: translateY(-3px);
        }
        
        .stat-card {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            height: 100%;
            border: none;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .order-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .order-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }
        
        .order-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .order-total {
            font-size: 1.3rem;
            font-weight: bold;
            color: #28a745;
        }
        
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
        }
        
        .item-row {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .empty-orders {
            text-align: center;
            padding: 80px 20px;
        }
        
        .empty-orders i {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .btn-action {
            border-radius: 20px;
            padding: 8px 20px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        .filter-tabs {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .breadcrumb {
            background: none;
            margin-bottom: 0;
        }
        
        @media (max-width: 768px) {
            .orders-header {
                padding: 40px 0 20px 0;
            }
            
            .orders-card {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .order-header {
                text-align: center;
            }
            
            .stat-card {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-crown me-2"></i> Novedades Ashley
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="productos.php">Productos</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="carrito.php">
                            <i class="fas fa-shopping-cart"></i> Carrito
                            <span class="badge bg-danger d-none" id="cart-count">0</span>
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="perfil.php">Mi Perfil</a></li>
                            <li><a class="dropdown-item active" href="mis_pedidos.php">Mis Pedidos</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="cerrarSesion()">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header de pedidos -->
    <section class="orders-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php" class="text-light">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="perfil.php" class="text-light">Mi Perfil</a></li>
                    <li class="breadcrumb-item active text-light">Mis Pedidos</li>
                </ol>
            </nav>
            
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 mb-3">
                        <i class="fas fa-history me-3"></i> Mis Pedidos
                    </h1>
                    <p class="lead">Revisa el estado y detalles de todos tus pedidos</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="bg-white text-dark rounded p-3 d-inline-block">
                        <h3 class="mb-0"><?= $stats['total_pedidos'] ?></h3>
                        <small>Pedidos Total</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Estadísticas -->
    <section class="py-4 bg-light">
        <div class="container">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['total_pedidos'] ?></div>
                        <small class="text-muted"><i class="fas fa-shopping-bag"></i> Total Pedidos</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['pedidos_completados'] ?></div>
                        <small class="text-muted"><i class="fas fa-check-circle"></i> Completados</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['pedidos_pendientes'] ?></div>
                        <small class="text-muted"><i class="fas fa-clock"></i> Pendientes</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">$<?= number_format($stats['total_gastado'], 0) ?></div>
                        <small class="text-muted"><i class="fas fa-dollar-sign"></i> Total Gastado</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contenido de pedidos -->
    <section class="py-5">
        <div class="container">
            <?php if (count($pedidos) > 0): ?>
                <!-- Filtros de estado -->
                <div class="filter-tabs">
                    <div class="d-flex justify-content-center">
                        <div class="btn-group" role="group" aria-label="Filtros de estado">
                            <button type="button" class="btn btn-outline-primary active" onclick="filtrarPedidos('todos')">
                                <i class="fas fa-list"></i> Todos (<?= count($pedidos) ?>)
                            </button>
                            <button type="button" class="btn btn-outline-warning" onclick="filtrarPedidos('pendiente')">
                                <i class="fas fa-clock"></i> Pendientes
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="filtrarPedidos('procesando')">
                                <i class="fas fa-cog"></i> Procesando
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="filtrarPedidos('en_transito')">
                                <i class="fas fa-truck"></i> En Tránsito
                            </button>
                            <button type="button" class="btn btn-outline-success" onclick="filtrarPedidos('entregado')">
                                <i class="fas fa-check-circle"></i> Entregados
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lista de pedidos -->
                <div id="pedidos-container">
                    <?php foreach ($pedidos as $pedido): ?>
                        <?php $estado_info = obtenerEstadoLegible($pedido['estado']); ?>
                        <div class="orders-card" data-estado="<?= $pedido['estado'] ?>">
                            <div class="order-header">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <div class="order-number"><?= htmlspecialchars($pedido['numero_pedido']) ?></div>
                                        <div class="order-date">
                                            <i class="fas fa-calendar"></i> 
                                            <?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <span class="badge bg-<?= $estado_info['clase'] ?> status-badge">
                                            <i class="fas fa-<?= $estado_info['icono'] ?>"></i> 
                                            <?= $estado_info['texto'] ?>
                                        </span>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <div class="order-total">$<?= number_format($pedido['total'], 2) ?></div>
                                        <small class="text-muted"><?= count($pedido['items']) ?> artículo(s)</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Detalles del pedido -->
                            <div class="order-details">
                                <h6 class="mb-3"><i class="fas fa-list"></i> Artículos del pedido:</h6>
                                
                                <?php foreach ($pedido['items'] as $item): ?>
                                    <div class="item-row">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <strong><?= htmlspecialchars($item['nombre']) ?></strong>
                                            </div>
                                            <div class="col-md-2 text-center">
                                                <span class="badge bg-light text-dark">
                                                    Qty: <?= $item['cantidad'] ?>
                                                </span>
                                            </div>
                                            <div class="col-md-2 text-center">
                                                $<?= number_format($item['precio'], 2) ?> c/u
                                            </div>
                                            <div class="col-md-2 text-end">
                                                <strong>$<?= number_format($item['precio'] * $item['cantidad'], 2) ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Acciones del pedido -->
                            <div class="order-actions mt-4 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            ID del pedido: #<?= $pedido['id'] ?>
                                        </small>
                                    </div>
                                    <div>
                                        <button class="btn btn-outline-primary btn-action btn-sm me-2" 
                                                onclick="verDetallesPedido(<?= $pedido['id'] ?>)">
                                            <i class="fas fa-eye"></i> Ver Detalles
                                        </button>
                                        
                                        <?php if ($pedido['estado'] === 'entregado'): ?>
                                            <button class="btn btn-outline-success btn-action btn-sm me-2" 
                                                    onclick="descargarFactura(<?= $pedido['id'] ?>)">
                                                <i class="fas fa-download"></i> Factura
                                            </button>
                                            <button class="btn btn-outline-warning btn-action btn-sm" 
                                                    onclick="volverAComprar(<?= $pedido['id'] ?>)">
                                                <i class="fas fa-redo"></i> Volver a Comprar
                                            </button>
                                        <?php elseif (in_array($pedido['estado'], ['pendiente', 'procesando'])): ?>
                                            <button class="btn btn-outline-danger btn-action btn-sm" 
                                                    onclick="cancelarPedido(<?= $pedido['id'] ?>)">
                                                <i class="fas fa-times"></i> Cancelar
                                            </button>
                                        <?php elseif ($pedido['estado'] === 'en_transito'): ?>
                                            <button class="btn btn-outline-info btn-action btn-sm" 
                                                    onclick="rastrearPedido('<?= $pedido['numero_pedido'] ?>')">
                                                <i class="fas fa-map-marker-alt"></i> Rastrear
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else: ?>
                <!-- Sin pedidos -->
                <div class="empty-orders">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>No tienes pedidos aún</h3>
                    <p class="text-muted mb-4">
                        ¡Es un buen momento para explorar nuestros productos y hacer tu primera compra!
                    </p>
                    <a href="productos.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-cart me-2"></i> Explorar Productos
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container text-center">
            <p class="mb-0">
                <a href="index.php" class="text-light text-decoration-none">
                    <i class="fas fa-crown me-2"></i> Novedades Ashley
                </a>
                - "Descubre lo nuevo, siente la diferencia"
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Carrito JS -->
    <script src="assets/js/carrito.js"></script>
    
    <script>
        // Función para filtrar pedidos por estado
        function filtrarPedidos(estado) {
            const pedidos = document.querySelectorAll('.orders-card');
            const botones = document.querySelectorAll('.btn-group .btn');
            
            // Actualizar botones activos
            botones.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filtrar pedidos
            pedidos.forEach(pedido => {
                const estadoPedido = pedido.getAttribute('data-estado');
                
                if (estado === 'todos' || estadoPedido === estado) {
                    pedido.style.display = 'block';
                    // Animación de entrada
                    pedido.style.opacity = '0';
                    pedido.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        pedido.style.transition = 'all 0.3s ease';
                        pedido.style.opacity = '1';
                        pedido.style.transform = 'translateY(0)';
                    }, 100);
                } else {
                    pedido.style.display = 'none';
                }
            });
        }
        
        // Función para ver detalles del pedido
        function verDetallesPedido(idPedido) {
            alert(`Ver detalles completos del pedido #${idPedido}\n\n` +
                  'Esta función mostraría:\n' +
                  '• Información de envío\n' +
                  '• Método de pago\n' +
                  '• Historial de estados\n' +
                  '• Información de contacto\n\n' +
                  'Por implementar en el futuro.');
        }
        
        // Función para descargar factura
        function descargarFactura(idPedido) {
            alert(`Descargando factura del pedido #${idPedido}\n\n` +
                  'Esta función generaría y descargaría un PDF con:\n' +
                  '• Datos fiscales\n' +
                  '• Detalles del pedido\n' +
                  '• Información de pago\n\n' +
                  'Por implementar en el futuro.');
        }
        
        // Función para volver a comprar
        function volverAComprar(idPedido) {
            if (confirm('¿Quieres agregar todos los productos de este pedido a tu carrito actual?')) {
                alert(`Agregando productos del pedido #${idPedido} al carrito...\n\n` +
                      'Esta función agregaría automáticamente todos los productos\n' +
                      'del pedido seleccionado a tu carrito actual.\n\n' +
                      'Por implementar en el futuro.');
            }
        }
        
        // Función para cancelar pedido
        function cancelarPedido(idPedido) {
            if (confirm('¿Estás seguro de que quieres cancelar este pedido?\n\nEsta acción no se puede deshacer.')) {
                alert(`Cancelando pedido #${idPedido}...\n\n` +
                      'Esta función:\n' +
                      '• Cambiaría el estado a "Cancelado"\n' +
                      '• Procesaría el reembolso si aplica\n' +
                      '• Enviaría notificación por email\n\n' +
                      'Por implementar en el futuro.');
            }
        }
        
        // Función para rastrear pedido
        function rastrearPedido(numeroPedido) {
            alert(`Rastreando pedido ${numeroPedido}\n\n` +
                  'Esta función mostraría:\n' +
                  '• Ubicación actual del paquete\n' +
                  '• Historial de movimientos\n' +
                  '• Fecha estimada de entrega\n' +
                  '• Información de la paquetería\n\n' +
                  'Por implementar en el futuro.');
        }
        
        // Función para cerrar sesión
        async function cerrarSesion() {
            if (!confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'logout');
                
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Sesión cerrada correctamente');
                    window.location.href = 'index.php';
                } else {
                    alert('Error al cerrar sesión: ' + data.message);
                }
                
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cerrar sesión');
            }
        }
        
        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar contador del carrito
            if (typeof actualizarContadorCarrito === 'function') {
                actualizarContadorCarrito();
            }
            
            // Animación de entrada para las cards
            const cards = document.querySelectorAll('.orders-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Animación para las estadísticas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.4s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });
    </script>
</body>
</html>