<?php
session_start();

include_once "../db/db.php";
$db = new db();
$db->conectar();

/* =========================================================
   1. SESIÓN Y DATOS DEL FORMULARIO
   ========================================================= */

$rol = strtoupper($_SESSION['rol'] ?? '');
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa_sesion = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);

$id_operador = isset($_REQUEST['id_operador']) && is_numeric($_REQUEST['id_operador']) ? (int)$_REQUEST['id_operador'] : 0;
$estatus = isset($_REQUEST['estatus']) ? (int)$_REQUEST['estatus'] : 1;
$id_empresa_form = isset($_REQUEST['id_empresa']) ? (int)$_REQUEST['id_empresa'] : 0;

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


/* =========================================================
   2. FECHAS
   ========================================================= */

$vencimiento_lic_federal = !empty($_REQUEST['vencimiento_lic_federal']) ? "'" . addslashes($_REQUEST['vencimiento_lic_federal']) . "'" : "NULL";
$vencimiento_apto_medico = !empty($_REQUEST['vencimiento_apto_medico']) ? "'" . addslashes($_REQUEST['vencimiento_apto_medico']) . "'" : "NULL";
$vencimiento_visa = !empty($_REQUEST['vencimiento_visa']) ? "'" . addslashes($_REQUEST['vencimiento_visa']) . "'" : "NULL";
$vencimiento_fast = !empty($_REQUEST['vencimiento_fast']) ? "'" . addslashes($_REQUEST['vencimiento_fast']) . "'" : "NULL";

$fecha_ingreso = !empty($_REQUEST['fecha_ingreso']) ? "'" . addslashes($_REQUEST['fecha_ingreso']) . "'" : "NULL";


/* =========================================================
   3. FUNCIÓN PARA MOSTRAR ERRORES
   ========================================================= */

function errorOperador($mensaje, $db)
{
    echo "<!-- Error MySQL -->
    <script>
        if (typeof mostrarToast === 'function') {
            mostrarToast('⚠️ " . addslashes($mensaje) . "');
        } else {
            alert('⚠️ " . addslashes($mensaje) . "');
        }
    </script>";

    $db->desconectar();
    exit;
}


/* =========================================================
   4. DETERMINAR EMPRESA SEGÚN EL ROL
   ========================================================= */

$id_empresa = 0;

/* ADMIN: puede seleccionar cualquier empresa */
if ($rol === 'ADMIN') {

    $id_empresa = $id_empresa_form;

    if ($id_empresa <= 0) {
        errorOperador('Debes seleccionar una empresa.', $db);
    }

    $empresa = $db->obtenerRegistros(
        "SELECT id_empresa FROM empresas WHERE id_empresa = $id_empresa LIMIT 1"
    );

    if (empty($empresa)) {
        errorOperador('La empresa seleccionada no existe.', $db);
    }

/* PROPIETARIO MULTIEMPRESA */
} elseif ($rol === 'PROPIETARIO' && $multiempresa === 1) {

    $id_empresa = $id_empresa_form;

    if ($id_empresa <= 0) {
        errorOperador('Debes seleccionar una empresa.', $db);
    }

    $permiso = $db->obtenerRegistros(
        "SELECT id_empresa
         FROM usuario_empresas
         WHERE id_usuario = $id_usuario
         AND id_empresa = $id_empresa
         LIMIT 1"
    );

    if (empty($permiso)) {
        errorOperador('No tienes permiso para utilizar esa empresa.', $db);
    }

/* PROPIETARIO DE UNA EMPRESA O RRHH */
} elseif ($rol === 'PROPIETARIO' || $rol === 'RRHH') {

    $id_empresa = $id_empresa_sesion;

    if ($id_empresa <= 0) {
        errorOperador('Tu usuario no tiene una empresa asignada.', $db);
    }

} else {
    errorOperador('No tienes permisos para registrar operadores.', $db);
}


/* =========================================================
   5. VALIDACIONES
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


/* Revisar RFC duplicado */
$sqlRFC = "SELECT id_operador FROM operadores WHERE rfc = '$rfc'";

if ($id_operador > 0) {
    $sqlRFC .= " AND id_operador <> $id_operador";
}

$sqlRFC .= " LIMIT 1";

if (!empty($db->obtenerRegistros($sqlRFC))) {
    errorOperador('El RFC introducido ya se encuentra registrado.', $db);
}


/* =========================================================
   6. SEGURIDAD AL EDITAR
   ========================================================= */

if ($id_operador > 0 && $rol !== 'ADMIN') {

    $actual = $db->obtenerRegistros(
        "SELECT id_empresa
         FROM operadores
         WHERE id_operador = $id_operador
         LIMIT 1"
    );

    if (empty($actual)) {
        errorOperador('El operador no existe.', $db);
    }

    $empresa_actual = (int)$actual[0]['id_empresa'];

    if ($rol === 'PROPIETARIO' && $multiempresa === 1) {

        $permiso = $db->obtenerRegistros(
            "SELECT id_empresa
             FROM usuario_empresas
             WHERE id_usuario = $id_usuario
             AND id_empresa = $empresa_actual
             LIMIT 1"
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
        $nombre = time() . "_" . $prefijo . "_" . basename($_FILES[$campo]['name']);

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
   8. ACTUALIZAR OPERADOR
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

    /* Solo cambiar fecha de ingreso si el formulario la envía */
    if (isset($_REQUEST['fecha_ingreso'])) {
        $sql .= ", fecha_ingreso = $fecha_ingreso";
    }

    /* Solo reemplazar PDF si se subió uno nuevo */
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


/* =========================================================
   9. INSERTAR OPERADOR
   ========================================================= */

} else {

    $sql = "INSERT INTO operadores (
                id_empresa, fecha_ingreso, estatus,
                rfc, nombres, primer_apellido, segundo_apellido, telefono_celular,
                calle_y_numero, colonia, codigo_postal,
                licencia_federal_actual, vencimiento_lic_federal, archivo_pdf_licencia,
                apto_medico_actual, vencimiento_apto_medico, archivo_pdf_apto_medico,
                visa, vencimiento_visa, archivo_pdf_visa,
                fast, vencimiento_fast, fast_pdf
            ) VALUES (
                $id_empresa, $fecha_ingreso, $estatus,
                '$rfc', '$nombres', '$primer_apellido', '$segundo_apellido', '$telefono_celular',
                '$calle_y_numero', '$colonia', '$codigo_postal',
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