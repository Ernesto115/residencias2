<?php
include_once "../db/db.php";
$db = new db();
$db->conectar();

// 1. Recepción y saneamiento de campos del formulario
$id_operador             = isset($_REQUEST['id_operador']) && is_numeric($_REQUEST['id_operador']) ? intval($_REQUEST['id_operador']) : 0;
$estatus                 = isset($_REQUEST['estatus']) ? intval($_REQUEST['estatus']) : 1;

$rfc                     = isset($_REQUEST['rfc']) ? addslashes(trim($_REQUEST['rfc'])) : '';
$nombres                 = isset($_REQUEST['nombres']) ? addslashes(trim($_REQUEST['nombres'])) : '';
$primer_apellido         = isset($_REQUEST['primer_apellido']) ? addslashes(trim($_REQUEST['primer_apellido'])) : '';
$segundo_apellido        = isset($_REQUEST['segundo_apellido']) ? addslashes(trim($_REQUEST['segundo_apellido'])) : '';
$telefono_celular        = isset($_REQUEST['telefono_celular']) ? addslashes(trim($_REQUEST['telefono_celular'])) : '';

$calle_y_numero          = isset($_REQUEST['calle_y_numero']) ? addslashes(trim($_REQUEST['calle_y_numero'])) : '';
$colonia                 = isset($_REQUEST['colonia']) ? addslashes(trim($_REQUEST['colonia'])) : '';
$codigo_postal           = isset($_REQUEST['codigo_postal']) ? addslashes(trim($_REQUEST['codigo_postal'])) : '';

$licencia_federal_actual = isset($_REQUEST['licencia_federal_actual']) ? addslashes(trim($_REQUEST['licencia_federal_actual'])) : '';
$apto_medico_actual      = isset($_REQUEST['apto_medico_actual']) ? addslashes(trim($_REQUEST['apto_medico_actual'])) : '';
$visa                    = isset($_REQUEST['visa']) ? addslashes(trim($_REQUEST['visa'])) : '';
$fast                    = isset($_REQUEST['fast']) ? addslashes(trim($_REQUEST['fast'])) : '';

// Manejo seguro para campos de fecha de MySQL (asigna 'YYYY-MM-DD' o NULL)
$vencimiento_lic_federal = !empty($_REQUEST['vencimiento_lic_federal']) ? "'".addslashes($_REQUEST['vencimiento_lic_federal'])."'" : "NULL";
$vencimiento_apto_medico = !empty($_REQUEST['vencimiento_apto_medico']) ? "'".addslashes($_REQUEST['vencimiento_apto_medico'])."'" : "NULL";
$vencimiento_visa        = !empty($_REQUEST['vencimiento_visa']) ? "'".addslashes($_REQUEST['vencimiento_visa'])."'" : "NULL";
$vencimiento_fast        = !empty($_REQUEST['vencimiento_fast']) ? "'".addslashes($_REQUEST['vencimiento_fast'])."'" : "NULL";

// 2. Procesamiento de archivos PDF
$dir_subida = "../uploads/pdf/";
if (!file_exists($dir_subida)) {
    mkdir($dir_subida, 0777, true);
}

$archivo_pdf_licencia = "";
if (isset($_FILES['archivo_pdf_licencia']) && $_FILES['archivo_pdf_licencia']['error'] === UPLOAD_ERR_OK) {
    $nombre_pdf_lic = time() . "_lic_" . basename($_FILES['archivo_pdf_licencia']['name']);
    move_uploaded_file($_FILES['archivo_pdf_licencia']['tmp_name'], $dir_subida . $nombre_pdf_lic);
    $archivo_pdf_licencia = $nombre_pdf_lic;
}

$archivo_pdf_apto_medico = "";
if (isset($_FILES['archivo_pdf_apto_medico']) && $_FILES['archivo_pdf_apto_medico']['error'] === UPLOAD_ERR_OK) {
    $nombre_pdf_med = time() . "_med_" . basename($_FILES['archivo_pdf_apto_medico']['name']);
    move_uploaded_file($_FILES['archivo_pdf_apto_medico']['tmp_name'], $dir_subida . $nombre_pdf_med);
    $archivo_pdf_apto_medico = $nombre_pdf_med;
}

$archivo_pdf_visa = "";
if (isset($_FILES['archivo_pdf_visa']) && $_FILES['archivo_pdf_visa']['error'] === UPLOAD_ERR_OK) {
    $nombre_pdf_visa = time() . "_visa_" . basename($_FILES['archivo_pdf_visa']['name']);
    move_uploaded_file($_FILES['archivo_pdf_visa']['tmp_name'], $dir_subida . $nombre_pdf_visa);
    $archivo_pdf_visa = $nombre_pdf_visa;
}

$fast_pdf = "";
if (isset($_FILES['fast_pdf']) && $_FILES['fast_pdf']['error'] === UPLOAD_ERR_OK) {
    $nombre_pdf_fast = time() . "_fast_" . basename($_FILES['fast_pdf']['name']);
    move_uploaded_file($_FILES['fast_pdf']['tmp_name'], $dir_subida . $nombre_pdf_fast);
    $fast_pdf = $nombre_pdf_fast;
}

// 3. Ejecución de Query (UPDATE o INSERT)
if ($id_operador > 0) {
    // ACTUALIZAR REGISTRO EXISTENTE
    $sql = "UPDATE operadores SET 
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

    if (!empty($archivo_pdf_licencia))   $sql .= ", archivo_pdf_licencia = '$archivo_pdf_licencia'";
    if (!empty($archivo_pdf_apto_medico)) $sql .= ", archivo_pdf_apto_medico = '$archivo_pdf_apto_medico'";
    if (!empty($archivo_pdf_visa))        $sql .= ", archivo_pdf_visa = '$archivo_pdf_visa'";
    if (!empty($fast_pdf))                $sql .= ", fast_pdf = '$fast_pdf'";

    $sql .= " WHERE id_operador = $id_operador";
    $db->actualizar($sql);
} else {
    // INSERTAR NUEVO REGISTRO
    $sql = "INSERT INTO operadores (
                estatus, rfc, nombres, primer_apellido, segundo_apellido, 
                telefono_celular, calle_y_numero, colonia, codigo_postal, 
                licencia_federal_actual, vencimiento_lic_federal, archivo_pdf_licencia,
                apto_medico_actual, vencimiento_apto_medico, archivo_pdf_apto_medico,
                visa, vencimiento_visa, archivo_pdf_visa,
                fast, vencimiento_fast, fast_pdf
            ) VALUES (
                $estatus, '$rfc', '$nombres', '$primer_apellido', '$segundo_apellido', 
                '$telefono_celular', '$calle_y_numero', '$colonia', '$codigo_postal', 
                '$licencia_federal_actual', $vencimiento_lic_federal, '$archivo_pdf_licencia',
                '$apto_medico_actual', $vencimiento_apto_medico, '$archivo_pdf_apto_medico',
                '$visa', $vencimiento_visa, '$archivo_pdf_visa',
                '$fast', $vencimiento_fast, '$fast_pdf'
            )";

    $res = $db->insertar($sql);

    // Capturar error si el RFC ya existe en la base de datos (Error 1062 de MySQL)
    if (!$res && isset($db->conexion->errno) && $db->conexion->errno === 1062) {
        echo "<script>
            if (typeof mostrarToast === 'function') {
                mostrarToast('⚠️ El RFC introducido ya se encuentra registrado.');
            } else {
                alert('⚠️ El RFC introducido ya existe en el sistema.');
            }
        </script>";
    }
}

// 4. Reinicio de variable para garantizar la carga de toda la lista
$id_operador = 0;

// 5. Cargar tabla actualizada con los operadores activos
$sql = "SELECT * FROM operadores WHERE estatus = 1 ORDER BY id_operador DESC";
$datos2 = $db->obtenerRegistros($sql);
$db->desconectar();

if (file_exists("tabla.php")) {
    include "tabla.php";
} elseif (file_exists("../operadores/tabla.php")) {
    include "../operadores/tabla.php";
}
?>