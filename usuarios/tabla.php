<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$rolSesion = strtoupper(trim($_SESSION['rol'] ?? ''));
if ($rolSesion === 'ADMINISTRADOR') $rolSesion = 'ADMIN';

if ($rolSesion !== 'ADMIN') {
    http_response_code(403);
    exit('Acceso no autorizado');
}


$idUsuarioSesion = (int)($_SESSION['id_usuario'] ?? 0);


/* CONEXIÓN */
if (!isset($dbtransportistas) && !isset($db)) {

    include_once "../db/db.php";

    $db = new db();
    $db->conectar();

    $conexionLocal = true;

} else {

    $db = $dbtransportistas ?? $db;
    $conexionLocal = false;
}


/* FILTRO */
$rolFiltro = strtoupper(trim($_GET['rol'] ?? 'TODOS'));

$filtrosValidos = [
    'TODOS',
    'PROPIETARIO',
    'ADMINISTRADOR',
    'RRHH'
];

if (!in_array($rolFiltro,$filtrosValidos,true)) {
    $rolFiltro = 'TODOS';
}


$paginaActual = max(
    1,
    (int)($_GET['pagina'] ?? 1)
);

$porPagina = 5;
$where = '';


if ($rolFiltro === 'PROPIETARIO') {

    $where = "WHERE UPPER(u.rol)='PROPIETARIO'";

} elseif ($rolFiltro === 'RRHH') {

    $where = "WHERE UPPER(u.rol)='RRHH'";

} elseif ($rolFiltro === 'ADMINISTRADOR') {

    $where =
        "WHERE UPPER(u.rol) IN ('ADMIN','ADMINISTRADOR')";
}


/* TOTAL */
$res = $db->obtenerRegistros(
    "SELECT COUNT(*) total
     FROM usuarios u
     $where"
);

$totalRegistros = (int)($res[0]['total'] ?? 0);

$totalPaginas = max(
    1,
    (int)ceil($totalRegistros/$porPagina)
);

if ($paginaActual > $totalPaginas) {
    $paginaActual = $totalPaginas;
}

$offset = ($paginaActual-1)*$porPagina;


/* USUARIOS */
$datos2 = $db->obtenerRegistros(
    "SELECT
        u.*,
        e.nombre_empresa,

        (
            SELECT COUNT(*)
            FROM usuario_empresas ue
            WHERE ue.id_usuario=u.id_usuario
        ) cantidad_empresas,

        (
            SELECT GROUP_CONCAT(
                e2.nombre_empresa
                ORDER BY e2.nombre_empresa
                SEPARATOR ' | '
            )
            FROM usuario_empresas ue2
            INNER JOIN empresas e2
                ON e2.id_empresa=ue2.id_empresa
            WHERE ue2.id_usuario=u.id_usuario
        ) empresas_multi

     FROM usuarios u

     LEFT JOIN empresas e
        ON e.id_empresa=u.id_empresa

     $where

     ORDER BY u.id_usuario DESC

     LIMIT $porPagina OFFSET $offset"
);


if ($conexionLocal) {
    $db->desconectar();
}


$h = fn($v) =>
    htmlspecialchars(
        (string)$v,
        ENT_QUOTES,
        'UTF-8'
    );
?>


<div class="table-container">


    <!-- FILTROS -->
    <div class="table-header-title">

        <div class="table-tabs-wrapper">

            <div class="table-tabs">

                <button type="button"
                        class="tab-btn <?= $rolFiltro==='TODOS' ? 'active' : '' ?>"
                        onclick="filtrarUsuarios('TODOS',this)">
                    Todos
                </button>

                <button type="button"
                        class="tab-btn <?= $rolFiltro==='PROPIETARIO' ? 'active' : '' ?>"
                        onclick="filtrarUsuarios('PROPIETARIO',this)">
                    Propietarios
                </button>

                <button type="button"
                        class="tab-btn <?= $rolFiltro==='ADMINISTRADOR' ? 'active' : '' ?>"
                        onclick="filtrarUsuarios('ADMINISTRADOR',this)">
                    Administradores
                </button>

                <button type="button"
                        class="tab-btn <?= $rolFiltro==='RRHH' ? 'active' : '' ?>"
                        onclick="filtrarUsuarios('RRHH',this)">
                    RRHH
                </button>

            </div>

        </div>

    </div>


    <div class="table-responsive">

        <table class="custom-table"
               id="tablaUsuarios">

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

                <?php foreach ($datos2 as $u):

                    $id = (int)$u['id_usuario'];

                    $rolUsuario =
                        strtoupper($u['rol'] ?? '');

                    $rolMostrar =
                        $rolUsuario === 'ADMIN'
                        ? 'ADMINISTRADOR'
                        : $rolUsuario;

                    $multi =
                        (int)($u['multiempresa'] ?? 0);

                    $nombreCompleto = trim(
                        ($u['nombres'] ?? '').' '.
                        ($u['primer_apellido'] ?? '').' '.
                        ($u['segundo_apellido'] ?? '')
                    );

                    $badge = 'role-default';

                    if (in_array(
                        $rolUsuario,
                        ['ADMIN','ADMINISTRADOR'],
                        true
                    )) {
                        $badge = 'role-admin';

                    } elseif ($rolUsuario === 'PROPIETARIO') {
                        $badge = 'role-propietario';

                    } elseif ($rolUsuario === 'RRHH') {
                        $badge = 'role-rrhh';
                    }
                ?>

                <tr data-rol="<?= $h($rolMostrar) ?>">


                    <!-- USUARIO -->
                    <td class="font-medium">

                        <?= $h($u['nombre_usuario'] ?? '') ?>

                        <?php if ($nombreCompleto !== ''): ?>

                            <div style="font-size:.82rem;opacity:.75;margin-top:3px;">
                                <?= $h($nombreCompleto) ?>
                            </div>

                        <?php endif; ?>

                    </td>


                    <!-- CORREO -->
                    <td>
                        <?= $h($u['correo_electronico'] ?? '') ?>
                    </td>


                    <!-- ROL -->
                    <td>

                        <span class="badge-role <?= $badge ?>">
                            <?= $h($rolMostrar) ?>
                        </span>

                    </td>


                    <!-- CUENTA -->
                    <td>

                        <?php if (
                            in_array(
                                $rolUsuario,
                                ['ADMIN','ADMINISTRADOR'],
                                true
                            )
                        ): ?>

                            <strong>
                                🌐 Acceso global
                            </strong>

                            <div style="font-size:.82rem;opacity:.75">
                                Todas las empresas
                            </div>


                        <?php elseif (
                            $rolUsuario==='PROPIETARIO' &&
                            $multi===1
                        ): ?>

                            <strong>
                                🏢 Multiempresa ·
                                <?= (int)$u['cantidad_empresas'] ?>
                                empresas
                            </strong>

                            <?php if (!empty($u['empresas_multi'])): ?>

                                <div style="font-size:.82rem;opacity:.75;margin-top:4px">
                                    <?= $h($u['empresas_multi']) ?>
                                </div>

                            <?php endif; ?>


                        <?php elseif ($rolUsuario==='PROPIETARIO'): ?>

                            <strong>
                                🏢 Una empresa
                            </strong>

                            <div style="font-size:.82rem;opacity:.75;margin-top:4px">
                                <?= $h(
                                    $u['nombre_empresa']
                                    ?: 'Sin empresa asignada'
                                ) ?>
                            </div>


                        <?php elseif ($rolUsuario==='RRHH'): ?>

                            <strong>
                                🏢 Empresa asignada
                            </strong>

                            <div style="font-size:.82rem;opacity:.75;margin-top:4px">
                                <?= $h(
                                    $u['nombre_empresa']
                                    ?: 'Sin empresa asignada'
                                ) ?>
                            </div>

                        <?php endif; ?>

                    </td>


                    <!-- EDITAR -->
                    <td class="text-center">

                        <button type="button"
                                class="btn-action btn-edit"
                                onclick="editar(
                                    '<?= $id ?>',
                                    'usuarios',
                                    'formGuardarUsuario'
                                )">
                            ✏️ Editar
                        </button>

                    </td>


                    <!-- ELIMINAR -->
                    <td class="text-center">

                        <?php if ($id === $idUsuarioSesion): ?>

                            <button type="button"
                                    class="btn-action btn-delete"
                                    disabled
                                    style="opacity:.45;cursor:not-allowed"
                                    title="No puedes eliminar tu propia cuenta">
                                🗑️ Eliminar
                            </button>

                        <?php else: ?>

                            <button type="button"
                                    class="btn-action btn-delete"
                                    onclick="eliminar(
                                        '<?= $id ?>',
                                        'usuarios'
                                    )">
                                🗑️ Eliminar
                            </button>

                        <?php endif; ?>

                    </td>

                </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td colspan="6"
                        class="text-center">
                        No se encontraron usuarios.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- PAGINACIÓN -->
    <?php if ($totalPaginas > 1): ?>

        <div class="pagination-wrapper">

            <div class="pagination-info">

                Página
                <span><?= $paginaActual ?></span>

                de
                <span><?= $totalPaginas ?></span>

            </div>


            <div class="pagination-controls">

                <button type="button"
                        class="pagination-btn <?= $paginaActual<=1 ? 'disabled' : '' ?>"
                        <?= $paginaActual<=1 ? 'disabled' : '' ?>
                        onclick="cambiarPaginaUsuarios(
                            <?= $paginaActual-1 ?>,
                            '<?= $h($rolFiltro) ?>'
                        )">
                    ← Anterior
                </button>


                <div class="pagination-current">
                    Página <?= $paginaActual ?>
                </div>


                <button type="button"
                        class="pagination-btn <?= $paginaActual >= $totalPaginas ? 'disabled' : '' ?>"
                        <?= $paginaActual >= $totalPaginas ? 'disabled' : '' ?>
                        onclick="cambiarPaginaUsuarios(
                            <?= $paginaActual+1 ?>,
                            '<?= $h($rolFiltro) ?>'
                        )">
                    Siguiente →
                </button>

            </div>

        </div>

    <?php endif; ?>

</div>