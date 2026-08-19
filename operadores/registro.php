<?php
include_once "../db/db.php";
$db = new db();
$db->conectar();

// Recibimos la consulta enviada desde la función editar()
$sql = isset($_POST['sql']) ? $_POST['sql'] : '';

if (!empty($sql)) {
    $registros = $db->obtenerRegistros($sql);
    
    if (!empty($registros)) {
        // Tomamos el primer registro encontrado
        $registro = $registros[0];
        
        // Si viene como objeto lo convertimos a array para el JSON
        if (is_object($registro)) {
            $registro = get_object_vars($registro);
        }
        
        header('Content-Type: application/json');
        echo json_encode($registro);
        $db->desconectar();
        exit;
    }
}

$db->desconectar();
echo json_encode([]);
?>