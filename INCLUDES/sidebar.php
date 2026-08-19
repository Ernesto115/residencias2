<?php
// Asegurar que la sesión esté iniciada para leer el rol de usuario
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Normalizamos el rol: a mayúsculas y sin espacios
$rolUsuario = strtoupper(trim($_SESSION['rol'] ?? ''));
$nombreUsuario = $_SESSION['nombre_usuario'] ?? 'Usuario';

// Definimos variantes permitidas para cada rol
$rolesEmpresas   = ['ADMIN', 'ADMINISTRADOR', 'PROPIETARIO'];
$rolesOperadores = ['ADMIN', 'ADMINISTRADOR', 'PROPIETARIO', 'RRHH', 'RH', 'RECURSOS HUMANOS'];
$rolesUsuarios   = ['ADMIN', 'ADMINISTRADOR'];
$rolesReportes   = ['ADMIN', 'ADMINISTRADOR', 'PROPIETARIO', 'RRHH', 'RH', 'RECURSOS HUMANOS'];
?>

<aside class="sidebar p-3">
    <div class="sidebar-header mb-4">
        <h4 class="mb-1">Menú Principal</h4>
        <small class="text-muted">
            <strong><?php echo htmlspecialchars($nombreUsuario); ?></strong> 
            (<?php echo htmlspecialchars($rolUsuario); ?>)
        </small>
    </div>

    <ul class="nav-links list-unstyled">

        <!-- =========================================================
             MÓDULO: EMPRESAS (Solo ADMIN y PROPIETARIO)
             ========================================================= -->
        <?php if (in_array($rolUsuario, $rolesEmpresas)): ?>
            <li class="mb-2">
                <a href="javascript:void(0);" onclick="ver('empresas/index.php')" class="nav-link">
                    <span>🏢</span> Empresas
                </a>
            </li>
        <?php endif; ?>


        <!-- =========================================================
             MÓDULO: OPERADORES (ADMIN, PROPIETARIO y RRHH)
             ========================================================= -->
        <?php if (in_array($rolUsuario, $rolesOperadores)): ?>
            <li class="mb-2">
                <a href="javascript:void(0);" onclick="ver('operadores/index.php')" class="nav-link">
                    <span>👷</span> Operadores
                </a>
            </li>
        <?php endif; ?>


        <!-- =========================================================
             MÓDULO: USUARIOS (Exclusivo para ADMIN)
             ========================================================= -->
        <?php if (in_array($rolUsuario, $rolesUsuarios)): ?>
            <li class="mb-2">
                <a href="javascript:void(0);" onclick="ver('usuarios/index.php')" class="nav-link">
                    <span>👤</span> Usuarios
                </a>
            </li>
        <?php endif; ?>


        <!-- =========================================================
             MÓDULO: REPORTE DE BAJA (ADMIN, PROPIETARIO y RRHH)
             ========================================================= -->
        <?php if (in_array($rolUsuario, $rolesReportes)): ?>
            <li class="mb-2">
                <a href="javascript:void(0);" onclick="ver('reporte_baja/index.php')" class="nav-link">
                    <span>📄</span> Reporte de Bajas
                </a>
            </li>
        <?php endif; ?>


        <hr class="my-3">

        <!-- =========================================================
             OPCIÓN GENERAL: CERRAR SESIÓN
             ========================================================= -->
        <li class="logout-link">
            <!-- La barra '/' al inicio garantiza ir a la raíz del servidor -->
            <a href="/autentificacion/logout.php" class="nav-link text-danger fw-bold">
                <span>🚪</span> Cerrar Sesión
            </a>
        </li>

    </ul>
</aside>