<?php
/* =========================================================
   TABLA DE USUARIOS
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) session_start();

/* Solo ADMIN puede consultar usuarios */
$rolSesion = strtoupper($_SESSION['rol'] ?? '');

if (!in_array($rolSesion, ['ADMIN', 'ADMINISTRADOR'])) {
    http_response_code(403);
    exit('Acceso no autorizado');
}


/* =========================================================
   1. CONEXIÓN
   ========================================================= */

if (!isset($dbtransportistas) && !isset($db)) {

    include_once "../db/db.php";

    $db = new db();
    $db->conectar();

    $conexion_local = true;

} else {

    $db = isset($dbtransportistas)
        ? $dbtransportistas
        : $db;

    $conexion_local = false;
}


/* =========================================================
   2. FILTRO Y PAGINACIÓN
   ========================================================= */

$rol_filtro = trim($_GET['rol'] ?? 'TODOS');

$registros_por_pagina = 5;
$pagina_actual = isset($_GET['pagina'])
    ? (int)$_GET['pagina']
    : 1;

if ($pagina_actual < 1) $pagina_actual = 1;

$where = '';

if (strtoupper($rol_filtro) !== 'TODOS') {

    $rol_clean = addslashes($rol_filtro);

    $where = "WHERE UPPER(u.rol) = UPPER('$rol_clean')";
}


/* Total */
$sql_total = "
    SELECT COUNT(*) AS total
    FROM usuarios u
    $where
";

$res_total = $db->obtenerRegistros($sql_total);

$total_registros = (int)($res_total[0]['total'] ?? 0);

$total_paginas = $total_registros > 0
    ? ceil($total_registros / $registros_por_pagina)
    : 1;

if ($pagina_actual > $total_paginas) {
    $pagina_actual = $total_paginas;
}

$offset = ($pagina_actual - 1) * $registros_por_pagina;


/* =========================================================
   3. CONSULTA DE USUARIOS + EMPRESAS
   ========================================================= */

$sql = "
    SELECT
        u.*,
        e.nombre_empresa,
        e.razon_social,

        (
            SELECT COUNT(*)
            FROM usuario_empresas ue
            WHERE ue.id_usuario = u.id_usuario
        ) AS cantidad_empresas,

        (
            SELECT GROUP_CONCAT(
                e2.nombre_empresa
                ORDER BY e2.nombre_empresa
                SEPARATOR ' | '
            )
            FROM usuario_empresas ue2
            INNER JOIN empresas e2
                ON e2.id_empresa = ue2.id_empresa
            WHERE ue2.id_usuario = u.id_usuario
        ) AS empresas_multi

    FROM usuarios u

    LEFT JOIN empresas e
        ON u.id_empresa = e.id_empresa

    $where

    ORDER BY u.id_usuario DESC

    LIMIT $registros_por_pagina
    OFFSET $offset
";

$datos2 = $db->obtenerRegistros($sql);

if ($conexion_local) {
    $db->desconectar();
}
?>


<!-- =========================================================
     TABLA
     ========================================================= -->

<div class="table-container">

    <!-- FILTROS -->
    <div class="table-header-title">

        <div class="table-tabs-wrapper">

            <div class="table-tabs">

                <button
                    type="button"
                    class="tab-btn tab-todos <?php echo strtoupper($rol_filtro) === 'TODOS' ? 'active' : ''; ?>"
                    onclick="filtrarUsuarios('TODOS', this)">
                    Todos
                </button>

                <button
                    type="button"
                    class="tab-btn tab-propietario <?php echo strtoupper($rol_filtro) === 'PROPIETARIO' ? 'active' : ''; ?>"
                    onclick="filtrarUsuarios('PROPIETARIO', this)">
                    Propietarios
                </button>

                <button
                    type="button"
                    class="tab-btn tab-admin <?php echo strtoupper($rol_filtro) === 'ADMINISTRADOR' ? 'active' : ''; ?>"
                    onclick="filtrarUsuarios('ADMINISTRADOR', this)">
                    Administradores
                </button>

                <button
                    type="button"
                    class="tab-btn tab-rrhh <?php echo strtoupper($rol_filtro) === 'RRHH' ? 'active' : ''; ?>"
                    onclick="filtrarUsuarios('RRHH', this)">
                    RRHH
                </button>

            </div>

        </div>

    </div>


    <div class="table-responsive">

        <table class="custom-table" id="tablaUsuarios">

            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Correo Electrónico</th>
                    <th>Rol</th>
                    <th>Cuenta / Empresa(s)</th>
                    <th class="text-center">Editar</th>
                    <th class="text-center">Eliminar</th>
                </tr>
            </thead>

            <tbody>

            <?php if (!empty($datos2)): ?>

                <?php foreach ($datos2 as $u): ?>

                    <?php
                    $id = (int)($u['id_usuario'] ?? 0);
                    $rol = strtoupper($u['rol'] ?? '');
                    $multiempresa = (int)($u['multiempresa'] ?? 0);

                    /* Nombre completo */
                    $nombreCompleto = trim(
                        ($u['nombres'] ?? '') . ' ' .
                        ($u['primer_apellido'] ?? '') . ' ' .
                        ($u['segundo_apellido'] ?? '')
                    );

                    /* Badge del rol */
                    $badgeClass = 'role-default';

                    if ($rol === 'ADMINISTRADOR') {
                        $badgeClass = 'role-admin';
                    } elseif ($rol === 'PROPIETARIO') {
                        $badgeClass = 'role-propietario';
                    } elseif ($rol === 'RRHH') {
                        $badgeClass = 'role-rrhh';
                    }
                    ?>

                    <tr data-rol="<?php echo htmlspecialchars($rol); ?>">


                        <!-- USUARIO -->
                        <td class="font-medium">

                            <?php echo htmlspecialchars($u['nombre_usuario'] ?? ''); ?>

                            <?php if ($nombreCompleto !== ''): ?>

                                <div style="font-size:0.82rem; opacity:0.75; margin-top:3px;">
                                    <?php echo htmlspecialchars($nombreCompleto); ?>
                                </div>

                            <?php endif; ?>

                        </td>


                        <!-- CORREO -->
                        <td>
                            <?php echo htmlspecialchars($u['correo_electronico'] ?? ''); ?>
                        </td>


                        <!-- ROL -->
                        <td>

                            <span class="badge-role <?php echo $badgeClass; ?>">
                                <?php echo htmlspecialchars($u['rol'] ?? ''); ?>
                            </span>

                        </td>


                        <!-- TIPO DE CUENTA / EMPRESAS -->
                        <td>

                            <?php if ($rol === 'ADMINISTRADOR'): ?>

                                <strong>🌐 Acceso global</strong>

                                <div style="font-size:0.82rem; opacity:0.75;">
                                    Todas las empresas
                                </div>


                            <?php elseif ($rol === 'PROPIETARIO' && $multiempresa === 1): ?>

                                <strong>
                                    🏢 Multiempresa ·
                                    <?php echo (int)$u['cantidad_empresas']; ?>
                                    empresas
                                </strong>

                                <?php if (!empty($u['empresas_multi'])): ?>

                                    <div style="font-size:0.82rem; opacity:0.75; margin-top:4px;">
                                        <?php echo htmlspecialchars($u['empresas_multi']); ?>
                                    </div>

                                <?php endif; ?>


                            <?php elseif ($rol === 'PROPIETARIO'): ?>

                                <strong>🏢 Una empresa</strong>

                                <div style="font-size:0.82rem; opacity:0.75; margin-top:4px;">

                                    <?php
                                    echo !empty($u['nombre_empresa'])
                                        ? htmlspecialchars($u['nombre_empresa'])
                                        : 'Sin empresa asignada';
                                    ?>

                                </div>


                            <?php elseif ($rol === 'RRHH'): ?>

                                <strong>🏢 Empresa asignada</strong>

                                <div style="font-size:0.82rem; opacity:0.75; margin-top:4px;">

                                    <?php
                                    echo !empty($u['nombre_empresa'])
                                        ? htmlspecialchars($u['nombre_empresa'])
                                        : 'Sin empresa asignada';
                                    ?>

                                </div>


                            <?php else: ?>

                                <span class="fecha-vencimiento">
                                    N/A
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- EDITAR -->
                        <td class="text-center">

                            <button
                                type="button"
                                class="btn-action btn-edit"
                                onclick="editar('<?php echo $id; ?>', 'usuarios', 'formGuardarUsuario')">
                                ✏️ Editar
                            </button>

                        </td>


                        <!-- ELIMINAR -->
                        <td class="text-center">

                            <button
                                type="button"
                                class="btn-action btn-delete"
                                onclick="eliminar('<?php echo $id; ?>', 'usuarios')">
                                🗑️ Eliminar
                            </button>

                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>
                    <td colspan="6" class="text-center">
                        No se encontraron registros para este apartado.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- =====================================================
         PAGINACIÓN
         ===================================================== -->

    <?php if ($total_paginas > 1): ?>

        <div class="pagination-wrapper">

            <div class="pagination-info">

                Página
                <span><?php echo $pagina_actual; ?></span>
                de
                <span><?php echo $total_paginas; ?></span>

            </div>


            <div class="pagination-controls">

                <?php if ($pagina_actual > 1): ?>

                    <button
                        type="button"
                        onclick="cambiarPaginaUsuarios(
                            <?php echo $pagina_actual - 1; ?>,
                            '<?php echo htmlspecialchars($rol_filtro); ?>'
                        )"
                        class="pagination-btn">
                        ← Anterior
                    </button>

                <?php else: ?>

                    <button
                        type="button"
                        class="pagination-btn disabled"
                        disabled>
                        ← Anterior
                    </button>

                <?php endif; ?>


                <div class="pagination-current">
                    Página <?php echo $pagina_actual; ?>
                </div>


                <?php if ($pagina_actual < $total_paginas): ?>

                    <button
                        type="button"
                        onclick="cambiarPaginaUsuarios(
                            <?php echo $pagina_actual + 1; ?>,
                            '<?php echo htmlspecialchars($rol_filtro); ?>'
                        )"
                        class="pagination-btn">
                        Siguiente →
                    </button>

                <?php else: ?>

                    <button
                        type="button"
                        class="pagination-btn disabled"
                        disabled>
                        Siguiente →
                    </button>

                <?php endif; ?>

            </div>

        </div>

    <?php endif; ?>

</div>