<?php
include_once "../db/db.php";
$db = new db();
$db->conectar();

$id = $_REQUEST['id'];
$tb = $_REQUEST['tb'];

// Normalizar el nombre de la tabla en MySQL si viene en singular
$tabla_bd = ($tb == 'reporte_baja') ? 'reportes_baja' : (($tb == 'usuario') ? 'usuarios' : $tb);

// Determinar llave primaria según la tabla
$campo_id = ($tabla_bd == 'empresas') ? 'id_empresa' : 
            (($tabla_bd == 'operadores') ? 'id_operador' : 
            (($tabla_bd == 'usuarios') ? 'id_usuario' : 
            (($tabla_bd == 'reportes_baja') ? 'id_reporte' : 'id')));

// 1. Eliminar el registro
$sql = "DELETE FROM $tabla_bd WHERE $campo_id = $id";
$db->eliminar($sql);

// 2. Consultar registros actualizados
$sql = "SELECT * FROM $tabla_bd";
$datos2 = $db->obtenerRegistros($sql);

$db->desconectar();

// 3. Devolver SOLO el fragmento de la tabla
include_once "../$tb/tabla.php";
?>