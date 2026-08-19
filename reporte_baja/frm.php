<!-- CABECERA DE LA TABLA CON EL BOTÓN AGREGAR -->
<div class="table-header-title">
  <div class="table-tabs-wrapper">
    <!-- BOTÓN QUE ABRE EL MODAL -->
    <button type="button" class="btn-agregar-op" onclick="abrirModalReporte()">
      + Agregar Reporte
    </button>
  </div>
</div>

<!-- ESTRUCTURA DEL MODAL FLOTANTE -->
<div id="modalReporte" class="modal-overlay"> 
  <div class="modal-container">
    
    <!-- ENCABEZADO DEL MODAL CON BOTÓN DE CERRAR -->
    <div class="modal-header">
      <h2 class="modal-title-text" id="tituloModalReporte">Formulario de Reporte de Baja</h2>
      <button type="button" class="btn-cerrar-modal" onclick="cerrarModalReporte()">&times;</button>
    </div>

    <div class="modal-body-scroll">
      <!-- FORMULARIO DE REPORTE DE BAJA -->
      <form id="frm" class="form-grid" action="javascript:void(0);" enctype="multipart/form-data" onsubmit="guardar('reporte_baja', 'frm')">
        
        <!-- CAMPO OCULTO IMPRESCINDIBLE PARA LA EDICIÓN UNIVERSAL -->
        <input type="hidden" id="id_reporte" name="id_reporte" value="">

        <!-- Datos Principales del Reporte -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Operador</label>
            <select class="form-control" name="id_operador" id="id_operador" required>
              <option value="">-- Seleccionar Operador --</option>
              <?php 
              // Identificar el operador asignado en caso de edición
              $id_operador_edit = isset($row['id_operador']) ? $row['id_operador'] : (isset($_REQUEST['id_operador']) ? $_REQUEST['id_operador'] : 0);

              // Consulta de respaldo directa a reportes_baja para garantizar el filtrado
              $ids_dados_de_baja = [];
              if (class_exists('db')) {
                  $db_check = new db();
                  $db_check->conectar();
                  $res_check = $db_check->obtenerRegistros("SELECT DISTINCT id_operador FROM reportes_baja");
                  if (!empty($res_check)) {
                      foreach ($res_check as $r_check) {
                          if (isset($r_check['id_operador'])) {
                              $ids_dados_de_baja[] = $r_check['id_operador'];
                          }
                      }
                  }
                  $db_check->desconectar();
              }

              if (!empty($operadores)) {
                  foreach ($operadores as $op) {
                      $id_op = $op['id_operador'];

                      // 1. Evaluar estatus del arreglo si existe
                      $estatus = isset($op['estatus']) ? $op['estatus'] : (isset($op['ESTATUS']) ? $op['ESTATUS'] : null);
                      $inactivo_por_estatus = ($estatus !== null && ($estatus == 0 || $estatus === '0'));

                      // 2. Evaluar si ya tiene reporte registrado en la base de datos
                      $ya_tiene_reporte = in_array($id_op, $ids_dados_de_baja);

                      // Si está inactivo O ya tiene reporte de baja, se oculta (a menos que sea el operador en edición)
                      if (($inactivo_por_estatus || $ya_tiene_reporte) && $id_op != $id_operador_edit) {
                          continue;
                      }

                      $nombre_completo = trim($op['nombres'] . ' ' . $op['primer_apellido'] . ' ' . $op['segundo_apellido']);
                      echo '<option value="' . htmlspecialchars($id_op) . '">' . htmlspecialchars($nombre_completo) . '</option>';
                  }
              }
              ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Empresa</label>
            <select class="form-control" name="id_empresa" id="id_empresa" required>
              <option value="">-- Seleccionar Empresa --</option>
              <?php 
              if (!empty($empresas)) {
                  foreach ($empresas as $emp) {
                      echo '<option value="' . htmlspecialchars($emp['id_empresa']) . '">' . htmlspecialchars($emp['nombre_empresa']) . '</option>';
                  }
              }
              ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Motivo de Baja</label>
            <!-- EVENTO onchange AGREGADO PARA MOSTRAR/OCULTAR EL DETALLE -->
            <select class="form-control" name="motivo_baja" id="motivo_baja" required onchange="evaluarMotivoBaja(this.value)">
              <option value="">-- Seleccionar Motivo --</option>
              <option value="ROBO">Robo</option>
              <option value="GASTO_COMBUSTIBLE">Gasto Excesivo de Combustible</option>
              <option value="CHOQUES">Choques / Colisiones</option>
              <option value="MULTAS">Multas / Infracciones</option>
              <option value="FALTAS">Faltas / Inasistencias</option>
              <option value="OTRO">Otros</option>
            </select>
          </div>
        </div>

        <!-- CAMPO DE CALIFICACIÓN CUALITATIVA (SOLO VISIBLE SI ES "OTRO") -->
        <div class="form-row" id="row_calif_cualitativa" style="display: none;">
          <div class="form-group" style="flex: 1;">
            <label class="form-label">📝 Calificación Cualitativa / Comentarios Adicionales</label>
            <textarea class="form-control" 
                      name="calif_cualitativa" 
                      id="calif_cualitativa" 
                      rows="3" 
                      placeholder="Especifica detalladamente la razón o comentarios de la baja..."></textarea>
          </div>
        </div>

        <!-- Calificación Cuantitativa -->
        <div class="form-row">
          <div class="form-group" style="flex: 1;">
            <label class="form-label">⭐ Calificación (1 - 10)</label>
            <input type="hidden" name="calificacion_cuantitativa" id="calificacion_cuantitativa" value="5" required>
            
            <div class="rating-container">
              <?php for($i = 1; $i <= 10; $i++): ?>
                <button type="button" 
                        class="rating-btn <?php echo ($i == 5) ? 'active' : ''; ?>" 
                        data-value="<?php echo $i; ?>"
                        onclick="seleccionarCalificacion(<?php echo $i; ?>, this)">
                  <?php echo $i; ?>
                </button>
              <?php endfor; ?>
            </div>
          </div>
        </div>

        <!-- Div para mostrar alertas dinámicas dentro del formulario -->
        <div id="contenedor-alertas-reportes" class="mt-3"></div>

        <!-- Acciones del Formulario -->
        <div class="form-actions" style="margin-top: 20px; display: flex; gap: 12px; justify-content: flex-end;">
          <button type="button" class="btn-action btn-delete" onclick="cerrarModalReporte()">Cancelar</button>
          <button type="submit" class="btn-prof-primary">Grabar Reporte</button>
        </div>
      </form>
    </div>

  </div>
</div>