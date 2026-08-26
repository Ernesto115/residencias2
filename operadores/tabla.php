<?php
/* =========================================================
   TABLA DE OPERADORES
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($dbtransportistas) && !isset($db)) {
    include_once "../db/db.php";
    $db = new db();
    $db->conectar();
    $conexion_local = true;
} else {
    $db = $dbtransportistas ?? $db;
    $conexion_local = false;
}

/* SESIÓN Y FILTROS */
$rol = strtoupper($_SESSION['rol'] ?? '');
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);

$busqueda = trim($_GET['busqueda'] ?? '');
$estatus_filtro = trim($_GET['estatus'] ?? 'todos');
$pagina_actual = max(1, (int)($_GET['pagina'] ?? 1));
$registros_por_pagina = 5;
$condiciones = [];

/* PERMISOS */
if ($rol === 'PROPIETARIO' && $multiempresa === 1) {
    $condiciones[] = "o.id_empresa IN (
        SELECT id_empresa FROM usuario_empresas
        WHERE id_usuario = $id_usuario
    )";
} elseif ($rol === 'PROPIETARIO' || $rol === 'RRHH') {
    $condiciones[] = "o.id_empresa = $id_empresa";
}

/* BÚSQUEDA GENERAL */
if ($busqueda !== '') {
    $b = addslashes($busqueda);

    $condiciones[] = "(
        o.rfc LIKE '%$b%' OR
        o.nombres LIKE '%$b%' OR
        o.primer_apellido LIKE '%$b%' OR
        o.segundo_apellido LIKE '%$b%' OR
        CONCAT(o.nombres,' ',o.primer_apellido,' ',o.segundo_apellido) LIKE '%$b%' OR
        e.nombre_empresa LIKE '%$b%'
    )";
}

/* ESTATUS */
if ($estatus_filtro === 'activos' || $estatus_filtro === 'ACTIVO') {
    $condiciones[] = "o.estatus = 1";
} elseif ($estatus_filtro === 'inactivos' || $estatus_filtro === 'INACTIVO') {
    $condiciones[] = "o.estatus = 0";
}

$where = $condiciones ? " WHERE " . implode(" AND ", $condiciones) : "";

/* PAGINACIÓN */
$sql_total = "SELECT COUNT(*) AS total
              FROM operadores o
              LEFT JOIN empresas e ON o.id_empresa=e.id_empresa
              $where";

$res_total = $db->obtenerRegistros($sql_total);
$total_registros = (int)($res_total[0]['total'] ?? 0);
$total_paginas = max(1, ceil($total_registros / $registros_por_pagina));

if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;

$offset = ($pagina_actual - 1) * $registros_por_pagina;

/* OPERADORES */
$sql = "SELECT o.*, e.nombre_empresa
        FROM operadores o
        LEFT JOIN empresas e ON o.id_empresa=e.id_empresa
        $where
        ORDER BY o.id_operador DESC
        LIMIT $registros_por_pagina OFFSET $offset";

$datos2 = $db->obtenerRegistros($sql);

if ($conexion_local) $db->desconectar();
?>

<div class="table-container">

    <!-- FILTROS -->
    <div class="table-header-title"
         style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;padding:15px;">

        <div class="table-tabs">

            <button type="button"
                    class="tab-btn <?= $estatus_filtro === 'todos' ? 'active' : '' ?>"
                    onclick="cambiarPagina(1,'todos')">
                Todos
            </button>

            <button type="button"
                    class="tab-btn <?= $estatus_filtro === 'activos' ? 'active' : '' ?>"
                    onclick="cambiarPagina(1,'activos')">
                Activos
            </button>

            <button type="button"
                    class="tab-btn <?= $estatus_filtro === 'inactivos' ? 'active' : '' ?>"
                    onclick="cambiarPagina(1,'inactivos')">
                Inactivos
            </button>

        </div>


        <!-- BUSCADOR -->
        <div class="search-box-wrapper"
             style="position:relative;min-width:280px;flex:1;max-width:360px;">

            <input type="text"
                   id="inputBuscadorOperador"
                   class="form-control"
                   placeholder="🔍 Buscar por Nombre, RFC o Empresa..."
                   value="<?= htmlspecialchars($busqueda) ?>"
                   style="padding-right:35px;height:40px;font-size:.9rem;">

            <?php if ($busqueda !== ''): ?>

                <button type="button"
                        onclick="limpiarBuscadorBD()"
                        title="Limpiar búsqueda"
                        style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:1.1rem;cursor:pointer;color:var(--texto-secundario);">
                    &times;
                </button>

            <?php endif; ?>

        </div>
    </div>


    <!-- TABLA -->
    <div class="table-responsive">

        <table class="custom-table" id="tablaOperadores">

            <thead>
                <tr>
                    <th>RFC</th>
                    <th>Nombre Completo</th>
                    <th>Empresa / Transportista</th>
                    <th class="text-center">Estatus</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>

            <tbody>

            <?php if (!empty($datos2)): ?>

                <?php foreach ($datos2 as $dato):

                    $id = $dato['id_operador'] ?? '';

                    $nombre_completo = trim(
                        ($dato['nombres'] ?? '') . ' ' .
                        ($dato['primer_apellido'] ?? '') . ' ' .
                        ($dato['segundo_apellido'] ?? '')
                    );

                    $esActivo = ($dato['estatus'] ?? 1) == 1;
                    $categoriaEstatus = $esActivo ? 'activos' : 'inactivos';
                    $categoriaCruce = (!empty($dato['visa']) || !empty($dato['fast']))
                        ? 'internacional' : 'nacional';

                    $nombreEmpresa = $dato['nombre_empresa'] ?: 'Sin empresa asignada';
                ?>

                <tr data-estatus="<?= $categoriaEstatus ?>"
                    data-cruce="<?= $categoriaCruce ?>">

                    <td class="font-medium cell-rfc">
                        <?= htmlspecialchars($dato['rfc'] ?? '') ?>
                    </td>

                    <td class="cell-nombre">
                        <?= htmlspecialchars($nombre_completo) ?>
                    </td>

                    <td><?= htmlspecialchars($nombreEmpresa) ?></td>

                    <td class="text-center">
                        <span class="badge-status <?= $esActivo ? 'status-activo' : 'status-inactivo' ?>">
                            <?= $esActivo ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>


                    <!-- ACCIONES -->
                    <td class="text-center">

                        <div class="acciones-operador">

                            <!-- VER MÁS -->
                            <?php if ($esActivo): ?>

                                <button type="button"
                                        class="btn-action btn-info"
                                        onclick="abrirModalDetalles('<?= $id ?>')"
                                        title="Ver información">
                                    👁️ Ver más
                                </button>

                            <?php else: ?>

                                <button type="button"
                                        class="btn-action btn-info btn-op-disabled"
                                        disabled
                                        title="No disponible para operador inactivo">
                                    👁️ Ver más
                                </button>

                            <?php endif; ?>


                            <!-- HISTORIAL -->
                            <button type="button"
                                    class="btn-action btn-historial"
                                    onclick="abrirHistorialOperador('<?= $id ?>')"
                                    title="Consultar historial laboral">
                                📋 Historial
                            </button>


                            <!-- EDITAR / ELIMINAR -->
                            <?php if ($esActivo): ?>

                                <button type="button"
                                        class="btn-action btn-edit btn-accion-icono"
                                        onclick="editar('<?= $id ?>','operadores','frm')"
                                        title="Editar operador">
                                    ✏️
                                </button>

                                <button type="button"
                                        class="btn-action btn-delete btn-accion-icono"
                                        onclick="eliminar('<?= $id ?>','operadores')"
                                        title="Eliminar operador">
                                    🗑️
                                </button>

                            <?php else: ?>

                                <button type="button"
                                        class="btn-action btn-edit btn-accion-icono btn-op-disabled"
                                        disabled
                                        title="Editar no disponible">
                                    ✏️
                                </button>

                                <button type="button"
                                        class="btn-action btn-delete btn-accion-icono btn-op-disabled"
                                        disabled
                                        title="Eliminar no disponible">
                                    🗑️
                                </button>

                            <?php endif; ?>

                        </div>
                    </td>

                </tr>


                <!-- MODAL DETALLES -->
                <?php if ($esActivo): ?>

                <div id="modal-detalle-<?= $id ?>"
                     class="modal-overlay"
                     style="display:none;">

                    <div class="modal-card"
                         style="width:90%;max-width:550px;max-height:85vh;overflow-y:auto;">

                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">

                            <h3 style="margin:0;color:var(--accent-color);font-size:1.1rem;">
                                📋 Detalles de <?= htmlspecialchars($dato['nombres'] ?? '') ?>
                            </h3>

                            <button type="button"
                                    onclick="cerrarModalDetalles('<?= $id ?>')"
                                    style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--texto-secundario);">
                                &times;
                            </button>

                        </div>


                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;font-size:.9rem;text-align:left;">

                            <!-- EMPRESA -->
                            <div>
                                <strong>🏢 Empresa / Transportista</strong>
                                <p><?= htmlspecialchars($nombreEmpresa) ?></p>

                                <?php if (!empty($dato['fecha_ingreso'])): ?>
                                    <p>
                                        <strong>Fecha ingreso:</strong>
                                        <?= htmlspecialchars($dato['fecha_ingreso']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>


                            <!-- CONTACTO -->
                            <div>
                                <strong>📞 Contacto</strong>
                                <p>
                                    <strong>Teléfono:</strong>
                                    <?= htmlspecialchars($dato['telefono_celular'] ?: 'N/A') ?>
                                </p>
                            </div>


                            <!-- DIRECCIÓN -->
                            <div>
                                <strong>📍 Dirección</strong>
                                <p><strong>Calle/No:</strong> <?= htmlspecialchars($dato['calle_y_numero'] ?: 'N/A') ?></p>
                                <p><strong>Colonia:</strong> <?= htmlspecialchars($dato['colonia'] ?: 'N/A') ?></p>
                                <p><strong>C.P.:</strong> <?= htmlspecialchars($dato['codigo_postal'] ?: 'N/A') ?></p>
                            </div>


                            <!-- LICENCIA -->
                            <div>
                                <strong>🪪 Licencia Federal</strong>
                                <p><?= htmlspecialchars($dato['licencia_federal_actual'] ?: 'N/A') ?></p>

                                <?php if (!empty($dato['vencimiento_lic_federal'])): ?>
                                    <p class="fecha-vencimiento">
                                        Vence: <?= htmlspecialchars($dato['vencimiento_lic_federal']) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($dato['archivo_pdf_licencia'])): ?>
                                    <a href="../uploads/pdf/<?= htmlspecialchars($dato['archivo_pdf_licencia']) ?>"
                                       target="_blank"
                                       class="btn-pdf-link">
                                        📄 Ver PDF Licencia
                                    </a>
                                <?php endif; ?>
                            </div>


                            <!-- APTO MÉDICO -->
                            <div>
                                <strong>🩺 Apto Médico</strong>
                                <p><?= htmlspecialchars($dato['apto_medico_actual'] ?: 'N/A') ?></p>

                                <?php if (!empty($dato['vencimiento_apto_medico'])): ?>
                                    <p class="fecha-vencimiento">
                                        Vence: <?= htmlspecialchars($dato['vencimiento_apto_medico']) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($dato['archivo_pdf_apto_medico'])): ?>
                                    <a href="../uploads/pdf/<?= htmlspecialchars($dato['archivo_pdf_apto_medico']) ?>"
                                       target="_blank"
                                       class="btn-pdf-link">
                                        📄 Ver PDF Apto
                                    </a>
                                <?php endif; ?>
                            </div>


                            <!-- CRUCE -->
                            <div>
                                <strong>🌐 Cruce Internacional</strong>

                                <p>
                                    <strong>VISA:</strong>
                                    <?= !empty($dato['visa'])
                                        ? '✓ ' . htmlspecialchars($dato['visa'])
                                        : '✗ No cuenta' ?>
                                </p>

                                <p>
                                    <strong>FAST:</strong>
                                    <?= !empty($dato['fast'])
                                        ? '✓ ' . htmlspecialchars($dato['fast'])
                                        : '✗ No cuenta' ?>
                                </p>
                            </div>

                        </div>


                        <div style="text-align:right;margin-top:15px;">
                            <button type="button"
                                    class="btn-action"
                                    onclick="cerrarModalDetalles('<?= $id ?>')">
                                Cerrar
                            </button>
                        </div>

                    </div>
                </div>

                <?php endif; ?>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>
                    <td colspan="5" class="text-center">
                        No se encontraron registros de operadores.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>
        </table>

    </div>


    <!-- PAGINACIÓN -->
    <?php if ($total_paginas > 1): ?>

    <div class="pagination-wrapper">

        <div class="pagination-info">
            Página <span><?= $pagina_actual ?></span>
            de <span><?= $total_paginas ?></span>
        </div>

        <div class="pagination-controls">

            <button type="button"
                    <?= $pagina_actual <= 1 ? 'disabled' : '' ?>
                    onclick="cambiarPagina(
                        <?= $pagina_actual - 1 ?>,
                        '<?= htmlspecialchars($estatus_filtro) ?>',
                        <?= json_encode($busqueda, JSON_UNESCAPED_UNICODE) ?>
                    )"
                    class="pagination-btn <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                ← Anterior
            </button>

            <div class="pagination-current">
                Página <?= $pagina_actual ?>
            </div>

            <button type="button"
                    <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>
                    onclick="cambiarPagina(
                        <?= $pagina_actual + 1 ?>,
                        '<?= htmlspecialchars($estatus_filtro) ?>',
                        <?= json_encode($busqueda, JSON_UNESCAPED_UNICODE) ?>
                    )"
                    class="pagination-btn <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                Siguiente →
            </button>

        </div>
    </div>

    <?php endif; ?>

</div>


<!-- =========================================================
     MODAL HISTORIAL
     ========================================================= -->

<div id="modalHistorialOperador"
     class="modal-overlay"
     style="display:none;">

    <div class="modal-card historial-operador-modal">

        <div class="historial-modal-header">

            <div>
                <h3>📋 Historial del Operador</h3>
                <p>Trayectoria laboral y reportes de baja</p>
            </div>

            <button type="button"
                    class="historial-modal-cerrar"
                    onclick="cerrarHistorialOperador()">
                &times;
            </button>

        </div>

        <div id="contenidoHistorialOperador"></div>

    </div>

</div>