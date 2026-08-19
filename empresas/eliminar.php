<?php
include_once "../db/db.php";
$db = new db();
$db->conectar();

$id = $_REQUEST['id'];
$tb = $_REQUEST['tb'];

// Determinar llave primaria según la tabla
$campo_id = ($tb == 'empresas') ? 'id_empresa' : (($tb == 'operadores') ? 'id_operador' : 'id');

// 1. Eliminar el registro
$sql = "DELETE FROM $tb WHERE $campo_id = $id";
$db->eliminar($sql);

// 2. Consultar registros actualizados
$sql = "SELECT * FROM $tb";
$datos2 = $db->obtenerRegistros($sql);

$db->desconectar();

// 3. Devolver SOLO el fragmento de la tabla
include_once "../$tb/tabla.php";
?>