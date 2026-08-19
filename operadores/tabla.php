<?php
// 1. Verificación e inicialización de BD
if (!isset($dbtransportistas) && !isset($db)) {
    include_once "../db/db.php";
    $db = new db();
    $db->conectar();
    $conexion_local = true;
} else {
    $db = isset($dbtransportistas) ? $dbtransportistas : $db;
    $conexion_local = false;
}

// Parámetros de búsqueda y filtro por estatus
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$estatus_filtro = isset($_GET['estatus']) ? trim($_GET['estatus']) : 'todos';

// 2. Parámetros y cálculo de paginación
$registros_por_pagina = 5; 
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}

// Construcción dinámica de la cláusula WHERE
$condiciones = [];

// Filtro por texto de búsqueda
if ($busqueda !== '') {
    $busqueda_esc = addslashes($busqueda);
    $condiciones[] = "(rfc LIKE '%$busqueda_esc%' 
                       OR nombres LIKE '%$busqueda_esc%' 
                       OR primer_apellido LIKE '%$busqueda_esc%' 
                       OR segundo_apellido LIKE '%$busqueda_esc%' 
                       OR CONCAT(nombres, ' ', primer_apellido, ' ', segundo_apellido) LIKE '%$busqueda_esc%')";
}

// Filtro por pestañas (Todos, Activos, Inactivos)
if ($estatus_filtro === 'activos' || $estatus_filtro === 'ACTIVO') {
    $condiciones[] = "estatus = 1";
} elseif ($estatus_filtro === 'inactivos' || $estatus_filtro === 'INACTIVO') {
    $condiciones[] = "estatus = 0";
}

$where = "";
if (count($condiciones) > 0) {
    $where = " WHERE " . implode(" AND ", $condiciones);
}

// Total de registros filtrados según la pestaña y la búsqueda
$sql_total = "SELECT COUNT(*) AS total FROM operadores $where";
$res_total = $db->obtenerRegistros($sql_total);
$total_registros = isset($res_total[0]['total']) ? (int)$res_total[0]['total'] : 0;
$total_paginas = ($total_registros > 0) ? ceil($total_registros / $registros_por_pagina) : 1;

if ($pagina_actual > $total_paginas) {
    $pagina_actual = $total_paginas;
}
$offset = ($pagina_actual - 1) * $registros_por_pagina;
if ($offset < 0) { $offset = 0; }

// 3. Consulta de registros paginados y filtrados en la BD
$sql = "SELECT * FROM operadores $where ORDER BY id_operador DESC LIMIT $registros_por_pagina OFFSET $offset";
$datos2 = $db->obtenerRegistros($sql);

if ($conexion_local) {
    $db->desconectar();
}
?>

<div class="table-container">
  <!-- CABECERA CON PESTAÑAS Y BARRA DE BÚSQUEDA -->
  <div class="table-header-title" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; padding: 15px;">
    
    <div class="table-tabs-wrapper">
      <div class="table-tabs">
        <button type="button" 
                class="tab-btn <?php echo ($estatus_filtro === 'todos' || $estatus_filtro === 'TODOS') ? 'active' : ''; ?>" 
                onclick="cambiarPagina(1, 'todos')">
          Todos
        </button>
        <button type="button" 
                class="tab-btn <?php echo ($estatus_filtro === 'activos' || $estatus_filtro === 'ACTIVO') ? 'active' : ''; ?>" 
                onclick="cambiarPagina(1, 'activos')">
          Activos
        </button>
        <button type="button" 
                class="tab-btn <?php echo ($estatus_filtro === 'inactivos' || $estatus_filtro === 'INACTIVO') ? 'active' : ''; ?>" 
                onclick="cambiarPagina(1, 'inactivos')">
          Inactivos
        </button>
      </div>
    </div>

    <!-- BARRA DE BÚSQUEDA RÁPIDA -->
    <div class="search-box-wrapper" style="position: relative; min-width: 280px; flex: 1; max-width: 360px;">
      <input type="text" 
             id="inputBuscadorOperador" 
             class="form-control" 
             placeholder="🔍 Buscar por Nombre o RFC..." 
             value="<?php echo htmlspecialchars($busqueda); ?>"
             onkeyup="filtrarTablaEnVivo()"
             style="padding-right: 35px; height: 40px; font-size: 0.9rem;">
      
      <?php if (!empty($busqueda)): ?>
        <button type="button" onclick="limpiarBuscadorBD()" title="Limpiar búsqueda" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 1.1rem; cursor: pointer; color: var(--texto-secundario);">&times;</button>
      <?php endif; ?>
    </div>

  </div>
  
  <div class="table-responsive">
    <table class="custom-table" id="tablaOperadores">
      <thead>
        <tr>
          <th>RFC</th>
          <th>Nombre Completo</th>
          <th>Teléfono</th>
          <th class="text-center">Estatus</th>
          <th class="text-center">Detalles</th>
          <th class="text-center">Editar</th>
          <th class="text-center">Eliminar</th>
        </tr>
      </thead>
      <tbody>
        <?php if (isset($datos2) && (is_array($datos2) || is_object($datos2)) && count($datos2) > 0): ?>
          <?php foreach($datos2 as $dato): ?>
            <?php 
              $id = isset($dato['id_operador']) ? $dato['id_operador'] : (isset($dato['id']) ? $dato['id'] : ''); 

              $nombres    = isset($dato['nombres']) ? $dato['nombres'] : '';
              $p_apellido = isset($dato['primer_apellido']) ? $dato['primer_apellido'] : '';
              $s_apellido = isset($dato['segundo_apellido']) ? $dato['segundo_apellido'] : '';
              $nombre_completo = trim("$nombres $p_apellido $s_apellido");

              $esActivo = (($dato['estatus'] ?? 1) == 1);
              $categoriaEstatus = $esActivo ? 'activos' : 'inactivos';
              
              $tieneVisaOFast = (!empty($dato['visa']) || !empty($dato['fast']));
              $categoriaCruce = $tieneVisaOFast ? 'internacional' : 'nacional';
            ?>
            <tr data-estatus="<?php echo $categoriaEstatus; ?>" data-cruce="<?php echo $categoriaCruce; ?>">
              <td class="font-medium cell-rfc"><?php echo htmlspecialchars($dato['rfc'] ?? ''); ?></td>
              <td class="cell-nombre"><?php echo htmlspecialchars($nombre_completo); ?></td>
              <td><?php echo htmlspecialchars($dato['telefono_celular'] ?? ''); ?></td>

              <td class="text-center">
                <?php if ($esActivo): ?>
                  <span class="badge-status status-activo">Activo</span>
                <?php else: ?>
                  <span class="badge-status status-inactivo">Inactivo</span>
                <?php endif; ?>
              </td>

              <!-- BLOQUEO DE DETALLES SI ESTÁ INACTIVO -->
              <td class="text-center">
                <?php if ($esActivo): ?>
                  <button type="button" class="btn-action btn-info" onclick="abrirModalDetalles('<?php echo $id; ?>')">👁️ Ver más</button>
                <?php else: ?>
                  <button type="button" class="btn-action btn-info disabled" disabled style="opacity: 0.4; cursor: not-allowed; filter: grayscale(100%);" title="Registro inactivo: Detalles deshabilitados" onclick="if(typeof mostrarToast === 'function'){ mostrarToast('⚠️ El registro está INACTIVO y no se pueden consultar sus detalles.'); }">👁️ Ver más</button>
                <?php endif; ?>
              </td>

              <!-- BLOQUEO DE EDITAR SI ESTÁ INACTIVO -->
              <td class="text-center">
                <?php if ($esActivo): ?>
                  <button type="button" class="btn-action btn-edit" onclick="editar('<?php echo $id; ?>', 'operadores', 'frm')">✏️ Editar</button>
                <?php else: ?>
                  <button type="button" class="btn-action btn-edit disabled" disabled style="opacity: 0.4; cursor: not-allowed; filter: grayscale(100%);" title="Registro inactivo: Edición deshabilitada" onclick="if(typeof mostrarToast === 'function'){ mostrarToast('⚠️ El registro está INACTIVO y no se puede editar.'); }">✏️ Editar</button>
                <?php endif; ?>
              </td>

              <!-- BLOQUEO DE ELIMINAR SI ESTÁ INACTIVO -->
              <td class="text-center">
                <?php if ($esActivo): ?>
                  <button type="button" class="btn-action btn-delete" onclick="eliminar('<?php echo $id; ?>', 'operadores')">🗑️ Eliminar</button>
                <?php else: ?>
                  <button type="button" class="btn-action btn-delete disabled" disabled style="opacity: 0.4; cursor: not-allowed; filter: grayscale(100%);" title="Registro inactivo: Eliminación deshabilitada" onclick="if(typeof mostrarToast === 'function'){ mostrarToast('⚠️ El registro está INACTIVO y no se puede eliminar.'); }">🗑️ Eliminar</button>
                <?php endif; ?>
              </td>
            </tr>

            <!-- MODAL DETALLES DEL OPERADOR -->
            <?php if ($esActivo): ?>
            <div id="modal-detalle-<?php echo $id; ?>" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; justify-content: center; align-items: center;">
              <div class="modal-card" style="width: 90%; max-width: 550px; padding: 20px; border-radius: 12px; position: relative; max-height: 85vh; overflow-y: auto;">
                
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--borde-sutil); padding-bottom: 10px; margin-bottom: 15px;">
                  <h3 style="margin: 0; color: var(--accent-color); font-size: 1.1rem;">📋 Detalles de <?php echo htmlspecialchars($nombres); ?></h3>
                  <button type="button" onclick="cerrarModalDetalles('<?php echo $id; ?>')" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--texto-secundario);">&times;</button>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 0.9rem; text-align: left;">
                  
                  <div>
                    <strong>📍 Dirección</strong>
                    <p style="margin: 2px 0;"><strong>Calle/No:</strong> <?php echo htmlspecialchars($dato['calle_y_numero'] ?? 'N/A'); ?></p>
                    <p style="margin: 2px 0;"><strong>Colonia:</strong> <?php echo htmlspecialchars($dato['colonia'] ?? 'N/A'); ?></p>
                    <p style="margin: 2px 0;"><strong>C.P.:</strong> <?php echo htmlspecialchars($dato['codigo_postal'] ?? 'N/A'); ?></p>
                  </div>

                  <div>
                    <strong>🪪 Licencia Federal</strong>
                    <p style="margin: 2px 0;"><strong>No:</strong> <?php echo htmlspecialchars($dato['licencia_federal_actual'] ?? 'N/A'); ?></p>
                    <?php if (!empty($dato['vencimiento_lic_federal'])): ?>
                      <p style="margin: 2px 0;" class="fecha-vencimiento">Vence: <?php echo htmlspecialchars($dato['vencimiento_lic_federal']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($dato['archivo_pdf_licencia'])): ?>
                      <a href="../uploads/pdf/<?php echo htmlspecialchars($dato['archivo_pdf_licencia']); ?>" target="_blank" class="btn-pdf-link mt-2" style="font-size: 0.8rem; display: inline-block; margin-top: 5px;">📄 Ver PDF Licencia</a>
                    <?php endif; ?>
                  </div>

                  <div>
                    <strong>🩺 Apto Médico</strong>
                    <p style="margin: 2px 0;"><strong>No:</strong> <?php echo htmlspecialchars($dato['apto_medico_actual'] ?? 'N/A'); ?></p>
                    <?php if (!empty($dato['vencimiento_apto_medico'])): ?>
                      <p style="margin: 2px 0;" class="fecha-vencimiento">Vence: <?php echo htmlspecialchars($dato['vencimiento_apto_medico']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($dato['archivo_pdf_apto_medico'])): ?>
                      <a href="../uploads/pdf/<?php echo htmlspecialchars($dato['archivo_pdf_apto_medico']); ?>" target="_blank" class="btn-pdf-link mt-2" style="font-size: 0.8rem; display: inline-block; margin-top: 5px;">📄 Ver PDF Apto</a>
                    <?php endif; ?>
                  </div>

                  <div>
                    <strong>🌐 Cruce Internacional</strong>
                    <div style="margin-bottom: 8px;">
                      <strong>VISA:</strong>
                      <?php if (!empty($dato['visa'])): ?>
                        <span class="badge-status status-activo" style="font-size: 0.75rem;">✓ Sí (<?php echo htmlspecialchars($dato['visa']); ?>)</span>
                        <?php if (!empty($dato['archivo_pdf_visa'])): ?>
                          <a href="../uploads/pdf/<?php echo htmlspecialchars($dato['archivo_pdf_visa']); ?>" target="_blank" class="btn-pdf-icon" title="Ver VISA PDF">📄</a>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="badge-status status-inactivo" style="font-size: 0.75rem;">✗ No cuenta</span>
                      <?php endif; ?>
                    </div>

                    <div>
                      <strong>FAST:</strong>
                      <?php if (!empty($dato['fast'])): ?>
                        <span class="badge-status status-activo" style="font-size: 0.75rem;">✓ Sí (<?php echo htmlspecialchars($dato['fast']); ?>)</span>
                        <?php if (!empty($dato['fast_pdf'])): ?>
                          <a href="../uploads/pdf/<?php echo htmlspecialchars($dato['fast_pdf']); ?>" target="_blank" class="btn-pdf-icon" title="Ver FAST PDF">📄</a>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="badge-status status-inactivo" style="font-size: 0.75rem;">✗ No cuenta</span>
                      <?php endif; ?>
                    </div>
                  </div>

                </div>

                <div style="text-align: right; margin-top: 15px; border-top: 1px solid var(--borde-sutil); padding-top: 10px;">
                  <button type="button" class="btn-action" onclick="cerrarModalDetalles('<?php echo $id; ?>')" style="background: #6c757d; color: #fff; padding: 6px 15px; border-radius: 5px; border: none; cursor: pointer;">Cerrar</button>
                </div>

              </div>
            </div>
            <?php endif; ?>

          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center">No se encontraron registros de operadores.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- CONTROL DE PAGINACIÓN -->
  <?php if (isset($total_paginas) && $total_paginas > 1): ?>
  <div class="pagination-wrapper">
    <div class="pagination-info">
      Página <span><?php echo $pagina_actual; ?></span> de <span><?php echo $total_paginas; ?></span>
    </div>

    <div class="pagination-controls">
      <?php if ($pagina_actual > 1): ?>
        <button type="button" onclick="cambiarPagina(<?php echo $pagina_actual - 1; ?>)" class="pagination-btn">&#8592; Anterior</button>
      <?php else: ?>
        <button type="button" class="pagination-btn disabled" disabled>&#8592; Anterior</button>
      <?php endif; ?>

      <div class="pagination-current">Página <?php echo $pagina_actual; ?></div>

      <?php if ($pagina_actual < $total_paginas): ?>
        <button type="button" onclick="cambiarPagina(<?php echo $pagina_actual + 1; ?>)" class="pagination-btn">Siguiente &#8594;</button>
      <?php else: ?>
        <button type="button" class="pagination-btn disabled" disabled>Siguiente &#8594;</button>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>