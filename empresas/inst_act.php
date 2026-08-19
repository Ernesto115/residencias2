<?php
include_once "../db/db.php";
$db = new db();
$db->conectar();

// Asignación segura con isset() para evitar Warnings
$id_empresa = isset($_REQUEST['id_empresa']) ? $_REQUEST['id_empresa'] : '';
$nombre_empresa = isset($_REQUEST['nombre_empresa']) ? $_REQUEST['nombre_empresa'] : '';
$razon_social = isset($_REQUEST['razon_social']) ? $_REQUEST['razon_social'] : '';
$direccion_fiscal = isset($_REQUEST['direccion_fiscal']) ? $_REQUEST['direccion_fiscal'] : '';
$responsable = isset($_REQUEST['responsable']) ? $_REQUEST['responsable'] : '';

if (!empty($id_empresa)) {
    // ACTUALIZAR REGISTRO EXISTENTE
    $sql = "UPDATE empresas SET  
            nombre_empresa='$nombre_empresa', 
            razon_social='$razon_social', 
            direccion_fiscal='$direccion_fiscal', 
            responsable='$responsable'
            WHERE id_empresa=$id_empresa";

    $db->actualizar($sql);
} else {
    // INSERTAR NUEVO REGISTRO
    $sql = "INSERT INTO empresas (nombre_empresa, razon_social, direccion_fiscal, responsable)
            VALUES ('$nombre_empresa','$razon_social','$direccion_fiscal','$responsable')";

    $db->insertar($sql);
}

$sql = "SELECT * FROM empresas";
$datos2 = $db->obtenerRegistros($sql);

$db->desconectar();

include_once "../empresas/tabla.php";
?>