<?php 
include_once "../db/db.php";
$ventas = new db();
$ventas->conectar();


// id = $_REQUEST['id'];
$sql = $_REQUEST['sql'];
$datos = $ventas->obtenerRegistros($sql);
$ventas->desconectar();
echo  json_encode($datos[0]);
?>