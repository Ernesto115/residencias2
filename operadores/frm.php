<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$rolSesion = strtoupper($_SESSION['rol'] ?? '');
$idUsuarioSesion = (int)($_SESSION['id_usuario'] ?? 0);
$idEmpresaSesion = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresaSesion = (int)($_SESSION['multiempresa'] ?? 0);

$empresasPermitidas = [];
$conexionFrmLocal = false;

if (isset($dbtransportistas)) {
    $dbFrm = $dbtransportistas;
} elseif (isset($db)) {
    $dbFrm = $db;
} else {
    include_once "../db/db.php";
    $dbFrm = new db();
    $dbFrm->conectar();
    $conexionFrmLocal = true;
}

$sqlEmpresas = "";

if ($rolSesion === 'ADMIN') {
    $sqlEmpresas = "SELECT id_empresa, nombre_empresa FROM empresas ORDER BY nombre_empresa ASC";

} elseif ($rolSesion === 'PROPIETARIO') {

    if ($multiempresaSesion === 1 && $idUsuarioSesion > 0) {
        $sqlEmpresas = "
            SELECT e.id_empresa, e.nombre_empresa
            FROM empresas e
            INNER JOIN usuario_empresas ue ON ue.id_empresa = e.id_empresa
            WHERE ue.id_usuario = $idUsuarioSesion
            ORDER BY e.nombre_empresa ASC
        ";
    } elseif ($idEmpresaSesion > 0) {
        $sqlEmpresas = "
            SELECT id_empresa, nombre_empresa
            FROM empresas
            WHERE id_empresa = $idEmpresaSesion
            LIMIT 1
        ";
    }

} elseif ($rolSesion === 'RRHH' && $idEmpresaSesion > 0) {
    $sqlEmpresas = "
        SELECT id_empresa, nombre_empresa
        FROM empresas
        WHERE id_empresa = $idEmpresaSesion
        LIMIT 1
    ";
}

if ($sqlEmpresas !== "") {
    $empresasPermitidas = $dbFrm->obtenerRegistros($sqlEmpresas);
}

if ($conexionFrmLocal) $dbFrm->desconectar();

$puedeElegirEmpresa =
    $rolSesion === 'ADMIN' ||
    ($rolSesion === 'PROPIETARIO' && $multiempresaSesion === 1);

$empresaFija = (!$puedeElegirEmpresa && !empty($empresasPermitidas))
    ? $empresasPermitidas[0]
    : null;
?>


<!-- BOTÓN AGREGAR -->
<div class="table-header-title">
    <div class="table-tabs-wrapper">
        <button type="button" class="btn-agregar-op" onclick="abrirModalOperador()">
            + Agregar Operador
        </button>
    </div>
</div>


<!-- MODAL -->
<div id="modalOperador" class="modal-overlay">
<div class="modal-container">

    <div class="modal-header">
        <h2 class="modal-title-text">Formulario de Operador</h2>
        <button type="button" class="btn-cerrar-modal" onclick="cerrarModalOperador()">&times;</button>
    </div>

    <div class="modal-body-scroll">

    <form id="frm" class="form-grid" action="javascript:void(0);"
          enctype="multipart/form-data"
          onsubmit="guardar('operadores', 'frm', event)">

        <input type="hidden" id="id_operador" name="id_operador" value="">


        <!-- DATOS PERSONALES -->
        <p style="font-weight:bold; color:var(--accent-color); margin-bottom:10px;">
            👤 Datos Personales (Obligatorio)
        </p>

        <div class="form-row">

            <div class="form-group">
                <label class="form-label">Nombres *</label>
                <input type="text" class="form-control" name="nombres" id="nombres"
                       required maxlength="30" placeholder="Ej. Juan Carlos">
            </div>

            <div class="form-group">
                <label class="form-label">Primer Apellido *</label>
                <input type="text" class="form-control" name="primer_apellido"
                       id="primer_apellido" required maxlength="30" placeholder="Ej. Gómez">
            </div>

            <div class="form-group">
                <label class="form-label">Segundo Apellido *</label>
                <input type="text" class="form-control" name="segundo_apellido"
                       id="segundo_apellido" required maxlength="30" placeholder="Ej. Martínez">
            </div>

        </div>


        <div class="form-row">

            <div class="form-group">
                <label class="form-label">RFC *</label>
                <input type="text" class="form-control" name="rfc" id="rfc"
                       required maxlength="13" placeholder="13 caracteres">
            </div>

            <div class="form-group">
                <label class="form-label">Teléfono Celular *</label>
                <input type="tel" class="form-control" name="telefono_celular"
                       id="telefono_celular" required maxlength="10"
                       placeholder="Ej. 8789876543">
            </div>

            <div class="form-group">
                <label class="form-label">Estatus del Operador</label>
                <select class="form-control" name="estatus" id="estatus">
                    <option value="1" selected>Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>

        </div>


        <!-- EMPRESA Y FECHA -->
        <div class="form-row">

            <div class="form-group">
                <label class="form-label">Empresa *</label>

                <?php if ($puedeElegirEmpresa): ?>

                    <select class="form-control" name="id_empresa" id="id_empresa" required>
                        <option value="">Seleccione una empresa</option>

                        <?php foreach ($empresasPermitidas as $empresa): ?>
                            <option value="<?= (int)$empresa['id_empresa'] ?>">
                                <?= htmlspecialchars($empresa['nombre_empresa']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($empresaFija): ?>

                    <input type="text" class="form-control"
                           value="<?= htmlspecialchars($empresaFija['nombre_empresa']) ?>" readonly>

                    <input type="hidden" name="id_empresa" id="id_empresa"
                           value="<?= (int)$empresaFija['id_empresa'] ?>">

                <?php else: ?>

                    <input type="text" class="form-control"
                           value="No hay una empresa asignada a este usuario" readonly>

                <?php endif; ?>
            </div>


            <div class="form-group">
                <label class="form-label">Fecha de Ingreso *</label>
                <input type="date" class="form-control" name="fecha_ingreso"
                       id="fecha_ingreso" value="<?= date('Y-m-d') ?>" required>
            </div>

        </div>


        <hr style="margin:15px 0; border:0; border-top:1px solid var(--borde-sutil);">


        <!-- DOMICILIO -->
        <p style="font-weight:bold; color:var(--accent-color); margin-bottom:10px;">
            📍 Domicilio - (Rellenar si se cuenta con la información)
        </p>

        <div class="form-row">

            <div class="form-group" style="flex:2;">
                <label class="form-label">Calle y Número</label>
                <input type="text" class="form-control" name="calle_y_numero"
                       id="calle_y_numero" maxlength="50"
                       placeholder="Ej. Av. Industrial #123">
            </div>

            <div class="form-group">
                <label class="form-label">Colonia</label>
                <input type="text" class="form-control" name="colonia"
                       id="colonia" maxlength="30" placeholder="Ej. Centro">
            </div>

            <div class="form-group">
                <label class="form-label">Código Postal</label>
                <input type="text" class="form-control" name="codigo_postal"
                       id="codigo_postal" maxlength="5" placeholder="Ej. 26000">
            </div>

        </div>


        <hr style="margin:15px 0; border:0; border-top:1px solid var(--borde-sutil);">


        <!-- LICENCIA FEDERAL -->
        <p style="font-weight:bold; color:var(--accent-color); margin-bottom:10px;">
            💳 Licencia Federal - (Rellenar si se cuenta con la información)
        </p>

        <div class="form-row">

            <div class="form-group">
                <label class="form-label">No. Licencia Federal Actual</label>
                <input type="text" class="form-control"
                       name="licencia_federal_actual"
                       id="licencia_federal_actual"
                       maxlength="20" placeholder="Ej. 1234567890">
            </div>

            <div class="form-group">
                <label class="form-label">Vencimiento Licencia Federal</label>
                <input type="date" class="form-control"
                       name="vencimiento_lic_federal"
                       id="vencimiento_lic_federal">
            </div>

            <div class="form-group">
                <label class="form-label">PDF Licencia Federal</label>
                <input type="file" class="form-control"
                       name="archivo_pdf_licencia"
                       id="archivo_pdf_licencia"
                       accept="application/pdf">
            </div>

        </div>


        <hr style="margin:15px 0; border:0; border-top:1px solid var(--borde-sutil);">


        <!-- APTO MÉDICO -->
        <p style="font-weight:bold; color:var(--accent-color); margin-bottom:10px;">
            🩺 Apto Médico - (Rellenar si se cuenta con la información)
        </p>

        <div class="form-row">

            <div class="form-group">
                <label class="form-label">No. Apto Médico Actual</label>
                <input type="text" class="form-control"
                       name="apto_medico_actual"
                       id="apto_medico_actual"
                       maxlength="20" placeholder="Ej. 178280">
            </div>

            <div class="form-group">
                <label class="form-label">Vencimiento Apto Médico</label>
                <input type="date" class="form-control"
                       name="vencimiento_apto_medico"
                       id="vencimiento_apto_medico">
            </div>

            <div class="form-group">
                <label class="form-label">PDF Apto Médico</label>
                <input type="file" class="form-control"
                       name="archivo_pdf_apto_medico"
                       id="archivo_pdf_apto_medico"
                       accept="application/pdf">
            </div>

        </div>


        <hr style="margin:15px 0; border:0; border-top:1px solid var(--borde-sutil);">


        <!-- CRUCE INTERNACIONAL -->
        <p style="font-weight:bold; color:var(--accent-color); margin-bottom:10px;">
            🇺🇸 Cruce Internacional - (Rellenar si se cuenta con la información)
        </p>

        <!-- VISA -->
        <div class="form-row">

            <div class="form-group">
                <label class="form-label">No. VISA Laser / LPR</label>
                <input type="text" class="form-control"
                       name="visa" id="visa"
                       maxlength="20" placeholder="Ej. 12 o 13 dígitos">
            </div>

            <div class="form-group">
                <label class="form-label">Vencimiento VISA</label>
                <input type="date" class="form-control"
                       name="vencimiento_visa"
                       id="vencimiento_visa">
            </div>

            <div class="form-group">
                <label class="form-label">PDF VISA</label>
                <input type="file" class="form-control"
                       name="archivo_pdf_visa"
                       id="archivo_pdf_visa"
                       accept="application/pdf">
            </div>

        </div>


        <!-- FAST -->
        <div class="form-row">

            <div class="form-group">
                <label class="form-label">No. Gafete FAST / SENTRI</label>
                <input type="text" class="form-control"
                       name="fast" id="fast"
                       maxlength="20"
                       placeholder="Ej. PASS ID o tradicional">
            </div>

            <div class="form-group">
                <label class="form-label">Vencimiento FAST</label>
                <input type="date" class="form-control"
                       name="vencimiento_fast"
                       id="vencimiento_fast">
            </div>

            <div class="form-group">
                <label class="form-label">PDF FAST</label>
                <input type="file" class="form-control"
                       name="fast_pdf"
                       id="fast_pdf"
                       accept="application/pdf">
            </div>

        </div>


        <div id="contenedor-alertas-operadores" class="mt-3"></div>


        <!-- BOTONES -->
        <div class="form-actions"
             style="margin-top:20px; display:flex; gap:12px; justify-content:flex-end;">

            <button type="button"
                    class="btn-action btn-delete"
                    onclick="cerrarModalOperador()">
                Cancelar
            </button>

            <button type="submit" class="btn-prof-primary">
                Grabar Operador
            </button>

        </div>

    </form>

    </div>
</div>
</div>