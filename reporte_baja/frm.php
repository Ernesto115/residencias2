<?php
$rolFormulario = strtoupper($rol ?? ($_SESSION['rol'] ?? ''));

/* Operadores con baja pendiente */
$ids_pendientes = [];

if (class_exists('db')) {
    $db_check = new db();

    $res_check = $db_check->obtenerRegistros(
        "SELECT DISTINCT id_operador
         FROM reportes_baja
         WHERE estatus_evaluacion = 'PENDIENTE'"
    );

    foreach ($res_check as $r_check) {
        $ids_pendientes[] = (int)$r_check['id_operador'];
    }
}

$motivos = [
    'ROBO' => 'Robo',
    'GASTO_COMBUSTIBLE' => 'Gasto Excesivo de Combustible',
    'CHOQUES' => 'Choques / Colisiones',
    'MULTAS' => 'Multas / Infracciones',
    'FALTAS' => 'Faltas / Inasistencias',
    'RENUNCIA_VOLUNTARIA' => 'Renuncia Voluntaria',
    'DESPIDO' => 'Despido',
    'ABANDONO_TRABAJO' => 'Abandono de Trabajo',
    'INCUMPLIMIENTO' => 'Incumplimiento',
    'OTRO' => 'Otro'
];
?>

<!-- BOTÓN SOLICITAR BAJA -->
<div class="table-header-title">
    <div class="table-tabs-wrapper">
        <button type="button" class="btn-agregar-op" onclick="abrirModalReporte()">
            + Solicitar Baja
        </button>
    </div>
</div>

<!-- MODAL: SOLICITUD DE BAJA -->
<div id="modalReporte" class="modal-overlay">
    <div class="modal-container">

        <div class="modal-header">
            <h2 class="modal-title-text">Solicitud de Baja de Operador</h2>
            <button type="button" class="btn-cerrar-modal" onclick="cerrarModalReporte()">&times;</button>
        </div>

        <div class="modal-body-scroll">

            <form id="frm" class="form-grid" action="javascript:void(0);"
                  onsubmit="guardar('reporte_baja', 'frm')">

                <input type="hidden" id="id_reporte" name="id_reporte" value="">

                <div style="padding:12px 15px; margin-bottom:18px; border:1px solid var(--borde-sutil); border-radius:10px; color:var(--texto-secundario);">
                    Esta solicitud quedará pendiente hasta que el propietario revise y confirme la baja del operador.
                </div>

                <!-- OPERADOR Y EMPRESA -->
                <div class="form-row">

                    <div class="form-group">
                        <label class="form-label">Operador</label>

                        <select class="form-control" name="id_operador" id="id_operador" required>
                            <option value="">-- Seleccionar Operador --</option>

                            <?php if (!empty($operadores)): ?>
                                <?php foreach ($operadores as $op): ?>
                                    <?php
                                    $id_op = (int)$op['id_operador'];
                                    $estatus = (int)($op['estatus'] ?? 1);

                                    if ($estatus !== 1 || in_array($id_op, $ids_pendientes)) {
                                        continue;
                                    }

                                    $nombre = trim(
                                        ($op['nombres'] ?? '') . ' ' .
                                        ($op['primer_apellido'] ?? '') . ' ' .
                                        ($op['segundo_apellido'] ?? '')
                                    );
                                    ?>

                                    <option value="<?= $id_op ?>">
                                        <?= htmlspecialchars($nombre) ?>
                                    </option>

                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Empresa</label>

                        <?php if ($rolFormulario === 'RRHH' && !empty($empresas)): ?>

                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars($empresas[0]['nombre_empresa'] ?? '') ?>"
                                   readonly>

                            <input type="hidden" name="id_empresa" id="id_empresa"
                                   value="<?= (int)($empresas[0]['id_empresa'] ?? 0) ?>">

                        <?php else: ?>

                            <select class="form-control" name="id_empresa" id="id_empresa" required>
                                <option value="">-- Seleccionar Empresa --</option>

                                <?php foreach ($empresas ?? [] as $emp): ?>
                                    <option value="<?= (int)$emp['id_empresa'] ?>">
                                        <?= htmlspecialchars($emp['nombre_empresa']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                        <?php endif; ?>
                    </div>

                </div>

                <!-- MOTIVO -->
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Motivo de Baja</label>

                        <select class="form-control" name="motivo_baja" id="motivo_baja"
                                required onchange="evaluarMotivoBaja(this.value)">

                            <option value="">-- Seleccionar Motivo --</option>

                            <?php foreach ($motivos as $valor => $texto): ?>
                                <option value="<?= $valor ?>">
                                    <?= htmlspecialchars($texto) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>
                </div>

                <!-- COMENTARIO PARA OTRO -->
                <div class="form-row" id="row_calif_cualitativa" style="display:none;">
                    <div class="form-group" style="flex:1;">

                        <label class="form-label">
                            📝 Especificar Motivo / Comentarios
                        </label>

                        <textarea class="form-control"
                                  name="calif_cualitativa"
                                  id="calif_cualitativa"
                                  rows="3"
                                  placeholder="Especifica detalladamente el motivo de la baja..."></textarea>
                    </div>
                </div>

                <!-- AVISO -->
                <div style="margin-top:10px; padding:12px 15px; border-radius:10px; background:rgba(234,88,12,.08); border:1px solid rgba(234,88,12,.30);">

                    <strong>⭐ Evaluación pendiente</strong>

                    <div style="margin-top:4px; font-size:.9rem; opacity:.8;">
                        La calificación será realizada por el propietario al confirmar la baja.
                    </div>

                </div>

                <div id="contenedor-alertas-reportes" class="mt-3"></div>

                <div class="form-actions"
                     style="margin-top:20px; display:flex; gap:12px; justify-content:flex-end;">

                    <button type="button" class="btn-action btn-delete"
                            onclick="cerrarModalReporte()">
                        Cancelar
                    </button>

                    <button type="submit" class="btn-prof-primary">
                        Solicitar Baja
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>


<?php if (
    $rolFormulario === 'PROPIETARIO' ||
    $rolFormulario === 'ADMIN' ||
    $rolFormulario === 'ADMINISTRADOR'
): ?>

<!-- MODAL: REVISIÓN DEL PROPIETARIO -->
<div id="modalRevisionBaja" class="modal-overlay">

    <div class="modal-container">

        <div class="modal-header">
            <h2 class="modal-title-text">⭐ Revisión de Baja</h2>

            <button type="button" class="btn-cerrar-modal"
                    onclick="cerrarRevisionBaja()">
                &times;
            </button>
        </div>

        <div class="modal-body-scroll">

            <form id="frmRevisionBaja" class="form-grid"
                  action="javascript:void(0);">

                <input type="hidden"
                       id="revision_id_reporte"
                       name="id_reporte">

                <!-- AVISO -->
                <div style="padding:14px 16px; margin-bottom:18px; border-radius:10px; background:rgba(245,158,11,.08); border:1px solid rgba(245,158,11,.30);">

                    <strong>⚠️ Revisión pendiente</strong>

                    <div style="margin-top:5px; font-size:.9rem; opacity:.85;">
                        Revisa la información y asigna una calificación antes de confirmar la baja.
                    </div>

                </div>

                <!-- OPERADOR Y EMPRESA -->
                <div class="form-row">

                    <div class="form-group">
                        <label class="form-label">Operador</label>
                        <input type="text"
                               class="form-control"
                               id="revision_operador"
                               readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Empresa</label>
                        <input type="text"
                               class="form-control"
                               id="revision_empresa"
                               readonly>
                    </div>

                </div>

                <!-- MOTIVO -->
                <div class="form-row">

                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Motivo de Baja</label>

                        <input type="text"
                               class="form-control"
                               id="revision_motivo"
                               readonly>
                    </div>

                </div>

                <!-- CALIFICACIÓN -->
                <div style="margin-top:10px; padding-top:15px; border-top:1px solid var(--borde-sutil);">

                    <label class="form-label"
                           style="display:block; margin-bottom:12px; font-weight:700;">
                        ⭐ Calificación del Operador (1 - 10)
                    </label>

                    <input type="hidden"
                           name="calificacion_cuantitativa"
                           id="calificacion_cuantitativa"
                           value="">

                    <div class="rating-container">

                        <?php for ($i = 1; $i <= 10; $i++): ?>

                            <button type="button"
                                    class="rating-btn"
                                    data-value="<?= $i ?>"
                                    onclick="seleccionarCalificacion(<?= $i ?>, this)">
                                <?= $i ?>
                            </button>

                        <?php endfor; ?>

                    </div>

                    <!-- NUEVO: MOSTRAR CALIFICACIÓN ELEGIDA -->
                    <div id="calificacionSeleccionada"
                         style="
                            margin-top:15px;
                            text-align:center;
                            font-weight:bold;
                            font-size:1rem;
                            color:#cbd5e1;
                         ">
                        Calificación seleccionada: —
                    </div>

                </div>

                <!-- ADVERTENCIA -->
                <div style="margin-top:20px; padding:14px 16px; border-radius:10px; background:rgba(220,38,38,.08); border:1px solid rgba(220,38,38,.30);">

                    <strong>🚨 Baja definitiva</strong>

                    <div style="margin-top:5px; font-size:.9rem; opacity:.85;">
                        Al confirmar, el operador será marcado como inactivo y la evaluación quedará completada.
                    </div>

                </div>

                <!-- BOTONES -->
                <div class="form-actions"
                     style="margin-top:22px; display:flex; gap:12px; justify-content:flex-end;">

                    <button type="button"
                            class="btn-action btn-delete"
                            onclick="cerrarRevisionBaja()">
                        Cancelar
                    </button>

                    <button type="button"
                            class="btn-prof-primary"
                            id="btnConfirmarBaja"
                            onclick="confirmarBaja()">
                        Confirmar Baja
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>
<?php endif; ?>