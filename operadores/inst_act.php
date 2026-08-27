<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include_once "../db/db.php";
$db = new db();
$db->conectar();

$rol = strtoupper(trim($_SESSION['rol'] ?? ''));

if ($rol === 'ADMINISTRADOR') $rol = 'ADMIN';
if (in_array($rol, ['RH','RECURSOS HUMANOS'], true)) $rol = 'RRHH';

$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa_sesion = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);

function errorOperador($mensaje, $db, $cerrar = false)
{
    $msg = json_encode($mensaje, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $cerrarJS = $cerrar
        ? "if(typeof cerrarModalOperador==='function') cerrarModalOperador();"
        : "";

    echo "<!-- Error MySQL -->
    <script>
        $cerrarJS
        if(typeof Swal!=='undefined'){
            Swal.fire({
                icon:'error',
                title:'Operación no permitida',
                text:$msg,
                confirmButtonText:'Entendido',
                confirmButtonColor:'#dc2626'
            });
        }else alert('❌ '+$msg);
    </script>";

    $db->desconectar();
    exit;
}

if (!in_array($rol, ['ADMIN','PROPIETARIO','RRHH'], true)) {
    errorOperador('No tienes permisos para registrar o editar operadores.', $db);
}

$id_operador = is_numeric($_REQUEST['id_operador'] ?? null)
    ? (int)$_REQUEST['id_operador'] : 0;

$id_empresa_form = (int)($_REQUEST['id_empresa'] ?? 0);

function campo($nombre) {
    return addslashes(trim($_REQUEST[$nombre] ?? ''));
}

$rfc = campo('rfc');
$nombres = campo('nombres');
$primer_apellido = campo('primer_apellido');
$segundo_apellido = campo('segundo_apellido');
$telefono_celular = campo('telefono_celular');
$calle_y_numero = campo('calle_y_numero');
$colonia = campo('colonia');
$codigo_postal = campo('codigo_postal');
$licencia_federal_actual = campo('licencia_federal_actual');
$apto_medico_actual = campo('apto_medico_actual');
$visa = campo('visa');
$fast = campo('fast');

function fechaSQL($campo)
{
    $fecha = trim($_REQUEST[$campo] ?? '');
    return $fecha === '' ? 'NULL' : "'" . addslashes($fecha) . "'";
}

$vencimiento_lic_federal = fechaSQL('vencimiento_lic_federal');
$vencimiento_apto_medico = fechaSQL('vencimiento_apto_medico');
$vencimiento_visa = fechaSQL('vencimiento_visa');
$vencimiento_fast = fechaSQL('vencimiento_fast');


/* EMPRESA PERMITIDA */
if ($rol === 'ADMIN') {

    $id_empresa = $id_empresa_form;

    if ($id_empresa <= 0 ||
        empty($db->obtenerRegistros(
            "SELECT id_empresa FROM empresas
             WHERE id_empresa=$id_empresa LIMIT 1"
        ))) {
        errorOperador('La empresa seleccionada no es válida.', $db);
    }

} elseif ($rol === 'PROPIETARIO' && $multiempresa === 1) {

    $id_empresa = $id_empresa_form;

    if ($id_empresa <= 0 ||
        empty($db->obtenerRegistros(
            "SELECT id_empresa FROM usuario_empresas
             WHERE id_usuario=$id_usuario
             AND id_empresa=$id_empresa LIMIT 1"
        ))) {
        errorOperador('No tienes permiso para utilizar esa empresa.', $db);
    }

} else {

    $id_empresa = $id_empresa_sesion;

    if ($id_empresa <= 0) {
        errorOperador('Tu usuario no tiene una empresa asignada.', $db);
    }
}


/* OBLIGATORIOS */
if (
    $rfc === '' ||
    $nombres === '' ||
    $primer_apellido === '' ||
    $segundo_apellido === '' ||
    $telefono_celular === ''
) {
    errorOperador('Completa los campos obligatorios.', $db);
}


$recontratacion = false;


/* EDITAR OPERADOR EXISTENTE */
if ($id_operador > 0) {

    $actual = $db->obtenerRegistros(
        "SELECT id_empresa,estatus
         FROM operadores
         WHERE id_operador=$id_operador LIMIT 1"
    );

    if (empty($actual)) {
        errorOperador('El operador no existe.', $db);
    }

    $empresa_actual = (int)$actual[0]['id_empresa'];
    $estatus_actual = (int)$actual[0]['estatus'];

    if ($estatus_actual !== 1) {
        errorOperador(
            'Un operador inactivo no puede editarse directamente. Utiliza el proceso de recontratación.',
            $db,
            true
        );
    }

    if ($rol === 'PROPIETARIO' && $multiempresa === 1) {

        if (empty($db->obtenerRegistros(
            "SELECT id_empresa FROM usuario_empresas
             WHERE id_usuario=$id_usuario
             AND id_empresa=$empresa_actual LIMIT 1"
        ))) {
            errorOperador('No puedes editar operadores de otra empresa.', $db);
        }

    } elseif ($rol !== 'ADMIN' && $empresa_actual !== $id_empresa_sesion) {

        errorOperador('No puedes editar operadores de otra empresa.', $db);
    }

    $duplicado = $db->obtenerRegistros(
        "SELECT id_operador FROM operadores
         WHERE rfc='$rfc'
         AND id_operador<>$id_operador LIMIT 1"
    );

    if (!empty($duplicado)) {
        errorOperador('El RFC introducido ya se encuentra registrado.', $db);
    }

}


/* NUEVO / RECONTRATACIÓN */
else {

    $existente = $db->obtenerRegistros(
        "SELECT o.id_operador,o.estatus,e.nombre_empresa
         FROM operadores o
         LEFT JOIN empresas e ON e.id_empresa=o.id_empresa
         WHERE o.rfc='$rfc' LIMIT 1"
    );

    if (!empty($existente)) {

        $op = $existente[0];
        $idExistente = (int)$op['id_operador'];

        $pendiente = $db->obtenerRegistros(
            "SELECT id_reporte FROM reportes_baja
             WHERE id_operador=$idExistente
             AND estatus_evaluacion='PENDIENTE'
             LIMIT 1"
        );

        if (!empty($pendiente)) {
            errorOperador(
                'Este operador tiene una baja pendiente. Debe completarse antes de contratarlo.',
                $db,
                true
            );
        }

        if ((int)$op['estatus'] === 1) {
            errorOperador(
                'Este operador ya se encuentra activo en ' .
                ($op['nombre_empresa'] ?? 'otra empresa') . '.',
                $db,
                true
            );
        }

        $ultimaBaja = $db->obtenerRegistros(
            "SELECT estatus_evaluacion
             FROM reportes_baja
             WHERE id_operador=$idExistente
             ORDER BY id_reporte DESC LIMIT 1"
        );

        if (
            empty($ultimaBaja) ||
            strtoupper($ultimaBaja[0]['estatus_evaluacion']) !== 'COMPLETADA'
        ) {
            errorOperador(
                'El operador está inactivo pero no cuenta con una baja completada.',
                $db,
                true
            );
        }

        $id_operador = $idExistente;
        $recontratacion = true;
    }
}


/* PDFs */
$dir = "../uploads/pdf/";

if (!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) {
    errorOperador('No fue posible preparar la carpeta de documentos.', $db);
}

function subirPDF($campo, $prefijo, $dir, $db)
{
    if (
        !isset($_FILES[$campo]) ||
        $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE
    ) return '';

    $a = $_FILES[$campo];

    if ($a['error'] !== UPLOAD_ERR_OK) {
        errorOperador('No fue posible subir el documento.', $db);
    }

    if ((int)$a['size'] <= 0 || (int)$a['size'] > 10 * 1024 * 1024) {
        errorOperador('Cada PDF debe pesar máximo 10 MB.', $db);
    }

    if (strtolower(pathinfo($a['name'], PATHINFO_EXTENSION)) !== 'pdf') {
        errorOperador('Solo se permiten archivos PDF.', $db);
    }

    $firma = @file_get_contents($a['tmp_name'], false, null, 0, 5);

    if ($firma !== '%PDF-') {
        errorOperador('El archivo seleccionado no es un PDF válido.', $db);
    }

    if (function_exists('mime_content_type')) {
        $mime = @mime_content_type($a['tmp_name']);

        if (
            $mime !== false &&
            !in_array(
                $mime,
                ['application/pdf','application/x-pdf','application/octet-stream'],
                true
            )
        ) {
            errorOperador('El tipo de archivo no está permitido.', $db);
        }
    }

    $nombre = $prefijo . '_' .
              date('Ymd_His') . '_' .
              bin2hex(random_bytes(5)) . '.pdf';

    if (!move_uploaded_file($a['tmp_name'], $dir.$nombre)) {
        errorOperador('No fue posible guardar el PDF.', $db);
    }

    return $nombre;
}

$pdfLic = subirPDF('archivo_pdf_licencia','lic',$dir,$db);
$pdfMed = subirPDF('archivo_pdf_apto_medico','med',$dir,$db);
$pdfVisa = subirPDF('archivo_pdf_visa','visa',$dir,$db);
$pdfFast = subirPDF('fast_pdf','fast',$dir,$db);


/* ACTUALIZAR / RECONTRATAR */
if ($id_operador > 0) {

    $sql = "UPDATE operadores SET
        id_empresa=$id_empresa,
        rfc='$rfc',
        nombres='$nombres',
        primer_apellido='$primer_apellido',
        segundo_apellido='$segundo_apellido',
        telefono_celular='$telefono_celular',
        calle_y_numero='$calle_y_numero',
        colonia='$colonia',
        codigo_postal='$codigo_postal',
        licencia_federal_actual='$licencia_federal_actual',
        vencimiento_lic_federal=$vencimiento_lic_federal,
        apto_medico_actual='$apto_medico_actual',
        vencimiento_apto_medico=$vencimiento_apto_medico,
        visa='$visa',
        vencimiento_visa=$vencimiento_visa,
        fast='$fast',
        vencimiento_fast=$vencimiento_fast";

    if ($recontratacion) {
        $sql .= ", estatus=1, fecha_ingreso=CURDATE()";
    }

    if ($pdfLic !== '')  $sql .= ", archivo_pdf_licencia='$pdfLic'";
    if ($pdfMed !== '')  $sql .= ", archivo_pdf_apto_medico='$pdfMed'";
    if ($pdfVisa !== '') $sql .= ", archivo_pdf_visa='$pdfVisa'";
    if ($pdfFast !== '') $sql .= ", fast_pdf='$pdfFast'";

    $sql .= " WHERE id_operador=$id_operador";

    $db->actualizar($sql);

}


/* NUEVO */
else {

    $sql = "INSERT INTO operadores (
        id_empresa,fecha_ingreso,estatus,
        rfc,nombres,primer_apellido,segundo_apellido,
        telefono_celular,calle_y_numero,colonia,codigo_postal,
        licencia_federal_actual,vencimiento_lic_federal,archivo_pdf_licencia,
        apto_medico_actual,vencimiento_apto_medico,archivo_pdf_apto_medico,
        visa,vencimiento_visa,archivo_pdf_visa,
        fast,vencimiento_fast,fast_pdf
    ) VALUES (
        $id_empresa,CURDATE(),1,
        '$rfc','$nombres','$primer_apellido','$segundo_apellido',
        '$telefono_celular','$calle_y_numero','$colonia','$codigo_postal',
        '$licencia_federal_actual',$vencimiento_lic_federal,'$pdfLic',
        '$apto_medico_actual',$vencimiento_apto_medico,'$pdfMed',
        '$visa',$vencimiento_visa,'$pdfVisa',
        '$fast',$vencimiento_fast,'$pdfFast'
    )";

    $db->insertar($sql);
}


/* ACTUALIZAR TABLA */
$id_operador = 0;

if (file_exists("tabla.php")) {
    include "tabla.php";
} else {
    include "../operadores/tabla.php";
}

$db->desconectar();
?>