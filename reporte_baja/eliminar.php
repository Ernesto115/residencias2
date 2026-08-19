<?php
include_once "../db/db.php";
$db = new db();
$db->conectar();

$id = $_REQUEST['id'];
$tb = $_REQUEST['tb']; // Llega 'reporte_baja' desde JS

// 1. Convertir 'reporte_baja' al nombre real de la tabla en MySQL ('reportes_baja')
$tabla_bd = ($tb == 'reporte_baja') ? 'reportes_baja' : $tb;

// 2. Determinar llave primaria según la tabla real
$campo_id = ($tabla_bd == 'empresas') ? 'id_empresa' : 
            (($tabla_bd == 'operadores') ? 'id_operador' : 
            (($tabla_bd == 'reportes_baja') ? 'id_reporte' : 'id'));

// 3. Eliminar el registro usando $tabla_bd
$sql = "DELETE FROM $tabla_bd WHERE $campo_id = $id";
$db->eliminar($sql);

// 4. Consultar registros de la tabla real en MySQL
if ($tabla_bd == 'reportes_baja') {
    $sql = "SELECT r.*, 
                   CONCAT(o.nombre, ' ', o.paterno, ' ', o.materno) AS nombre_operador, 
                   e.nombre_empresa 
            FROM reportes_baja r
            LEFT JOIN operadores o ON r.id_operador = o.id_operador
            LEFT JOIN empresas e ON r.id_empresa = e.id_empresa
            ORDER BY r.id_reporte DESC";
} else {
    $sql = "SELECT * FROM $tabla_bd";
}

$datos2 = $db->obtenerRegistros($sql);

$db->desconectar();

// 5. Incluir la plantilla desde la carpeta del proyecto
include_once "../$tb/tabla.php";
?>