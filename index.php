<?php

/* =========================================================
   1. SESIÓN Y SEGURIDAD
   ========================================================= */

require_once "configuracion/sesion.php";
verificarSesion();

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   2. DATOS DEL USUARIO
   ========================================================= */

$rol = strtoupper(trim($_SESSION['rol'] ?? ''));
$idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
$idEmpresa = (int)($_SESSION['id_empresa'] ?? 0);

$nombreUsuario = $_SESSION['nombre_usuario'] ?? 'Usuario';
$nombreCompleto = trim($_SESSION['nombre_completo'] ?? '');
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);

$nombreMostrar = $nombreCompleto !== ''
    ? $nombreCompleto
    : $nombreUsuario;

$inicialUsuario = mb_strtoupper(
    mb_substr($nombreMostrar, 0, 1, 'UTF-8'),
    'UTF-8'
);


/* =========================================================
   3. ROLES PERMITIDOS
   ========================================================= */

$rolesEmpresas = ['ADMIN', 'ADMINISTRADOR'];

$puedeVerEmpresas =
    in_array($rol, $rolesEmpresas) ||
    ($rol === 'PROPIETARIO' && $multiempresa === 1);

$puedeAgregarOtraEmpresa =
    ($rol === 'PROPIETARIO' && $multiempresa === 0);


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
   4. MÉTRICAS DEL DASHBOARD
   ========================================================= */

require_once "db/db.php";

$dbDashboard = new db();
$dbDashboard->conectar();

function conteoDashboard($db, $sql) {
    $resultado = $db->obtenerRegistros($sql);
    return (int)($resultado[0]['total'] ?? 0);
}

$esAdmin = in_array($rol, ['ADMIN', 'ADMINISTRADOR'], true);
$esPropietario = $rol === 'PROPIETARIO';
$esRRHH = in_array($rol, ['RRHH', 'RH', 'RECURSOS HUMANOS'], true);

/* Limita cada conteo al alcance real del usuario */
$filtroEmpresa = function($alias) use (
    $esAdmin,
    $esPropietario,
    $esRRHH,
    $multiempresa,
    $idUsuario,
    $idEmpresa
) {
    $campo = $alias . '.id_empresa';

    if ($esAdmin) return '';

    if ($esPropietario && $multiempresa === 1) {
        return " AND $campo IN (
            SELECT id_empresa
            FROM usuario_empresas
            WHERE id_usuario = $idUsuario
        )";
    }

    if (($esPropietario || $esRRHH) && $idEmpresa > 0) {
        return " AND $campo = $idEmpresa";
    }

    return " AND 1=0";
};

$operadoresActivos = conteoDashboard(
    $dbDashboard,
    "SELECT COUNT(*) AS total FROM operadores o WHERE o.estatus=1" . $filtroEmpresa('o')
);

$operadoresInactivos = conteoDashboard(
    $dbDashboard,
    "SELECT COUNT(*) AS total FROM operadores o WHERE o.estatus=0" . $filtroEmpresa('o')
);

$empresasRegistradas = conteoDashboard(
    $dbDashboard,
    "SELECT COUNT(*) AS total FROM empresas e WHERE 1=1" . $filtroEmpresa('e')
);

$bajasPendientes = conteoDashboard(
    $dbDashboard,
    "SELECT COUNT(*) AS total FROM reportes_baja rb WHERE rb.estatus_evaluacion='PENDIENTE'" . $filtroEmpresa('rb')
);

$bajasCompletadas = conteoDashboard(
    $dbDashboard,
    "SELECT COUNT(*) AS total FROM reportes_baja rb WHERE rb.estatus_evaluacion='COMPLETADA'" . $filtroEmpresa('rb')
);


/* =========================================================
   5. MÓDULOS PERMITIDOS
   ========================================================= */

$modulosPermitidos = [];

if ($puedeVerEmpresas) {
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

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Plataforma de Transportistas</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link href="/css/styles.css" rel="stylesheet">

</head>

<body>


<!-- =========================================================
     6. MENÚ SUPERIOR
     ========================================================= -->

<nav class="top-nav-controls">

    <button
        id="btn-Tema"
        class="btn-theme-toggle-custom"
        onclick="alternarTema()"
    >
        <span id="icono-tema" class="theme-icon">🌙</span>
        <span id="texto-tema" class="btn-text">Modo Oscuro</span>
    </button>


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
     7. CONTENIDO PRINCIPAL
     ========================================================= -->

<div class="container my-4" id="seccion-principal-dashboard">

    <div
        id="contenido-principal"
        class="text-center"
        style="max-width:1200px; margin:0 auto;"
    >


        <!-- BIENVENIDA -->
        <div class="welcome-banner text-start mb-5">

            <div class="welcome-header">

                <div class="user-profile-info">

                    <div class="user-avatar-circle">
                        <?php echo e($inicialUsuario); ?>
                    </div>


                    <div class="user-greeting">

                        <h2>

                            ¡Hola, <?php echo e($nombreMostrar); ?>! 👋

                            <span class="badge-role-user">
                                Rol: <?php echo e($rol); ?>
                            </span>

                            <?php if ($rol === 'PROPIETARIO'): ?>

                                <span class="badge-role-user">

                                    <?php if ($multiempresa === 1): ?>
                                        🏢 Multiempresa
                                    <?php else: ?>
                                        🏢 Una empresa
                                    <?php endif; ?>

                                </span>

                            <?php endif; ?>

                        </h2>

                        <p>
                            Acceso autorizado a la plataforma de control y
                            desempeño de operadores.
                        </p>

                    </div>

                </div>


                <div class="text-md-end">
                    <span class="badge-session-active">
                        Sesión Activa
                    </span>
                </div>

            </div>


            <!-- PERMISOS + RESUMEN COMPACTO -->
            <div class="permissions-row dashboard-info-row">

                <div class="dashboard-permissions-block">
                    <span class="permissions-label">Permisos del Sistema:</span>

                    <div class="permissions-badges-group">
                        <?php foreach ($modulosPermitidos as $modulo): ?>
                            <span class="badge-permiso">
                                <span><?php echo $modulo['icono']; ?></span>
                                <span><?php echo e($modulo['nombre']); ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="dashboard-mini-stats">
                    <span class="dashboard-mini-label">Resumen:</span>
                    <span class="mini-stat">👷 <strong><?= $operadoresActivos ?></strong> Activos</span>
                    <span class="mini-stat">⛔ <strong><?= $operadoresInactivos ?></strong> Inactivos</span>
                    <span class="mini-stat">🏢 <strong><?= $empresasRegistradas ?></strong> Empresas</span>
                    <span class="mini-stat">⏳ <strong><?= $bajasPendientes ?></strong> Pendientes</span>
                    <span class="mini-stat">✅ <strong><?= $bajasCompletadas ?></strong> Completadas</span>
                </div>

            </div>

        </div>


        <!-- =================================================
             8. MÓDULOS
             ================================================= -->

        <div class="row g-4 justify-content-center">


            <!-- =================================================
                 EMPRESAS
                 ADMIN / PROPIETARIO MULTIEMPRESA
                 ================================================= -->

            <?php if ($puedeVerEmpresas): ?>

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


            <!-- =================================================
                 AGREGAR OTRA EMPRESA
                 SOLO PROPIETARIO CON UNA EMPRESA
                 ================================================= -->

            <?php if ($puedeAgregarOtraEmpresa): ?>

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="card-professional dashboard-card modulo-empresas h-100 d-flex flex-column justify-content-between text-start">

                        <div>

                            <div class="card-icon-wrapper">🏢</div>

                            <h4 class="card-module-title">
                                Agregar otra empresa
                            </h4>

                            <p class="card-module-desc">
                                Registre una empresa adicional para administrar
                                varias empresas desde la misma cuenta.
                            </p>

                        </div>


                        <div class="mt-4 card-footer-action">

                            <button
                                onclick="ver('empresas/index.php')"
                                class="btn-dashboard"
                            >
                                + Agregar Empresa
                            </button>

                        </div>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 OPERADORES
                 ================================================= -->

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


            <!-- =================================================
                 USUARIOS
                 ================================================= -->

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


            <!-- =================================================
                 REPORTE DE BAJA
                 ================================================= -->

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
                                    style="color:var(--accent-color);"
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
             9. PIE DE PÁGINA
             ================================================= -->

        <div
            class="mt-5 pt-4"
            style="
                border-top:1px solid var(--borde-sutil);
                color:var(--texto-secundario);
                font-size:0.85rem;
            "
        >
            Plataforma de Transportistas • Gestión Modular
        </div>

    </div>

</div>


<!-- =========================================================
     10. JAVASCRIPT
     ========================================================= -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/JS/funciones.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>