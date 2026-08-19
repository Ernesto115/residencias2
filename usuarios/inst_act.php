<?php
include_once "../db/db.php";
$db = new db();
$db->conectar();

// 1. Recepción de variables desde el formulario
$id_usuario         = isset($_REQUEST['id_usuario']) ? $_REQUEST['id_usuario'] : '';
$nombre_usuario     = isset($_REQUEST['nombre_usuario']) ? $_REQUEST['nombre_usuario'] : '';
$contrasena         = isset($_REQUEST['contrasena']) ? $_REQUEST['contrasena'] : '';
$rol                = isset($_REQUEST['rol']) ? $_REQUEST['rol'] : '';
$correo_electronico = isset($_REQUEST['correo_electronico']) ? $_REQUEST['correo_electronico'] : '';

// Manejo de id_empresa (permite NULL en BD si viene vacío o no aplica)
$id_empresa_val = (!empty($_REQUEST['id_empresa'])) ? intval($_REQUEST['id_empresa']) : "NULL";

// 2. Proceso de Actualización o Inserción
if (!empty($id_usuario)) {
    // ACTUALIZAR USUARIO
    $sql = "UPDATE usuarios SET 
            nombre_usuario = '$nombre_usuario', 
            rol = '$rol', 
            correo_electronico = '$correo_electronico', 
            id_empresa = $id_empresa_val";

    // Solo actualiza la contraseña si se escribió una nueva
    if (!empty($contrasena)) {
        $sql .= ", contrasena = '$contrasena'";
    }

    $sql .= " WHERE id_usuario = " . intval($id_usuario);

    $db->actualizar($sql);
} else {
    // INSERTAR NUEVO USUARIO
    $sql = "INSERT INTO usuarios (
                nombre_usuario, contrasena, rol, correo_electronico, id_empresa
            ) VALUES (
                '$nombre_usuario', '$contrasena', '$rol', '$correo_electronico', $id_empresa_val
            )";

    $db->insertar($sql);
}

// 3. Consulta de usuarios con JOIN para traer el nombre de la empresa
$sql = "SELECT u.*, e.nombre_empresa, e.razon_social 
        FROM usuarios u 
        LEFT JOIN empresas e ON u.id_empresa = e.id_empresa 
        ORDER BY u.id_usuario DESC";
$datos2 = $db->obtenerRegistros($sql);

$db->desconectar();

// 4. Se renderiza únicamente la tabla para la respuesta AJAX
include_once "../usuarios/tabla.php";
?>