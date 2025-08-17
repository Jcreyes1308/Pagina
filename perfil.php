<?php
// perfil.php - Página de perfil del usuario
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php?redirect=perfil.php');
    exit();
}

require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Obtener datos actuales del usuario
try {
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        // Usuario no encontrado - cerrar sesión
        session_destroy();
        header('Location: login.php');
        exit();
    }
} catch (Exception $e) {
    $error = 'Error al cargar perfil: ' . $e->getMessage();
    $usuario = [];
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $nombre = trim($_POST['nombre'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        
        if (empty($nombre)) {
            $error = 'El nombre es requerido';
        } else {
            try {
                $stmt = $conn->prepare("UPDATE clientes SET nombre = ?, telefono = ?, direccion = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$nombre, $telefono, $direccion, $_SESSION['usuario_id']]);
                
                // Actualizar sesión
                $_SESSION['usuario_nombre'] = $nombre;
                $_SESSION['usuario_telefono'] = $telefono;
                
                $success = 'Perfil actualizado correctamente';
                
                // Recargar datos
                $usuario['nombre'] = $nombre;
                $usuario['telefono'] = $telefono;
                $usuario['direccion'] = $direccion;
                
            } catch (Exception $e) {
                $error = 'Error al actualizar perfil: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Todos los campos de contraseña son requeridos';
        } elseif (strlen($new_password) < 6) {
            $error = 'La nueva contraseña debe tener al menos 6 caracteres';
        } elseif ($new_password !== $confirm_password) {
            $error = 'La nueva contraseña y su confirmación no coinciden';
        } elseif (!password_verify($current_password, $usuario['password'])) {
            $error = 'La contraseña actual es incorrecta';
        } else {
            try {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE clientes SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_password_hash, $_SESSION['usuario_id']]);
                
                $success = 'Contraseña actualizada correctamente';
                
            } catch (Exception $e) {
                $error = 'Error al cambiar contraseña: ' . $e->getMessage();
            }
        }
    }
}

// Obtener estadísticas del usuario
$stats = [
    'pedidos_total' => 0,
    'pedidos_pendientes' => 0,
    'total_gastado' => 0,
    'items_carrito' => 0
];

try {
    // Contar items en carrito
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(cantidad), 0) as total 
        FROM carrito_compras 
        WHERE id_cliente = ?
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $result = $stmt->fetch();
    $stats['items_carrito'] = $result['total'];
    
    // Aquí podrías agregar más estadísticas cuando implementes el sistema de pedidos
    
} catch (Exception $e) {
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Novedades Ashley</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0 40px 0;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }
        
        .profile-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            margin: 0 auto 20px auto;
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
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-update {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: bold;
            transition: all 0.3s ease;
            color: white;
        }
        
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .section-title {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .info-item {
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .breadcrumb {
            background: none;
            margin-bottom: 0;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: #6c757d;
            cursor: pointer;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                padding: 40px 0 20px 0;
            }
            
            .profile-card {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
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
                            <li><a class="dropdown-item active" href="perfil.php">Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="mis_pedidos.php">Mis Pedidos</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="cerrarSesion()">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header del perfil -->
    <section class="profile-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php" class="text-light">Inicio</a></li>
                    <li class="breadcrumb-item active text-light">Mi Perfil</li>
                </ol>
            </nav>
            
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 mb-3">
                        <i class="fas fa-user-circle me-3"></i> Mi Perfil
                    </h1>
                    <p class="lead">Gestiona tu información personal y configuración de cuenta</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="avatar">
                        <?= strtoupper(substr($usuario['nombre'] ?? 'U', 0, 1)) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contenido del perfil -->
    <section class="py-5">
        <div class="container">
            <!-- Alertas -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Panel izquierdo - Información del usuario -->
                <div class="col-lg-4">
                    <!-- Información básica -->
                    <div class="profile-card">
                        <div class="text-center mb-4">
                            <div class="avatar" style="margin: 0 auto 20px auto;">
                                <?= strtoupper(substr($usuario['nombre'] ?? 'U', 0, 1)) ?>
                            </div>
                            <h4><?= htmlspecialchars($usuario['nombre'] ?? '') ?></h4>
                            <p class="text-muted"><?= htmlspecialchars($usuario['email'] ?? '') ?></p>
                            <small class="text-muted">
                                Miembro desde: <?= date('d/m/Y', strtotime($usuario['created_at'] ?? 'now')) ?>
                            </small>
                        </div>
                        
                        <div class="info-item">
                            <strong><i class="fas fa-phone me-2"></i> Teléfono:</strong><br>
                            <span class="text-muted">
                                <?= $usuario['telefono'] ? htmlspecialchars($usuario['telefono']) : 'No especificado' ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <strong><i class="fas fa-map-marker-alt me-2"></i> Dirección:</strong><br>
                            <span class="text-muted">
                                <?= $usuario['direccion'] ? htmlspecialchars($usuario['direccion']) : 'No especificada' ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <strong><i class="fas fa-shield-alt me-2"></i> Estado:</strong><br>
                            <span class="badge bg-success">Cuenta Activa</span>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="profile-card">
                        <h5 class="section-title">
                            <i class="fas fa-chart-bar"></i> Mis Estadísticas
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-number"><?= $stats['pedidos_total'] ?></div>
                                    <small class="text-muted">Pedidos Total</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-number"><?= $stats['items_carrito'] ?></div>
                                    <small class="text-muted">En Carrito</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-number">$<?= number_format($stats['total_gastado'], 0) ?></div>
                                    <small class="text-muted">Total Gastado</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-number"><?= $stats['pedidos_pendientes'] ?></div>
                                    <small class="text-muted">Pendientes</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel derecho - Formularios de edición -->
                <div class="col-lg-8">
                    <!-- Editar información personal -->
                    <div class="profile-card">
                        <h5 class="section-title">
                            <i class="fas fa-edit"></i> Editar Información Personal
                        </h5>
                        
                        <form method="POST" action="" id="profileForm">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="nombre" class="form-label">
                                        <i class="fas fa-user"></i> Nombre Completo *
                                    </label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" 
                                           required maxlength="100">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email_display" class="form-label">
                                        <i class="fas fa-envelope"></i> Correo Electrónico
                                    </label>
                                    <input type="email" class="form-control" id="email_display" 
                                           value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" 
                                           readonly disabled>
                                    <small class="text-muted">
                                        Para cambiar tu email, contacta al administrador
                                    </small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label">
                                        <i class="fas fa-phone"></i> Teléfono
                                    </label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" 
                                           value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>" 
                                           maxlength="20" placeholder="555-0123">
                                </div>
                                
                                <div class="col-12 mb-4">
                                    <label for="direccion" class="form-label">
                                        <i class="fas fa-map-marker-alt"></i> Dirección
                                    </label>
                                    <textarea class="form-control" id="direccion" name="direccion" 
                                              rows="3" maxlength="200" 
                                              placeholder="Tu dirección completa..."><?= htmlspecialchars($usuario['direccion'] ?? '') ?></textarea>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-update">
                                <i class="fas fa-save"></i> Actualizar Información
                            </button>
                        </form>
                    </div>

                    <!-- Cambiar contraseña -->
                    <div class="profile-card">
                        <h5 class="section-title">
                            <i class="fas fa-lock"></i> Cambiar Contraseña
                        </h5>
                        
                        <form method="POST" action="" id="passwordForm">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="current_password" class="form-label">
                                        <i class="fas fa-key"></i> Contraseña Actual *
                                    </label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" id="current_password" 
                                               name="current_password" required>
                                        <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                            <i class="fas fa-eye" id="current_password-icon"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="new_password" class="form-label">
                                        <i class="fas fa-lock"></i> Nueva Contraseña *
                                    </label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" id="new_password" 
                                               name="new_password" required minlength="6">
                                        <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                            <i class="fas fa-eye" id="new_password-icon"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Mínimo 6 caracteres</small>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-lock"></i> Confirmar Nueva Contraseña *
                                    </label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" id="confirm_password" 
                                               name="confirm_password" required minlength="6">
                                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye" id="confirm_password-icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-shield-alt"></i> Cambiar Contraseña
                            </button>
                        </form>
                    </div>

                    <!-- Acciones de cuenta -->
                    <div class="profile-card">
                        <h5 class="section-title">
                            <i class="fas fa-cog"></i> Acciones de Cuenta
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="carrito.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-shopping-cart"></i> Ver Mi Carrito
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="mis_pedidos.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-history"></i> Historial de Pedidos
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="productos.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-shopping-bag"></i> Continuar Comprando
                                </a>
                            </div>
                            <div class="col-md-6">
                                <button onclick="cerrarSesion()" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
        // Función para mostrar/ocultar contraseña
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const passwordIcon = document.getElementById(fieldId + '-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
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
        
        // Validación del formulario de perfil
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            
            if (!nombre) {
                e.preventDefault();
                alert('El nombre es requerido');
                return;
            }
            
            // Mostrar loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            submitBtn.disabled = true;
            
            // Restaurar botón si no se procesa
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 5000);
        });
        
        // Validación del formulario de contraseña
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (!currentPassword || !newPassword || !confirmPassword) {
                e.preventDefault();
                alert('Todos los campos de contraseña son requeridos');
                return;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('La nueva contraseña debe tener al menos 6 caracteres');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('La nueva contraseña y su confirmación no coinciden');
                return;
            }
            
            // Mostrar loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cambiando...';
            submitBtn.disabled = true;
            
            // Restaurar botón si no se procesa
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 5000);
        });
        
        // Validación en tiempo real de contraseñas
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword.length > 0) {
                if (newPassword === confirmPassword) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
        
        // Formatear teléfono
        document.getElementById('telefono').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 10) {
                value = value.substring(0, 10);
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
            }
            e.target.value = value;
        });
        
        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar contador del carrito
            if (typeof actualizarContadorCarrito === 'function') {
                actualizarContadorCarrito();
            }
            
            // Animación de entrada
            const cards = document.querySelectorAll('.profile-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Auto-ocultar alertas después de 5 segundos
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    if (alert.classList.contains('show')) {
                        alert.classList.remove('show');
                        setTimeout(() => alert.remove(), 150);
                    }
                });
            }, 5000);
        });
    </script>
</body>
</html>