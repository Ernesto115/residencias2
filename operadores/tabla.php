<?php
/* =========================================================
   TABLA DE OPERADORES
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) session_start();

/* Conexión */
if (!isset($dbtransportistas) && !isset($db)) {
    include_once "../db/db.php";
    $db = new db();
    $db->conectar();
    $conexion_local = true;
} else {
    $db = isset($dbtransportistas) ? $dbtransportistas : $db;
    $conexion_local = false;
}

/* =========================================================
   1. SESIÓN, FILTROS Y PAGINACIÓN
   ========================================================= */

$rol = strtoupper($_SESSION['rol'] ?? '');
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);

$busqueda = trim($_GET['busqueda'] ?? '');
$estatus_filtro = trim($_GET['estatus'] ?? 'todos');
$registros_por_pagina = 5;
$pagina_actual = max(1, (int)($_GET['pagina'] ?? 1));
$condiciones = [];

/* Filtro por empresa según el rol */
if ($rol === 'PROPIETARIO' && $multiempresa === 1) {
    $condiciones[] = "o.id_empresa IN (
        SELECT id_empresa FROM usuario_empresas WHERE id_usuario = $id_usuario
    )";
} elseif ($rol === 'PROPIETARIO' || $rol === 'RRHH') {
    $condiciones[] = "o.id_empresa = $id_empresa";
}
// ADMIN no lleva filtro: ve todos.

/* Búsqueda */
if ($busqueda !== '') {
    $b = addslashes($busqueda);
    $condiciones[] = "(
        o.rfc LIKE '%$b%' OR o.nombres LIKE '%$b%' OR
        o.primer_apellido LIKE '%$b%' OR o.segundo_apellido LIKE '%$b%' OR
        CONCAT(o.nombres,' ',o.primer_apellido,' ',o.segundo_apellido) LIKE '%$b%' OR
        e.nombre_empresa LIKE '%$b%'
    )";
}

/* Filtro por estatus */
if ($estatus_filtro === 'activos' || $estatus_filtro === 'ACTIVO') {
    $condiciones[] = "o.estatus = 1";
} elseif ($estatus_filtro === 'inactivos' || $estatus_filtro === 'INACTIVO') {
    $condiciones[] = "o.estatus = 0";
}

$where = $condiciones ? " WHERE " . implode(" AND ", $condiciones) : "";

/* Total de registros */
$sql_total = "SELECT COUNT(*) AS total
              FROM operadores o
              LEFT JOIN empresas e ON o.id_empresa = e.id_empresa
              $where";

$res_total = $db->obtenerRegistros($sql_total);
$total_registros = (int)($res_total[0]['total'] ?? 0);
$total_paginas = max(1, ceil($total_registros / $registros_por_pagina));

if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

/* Consulta de operadores + empresa */
$sql = "SELECT o.*, e.nombre_empresa
        FROM operadores o
        LEFT JOIN empresas e ON o.id_empresa = e.id_empresa
        $where
        ORDER BY o.id_operador DESC
        LIMIT $registros_por_pagina OFFSET $offset";

$datos2 = $db->obtenerRegistros($sql);

if ($conexion_local) $db->desconectar();
?>


<!-- =========================================================
     TABLA
     ========================================================= -->

<div class="table-container">

    <!-- FILTROS Y BUSCADOR -->
    <div class="table-header-title" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:12px; padding:15px;">

        <div class="table-tabs-wrapper">
            <div class="table-tabs">
                <button type="button" class="tab-btn <?= ($estatus_filtro === 'todos' || $estatus_filtro === 'TODOS') ? 'active' : '' ?>" onclick="cambiarPagina(1,'todos')">Todos</button>
                <button type="button" class="tab-btn <?= ($estatus_filtro === 'activos' || $estatus_filtro === 'ACTIVO') ? 'active' : '' ?>" onclick="cambiarPagina(1,'activos')">Activos</button>
                <button type="button" class="tab-btn <?= ($estatus_filtro === 'inactivos' || $estatus_filtro === 'INACTIVO') ? 'active' : '' ?>" onclick="cambiarPagina(1,'inactivos')">Inactivos</button>
            </div>
        </div>

        <div class="search-box-wrapper" style="position:relative; min-width:280px; flex:1; max-width:360px;">
            <input type="text" id="inputBuscadorOperador" class="form-control"
                   placeholder="🔍 Buscar por Nombre o RFC..."
                   value="<?= htmlspecialchars($busqueda) ?>"
                   onkeyup="filtrarTablaEnVivo()"
                   style="padding-right:35px; height:40px; font-size:0.9rem;">

            <?php if ($busqueda !== ''): ?>
                <button type="button" onclick="limpiarBuscadorBD()" title="Limpiar búsqueda"
                        style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; font-size:1.1rem; cursor:pointer; color:var(--texto-secundario);">
                    &times;
                </button>
            <?php endif; ?>
        </div>
    </div>


    <!-- TABLA DE RESULTADOS -->
    <div class="table-responsive">
        <table class="custom-table" id="tablaOperadores">

            <thead>
                <tr>
                    <th>RFC</th>
                    <th>Nombre Completo</th>
                    <th>Teléfono</th>
                    <th>Empresa / Transportista</th>
                    <th class="text-center">Estatus</th>
                    <th class="text-center">Detalles</th>
                    <th class="text-center">Editar</th>
                    <th class="text-center">Eliminar</th>
                </tr>
            </thead>

            <tbody>

            <?php if (!empty($datos2)): ?>
                <?php foreach ($datos2 as $dato):

                    $id = $dato['id_operador'] ?? '';
                    $nombres = $dato['nombres'] ?? '';
                    $nombre_completo = trim(
                        $nombres . ' ' .
                        ($dato['primer_apellido'] ?? '') . ' ' .
                        ($dato['segundo_apellido'] ?? '')
                    );

                    $esActivo = ($dato['estatus'] ?? 1) == 1;
                    $categoriaEstatus = $esActivo ? 'activos' : 'inactivos';
                    $categoriaCruce = (!empty($dato['visa']) || !empty($dato['fast'])) ? 'internacional' : 'nacional';
                    $nombreEmpresa = $dato['nombre_empresa'] ?: 'Sin empresa asignada';
                ?>

                <tr data-estatus="<?= $categoriaEstatus ?>" data-cruce="<?= $categoriaCruce ?>">

                    <td class="font-medium cell-rfc"><?= htmlspecialchars($dato['rfc'] ?? '') ?></td>
                    <td class="cell-nombre"><?= htmlspecialchars($nombre_completo) ?></td>
                    <td><?= htmlspecialchars($dato['telefono_celular'] ?? '') ?></td>
                    <td><?= htmlspecialchars($nombreEmpresa) ?></td>

                    <td class="text-center">
                        <span class="badge-status <?= $esActivo ? 'status-activo' : 'status-inactivo' ?>">
                            <?= $esActivo ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>

                    <!-- DETALLES -->
                    <td class="text-center">
                        <?php if ($esActivo): ?>
                            <button type="button" class="btn-action btn-info" onclick="abrirModalDetalles('<?= $id ?>')">👁️ Ver más</button>
                        <?php else: ?>
                            <button type="button" class="btn-action btn-info disabled" disabled
                                    style="opacity:.4; cursor:not-allowed; filter:grayscale(100%);"
                                    title="Registro inactivo: Detalles deshabilitados">👁️ Ver más</button>
                        <?php endif; ?>
                    </td>

                    <!-- EDITAR -->
                    <td class="text-center">
                        <?php if ($esActivo): ?>
                            <button type="button" class="btn-action btn-edit" onclick="editar('<?= $id ?>','operadores','frm')">✏️ Editar</button>
                        <?php else: ?>
                            <button type="button" class="btn-action btn-edit disabled" disabled
                                    style="opacity:.4; cursor:not-allowed; filter:grayscale(100%);">✏️ Editar</button>
                        <?php endif; ?>
                    </td>

                    <!-- ELIMINAR -->
                    <td class="text-center">
                        <?php if ($esActivo): ?>
                            <button type="button" class="btn-action btn-delete" onclick="eliminar('<?= $id ?>','operadores')">🗑️ Eliminar</button>
                        <?php else: ?>
                            <button type="button" class="btn-action btn-delete disabled" disabled
                                    style="opacity:.4; cursor:not-allowed; filter:grayscale(100%);">🗑️ Eliminar</button>
                        <?php endif; ?>
                    </td>

                </tr>


                <!-- =====================================================
                     MODAL DE DETALLES
                     ===================================================== -->

                <?php if ($esActivo): ?>
                <div id="modal-detalle-<?= $id ?>" class="modal-overlay"
                     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.6); z-index:9999; justify-content:center; align-items:center;">

                    <div class="modal-card"
                         style="width:90%; max-width:550px; padding:20px; border-radius:12px; position:relative; max-height:85vh; overflow-y:auto;">

                        <!-- CABECERA -->
                        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--borde-sutil); padding-bottom:10px; margin-bottom:15px;">
                            <h3 style="margin:0; color:var(--accent-color); font-size:1.1rem;">
                                📋 Detalles de <?= htmlspecialchars($nombres) ?>
                            </h3>

                            <button type="button" onclick="cerrarModalDetalles('<?= $id ?>')"
                                    style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:var(--texto-secundario);">
                                &times;
                            </button>
                        </div>


                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; font-size:.9rem; text-align:left;">

                            <!-- EMPRESA -->
                            <div>
                                <strong>🏢 Empresa / Transportista</strong>
                                <p style="margin:2px 0;"><?= htmlspecialchars($nombreEmpresa) ?></p>

                                <?php if (!empty($dato['fecha_ingreso'])): ?>
                                    <p style="margin:2px 0;">
                                        <strong>Fecha ingreso:</strong>
                                        <?= htmlspecialchars($dato['fecha_ingreso']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <!-- DIRECCIÓN -->
                            <div>
                                <strong>📍 Dirección</strong>
                                <p style="margin:2px 0;"><strong>Calle/No:</strong> <?= htmlspecialchars($dato['calle_y_numero'] ?: 'N/A') ?></p>
                                <p style="margin:2px 0;"><strong>Colonia:</strong> <?= htmlspecialchars($dato['colonia'] ?: 'N/A') ?></p>
                                <p style="margin:2px 0;"><strong>C.P.:</strong> <?= htmlspecialchars($dato['codigo_postal'] ?: 'N/A') ?></p>
                            </div>

                            <!-- LICENCIA -->
                            <div>
                                <strong>🪪 Licencia Federal</strong>
                                <p style="margin:2px 0;"><strong>No:</strong> <?= htmlspecialchars($dato['licencia_federal_actual'] ?: 'N/A') ?></p>

                                <?php if (!empty($dato['vencimiento_lic_federal'])): ?>
                                    <p class="fecha-vencimiento" style="margin:2px 0;">Vence: <?= htmlspecialchars($dato['vencimiento_lic_federal']) ?></p>
                                <?php endif; ?>

                                <?php if (!empty($dato['archivo_pdf_licencia'])): ?>
                                    <a href="../uploads/pdf/<?= htmlspecialchars($dato['archivo_pdf_licencia']) ?>"
                                       target="_blank" class="btn-pdf-link mt-2">📄 Ver PDF Licencia</a>
                                <?php endif; ?>
                            </div>

                            <!-- APTO MÉDICO -->
                            <div>
                                <strong>🩺 Apto Médico</strong>
                                <p style="margin:2px 0;"><strong>No:</strong> <?= htmlspecialchars($dato['apto_medico_actual'] ?: 'N/A') ?></p>

                                <?php if (!empty($dato['vencimiento_apto_medico'])): ?>
                                    <p class="fecha-vencimiento" style="margin:2px 0;">Vence: <?= htmlspecialchars($dato['vencimiento_apto_medico']) ?></p>
                                <?php endif; ?>

                                <?php if (!empty($dato['archivo_pdf_apto_medico'])): ?>
                                    <a href="../uploads/pdf/<?= htmlspecialchars($dato['archivo_pdf_apto_medico']) ?>"
                                       target="_blank" class="btn-pdf-link mt-2">📄 Ver PDF Apto</a>
                                <?php endif; ?>
                            </div>

                            <!-- CRUCE INTERNACIONAL -->
                            <div>
                                <strong>🌐 Cruce Internacional</strong>

                                <p style="margin:4px 0;">
                                    <strong>VISA:</strong>
                                    <?php if (!empty($dato['visa'])): ?>
                                        <span class="badge-status status-activo">✓ Sí (<?= htmlspecialchars($dato['visa']) ?>)</span>
                                        <?php if (!empty($dato['archivo_pdf_visa'])): ?>
                                            <a href="../uploads/pdf/<?= htmlspecialchars($dato['archivo_pdf_visa']) ?>" target="_blank">📄</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge-status status-inactivo">✗ No cuenta</span>
                                    <?php endif; ?>
                                </p>

                                <p style="margin:4px 0;">
                                    <strong>FAST:</strong>
                                    <?php if (!empty($dato['fast'])): ?>
                                        <span class="badge-status status-activo">✓ Sí (<?= htmlspecialchars($dato['fast']) ?>)</span>
                                        <?php if (!empty($dato['fast_pdf'])): ?>
                                            <a href="../uploads/pdf/<?= htmlspecialchars($dato['fast_pdf']) ?>" target="_blank">📄</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge-status status-inactivo">✗ No cuenta</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                        </div>

                        <div style="text-align:right; margin-top:15px; border-top:1px solid var(--borde-sutil); padding-top:10px;">
                            <button type="button" class="btn-action" onclick="cerrarModalDetalles('<?= $id ?>')"
                                    style="background:#6c757d; color:#fff; padding:6px 15px; border-radius:5px; border:none; cursor:pointer;">
                                Cerrar
                            </button>
                        </div>

                    </div>
                </div>
                <?php endif; ?>

                <?php endforeach; ?>

            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No se encontraron registros de operadores.</td>
                </tr>
            <?php endif; ?>

            </tbody>
        </table>
    </div>


    <!-- =========================================================
         PAGINACIÓN
         ========================================================= -->

    <?php if ($total_paginas > 1): ?>
    <div class="pagination-wrapper">

        <div class="pagination-info">
            Página <span><?= $pagina_actual ?></span> de <span><?= $total_paginas ?></span>
        </div>

        <div class="pagination-controls">

            <button type="button"
                    <?= $pagina_actual <= 1 ? 'disabled' : '' ?>
                    onclick="cambiarPagina(<?= $pagina_actual - 1 ?>)"
                    class="pagination-btn <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                &#8592; Anterior
            </button>

            <div class="pagination-current">
                Página <?= $pagina_actual ?>
            </div>

            <button type="button"
                    <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>
                    onclick="cambiarPagina(<?= $pagina_actual + 1 ?>)"
                    class="pagination-btn <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                Siguiente &#8594;
            </button>

        </div>
    </div>
    <?php endif; ?>

</div>