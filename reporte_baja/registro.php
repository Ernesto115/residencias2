<?php
include_once "../db/db.php";
$db = new db();
$db->conectar();

// Encabezado para garantizar que la respuesta sea tratada como JSON
header('Content-Type: application/json; charset=utf-8');

// Captura el ID sin importar si tu función universal envía 'id' o 'id_reporte'
$id = 0;
if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
    $id = intval($_REQUEST['id']);
} elseif (isset($_REQUEST['id_reporte']) && !empty($_REQUEST['id_reporte'])) {
    $id = intval($_REQUEST['id_reporte']);
}

if ($id > 0) {
    // Consulta directa a la tabla
    $sql = "SELECT * FROM reportes_baja WHERE id_reporte = $id";
    $datos = $db->obtenerRegistros($sql);

    if (!empty($datos)) {
        // Retorna el registro. Los nombres de las columnas coinciden con los ID de frm.php
        echo json_encode($datos[0]);
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}

$db->desconectar();
exit;
?>