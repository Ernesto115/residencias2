<?php
session_start();
include_once "../db/db.php";

$db = new db();
$db->conectar();

/* =========================================================
   1. SESIÓN Y FORMULARIO
   ========================================================= */
$rol = strtoupper($_SESSION['rol'] ?? '');
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa_sesion = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);

$id_operador = (isset($_REQUEST['id_operador']) && is_numeric($_REQUEST['id_operador']))
    ? (int)$_REQUEST['id_operador'] : 0;

$estatus = isset($_REQUEST['estatus']) ? (int)$_REQUEST['estatus'] : 1;
$id_empresa_form = (int)($_REQUEST['id_empresa'] ?? 0);

$rfc = addslashes(trim($_REQUEST['rfc'] ?? ''));
$nombres = addslashes(trim($_REQUEST['nombres'] ?? ''));
$primer_apellido = addslashes(trim($_REQUEST['primer_apellido'] ?? ''));
$segundo_apellido = addslashes(trim($_REQUEST['segundo_apellido'] ?? ''));
$telefono_celular = addslashes(trim($_REQUEST['telefono_celular'] ?? ''));

$calle_y_numero = addslashes(trim($_REQUEST['calle_y_numero'] ?? ''));
$colonia = addslashes(trim($_REQUEST['colonia'] ?? ''));
$codigo_postal = addslashes(trim($_REQUEST['codigo_postal'] ?? ''));

$licencia_federal_actual = addslashes(trim($_REQUEST['licencia_federal_actual'] ?? ''));
$apto_medico_actual = addslashes(trim($_REQUEST['apto_medico_actual'] ?? ''));
$visa = addslashes(trim($_REQUEST['visa'] ?? ''));
$fast = addslashes(trim($_REQUEST['fast'] ?? ''));

/* FECHAS DE DOCUMENTOS */
$vencimiento_lic_federal = !empty($_REQUEST['vencimiento_lic_federal'])
    ? "'" . addslashes($_REQUEST['vencimiento_lic_federal']) . "'" : "NULL";

$vencimiento_apto_medico = !empty($_REQUEST['vencimiento_apto_medico'])
    ? "'" . addslashes($_REQUEST['vencimiento_apto_medico']) . "'" : "NULL";

$vencimiento_visa = !empty($_REQUEST['vencimiento_visa'])
    ? "'" . addslashes($_REQUEST['vencimiento_visa']) . "'" : "NULL";

$vencimiento_fast = !empty($_REQUEST['vencimiento_fast'])
    ? "'" . addslashes($_REQUEST['vencimiento_fast']) . "'" : "NULL";


/* =========================================================
   2. FUNCIÓN DE ERROR
   ========================================================= */
function errorOperador($mensaje, $db, $cerrarModal = false)
{
    $mensajeJS = json_encode(
        $mensaje,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    $cerrarJS = $cerrarModal
        ? "if (typeof cerrarModalOperador === 'function') cerrarModalOperador();"
        : "";

    echo "<!-- Error MySQL -->
    <script>
        $cerrarJS

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Operación no permitida',
                text: $mensajeJS,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#dc2626',
                background: '#1e293b',
                color: '#ffffff'
            });
        } else {
            alert('❌ ' + $mensajeJS);
        }
    </script>";

    $db->desconectar();
    exit;
}


/* =========================================================
   3. EMPRESA SEGÚN ROL
   ========================================================= */
$id_empresa = 0;

if ($rol === 'ADMIN') {

    $id_empresa = $id_empresa_form;

    if ($id_empresa <= 0) {
        errorOperador('Debes seleccionar una empresa.', $db);
    }

    if (empty($db->obtenerRegistros(
        "SELECT id_empresa FROM empresas
         WHERE id_empresa = $id_empresa LIMIT 1"
    ))) {
        errorOperador('La empresa seleccionada no existe.', $db);
    }

} elseif ($rol === 'PROPIETARIO' && $multiempresa === 1) {

    $id_empresa = $id_empresa_form;

    if ($id_empresa <= 0) {
        errorOperador('Debes seleccionar una empresa.', $db);
    }

    if (empty($db->obtenerRegistros(
        "SELECT id_empresa FROM usuario_empresas
         WHERE id_usuario = $id_usuario
         AND id_empresa = $id_empresa LIMIT 1"
    ))) {
        errorOperador('No tienes permiso para utilizar esa empresa.', $db);
    }

} elseif ($rol === 'PROPIETARIO' || $rol === 'RRHH') {

    $id_empresa = $id_empresa_sesion;

    if ($id_empresa <= 0) {
        errorOperador('Tu usuario no tiene una empresa asignada.', $db);
    }

} else {
    errorOperador('No tienes permisos para registrar operadores.', $db);
}


/* =========================================================
   4. VALIDACIONES
   ========================================================= */
if (
    $rfc === '' ||
    $nombres === '' ||
    $primer_apellido === '' ||
    $segundo_apellido === '' ||
    $telefono_celular === ''
) {
    errorOperador('Completa los campos obligatorios.', $db);
}


/* =========================================================
   5. RFC / RECONTRATACIÓN
   ========================================================= */
$recontratacion = false;

/* EDITAR */
if ($id_operador > 0) {

    $duplicado = $db->obtenerRegistros(
        "SELECT id_operador FROM operadores
         WHERE rfc = '$rfc'
         AND id_operador <> $id_operador
         LIMIT 1"
    );

    if (!empty($duplicado)) {
        errorOperador('El RFC introducido ya se encuentra registrado.', $db);
    }

}

/* NUEVO / POSIBLE RECONTRATACIÓN */
else {

    $existente = $db->obtenerRegistros(
        "SELECT o.id_operador, o.estatus, o.id_empresa, e.nombre_empresa
         FROM operadores o
         LEFT JOIN empresas e ON e.id_empresa = o.id_empresa
         WHERE o.rfc = '$rfc'
         LIMIT 1"
    );

    if (!empty($existente)) {

        $opExistente = $existente[0];
        $idExistente = (int)$opExistente['id_operador'];
        $estatusExistente = (int)$opExistente['estatus'];
        $empresaActual = $opExistente['nombre_empresa'] ?? 'otra empresa';


        /* BAJA PENDIENTE */
        $pendiente = $db->obtenerRegistros(
            "SELECT id_reporte FROM reportes_baja
             WHERE id_operador = $idExistente
             AND estatus_evaluacion = 'PENDIENTE'
             LIMIT 1"
        );

        if (!empty($pendiente)) {
            errorOperador(
                'Este operador tiene una baja pendiente. Debe completarse antes de poder contratarlo.',
                $db,
                true
            );
        }


        /* SIGUE ACTIVO */
        if ($estatusExistente === 1) {
            errorOperador(
                'Este operador ya se encuentra activo en ' . $empresaActual . '.',
                $db,
                true
            );
        }


        /* ÚLTIMA BAJA */
        $ultimaBaja = $db->obtenerRegistros(
            "SELECT id_reporte, estatus_evaluacion
             FROM reportes_baja
             WHERE id_operador = $idExistente
             ORDER BY id_reporte DESC
             LIMIT 1"
        );

        if (
            empty($ultimaBaja) ||
            strtoupper($ultimaBaja[0]['estatus_evaluacion']) !== 'COMPLETADA'
        ) {
            errorOperador(
                'El operador está inactivo, pero no cuenta con una baja completada.',
                $db,
                true
            );
        }


        /* ✅ RECONTRATACIÓN */
        $id_operador = $idExistente;
        $estatus = 1;
        $recontratacion = true;
    }
}


/* =========================================================
   6. SEGURIDAD AL EDITAR
   ========================================================= */
if ($id_operador > 0 && !$recontratacion && $rol !== 'ADMIN') {

    $actual = $db->obtenerRegistros(
        "SELECT id_empresa FROM operadores
         WHERE id_operador = $id_operador LIMIT 1"
    );

    if (empty($actual)) {
        errorOperador('El operador no existe.', $db);
    }

    $empresa_actual = (int)$actual[0]['id_empresa'];

    if ($rol === 'PROPIETARIO' && $multiempresa === 1) {

        $permiso = $db->obtenerRegistros(
            "SELECT id_empresa FROM usuario_empresas
             WHERE id_usuario = $id_usuario
             AND id_empresa = $empresa_actual LIMIT 1"
        );

        if (empty($permiso)) {
            errorOperador('No puedes editar operadores de otra empresa.', $db);
        }

    } elseif ($empresa_actual !== $id_empresa_sesion) {
        errorOperador('No puedes editar operadores de otra empresa.', $db);
    }
}


/* =========================================================
   7. ARCHIVOS PDF
   ========================================================= */
$dir_subida = "../uploads/pdf/";

if (!file_exists($dir_subida)) {
    mkdir($dir_subida, 0777, true);
}

function subirPDF($campo, $prefijo, $dir)
{
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {

        $nombre = time() . "_" . $prefijo . "_" .
                  basename($_FILES[$campo]['name']);

        if (move_uploaded_file($_FILES[$campo]['tmp_name'], $dir . $nombre)) {
            return $nombre;
        }
    }

    return "";
}

$archivo_pdf_licencia = subirPDF('archivo_pdf_licencia', 'lic', $dir_subida);
$archivo_pdf_apto_medico = subirPDF('archivo_pdf_apto_medico', 'med', $dir_subida);
$archivo_pdf_visa = subirPDF('archivo_pdf_visa', 'visa', $dir_subida);
$fast_pdf = subirPDF('fast_pdf', 'fast', $dir_subida);


/* =========================================================
   8. ACTUALIZAR / RECONTRATAR
   ========================================================= */
if ($id_operador > 0) {

    $sql = "UPDATE operadores SET
            id_empresa = $id_empresa,
            estatus = $estatus,
            rfc = '$rfc',
            nombres = '$nombres',
            primer_apellido = '$primer_apellido',
            segundo_apellido = '$segundo_apellido',
            telefono_celular = '$telefono_celular',
            calle_y_numero = '$calle_y_numero',
            colonia = '$colonia',
            codigo_postal = '$codigo_postal',
            licencia_federal_actual = '$licencia_federal_actual',
            vencimiento_lic_federal = $vencimiento_lic_federal,
            apto_medico_actual = '$apto_medico_actual',
            vencimiento_apto_medico = $vencimiento_apto_medico,
            visa = '$visa',
            vencimiento_visa = $vencimiento_visa,
            fast = '$fast',
            vencimiento_fast = $vencimiento_fast";


    /* SOLO EN RECONTRATACIÓN SE CAMBIA FECHA DE INGRESO */
    if ($recontratacion) {
        $sql .= ", fecha_ingreso = CURDATE()";
    }


    if ($archivo_pdf_licencia !== '') {
        $sql .= ", archivo_pdf_licencia = '$archivo_pdf_licencia'";
    }

    if ($archivo_pdf_apto_medico !== '') {
        $sql .= ", archivo_pdf_apto_medico = '$archivo_pdf_apto_medico'";
    }

    if ($archivo_pdf_visa !== '') {
        $sql .= ", archivo_pdf_visa = '$archivo_pdf_visa'";
    }

    if ($fast_pdf !== '') {
        $sql .= ", fast_pdf = '$fast_pdf'";
    }

    $sql .= " WHERE id_operador = $id_operador";

    $db->actualizar($sql);
}


/* =========================================================
   9. OPERADOR NUEVO
   ========================================================= */
else {

    $sql = "INSERT INTO operadores (
                id_empresa, fecha_ingreso, estatus,
                rfc, nombres, primer_apellido, segundo_apellido,
                telefono_celular, calle_y_numero, colonia, codigo_postal,
                licencia_federal_actual, vencimiento_lic_federal, archivo_pdf_licencia,
                apto_medico_actual, vencimiento_apto_medico, archivo_pdf_apto_medico,
                visa, vencimiento_visa, archivo_pdf_visa,
                fast, vencimiento_fast, fast_pdf
            ) VALUES (
                $id_empresa, CURDATE(), $estatus,
                '$rfc', '$nombres', '$primer_apellido', '$segundo_apellido',
                '$telefono_celular', '$calle_y_numero', '$colonia', '$codigo_postal',
                '$licencia_federal_actual', $vencimiento_lic_federal, '$archivo_pdf_licencia',
                '$apto_medico_actual', $vencimiento_apto_medico, '$archivo_pdf_apto_medico',
                '$visa', $vencimiento_visa, '$archivo_pdf_visa',
                '$fast', $vencimiento_fast, '$fast_pdf'
            )";

    $db->insertar($sql);
}


/* =========================================================
   10. ACTUALIZAR TABLA
   ========================================================= */
$id_operador = 0;

if (file_exists("tabla.php")) {
    include "tabla.php";
} elseif (file_exists("../operadores/tabla.php")) {
    include "../operadores/tabla.php";
}

$db->desconectar();
?>