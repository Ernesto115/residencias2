<?php
include_once "../db/db.php";
$db = new db();
$db->conectar();

// Recepción de parámetros exclusivamente de la tabla reportes_baja
$id_reporte                = isset($_REQUEST['id_reporte']) ? $_REQUEST['id_reporte'] : '';
$id_operador               = isset($_REQUEST['id_operador']) ? intval($_REQUEST['id_operador']) : 0;
$id_empresa                = isset($_REQUEST['id_empresa']) ? intval($_REQUEST['id_empresa']) : 0;
$motivo_baja               = isset($_REQUEST['motivo_baja']) ? addslashes(trim($_REQUEST['motivo_baja'])) : '';
$calificacion_cuantitativa = isset($_REQUEST['calificacion_cuantitativa']) ? intval($_REQUEST['calificacion_cuantitativa']) : 0;

// Recepción directa del comentario cualitativo
$calif_cualitativa         = isset($_REQUEST['calif_cualitativa']) ? addslashes(trim($_REQUEST['calif_cualitativa'])) : '';

// Si el motivo NO es 'OTRO', se limpia la calificación cualitativa para mantener consistencia en la BD
if ($motivo_baja !== 'OTRO') {
    $calif_cualitativa = '';
}

$fecha_registro            = isset($_REQUEST['fecha_registro']) && !empty($_REQUEST['fecha_registro']) 
                             ? $_REQUEST['fecha_registro'] 
                             : date('Y-m-d H:i:s');

if (!empty($id_reporte) && $id_reporte != '0') {
    // 1. Obtener el id_operador anterior antes de actualizar (por si cambió de operador en la edición)
    $sql_anterior = "SELECT id_operador FROM reportes_baja WHERE id_reporte = " . intval($id_reporte);
    $res_anterior = $db->obtenerRegistros($sql_anterior);
    $id_operador_anterior = isset($res_anterior[0]['id_operador']) ? intval($res_anterior[0]['id_operador']) : 0;

    // Actualización de un reporte existente
    $sql_baja = "UPDATE reportes_baja SET 
                    id_operador = $id_operador,
                    id_empresa = $id_empresa,
                    motivo_baja = '$motivo_baja',
                    calificacion_cuantitativa = $calificacion_cuantitativa,
                    calif_cualitativa = '$calif_cualitativa',
                    fecha_registro = '$fecha_registro'
                 WHERE id_reporte = " . intval($id_reporte);
                 
    if ($db->actualizar($sql_baja)) {
        // Si cambió el operador al editar, reactivar el anterior (estatus = 1)
        if ($id_operador_anterior > 0 && $id_operador_anterior != $id_operador) {
            $sql_reactivar = "UPDATE operadores SET estatus = 1 WHERE id_operador = $id_operador_anterior";
            $db->actualizar($sql_reactivar);
        }

        // --- DESACTIVAR AL NUEVO OPERADOR ASIGNADO EN LA TABLA OPERADORES ---
        if ($id_operador > 0) {
            $sql_estatus = "UPDATE operadores SET estatus = 0 WHERE id_operador = $id_operador";
            $db->actualizar($sql_estatus);
        }
    }
} else {
    // Inserción de un nuevo reporte
    $sql_baja = "INSERT INTO reportes_baja (
                    id_operador, 
                    id_empresa, 
                    motivo_baja, 
                    calificacion_cuantitativa, 
                    calif_cualitativa, 
                    fecha_registro
                 ) VALUES (
                    $id_operador, 
                    $id_empresa, 
                    '$motivo_baja', 
                    $calificacion_cuantitativa, 
                    '$calif_cualitativa', 
                    '$fecha_registro'
                 )";
                 
    if ($db->insertar($sql_baja)) {
        // --- DESACTIVAR AL OPERADOR EN LA TABLA OPERADORES ---
        if ($id_operador > 0) {
            $sql_estatus = "UPDATE operadores SET estatus = 0 WHERE id_operador = $id_operador";
            $db->actualizar($sql_estatus);
        }
    }
}

// --- LÓGICA DE PAGINACIÓN PARA LA RECARGA DE LA TABLA ---
$registros_por_pagina = 5;
$pagina_actual = isset($_REQUEST['pagina']) ? (int)$_REQUEST['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}

$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Total de registros
$sql_total = "SELECT COUNT(*) AS total FROM reportes_baja";
$res_total = $db->obtenerRegistros($sql_total);
$total_registros = isset($res_total[0]['total']) ? $res_total[0]['total'] : 0;
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta paginada recargada por AJAX
$sql = "SELECT rb.id_reporte, 
               rb.id_operador, 
               rb.id_empresa, 
               rb.motivo_baja, 
               rb.calificacion_cuantitativa, 
               rb.calif_cualitativa, 
               rb.fecha_registro,
               CONCAT(o.nombres, ' ', o.primer_apellido, ' ', o.segundo_apellido) AS nombre_operador,
               e.nombre_empresa 
        FROM reportes_baja rb
        INNER JOIN operadores o ON rb.id_operador = o.id_operador
        INNER JOIN empresas e ON rb.id_empresa = e.id_empresa
        ORDER BY rb.id_reporte DESC 
        LIMIT $registros_por_pagina OFFSET $offset";

$datos2 = $db->obtenerRegistros($sql);

$db->desconectar();

// Carga directa del componente de tabla con sus datos y paginación
include_once "../reporte_baja/tabla.php";
?>