<?php

/* =========================================================
   1. SESIÓN Y SEGURIDAD
   ========================================================= */

require_once "configuracion/sesion.php";
verificarSesion();

// Evita inyecciones de código HTML
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   2. DATOS DEL USUARIO
   ========================================================= */

$rol = strtoupper(trim($_SESSION['rol'] ?? ''));
$nombreUsuario = $_SESSION['nombre_usuario'] ?? 'Usuario';

// Obtener la primera letra del nombre para el avatar
$inicialUsuario = mb_strtoupper(
    mb_substr(trim($nombreUsuario), 0, 1, 'UTF-8'),
    'UTF-8'
);


/* =========================================================
   3. ROLES PERMITIDOS POR MÓDULO
   ========================================================= */

$rolesEmpresas = [
    'ADMIN',
    'ADMINISTRADOR',
    'PROPIETARIO'
];

$rolesOperadores = [
    'ADMIN',
    'ADMINISTRADOR',
    'PROPIETARIO',
    'RRHH',
    'RH',
    'RECURSOS HUMANOS'
];

$rolesUsuarios = [
    'ADMIN',
    'ADMINISTRADOR'
];

$rolesReportes = [
    'ADMIN',
    'ADMINISTRADOR',
    'PROPIETARIO',
    'RRHH',
    'RH',
    'RECURSOS HUMANOS'
];


/* =========================================================
   4. MÓDULOS PERMITIDOS PARA EL USUARIO
   ========================================================= */

$modulosPermitidos = [];

if (in_array($rol, $rolesEmpresas)) {
    $modulosPermitidos[] = [
        'icono' => '🏢',
        'nombre' => 'Empresas'
    ];
}

if (in_array($rol, $rolesOperadores)) {
    $modulosPermitidos[] = [
        'icono' => '📋',
        'nombre' => 'Operadores'
    ];
}

if (in_array($rol, $rolesUsuarios)) {
    $modulosPermitidos[] = [
        'icono' => '👤',
        'nombre' => 'Usuarios'
    ];
}

if (in_array($rol, $rolesReportes)) {
    $modulosPermitidos[] = [
        'icono' => '📄',
        'nombre' => 'Reporte de Bajas'
    ];
}

?>


<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>

    <!-- =========================================
         CONFIGURACIÓN DE LA PÁGINA
         ========================================= -->

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Plataforma de Transportistas</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Estilos personalizados -->
    <link href="/css/styles.css" rel="stylesheet">

</head>


<body>


<!-- =========================================================
     5. MENÚ SUPERIOR
     ========================================================= -->

<nav class="top-nav-controls">

    <!-- Cambiar modo claro / oscuro -->
    <button
        id="btn-Tema"
        class="btn-theme-toggle-custom"
        onclick="alternarTema()"
    >
        <span id="icono-tema" class="theme-icon">🌙</span>
        <span id="texto-tema" class="btn-text">Modo Oscuro</span>
    </button>


    <!-- Cerrar sesión -->
    <a
        href="/autentificacion/logout.php"
        class="btn-logout-custom"
        title="Finalizar sesión actual"
    >
        <span class="btn-text">Cerrar Sesión</span>

        <svg
            class="logout-icon"
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
        </svg>

    </a>

</nav>



<!-- =========================================================
     6. CONTENIDO PRINCIPAL
     ========================================================= -->

<div class="container my-4" id="seccion-principal-dashboard">

    <div
        id="contenido-principal"
        class="text-center"
        style="max-width: 1200px; margin: 0 auto;"
    >


        <!-- =============================================
             BIENVENIDA
             ============================================= -->

        <div class="welcome-banner text-start mb-5">

            <div class="welcome-header">


                <!-- Información del usuario -->
                <div class="user-profile-info">

                    <!-- Avatar -->
                    <div class="user-avatar-circle">
                        <?php echo e($inicialUsuario); ?>
                    </div>


                    <!-- Nombre y rol -->
                    <div class="user-greeting">

                        <h2>
                            ¡Hola, <?php echo e($nombreUsuario); ?>! 👋

                            <span class="badge-role-user">
                                Rol: <?php echo e($rol); ?>
                            </span>
                        </h2>

                        <p>
                            Acceso autorizado a la plataforma de control y
                            desempeño de operadores.
                        </p>

                    </div>

                </div>


                <!-- Estado de la sesión -->
                <div class="text-md-end">
                    <span class="badge-session-active">
                        Sesión Activa
                    </span>
                </div>

            </div>


            <!-- =========================================
                 PERMISOS DEL USUARIO
                 ========================================= -->

            <div class="permissions-row">

                <span class="permissions-label">
                    Permisos del Sistema:
                </span>


                <div class="permissions-badges-group">

                    <?php foreach ($modulosPermitidos as $modulo): ?>

                        <span class="badge-permiso">
                            <span><?php echo $modulo['icono']; ?></span>
                            <span><?php echo e($modulo['nombre']); ?></span>
                        </span>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>



        <!-- =================================================
             7. MÓDULOS DEL SISTEMA
             ================================================= -->

        <div class="row g-4 justify-content-center">


            <!-- =============================================
                 MÓDULO: EMPRESAS
                 ============================================= -->

            <?php if (in_array($rol, $rolesEmpresas)): ?>

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="card-professional dashboard-card modulo-empresas h-100 d-flex flex-column justify-content-between text-start">

                        <div>
                            <div class="card-icon-wrapper">🏢</div>

                            <h4 class="card-module-title">
                                Empresas
                            </h4>

                            <p class="card-module-desc">
                                Administre el catálogo de razones sociales,
                                datos fiscales y vinculación de transportistas
                                del sector.
                            </p>
                        </div>


                        <div class="mt-4 card-footer-action">

                            <button
                                onclick="ver('empresas/index.php')"
                                class="btn-dashboard"
                            >
                                Abrir Módulo <span>→</span>
                            </button>

                        </div>

                    </div>

                </div>

            <?php endif; ?>



            <!-- =============================================
                 MÓDULO: OPERADORES
                 ============================================= -->

            <?php if (in_array($rol, $rolesOperadores)): ?>

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="card-professional dashboard-card modulo-operadores h-100 d-flex flex-column justify-content-between text-start">

                        <div>
                            <div class="card-icon-wrapper">📋</div>

                            <h4 class="card-module-title">
                                Operadores
                            </h4>

                            <p class="card-module-desc">
                                Controle el padrón de choferes, vencimientos
                                de licencias federales y dictámenes aptos médicos.
                            </p>
                        </div>


                        <div class="mt-4 card-footer-action">

                            <button
                                onclick="ver('operadores/index.php')"
                                class="btn-dashboard"
                            >
                                Abrir Módulo <span>→</span>
                            </button>

                        </div>

                    </div>

                </div>

            <?php endif; ?>



            <!-- =============================================
                 MÓDULO: USUARIOS
                 ============================================= -->

            <?php if (in_array($rol, $rolesUsuarios)): ?>

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="card-professional dashboard-card modulo-usuarios h-100 d-flex flex-column justify-content-between text-start">

                        <div>
                            <div class="card-icon-wrapper">👤</div>

                            <h4 class="card-module-title">
                                Usuarios
                            </h4>

                            <p class="card-module-desc">
                                Gestione credenciales de acceso (RFC),
                                contraseñas obligatorias de 12 dígitos
                                y asignación de roles.
                            </p>
                        </div>


                        <div class="mt-4 card-footer-action">

                            <button
                                onclick="ver('usuarios/index.php')"
                                class="btn-dashboard"
                            >
                                Abrir Módulo <span>→</span>
                            </button>

                        </div>

                    </div>

                </div>

            <?php endif; ?>



            <!-- =============================================
                 MÓDULO: REPORTE DE BAJA
                 ============================================= -->

            <?php if (in_array($rol, $rolesReportes)): ?>

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="card-professional dashboard-card modulo-reportes h-100 d-flex flex-column justify-content-between text-start">

                        <div>

                            <div class="card-icon-wrapper">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    height="28px"
                                    width="28px"
                                    viewBox="0 -960 960 960"
                                    fill="currentColor"
                                    style="color: var(--accent-color);"
                                >
                                    <path d="m376-300 104-104 104 104 56-56-104-104 104-104-56-56-104 104-104-104-56 56 104 104-104 104 56 56Zm-96 180q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520Zm-400 0v520-520Z"/>
                                </svg>

                            </div>


                            <h4 class="card-module-title">
                                Reporte de Baja
                            </h4>

                            <p class="card-module-desc">
                                Aquí podrá hacer el reporte de baja que desee
                                con sus calificaciones y observaciones
                                correspondientes.
                            </p>

                        </div>


                        <div class="mt-4 card-footer-action">

                            <button
                                onclick="ver('reporte_baja/index.php')"
                                class="btn-dashboard"
                            >
                                Abrir Módulo <span>→</span>
                            </button>

                        </div>

                    </div>

                </div>

            <?php endif; ?>


        </div>



        <!-- =================================================
             8. PIE DE PÁGINA
             ================================================= -->

        <div
            class="mt-5 pt-4"
            style="
                border-top: 1px solid var(--borde-sutil);
                color: var(--texto-secundario);
                font-size: 0.85rem;
            "
        >
            Plataforma de Transportistas • Gestión Modular
        </div>


    </div>

</div>



<!-- =========================================================
     9. JAVASCRIPT
     ========================================================= -->

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Funciones del sistema -->
<script src="/JS/funciones.js"></script>

<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


</body>
</html>